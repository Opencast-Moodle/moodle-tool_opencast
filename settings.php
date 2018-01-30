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

if ($ADMIN->fulltree) {

    $settings = new admin_settingpage('tool_opencast_settings', new lang_string('pluginname', 'tool_opencast'));

    $settings->add(new admin_setting_configtext('apiurl', get_string('apiurl', 'tool_opencast'),
        get_string('apiurldesc', 'tool_opencast'), 'moodle-proxy.rz.tu-ilmenau.de'));
    $settings->add(new admin_setting_configtext('apiusername', get_string('apiusername', 'tool_opencast'),
        get_string('apiusernamedesc', 'tool_opencast'), ''));
    $settings->add(new admin_setting_configpasswordunmask('apipassword', get_string('apipassword', 'tool_opencast'),
        get_string('apipassworddesc', 'tool_opencast'), ''));
    $settings->add(new admin_setting_configduration('connecttimeout', get_string('connecttimeout', 'tool_opencast'),
        get_string('connecttimeoutdesc', 'tool_opencast'), 1));

    $ADMIN->add('tools', $settings);
}
