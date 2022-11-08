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
use tool_opencast\admin_setting_configtextwithvalidation;

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

        $instancesconfig = new admin_setting_configtextwithvalidation('tool_opencast/ocinstances',
            get_string('ocinstances', 'tool_opencast'),
            get_string('ocinstancesdesc',
                'tool_opencast'), '[{"id":1,"name":"Default","isvisible":true,"isdefault":true}]');


        // Crashes if plugins.php is opened because css cannot be included anymore.
        if ($PAGE->state !== moodle_page::STATE_IN_BODY) {
            $PAGE->requires->jquery();
            $PAGE->requires->js_call_amd('tool_opencast/tool_testtool', 'init');
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
            } else {
                $settings = new admin_settingpage('tool_opencast_configuration_' . $instance->id,
                    new lang_string('configuration_instance', 'tool_opencast', $instance->name));
            }


            if ($instance->isdefault) {
                // Show a notification banner if the plugin is connected to the Opencast demo server.
                if (strpos(get_config('tool_opencast', 'apiurl'), 'stable.opencast.org') !== false) {
                    $demoservernotification = $OUTPUT->notification(get_string('demoservernotification', 'tool_opencast'),
                        \core\output\notification::NOTIFY_WARNING);
                    $settings->add(new admin_setting_heading('tool_opencast/demoservernotification', '', $demoservernotification));
                }

                $apiurlsetting = new admin_setting_configtext('tool_opencast/apiurl', get_string('apiurl', 'tool_opencast'),
                    get_string('apiurldesc', 'tool_opencast'), 'https://stable.opencast.org', PARAM_URL);
                $settings->add($apiurlsetting);

                $apiusernamesetting = new admin_setting_configtext('tool_opencast/apiusername',
                    get_string('apiusername', 'tool_opencast'),
                    get_string('apiusernamedesc', 'tool_opencast'), 'admin');
                $settings->add($apiusernamesetting);

                $apipasswordsetting = new admin_setting_configpasswordunmask('tool_opencast/apipassword',
                    get_string('apipassword', 'tool_opencast'),
                    get_string('apipassworddesc', 'tool_opencast'), 'opencast');
                $settings->add($apipasswordsetting);

                $lticonsumerkeysetting = new admin_setting_configtext('tool_opencast/lticonsumerkey',
                    get_string('lticonsumerkey', 'tool_opencast'),
                    get_string('lticonsumerkey_desc', 'tool_opencast'), "");
                $settings->add($lticonsumerkeysetting);

                $lticonsumersecretsetting = new admin_setting_configpasswordunmask('tool_opencast/lticonsumersecret',
                    get_string('lticonsumersecret', 'tool_opencast'),
                    get_string('lticonsumersecret_desc', 'tool_opencast'), "");
                $settings->add($lticonsumersecretsetting);

                $settings->add(new admin_setting_configtext('tool_opencast/apitimeout',
                    get_string('timeout', 'tool_opencast'),
                    get_string('timeoutdesc', 'tool_opencast'), 2000, PARAM_INT));

                $settings->add(new admin_setting_configtext('tool_opencast/apiconnecttimeout',
                    get_string('connecttimeout', 'tool_opencast'),
                    get_string('connecttimeoutdesc', 'tool_opencast'), 1000, PARAM_INT));

            } else {
                // Show a notification banner if the plugin is connected to the Opencast demo server.
                if (strpos(get_config('tool_opencast', 'apiurl_' . $instance->id), 'stable.opencast.org') !== false) {
                    $demoservernotification = $OUTPUT->notification(get_string('demoservernotification', 'tool_opencast'),
                        \core\output\notification::NOTIFY_WARNING);
                    $settings->add(new admin_setting_heading('tool_opencast/demoservernotification_' . $instance->id,
                        '', $demoservernotification));
                }

                $apiurlsetting = new admin_setting_configtext('tool_opencast/apiurl_' . $instance->id,
                    get_string('apiurl', 'tool_opencast'),
                    get_string('apiurldesc', 'tool_opencast'), 'https://stable.opencast.org', PARAM_URL);
                $settings->add($apiurlsetting);

                $apiusernamesetting = new admin_setting_configtext('tool_opencast/apiusername_' . $instance->id,
                    get_string('apiusername', 'tool_opencast'),
                    get_string('apiusernamedesc', 'tool_opencast'), 'admin');
                $settings->add($apiusernamesetting);

                $apipasswordsetting = new admin_setting_configpasswordunmask('tool_opencast/apipassword_' . $instance->id,
                    get_string('apipassword', 'tool_opencast'),
                    get_string('apipassworddesc', 'tool_opencast'), 'opencast');
                $settings->add($apipasswordsetting);

                $lticonsumerkeysetting = new admin_setting_configtext('tool_opencast/lticonsumerkey_' . $instance->id,
                    get_string('lticonsumerkey', 'tool_opencast'),
                    get_string('lticonsumerkey_desc', 'tool_opencast'), "");
                $settings->add($lticonsumerkeysetting);

                $lticonsumersecretsetting = new admin_setting_configpasswordunmask(
                    'tool_opencast/lticonsumersecret_' . $instance->id,
                    get_string('lticonsumersecret', 'tool_opencast'),
                    get_string('lticonsumersecret_desc', 'tool_opencast'), "");
                $settings->add($lticonsumersecretsetting);

                $settings->add(new admin_setting_configtext('tool_opencast/apitimeout_' . $instance->id,
                    get_string('timeout', 'tool_opencast'),
                    get_string('timeoutdesc', 'tool_opencast'), 2000, PARAM_INT));

                $settings->add(new admin_setting_configtext('tool_opencast/apiconnecttimeout_' . $instance->id,
                    get_string('connecttimeout', 'tool_opencast'),
                    get_string('connecttimeoutdesc', 'tool_opencast'), 1000, PARAM_INT));
            }

            // Provide Connection Test Tool button.
            $attributes = [
                'class' => 'btn btn-warning disabled testtool-modal',
                'disabled' => 'disabled',
                'title' => get_string('testtooldisabledbuttontitle', 'tool_opencast'),
                'data-instanceid' => ($instance->isdefault) ? '' : $instance->id
            ];
            $connectiontoolbutton = html_writer::tag('button', get_string('testtoolurl', 'tool_opencast'), $attributes);
            // Place the button inside the header description.
            $settings->add(new admin_setting_heading('tool_opencast/testtoolexternalpage',
                get_string('testtoolheader', 'tool_opencast'),
                get_string('testtoolheaderdesc', 'tool_opencast', $connectiontoolbutton)));

            $ADMIN->add('tool_opencast', $settings);
        }
    }
}
