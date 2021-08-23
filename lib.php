<?php

/**
 *
 * @package     mod_activitystatus
 * @subpackage
 * @copyright   2019 Olumuyiwa Taiwo <muyi.taiwo@logicexpertise.com>
 * @author      Olumuyiwa Taiwo {@link https://moodle.org/user/view.php?id=416594}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function activitystatus_supports($feature) {

    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE: return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_NO_VIEW_LINK: return true;
        case FEATURE_BACKUP_MOODLE2: return true;
        case FEATURE_MOD_INTRO: return false;
        case FEATURE_SHOW_DESCRIPTION: return false;

        default: return null;
    }
}

/**
 * Add activitystatus instance.
 * @param object $data
 * @param object $mform
 * @return int new activitystatus instance id
 */
function activitystatus_add_instance($data, $mform) {
    global $DB;
    $course = new stdClass();
    $course->id = $data->course;

    $cmid = $data->coursemodule;
    $data->name = get_string('activitystatustitle', 'mod_activitystatus');
    $data->timemodified = time();
    $data->id = $DB->insert_record('activitystatus', $data);
    // we need to use context now, so we need to make sure all needed info is already in db
    $DB->set_field('course_modules', 'instance', $data->id, ['id' => $cmid]);
    activitystatus_set_image_files($data);
    activitystatus_save_displayorder($data, activitystatus_get_tracked_coursemodules($data), 'mod');
    activitystatus_save_displayorder($data, activitystatus_get_linked_courses($data), 'course');
    return $data->id;
}

function activitystatus_update_instance($data, $mform) {
    global $DB;

    $course = new stdClass();
    $course->id = $data->course;

    $cmid = $data->coursemodule;
    $data->id = $data->instance;
    $data->timemodified = time();
    $DB->update_record('activitystatus', $data);
    activitystatus_set_image_files($data);
    activitystatus_save_displayorder($data, activitystatus_get_tracked_coursemodules($data), 'mod');
    activitystatus_save_displayorder($data, activitystatus_get_linked_courses($data), 'course');
    return true;
}

function activitystatus_delete_instance($id) {
    global $DB;

    $widget = $DB->get_record('activitystatus', array('id' => $id));
    $displayorder = $DB->get_records('activitystatus_displayorder', ['modid' => $id]);
    if (!$widget && !$displayorder) {
        return false;
    }

    $deletewidget = $DB->delete_records('activitystatus', array('id' => $widget->id));
    $deletedisplayorder = $DB->delete_records('activitystatus_displayorder', ['modid' => $id]);

    return ($deletewidget && $deletedisplayorder);
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 *
 * @global object
 * @param object $cm
 * @return cached_cm_info|null
 */
function activitystatus_get_coursemodule_info($cm) {
    global $DB;

    if ($widget = $DB->get_record('activitystatus', array('id' => $cm->instance), 'id, name, intro, introformat')) {
        if (empty($widget->name)) {
            // activitystatus name missing, fix it
            $widget->name = "Activity status {$widget->id}";
            $DB->set_field('activitystatus', 'name', $widget->name, array('id' => $widget->id));
        }
        $info = new cached_cm_info();
        // no filtering here because this info is cached and filtered later
        $info->name = $widget->name;
        return $info;
    } else {
        return null;
    }
}

/**
 * @todo this needs serious cleaning up!
 *
 * @global type $CFG
 * @global type $PAGE
 * @global type $USER
 * @param cm_info $cm
 */
function activitystatus_cm_info_view(cm_info $cm) {
    global $CFG, $PAGE, $USER;
    $course = new stdClass();
    $course->id = $cm->course;

    $modcontext = context_module::instance($cm->id);
    $coursecontext = context_course::instance($course->id);
    // Can edit settings?
    $can_edit = has_capability('moodle/course:update', $coursecontext);

    $content = '';
    $widget = new stdClass();
    // Get course completion data.
    $completioninfo = new completion_info($course);

    // Don't display if completion isn't enabled!
    if (!completion_info::is_enabled_for_site()) {
        if ($can_edit) {
            $content .= get_string('completionnotenabledforsite', 'completion');
        }
    } else if (!$completioninfo->is_enabled()) {
        if ($can_edit) {
            $content .= get_string('completionnotenabledforcourse', 'completion');
        }
    }

    // Load criteria to display.
    $completions = $completioninfo->get_completions($USER->id);

    // Check if this course has any criteria.
    if (empty($completions)) {
        if ($can_edit) {
            $content .= get_string('nocriteriaset', 'completion');
        }
    }

    if ($completioninfo->is_tracked_user($USER->id) || $can_edit) {
        $fs = get_file_storage();
        require_once($CFG->dirroot . '/mod/activitystatus/locallib.php');
        $completiontypes_mods = activitystatus_get_completion_types_mods();
        $completiontypes_courses = activitystatus_get_completion_types_courses();
        $files = $fs->get_area_files($modcontext->id, 'mod_activitystatus', 'default_background', 0, '', false);
        if ($files) {
            $file = array_shift($files);
            $backgroundimageurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
        } else {
            $backgroundimageurl = '';
        }

        $trackedmodules = activitystatus_get_tracked_coursemodules($cm);
        $linkedcourses = activitystatus_get_linked_courses($cm);
        $linked = array_merge($trackedmodules, $linkedcourses);

        $displayorder = activitystatus_load_displayorder($cm);
        $iconsorder = activitystatus_icons_order($displayorder);

        if (empty($iconsorder)) { // Previously existing widget.
            foreach ($linked as $l) {
                $iconsorder[$l->id] = 1; // Updated
            }
        }
        $content .= html_writer::start_div('widgetcontainer', ['style' => 'background-image: url(' . $backgroundimageurl . ');']);
        $content .= html_writer::start_div('widgetcontents');
        foreach ($iconsorder as $key => $order) {
            if ($order < 1) {
              continue;
            }
            $item = activitystatus_get_courseormodule_with_id($linked, $key);
            if (isset($item->module)) {
                $mod = $item;
                if (empty($mod->available)) {
                    $key = 3;
                } else {
                    $completiondata = $completioninfo->get_data($mod, true, $USER->id);
                    $state = $completiondata->completionstate;
                    switch ($state) {
                        case COMPLETION_INCOMPLETE:
                            $key = 0;
                            break;
                        case COMPLETION_COMPLETE:
                        case COMPLETION_COMPLETE_PASS:
                        case COMPLETION_COMPLETE_RPL:
                            $key = 1;
                            break;
                        case COMPLETION_COMPLETE_FAIL:
                            $key = 2;
                            break;
                        default;
                    }
                }
                $status = $completiontypes_mods[$key];
                if (!$files = $fs->get_area_files($modcontext->id, 'mod_activitystatus', 'statusimages', $mod->id . $key, '', false)) {
                    $files = $fs->get_area_files($modcontext->id, 'mod_activitystatus', 'default_status', false, '', false);
                }
                if ($files) {
                    $file = array_shift($files);
                    $statusimageurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
                } else {
                    $statusimageurl = new moodle_url('/mod/activitystatus/pix/status.png');
                }
                $content .= html_writer::start_div('modcontainer');
                $content .= html_writer::link($mod->url, html_writer::div(html_writer::img($statusimageurl, get_string('statusimagealt', 'mod_activitystatus', $status)), 'activitystatus statusimage'));
                $content .= html_writer::end_div();
            } else {
                if (isset($item->id)) { // Course may have been removed from completion criteria.
                    $course = $item;
                    $completion = new completion_completion(array('userid' => $USER->id, 'course' => $course->id));
                    $key = $completion->status ? $completion->status : COMPLETION_STATUS_NOTYETSTARTED;
                    $status = $completiontypes_courses[$key];
                    if (!$files = $fs->get_area_files($modcontext->id, 'mod_activitystatus', 'statusimages', $course->id . $key, '', false)) {
                        $files = $fs->get_area_files($modcontext->id, 'mod_activitystatus', 'default_status', 0, '', false);
                    }
                    if ($files) {
                        $file = array_shift($files);
                        $statusimageurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
                    } else {
                        $statusimageurl = new moodle_url('/mod/activitystatus/pix/status.png');
                    }
                    $content .= html_writer::start_div('modcontainer');
                    $courseurl = new moodle_url('/course/view.php', ['id' => $course->id]);
                    $content .= html_writer::link($courseurl, html_writer::div(html_writer::img($statusimageurl, get_string('statusimagealt', 'mod_activitystatus', $status)), 'activitystatus statusimage'));
                    $content .= html_writer::end_div();
                }
            }
        }
        $content .= html_writer::end_div();
        $content .= html_writer::end_div();
    } else {
        // If user is not enrolled, show error.
        $content .= get_string('nottracked', 'completion');
    }

    $cm->set_content($content);
}

/**
 * Sets dynamic information about a course module
 *
 * This function is called from cm_info when displaying the module
 * mod_folder can be displayed inline on course page and therefore have no course link
 *
 * @param cm_info $cm
 */
function activitystatus_cm_info_dynamic(cm_info $cm) {
    // This module has no 'view' link
    $cm->set_no_view_link();
}

function activitystatus_pluginfile($course, $cm, context $context, $filearea, $args, $forcedownload) {

    require_course_login($course, true, $cm);
    if (!has_capability('mod/activitystatus:view', $context)) {
        return false;
    }

    $itemid = array_shift($args);
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // $args is empty => the path is '/'
    } else {
        $filepath = '/' . implode('/', $args) . '/'; // $args contains elements of the filepath
    }
    $fs = get_file_storage();

    $userfile = $fs->get_file($context->id, 'mod_activitystatus', $filearea, $itemid, $filepath, $filename);
    if (!$userfile) {
        return false;
    }

    // Serve file
    send_stored_file($userfile);
}
