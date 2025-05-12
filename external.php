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
 * Opencast external API
 *
 * @package    tool_opencast
 * @category   external
 * @copyright  2018 Tobias Reischmann <tobias.reischmann@wi.uni-muenster.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.2
 */

use tool_opencast\local\apibridge;
use tool_opencast\local\series_form;
use tool_opencast\local\liveupdate_helper;
use tool_opencast\local\upload_helper;
use tool_opencast\seriesmapping;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/authlib.php');

/**
 * Opencast external API
 *
 * @package    tool_opencast
 * @category   external
 * @copyright  2018 Tobias Reischmann <tobias.reischmann@wi.uni-muenster.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_opencast_external extends external_api {

    /**
     * Describes the parameters for getting courses for a opencast instructor.
     *
     * @return external_function_parameters
     * @throws coding_exception
     */
    public static function get_courses_for_instructor_parameters() {
        return new external_function_parameters(
            [
                'username' => new external_value(core_user::get_property_type('username'), 'User Name'),
            ]
        );
    }

    /**
     * Describes the parameters for getting courses for a opencast learner.
     *
     * @return external_function_parameters
     * @throws coding_exception
     */
    public static function get_courses_for_learner_parameters() {
        return new external_function_parameters(
            [
                'username' => new external_value(core_user::get_property_type('username'), 'User Name'),
            ]
        );
    }

    /**
     * Describes the parameters for getting groups for a opencast user.
     *
     * @return external_function_parameters
     * @throws coding_exception
     */
    public static function get_groups_for_learner_parameters() {
        return new external_function_parameters(
            [
                'username' => new external_value(core_user::get_property_type('username'), 'User Name'),
            ]
        );
    }

    /**
     * Get all courses for a user, in which he has the capabilities of a instructor.
     *
     * @param string $username user name
     * @return array list of course ids
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     */
    public static function get_courses_for_instructor($username) {
        self::validate_parameters(self::get_courses_for_instructor_parameters(), ['username' => $username]);

        return self::get_courses_with_capability($username, 'tool/opencast:instructor');
    }

    /**
     * Get all courses for a user, in which he has the capabilities of a learner.
     *
     * @param string $username user name
     * @return array list of course ids
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     */
    public static function get_courses_for_learner($username) {
        self::validate_parameters(self::get_courses_for_learner_parameters(), ['username' => $username]);

        return self::get_courses_with_capability($username, 'tool/opencast:learner');
    }

    /**
     * Get all courses for a user, in which he has the capabilities of a learner.
     *
     * @param string $username user name
     * @return array list of course ids
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     */
    public static function get_groups_for_learner($username) {
        self::validate_parameters(self::get_groups_for_learner_parameters(), ['username' => $username]);

        $context = context_system::instance();
        if (!has_capability('tool/opencast:externalapi', $context)) {
            throw new required_capability_exception($context, 'tool/opencast:externalapi', 'nopermissions', '');
        }
        if (!has_capability('moodle/site:accessallgroups', $context)) {
            throw new required_capability_exception($context, 'moodle/site:accessallgroups', 'nopermissions', '');
        }

        global $DB;
        $user = core_user::get_user_by_username($username);
        return $DB->get_records('groups_members', ['userid' => $user->id], '', 'groupid as id');
    }

    /**
     * Returns all course ids where the user has the specific capability in.
     * @param string $username the username
     * @param string $capability the moodle capability
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws required_capability_exception
     */
    private static function get_courses_with_capability($username, $capability) {
        $result = [];

        $context = context_system::instance();
        if (!has_capability('tool/opencast:externalapi', $context)) {
            throw new required_capability_exception($context, 'tool/opencast:externalapi', 'nopermissions', '');
        }

        $user = core_user::get_user_by_username($username);
        $courses = enrol_get_all_users_courses($user->id);
        foreach ($courses as $course) {
            $context = context_course::instance($course->id);
            if (has_capability($capability, $context, $user)) {
                $result[] = ['id' => $course->id];
            }
        }
        return $result;
    }

    /**
     * Describes the confirm_user return value.
     *
     * @return external_multiple_structure array of course ids
     */
    public static function get_courses_for_instructor_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'id' => new external_value(PARAM_INT, 'id of course'),
                ]
            )
        );
    }

    /**
     * Describes the confirm_user return value.
     *
     * @return external_multiple_structure array of course ids
     */
    public static function get_courses_for_learner_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'id' => new external_value(PARAM_INT, 'id of course'),
                ]
            )
        );
    }

    /**
     * Describes the confirm_user return value.
     *
     * @return external_multiple_structure array of course ids
     */
    public static function get_groups_for_learner_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'id' => new external_value(PARAM_INT, 'id of group'),
                ]
            )
        );
    }

    /**
     * Describes the connection_test_tool return value.
     *
     * @return external_single_structure array the result of the connection test
     */
    public static function connection_test_tool_returns() {
        return new external_single_structure(
            [
                'testresult' => new external_value(PARAM_RAW, 'Opencast API URL Test result'),
            ]
        );
    }

    /**
     * Describes the maintenance_sync return value.
     *
     * @return external_single_structure array the result of the connection test
     */
    public static function maintenance_sync_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_BOOL, 'Maintenance Synchronization result status'),
            ]
        );
    }

    /**
     * Describes the parameters for testing the connection.
     *
     * @return external_function_parameters
     * @throws coding_exception
     */
    public static function connection_test_tool_parameters() {
        return new external_function_parameters(
            [
                'apiurl' => new external_value(PARAM_TEXT, 'Opencast API URL'),
                'apiusername' => new external_value(PARAM_TEXT, 'Opencast API User'),
                'apipassword' => new external_value(PARAM_RAW, 'Opencast API Password'),
                'apitimeout' => new external_value(PARAM_INT, 'API timeout', VALUE_DEFAULT, 2000),
                'apiconnecttimeout' => new external_value(PARAM_INT, 'API connect timeout', VALUE_DEFAULT, 1000),
            ]
        );
    }

    /**
     * Describes the parameters for syncing the maintenance.
     *
     * @return external_function_parameters
     * @throws coding_exception
     */
    public static function maintenance_sync_parameters() {
        return new external_function_parameters(
            [
                'ocinstanceid' => new external_value(PARAM_INT, 'Opencast instance id'),
            ]
        );
    }

    /**
     * Builds a html tag for the alert of the connection test tool.
     *
     * @param string $connectiontestresult The result of a connection test.
     * @param string $testsuccessfulstringidentifier The string identifier of a successful connection test.
     * @param string $testfailedstringidentifier The string identifier of a failed connection test.
     * @return string The html tag as string.
     */
    private static function connection_test_tool_build_html_alert_tag($connectiontestresult,
                                                                      string $testsuccessfulstringidentifier,
                                                                      string $testfailedstringidentifier): string {
        // Check, if the test was successful.
        if ($connectiontestresult === true) {
            return html_writer::tag(
                'p',
                get_string($testsuccessfulstringidentifier, 'tool_opencast'),
                ['class' => 'alert alert-success']
            );
        }

        return html_writer::tag(
            'p',
            get_string($testfailedstringidentifier, 'tool_opencast', $connectiontestresult),
            ['class' => 'alert alert-danger']
        );
    }

    /**
     * Perform the connection test via Ajax call to be able to show it in Modal.
     *
     * @param string $apiurl Opencast API URL
     * @param string $apiusername Opencast API username
     * @param string $apipassword Opencast API password
     * @param int $apitimeout Overall API request execution timeout in milliseconds
     * @param int $apiconnecttimeout Connection timeout in milliseconds
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     */
    public static function connection_test_tool($apiurl, $apiusername, $apipassword, $apitimeout, $apiconnecttimeout) {

        // Validate the parameters.
        $params = self::validate_parameters(self::connection_test_tool_parameters(),
            [
                'apiurl' => $apiurl,
                'apiusername' => $apiusername,
                'apipassword' => $apipassword,
                'apitimeout' => $apitimeout,
                'apiconnecttimeout' => $apiconnecttimeout,
            ]
        );

        // Get a customized api instance to use.
        $customizedapi = \tool_opencast\local\api::get_instance(null, [], [
                'apiurl' => $params['apiurl'],
                'apiusername' => $params['apiusername'],
                'apipassword' => $params['apipassword'],
                'apitimeout' => $params['apitimeout'],
                'apiconnecttimeout' => $params['apiconnecttimeout'],
        ]);

        // Test the URL.
        $connectiontesturlresult = $customizedapi->connection_test_url();
        $resulthtml = self::connection_test_tool_build_html_alert_tag(
            $connectiontesturlresult,
            'apiurltestsuccessfulshort',
            'apiurltestfailedshort');

        // Test the Credentials.
        $connectiontestcredentialsresult = $customizedapi->connection_test_credentials();
        $resulthtml .= self::connection_test_tool_build_html_alert_tag(
            $connectiontestcredentialsresult,
            'apicreadentialstestsuccessfulshort',
            'apicreadentialstestfailedshort');

        return [
            'testresult' => $resulthtml,
        ];
    }

    /**
     * Perform fetching and syncing maintenance mode data from Opencast.
     *
     * @param int $ocinstanceid Opencast instance id.
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     */
    public static function maintenance_sync($ocinstanceid) {

        // Validate the parameters.
        $params = self::validate_parameters(self::maintenance_sync_parameters(),
            [
                'ocinstanceid' => $ocinstanceid,
            ]
        );

        // Get a customized api instance to use.
        $api = \tool_opencast\local\api::get_instance($params['ocinstanceid']);

        $result = $api->sync_maintenance_with_opencast();

        return [
            'status' => $result,
        ];
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function submit_series_form_parameters() {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
            'ocinstanceid' => new external_value(PARAM_INT, 'The Opencast instance id'),
            'seriesid' => new external_value(PARAM_ALPHANUMEXT, 'The series id'),
            'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as json array'),
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_series_titles_parameters() {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
            'ocinstanceid' => new external_value(PARAM_INT, 'The Opencast instance id'),
            'series' => new external_value(PARAM_RAW, 'Requested series, encoded as json array'),
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function import_series_parameters() {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
            'ocinstanceid' => new external_value(PARAM_INT, 'The Opencast instance id'),
            'seriesid' => new external_value(PARAM_ALPHANUMEXT, 'Series to be imported'),
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function unlink_series_parameters() {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
            'ocinstanceid' => new external_value(PARAM_INT, 'The Opencast instance id'),
            'seriesid' => new external_value(PARAM_ALPHANUMEXT, 'Series to be removed'),
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function set_default_series_parameters() {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
            'ocinstanceid' => new external_value(PARAM_INT, 'The Opencast instance id'),
            'seriesid' => new external_value(PARAM_ALPHANUMEXT, 'Series to be set as default'),
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_liveupdate_info_parameters() {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
            'ocinstanceid' => new external_value(PARAM_INT, 'The Opencast instance id'),
            'type' => new external_value(PARAM_TEXT, 'The type of domain to check the status from'),
            'identifier' => new external_value(PARAM_ALPHANUMEXT, 'Event id to observe its processing state'),
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function unarchive_uploadjob_parameters() {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
            'ocinstanceid' => new external_value(PARAM_INT, 'The Opencast instance id'),
            'uploadjobid' => new external_value(PARAM_INT, 'The upload job id'),
        ]);
    }

    /**
     * Submits the series form.
     *
     * @param int $contextid The context id for the course.
     * @param int $ocinstanceid Opencast instance id
     * @param string $seriesid Series identifier
     * @param string $jsonformdata The data from the form, encoded as json array.
     *
     * @return string new series id
     */
    public static function submit_series_form($contextid, int $ocinstanceid, string $seriesid, string $jsonformdata) {
        global $USER, $DB;

        $params = self::validate_parameters(self::submit_series_form_parameters(), [
            'contextid' => $contextid,
            'ocinstanceid' => $ocinstanceid,
            'seriesid' => $seriesid,
            'jsonformdata' => $jsonformdata,
        ]);

        $context = context::instance_by_id($params['contextid']);
        self::validate_context($context);
        require_capability('tool/opencast:createseriesforcourse', $context);

        list($ignored, $course) = get_context_info_array($context->id);

        // Check if the maximum number of series is already reached.
        $courseseries = $DB->get_records('tool_opencast_series', ['ocinstanceid' => $ocinstanceid, 'courseid' => $course->id]);
        if (!$params['seriesid'] && count($courseseries) >= get_config('tool_opencast', 'maxseries_' . $ocinstanceid)) {
            throw new moodle_exception('maxseriesreached', 'tool_opencast');
        }

        $data = [];
        parse_str($params['jsonformdata'], $data);
        $data['courseid'] = $course->id;

        $metadatacatalog = json_decode(get_config('tool_opencast', 'metadataseries_' . $params['ocinstanceid']));
        // Make sure $metadatacatalog is array.
        $metadatacatalog = !empty($metadatacatalog) ? $metadatacatalog : [];
        $createseriesform = new series_form(null, ['courseid' => $course->id,
            'ocinstanceid' => $params['ocinstanceid'],
            'metadata_catalog' => $metadatacatalog, ], 'post', '', null, true, $data);
        $validateddata = $createseriesform->get_data();

        if ($validateddata) {
            $metadata = [];
            foreach ($validateddata as $field => $value) {
                if ($field === 'courseid') {
                    continue;
                }
                if ($field === 'subjects') {
                    $metadata[] = [
                        'id' => 'subject',
                        'value' => implode(',', $value),
                    ];
                } else {
                    $metadata[] = [
                        'id' => $field,
                        'value' => $value,
                    ];
                }
            }

            $apibridge = apibridge::get_instance($params['ocinstanceid']);
            if (!$params['seriesid']) {
                return json_encode($apibridge->create_course_series($course->id, $metadata, $USER->id));
            } else {
                $result = $apibridge->update_series_metadata($params['seriesid'], $metadata);
                if (!$result) {
                    throw new moodle_exception('metadataseriesupdatefailed', 'tool_opencast');
                }
                return $result;
            }
        } else {
            throw new moodle_exception('missingrequiredfield');
        }
    }

    /**
     * Retrieves the series titles.
     *
     * @param int $contextid The context id for the course.
     * @param int $ocinstanceid Opencast instance id
     * @param string $series Requested series, encoded as json array.
     *
     * @return string Series titles
     */
    public static function get_series_titles(int $contextid, int $ocinstanceid, string $series) {
        $params = self::validate_parameters(self::get_series_titles_parameters(), [
            'contextid' => $contextid,
            'ocinstanceid' => $ocinstanceid,
            'series' => $series,
        ]);

        $context = context::instance_by_id($params['contextid']);
        self::validate_context($context);
        require_capability('tool/opencast:manageseriesforcourse', $context);

        $serialiseddata = json_decode($params['series']);
        $seriestitles = [];

        $apibridge = apibridge::get_instance($params['ocinstanceid']);
        $seriesrecords = $apibridge->get_multiple_series_by_identifier($serialiseddata);

        foreach ($seriesrecords as $s) {
            $seriestitles[$s->identifier] = $s->title;
        }

        return json_encode($seriestitles);
    }

    /**
     * Imports a series into a course.
     *
     * @param int $contextid The context id for the course.
     * @param int $ocinstanceid Opencast instance id
     * @param string $series Series to be imported
     *
     * @return bool True if successful
     */
    public static function import_series(int $contextid, int $ocinstanceid, string $series) {
        global $USER, $DB;
        $params = self::validate_parameters(self::import_series_parameters(), [
            'contextid' => $contextid,
            'ocinstanceid' => $ocinstanceid,
            'seriesid' => $series,
        ]);

        $context = context::instance_by_id($params['contextid']);
        self::validate_context($context);
        require_capability('tool/opencast:importseriesintocourse', $context);

        list($unused, $course, $cm) = get_context_info_array($context->id);

        // Check if the maximum number of series is already reached.
        $courseseries = $DB->get_records('tool_opencast_series', ['ocinstanceid' => $ocinstanceid, 'courseid' => $course->id]);
        if (count($courseseries) >= get_config('tool_opencast', 'maxseries_' . $ocinstanceid)) {
            throw new moodle_exception('maxseriesreached', 'tool_opencast');
        }

        // Check if the series id already exists in this course.
        $importingseriesid = $params['seriesid'];
        $existingseries = array_filter($courseseries, function ($courseserie) use ($importingseriesid) {
            return $courseserie->series === $importingseriesid;
        });

        if (count($existingseries) > 0) {
            throw new moodle_exception('importseries_alreadyexists', 'tool_opencast');
        }

        $apibridge = apibridge::get_instance($params['ocinstanceid']);
        // Ensure the import series is allowed.
        if (!$apibridge->can_user_import_arbitrary_series($params['seriesid'], $USER->id)) {
            throw new moodle_exception('importseries_notallowed', 'tool_opencast');
        }

        // Perform ACL change.
        $result = $apibridge->import_series_to_course_with_acl_change($course->id, $params['seriesid'], $USER->id);

        if ($result->error) {
            throw new moodle_exception('importfailed', 'tool_opencast');
        }

        $seriesinfo = new stdClass();
        $seriesinfo->id = $params['seriesid'];
        $seriesinfo->title = $apibridge->get_series_by_identifier($params['seriesid'])->title;
        $seriesinfo->isdefault = $result->seriesmapped->isdefault;

        return json_encode($seriesinfo);
    }

    /**
     * Removes a series from a course but does not delete it in Opencast.
     *
     * @param int $contextid The context id for the course.
     * @param int $ocinstanceid Opencast instance id
     * @param string $series Series to be removed from the course
     *
     * @return bool True if successful
     */
    public static function unlink_series(int $contextid, int $ocinstanceid, string $series) {
        $params = self::validate_parameters(self::unlink_series_parameters(), [
            'contextid' => $contextid,
            'ocinstanceid' => $ocinstanceid,
            'seriesid' => $series,
        ]);

        $unlinkall = $params['seriesid'] === 'all';

        $context = context::instance_by_id($params['contextid']);
        self::validate_context($context);
        require_capability('tool/opencast:manageseriesforcourse', $context);

        list($unused, $course, $cm) = get_context_info_array($context->id);

        // In case the request comes from block deletion remove all mappings.
        if ($unlinkall) {
            $mappings = seriesmapping::get_records(['courseid' => $course->id]);
        } else {
            // Otherwise, the request comes from normal series deletion page.
            $mappings = seriesmapping::get_records(['ocinstanceid' => $params['ocinstanceid'], 'courseid' => $course->id,
                    'series' => $params['seriesid']]);
        }

        foreach ($mappings as $mapping) {
            $isdefault = $mapping->get('isdefault');
            // We need to check the uniqueness of the mapping record when it is a single mapping removal.
            if ($isdefault && !$unlinkall) {
                // Prevent deletion of default series.
                // By checking the number of default series,
                // it is still possible to correct the faulty scenario of having multi-default series in a course.
                if (seriesmapping::count_records(['ocinstanceid' => $params['ocinstanceid'],
                        'courseid' => $course->id, 'isdefault' => true, ]) === 1) {
                    throw new moodle_exception('cantdeletedefaultseries', 'tool_opencast');
                }
            }

            if (!$mapping->delete()) {
                throw new moodle_exception('delete_series_failed', 'tool_opencast');
            }

            // Unlinking series from course.
            $apibridge = apibridge::get_instance($mapping->get('ocinstanceid'));
            $seriesunlinked = $apibridge->unlink_series_from_course($course->id, $mapping->get('series'));

            if (!$seriesunlinked) {
                throw new moodle_exception('delete_series_failed', 'tool_opencast');
            }
        }

        return true;
    }

    /**
     * Sets a new default series for a course.
     *
     * @param int $contextid The context id for the course.
     * @param int $ocinstanceid Opencast instance id
     * @param string $series Series to be set as default
     *
     * @return bool True if successful
     */
    public static function set_default_series(int $contextid, int $ocinstanceid, string $series) {
        $params = self::validate_parameters(self::set_default_series_parameters(), [
            'contextid' => $contextid,
            'ocinstanceid' => $ocinstanceid,
            'seriesid' => $series,
        ]);

        $context = context::instance_by_id($params['contextid']);
        self::validate_context($context);
        require_capability('tool/opencast:manageseriesforcourse', $context);

        list($unused, $course, $cm) = get_context_info_array($context->id);

        $olddefaultseries = seriesmapping::get_record(['ocinstanceid' => $params['ocinstanceid'],
            'courseid' => $course->id, 'isdefault' => true, ]);

        // Series is already set as default.
        // We provide an exception here to fix the problem of having a course with no default series, which should not happen,
        // by letting it pass through when the old default does not exist.
        if (!empty($olddefaultseries) && $olddefaultseries->get('series') == $params['seriesid']) {
            return true;
        }

        // Set new series as default.
        $mapping = seriesmapping::get_record(['ocinstanceid' => $params['ocinstanceid'],
            'courseid' => $course->id, 'series' => $params['seriesid'], ], true);

        // Remove default flag from old series first.
        $canbeupdated = empty($olddefaultseries);
        if (!empty($olddefaultseries)) {
            $olddefaultseries->set('isdefault', false);
            if ($olddefaultseries->update()) {
                $canbeupdated = true;
            }
        }

        // Now, we go for the actual update.
        if ($canbeupdated && $mapping) {
            $mapping->set('isdefault', true);
            if ($mapping->update()) {
                return true;
            }
        }

        throw new moodle_exception('setdefaultseriesfailed', 'tool_opencast');
    }

    /**
     * Returns the live update info for:
     * - Workflow processing state.
     * - Upload status.
     *
     * @param int $contextid The context id for the course.
     * @param int $ocinstanceid Opencast instance id
     * @param string $type the type of live update to check against
     * @param string $identifier the identifier to get records for
     *
     * @return string Latest update state info
     */
    public static function get_liveupdate_info(int $contextid, int $ocinstanceid, string $type, string $identifier) {
        $params = self::validate_parameters(self::get_liveupdate_info_parameters(), [
            'contextid' => $contextid,
            'ocinstanceid' => $ocinstanceid,
            'type' => $type,
            'identifier' => $identifier,
        ]);

        $context = context::instance_by_id($params['contextid']);
        self::validate_context($context);
        require_capability('tool/opencast:viewunpublishedvideos', $context);

        // Initialise the live update info as an empty array.
        $liveupdateinfo = [];
        // Get processing state info.
        if ($params['type'] == 'processing') {
            $liveupdateinfo = liveupdate_helper::get_processing_state_info($params['ocinstanceid'], $params['identifier']);
        } else if ($type == 'uploading') {
            // Get uploading status.
            $liveupdateinfo = liveupdate_helper::get_uploading_info($params['identifier']);
        }

        // Force to have replace and remove params, otherwise empty must be returned.
        if (!isset($liveupdateinfo['replace']) || !isset($liveupdateinfo['remove'])) {
            // Returning empty string helps to remove the item in the javascript, that results in cleaning the interval.
            return '';
        }

        // Finally, we return info as json encoded string.
        return json_encode($liveupdateinfo);
    }

    /**
     * Perform unarchiving an uploadjob.
     *
     * @param int $contextid The context id for the course.
     * @param int $ocinstanceid Opencast instance id
     * @param int $uploadjobid Uploadjob id
     *
     * @return string Latest update state info
     */
    public static function unarchive_uploadjob(int $contextid, int $ocinstanceid, int $uploadjobid) {
        global $USER, $DB;
        $params = self::validate_parameters(self::unarchive_uploadjob_parameters(), [
            'contextid' => $contextid,
            'ocinstanceid' => $ocinstanceid,
            'uploadjobid' => $uploadjobid,
        ]);

        $context = context::instance_by_id($params['contextid']);
        self::validate_context($context);
        require_capability('tool/opencast:addvideo', $context);

        list($unused, $course, $cm) = get_context_info_array($context->id);

        $params = [
            'id' => $params['uploadjobid'],
            'ocinstanceid' => $params['ocinstanceid'],
            'courseid' => $course->id,
            'status' => upload_helper::STATUS_ARCHIVED_FAILED_UPLOAD,
        ];
        $uploadjob = $DB->get_record('tool_opencast_uploadjob', $params);

        if (!empty($uploadjob)) {
            $time = time();
            $uploadjob->timemodified = $time;
            $uploadjob->countfailed = 0;
            $uploadjob->status = upload_helper::STATUS_READY_TO_UPLOAD;
            $DB->update_record('tool_opencast_uploadjob', $uploadjob);
            return true;
        }

        throw new moodle_exception('uploadjobnotfound', 'tool_opencast');
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function submit_series_form_returns() {
        return new external_value(PARAM_RAW, 'Json series data');
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_series_titles_returns() {
        return new external_value(PARAM_RAW, 'json array for the series');
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function import_series_returns() {
        return new external_value(PARAM_RAW, 'Json series data');
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function unlink_series_returns() {
        return new external_value(PARAM_BOOL, 'True if successful');
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function set_default_series_returns() {
        return new external_value(PARAM_BOOL, 'True if successful');
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_liveupdate_info_returns() {
        return new external_value(PARAM_RAW, 'Json live update info');
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function unarchive_uploadjob_returns() {
        return new external_value(PARAM_BOOL, 'True if successful');
    }
}
