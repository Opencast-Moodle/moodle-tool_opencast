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
use tool_opencast\local\maintenance_class;
use block_opencast\setting_helper; // TODO: migrieren
use block_opencast\opencast_connection_exception; // TODO: migrieren
use block_opencast\admin_setting_configtextvalidate; // TODO: migrieren
use block_opencast\admin_setting_hiddenhelpbtn; // TODO: migrieren
use block_opencast\setting_default_manager; // TODO: migrieren


use tool_opencast\empty_configuration_exception;


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
        } else{
            foreach ($instances as $instance) {
                self::add_admin_settingpage('tool_opencast_configuration_' . $instance->id,
                    'configuration_instance', $instance->name);
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
                $settings = self::create_admin_settingpage('tool_opencast_configuration',
                    'configuration');
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

        $instancessettings->add(new admin_setting_configeditabletable(
                'tool_opencast/instancestable',
                'instancestable')
        );

        global $ADMIN;
        $ADMIN->add(self::PLUGINNAME, $instancessettings);
    }

    private static function add_admin_shared_settings(): void {

        // Shared Settings Page
        $sharedsettings = self::create_admin_settingpage('tool_opencast_sharedsettings',
                    'shared_settings');

        // Cache Validtime
        self::add_admin_setting_configtext($sharedsettings, 'tool_opencast/cachevalidtime',
            'cachevalidtime',
            'cachevalidtime_desc', 500, PARAM_INT);

        // Upload timeout
        self::add_admin_setting_configtext($sharedsettings, 'tool_opencast/uploadtimeout',
            'uploadtimeout',
            'uploadtimeoutdesc', 60, PARAM_INT);

        // Failedupload retrylimit
        self::add_admin_setting_configtext($sharedsettings, 'tool_opencast/faileduploadretrylimit',
        'faileduploadretrylimit',
        'faileduploadretrylimitdesc', 0, PARAM_INT);

        global $ADMIN;
        $ADMIN->add(self::PLUGINNAME, $sharedsettings);
    }

    private static function add_admin_general_settings($settings, $instanceid, $instance): void {

        global $PAGE, $CFG;

        // General Settings Page
        $generalsettings = self::create_admin_settingpage('tool_opencast_generalsettings_' . $instanceid,
                    'general_settings');


        $opencasterror = false;

        // Initialize the default settings for each instance.
        setting_default_manager::init_regirstered_defaults($instanceid);

        // Setup js.
        $rolesdefault = setting_default_manager::get_default_roles();
        $metadatadefault = setting_default_manager::get_default_metadata();
        $metadataseriesdefault = setting_default_manager::get_default_metadataseries();
        $defaulttranscriptionflavors = setting_default_manager::get_default_transcriptionflavors();

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
            get_string('aclrolesnamedesc',
                'tool_opencast'), $rolesdefault);

        $dcmitermsnotice = get_string('dcmitermsnotice', 'tool_opencast');
        $metadatasetting = new \admin_setting_configtext('tool_opencast/metadata_' . $instanceid,
            get_string('metadata', 'tool_opencast'),
            get_string('metadatadesc', 'tool_opencast') . $dcmitermsnotice, $metadatadefault);

        $metadataseriessetting = new \admin_setting_configtext('tool_opencast/metadataseries_' . $instanceid,
            get_string('metadataseries', 'tool_opencast'),
            get_string('metadataseriesdesc', 'tool_opencast') . $dcmitermsnotice, $metadataseriesdefault);

        $transcriptionflavors = new \admin_setting_configtext('tool_opencast/transcriptionflavors_' . $instanceid,
            get_string('transcriptionflavors', 'tool_opencast'),
            get_string('transcriptionflavors_desc', 'tool_opencast'), $defaulttranscriptionflavors);

        // Crashes if plugins.php is opened because css cannot be included anymore.
        if ($PAGE->state !== \moodle_page::STATE_IN_BODY) {
            $PAGE->requires->js_call_amd('tool_opencast/tool_settings', 'init', [
                $rolessetting->get_id(),
                $metadatasetting->get_id(),
                $metadataseriessetting->get_id(),
                $transcriptionflavors->get_id(),
                $instanceid,
            ]);
        }

        // Limit uploadjobs
        $url = new \moodle_url('/admin/tool/task/scheduledtasks.php');
        $link = \html_writer::link($url, get_string('pluginname', 'tool_task'), ['target' => '_blank']);
        $generalsettings->add(
            new \admin_setting_configtext('tool_opencast/limituploadjobs_' . $instanceid,
                get_string('limituploadjobs', 'tool_opencast'),
                get_string('limituploadjobsdesc', 'tool_opencast', $link), 1, PARAM_INT));

        $workflowchoices = setting_helper::load_workflow_choices($instanceid, 'upload');
        if ($workflowchoices instanceof opencast_connection_exception ||
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
        if ($workflowchoices instanceof opencast_connection_exception ||
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
        if ($workflowchoices instanceof opencast_connection_exception ||
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

        // $opencasterror = false;

        // // Initialize the default settings for each instance.
        // setting_default_manager::init_regirstered_defaults($instance->id);

        // // Setup js.
        // $rolesdefault = setting_default_manager::get_default_roles();
        // $metadatadefault = setting_default_manager::get_default_metadata();
        // $metadataseriesdefault = setting_default_manager::get_default_metadataseries();
        // $defaulttranscriptionflavors = setting_default_manager::get_default_transcriptionflavors();

        // $generalsettings->add(new admin_setting_hiddenhelpbtn('block_opencast/hiddenhelpname_' . $instance->id,
        //     'helpbtnname_' . $instance->id, 'descriptionmdfn', 'block_opencast'));
        // $generalsettings->add(new admin_setting_hiddenhelpbtn('block_opencast/hiddenhelpparams_' . $instance->id,
        //     'helpbtnparams_' . $instance->id, 'catalogparam', 'block_opencast'));
        // $generalsettings->add(new admin_setting_hiddenhelpbtn('block_opencast/hiddenhelpdescription_' . $instance->id,
        //     'helpbtndescription_' . $instance->id, 'descriptionmdfd', 'block_opencast'));
        // $generalsettings->add(new admin_setting_hiddenhelpbtn('block_opencast/hiddenhelpdefaultable_' . $instance->id,
        //     'helpbtndefaultable_' . $instance->id, 'descriptionmddefaultable', 'block_opencast'));
        // $generalsettings->add(new admin_setting_hiddenhelpbtn('block_opencast/hiddenhelpbatchable_' . $instance->id,
        //     'helpbtnbatchable_' . $instance->id, 'descriptionmdbatchable', 'block_opencast'));
        // $generalsettings->add(new admin_setting_hiddenhelpbtn('block_opencast/hiddenhelpreadonly_' . $instance->id,
        //     'helpbtnreadonly_' . $instance->id, 'descriptionmdreadonly', 'block_opencast'));

        // $rolessetting = new admin_setting_configtext('block_opencast/roles_' . $instance->id,
        //     get_string('aclrolesname', 'block_opencast'),
        //     get_string('aclrolesnamedesc',
        //         'block_opencast'), $rolesdefault);

        // $dcmitermsnotice = get_string('dcmitermsnotice', 'block_opencast');
        // $metadatasetting = new admin_setting_configtext('block_opencast/metadata_' . $instance->id,
        //     get_string('metadata', 'block_opencast'),
        //     get_string('metadatadesc', 'block_opencast') . $dcmitermsnotice, $metadatadefault);

        // $metadataseriessetting = new admin_setting_configtext('block_opencast/metadataseries_' . $instance->id,
        //     get_string('metadataseries', 'block_opencast'),
        //     get_string('metadataseriesdesc', 'block_opencast') . $dcmitermsnotice, $metadataseriesdefault);

        // $transcriptionflavors = new admin_setting_configtext('block_opencast/transcriptionflavors_' . $instance->id,
        //     get_string('transcriptionflavors', 'block_opencast'),
        //     get_string('transcriptionflavors_desc', 'block_opencast'), $defaulttranscriptionflavors);

        global $ADMIN;
        $ADMIN->add(self::PLUGINNAME, $generalsettings);

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
        $PAGE->requires->js_call_amd('tool_opencast/tool_settings', 'init', [$pluginnameid]);
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
