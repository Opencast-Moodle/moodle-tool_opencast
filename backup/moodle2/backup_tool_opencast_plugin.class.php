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

        // Define root of backup structure
        $opencast = new backup_nested_element('opencast');
        $plugin->add_child($opencast);

        $sitedata = [];
        $eventlist = [];
        $serieslist = [];
        $importdata = (object)['sourcecourseid' => $courseid];

        // EVENTS information.
        $events = new backup_nested_element('events');
        $event = new backup_nested_element('event', [], ['eventid', 'instanceid']);
        $events->add_child($event);
        $opencast->add_child($events);

        // SERIES import information.
        $import = new backup_nested_element('import', [], ['sourcecourseid']);
        $serieselement = new backup_nested_element('series', [], ['seriesid', 'instanceid']);
        $import->add_child($serieselement);
        $opencast->add_child($import);


        // Handle each Opencast instance
        foreach($ocinstances as $ocinstance) {

            // // Define root of backup structure
            // $opencast = new backup_nested_element('opencast_' . $ocinstanceid, [], ['ocinstanceid']);
            // $plugin->add_child($opencast);

            // $series_array = $apibridge->get_course_series($courseid);
            // foreach($series_array as $series) {
            //     $seriesid = $series->series;
            //     echo $seriesid;
            // }

            $ocinstanceid = $ocinstance->id;

            $apibridge = apibridge::get_instance($ocinstanceid);

            $apiurl = settings_api::get_apiurl($ocinstanceid);
            $sitedata[] = (object)[
                'ocinstanceid' => $ocinstanceid,
                'apiurl' => $apiurl,
            ];

            $coursevideos = [];
            // If config is set we only want to backup the videos that are used in the course.
            $only_backup_usedvideos = get_config('tool_opencast', 'importreducedduplication_' . $ocinstanceid);
            if($only_backup_usedvideos) {
                $coursevideos = $apibridge->get_used_course_videos_for_backup($courseid);
            } else {
                $coursevideos = $apibridge->get_course_videos_for_backup($courseid);
            }

            // Add course videos.
            foreach ($coursevideos as $video) {
                $eventlist[] = (object)[
                    'eventid' => $video->identifier,
                    'instanceid' => $ocinstanceid,
                ];
            }

            // Get the stored seriesid for this course.
            $courseseries = $apibridge->get_course_series($courseid);

            foreach ($courseseries as $series) {
                $serieslist[] = (object)[
                    'seriesid' => $series->series,
                    'instanceid' => $ocinstanceid
                ];
            }
        }

        echo 'Series: ' . print_r($serieslist, true) . PHP_EOL;
        echo 'Events: ' . print_r($eventlist, true) . PHP_EOL;
        echo 'Events: ' . print_r($sitedata, true) . PHP_EOL;

        // Define sources.
        $event->set_source_array($eventlist);
        $serieselement->set_source_array($serieslist);
        $import->set_source_array([$importdata]);
        $site->set_source_array($sitedata);

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