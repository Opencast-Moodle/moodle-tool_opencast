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

$string['pluginname'] = 'Opencast API';

$string['apipassword'] = 'Password of Opencast API user';
$string['apipassworddesc'] = 'Configure the password of the Opencast user who is used to do the Opencast API calls.';
$string['apipasswordempty'] = 'Password of Opencast API user is not configured correctly. Go to the settings of the Opencast API tool to fix this.';
$string['apiurl'] = 'Opencast API URL';
$string['apiurldesc'] = 'Configure the base URL of the Opencast system. A valid URL is required here. If you omit the protocol part here, \'https://\' is added on-the-fly when doing Opencast API calls.';
$string['apiurlempty'] = 'URL of Opencast API is not configured correctly. Go to the settings of the Opencast API tool to fix this.';
$string['apiusername'] = 'Username of Opencast API user';
$string['apiusernamedesc'] = 'Configure the username of the Opencast user who is used to do the Opencast API calls. Moodle uses this Opencast user for all communication with Opencast. Authorization is done by adding suitable roles to the call.';
$string['apiusernameempty'] = 'Username of Opencast API user is not configured correctly. Go to the settings of the Opencast API tool to fix this.';
$string['connecttimeout'] = 'Connection timeout';
$string['connecttimeoutdesc'] = 'Configure the time in seconds while Moodle is trying to connect to Opencast. If Opencast does not answer within this time, the connection attempt times out.';
$string['demoservernotification'] = 'The Opencast API tool is currently configured to connect to the <a href="https://stable.opencast.org">public Opencast demo server</a>. You can use this Opencast server for evaluating this plugin.<br />Do not use it for any production purposes. Please <a href="https://docs.opencast.org/">setup your own Opencast server</a> instead.';
$string['apiurltestfailedlong'] = 'There is no Opencast instance running on the given URL.<br />Please use a valid URL in order to avoid fatal error during tasks which use this setting.';
$string['apiurltestsucceedbutnocredentialslong'] = 'The Opencast API URL is valid, but Username or Password are not yet provided.<br />Please enter valid Username and Password in order to avoid fatal error during tasks which use this setting.';
$string['apicreadentialstestfailedlong'] = 'The given Username or Password for the Opencast API is not valid.<br />Please use valid Username and Password in order to avoid fatal error during tasks which use this setting.';
$string['testtoolheader'] = 'Connection test tool';
$string['testtoolheaderdesc'] = 'To test the current Opencast API settings use: {$a}';
$string['testtoolurl'] = 'Connection Test Tool';
$string['apiurltestsuccessfulshort'] = 'Opencast API URL test successful.';
$string['apiurltestfailedshort'] = 'Opencast API URL test failed!';
$string['apicreadentialstestsuccessfulshort'] = 'Opencast API User Credentials test successful.';
$string['apicreadentialstestfailedshort'] = 'Opencast API User Credentials test failed!';
$string['testtooldisabledbuttontitle'] = 'Unable to conduct the connection test due to unloaded js modules.';

$string['configuration'] = 'Configuration';
$string['configuration_instance'] = 'Configuration: {$a}';
$string['ocinstances'] = 'Opencast Instances';
$string['ocinstancesdesc'] = 'Defines a list of Opencast Instances to which the Opencast plugins can connect.';
$string['name'] = 'Name';
$string['isvisible'] = 'Is visible to teachers';
$string['isdefault'] = 'Default';
$string['addinstance'] = 'Add instance';
$string['delete_instance'] = 'Delete instance';
$string['delete_instance_confirm'] = 'Do you really want to delete this instance?<br>
Teachers will not be able to see videos used in this instance anymore.<br>
<b>Caution:</b> All data related to this instance will be lost.<br><br>
The deletion will be performed after you click on "Save changes" on the main settings page.';
$string['errornumdefaultinstances'] = 'There must be exactly one default Opencast instance.';

$string['opencast:externalapi'] = 'Access to Opencast API webservices';
$string['opencast:instructor'] = 'Gives the role of an instructor in Opencast';
$string['opencast:learner'] = 'Gives the role of a learner in Opencast';

$string['needphp55orhigher'] = 'PHP Version 5.5 or higher is needed';
$string['wrongmimetypedetected'] = 'Wrong mimetype was detected, while trying to upload {$a->filename} from course {$a->coursename}. You can only upload video files!';
$string['serverconnectionerror'] = 'There was a problem with the connection to the Opencast server. Please check your Opencast API credentials and your network settings.';

// Privacy API.
$string['privacy:metadata'] = 'The Opencast API admin tool only provides API endpoints and general settings for the set of Opencast plugins. It stores which Opencast series belongs to which Moodle course, but it does not store any personal data.';
