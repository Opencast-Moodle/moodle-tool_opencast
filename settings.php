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

global $OUTPUT;

if ($hassiteconfig) {

    $settings = new admin_settingpage('tool_opencast', new lang_string('pluginname', 'tool_opencast'));

    // Show a notification banner if the plugin is connected to the Opencast demo server.
    if (strpos(get_config('tool_opencast', 'apiurl'), 'stable.opencast.org') !== false) {
        $demoservernotification = $OUTPUT->notification(get_string('demoservernotification', 'tool_opencast'), \core\output\notification::NOTIFY_WARNING);
        $settings->add(new admin_setting_heading('tool_opencast/demoservernotification', '', $demoservernotification));
    }

    $settings->add(new admin_setting_configtext('tool_opencast/apiurl', get_string('apiurl', 'tool_opencast'),
        get_string('apiurldesc', 'tool_opencast'), 'https://stable.opencast.org', PARAM_URL));
    $settings->add(new admin_setting_configtext('tool_opencast/apiusername', get_string('apiusername', 'tool_opencast'),
        get_string('apiusernamedesc', 'tool_opencast'), 'admin'));
    $settings->add(new admin_setting_configpasswordunmask('tool_opencast/apipassword', get_string('apipassword', 'tool_opencast'),
        get_string('apipassworddesc', 'tool_opencast'), 'opencast'));
    $settings->add(new admin_setting_configduration('tool_opencast/connecttimeout', get_string('connecttimeout', 'tool_opencast'),
        get_string('connecttimeoutdesc', 'tool_opencast'), 1));

    $ADMIN->add('tools', $settings);
}
