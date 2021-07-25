<?php
/**
 *
 * @package
 * @subpackage
 * @copyright   2019 Olumuyiwa Taiwo <muyi.taiwo@logicexpertise.com>
 * @author      Olumuyiwa Taiwo {@link https://moodle.org/user/view.php?id=416594}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
require_once($CFG->dirroot . '/mod/activitystatus/backup/moodle2/restore_activitystatus_stepslib.php');

class restore_activitystatus_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Choice only has one structure step
        $this->add_step(new restore_activitystatus_activity_structure_step('activitystatus_structure', 'activitystatus.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('activitystatus', array('intro'), 'activitystatus');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder.
     *
     * @return array of restore_decode_rule
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('ACTIVITYSTATUSVIEWBYID',
                '/mod/activitystatus/view.php?id=$1',
                'course_module');
        $rules[] = new restore_decode_rule('ACTIVITYSTATUSINDEX',
                '/mod/activitystatus/index.php?id=$1',
                'course_module');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * activitystatus logs. It must return one array
     * of {@link restore_log_rule} objects.
     *
     * @return array of restore_log_rule
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('activitystatus', 'add', 'view.php?id={course_module}', '{activitystatus}');
        $rules[] = new restore_log_rule('activitystatus', 'view', 'view.php?id={course_module}', '{activitystatus}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     *
     * @return array
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        return $rules;
    }

    /**
     * Getter for activitystatus plugins.
     *
     * @return int
     */
    public function get_old_moduleid() {
        return $this->oldmoduleid;
    }

}
