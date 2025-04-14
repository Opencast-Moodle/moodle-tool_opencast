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
use tool_opencast\local\event;
use tool_opencast\local\notifications;
use tool_opencast\local\importvideosmanager;


defined('MOODLE_INTERNAL') || die();

global $CFG, $DB;

require_once($CFG->dirroot . '/backup/moodle2/restore_tool_plugin.class.php');

class restore_tool_opencast_plugin extends restore_tool_plugin {

    protected $series = [];
    protected $importmode;
    protected $missingeventids = [];
    protected $missingimportmappingeventids = [];
    protected $missingimportmappingseriesids = [];
    protected $backupeventids = [];
    protected $restoreuniqueid;
    protected $sourcecourseid;
    protected $aclchanged = [];
    protected $instanceid_skip = [];


    protected function define_course_plugin_structure() {

        echo "Hello, restore structure!";

        global $USER;

        $paths = [];

        // Get instace ids
        $ocinstances = settings_api::get_ocinstances();

        // Get course id
        $contextid = $this->task->get_contextid();
        $context = \core\context::instance_by_id($contextid);
        $courseid = $context->instanceid;

        // Generate restore unique identifier,
        // to keep track of restore session in later stages e.g. module mapping and repair.
        $this->restoreuniqueid = uniqid('oc_restore_' . $courseid);


        $paths[] = new restore_path_element('site', $this->connectionpoint->get_path() . '/site');



        // Processing events, grouped by main opencast, in order to get series as well.
        $paths[] = new restore_path_element('opencast', $this->connectionpoint->get_path()  . '/opencast', true);
        $paths[] = new restore_path_element('events', $this->connectionpoint->get_path() . '/opencast/events');
        $paths[] = new restore_path_element('event', $this->connectionpoint->get_path() . '/opencast/events/event');


        // Adding import property here, to access series.
        $paths[] = new restore_path_element('import', $this->connectionpoint->get_path() . '/opencast/import');
        $paths[] = new restore_path_element('series', $this->connectionpoint->get_path() . '/opencast/import/series');

        return $paths;
    }

    public function process_opencast($data) {

        global $USER;

        $data = (object) $data;

        echo "Hello, restore opencat!";
        echo('Data: ' . print_r($data, true) . PHP_EOL);


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
            echo "Hello, restore instanceid: " . $ocinstanceid . PHP_EOL;

            // Check against skip list
            if(in_array($ocinstanceid, $this->instanceid_skip)) {
                // Skip instance id, to avoid restoring into wrong instance.
                continue;
            }

            // Get apibridge instance.
            $apibridge = apibridge::get_instance($ocinstanceid);

            // Get the import mode to decide the way of importing opencast videos.
            $importmode = get_config('tool_opencast', 'importmode_' . $ocinstanceid);
            $this->importmode = $importmode;


            // If ACL Change is the mode.
            if ($importmode == 'acl') {

                echo("Source course id: " . $data->import[0]["sourcecourseid"] . PHP_EOL);

                // Collect sourcecourseid for further processing.
                $this->sourcecourseid = $data->import[0]["sourcecourseid"];

                // First level checker.
                // Exit when the course by any chance wanted to restore itself.
                if (!empty($this->sourcecourseid) && $courseid == $this->sourcecourseid) {
                    return;
                }

                // Get apibridge instance, to ensure series validity and edit series mapping.
                $apibridge = apibridge::get_instance($ocinstanceid);

                if (isset($data->import[0]['series'][0])) {
                    foreach ($data->import[0]['series'] as $series) {
                        $seriesid = $series['seriesid'];

                        // Second level checker.
                        // Exit when there is no original series, or the series is invalid.
                        if (empty($seriesid) || !$apibridge->ensure_series_is_valid($seriesid)) {
                            continue;
                        }

                        // Collect series id for notifications.
                        $this->series[$ocinstanceid] = $seriesid;

                        // Assign Seriesid to new course and change ACL.
                        $this->aclchanged[] = $apibridge->import_series_to_course_with_acl_change($courseid, $seriesid, $USER->id);
                    }
                }


            } else if ($importmode == 'duplication') {

                // Get series id.
                $seriesid = $apibridge->get_stored_seriesid($courseid, true, $USER->id);

                // If seriesid does not exist, we create one.
                if (!$seriesid) {
                    // Make sure to create using another method.
                    $seriesid = $apibridge->create_course_series($courseid, null, $USER->id);
                }
                $this->series[$ocinstanceid] = $seriesid;


                // Check if all required information is available.
                if (empty($this->series) || !isset($data->import) || !isset($data->events) ||
                    empty($data->import[0]['series']) || empty($data->events['event'])) {
                    // Nothing to do here, as the data is not enough.
                    return;
                }


                // Proceed with the backedup series, to save the mapping and repair the modules.
                foreach ($data->import[0]['series'] as $series) {
                    // Skip when the series is not from the current instance.
                    if($series['instanceid'] != $ocinstanceid) {
                        continue;
                    }
                    $seriesid = $series['seriesid'] ?? null;
                    // Skip when there is no original series, or the series is invalid.
                    if (empty($seriesid) || !$apibridge->ensure_series_is_valid($seriesid)) {
                        continue;
                    }

                    echo('Saving series importvideosmanager for instanceid: ' . $ocinstanceid . ' and seriesid: ' . $seriesid . ' and courseid: ' . $courseid . PHP_EOL);

                    // Record series mapping for module fix.
                    $issaved = importvideosmanager::save_series_import_mapping_record(
                        $ocinstanceid,
                        $courseid,
                        $seriesid,
                        $this->restoreuniqueid
                    );
                    if (!$issaved) {
                        $this->missingimportmappingseriesids[] = $seriesid;
                    }
                }

                foreach ($data->events['event'] as $event) {

                    // Skip when the event is not from the current instance.
                    if($event['instanceid'] != $ocinstanceid) {
                        continue;
                    }
                    $eventid = $event['eventid'] ?? null;
                    $this->backupeventids[] = $eventid;

                    // Only duplicate, when the event exists in opencast.
                    if (!$apibridge->get_already_existing_event([$eventid])) {
                        $this->missingeventids[] = $eventid;
                    } else {
                        // Check for and record the module mappings.
                        $issaved = importvideosmanager::save_episode_import_mapping_record(
                            $ocinstanceid,
                            $courseid,
                            $eventid,
                            $this->restoreuniqueid
                        );
                        if (!$issaved) {
                            $this->missingimportmappingeventids[] = $eventid;
                        }

                        echo('Creating duplication task for eventid: ' . $eventid . ' and seriesid: ' . $this->series[$ocinstanceid] . ' and courseid: ' . $courseid . PHP_EOL);

                        // Add the duplication task.
                        event::create_duplication_task(
                            $ocinstanceid,
                            $courseid,
                            $this->series[$ocinstanceid],
                            $eventid,
                            false,
                            null,
                            $this->restoreuniqueid
                        );
                    }
                }

            }

        }

    }

    public function process_site($data) {

        echo "Hello, restore site!";
        echo('Data: ' . print_r($data, true) . PHP_EOL);
        echo('Ocinstanceid: ' . print_r($data['ocinstanceid'], true) . PHP_EOL);
        echo('Apiurl: ' . print_r($data['apiurl'], true) . PHP_EOL);


        // Verify if ocinstance exists. Skip if not.
        $ocinstanceid = $data['ocinstanceid'];
        $apiurl = settings_api::get_apiurl($ocinstanceid);
        if(!$apiurl) {
            echo('No apiurl found for instanceid: ' . $ocinstanceid . ' Skipping this instance while restoring.' . PHP_EOL);
            $this->instanceid_skip[] = $ocinstanceid;
        }else if($apiurl != $data['apiurl']) {
            // Skip instance id, to avoid restoring into wrong instance.
            $this->instanceid_skip[] = $ocinstanceid;
            echo('Wrong apiurl found for instanceid: ' . $ocinstanceid . ' Skipping this instance while restoring.' . PHP_EOL);
        }

    }

    public function after_restore_course() {
        global $DB;

        // // Check if the course is restored.
        // $courseid = $this->task->get_courseid();
        // $course = $DB->get_record('course', ['id' => $courseid], 'id, shortname, fullname');
        // if ($course) {
        //     notifications::add_notification(
        //         'Course restored successfully: ' . $course->fullname,
        //         notifications::NOTIFICATION_SUCCESS
        //     );
        // } else {
        //     notifications::add_notification(
        //         'Course restoration failed.',
        //         notifications::NOTIFICATION_ERROR
        //     );
        // }

        echo "Hello, after restore course!";
        file_put_contents(
            '/var/www/moodledata/temp/restore_opencast.log',
            'Hello, after restore course!' . PHP_EOL,
            FILE_APPEND
        );

        // Get instace ids
        $ocinstances = settings_api::get_ocinstances();

        // Get course id
        $contextid = $this->task->get_contextid();
        $context = \core\context::instance_by_id($contextid);
        $courseid = $context->instanceid;

        // Import mode is not defined.
        if (!$this->importmode) {
            notifications::notify_failed_importmode($courseid);
            return;
        }

        if ($this->importmode == 'duplication') {
            // None of the backupeventids are used for starting a workflow.
            if (!$this->series) {
                notifications::notify_failed_course_series($courseid, $this->backupeventids);
                return;
            }

            // A course series is created, but some events are not found on opencast server.
            if ($this->missingeventids) {
                notifications::notify_missing_events($courseid, $this->missingeventids);
            }

            // Notify those series that were unable to have an import mapping record.
            if (!empty($this->missingimportmappingseriesids)) {
                notifications::notify_missing_import_mapping($courseid, $this->missingimportmappingseriesids, 'series');
            }

            // Notify those events that were unable to have an import mapping record.
            if (!empty($this->missingimportmappingeventids)) {
                notifications::notify_missing_import_mapping($courseid, $this->missingimportmappingeventids, 'events');
            }

            // Set the completion for mapping.
            $report = importvideosmanager::set_import_mapping_completion_status($this->restoreuniqueid);
            // If it is report has values, that means there were failures and we report them.
            if (is_array($report)) {
                notifications::notify_incompleted_import_mapping_records($courseid, $report);
            }
        }

        // Handle each Opencast instance
        foreach($ocinstances as $ocinstance) {

            $ocinstanceid = $ocinstance->id;

            // Check against skip list
            if(in_array($ocinstanceid, $this->instanceid_skip)) {
                // Skip instance id, to avoid restoring into wrong instance.
                continue;
            }

            $importmode = get_config('tool_opencast', 'importmode_' . $ocinstanceid);

            if ($importmode == 'duplication') {

                    // After all, we proceed to fix the series modules because they should not wait for the duplicate workflow to finish!
                    importvideosmanager::fix_imported_series_modules_in_new_course(
                        $ocinstanceid,
                        $courseid,
                        $this->series[$ocinstanceid],
                        $this->restoreuniqueid
                    );
                }
        }

    }

}