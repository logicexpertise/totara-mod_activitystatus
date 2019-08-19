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

class restore_activitystatus_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');
        // Define each element separated.
        $paths[] = new restore_path_element('activitystatus', '/activity/activitystatus');
        
        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_activitystatus($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();

        // insert the activitystatus record
        $newitemid = $DB->insert_record('activitystatus', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }
    
    protected function after_execute() {
        // Add activitystatus related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_activitystatus', 'default_background', null);
        $this->add_related_files('mod_activitystatus', 'default_status', null);
        $this->add_related_files('mod_activitystatus', 'statusimages', null);
        
        $oldmodid = $this->task->get_old_activityid();
        $newmodid = $this->task->get_old_moduleid();
        
        var_dump($oldmodid, $newmodid); die();
    }

}
