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

use tool_opencast\admin_setting_configeditabletable;

defined('MOODLE_INTERNAL') || die();

global $OUTPUT, $DB, $PAGE, $ADMIN, $CFG;

if ($hassiteconfig) {
    $settingscategory = new admin_category('tool_opencast', new lang_string('pluginname', 'tool_opencast'));
    $ADMIN->add('tools', $settingscategory);

    $instances = json_decode(get_config('tool_opencast', 'ocinstances'));

    if (!$ADMIN->fulltree) {
        $settings = new admin_settingpage('tool_opencast_instances', new lang_string('ocinstances', 'tool_opencast'));
        $ADMIN->add('tool_opencast', $settings);

        if (count($instances) <= 1) {
            $settings = new admin_settingpage('tool_opencast_configuration', new lang_string('configuration', 'tool_opencast'));
            $ADMIN->add('tool_opencast', $settings);
        } else {
            foreach ($instances as $instance) {
                $settings = new admin_settingpage('tool_opencast_configuration_' . $instance->id,
                    new lang_string('configuration_instance', 'tool_opencast', $instance->name));
                $ADMIN->add('tool_opencast', $settings);
            }
        }
    } else {
        // Require the lib to use in set_updatedcallback method(s).
        require_once($CFG->dirroot.'/admin/tool/opencast/lib.php');

        // To add connection test tool required js to the page.
        if (isset($PAGE) AND is_callable(array($PAGE->requires, 'js'))) {
            $PAGE->requires->jquery();
            $PAGE->requires->js_call_amd('tool_opencast/opencasttesttool', 'init');
        }

        // TODO hide
        // TODO add general description describing the settings and effects.
        $instancesconfig = new admin_setting_configtext('tool_opencast/ocinstances',
            get_string('ocinstances', 'tool_opencast'),
            get_string('ocinstancesdesc',
                'tool_opencast'), '[{"id":1,"name":"Default","isvisible":true,"isdefault":true}]');
        $instancesconfig->set_updatedcallback(function () {
            // TODO move this function somewhere else.

            // TODO handle default.

            // Todo handle delete.
            $adminroot = admin_get_root();
            $toolsettings = $adminroot->locate('tool_opencast');
            foreach($toolsettings->get_children() as $child) {
                if(substr($child->name, 0, 28) == 'tool_opencast_configuration_') {
                    foreach($child->settings as $name => $setting) {
                        $data = $setting->get_setting();
                        if (is_null($data)) {
                            $data = $setting->get_defaultsetting();
                            $setting->write_setting($data);
                        }
                    }
                }
            }

            // Block settings.
            if (core_plugin_manager::instance()->get_plugin_info('block_opencast')) {
                $blocksettings = $adminroot->locate('block_opencast');
                foreach($blocksettings->get_children() as $category) {
                    if($category instanceof admin_category) {
                        foreach($category->get_children() as $child) {
                            foreach ($child->settings as $name => $setting) {
                                $data = $setting->get_setting();
                                if (is_null($data)) {
                                    $data = $setting->get_defaultsetting();
                                    if(!is_null($data)) {
                                        $setting->write_setting($data);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Activity settings.
            if (core_plugin_manager::instance()->get_plugin_info('mod_opencast')) {
                $modsettings = $adminroot->locate('modsettingopencast');
                foreach ($modsettings->settings as $name => $setting) {
                    $data = $setting->get_setting();
                    if (is_null($data)) {
                        $data = $setting->get_defaultsetting();
                        $setting->write_setting($data);
                    }
                }
            }

            // TODO filter repository?




        });


        // Crashes if plugins.php is opened because css cannot be included anymore.
        if ($PAGE->state !== moodle_page::STATE_IN_BODY) {
            $PAGE->requires->js_call_amd('tool_opencast/tool_settings', 'init', [$instancesconfig->get_id()]);
            $PAGE->requires->css('/admin/tool/opencast/css/tabulator.min.css');
            $PAGE->requires->css('/admin/tool/opencast/css/tabulator_bootstrap4.min.css');
        }

        $instancessettings = new admin_settingpage('tool_opencast_instances', new lang_string('ocinstances', 'tool_opencast'));

        $instancessettings->add($instancesconfig);
        $instancessettings->add(new admin_setting_configeditabletable('tool_opencast/instancestable', 'instancestable'));
        $ADMIN->add('tool_opencast', $instancessettings);

        foreach ($instances as $instance) {
            if (count($instances) <= 1) {
                $settings = new admin_settingpage('tool_opencast_configuration',
                    new lang_string('configuration', 'tool_opencast'));
            }
             else {
                 $settings = new admin_settingpage('tool_opencast_configuration_' . $instance->id,
                     new lang_string('configuration_instance', 'tool_opencast', $instance->name));
             }


            if ($instance->isdefault) {
                // Show a notification banner if the plugin is connected to the Opencast demo server.
                if (strpos(get_config('tool_opencast', 'apiurl'), 'stable.opencast.org') !== false) {
                    $demoservernotification = $OUTPUT->notification(get_string('demoservernotification', 'tool_opencast'), \core\output\notification::NOTIFY_WARNING);
                    $settings->add(new admin_setting_heading('tool_opencast/demoservernotification', '', $demoservernotification));
                }

                $apiurlsetting = new admin_setting_configtext('tool_opencast/apiurl', get_string('apiurl', 'tool_opencast'),
                    get_string('apiurldesc', 'tool_opencast'), 'https://stable.opencast.org', PARAM_URL);
                // Set updatedcallback for API URL to validate the given url.
                $apiurlsetting->set_updatedcallback('tool_opencast_test_url_connection');
                $settings->add($apiurlsetting);

                $apiusernamesetting = new admin_setting_configtext('tool_opencast/apiusername', get_string('apiusername', 'tool_opencast'),
                    get_string('apiusernamedesc', 'tool_opencast'), 'admin');
                $apiusernamesetting->set_updatedcallback('tool_opencast_test_connection_with_credentials');
                $settings->add($apiusernamesetting);

                $apipasswordsetting = new admin_setting_configpasswordunmask('tool_opencast/apipassword', get_string('apipassword', 'tool_opencast'),
                    get_string('apipassworddesc', 'tool_opencast'), 'opencast');
                $apipasswordsetting->set_updatedcallback('tool_opencast_test_connection_with_credentials');
                $settings->add($apipasswordsetting);

                $settings->add(new admin_setting_configduration('tool_opencast/connecttimeout', get_string('connecttimeout', 'tool_opencast'),
                    get_string('connecttimeoutdesc', 'tool_opencast'), 1));

            } else {
                // Show a notification banner if the plugin is connected to the Opencast demo server.
                if (strpos(get_config('tool_opencast', 'apiurl_' . $instance->id), 'stable.opencast.org') !== false) {
                    $demoservernotification = $OUTPUT->notification(get_string('demoservernotification', 'tool_opencast'), \core\output\notification::NOTIFY_WARNING);
                    $settings->add(new admin_setting_heading('tool_opencast/demoservernotification_' . $instance->id, '', $demoservernotification));
                }

                $apiurlsetting = new admin_setting_configtext('tool_opencast/apiurl_' . $instance->id, get_string('apiurl', 'tool_opencast'),
                    get_string('apiurldesc', 'tool_opencast'), 'https://stable.opencast.org', PARAM_URL);
                $apiurlsetting->set_updatedcallback('tool_opencast_test_url_connection');
                $settings->add($apiurlsetting);

                $apiusernamesetting = new admin_setting_configtext('tool_opencast/apiusername_' . $instance->id, get_string('apiusername', 'tool_opencast'),
                    get_string('apiusernamedesc', 'tool_opencast'), 'admin');
                $apiusernamesetting->set_updatedcallback('tool_opencast_test_connection_with_credentials');
                $settings->add($apiusernamesetting);

                $apipasswordsetting = new admin_setting_configpasswordunmask('tool_opencast/apipassword_' . $instance->id, get_string('apipassword', 'tool_opencast'),
                    get_string('apipassworddesc', 'tool_opencast'), 'opencast');
                $apipasswordsetting->set_updatedcallback('tool_opencast_test_connection_with_credentials');
                $settings->add($apipasswordsetting);

                $settings->add(new admin_setting_configduration('tool_opencast/connecttimeout_' . $instance->id, get_string('connecttimeout', 'tool_opencast'),
                    get_string('connecttimeoutdesc', 'tool_opencast'), 1));
            }

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

            $ADMIN->add('tool_opencast', $settings);
        }
    }
}
