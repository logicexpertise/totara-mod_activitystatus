<?php
/**
 *
 * @package
 * @subpackage
 * @copyright   2019 Olumuyiwa Taiwo <muyi.taiwo@logicexpertise.com>
 * @author      Olumuyiwa Taiwo {@link https://moodle.org/user/view.php?id=416594}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");

$id = optional_param('id', 0, PARAM_INT);    // Course Module ID, or
$wid = optional_param('wid', 0, PARAM_INT);     // Activity status widget ID

if ($id) {
    $PAGE->set_url('/mod/activitystatus/index.php', array('id' => $id));
    if (!$cm = get_coursemodule_from_id('activitystatus', $id)) {
        print_error('invalidcoursemodule');
    }

    if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
        print_error('coursemisconf');
    }

    if (!$widget = $DB->get_record("label", array("id" => $cm->instance))) {
        print_error('invalidcoursemodule');
    }
} else {
    $PAGE->set_url('/mod/activitystatus/index.php', array('l' => $wid));
    if (!$widget = $DB->get_record("activitystatus", array("id" => $wid))) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record("course", array("id" => $widget->course))) {
        print_error('coursemisconf');
    }
    if (!$cm = get_coursemodule_from_instance("activitystatus", $widget->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
}

require_login($course, true, $cm);

redirect("$CFG->wwwroot/course/view.php?id=$course->id");
