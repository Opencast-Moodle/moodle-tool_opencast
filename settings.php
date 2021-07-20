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
 * Plugin administration pages are defined here.
 *
 * @package     tool_opencast
 * @category    admin
 * @copyright   2018 Tobias Reischmann <tobias.reischmann@wi.uni-muenster.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $OUTPUT, $CFG;

if ($hassiteconfig) {

    // Require the lib to use in set_updatedcallback method(s).
    require_once($CFG->dirroot.'/admin/tool/opencast/lib.php');

    // To add connection test tool required js to the page.
    if (isset($PAGE) AND is_callable(array($PAGE->requires, 'js'))) {
        $PAGE->requires->jquery();
        $PAGE->requires->js_call_amd('tool_opencast/opencasttesttool', 'init');
    }

    $settings = new admin_settingpage('tool_opencast', new lang_string('pluginname', 'tool_opencast'));

    // Show a notification banner if the plugin is connected to the Opencast demo server.
    if (strpos(get_config('tool_opencast', 'apiurl'), 'stable.opencast.org') !== false) {
        $demoservernotification = $OUTPUT->notification(get_string('demoservernotification', 'tool_opencast'), \core\output\notification::NOTIFY_WARNING);
        $settings->add(new admin_setting_heading('tool_opencast/demoservernotification', '', $demoservernotification));
    }

    // Admin setting for API URL.
    $apiurlsetting = new admin_setting_configtext('tool_opencast/apiurl', get_string('apiurl', 'tool_opencast'),
        get_string('apiurldesc', 'tool_opencast'), 'https://stable.opencast.org', PARAM_URL);
    // Set updatedcallback for API URL to validate the given url.
    $apiurlsetting->set_updatedcallback('tool_opencast_test_url_connection');
    $settings->add($apiurlsetting);

    // Admin setting for API user.
    $apiusernamesetting = new admin_setting_configtext('tool_opencast/apiusername', get_string('apiusername', 'tool_opencast'),
        get_string('apiusernamedesc', 'tool_opencast'), 'admin');
    // Set updatedcallback for API user to validate the given username.
    $apiusernamesetting->set_updatedcallback('tool_opencast_test_connection_with_credentials');
    $settings->add($apiusernamesetting);

    // Admin setting for the Password of API user.
    $apipasswordsetting = new admin_setting_configpasswordunmask('tool_opencast/apipassword', get_string('apipassword', 'tool_opencast'),
        get_string('apipassworddesc', 'tool_opencast'), 'opencast');
    // Set updatedcallback for the Password of API user to validate the given password.
    $apipasswordsetting->set_updatedcallback('tool_opencast_test_connection_with_credentials');
    $settings->add($apipasswordsetting);

    $settings->add(new admin_setting_configduration('tool_opencast/connecttimeout', get_string('connecttimeout', 'tool_opencast'),
        get_string('connecttimeoutdesc', 'tool_opencast'), 1));

    // Provide Connection Test Tool button.
    $attributes = [
        'id' => 'testtool-modal',
        'class' => 'btn btn-warning disabled',
        'disabled' => 'disabled',
        'title' => get_string('testtooldisabledbuttontitle', 'tool_opencast')
    ];
    $connectiontoolbutton = html_writer::tag('button', get_string('testtoolurl', 'tool_opencast'), $attributes);
    // Place the button inside the header description.
    $settings->add(new admin_setting_heading('tool_opencast/testtoolexternalpage', get_string('testtoolheader', 'tool_opencast'),
        get_string('testtoolheaderdesc', 'tool_opencast', $connectiontoolbutton)));

    $ADMIN->add('tools', $settings);
}
