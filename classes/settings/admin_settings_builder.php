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

namespace tool_opencast\settings;

use tool_opencast\local\settings_api;

/**
 * Static admin setting builder class, which is used, to create and to add admin settings for tool_opencast.
 *
 * @package    tool_opencast
 * @copyright  2022 Matthias Kollenbroich, University of Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_settings_builder {
    /**
     * The name of the plugin tool_opencast.
     *
     * @var string
     */
    private const PLUGINNAME = 'tool_opencast';

    /**
     * The default Opencast instances config.
     *
     * @var string
     */
    private const DEFAULTINSTANCESCONFIG = '[{"id":1,"name":"Default","isvisible":true,"isdefault":true}]';

    /**
     * Make this class not instantiable.
     */
    private function __construct() {
    }

    /**
     * Creates the settings for all Opencast instances and adds them to the admin settings page.
     *
     * @return void
     */
    public static function create_settings() : void {
        $instances = settings_api::get_ocinstances();

        global $ADMIN;
        if (!$ADMIN->fulltree) {
            self::create_settings_no_fulltree($instances);
            return;
        }

        self::create_settings_fulltree($instances);
    }

    /**
     * Creates the settings for all Opencast instances for no fulltree and adds them to the admin settings page.
     *
     * @param array $instances
     * The Opencast instances.
     *
     * @return void
     */
    private static function create_settings_no_fulltree($instances) : void {
        self::add_admin_category();
        self::add_admin_settingpage('tool_opencast_instances', 'ocinstances');

        if (count($instances) <= 1) {
            self::add_admin_settingpage('tool_opencast_configuration', 'configuration');
            return;
        }

        foreach ($instances as $instance) {
            self::add_admin_settingpage('tool_opencast_configuration_' . $instance->id,
                'configuration_instance', $instance->name);
        }
    }

    /**
     * Creates the settings for all Opencast instances for fulltree and adds them to the admin settings page.
     *
     * @param array $instances
     * The Opencast instances.
     *
     * @return void
     */
    private static function create_settings_fulltree($instances) : void {
        self::add_admin_category();
        self::add_admin_instances_config();

        foreach ($instances as $instance) {
            $instanceid = $instance->id;

            if (count($instances) <= 1) {
                $settings = self::create_admin_settingpage('tool_opencast_configuration',
                    'configuration');
            } else {
                $settings = self::create_admin_settingpage('tool_opencast_configuration_' . $instanceid,
                    'configuration_instance', $instance->name);
            }

            self::add_notification_banner_for_demo_instance($settings, $instanceid);
            self::add_config_settings_fulltree($settings, $instanceid);
            self::add_connection_test_tool($settings, $instanceid);

            self::include_admin_settingpage($settings);
        }
    }

    /**
     * Adds an admin category to the admin settings page.
     *
     * @return void
     */
    private static function add_admin_category() : void {
        $category = new \admin_category(self::PLUGINNAME,
            new \lang_string('pluginname', self::PLUGINNAME)
        );

        global $ADMIN;
        $ADMIN->add('tools', $category);
    }

    /**
     * Adds an admin settingpage to the admin settings page.
     *
     * @param string $name
     * The internal name for this settingpage.
     *
     * @param string $stringidentifier
     * The identifier for the string, that is used for the displayed name for this settingpage.
     *
     * @param stdClass|array $stringidentifierarguments
     * Optional arguments, which the string for the passed identifier requires,
     * that is used for the displayed name for this settingpage.
     *
     * @return void
     */
    private static function add_admin_settingpage(string $name, string $stringidentifier,
                                                  $stringidentifierarguments = null) : void {
        $settingpage = self::create_admin_settingpage($name, $stringidentifier, $stringidentifierarguments);
        self::include_admin_settingpage($settingpage);
    }

    /**
     * Creates an admin settingpage.
     *
     * @param string $name
     * The internal name for this settingpage.
     *
     * @param string $stringidentifier
     * The identifier for the string, that is used for the displayed name for this settingpage.
     *
     * @param stdClass|array $stringidentifierarguments
     * Optional arguments, which the string for the passed identifier requires,
     * that is used for the displayed name for this settingpage.
     *
     * @return \admin_settingpage
     * The created admin settingpage.
     */
    private static function create_admin_settingpage(string $name, string $stringidentifier,
                                                     $stringidentifierarguments = null) : \admin_settingpage {
        return new \admin_settingpage($name,
            new \lang_string($stringidentifier, self::PLUGINNAME, $stringidentifierarguments)
        );
    }

    /**
     * Includes an admin settingpage in the admin settings page.
     *
     * @param \admin_settingpage $settingpage
     * The admin settingpage to include.
     *
     * @return void
     */
    private static function include_admin_settingpage(\admin_settingpage $settingpage) : void {
        global $ADMIN;
        $ADMIN->add(self::PLUGINNAME, $settingpage);
    }

    /**
     * Adds the admin instances config to the admin settings page.
     *
     * The admin instances config is part of the admin settings and
     * consists of the table with its description of added Opencast instances as well as
     * of the button for adding a new Opencast instance.
     *
     * Note, that this function calls self::require_amds with the id of the added admin instances config.
     *
     * @return void
     */
    private static function add_admin_instances_config() : void {
        $instancesconfig = new admin_setting_configtextwithvalidation(
            'tool_opencast/ocinstances',
            get_string('ocinstances', self::PLUGINNAME),
            get_string('ocinstancesdesc', self::PLUGINNAME),
            self::DEFAULTINSTANCESCONFIG
        );

        self::require_amds($instancesconfig->get_id());

        $instancessettings = new \admin_settingpage(
            'tool_opencast_instances',
            new \lang_string('ocinstances', self::PLUGINNAME)
        );

        $instancessettings->add($instancesconfig);

        $instancessettings->add(new admin_setting_configeditabletable(
                'tool_opencast/instancestable',
                'instancestable')
        );

        global $ADMIN;
        $ADMIN->add(self::PLUGINNAME, $instancessettings);
    }

    /**
     * Requires jquery, amds and css for $PAGE.
     *
     * @param string $pluginnameid
     * The id of the admin instances config of the admin settings of the plugin.
     *
     * @return void
     */
    private static function require_amds(string $pluginnameid) : void {
        global $PAGE;

        // Crashes, if plugins.php is opened, because css cannot be included anymore.
        if ($PAGE->state === \moodle_page::STATE_IN_BODY) {
            return;
        }

        $PAGE->requires->jquery();
        $PAGE->requires->js_call_amd('tool_opencast/tool_testtool', 'init');
        $PAGE->requires->js_call_amd('tool_opencast/tool_settings', 'init', [$pluginnameid]);
        $PAGE->requires->css('/admin/tool/opencast/css/tabulator.min.css');
        $PAGE->requires->css('/admin/tool/opencast/css/tabulator_bootstrap4.min.css');
    }

    /**
     * Adds a notification banner to the passed admin settingpage for the passed Opencast instance id,
     * if the plugin is connected to the Opencast demo server for this Opencast instance id.
     *
     * @param \admin_settingpage $settings
     * The admin settingpage, to add a notification banner to.
     *
     * @param int $instanceid
     * The id of the Opencast instance, for which the notification banner is added.
     *
     * @return void
     */
    private static function add_notification_banner_for_demo_instance(\admin_settingpage $settings,
                                                                      int $instanceid) : void {
        $instanceapiurl = settings_api::get_apiurl($instanceid);

        // Show a notification banner, if the plugin is connected to the Opencast demo server.
        if (strpos($instanceapiurl, 'stable.opencast.org') !== false) {
            global $OUTPUT;
            $demoservernotification = $OUTPUT->notification(
                get_string('demoservernotification', self::PLUGINNAME),
                \core\output\notification::NOTIFY_WARNING
            );

            $settings->add(new \admin_setting_heading(
                    'tool_opencast/demoservernotification_' . $instanceid,
                    '',
                    $demoservernotification)
            );
        }
    }

    /**
     * Adds the config settings for fulltree to the passed admin settingpage for the
     * passed Opencast instance id.
     *
     * @param \admin_settingpage $settings
     * The admin settingpage, the config settings are added to.
     *
     * @param int $instanceid
     * The Opencast instance id, to that the added settings are associated.
     *
     * @return void
     */
    private static function add_config_settings_fulltree(\admin_settingpage $settings,
                                                         int $instanceid) : void {
        self::add_admin_setting_configtext($settings,
            'tool_opencast/apiurl_' . $instanceid,
            'apiurl', 'apiurldesc',
            'https://stable.opencast.org',
            PARAM_URL
        );

        self::add_admin_setting_configtext($settings,
            'tool_opencast/apiversion_' . $instanceid,
            'apiversion', 'apiversiondesc',
            ''
        );

        self::add_admin_setting_configtext($settings,
            'tool_opencast/apiusername_' . $instanceid,
            'apiusername', 'apiusernamedesc',
            'admin'
        );

        self::add_admin_setting_configpasswordunmask($settings,
            'tool_opencast/apipassword_' . $instanceid,
            'apipassword', 'apipassworddesc',
            'opencast'
        );

        self::add_admin_setting_configtext($settings,
            'tool_opencast/lticonsumerkey_' . $instanceid,
            'lticonsumerkey', 'lticonsumerkey_desc',
            ''
        );

        self::add_admin_setting_configpasswordunmask($settings,
            'tool_opencast/lticonsumersecret_' . $instanceid,
            'lticonsumersecret', 'lticonsumersecret_desc',
            ''
        );

        self::add_admin_setting_configtext($settings,
            'tool_opencast/apitimeout_' . $instanceid,
            'timeout', 'timeoutdesc',
            '2000',
            PARAM_INT
        );

        self::add_admin_setting_configtext($settings,
            'tool_opencast/apiconnecttimeout_' . $instanceid,
            'connecttimeout', 'connecttimeoutdesc',
            '1000',
            PARAM_INT
        );
    }

    /**
     * Adds an admin setting configtext to the passed admin settingpage.
     *
     * @param \admin_settingpage $settings
     * The admin settingpage, the configtext is added to.
     *
     * @param string $name
     * The internal name for the configtext.
     *
     * @param string $visiblenameidentifier
     * The identifier for the string, that is used for the visible name of the configtext.
     *
     * @param string $descriptionidentifier
     * The identifier for the string, that is used for the visible description of the configtext.
     *
     * @param string $defaultsetting
     * The default setting for the configtext.
     *
     * @param mixed $paramtype
     * The parameter type of the configtext.
     *
     * @return void
     */
    private static function add_admin_setting_configtext(\admin_settingpage $settings,
                                                         string $name,
                                                         string $visiblenameidentifier,
                                                         string $descriptionidentifier,
                                                         string $defaultsetting,
                                                         $paramtype = PARAM_RAW) : void {
        $settingconfigtext = new \admin_setting_configtext(
            $name,
            get_string($visiblenameidentifier, self::PLUGINNAME),
            get_string($descriptionidentifier, self::PLUGINNAME),
            $defaultsetting,
            $paramtype
        );
        $settings->add($settingconfigtext);
    }

    /**
     * Adds an admin setting configpasswordunmask to the passed admin settingpage.
     *
     * @param \admin_settingpage $settings
     * The admin settingpage, the configpasswordunmask is added to.
     *
     * @param string $name
     * The internal name for the configpasswordunmask.
     *
     * @param string $visiblenameidentifier
     * The identifier for the string, that is used for the visible name of the configpasswordunmask.
     *
     * @param string $descriptionidentifier
     * The identifier for the string, that is used for the visible description of the configpasswordunmask.
     *
     * @param string $defaultsetting
     * The default password for the configpasswordunmask.
     *
     * @return void
     */
    private static function add_admin_setting_configpasswordunmask(\admin_settingpage $settings,
                                                                   string $name,
                                                                   string $visiblenameidentifier,
                                                                   string $descriptionidentifier,
                                                                   string $defaultsetting) : void {
        $settingconfigpasswordunmask = new \admin_setting_configpasswordunmask(
            $name,
            get_string($visiblenameidentifier, self::PLUGINNAME),
            get_string($descriptionidentifier, self::PLUGINNAME),
            $defaultsetting
        );
        $settings->add($settingconfigpasswordunmask);
    }

    /**
     * Adds the connection test tool to the passed admin settingpage for the passed Opencast instance id,
     * where a button with its description is added to the passed admin settingpage,
     * that can be clicked, to perform this connection test to the corresponding Opencast instance.
     *
     * A popup is shown, containing the results of this connection test, if this connection test is completed.
     *
     * @param \admin_settingpage $settings
     * The admin settingpage, to add the connection test tool to.
     *
     * @param int $instanceid
     * The id of the Opencast instance, for which the connection test tool is added and the connection test
     * is performed.
     *
     * @return void
     */
    private static function add_connection_test_tool(\admin_settingpage $settings,
                                                     int $instanceid) : void {
        // Provide Connection Test Tool button.
        $attributes = [
            'class' => 'btn btn-warning disabled testtool-modal',
            'disabled' => 'disabled',
            'title' => get_string('testtooldisabledbuttontitle', self::PLUGINNAME),
            'data-instanceid' => strval($instanceid),
        ];

        $connectiontoolbutton = \html_writer::tag(
            'button',
            get_string('testtoolurl', self::PLUGINNAME),
            $attributes
        );

        // Place the button inside the header description.
        $settings->add(new \admin_setting_heading(
            'tool_opencast/testtoolexternalpage',
            get_string('testtoolheader', self::PLUGINNAME),
            get_string('testtoolheaderdesc', self::PLUGINNAME, $connectiontoolbutton))
        );
    }
}
