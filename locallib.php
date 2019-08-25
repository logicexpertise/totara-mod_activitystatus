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

function activitystatus_set_image_files($data) {
    global $COURSE;
    $cmid = $data->coursemodule;
    $coursecontext = context_course::instance($COURSE->id);
    $modcontext = context_module::instance($cmid);
    $elements = activitystatus_background_elements();
    foreach ($elements as $el) {
        file_save_draft_area_files($data->$el, $modcontext->id, 'mod_activitystatus', $el, 0, ['subdirs' => false]);
    }

    $completiontypes_courses = activitystatus_get_completion_types_courses();
    $linkedcourses = activitystatus_get_linked_courses($COURSE);
    foreach ($linkedcourses as $course) {
        $tracked = new stdClass();
        $tracked->courseormodid = $course->id;
        $tracked->type = 'course';
        foreach ($completiontypes_courses as $key => $type) {
            $el = "courseimagefile_$course->id" . "_$key";
            $tracked->$el = $data->$el;
            file_save_draft_area_files($data->$el, $modcontext->id, 'mod_activitystatus', 'coursestatusimages', $course->id . $key, ['subdirs' => false]);
        }
    }

    $completiontypes_mods = activitystatus_get_completion_types_mods();
    $trackedmodules = activitystatus_get_tracked_coursemodules($COURSE);
    foreach ($trackedmodules as $cm) {
        $tracked = new stdClass();
        $tracked->courseormodid = $cm->id;
        $tracked->type = 'mod';
        foreach ($completiontypes_mods as $key => $type) {
            $el = 'modimagefile_' . $cm->id . '_' . $key;
            $tracked->$el = $data->$el;
            file_save_draft_area_files($data->$el, $modcontext->id, 'mod_activitystatus', 'modstatusimages', $cm->id . $key, ['subdirs' => false]);
        }
    }
}

function activitystatus_get_tracked_coursemodules($course) {
    $trackedmodules = [];
    $modinfo = get_fast_modinfo($course);
    foreach ($modinfo->cms as $cm) {
        if ($cm->completion != COMPLETION_TRACKING_NONE) {
            $trackedmodules[] = $cm;
        }
    }
    return $trackedmodules;
}

function activitystatus_get_linked_courses($course) {
    $courses = [];
    $completion = new completion_info($course);
    foreach ($completion->get_criteria(COMPLETION_CRITERIA_TYPE_COURSE) as $criterion) {
        $courses[] = get_fast_modinfo($criterion->courseinstance)->get_course();
    }
    return $courses;
}

function activitystatus_get_completion_types_mods() {
    $completiontypes = [
        0 => 'incomplete',
        1 => 'complete',
        2 => 'complete but failed',
        3 => 'restricted',
    ];

    return $completiontypes;
}

function activitystatus_get_completion_types_courses() {
    $completiontypes = array(
        // Use core course completion status because they're convenient
        COMPLETION_STATUS_NOTYETSTARTED => 'not yet started',
        COMPLETION_STATUS_INPROGRESS => 'in progress',
        COMPLETION_STATUS_COMPLETE => 'complete',
        COMPLETION_STATUS_COMPLETEVIARPL => 'complete via rpl',
    );
    return $completiontypes;
}

function activitystatus_background_elements() {
    $elements = ['default_background',
        'default_status',
    ];
    return $elements;
}
