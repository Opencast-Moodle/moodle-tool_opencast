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

defined('MOODLE_INTERNAL') || die();

use core\notification;
use tool_opencast\local\settings_api;
use tool_opencast\local\maintenance_class;
use tool_opencast\exception\opencast_api_response_exception;
use tool_opencast\local\visibility_helper;
use tool_opencast\local\ltimodulemanager;
use tool_opencast\empty_configuration_exception;
use tool_opencast\setting_default_manager;


require_once(__DIR__ . '/admin_setting_configeditabletable.php');
require_once(__DIR__ . '/admin_setting_hiddenhelpbtn.php');
require_once(__DIR__ . '/setting_helper.php');
require_once(__DIR__ . '/admin_setting_configtextvalidate.php');


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
    public static function create_settings(): void {
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
    private static function create_settings_no_fulltree($instances): void {
        self::add_admin_category();
        self::add_admin_settingpage('tool_opencast_instances', 'ocinstances');

        if (count($instances) <= 1) {
            self::add_admin_settingpage('tool_opencast_configuration', 'configuration');
            self::add_admin_settingpage('tool_opencast_generalsettings_1', 'general_settings');
            self::add_admin_settingpage('tool_opencast_appearancesettings_1', 'appearance_settings');
            self::add_admin_settingpage('tool_opencast_additionalsettings_1', 'additional_settings');
            self::add_admin_settingpage('tool_opencast_ltimodulesettings_1', 'ltimodule_settings');
            self::add_admin_settingpage('tool_opencast_importvideossettings_1', 'importvideos_settings');

        } else {
            foreach ($instances as $instance) {
                self::add_admin_settingpage(
                    'tool_opencast_configuration_' . $instance->id, 'configuration_instance', $instance->name);
                self::add_admin_settingpage(
                    'tool_opencast_generalsettings_' . $instance->id, 'general_instance', $instance->name);
                self::add_admin_settingpage(
                    'tool_opencast_appearancesettings_' . $instance->id, 'appearance_instance', $instance->name);
                self::add_admin_settingpage(
                    'tool_opencast_additionalsettings_' . $instance->id, 'additional_instance', $instance->name);
                self::add_admin_settingpage(
                    'tool_opencast_ltimodulesettings_' . $instance->id, 'ltimodule_instance', $instance->name);
                self::add_admin_settingpage(
                    'tool_opencast_importvideossettings_' . $instance->id, 'importvideos_instance', $instance->name);

            }
        }

        self::add_admin_settingpage('tool_opencast_sharedsettings', 'shared_settings');
    }

    /**
     * Creates the settings for all Opencast instances for fulltree and adds them to the admin settings page.
     *
     * @param array $instances
     * The Opencast instances.
     *
     * @return void
     */
    private static function create_settings_fulltree($instances): void {
        self::add_admin_category();
        self::add_admin_instances_config();

        foreach ($instances as $instance) {
            $instanceid = $instance->id;

            if (count($instances) <= 1) {
                $settings = self::create_admin_settingpage('tool_opencast_configuration', 'configuration');
            } else {
                $settings = self::create_admin_settingpage('tool_opencast_configuration_' . $instanceid,
                    'configuration_instance', $instance->name);
            }

            self::add_notification_banner_for_demo_instance($settings, $instanceid);
            self::add_config_settings_fulltree($settings, $instanceid);
            self::add_maintenance_mode_block($settings, $instanceid);
            self::add_connection_test_tool($settings, $instanceid);

            self::include_admin_settingpage($settings);

            self::add_admin_general_settings($settings, $instanceid, $instance);
            self::add_admin_appearance_settings($settings, $instanceid, $instance);
            self::add_admin_additional_settings($settings, $instanceid, $instance);
            self::add_admin_ltimodule_settings($settings, $instanceid, $instance);
            self::add_admin_importvideos_settings($settings, $instanceid, $instance);

        }

        self::add_admin_shared_settings();

    }

    /**
     * Adds an admin category to the admin settings page.
     *
     * @return void
     */
    private static function add_admin_category(): void {
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
                                                  $stringidentifierarguments = null): void {
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
                                                     $stringidentifierarguments = null): \admin_settingpage {
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
    private static function include_admin_settingpage(\admin_settingpage $settingpage): void {
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
    private static function add_admin_instances_config(): void {
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

        $instancessettings->add(new admin_setting_configeditabletable_addinstance(
                'tool_opencast/instancestable',
                'instancestable')
        );

        global $ADMIN;
        $ADMIN->add(self::PLUGINNAME, $instancessettings);
    }

    /**
     * Adds the shared admin settings for all Opencast instances.
     * @return void
     */
    private static function add_admin_shared_settings(): void {

        global $ADMIN;

        // Shared Settings Page.
        $sharedsettings = self::create_admin_settingpage('tool_opencast_sharedsettings',
                    'shared_settings');

        // Cache Validtime.
        self::add_admin_setting_configtext($sharedsettings, 'tool_opencast/cachevalidtime',
            'cachevalidtime',
            'cachevalidtime_desc', 500, PARAM_INT);

        // Upload timeout.
        self::add_admin_setting_configtext($sharedsettings, 'tool_opencast/uploadtimeout',
            'uploadtimeout',
            'uploadtimeoutdesc', 60, PARAM_INT);

        // Failedupload retrylimit.
        self::add_admin_setting_configtext($sharedsettings, 'tool_opencast/faileduploadretrylimit',
        'faileduploadretrylimit',
        'faileduploadretrylimitdesc', 0, PARAM_INT);

        $ADMIN->add(self::PLUGINNAME, $sharedsettings);
    }

    /**
     * Adds the appearance admin settings.
     *
     * @param \admin_settingpage $settings
     * The admin settingpage to add the notification banner to.
     *
     * @param int $instanceid
     * The id of the Opencast instance.
     *
     * @param object $instance
     *
     * @return void
     */
    private static function add_admin_appearance_settings($settings, $instanceid, $instance): void {

        global $ADMIN;

        // Settings page: Appearance settings.
        $appearancesettings = self::create_admin_settingpage(
            'tool_opencast_appearancesettings_' . $instance->id, 'appearance_settings');

        $appearancesettings->add(
            new \admin_setting_heading('tool_opencast/appearance_overview_' . $instance->id,
                get_string('appearance_overview_settingheader', 'tool_opencast'),
                ''));

        $appearancesettings->add(
            new \admin_setting_configcheckbox('tool_opencast/showpublicationchannels_' . $instance->id,
                get_string('appearance_overview_settingshowpublicationchannels', 'tool_opencast'),
                get_string('appearance_overview_settingshowpublicationchannels_desc', 'tool_opencast'), 1));

        $appearancesettings->add(
            new \admin_setting_configcheckbox('tool_opencast/showenddate_' . $instance->id,
                get_string('appearance_overview_settingshowenddate', 'tool_opencast'),
                get_string('appearance_overview_settingshowenddate_desc', 'tool_opencast'), 1));

        $appearancesettings->add(
            new \admin_setting_configcheckbox('tool_opencast/showlocation_' . $instance->id,
                get_string('appearance_overview_settingshowlocation', 'tool_opencast'),
                get_string('appearance_overview_settingshowlocation_desc', 'tool_opencast'), 1));

        $ADMIN->add(self::PLUGINNAME, $appearancesettings);
    }

    /**
     * Adds the additional admin settings.
     *
     * @param \admin_settingpage $settings
     * The admin settingpage to add the notification banner to.
     *
     * @param int $instanceid
     * The id of the Opencast instance.
     *
     * @param object $instance
     *
     * @return void
     */
    private static function add_admin_additional_settings($settings, $instanceid, $instance): void {

        global $ADMIN, $CFG, $PAGE;

        // Settings page: Additional settings.
        $additionalsettings = self::create_admin_settingpage(
            'tool_opencast_additionalsettings_' . $instance->id, 'additional_settings');
        $ADMIN->add(self::PLUGINNAME, $additionalsettings);

        $installedplugins = \core_plugin_manager::instance()->get_installed_plugins('local');
        $chunkuploadisinstalled = array_key_exists('chunkupload', $installedplugins);

        if ($chunkuploadisinstalled) {

            $additionalsettings->add(
                new \admin_setting_heading('tool_opencast/upload_' . $instance->id,
                    get_string('uploadsettings', 'tool_opencast'),
                    ''));
            $additionalsettings->add(
                new \admin_setting_configcheckbox('tool_opencast/enablechunkupload_' . $instance->id,
                    get_string('enablechunkupload', 'tool_opencast'),
                    get_string('enablechunkupload_desc', 'tool_opencast'), true));

            $sizelist = [-1, 53687091200, 21474836480, 10737418240, 5368709120, 2147483648, 1610612736, 1073741824,
                536870912, 268435456, 134217728, 67108864, ];
            $filesizes = [];
            foreach ($sizelist as $sizebytes) {
                $filesizes[(string)intval($sizebytes)] = display_size($sizebytes);
            }

            $additionalsettings->add(new \admin_setting_configselect('tool_opencast/uploadfilelimit_' . $instance->id,
                get_string('uploadfilelimit', 'tool_opencast'),
                get_string('uploadfilelimitdesc', 'tool_opencast'),
                2147483648, $filesizes));
            if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
                $additionalsettings->hide_if('tool_opencast/uploadfilelimit_' . $instance->id,
                    'tool_opencast/enablechunkupload_' . $instance->id, 'notchecked');
            }

            $additionalsettings->add(
                new \admin_setting_configcheckbox('tool_opencast/offerchunkuploadalternative_' . $instance->id,
                    get_string('offerchunkuploadalternative', 'tool_opencast'),
                    get_string('offerchunkuploadalternative_desc', 'tool_opencast',
                        get_string('usedefaultfilepicker', 'tool_opencast')), true));
            if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
                $additionalsettings->hide_if('tool_opencast/offerchunkuploadalternative_' . $instance->id,
                    'tool_opencast/enablechunkupload_' . $instance->id, 'notchecked');
            }
        }

        $additionalsettings->add(
            new \admin_setting_heading('tool_opencast/opencast_studio_' . $instance->id,
                get_string('opencaststudiointegration', 'tool_opencast'),
                ''));

        $additionalsettings->add(
            new \admin_setting_configcheckbox('tool_opencast/enable_opencast_studio_link_' . $instance->id,
                get_string('enableopencaststudiolink', 'tool_opencast'),
                get_string('enableopencaststudiolink_desc', 'tool_opencast'), 0));

        // New tab config for Studio.
        $additionalsettings->add(
            new \admin_setting_configcheckbox('tool_opencast/open_studio_in_new_tab_' . $instance->id,
                get_string('opencaststudionewtab', 'tool_opencast'),
                get_string('opencaststudionewtab_desc', 'tool_opencast'), 1));

        $additionalsettings->add(
            new \admin_setting_configtext('tool_opencast/opencast_studio_baseurl_' . $instance->id,
                get_string('opencaststudiobaseurl', 'tool_opencast'),
                get_string('opencaststudiobaseurl_desc', 'tool_opencast'), ''));

        // Studio redirect button settings.
        $additionalsettings->add(
            new \admin_setting_configcheckbox('tool_opencast/show_opencast_studio_return_btn_' . $instance->id,
                get_string('enableopencaststudioreturnbtn', 'tool_opencast'),
                get_string('enableopencaststudioreturnbtn_desc', 'tool_opencast'), 0));

        $additionalsettings->add(
            new \admin_setting_configtext('tool_opencast/opencast_studio_return_btn_label_' . $instance->id,
                get_string('opencaststudioreturnbtnlabel', 'tool_opencast'),
                get_string('opencaststudioreturnbtnlabel_desc', 'tool_opencast'),
                ''));

        $additionalsettings->add(
            new \admin_setting_configtext('tool_opencast/opencast_studio_return_url_' . $instance->id,
                get_string('opencaststudioreturnurl', 'tool_opencast'),
                get_string('opencaststudioreturnurl_desc', 'tool_opencast'),
                '/admin/tool/opencast/index.php?courseid=[COURSEID]&ocinstanceid=[OCINSTANCEID]'));

        $additionalsettings->add(
            new \admin_setting_configtext('tool_opencast/opencast_studio_custom_settings_filename_' . $instance->id,
                get_string('opencaststudiocustomsettingsfilename', 'tool_opencast'),
                get_string('opencaststudiocustomsettingsfilename_desc', 'tool_opencast'),
                ''));

        // Opencast Editor Integration in additional feature settings.
        $additionalsettings->add(
            new \admin_setting_heading('tool_opencast/opencast_videoeditor_' . $instance->id,
                get_string('opencasteditorintegration', 'tool_opencast'),
                ''));

        // The Generall Integration Permission.
        $additionalsettings->add(
            new \admin_setting_configcheckbox('tool_opencast/enable_opencast_editor_link_' . $instance->id,
                get_string('enableopencasteditorlink', 'tool_opencast'),
                get_string('enableopencasteditorlink_desc', 'tool_opencast'), 0));

        // The External base url to call editor (if any). The opencast instance URL will be used if empty.
        $additionalsettings->add(
            new \admin_setting_configtext('tool_opencast/editorbaseurl_' . $instance->id,
                get_string('editorbaseurl', 'tool_opencast'),
                get_string('editorbaseurl_desc', 'tool_opencast'), ""));

        // The Editor endpoint url. It defines where to look for the editor in base url.
        $additionalsettings->add(
            new \admin_setting_configtext('tool_opencast/editorendpointurl_' . $instance->id,
                get_string('editorendpointurl', 'tool_opencast'),
                get_string('editorendpointurl_desc', 'tool_opencast'), "/editor-ui/index.html?mediaPackageId="));

        // Opencast Video Player in additional feature settings.
        $additionalsettings->add(
            new \admin_setting_heading('tool_opencast/opencast_access_video_' . $instance->id,
                get_string('engageplayerintegration', 'tool_opencast'),
                ''));

        // The link to the engage player.
        $additionalsettings->add(
            new \admin_setting_configtext('tool_opencast/engageurl_' . $instance->id,
                get_string('engageurl', 'tool_opencast'),
                get_string('engageurl_desc', 'tool_opencast'), ""));

        // Notifications in additional features settings.
        $additionalsettings->add(
            new \admin_setting_heading('tool_opencast/notifications_' . $instance->id,
                get_string('notifications_settings_header', 'tool_opencast'),
                ''));

        $additionalsettings->add(
            new \admin_setting_configcheckbox('tool_opencast/eventstatusnotificationenabled_' . $instance->id,
                get_string('notificationeventstatus', 'tool_opencast'),
                get_string('notificationeventstatus_desc', 'tool_opencast'), 0));

        $additionalsettings->add(
            new \admin_setting_configcheckbox('tool_opencast/eventstatusnotifyteachers_' . $instance->id,
                get_string('notificationeventstatusteachers', 'tool_opencast'),
                get_string('notificationeventstatusteachers_desc', 'tool_opencast'), 0));
        if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
            $additionalsettings->hide_if('tool_opencast/eventstatusnotifyteachers_' . $instance->id,
                'tool_opencast/eventstatusnotificationenabled_' . $instance->id, 'notchecked');
        }

        $additionalsettings->add(
            new \admin_setting_configtext('tool_opencast/eventstatusnotificationdeletion_' . $instance->id,
                get_string('notificationeventstatusdeletion', 'tool_opencast'),
                get_string('notificationeventstatusdeletion_desc', 'tool_opencast'), 0, PARAM_INT));

        // Control ACL section.
        $additionalsettings->add(
            new \admin_setting_heading('tool_opencast/acl_settingheader_' . $instance->id,
                get_string('acl_settingheader', 'tool_opencast'),
                ''));

        // Control ACL: Enable feature.
        $additionalsettings->add(
            new \admin_setting_configcheckbox('tool_opencast/aclcontrol_' . $instance->id,
                get_string('acl_settingcontrol', 'tool_opencast'),
                get_string('acl_settingcontrol_desc', 'tool_opencast'), 1));

        $additionalsettings->add(
            new \admin_setting_configcheckbox('tool_opencast/aclcontrolafter_' . $instance->id,
                get_string('acl_settingcontrolafter', 'tool_opencast'),
                get_string('acl_settingcontrolafter_desc', 'tool_opencast'), 1));

        // Control ACL: Enable group restriction.
        $additionalsettings->add(
            new \admin_setting_configcheckbox('tool_opencast/aclcontrolgroup_' . $instance->id,
                get_string('acl_settingcontrolgroup', 'tool_opencast'),
                get_string('acl_settingcontrolgroup_desc', 'tool_opencast'), 1));
        if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
            $additionalsettings->hide_if('tool_opencast/aclcontrolgroup_' . $instance->id,
                'tool_opencast/aclcontrolafter_' . $instance->id, 'notchecked');
        }

        // Control ACL: Waiting time for scheduled visibility change.
        $additionalsettings->add(
            new \admin_setting_configtext('tool_opencast/aclcontrolwaitingtime_' . $instance->id,
                get_string('acl_settingcontrolwaitingtime', 'tool_opencast'),
                get_string('acl_settingcontrolwaitingtime_desc', 'tool_opencast'),
                visibility_helper::DEFAULT_WAITING_TIME, PARAM_INT));
        if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
            $additionalsettings->hide_if('tool_opencast/aclcontrolwaitingtime_' . $instance->id,
                'tool_opencast/aclcontrolafter_' . $instance->id, 'notchecked');
        }

        if (\core_plugin_manager::instance()->get_plugin_info('mod_opencast')) {

            // Add Opencast Activity modules section.
            $additionalsettings->add(
                new \admin_setting_heading('tool_opencast/addactivity_settingheader_' . $instance->id,
                    get_string('addactivity_settingheader', 'tool_opencast'),
                    ''));

            // Add Opencast Activity series modules: Enable feature.
            $additionalsettings->add(
                new \admin_setting_configcheckbox('tool_opencast/addactivityenabled_' . $instance->id,
                    get_string('addactivity_settingenabled', 'tool_opencast'),
                    get_string('addactivity_settingenabled_desc', 'tool_opencast'), 0));

            // Add Opencast Activity series modules: Default Opencast Activity series module title.
            $additionalsettings->add(
                new \admin_setting_configtext('tool_opencast/addactivitydefaulttitle_' . $instance->id,
                    get_string('addactivity_settingdefaulttitle', 'tool_opencast'),
                    get_string('addactivity_settingdefaulttitle_desc', 'tool_opencast'),
                    get_string('addactivity_defaulttitle', 'tool_opencast'),
                    PARAM_TEXT));
            if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
                $additionalsettings->hide_if('tool_opencast/addactivitydefaulttitle_' . $instance->id,
                    'tool_opencast/addactivityenabled_' . $instance->id, 'notchecked');
            }

            // Add Opencast Activity series modules: Intro.
            $additionalsettings->add(
                new \admin_setting_configcheckbox('tool_opencast/addactivityintro_' . $instance->id,
                    get_string('addactivity_settingintro', 'tool_opencast'),
                    get_string('addactivity_settingintro_desc', 'tool_opencast'), 0));
            if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
                $additionalsettings->hide_if('tool_opencast/addactivityintro_' . $instance->id,
                    'tool_opencast/addactivityenabled_' . $instance->id, 'notchecked');
            }

            // Add Opencast Activity series modules: Section.
            $additionalsettings->add(
                new \admin_setting_configcheckbox('tool_opencast/addactivitysection_' . $instance->id,
                    get_string('addactivity_settingsection', 'tool_opencast'),
                    get_string('addactivity_settingsection_desc', 'tool_opencast'), 0));
            if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
                $additionalsettings->hide_if('tool_opencast/addactivitysection_' . $instance->id,
                    'tool_opencast/addactivityenabled_' . $instance->id, 'notchecked');
            }

            // Add Opencast Activity series modules: Availability.
            $url = new \moodle_url('/admin/settings.php?section=optionalsubsystems');
            $link = \html_writer::link($url, get_string('advancedfeatures', 'admin'), ['target' => '_blank']);
            $description = get_string('addactivity_settingavailability_desc', 'tool_opencast') . '<br />' .
                get_string('addactivity_settingavailability_note', 'tool_opencast', $link);
            $additionalsettings->add(
                new \admin_setting_configcheckbox('tool_opencast/addactivityavailability_' . $instance->id,
                    get_string('addactivity_settingavailability', 'tool_opencast'),
                    $description, 0));
            if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
                $additionalsettings->hide_if('tool_opencast/addactivityavailability_' . $instance->id,
                    'tool_opencast/addactivityenabled_' . $instance->id, 'notchecked');
            }

            // Add Opencast Activity episode modules: Enable feature.
            $additionalsettings->add(
                new \admin_setting_configcheckbox('tool_opencast/addactivityepisodeenabled_' . $instance->id,
                    get_string('addactivityepisode_settingenabled', 'tool_opencast'),
                    get_string('addactivityepisode_settingenabled_desc', 'tool_opencast'), 0));

            // Add Opencast Activity episode modules: Intro.
            $additionalsettings->add(
                new \admin_setting_configcheckbox('tool_opencast/addactivityepisodeintro_' . $instance->id,
                    get_string('addactivityepisode_settingintro', 'tool_opencast'),
                    get_string('addactivityepisode_settingintro_desc', 'tool_opencast'), 0));
            if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
                $additionalsettings->hide_if('tool_opencast/addactivityepisodeintro_' . $instance->id,
                    'tool_opencast/addactivityepisodeenabled_' . $instance->id, 'notchecked');
            }

            // Add Opencast Activity episode modules: Section.
            $additionalsettings->add(
                new \admin_setting_configcheckbox('tool_opencast/addactivityepisodesection_' . $instance->id,
                    get_string('addactivityepisode_settingsection', 'tool_opencast'),
                    get_string('addactivityepisode_settingsection_desc', 'tool_opencast'), 0));
            if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
                $additionalsettings->hide_if('tool_opencast/addactivityepisodesection_' . $instance->id,
                    'tool_opencast/addactivityepisodeenabled_' . $instance->id, 'notchecked');
            }

            // Add Opencast Activity episode modules: Availability.
            $url = new \moodle_url('/admin/settings.php?section=optionalsubsystems');
            $link = \html_writer::link($url, get_string('advancedfeatures', 'admin'), ['target' => '_blank']);
            $description = get_string('addactivityepisode_settingavailability_desc', 'tool_opencast') . '<br />' .
                get_string('addactivity_settingavailability_note', 'tool_opencast', $link);
            $additionalsettings->add(
                new \admin_setting_configcheckbox('tool_opencast/addactivityepisodeavailability_' . $instance->id,
                    get_string('addactivityepisode_settingavailability', 'tool_opencast'),
                    $description, 0));
            if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
                $additionalsettings->hide_if('tool_opencast/addactivityepisodeavailability_' . $instance->id,
                    'tool_opencast/addactivityepisodeenabled_' . $instance->id, 'notchecked');
            }
        }

        // Transcription upload settings.
        $additionalsettings->add(
            new \admin_setting_heading('tool_opencast/transcription_header_' . $instance->id,
                get_string('transcriptionsettingsheader', 'tool_opencast'),
                ''));

        $additionalsettings->add(
            new \admin_setting_configcheckbox('tool_opencast/enableuploadtranscription_' . $instance->id,
                get_string('transcriptionsettingsenableupload', 'tool_opencast'),
                get_string('transcriptionsettingsenableupload_desc', 'tool_opencast'), 0));

        $additionalsettings->add(
            new \admin_setting_configcheckbox('tool_opencast/enablemanagetranscription_' . $instance->id,
                get_string('transcriptionsettingsenablemanage', 'tool_opencast'),
                get_string('transcriptionsettingsenablemanage_desc', 'tool_opencast'), 0));

        $additionalsettings->add(
            new \admin_setting_configcheckbox('tool_opencast/allowdownloadtranscription_' . $instance->id,
                get_string('allowdownloadtranscriptionsetting', 'tool_opencast'),
                get_string('allowdownloadtranscriptionsetting_desc', 'tool_opencast'), 1));
        $additionalsettings->hide_if('tool_opencast/allowdownloadtranscription_' . $instance->id,
            'tool_opencast/enablemanagetranscription_' . $instance->id, 'notchecked');

        $additionalsettings->add(
            new \admin_setting_configtext('tool_opencast/transcriptionworkflow_' . $instance->id,
                get_string('transcriptionworkflow', 'tool_opencast'),
                get_string('transcriptionworkflow_desc', 'tool_opencast'), 'publish', PARAM_TEXT));

        $additionalsettings->add(
            new \admin_setting_configtext('tool_opencast/deletetranscriptionworkflow_' . $instance->id,
                get_string('deletetranscriptionworkflow', 'tool_opencast'),
                get_string('deletetranscriptionworkflow_desc', 'tool_opencast'), 'publish', PARAM_TEXT));

        $defaulttranscriptionlanguages = setting_default_manager::get_default_transcriptionlanguages();

        $transcriptionlanguages = new \admin_setting_configtext('tool_opencast/transcriptionlanguages_' . $instanceid,
            get_string('transcriptionlanguages', 'tool_opencast'),
            get_string('transcriptionlanguages_desc', 'tool_opencast'), $defaulttranscriptionlanguages);

        // Crashes if plugins.php is opened because css cannot be included anymore.
        if ($PAGE->state !== \moodle_page::STATE_IN_BODY) {
            $PAGE->requires->js_call_amd('tool_opencast/tool_settings', 'init_additional_settings', [
                $transcriptionlanguages->get_id(),
                $instanceid,
            ]);
        }

        $additionalsettings->add($transcriptionlanguages);
        $additionalsettings->add(
            new admin_setting_configeditabletable(
                'tool_opencast/transcriptionlanguagesoptions_' . $instance->id,
                'transcriptionlanguagesoptions_' . $instance->id,
                get_string('transcriptionaddnewlanguage', 'tool_opencast')));

        $additionalsettings->add(
            new \admin_setting_filetypes('tool_opencast/transcriptionfileextensions_' . $instance->id,
                new \lang_string('transcriptionfileextensions', 'tool_opencast'),
                get_string('transcriptionfileextensions_desc', 'tool_opencast',
                    $CFG->wwwroot . '/admin/tool/filetypes/index.php')
            ));
        // End of transcription upload settings.
        // Live Status Update.
        // Setting for live status update for processing as well as uploading events.
        $additionalsettings->add(
            new \admin_setting_heading('tool_opencast/liveupdate_settingheader_' . $instance->id,
                get_string('liveupdate_settingheader', 'tool_opencast'),
                ''));

        // Enables live status update here.
        $additionalsettings->add(
            new \admin_setting_configcheckbox('tool_opencast/liveupdateenabled_' . $instance->id,
                get_string('liveupdate_settingenabled', 'tool_opencast'),
                get_string('liveupdate_settingenabled_desc', 'tool_opencast'), 1));

        // Setting for reload timeout after an event has new changes.
        $additionalsettings->add(
            new \admin_setting_configtext('tool_opencast/liveupdatereloadtimeout_' . $instance->id,
                get_string('liveupdate_reloadtimeout', 'tool_opencast'),
                get_string('liveupdate_reloadtimeout_desc', 'tool_opencast'), 3, PARAM_INT));
        $additionalsettings->hide_if('tool_opencast/liveupdatereloadtimeout_' . $instance->id,
            'tool_opencast/liveupdateenabled_' . $instance->id, 'notchecked');

        // Privacy notice display additional settings.
        $additionalsettings->add(
            new \admin_setting_heading('tool_opencast/swprivacynotice_header_' . $instance->id,
                get_string('swprivacynotice_settingheader', 'tool_opencast'),
                ''));

        $additionalsettings->add(
            new \admin_setting_confightmleditor('tool_opencast/swprivacynoticeinfotext_' . $instance->id,
                get_string('swprivacynotice_settinginfotext', 'tool_opencast'),
                get_string('swprivacynotice_settinginfotext_desc', 'tool_opencast'), null));

        $additionalsettings->add(
            new \admin_setting_configtext('tool_opencast/swprivacynoticewfds_' . $instance->id,
                get_string('swprivacynotice_settingwfds', 'tool_opencast'),
                get_string('swprivacynotice_settingwfds_desc', 'tool_opencast'), null));
        // Providing hide_if for this setting.
        $additionalsettings->hide_if('tool_opencast/swprivacynoticewfds_' . $instance->id,
            'tool_opencast/swprivacynoticeinfotext_' . $instance->id, 'eq', '');

        $additionalsettings->add(
            new \admin_setting_configtext('tool_opencast/swprivacynoticetitle_' . $instance->id,
                get_string('swprivacynotice_settingtitle', 'tool_opencast'),
                get_string('swprivacynotice_settingtitle_desc', 'tool_opencast'), null));
        // Providing hide_if for this setting.
        $additionalsettings->hide_if('tool_opencast/swprivacynoticetitle_' . $instance->id,
            'tool_opencast/swprivacynoticeinfotext_' . $instance->id, 'eq', '');
        // End of privacy notice.

        // Additional Settings.
        // Terms of use. Downlaod channel. Custom workflows channel. Support email.
        $additionalsettings->add(
            new \admin_setting_heading('tool_opencast/download_settingheader_' . $instance->id,
                get_string('additional_settings', 'tool_opencast'),
                ''));

        $additionalsettings->add(
            new \admin_setting_configtext('tool_opencast/download_channel_' . $instance->id,
                get_string('download_setting', 'tool_opencast'),
                get_string('download_settingdesc', 'tool_opencast'), "api"));

        $additionalsettings->add(
            new \admin_setting_configtext('tool_opencast/direct_access_channel_' . $instance->id,
                get_string('directaccess_setting', 'tool_opencast'),
                get_string('directaccess_settingdesc', 'tool_opencast'), ''));

        $additionalsettings->add(
            new \admin_setting_configtext('tool_opencast/workflow_tags_' . $instance->id,
                get_string('workflowtags_setting', 'tool_opencast'),
                get_string('workflowtags_settingdesc', 'tool_opencast'), null));

        $additionalsettings->add(
            new \admin_setting_configtext('tool_opencast/support_email_' . $instance->id,
                get_string('support_setting', 'tool_opencast'),
                get_string('support_settingdesc', 'tool_opencast'), null));

        $additionalsettings->add(new \admin_setting_confightmleditor(
            'tool_opencast/termsofuse_' . $instance->id,
            get_string('termsofuse', 'tool_opencast'),
            get_string('termsofuse_desc', 'tool_opencast'), null));

    }


    /**
     * Adds the general admin settings.
     *
     * @param \admin_settingpage $settings
     * The admin settingpage to add the notification banner to.
     *
     * @param int $instanceid
     * The id of the Opencast instance.
     *
     * @param object $instance
     *
     * @return void
     */
    private static function add_admin_general_settings($settings, $instanceid, $instance): void {

        global $PAGE, $CFG, $ADMIN;

        $ocinstances = settings_api::get_ocinstances();
        $multiocinstance = count($ocinstances) > 1;

        // General Settings Pageblock_.
        $generalsettings = self::create_admin_settingpage('tool_opencast_generalsettings_' . $instanceid, 'general_settings');

        $opencasterror = false;

        // Initialize the default settings for each instance.
        setting_default_manager::init_regirstered_defaults($instanceid);
        // Setup js.
        $rolesdefault = setting_default_manager::get_default_roles();
        $metadatadefault = setting_default_manager::get_default_metadata();
        $metadataseriesdefault = setting_default_manager::get_default_metadataseries();

        $generalsettings->add(new admin_setting_hiddenhelpbtn('tool_opencast/hiddenhelpname_' . $instanceid,
            'helpbtnname_' . $instanceid, 'descriptionmdfn', 'tool_opencast'));
        $generalsettings->add(new admin_setting_hiddenhelpbtn('tool_opencast/hiddenhelpparams_' . $instanceid,
            'helpbtnparams_' . $instanceid, 'catalogparam', 'tool_opencast'));
        $generalsettings->add(new admin_setting_hiddenhelpbtn('tool_opencast/hiddenhelpdescription_' . $instanceid,
            'helpbtndescription_' . $instanceid, 'descriptionmdfd', 'tool_opencast'));
        $generalsettings->add(new admin_setting_hiddenhelpbtn('tool_opencast/hiddenhelpdefaultable_' . $instanceid,
            'helpbtndefaultable_' . $instanceid, 'descriptionmddefaultable', 'tool_opencast'));
        $generalsettings->add(new admin_setting_hiddenhelpbtn('tool_opencast/hiddenhelpbatchable_' . $instanceid,
            'helpbtnbatchable_' . $instanceid, 'descriptionmdbatchable', 'tool_opencast'));
        $generalsettings->add(new admin_setting_hiddenhelpbtn('tool_opencast/hiddenhelpreadonly_' . $instanceid,
            'helpbtnreadonly_' . $instanceid, 'descriptionmdreadonly', 'tool_opencast'));

        $rolessetting = new \admin_setting_configtext('tool_opencast/roles_' . $instanceid,
            get_string('aclrolesname', 'tool_opencast'),
            get_string('aclrolesnamedesc', 'tool_opencast'),
            $rolesdefault);

        $dcmitermsnotice = get_string('dcmitermsnotice', 'tool_opencast');
        $metadatasetting = new \admin_setting_configtext('tool_opencast/metadata_' . $instanceid,
            get_string('metadata', 'tool_opencast'),
            get_string('metadatadesc', 'tool_opencast') . $dcmitermsnotice, $metadatadefault);

        $metadataseriessetting = new \admin_setting_configtext('tool_opencast/metadataseries_' . $instanceid,
            get_string('metadataseries', 'tool_opencast'),
            get_string('metadataseriesdesc', 'tool_opencast') . $dcmitermsnotice, $metadataseriesdefault);

        // Crashes if plugins.php is opened because css cannot be included anymore.
        if ($PAGE->state !== \moodle_page::STATE_IN_BODY) {
            $PAGE->requires->js_call_amd('tool_opencast/tool_settings', 'init_general_settings', [
                $rolessetting->get_id(),
                $metadatasetting->get_id(),
                $metadataseriessetting->get_id(),
                $instanceid,
            ]);
        }

        // Limit uploadjobs.
        $url = new \moodle_url('/admin/tool/task/scheduledtasks.php');
        $link = \html_writer::link($url, get_string('pluginname', 'tool_task'), ['target' => '_blank']);
        $generalsettings->add(
            new \admin_setting_configtext('tool_opencast/limituploadjobs_' . $instanceid,
                get_string('limituploadjobs', 'tool_opencast'),
                get_string('limituploadjobsdesc', 'tool_opencast', $link), 1, PARAM_INT));

        $workflowchoices = setting_helper::load_workflow_choices($instanceid, 'upload');
        if ($workflowchoices instanceof opencast_api_response_exception ||
            $workflowchoices instanceof empty_configuration_exception) {
            $opencasterror = $workflowchoices->getMessage();
            $workflowchoices = [null => get_string('adminchoice_noconnection', 'tool_opencast')];
        }

        $generalsettings->add(new \admin_setting_configselect('tool_opencast/uploadworkflow_' . $instanceid,
            get_string('uploadworkflow', 'tool_opencast'),
            get_string('uploadworkflowdesc', 'tool_opencast'),
            'ng-schedule-and-upload', $workflowchoices
        ));

        $generalsettings->add(new \admin_setting_configcheckbox('tool_opencast/enableuploadwfconfigpanel_' . $instanceid,
            get_string('enableuploadwfconfigpanel', 'tool_opencast'),
            get_string('enableuploadwfconfigpaneldesc', 'tool_opencast'),
            0
        ));

        $generalsettings->add(new \admin_setting_configtext('tool_opencast/alloweduploadwfconfigs_' . $instanceid,
            get_string('alloweduploadwfconfigs', 'tool_opencast'),
            get_string('alloweduploadwfconfigsdesc', 'tool_opencast'),
            '',
            PARAM_TEXT
        ));

        $generalsettings->hide_if('tool_opencast/alloweduploadwfconfigs_' . $instanceid,
            'tool_opencast/enableuploadwfconfigpanel_' . $instanceid, 'notchecked');

        $generalsettings->add(new \admin_setting_configcheckbox('tool_opencast/publishtoengage_' . $instanceid,
            get_string('publishtoengage', 'tool_opencast'),
            get_string('publishtoengagedesc', 'tool_opencast'),
            0
        ));

        $generalsettings->add(new \admin_setting_configcheckbox('tool_opencast/ingestupload_' . $instanceid,
            get_string('ingestupload', 'tool_opencast'),
            get_string('ingestuploaddesc', 'tool_opencast'),
            0
        ));

        $generalsettings->add(new \admin_setting_configcheckbox('tool_opencast/reuseexistingupload_' . $instanceid,
            get_string('reuseexistingupload', 'tool_opencast'),
            get_string('reuseexistinguploaddesc', 'tool_opencast'),
            0
        ));

        $generalsettings->hide_if('tool_opencast/reuseexistingupload_' . $instanceid,
            'tool_opencast/ingestupload_' . $instanceid, 'checked');

        $generalsettings->add(new \admin_setting_configcheckbox('tool_opencast/allowunassign_' . $instanceid,
            get_string('allowunassign', 'tool_opencast'),
            get_string('allowunassigndesc', 'tool_opencast'),
            0
        ));

        $workflowchoices = setting_helper::load_workflow_choices($instanceid, 'delete');
        if ($workflowchoices instanceof opencast_api_response_exception ||
            $workflowchoices instanceof empty_configuration_exception) {
            $opencasterror = $workflowchoices->getMessage();
            $workflowchoices = [null => get_string('adminchoice_noconnection', 'tool_opencast')];
        }

        $generalsettings->add(new \admin_setting_configselect('tool_opencast/deleteworkflow_' . $instanceid,
                get_string('deleteworkflow', 'tool_opencast'),
                get_string('deleteworkflowdesc', 'tool_opencast'),
                null, $workflowchoices)
        );

        $generalsettings->add(new \admin_setting_configcheckbox('tool_opencast/adhocfiledeletion_' . $instanceid,
            get_string('adhocfiledeletion', 'tool_opencast'),
            get_string('adhocfiledeletiondesc', 'tool_opencast'),
            0
        ));

        $generalsettings->add(new \admin_setting_filetypes('tool_opencast/uploadfileextensions_' . $instanceid,
            new \lang_string('uploadfileextensions', 'tool_opencast'),
            get_string('uploadfileextensionsdesc', 'tool_opencast', $CFG->wwwroot . '/admin/tool/filetypes/index.php')
        ));

        $generalsettings->add(new \admin_setting_configtext('tool_opencast/maxseries_' . $instanceid,
            new \lang_string('maxseries', 'tool_opencast'),
            get_string('maxseriesdesc', 'tool_opencast'), 3, PARAM_INT
        ));

        // Batch upload setting.
        $uploadtimeouturl = new \moodle_url('/admin/settings.php?section=tool_opencast_sharedsettings');
        $uploadtimeoutlink = \html_writer::link($uploadtimeouturl,
            get_string('uploadtimeout', 'tool_opencast'), ['target' => '_blank']);

        $octoolshorturl = '/admin/settings.php?section=tool_opencast_configuration';
        if ($multiocinstance) {
            $octoolshorturl .= '_' . $instanceid;
        }
        $toolopencastinstanceurl = new \moodle_url($octoolshorturl);
        $toolopencastinstancelink = \html_writer::link($toolopencastinstanceurl,
            get_string('configuration_instance', 'tool_opencast', $instance->name), ['target' => '_blank']);
        $stringobj = new \stdClass();
        $stringobj->uploadtimeoutlink = $uploadtimeoutlink;
        $stringobj->toolopencastinstancelink = $toolopencastinstancelink;
        $generalsettings->add(new \admin_setting_configcheckbox('tool_opencast/batchuploadenabled_' . $instanceid,
            get_string('batchupload_setting', 'tool_opencast'),
            get_string('batchupload_setting_desc', 'tool_opencast', $stringobj),
            1
        ));

        $generalsettings->add(
            new \admin_setting_heading('tool_opencast/groupseries_header_' . $instanceid,
                get_string('groupseries_header', 'tool_opencast'),
                ''));

        $generalsettings->add(
            new \admin_setting_configcheckbox('tool_opencast/group_creation_' . $instanceid,
                get_string('groupcreation', 'tool_opencast'),
                get_string('groupcreationdesc', 'tool_opencast'), 0
            ));

        $generalsettings->add(
            new \admin_setting_configtext('tool_opencast/group_name_' . $instanceid,
                get_string('groupname', 'tool_opencast'),
                get_string('groupnamedesc', 'tool_opencast'), 'Moodle_course_[COURSEID]', PARAM_TEXT));

        $generalsettings->add(
            new \admin_setting_configtext('tool_opencast/series_name_' . $instanceid,
                get_string('seriesname', 'tool_opencast'),
                get_string('seriesnamedesc', 'tool_opencast', $link), 'Course_Series_[COURSEID]', PARAM_TEXT));

        $generalsettings->add(
            new \admin_setting_heading('tool_opencast/roles_header_' . $instanceid,
                get_string('aclrolesname', 'tool_opencast'),
                ''));

        $workflowchoices = setting_helper::load_workflow_choices($instanceid, 'archive');
        if ($workflowchoices instanceof opencast_api_response_exception ||
            $workflowchoices instanceof empty_configuration_exception) {
            $opencasterror = $workflowchoices->getMessage();
            $workflowchoices = [null => get_string('adminchoice_noconnection', 'tool_opencast')];
        }
        $generalsettings->add(new \admin_setting_configselect('tool_opencast/workflow_roles_' . $instanceid,
                get_string('workflowrolesname', 'tool_opencast'),
                get_string('workflowrolesdesc', 'tool_opencast'),
                null, $workflowchoices)
        );

        $generalsettings->add($rolessetting);
        $generalsettings->add(new admin_setting_configeditabletable('tool_opencast/rolestable_' .
            $instanceid, 'rolestable_' . $instanceid,
            get_string('addrole', 'tool_opencast')));

        $roleownersetting = new admin_setting_configtextvalidate('tool_opencast/aclownerrole_' . $instanceid,
            get_string('aclownerrole', 'tool_opencast'),
            get_string('aclownerrole_desc', 'tool_opencast'), '');
        $roleownersetting->set_validate_function([setting_helper::class, 'validate_aclownerrole_setting']);
        $generalsettings->add($roleownersetting);

        $generalsettings->add(
            new \admin_setting_heading('tool_opencast/metadata_header_' . $instanceid,
                get_string('metadata', 'tool_opencast'),
                ''));

        $generalsettings->add($metadatasetting);
        $generalsettings->add(new admin_setting_configeditabletable('tool_opencast/metadatatable_' .
            $instanceid, 'metadatatable_' . $instanceid,
            get_string('addcatalog', 'tool_opencast')));

        $generalsettings->add(
            new \admin_setting_heading('tool_opencast/metadataseries_header_' . $instanceid,
                get_string('metadataseries', 'tool_opencast'),
                ''));

        $generalsettings->add($metadataseriessetting);
        $generalsettings->add(new admin_setting_configeditabletable('tool_opencast/metadataseriestable_' .
            $instanceid, 'metadataseriestable_' . $instanceid,
            get_string('addcatalog', 'tool_opencast')));

        // Don't spam other setting pages with error messages just because the tree was built.
        if ($opencasterror && ($PAGE->pagetype == 'admin-setting-tool_opencast'
        || $PAGE->pagetype == 'admin-setting-tool_opencast_generalsettings_' . $instanceid)) {
            notification::error($opencasterror);
        }

        $ADMIN->add(self::PLUGINNAME, $generalsettings);

    }

    /**
     * Adds the LTI module settings.
     *
     * @param \admin_settingpage $settings
     * The admin settingpage to add the notification banner to.
     *
     * @param int $instanceid
     * The id of the Opencast instance.
     *
     * @param object $instance
     *
     * @return void
     */
    private static function add_admin_ltimodule_settings($settings, $instanceid, $instance): void {

        global $CFG, $ADMIN;

        $ltimodulesettings = self::create_admin_settingpage('tool_opencast_ltimodulesettings_' . $instanceid, 'ltimodule_settings');

        // Add LTI series modules section.
        $ltimodulesettings->add(
            new \admin_setting_heading('tool_opencast/addlti_settingheader_' . $instance->id,
                get_string('addlti_settingheader', 'tool_opencast'),
                ''));

        // Add LTI series modules: Enable feature.
        $ltimodulesettings->add(
            new \admin_setting_configcheckbox('tool_opencast/addltienabled_' . $instance->id,
                get_string('addlti_settingenabled', 'tool_opencast'),
                get_string('addlti_settingenabled_desc', 'tool_opencast'), 0));

        // Add LTI series modules: Default LTI series module title.
        $ltimodulesettings->add(
            new \admin_setting_configtext('tool_opencast/addltidefaulttitle_' . $instance->id,
                get_string('addlti_settingdefaulttitle', 'tool_opencast'),
                get_string('addlti_settingdefaulttitle_desc', 'tool_opencast'),
                get_string('addlti_defaulttitle', 'tool_opencast'),
                PARAM_TEXT));
        if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
            $ltimodulesettings->hide_if('tool_opencast/addltidefaulttitle_' . $instance->id,
                'tool_opencast/addltienabled_' . $instance->id, 'notchecked');
        }

        // Add LTI series modules: Preconfigured LTI tool.
        $tools = ltimodulemanager::get_preconfigured_tools();
        // If there are any tools to be selected.
        if (count($tools) > 0) {
            $ltimodulesettings->add(
                new \admin_setting_configselect('tool_opencast/addltipreconfiguredtool_' . $instance->id,
                    get_string('addlti_settingpreconfiguredtool', 'tool_opencast'),
                    get_string('addlti_settingpreconfiguredtool_desc', 'tool_opencast'),
                    null,
                    $tools));

            // If there aren't any preconfigured tools to be selected.
        } else {
            // Add an empty element to at least create the setting when the plugin is installed.
            // Additionally, show some information text where to add preconfigured tools.
            $url = new \moodle_url('/admin/settings.php?section=modsettinglti');
            $link = \html_writer::link($url, get_string('manage_tools', 'mod_lti'), ['target' => '_blank']);
            $description = get_string('addlti_settingpreconfiguredtool_notools', 'tool_opencast', $link);
            $ltimodulesettings->add(
                new \admin_setting_configempty('tool_opencast/addltipreconfiguredtool_' . $instance->id,
                    get_string('addlti_settingpreconfiguredtool', 'tool_opencast'),
                    $description));
        }
        if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
            $ltimodulesettings->hide_if('tool_opencast/addltipreconfiguredtool_' . $instance->id,
                'tool_opencast/addltienabled_' . $instance->id, 'notchecked');
        }

        // Add LTI series modules: Intro.
        $ltimodulesettings->add(
            new \admin_setting_configcheckbox('tool_opencast/addltiintro_' . $instance->id,
                get_string('addlti_settingintro', 'tool_opencast'),
                get_string('addlti_settingintro_desc', 'tool_opencast'), 0));
        if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
            $ltimodulesettings->hide_if('tool_opencast/addltiintro_' . $instance->id,
                'tool_opencast/addltienabled_' . $instance->id, 'notchecked');
        }

        // Add LTI series modules: Section.
        $ltimodulesettings->add(
            new \admin_setting_configcheckbox('tool_opencast/addltisection_' . $instance->id,
                get_string('addlti_settingsection', 'tool_opencast'),
                get_string('addlti_settingsection_desc', 'tool_opencast'), 0));
        if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
            $ltimodulesettings->hide_if('tool_opencast/addltisection_' . $instance->id,
                'tool_opencast/addltienabled_' . $instance->id, 'notchecked');
        }

        // Add LTI series modules: Availability.
        $url = new \moodle_url('/admin/settings.php?section=optionalsubsystems');
        $link = \html_writer::link($url, get_string('advancedfeatures', 'admin'), ['target' => '_blank']);
        $description = get_string('addlti_settingavailability_desc', 'tool_opencast') . '<br />' .
            get_string('addlti_settingavailability_note', 'tool_opencast', $link);
        $ltimodulesettings->add(
            new \admin_setting_configcheckbox('tool_opencast/addltiavailability_' . $instance->id,
                get_string('addlti_settingavailability', 'tool_opencast'),
                $description, 0));
        if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
            $ltimodulesettings->hide_if('tool_opencast/addltiavailability_' . $instance->id,
                'tool_opencast/addltienabled_' . $instance->id, 'notchecked');
        }

        // Add LTI episode modules section.
        $ltimodulesettings->add(
            new \admin_setting_heading('tool_opencast/addltiepisode_settingheader_' . $instance->id,
                get_string('addltiepisode_settingheader', 'tool_opencast'),
                ''));

        // Add LTI episode modules: Enable feature.
        $ltimodulesettings->add(
            new \admin_setting_configcheckbox('tool_opencast/addltiepisodeenabled_' . $instance->id,
                get_string('addltiepisode_settingenabled', 'tool_opencast'),
                get_string('addltiepisode_settingenabled_desc', 'tool_opencast'), 0));

        // Add LTI episode modules: Preconfigured LTI tool.
        $tools = ltimodulemanager::get_preconfigured_tools();
        // If there are any tools to be selected.
        if (count($tools) > 0) {
            $ltimodulesettings->add(
                new \admin_setting_configselect('tool_opencast/addltiepisodepreconfiguredtool_' . $instance->id,
                    get_string('addltiepisode_settingpreconfiguredtool', 'tool_opencast'),
                    get_string('addltiepisode_settingpreconfiguredtool_desc', 'tool_opencast'),
                    null,
                    $tools));

            // If there aren't any preconfigured tools to be selected.
        } else {
            // Add an empty element to at least create the setting when the plugin is installed.
            // Additionally, show some information text where to add preconfigured tools.
            $url = new \moodle_url('/admin/settings.php?section=modsettinglti');
            $link = \html_writer::link($url, get_string('manage_tools', 'mod_lti'), ['target' => '_blank']);
            $description = get_string('addltiepisode_settingpreconfiguredtool_notools', 'tool_opencast', $link);
            $ltimodulesettings->add(
                new \admin_setting_configempty('tool_opencast/addltiepisodepreconfiguredtool_' . $instance->id,
                    get_string('addltiepisode_settingpreconfiguredtool', 'tool_opencast'),
                    $description));
        }
        if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
            $ltimodulesettings->hide_if('tool_opencast/addltiepisodepreconfiguredtool_' . $instance->id,
                'tool_opencast/addltiepisodeenabled_' . $instance->id, 'notchecked');
        }

        // Add LTI episode modules: Intro.
        $ltimodulesettings->add(
            new \admin_setting_configcheckbox('tool_opencast/addltiepisodeintro_' . $instance->id,
                get_string('addltiepisode_settingintro', 'tool_opencast'),
                get_string('addltiepisode_settingintro_desc', 'tool_opencast'), 0));
        if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
            $ltimodulesettings->hide_if('tool_opencast/addltiepisodeintro_' . $instance->id,
                'tool_opencast/addltiepisodeenabled_' . $instance->id, 'notchecked');
        }

        // Add LTI episode modules: Section.
        $ltimodulesettings->add(
            new \admin_setting_configcheckbox('tool_opencast/addltiepisodesection_' . $instance->id,
                get_string('addltiepisode_settingsection', 'tool_opencast'),
                get_string('addltiepisode_settingsection_desc', 'tool_opencast'), 0));
        if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
            $ltimodulesettings->hide_if('tool_opencast/addltiepisodesection_' . $instance->id,
                'tool_opencast/addltiepisodeenabled_' . $instance->id, 'notchecked');
        }

        // Add LTI episode modules: Availability.
        $url = new \moodle_url('/admin/settings.php?section=optionalsubsystems');
        $link = \html_writer::link($url, get_string('advancedfeatures', 'admin'), ['target' => '_blank']);
        $description = get_string('addltiepisode_settingavailability_desc', 'tool_opencast') . '<br />' .
            get_string('addlti_settingavailability_note', 'tool_opencast', $link);
        $ltimodulesettings->add(
            new \admin_setting_configcheckbox('tool_opencast/addltiepisodeavailability_' . $instance->id,
                get_string('addltiepisode_settingavailability', 'tool_opencast'),
                $description, 0));
        if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
            $ltimodulesettings->hide_if('tool_opencast/addltiepisodeavailability_' . $instance->id,
                'tool_opencast/addltiepisodeenabled_' . $instance->id, 'notchecked');
        }

        $ADMIN->add(self::PLUGINNAME, $ltimodulesettings);
    }

    /**
     * Adds the import videos settings.
     *
     * @param \admin_settingpage $settings
     * The admin settingpage to add the notification banner to.
     *
     * @param int $instanceid
     * The id of the Opencast instance.
     *
     * @param object $instance
     *
     * @return void
     */
    private static function add_admin_importvideos_settings($settings, $instanceid, $instance): void {

        global $PAGE, $CFG, $ADMIN;

        $opencasterror = false;

        $importvideossettings = self::create_admin_settingpage(
            'tool_opencast_importvideossettings_' . $instanceid, 'importvideos_settings');

        // Import videos section.
        $importvideossettings->add(
            new \admin_setting_heading('tool_opencast/importvideos_settingheader_' . $instance->id,
                get_string('importvideos_settingheader', 'tool_opencast'),
                ''));

        // Import videos: Enable feature.
        $importvideossettings->add(
            new \admin_setting_configcheckbox('tool_opencast/importvideosenabled_' . $instance->id,
                get_string('importvideos_settingenabled', 'tool_opencast'),
                get_string('importvideos_settingenabled_desc', 'tool_opencast'), 1));

        // Import Video: define modes (ACL Change / Duplicating Events).
        $importmodechoices = [
            'duplication' => get_string('importvideos_settingmodeduplication', 'tool_opencast'),
            'acl' => get_string('importvideos_settingmodeacl', 'tool_opencast'),
        ];

        // Set default to duplication mode.
        $select = new \admin_setting_configselect('tool_opencast/importmode_' . $instance->id,
            get_string('importmode', 'tool_opencast'),
            get_string('importmodedesc', 'tool_opencast'),
            'duplication', $importmodechoices);

        $importvideossettings->add($select);

        if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
            $importvideossettings->hide_if('tool_opencast/importmode_' . $instance->id,
                'tool_opencast/importvideosenabled_' . $instance->id, 'notchecked');
        }

        // Import videos: Duplicate workflow.
        // The default duplicate-event workflow has archive tag, therefore it needs to be adjusted here as well.
        // As this setting has used api tag for the duplicate event, it is now possible to have multiple tags in here.
        $workflowchoices = setting_helper::load_workflow_choices($instance->id, 'api,archive');
        if ($workflowchoices instanceof opencast_api_response_exception ||
            $workflowchoices instanceof empty_configuration_exception) {
            $opencasterror = $workflowchoices->getMessage();
            $workflowchoices = [null => get_string('adminchoice_noconnection', 'tool_opencast')];
        }
        $select = new \admin_setting_configselect('tool_opencast/duplicateworkflow_' . $instance->id,
            get_string('duplicateworkflow', 'tool_opencast'),
            get_string('duplicateworkflowdesc', 'tool_opencast'),
            null, $workflowchoices);

        if ($CFG->branch >= 310) { // The validation functionality for admin settings is not available before Moodle 3.10.
            $select->set_validate_function([setting_helper::class, 'validate_workflow_setting']);
        }

        $importvideossettings->add($select);

        if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
            $importvideossettings->hide_if('tool_opencast/duplicateworkflow_' . $instance->id,
                'tool_opencast/importvideosenabled_' . $instance->id, 'notchecked');
            $importvideossettings->hide_if('tool_opencast/duplicateworkflow_' . $instance->id,
                'tool_opencast/importmode_' . $instance->id, 'eq', 'acl');
        }

        // Import videos: Define if the videos should be imported during the course backup restore.
        $importvideosonbackup = [
            0 => get_string('importvideos_settingonbackupvalue_false', 'tool_opencast'),
            1 => get_string('importvideos_settingonbackupvalue_true', 'tool_opencast'), ];
        $defaultvaluechioce = 1;
        $importvideossettings->add(
            new \admin_setting_configselect('tool_opencast/importvideosonbackup_' . $instance->id,
                get_string('importvideos_settingonbackupvalue', 'tool_opencast'),
                get_string('importvideos_settingonbackupvalue_desc', 'tool_opencast'),
                $defaultvaluechioce, $importvideosonbackup));
        if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
            $importvideossettings->hide_if('tool_opencast/importvideosonbackup_' . $instance->id,
                'tool_opencast/importvideosonbackup_' . $instance->id, 'notchecked');
        }

        $importvideossettings->add(
            new \admin_setting_configcheckbox('tool_opencast/importreducedduplication_' . $instance->id,
                get_string('importreducedduplication', 'tool_opencast'),
                get_string('importreducedduplication_desc', 'tool_opencast'), 0));

        if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
            $importvideossettings->hide_if('tool_opencast/importreducedduplication_' . $instance->id,
                'tool_opencast/importvideosenabled_' . $instance->id, 'notchecked');
        }

        // Import videos: Enable import videos within Moodle core course import wizard feature.
        // This setting applies to both of import modes, therefore hide_if is only limited to importvideosenabled.
        $importvideossettings->add(
            new \admin_setting_configcheckbox('tool_opencast/importvideoscoreenabled_' . $instance->id,
                get_string('importvideos_settingcoreenabled', 'tool_opencast'),
                get_string('importvideos_settingcoreenabled_desc', 'tool_opencast'), 1));
        if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
            $importvideossettings->hide_if('tool_opencast/importvideoscoreenabled_' . $instance->id,
                'tool_opencast/importvideosenabled_' . $instance->id, 'notchecked');
        }

        // Import videos: Enable manual import videos feature.
        // This setting applies to both of import modes, therefore hide_if is only limited to importvideosenabled.
        $importvideossettings->add(
            new \admin_setting_configcheckbox('tool_opencast/importvideosmanualenabled_' . $instance->id,
                get_string('importvideos_settingmanualenabled', 'tool_opencast'),
                get_string('importvideos_settingmanualenabled_desc', 'tool_opencast'), 0));
        if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
            $importvideossettings->hide_if('tool_opencast/importvideosmanualenabled_' . $instance->id,
                'tool_opencast/importvideosenabled_' . $instance->id, 'notchecked');
        }

        // Import videos: Handle Opencast series modules during manual import.
        $importvideossettings->add(
            new \admin_setting_configcheckbox('tool_opencast/importvideoshandleseriesenabled_' . $instance->id,
                get_string('importvideos_settinghandleseriesenabled', 'tool_opencast'),
                get_string('importvideos_settinghandleseriesenabled_desc', 'tool_opencast'), 0));
        if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
            $importvideossettings->hide_if('tool_opencast/importvideoshandleseriesenabled_' . $instance->id,
                'tool_opencast/importvideosenabled_' . $instance->id, 'notchecked');
            $importvideossettings->hide_if('tool_opencast/importvideoshandleseriesenabled_' . $instance->id,
                'tool_opencast/importmode_' . $instance->id, 'eq', 'acl');
            $importvideossettings->hide_if('tool_opencast/importvideoshandleseriesenabled_' . $instance->id,
                'tool_opencast/duplicateworkflow_' . $instance->id, 'eq', '');
            $importvideossettings->hide_if('tool_opencast/importvideoshandleseriesenabled_' . $instance->id,
                'tool_opencast/importvideosmanualenabled_' . $instance->id, 'notchecked');
        }

        // Import videos: Handle Opencast episode modules during manual import.
        $importvideossettings->add(
            new \admin_setting_configcheckbox('tool_opencast/importvideoshandleepisodeenabled_' . $instance->id,
                get_string('importvideos_settinghandleepisodeenabled', 'tool_opencast'),
                get_string('importvideos_settinghandleepisodeenabled_desc', 'tool_opencast'), 0));
        if ($CFG->branch >= 37) { // The hide_if functionality for admin settings is not available before Moodle 3.7.
            $importvideossettings->hide_if('tool_opencast/importvideoshandleepisodeenabled_' . $instance->id,
                'tool_opencast/importvideosenabled_' . $instance->id, 'notchecked');
            $importvideossettings->hide_if('tool_opencast/importvideoshandleepisodeenabled_' . $instance->id,
                'tool_opencast/importmode_' . $instance->id, 'eq', 'acl');
            $importvideossettings->hide_if('tool_opencast/importvideoshandleepisodeenabled_' . $instance->id,
                'tool_opencast/duplicateworkflow_' . $instance->id, 'eq', '');
            $importvideossettings->hide_if('tool_opencast/importvideoshandleepisodeenabled_' . $instance->id,
                'tool_opencast/importvideosmanualenabled_' . $instance->id, 'notchecked');
        }

        // Don't spam other setting pages with error messages just because the tree was built.
        if ($opencasterror && ($PAGE->pagetype == 'admin-setting-tool_opencast'
        || $PAGE->pagetype == 'admin-setting-tool_opencast_importvideossettings_' . $instanceid)) {
            notification::error($opencasterror);
        }

        $ADMIN->add(self::PLUGINNAME, $importvideossettings);
    }



    /**
     * Requires jquery, amds and css for $PAGE.
     *
     * @param string $pluginnameid
     * The id of the admin instances config of the admin settings of the plugin.
     *
     * @return void
     */
    private static function require_amds(string $pluginnameid): void {
        global $PAGE;

        // Crashes, if plugins.php is opened, because css cannot be included anymore.
        if ($PAGE->state === \moodle_page::STATE_IN_BODY) {
            return;
        }

        // Important for maintenance start and end date calendar js.
        form_init_date_js();
        $PAGE->requires->jquery();
        $PAGE->requires->js_call_amd('tool_opencast/tool_testtool', 'init');
        $PAGE->requires->js_call_amd('tool_opencast/tool_settings', 'init_tool', [$pluginnameid]);
        $PAGE->requires->js_call_amd('tool_opencast/maintenance', 'init');
        $PAGE->requires->css('/admin/tool/opencast/css/tabulator.min.css');
        $PAGE->requires->css('/admin/tool/opencast/css/tabulator_bootstrap4.min.css');
        $PAGE->requires->css('/admin/tool/opencast/css/styles.css');
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
                                                                      int $instanceid): void {
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
                                                         int $instanceid): void {
        self::add_admin_setting_configtext($settings,
            'tool_opencast/apiurl_' . $instanceid,
            'apiurl', 'apiurldesc',
            'https://stable.opencast.org',
            PARAM_URL
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
                                                         $paramtype = PARAM_RAW): void {
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
                                                                   string $defaultsetting): void {
        $settingconfigpasswordunmask = new \admin_setting_configpasswordunmask(
            $name,
            get_string($visiblenameidentifier, self::PLUGINNAME),
            get_string($descriptionidentifier, self::PLUGINNAME),
            $defaultsetting
        );
        $settings->add($settingconfigpasswordunmask);
    }

    /**
     * Adds an admin setting configselect to the passed admin settingpage.
     *
     * @param \admin_settingpage $settings
     * The admin settingpage, the configselect is added to.
     *
     * @param string $name
     * The internal name for the configselect.
     *
     * @param string $visiblenameidentifier
     * The identifier for the string, that is used for the visible name of the configselect.
     *
     * @param string $descriptionidentifier
     * The identifier for the string, that is used for the visible description of the configselect.
     *
     * @param string $defaultsetting
     * The default setting for the configselect.
     *
     * @param array $choices
     * The choices options of the configselect.
     *
     * @return void
     */
    private static function add_admin_setting_configselect(\admin_settingpage $settings,
                                                            string $name,
                                                            string $visiblenameidentifier,
                                                            string $descriptionidentifier,
                                                            string $defaultsetting,
                                                            array $choices): void {
        $settingconfigselect = new \admin_setting_configselect(
            $name,
            get_string($visiblenameidentifier, self::PLUGINNAME),
            get_string($descriptionidentifier, self::PLUGINNAME),
            $defaultsetting,
            $choices
        );
        $settings->add($settingconfigselect);
    }

    /**
     * Adds an admin setting configtextarea to the passed admin settingpage.
     *
     * @param \admin_settingpage $settings
     * The admin settingpage, the configtextarea is added to.
     *
     * @param string $name
     * The internal name for the configtextarea.
     *
     * @param string $visiblenameidentifier
     * The identifier for the string, that is used for the visible name of the configtextarea.
     *
     * @param string $descriptionidentifier
     * The identifier for the string, that is used for the visible description of the configtextarea.
     *
     * @param string $defaultsetting
     * The default setting for the configtextarea.
     *
     * @param mixed $paramtype
     * The parameter type of the configtext.
     *
     * @param string $cols
     * The number of columns to make the editor.
     *
     * @param string $rows
     * The number of rows to make the editor.
     *
     * @return void
     */
    private static function add_admin_setting_configtextarea(\admin_settingpage $settings,
                                                            string $name,
                                                            string $visiblenameidentifier,
                                                            string $descriptionidentifier,
                                                            string $defaultsetting,
                                                            $paramtype = PARAM_RAW,
                                                            string $cols='60',
                                                            string $rows='8'): void {
        $settingconfigtextarea = new admin_setting_configtextarea(
            $name,
            get_string($visiblenameidentifier, self::PLUGINNAME),
            get_string($descriptionidentifier, self::PLUGINNAME),
            $defaultsetting, $paramtype, $cols, $rows
        );
        $settings->add($settingconfigtextarea);
    }


    /**
     * Adds an admin setting confightmleditor to the passed admin settingpage.
     *
     * @param \admin_settingpage $settings
     * The admin settingpage, the confightmleditor is added to.
     *
     * @param string $name
     * The internal name for the confightmleditor.
     *
     * @param string $visiblenameidentifier
     * The identifier for the string, that is used for the visible name of the confightmleditor.
     *
     * @param string $descriptionidentifier
     * The identifier for the string, that is used for the visible description of the confightmleditor.
     *
     * @param string $defaultsetting
     * The default setting for the confightmleditor.
     *
     * @param mixed $paramtype
     * The parameter type of the configtext.
     *
     * @param string $cols
     * The number of columns to make the editor.
     *
     * @param string $rows
     * The number of rows to make the editor.
     *
     * @return void
     */
    private static function add_admin_setting_confightmleditor(\admin_settingpage $settings,
                                                            string $name,
                                                            string $visiblenameidentifier,
                                                            string $descriptionidentifier,
                                                            string $defaultsetting,
                                                            $paramtype = PARAM_RAW,
                                                            string $cols='60',
                                                            string $rows='8'): void {
        $settingconfightmleditor = new \admin_setting_confightmleditor(
            $name,
            get_string($visiblenameidentifier, self::PLUGINNAME),
            get_string($descriptionidentifier, self::PLUGINNAME),
            $defaultsetting, $paramtype, $cols, $rows
        );
        $settings->add($settingconfightmleditor);
    }

    /**
     * Adds an admin setting configdatetimeselector to the passed admin settingpage.
     *
     * @param \admin_settingpage $settings
     * The admin settingpage, the configdatetimeselector is added to.
     *
     * @param string $name
     * The internal name for the configdatetimeselector.
     *
     * @param string $visiblenameidentifier
     * The identifier for the string, that is used for the visible name of the configdatetimeselector.
     *
     * @param string $descriptionidentifier
     * The identifier for the string, that is used for the visible description of the configdatetimeselector.
     *
     * @param int $defaultsetting
     * The default setting timestamp for the configdatetimeselector.
     *
     * @param bool $optional
     * Flag indicating whether this config should be optional with enable checkbox to disable/enable.
     *
     * @param callable|null $validatefunction Validate function or null to clear
     *
     * @return void
     */
    private static function add_admin_setting_configdatetimeselector(\admin_settingpage $settings,
                                                            string $name,
                                                            string $visiblenameidentifier,
                                                            string $descriptionidentifier,
                                                            int $defaultsetting = 0,
                                                            bool $optional = false,
                                                            ?callable $validatefunction = null): void {
        $settingconfigdatetimeselector = new admin_setting_configdatetimeselector(
            $name,
            get_string($visiblenameidentifier, self::PLUGINNAME),
            get_string($descriptionidentifier, self::PLUGINNAME),
            $defaultsetting, $optional
        );
        $settingconfigdatetimeselector->set_validate_function($validatefunction);
        $settings->add($settingconfigdatetimeselector);
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
                                                     int $instanceid): void {
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

    /**
     * Adds the maintenance mode block to the passed admin setting page for the given Opencast instance ID.
     *
     * This block includes a button to sync the maintenance mode settings with the corresponding Opencast instance,
     * a dropdown to select the maintenance mode, a dropdown to select the notification level, a textarea/htmleditor to enter the
     * maintenance message, and two datetime selectors to set the start and end dates of the maintenance period.
     *
     * @param \admin_settingpage $settings The admin setting page to add the maintenance mode block to.
     * @param int $instanceid The ID of the Opencast instance to add the maintenance mode block for.
     * @return void
     */
    private static function add_maintenance_mode_block(\admin_settingpage $settings,
                                                        int $instanceid): void {

        // Prepare the Opencast maintenance sync button.
        $attributes = [
            'class' => 'btn btn-warning disabled maintenance-sync-btn mb-3 mt-2',
            'disabled' => 'disabled',
            'title' => get_string('maintenancemode_btn_disabled', self::PLUGINNAME),
            'data-ocinstanceid' => strval($instanceid),
        ];
        // Get the API fetch (sync) button HTML.
        $apifetchbutton = \html_writer::tag(
            'button',
            get_string('maintenancemode_btn', self::PLUGINNAME),
            $attributes
        );
        // Place the button inside the header description.
        $settings->add(new \admin_setting_heading(
            'tool_opencast/maintenancemodesection',
            get_string('maintenanceheader', self::PLUGINNAME),
            get_string('maintenanceheader_desc', self::PLUGINNAME, $apifetchbutton))
        );

        // Render the maintenance mode option.
        // Record ID outside in order to apply hide_if dependency option.
        $maintenancemodeid = maintenance_class::get_mode_full_config_id($instanceid, true);
        self::add_admin_setting_configselect($settings,
            $maintenancemodeid,
            'maintenancemode', 'maintenancemode_desc',
            maintenance_class::MODE_DISABLE,
            maintenance_class::get_admin_settings_mode_choices()
        );

        // Render the maintenance notify level option.
        $maintenancemodenotiflevelid = maintenance_class::get_notificationlevel_full_config_id($instanceid, true);
        self::add_admin_setting_configselect($settings,
            $maintenancemodenotiflevelid,
            'maintenancemode_notiflevel', 'maintenancemode_notiflevel_desc',
            \core\output\notification::NOTIFY_WARNING,
            maintenance_class::get_admin_settings_notiflevel_choices()
        );
        // Apply hide_if dependency option.
        $settings->hide_if($maintenancemodenotiflevelid, $maintenancemodeid, 'eq', maintenance_class::MODE_DISABLE);

        // Render the maintenance message option.
        $maintenancemessageid = maintenance_class::get_message_full_config_id($instanceid, true);
        self::add_admin_setting_confightmleditor($settings,
            $maintenancemessageid,
            'maintenancemode_message', 'maintenancemode_message_desc',
            '',
        );
        // Apply hide_if dependency option.
        $settings->hide_if($maintenancemessageid, $maintenancemodeid, 'eq', maintenance_class::MODE_DISABLE);

        // Render the maintenance start date options.
        $maintenancestartdateid = maintenance_class::get_startdate_full_config_id($instanceid, true);
        self::add_admin_setting_configdatetimeselector($settings,
            $maintenancestartdateid,
            'maintenancemode_start', 'maintenancemode_start_desc',
            0,
            true,
            maintenance_class::maintenance_datetime_validation(
                $maintenancestartdateid,
                maintenance_class::get_enddate_full_config_id($instanceid),
                'maintenancemode_end',
                '>='
            )
        );
        // Apply hide_if dependency option.
        $settings->hide_if($maintenancestartdateid, $maintenancemodeid, 'eq', maintenance_class::MODE_DISABLE);

        // Render the maintenance end date options.
        $maintenanceenddateid = maintenance_class::get_enddate_full_config_id($instanceid, true);
        self::add_admin_setting_configdatetimeselector($settings,
            $maintenanceenddateid,
            'maintenancemode_end', 'maintenancemode_end_desc',
            0,
            true,
            maintenance_class::maintenance_datetime_validation(
                $maintenanceenddateid,
                maintenance_class::get_startdate_full_config_id($instanceid),
                'maintenancemode_start',
                '<='
            )
        );
        // Apply hide_if dependency option.
        $settings->hide_if($maintenanceenddateid, $maintenancemodeid, 'eq', maintenance_class::MODE_DISABLE);
    }
}
