<?php

/**
 *
 * @package
 * @subpackage
 * @copyright   2019 Olumuyiwa Taiwo <muyi.taiwo@logicexpertise.com>
 * @author      Olumuyiwa Taiwo {@link https://moodle.org/user/view.php?id=416594}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/mod/activitystatus/locallib.php');

class mod_activitystatus_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG, $COURSE;

        $settingsurl = '/mod/activitystatus/statusimagesettings.php';
        $mform = $this->_form;

        $filemanageroptions = [
            'subdirs' => 0,
            'maxbytes' => get_max_upload_file_size($CFG->maxbytes, $COURSE->maxbytes),
            'maxfiles' => 1,
            'accepted_types' => [
                'web_image'
            ],
        ];

        $mform->addElement('header', 'statusimagesettings', get_string('statusimagesettings', 'mod_activitystatus'));

        $mform->addElement('filemanager', 'default_background', get_string('backgroundimage', 'mod_activitystatus'), null, $filemanageroptions);
        $mform->addElement('filemanager', 'default_status', get_string('defaultstatusimage', 'mod_activitystatus'), null, $filemanageroptions);

        $mform->addElement('html', html_writer::start_div('statusimagesettings'));
        $trackedmodules = activitystatus_get_tracked_coursemodules($COURSE);
        if (!empty($trackedmodules)) {
            $mform->addElement('html', html_writer::start_div('modsheader'));
            $mform->addElement('static', 'modulesactivities', get_string('coursemodules', 'mod_activitystatus'));
            $mform->addElement('html', html_writer::end_div());
            $mform->addElement('html', html_writer::start_div('mods'));
            $completiontypes_mods = activitystatus_get_completion_types_mods();
            foreach ($trackedmodules as $cm) {
                $mform->addElement('html', html_writer::start_div('modinfo'));
                $mform->addElement('html', html_writer::div($cm->name, 'modname'));
                // Add filemanager elements, one per image
                foreach ($completiontypes_mods as $key => $type) {
                    $mform->addElement('html', html_writer::start_div('imagefile'));
                    $mform->addElement('filemanager', 'modimagefile_' . $cm->id . '_' . $key, get_string('completionimagemod', 'mod_activitystatus', $type), null, $filemanageroptions);
                    $mform->addElement('html', html_writer::end_div());
                }
                $mform->addElement('html', html_writer::end_div());
            }
            $mform->addElement('html', html_writer::end_div());
        }

        $linkedcourses = activitystatus_get_linked_courses($COURSE);
        if (!empty($linkedcourses)) {
            $mform->addElement('html', html_writer::start_div('modsheader'));
            $mform->addElement('static', 'linkedcourses', get_string('linkedcourses', 'mod_activitystatus'));
            $mform->addElement('html', html_writer::end_div());
            $completiontypes_courses = activitystatus_get_completion_types_courses();
            $mform->addElement('html', html_writer::start_div('courses'));
            foreach ($linkedcourses as $course) {
                $mform->addElement('html', html_writer::start_div('modinfo'));
                $mform->addElement('html', html_writer::div($course->fullname, 'modname'));
                foreach ($completiontypes_courses as $key => $type) {
                    $mform->addElement('html', html_writer::start_div('imagefile'));
                    $mform->addElement('filemanager', 'courseimagefile_' . $course->id . '_' . $key, get_string('completionimagecourse', 'mod_activitystatus', $type), null, $filemanageroptions);
                     $mform->addElement('html', html_writer::end_div());
                }
                $mform->addElement('html', html_writer::end_div());
            }
            $mform->addElement('html', html_writer::end_div());
        }
        $mform->addElement('html', html_writer::end_div());
        //-------------------------------------------------------
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------
        $this->add_action_buttons();
    }

    public function definition_after_data() {
        $mform = $this->_form;

        if ($mform->elementExists('completion')) {
            $mform->setDefault('completion', COMPLETION_TRACKING_NONE);
        }
        parent::definition_after_data();
    }

    public function data_preprocessing(&$default_values) {
        global $COURSE;
        // Editing current instance - copy existing files into draft area
        if ($this->current->instance) {
            $elements = activitystatus_background_elements();
            foreach ($elements as $el) {
                $draftitemid = file_get_submitted_draft_itemid($el);
                file_prepare_draft_area($draftitemid, $this->context->id, 'mod_activitystatus', $el, 0, ['subdirs' => false]);
                $default_values[$el] = $draftitemid;
            }

            $trackedmodules = activitystatus_get_tracked_coursemodules($COURSE);
            if (!empty($trackedmodules)) {
                $completiontypes_mods = activitystatus_get_completion_types_mods();
                foreach ($trackedmodules as $cm) {
                    foreach ($completiontypes_mods as $key => $type) {
                        $el = 'modimagefile_' . $cm->id . '_' . $key;
                        $draftitemid = file_get_submitted_draft_itemid($el);
                        file_prepare_draft_area($draftitemid, $this->context->id, 'mod_activitystatus', 'modstatusimages', $cm->id . $key, ['subdirs' => false]);
                        $default_values[$el] = $draftitemid;
                    }
                }
            }

            $linkedcourses = activitystatus_get_linked_courses($COURSE);
            if (!empty($linkedcourses)) {
                $completiontypes_courses = activitystatus_get_completion_types_courses();
                foreach ($linkedcourses as $course) {
                    foreach ($completiontypes_courses as $key => $type) {
                        $el = 'courseimagefile_' . $course->id . '_' . $key;
                        $draftitemid = file_get_submitted_draft_itemid($el);
                        file_prepare_draft_area($draftitemid, $this->context->id, 'mod_activitystatus', 'coursestatusimages', $course->id . $key, ['subdirs' => false]);
                        $default_values[$el] = $draftitemid;
                    }
                }
            }
        }
    }

}
