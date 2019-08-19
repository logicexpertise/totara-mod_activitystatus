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
require_once("lib.php");

$id = required_param('id',PARAM_INT);   // course

$PAGE->set_url('/mod/activitystatus/index.php', array('id'=>$id));

redirect("$CFG->wwwroot/course/view.php?id=$id");