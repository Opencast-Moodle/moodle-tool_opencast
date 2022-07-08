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
            array(
                'username' => new external_value(core_user::get_property_type('username'), 'User Name'),
            )
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
            array(
                'username' => new external_value(core_user::get_property_type('username'), 'User Name'),
            )
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
            array(
                'username' => new external_value(core_user::get_property_type('username'), 'User Name'),
            )
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
        self::validate_parameters(self::get_courses_for_instructor_parameters(), array('username' => $username));

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
        self::validate_parameters(self::get_courses_for_learner_parameters(), array('username' => $username));

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
        self::validate_parameters(self::get_groups_for_learner_parameters(), array('username' => $username));

        $context = context_system::instance();
        if (!has_capability('tool/opencast:externalapi', $context)) {
            throw new required_capability_exception($context, 'tool/opencast:externalapi', 'nopermissions', '');
        }
        if (!has_capability('moodle/site:accessallgroups', $context)) {
            throw new required_capability_exception($context, 'moodle/site:accessallgroups', 'nopermissions', '');
        }

        global $DB;
        $user = core_user::get_user_by_username($username);
        return $DB->get_records('groups_members', array('userid' => $user->id), '', 'groupid as id');
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
        $result = array();

        $context = context_system::instance();
        if (!has_capability('tool/opencast:externalapi', $context)) {
            throw new required_capability_exception($context, 'tool/opencast:externalapi', 'nopermissions', '');
        }

        $user = core_user::get_user_by_username($username);
        $courses = enrol_get_all_users_courses($user->id);
        foreach ($courses as $course) {
            $context = context_course::instance($course->id);
            if (has_capability($capability, $context, $user)) {
                $result[] = array('id' => $course->id);
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
                array(
                    'id' => new external_value(PARAM_INT, 'id of course'),
                )
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
                array(
                    'id' => new external_value(PARAM_INT, 'id of course'),
                )
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
                array(
                    'id' => new external_value(PARAM_INT, 'id of group'),
                )
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
            array(
                'testresult' => new external_value(PARAM_RAW, 'Opencast API URL Test result'),
            )
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
            array(
                'apiurl' => new external_value(PARAM_TEXT, 'Opencast API URL'),
                'apiusername' => new external_value(PARAM_TEXT, 'Opencast API User'),
                'apipassword' => new external_value(PARAM_RAW, 'Opencast API Password'),
                'apitimeout' => new external_value(PARAM_INT, 'API timeout'),
                'apiconnecttimeout' => new external_value(PARAM_INT, 'API connect timeout'),
            )
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
            array(
                'apiurl' => $apiurl,
                'apiusername' => $apiusername,
                'apipassword' => $apipassword,
                'apitimeout' => $apitimeout,
                'apiconnecttimeout' => $apiconnecttimeout
            )
        );

        // Initialise the result array.
        $result = array();

        // We assume that both tests are successful, so we define the success p tags for both.
        $result['testurl'] = \html_writer::tag('p', get_string('apiurltestsuccessfulshort', 'tool_opencast'),
            array('class' => 'alert alert-success'));
        $result['testcreadentials'] = html_writer::tag('p', get_string('apicreadentialstestsuccessfulshort', 'tool_opencast'),
            array('class' => 'alert alert-success'));

        // Get the a customized api instance to use.
        $customizedapi = \tool_opencast\local\api::get_instance(null, array(), array('apiurl' => $params['apiurl'],
            'apiusername' => $params['apiusername'], 'apipassword' => $params['apipassword'],
            'apitimeout' => $params['apitimeout'], 'apiconnecttimeout' => $params['apiconnecttimeout']));

        // First we test the URL.
        if ($customizedapi->connection_test_url() == false) {
            // In case it failed, we replace the succes p tag with error p tag.
            $result['testurl'] = \html_writer::tag('p', get_string('apiurltestfailedshort', 'tool_opencast'),
                array('class' => 'alert alert-danger'));
        }

        // After that we test the Credentials.
        if ($customizedapi->connection_test_credentials() == false) {
            // In case it failed, we replace the succes p tag with error p tag.
            $result['testcreadentials'] = html_writer::tag('p', get_string('apicreadentialstestfailedshort', 'tool_opencast'),
                array('class' => 'alert alert-danger'));
        }

        // Finally, we concatenate both test results and return it.
        return [
            'testresult' => $result['testurl'] . $result['testcreadentials']
        ];
    }
}
