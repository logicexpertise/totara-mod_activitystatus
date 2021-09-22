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
        $this->newitemid = $DB->insert_record('activitystatus', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($this->newitemid);
    }

    protected function after_execute() {
        global $DB;
        // Add activitystatus related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_activitystatus', 'default_background', null);
        $this->add_related_files('mod_activitystatus', 'default_status', null);
        $this->add_related_files('mod_activitystatus', 'modstatusimages', null);
        $this->add_related_files('mod_activitystatus', 'coursestatusimages', null);

        $oldmodid = $this->task->get_old_activityid();
        $newmodid = $this->task->get_old_moduleid();

        // Get all the activitystatus_displayorder for the parent
        $items = $DB->get_records('activitystatus_displayorder', ['modid'=>$newmodid]);
        $mod = $DB->get_record_sql(
          'select cm.* from {course_modules} cm
          join {modules} m on cm.module = m.id
          where m.name = :name
          and cm.instance = :instance',
          ['name'=>'activitystatus', 'instance' => $this->newitemid]);

        // Write copies, but setting displayorder to 0
        foreach ($items as $item) {
          $item->modid = $mod->id;
          $item->modinstanceid = $this->newitemid;
          $item->displayorder = 0;
          $DB->insert_record('activitystatus_displayorder', $item);
        }
    }

}
