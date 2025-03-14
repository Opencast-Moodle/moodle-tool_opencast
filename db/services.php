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
 * Services for the Opencast API.
 *
 * @package tool_opencast
 * @copyright 2018 Tobias Reischmann <tobias.reischmann@wi.uni-muenster.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'tool_opencast_get_courses_for_learner' => [
        'classname' => 'tool_opencast_external',
        'methodname' => 'get_courses_for_learner',
        'classpath'   => 'admin/tool/opencast/external.php',
        'description' => 'Service to query the courses in which a user has the capability of a learner',
        'type' => 'read',
        'capabilities' => 'tool/opencast:externalapi',
    ],
    'tool_opencast_get_courses_for_instructor' => [
        'classname' => 'tool_opencast_external',
        'methodname' => 'get_courses_for_instructor',
        'classpath'   => 'admin/tool/opencast/external.php',
        'description' => 'Service to query the courses in which a user has the capability of a instructor',
        'type' => 'read',
        'capabilities' => 'tool/opencast:externalapi',
    ],
    'tool_opencast_get_groups_for_learner' => [
        'classname' => 'tool_opencast_external',
        'methodname' => 'get_groups_for_learner',
        'classpath'   => 'admin/tool/opencast/external.php',
        'description' => 'Service to query the groups in which a user has a membership in',
        'type' => 'read',
        'capabilities' => 'tool/opencast:externalapi, moodle/site:accessallgroups',
    ],
    'tool_opencast_connection_test_tool' => [
        'classname'     => 'tool_opencast_external',
        'methodname'    => 'connection_test_tool',
        'classpath'     => 'admin/tool/opencast/external.php',
        'description'   => 'Service to test Opencast API URL connection',
        'type'          => 'read',
        'capabilities'  => 'tool/opencast:externalapi',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'tool_opencast_maintenance_sync' => [
        'classname'     => 'tool_opencast_external',
        'methodname'    => 'maintenance_sync',
        'classpath'     => 'admin/tool/opencast/external.php',
        'description'   => 'Service to Sync Maintenance Mode with Opencast',
        'type'          => 'read',
        'capabilities'  => 'tool/opencast:externalapi',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'tool_opencast_submit_series_form' => [
        'classname' => 'tool_opencast_external',
        'methodname' => 'submit_series_form',
        'classpath' => 'admin/tool/opencast/external.php',
        'description' => 'Creates/Modifies a series',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'tool/opencast:createseriesforcourse',
    ],
    'tool_opencast_get_series_titles' => [
        'classname' => 'tool_opencast_external',
        'methodname' => 'get_series_titles',
        'classpath' => 'admin/tool/opencast/external.php',
        'description' => 'Retrieves series titles',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'tool/opencast:manageseriesforcourse',
    ],
    'tool_opencast_import_series' => [
        'classname' => 'tool_opencast_external',
        'methodname' => 'import_series',
        'classpath' => 'admin/tool/opencast/external.php',
        'description' => 'Imports a series into a course',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'tool/opencast:importseriesintocourse',
    ],
    'tool_opencast_unlink_series' => [
        'classname' => 'tool_opencast_external',
        'methodname' => 'unlink_series',
        'classpath' => 'admin/tool/opencast/external.php',
        'description' => 'Removes a series from a course',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'tool/opencast:manageseriesforcourse',
    ],
    'tool_opencast_set_default_series' => [
        'classname' => 'tool_opencast_external',
        'methodname' => 'set_default_series',
        'classpath' => 'admin/tool/opencast/external.php',
        'description' => 'Sets a new default series for a course',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'tool/opencast:manageseriesforcourse',
    ],
    'tool_opencast_get_liveupdate_info' => [
        'classname' => 'tool_opencast_external',
        'methodname' => 'get_liveupdate_info',
        'classpath' => 'admin/tool/opencast/external.php',
        'description' => 'Gets the latest live update information.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'tool/opencast:viewunpublishedvideos',
    ],
    'tool_opencast_unarchive_uploadjob' => [
        'classname' => 'tool_opencast_external',
        'methodname' => 'unarchive_uploadjob',
        'classpath' => 'admin/tool/opencast/external.php',
        'description' => 'Perform unarchiving an upload job',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'tool/opencast:addvideo',
    ],
];

$services = [
    'Opencast web service' => [
        'functions' => [
            'tool_opencast_get_courses_for_learner',
            'tool_opencast_get_courses_for_instructor',
            'tool_opencast_get_groups_for_learner',
            'core_user_get_users_by_field',
        ],
        'restrictedusers' => 1, // If 1, the administrator must manually select which user can use this service.
        // (Administration > Plugins > Web services > Manage services > Authorised users).
        'enabled' => 1, // If 0, then token linked to this service won't work.
    ],
];
