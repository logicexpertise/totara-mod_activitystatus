<?php

/**
 *
 * @package
 * @subpackage
 * @copyright   2019 Olumuyiwa Taiwo <muyi.taiwo@logicexpertise.com>
 * @author      Olumuyiwa Taiwo {@link https://moodle.org/user/view.php?id=416594}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function xmldb_activitystatus_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2019051600) {

        // Define field course to be added to activitystatus.
        $table = new xmldb_table('activitystatus');
        $field = new xmldb_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1', 'id');

        // Conditionally launch add field course.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Activitystatus savepoint reached.
        upgrade_mod_savepoint(true, 2019051600, 'activitystatus');
    }


    return true;
}
