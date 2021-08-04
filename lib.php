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
 * 
 * @package    tool_opencast
 * @copyright  2021 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Helperfunction to validate given Opencast API URL.
 * In case of test failure, a redirect action takes place.
 */
function tool_opencast_test_url_connection() {
    // Get apiurl from admin setting.
    $apiurl = get_config('tool_opencast', 'apiurl');

    // Get apiusername from admin setting.
    $apiusername = get_config('tool_opencast', 'apiusername');

    // Get apipassword from admin setting as well.
    $apipassword = get_config('tool_opencast', 'apipassword');

    // Initialise the costum config array.
    $customconfigs = array();
    $customconfigs['apiurl'] = $apiurl;
    
    // Username and password are optional here and they will be used to get api info correctly.
    if (!empty($apiusername)) {
        $customconfigs['apiusername'] = $apiusername;
    }

    if (!empty($apipassword)) {
        $customconfigs['apipassword'] = $apipassword;
    }

    // Get an api instance with optional entries.
    $customizedapi = new \tool_opencast\local\api(array(), $customconfigs);

    // If the URL connection test fails.
    if ($customizedapi->connection_test_url() == false) {
        // Redirect back to admin page with related error message.
        redirect(new \moodle_url('/admin/settings.php?section=tool_opencast'),
            get_string('apiurltestfailedlong', 'tool_opencast') , 0, \core\output\notification::NOTIFY_ERROR);
    }

    // In case given URL is valid, we now check if the username and password is provided.
    // Get apiusername from admin setting.
    $apiusername = get_config('tool_opencast', 'apiusername');

    // Get apipassword from admin setting as well.
    $apipassword = get_config('tool_opencast', 'apipassword');

    // Here we check if credentials are not empty.
    if (empty($apiusername) || empty($apipassword)) {
        // Redirect back to admin page with a warning message to inform admin that credentials are not yet provided.
        redirect(new \moodle_url('/admin/settings.php?section=tool_opencast'),
            get_string('apiurltestsucceedbutnocredentialslong', 'tool_opencast') , 0, \core\output\notification::NOTIFY_WARNING);
    }

    // When we reach here, it means that everything went fine, so we leave it to the admin_settings to handel the rest from here.
}

/**
 * Helperfunction to validate given credentials for the Opencast API URL.
 * In case of test failure, a redirect action takes place.
 */
function tool_opencast_test_connection_with_credentials() {
    // Get apiurl from admin setting.
    $apiurl = get_config('tool_opencast', 'apiurl');

    // Get apiusername from admin setting.
    $apiusername = get_config('tool_opencast', 'apiusername');

    // Get apipassword from admin setting as well.
    $apipassword = get_config('tool_opencast', 'apipassword');

    // Define the costum config array.
    $customconfigs = array();
    $customconfigs['apiurl'] = $apiurl;
    $customconfigs['apiusername'] = $apiusername;
    $customconfigs['apipassword'] = $apipassword;

    // Get an api instance with optional entries.
    $customizedapi = new \tool_opencast\local\api(array(), $customconfigs);
    
    // Check the creadentials.
    if ($customizedapi->connection_test_credentials() == false) {
        // Redirect back to admin page with related error message.
        redirect(new \moodle_url('/admin/settings.php?section=tool_opencast'),
            get_string('apicreadentialstestfailedlong', 'tool_opencast') , 0, \core\output\notification::NOTIFY_ERROR);
    }
    
    // When we reach here, it means that everything went fine, so we leave it to the admin_settings to handel the rest from here.
}