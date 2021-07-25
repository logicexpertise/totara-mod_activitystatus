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

/**
 * Course completion status constants
 */
define('COMPLETION_STATUS_NOTYETSTARTED', 10);
define('COMPLETION_STATUS_INPROGRESS', 25);
define('COMPLETION_STATUS_COMPLETE', 50);
define('COMPLETION_STATUS_COMPLETEVIARPL', 75);

function activitystatus_set_image_files($data) {
    $course = new stdClass();
    $course->id = $data->course;
    $cmid = $data->coursemodule;
    $modcontext = context_module::instance($cmid);
    $elements = activitystatus_background_elements();
    foreach ($elements as $el) {
        file_save_draft_area_files($data->$el, $modcontext->id, 'mod_activitystatus', $el, 0, ['subdirs' => false]);
    }

    $completiontypes_courses = activitystatus_get_completion_types_courses();
    $linkedcourses = activitystatus_get_linked_courses($data);
    foreach ($linkedcourses as $linkedcourse) {
        $tracked = new stdClass();
        $tracked->courseormodid = $linkedcourse->id;
        $tracked->type = 'course';
        foreach ($completiontypes_courses as $key => $type) {
            $el = "courseimagefile_$linkedcourse->id" . "_$key";
            $tracked->$el = $data->$el;
            file_save_draft_area_files($data->$el, $modcontext->id, 'mod_activitystatus', 'coursestatusimages', $linkedcourse->id . $key, ['subdirs' => false]);
        }
    }

    $completiontypes_mods = activitystatus_get_completion_types_mods();
    $trackedmodules = activitystatus_get_tracked_coursemodules($data);
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

function activitystatus_get_tracked_coursemodules($data) {
    $course = new stdClass();
    $course->id = $data->course;
    $trackedmodules = [];
    $modinfo = get_fast_modinfo($course);
    foreach ($modinfo->cms as $trackedcm) {
        if ($trackedcm->completion != COMPLETION_TRACKING_NONE) {
            $trackedmodules[] = $trackedcm;
        }
    }
    return $trackedmodules;
}

function activitystatus_get_linked_courses($data) {
    $course = new stdClass();
    $course->id = $data->course;

    $courses = [];
    $completion = new completion_info($course);
    foreach ($completion->get_criteria(COMPLETION_CRITERIA_TYPE_COURSE) as $criterion) {
        $linkedcourse = get_fast_modinfo($criterion->courseinstance)->get_course();
        $courses[] = $linkedcourse; // get_fast_modinfo($criterion->courseinstance)->get_course();
    }
    return $courses;
}

function activitystatus_save_displayorder($data, $trackedmodsorcourses, $type) {
    global $DB;
    foreach ($trackedmodsorcourses as $cm) {
        $params = [
            'modid' => $data->coursemodule,
            'modinstanceid' => $data->instance,
            'courseormodid' => $cm->id,
            'itemtype' => $type,
        ];
        if (false !== $pos = $DB->get_field('activitystatus_displayorder', 'displayorder', $params)) {
            // Update.
            $DB->set_field('activitystatus_displayorder', 'displayorder', $data->{"displayorder_" . $type . "_$cm->id"}, $params);
        } else {
            // Insert.
            $record = new \stdClass();
            $record->modid = $data->coursemodule;
            $record->modinstanceid = $data->instance;
            $record->courseormodid = $cm->id;
            $record->itemtype = $type;
            $record->displayorder = $data->{"displayorder_" . $type . "_$cm->id"};
            $DB->insert_record('activitystatus_displayorder', $record);
        }
    }
}

function activitystatus_load_displayorder($cm_data) {
    global $DB;
    return $DB->get_records('activitystatus_displayorder', ['modid' => $cm_data->id, 'modinstanceid' => $cm_data->instance]);
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
        // Using core course completion status because they're convenient.
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

function activitystatus_get_displayorder($array, $type, $id) {
    foreach ($array as $item) {
        if ($item->itemtype == $type && $item->courseormodid == $id) {
            return $item->displayorder;
        }
    }
    return 0;
}

function activitystatus_icons_order($displayorder) {
    $order = array_column(
            json_decode(
                    json_encode($displayorder), true
            ), 'displayorder', 'courseormodid'
    );
    // Sort if non-zero positions
    if (!empty(array_filter($order))) {
        asort($order);
    }
    return $order;
}

function activitystatus_get_courseormodule_with_id($objects, $id) {
    $item = array_filter($objects, function($e) use ($id) {
        return $e->id == $id;
    }); // Array containing 1 object
    return array_shift($item);
}
