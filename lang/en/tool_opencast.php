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
 * Plugin strings are defined here.
 *
 * @package     tool_opencast
 * @category    string
 * @copyright   2018 Tobias Reischmann <tobias.reischmann@wi.uni-muenster.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addinstance'] = 'Add instance';
$string['apicreadentialstestfailedshort'] = 'Opencast API User Credentials test failed with http code: {$a}';
$string['apicreadentialstestfailedlong'] = 'The given Username or Password for the Opencast API is not valid.<br />Please use valid Username and Password in order to avoid fatal error during tasks which use this setting.';
$string['apicreadentialstestsuccessfulshort'] = 'Opencast API User Credentials test successful.';
$string['apipassword'] = 'Password of Opencast API user';
$string['apipassworddesc'] = 'Configure the password of the Opencast user who is used to do the Opencast API calls.';
$string['apipasswordempty'] = 'Password of Opencast API user is not configured correctly. Go to the settings of the Opencast API tool to fix this.';
$string['apiurl'] = 'Opencast API URL';
$string['apiurldesc'] = 'Configure the base URL of the Opencast system. A valid URL is required here. If you omit the protocol part here, \'https://\' is added on-the-fly when doing Opencast API calls.';
$string['apiurlempty'] = 'URL of Opencast API is not configured correctly. Go to the settings of the Opencast API tool to fix this.';
$string['apiurltestfailedlong'] = 'There is no Opencast instance running on the given URL.<br />Please use a valid URL in order to avoid fatal error during tasks which use this setting.';
$string['apiurltestfailedshort'] = 'Opencast API URL test failed with http code: {$a}';
$string['apiurltestsucceedbutnocredentialslong'] = 'The Opencast API URL is valid, but Username or Password are not yet provided.<br />Please enter valid Username and Password in order to avoid fatal error during tasks which use this setting.';
$string['apiurltestsuccessfulshort'] = 'Opencast API URL test successful.';
$string['apiusername'] = 'Username of Opencast API user';
$string['apiusernamedesc'] = 'Configure the username of the Opencast user who is used to do the Opencast API calls. Moodle uses this Opencast user for all communication with Opencast. Authorization is done by adding suitable roles to the call.';
$string['apiusernameempty'] = 'Username of Opencast API user is not configured correctly. Go to the settings of the Opencast API tool to fix this.';
$string['configuration'] = 'Configuration';
$string['configuration_instance'] = 'Configuration: {$a}';
$string['connecttimeout'] = 'Connection timeout';
$string['connecttimeoutdesc'] = 'Configure the time in milliseconds while Moodle is trying to connect to Opencast. If Opencast does not answer within this time, the connection attempt times out.';
$string['delete_instance'] = 'Delete instance';
$string['delete_instance_confirm'] = 'Do you really want to delete this instance?<br>
Teachers will not be able to see videos used in this instance anymore.<br>
<b>Caution:</b> All data related to this instance will be lost.<br><br>
The deletion will be performed after you click on \'Save changes\' on the main settings page.';
$string['demoservernotification'] = 'The Opencast API tool is currently configured to connect to the <a href=\'https://stable.opencast.org\'>public Opencast demo server</a>. You can use this Opencast server for evaluating this plugin.<br />Do not use it for any production purposes. Please <a href=\'https://docs.opencast.org/\'>setup your own Opencast server</a> instead.';
$string['errornumdefaultinstances'] = 'There must be exactly one default Opencast instance.';
$string['isdefault'] = 'Default';
$string['isvisible'] = 'Is visible to teachers';
$string['lticonsumerkey'] = 'Consumer key';
$string['lticonsumerkey_desc'] = 'LTI Consumer key for the integration of Opencast services that require authentication such as Studio or the editor.';
$string['lticonsumersecret'] = 'Consumer secret';
$string['lticonsumersecret_desc'] = 'LTI Consumer secret for the integration of Opencast services that require authentication.';
$string['maintenance_default_notification_message'] = '<h5>Opencast Maintenance Notice</h5><br>Please note that Opencast is currently undergoing maintenance. As a result, some or all features related to Opencast may be temporarily unavailable. Thank you for your understanding.';
$string['maintenance_exception_message'] = 'Opencast is currently undergoing maintenance. Interactions are temporarily disabled.';
$string['maintenanceheader'] = 'Maintenance';
$string['maintenanceheader_desc'] = 'In this section the maintenance mode can be configured with the following settings.<br />Depending on Opencast feature and settings availability, is it also possible to {$a}';
$string['maintenancemode'] = 'Maintenance mode';
$string['maintenancemode_btn'] = 'Sync Opencast Maintenance Mode';
$string['maintenancemode_btn_disabled'] = 'Required js modules are not loaded.';
$string['maintenancemode_enable'] = 'Enable';
$string['maintenancemode_end'] = 'Maintenance ends at';
$string['maintenancemode_end_desc'] = 'The end date and time of maintenance';
$string['maintenancemode_datetime_expired_error'] = 'This field should not be in the past!';
$string['maintenancemode_datetime_ge_error'] = 'This field should be before "{$a}"';
$string['maintenancemode_datetime_le_error'] = 'This field should be after "{$a}"';
$string['maintenancemode_desc'] = 'Setting maintenance mode to avoid conflict during Opencast downtime.<br>If Read-Only mode is selected, only reading resources from Opencast will be allowed.';
$string['maintenancemode_disable'] = 'Disable';
$string['maintenancemode_message'] = 'Maintenance Message';
$string['maintenancemode_message_desc'] = 'An error message to display during maintenance.';
$string['maintenancemode_modal_sync_confirmation_title'] = 'Sync Opencast Maintenance Mode';
$string['maintenancemode_modal_sync_confirmation_text'] = 'Are you sure to sync the maintenance mode with Opencast? This wil overwrite the current configuration.';
$string['maintenancemode_modal_sync_confirmation_btn'] = 'Sync';
$string['maintenancemode_modal_sync_error_title'] = 'Syncing Error';
$string['maintenancemode_modal_sync_error_noinstance_message'] = 'Unable to find the Opencast instance id!';
$string['maintenancemode_modal_sync_failed'] = 'Maintenance Synchronization Unsuccessful!';
$string['maintenancemode_modal_sync_succeeded'] = 'Maintenance successfully synchronized. The page will refresh in 3 seconds to apply the updated changes.';
$string['maintenancemode_notiflevel'] = 'Notification Level';
$string['maintenancemode_notiflevel_desc'] = 'By this setting you can set the level of notification message which helps rendering it in different styles and color based on the level e.g. Error Level will print a notification in a red box.';
$string['maintenancemode_notiflevel_error'] = 'Error';
$string['maintenancemode_notiflevel_info'] = 'Information';
$string['maintenancemode_notiflevel_success'] = 'Success';
$string['maintenancemode_notiflevel_warning'] = 'Warning';
$string['maintenancemode_readonly'] = 'Read Only';
$string['maintenancemode_start'] = 'Maintenance starts at';
$string['maintenancemode_start_desc'] = 'The start date and time of maintenance';
$string['name'] = 'Name';
$string['needphp55orhigher'] = 'PHP Version 5.5 or higher is needed';
$string['nomockhandler'] = 'The Opencast Api Object is unable to handle the responses for testing purposes.';
$string['notestingjsonresponses'] = 'The JSON responses are not set, please make sure to use api_testable::add_json_response before running and using the class.';
$string['ocinstances'] = 'Opencast Instances';
$string['ocinstancesdesc'] = 'Defines a list of Opencast Instances to which the Opencast plugins can connect.';
$string['opencast:externalapi'] = 'Access to Opencast API webservices';
$string['opencast:instructor'] = 'Gives the role of an instructor in Opencast';
$string['opencast:learner'] = 'Gives the role of a learner in Opencast';
$string['pluginname'] = 'Opencast API';
$string['privacy:metadata'] = 'The Opencast API admin tool only provides API endpoints and general settings for the set of Opencast plugins. It stores which Opencast series belongs to which Moodle course, but it does not store any personal data.';
$string['serverconnectionerror'] = 'There was a problem with the connection to the Opencast server. Please check your Opencast API credentials and your network settings.';
$string['testtooldisabledbuttontitle'] = 'Unable to conduct the connection test due to unloaded js modules.';
$string['testtoolheader'] = 'Connection test tool';
$string['testtoolheaderdesc'] = 'To test the current Opencast API settings use: {$a}';
$string['testtoolurl'] = 'Connection Test Tool';
$string['timeout'] = 'Overall API request execution timeout';
$string['timeoutdesc'] = 'Configure the time in milliseconds each API request to Opencast may take. If Opencast does not finish answering the request within this time, the request is aborted.';
$string['wrongmimetypedetected'] = 'Wrong mimetype was detected, while trying to upload {$a->filename} from course {$a->coursename}. You can only upload video files!';
