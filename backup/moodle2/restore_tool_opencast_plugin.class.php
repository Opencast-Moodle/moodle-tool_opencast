<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Handle the course backup.
 *
 * @package    tool_opencast
 * @copyright  2025 Berthold Bußkamp, ssystems GmbH <bbusskamp@ssystems.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_opencast\local\settings_api;
use tool_opencast\local\apibridge;


defined('MOODLE_INTERNAL') || die();

global $CFG, $DB;

require_once($CFG->dirroot . '/backup/moodle2/restore_tool_plugin.class.php');

class restore_tool_opencast_plugin extends restore_tool_plugin {

    protected $series = [];
    protected $importmode;

    protected function define_structure() {

    }

    protected function define_course_plugin_structure() {

        echo "Hello, restore structure!";

        global $USER;
        // $ocinstanceid = intval(ltrim($this->get_name(), "opencast_structure_"));
        // $this->ocinstanceid = $ocinstanceid;

        // // Check, target series.
        // $courseid = $this->get_courseid();

        // // Generate restore unique identifier,
        // // to keep track of restore session in later stages e.g. module mapping and repair.
        // $this->restoreuniqueid = uniqid('oc_restore_' . $ocinstanceid . '_' . $courseid);

        $paths = [];

        // Get instace ids
        $ocinstances = settings_api::get_ocinstances();

        // Get course id
        $contextid = $this->task->get_contextid();
        $context = \core\context::instance_by_id($contextid);
        $courseid = $context->instanceid;

        // Handle each Opencast instance
        foreach($ocinstances as $ocinstance) {
            $ocinstanceid = $ocinstance->id;

            // Get apibridge instance.
            $apibridge = apibridge::get_instance($ocinstanceid);

            // Get the import mode to decide the way of importing opencast videos.
            $importmode = get_config('tool_opencast', 'importmode_' . $ocinstanceid);
            $this->importmode = $importmode;

            // If ACL Change is the mode.
            if ($importmode == 'acl') {
                // Processing series, grouped by import.
                $paths[] = new restore_path_element('import', $this->connectionpoint->get_path() . '/opencast/import', true);
                $paths[] = new restore_path_element('series', $this->connectionpoint->get_path() . '/opencast/import/series');
            } else if ($importmode == 'duplication') {
                // In case Duplicating Events is the mode.

                // Get series id.
                $seriesid = $apibridge->get_stored_seriesid($courseid, true, $USER->id);

                // If seriesid does not exist, we create one.
                if (!$seriesid) {
                    // Make sure to create using another method.
                    $seriesid = $apibridge->create_course_series($courseid, null, $USER->id);
                }
                $this->series[] = $seriesid;

                // Processing events, grouped by main opencast, in order to get series as well.
                $paths[] = new restore_path_element('opencast', $this->connectionpoint->get_path()  . '/opencast', true);
                $paths[] = new restore_path_element('events', $this->connectionpoint->get_path() . '/opencast/events');
                $paths[] = new restore_path_element('event', $this->connectionpoint->get_path() . '/opencast/events/event');

                $paths[] = new restore_path_element('site', $this->connectionpoint->get_path()  . '/site', true);

                // Adding import property here, to access series.
                $paths[] = new restore_path_element('import', $this->connectionpoint->get_path() . '/opencast/import');
                $paths[] = new restore_path_element('series', $this->connectionpoint->get_path() . '/opencast/import/series');
            }

        }
        return $paths;
    }

    public function process_import($data) {

        echo "Hello, restore import!";
        echo('Data: ' . print_r($data, true) . PHP_EOL);


    }

    public function process_opencast($data) {

        $data = (object) $data;

        // Check if all required information is available.
        if (empty($this->series) || !isset($data->import) || !isset($data->events) ||
            empty($data->import[0]['series']) || empty($data->events['event'])) {
            // Nothing to do here, as the data is not enough.
            echo "No data to process!" . PHP_EOL;
            echo('Data: ' . print_r($data, true) . PHP_EOL);
            return;
        }

        echo "Hello, restore opencat!";
        echo('Data: ' . print_r($data, true) . PHP_EOL);

    }

    public function process_site($data) {

        echo "Hello, restore site!";
        echo('Data: ' . print_r($data, true) . PHP_EOL);

    }

    public function after_restore() {

        echo "Hello, after restore!";
        echo('Data: ' . print_r($data, true) . PHP_EOL);

    }

}