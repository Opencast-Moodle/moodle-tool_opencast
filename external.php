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
     * Get all courses for a user, in which he has the capabilities of a instructor.
     *
     * @param  string $username user name
     * @return array list of course ids
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function get_courses_for_instructor($username) {
        $params = self::validate_parameters(self::get_courses_for_instructor_parameters(), array('username'=>$username));

        return self::get_courses_with_capability($params->username, 'tool/opencast:instructor');
    }

    /**
     * Get all courses for a user, in which he has the capabilities of a learner.
     *
     * @param  string $username user name
     * @return array list of course ids
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function get_courses_for_learner($username) {
        $params = self::validate_parameters(self::get_courses_for_learner_parameters(), array('username'=>$username));

        return self::get_courses_with_capability($params->username, 'tool/opencast:learner');
    }

    /**
     * Returns all course ids where the user has the specific capability in.
     * @param $username
     * @param $capability
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    private static function get_courses_with_capability($username, $capability) {
        $result = array();

        $user = core_user::get_user_by_username($username);
        $courses = enrol_get_all_users_courses($user->id);
        foreach ($courses as $course) {
            $context = context_course::instance($course->id);
            if (has_capability($capability, $context, $user)) {
                $result []= $course->id;
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
}
