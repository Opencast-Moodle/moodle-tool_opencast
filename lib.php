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
 * It uses curl without any credentials to make sure that Opencast instance is running on the given URL.
 *
 * 
 * @param bool $redirect when true a redirect action to admin page will be occured upon failure. Otherwise, an html tag as string will be returned.
 * 
 * @return string  in case the function is called through connection test tool an html tag as string will be returned.
 */
function tool_opencast_test_url_connection($redirect = true) {
    // Initialize cURL.
    $curl = new \curl();
    $curl->resetHeader();

    // Set required headers.
    $header[] = 'Content-Type: application/json';
    $curl->setHeader($header);
    $curl->setopt(array('CURLOPT_HEADER' => false));

    // Get apiurl from admin setting.
    $apiurl = get_config('tool_opencast', 'apiurl');

    // Use "/api" endpoint to get key characteristics of the API such as the server name and the default version.
    $resource = $apiurl . '/api';
    $serverinfo = $curl->get($resource);

    // Get curl request info.
    $info = $curl->get_info();
    // We don't accept anything other than http_code 200.
    if (!isset($info['http_code']) || $info['http_code'] != 200) {
        // If we want the result to be displayed in adamin page with redirect action.
        if ($redirect) {
            // Redirect back to admin page with related error message.
            redirect(new \moodle_url('/admin/settings.php?section=tool_opencast'),
                get_string('apiurltestfailedlong', 'tool_opencast') , 0, \core\output\notification::NOTIFY_ERROR);
        }

        // Otherwise, we create the error message text with html_write here and return it.
        return \html_writer::tag('p', get_string('apiurltestfailedshort', 'tool_opencast'), array('class' => 'alert alert-danger'));
    }

    //In case given URL is valid, we now check if the username and password is provided.
    // Get apiusername from admin setting.
    $apiusername = get_config('tool_opencast', 'apiusername');

    // Get apipassword from admin setting as well.
    $apipassword = get_config('tool_opencast', 'apipassword');

    // Here we check if credentials are not empty.
    if (empty($apiusername) || empty($apipassword)) {
        // If we want the result to be displayed in adamin page with redirect action.
        if ($redirect) {
            // Redirect back to admin page with a warning message to inform admin that credentials are not yet provided.
            redirect(new \moodle_url('/admin/settings.php?section=tool_opencast'),
                get_string('apiurltestsucceedbutnocredentialslong', 'tool_opencast') , 0, \core\output\notification::NOTIFY_WARNING);
        }
    }

    // In case that redirect is false, it means we call this function from connection test tool and it has to return a successs notification as html tag string.
    if (!$redirect) {
        return \html_writer::tag('p', get_string('apiurltestsuccessfulshort', 'tool_opencast'), array('class' => 'alert alert-success'));
    }

    // When we reach here, it means that everything went fine and the function is called from admin page, therefore
    // we leave it to the admin_settings to handel the rest from here.
}

/**
 * Helperfunction to validate given credentials for the Opencast API URL.
 * It uses curl with Authorization header.
 *
 * @param bool $redirect: When true a redirect action to admin page will occure upon failure. Otherwise, an html tag as string will be returned.
 * 
 * @return string in case the function is called through connection test tool an html tag as string will be returned.
 */
function tool_opencast_test_connection_with_credentials($redirect = true) {
    // Initialize cURL.
    $curl = new \curl();
    $curl->resetHeader();

    // Get apiurl from admin setting.
    $apiurl = get_config('tool_opencast', 'apiurl');

    // Get apiusername from admin setting.
    $apiusername = get_config('tool_opencast', 'apiusername');

    // Get apipassword from admin setting as well.
    $apipassword = get_config('tool_opencast', 'apipassword');

    // Set basic Auth.
    $basicauth = base64_encode($apiusername . ":" .  $apipassword);

    // Set required headers.
    $header[] = 'Content-Type: application/json';
    $header[] = sprintf(
        'Authorization: Basic %s', $basicauth
    );
    $curl->setHeader($header);
    $curl->setopt(array('CURLOPT_HEADER' => false));

    // Use "/api/info/me" endpoint to get information of the logged in user.
    $resource = $apiurl . '/api/info/me';
    $userinfo = $curl->get($resource);

    // Get curl request info.
    $info = $curl->get_info();
    // We don't accept anything other that http_code 200.
    if (!isset($info['http_code']) || $info['http_code'] != 200) {
        // If we want the result to be displayed in adamin page with redirect action.
        if ($redirect) {
            // Redirect back to admin page with related error message.
            redirect(new \moodle_url('/admin/settings.php?section=tool_opencast'),
                get_string('apicreadentialstestfailedlong', 'tool_opencast') , 0, \core\output\notification::NOTIFY_ERROR);
        }

        // Otherwise, we create the error message text with html_write here and return it.
        return html_writer::tag('p', get_string('apicreadentialstestfailedshort', 'tool_opencast'), array('class' => 'alert alert-danger'));
    }

    // In case that redirect is false, it means we call this function from connection test tool and it has to return a successs notification as html tag string.
    if (!$redirect) {
        return html_writer::tag('p', get_string('apicreadentialstestsuccessfulshort', 'tool_opencast'), array('class' => 'alert alert-success'));
    }
    
    // When we reach here, it means that everything went fine and the function is called from admin page, therefore
    // we leave it to the admin_settings to handel the rest from here.
}
