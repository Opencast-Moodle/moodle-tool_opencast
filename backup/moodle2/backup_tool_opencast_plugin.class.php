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

require_once($CFG->dirroot . '/backup/moodle2/backup_tool_plugin.class.php');

class backup_tool_opencast_plugin extends backup_tool_plugin {

    protected function define_course_plugin_structure() {
        echo "Hello, backup!";
        $plugin = $this->get_plugin_element();

        // Get instace ids
        $ocinstances = settings_api::get_ocinstances();

        // Get course ids
        $contextid = $this->task->get_contextid();
        $context = \core\context::instance_by_id($contextid);
        $courseid = $context->instanceid;

        // SITE information.
        $site = new backup_nested_element('site', [], ['ocinstanceid', 'apiurl']);
        $plugin->add_child($site);
        $sitedata = [];

        // Handle each Opencast instance
        foreach($ocinstances as $ocinstance) {
            $ocinstanceid = $ocinstance->id;

            // // Define root of backup structure
            // $opencast = new backup_nested_element('opencast_' . $ocinstanceid, [], ['ocinstanceid']);
            // $plugin->add_child($opencast);

            $apibridge = apibridge::get_instance($ocinstanceid);
            // $series_array = $apibridge->get_course_series($courseid);
            // foreach($series_array as $series) {
            //     $seriesid = $series->series;
            //     echo $seriesid;
            // }

            $opencast = new backup_nested_element('opencast_' . $ocinstanceid);
            $plugin->add_child($opencast);

            $apiurl = settings_api::get_apiurl($ocinstanceid);
            $sitedata[] = (object)[
                'ocinstanceid' => $ocinstanceid,
                'apiurl' => $apiurl,
            ];

            // EVENTS information.
            $events = new backup_nested_element('events');
            $event = new backup_nested_element('event', [], ['eventid']);
            $events->add_child($event);
            $opencast->add_child($events); //Hier knallts

            $coursevideos = $apibridge->get_course_videos_for_backup($courseid);

            $list = [];
            // Add course videos.
            foreach ($coursevideos as $video) {
                $list[] = (object)['eventid' => $video->identifier];
            }

            // Define sources.
            $event->set_source_array($list);


            // SERIES import information.
            $import = new backup_nested_element('import', [], ['sourcecourseid']);
            $serieselement = new backup_nested_element('series', [], ['seriesid']);
            $import->add_child($serieselement);
            $opencast->add_child($import);

            // Get the stored seriesid for this course.
            $courseseries = $apibridge->get_course_series($courseid);

            $list = [];
            foreach ($courseseries as $series) {
                $list[] = (object)['seriesid' => $series->series];
            }
            $serieselement->set_source_array($list);

            $importdata = (object)[
                'sourcecourseid' => $courseid,
            ];

            $import->set_source_array([$importdata]);

            echo 'Series: ' . print_r($importdata, true) . PHP_EOL;
            echo 'Events: ' . print_r($list, true) . PHP_EOL;

        }

        $site->set_source_array([$sitedata]);

        // $this->step = new backup_opencast_block_structure_step('opencast_structure', 'opencast_structure');
        // $this->step->set_path('/opencast_structure');
        // $this->step->set_task($this->task);
        // $this->step->set_contextid($this->task->get_contextid());
        // $this->step->set_plugin($this);
        // $this->step->set_plugin_name('opencast');
        // $this->step->set_plugin_type('block');
        // $this->step->set_plugin_id($this->task->get_contextid());
        // $this->step->set_plugin_type('block');
        // $this->step->set_plugin_name('opencast');

        // $plugin = $this->get_plugin_element();
        $this->step->log('Yay, backup!', backup::LOG_DEBUG);
        // Return the root element ($opencast)
        return $plugin;
    }

}