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
 * Define the complete activitystatus structure for backup, with file and id annotations
 */
class backup_activitystatus_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $activitystatus = new backup_nested_element(
                'activitystatus', [
            'id',
                ], [
            'course',
            'name',
            'intro',
            'introformat',
            'timemodified',
                ],
        );

        // Define sources
        $activitystatus->set_source_table('activitystatus', array('id' => backup::VAR_ACTIVITYID));
        // Define file annotations
        $activitystatus->annotate_files('mod_activitystatus', 'default_background', null);
        $activitystatus->annotate_files('mod_activitystatus', 'default_status', null);
        $activitystatus->annotate_files('mod_activitystatus', 'modstatusimages', null, $this->get_task()->get_contextid());
        $activitystatus->annotate_files('mod_activitystatus', 'coursestatusimages', null, $this->get_task()->get_contextid());
        // Return the root element (activitystatus), wrapped into standard activity structure
        return $this->prepare_activity_structure($activitystatus);
    }

}
