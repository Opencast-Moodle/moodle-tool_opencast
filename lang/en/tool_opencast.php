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

$string['apipassword'] = 'Password for API user';
$string['apipassworddesc'] = 'Setup a password for the super user, who does the api calls.';
$string['apipasswordempty'] = 'Password for API user is not setup correctly, go to settings of tool opencast to fix this';
$string['apiurl'] = 'Opencast API url';
$string['apiurldesc'] = 'Setup the base url of the Opencast system, for example: opencast.example.com';
$string['apiurlempty'] = 'Url for Opencast API is not setup correctly, go to settings of tool opencast to fix this';
$string['apiusername'] = 'Username for API calls';
$string['apiusernamedesc'] = 'For all calls to the API moodle uses this user. Authorization is done by adding suitable roles to the call';
$string['apiusernameempty'] = 'Username for Opencast API user is not setup correctly, go to settings of tool opencast to fix this';
$string['connecttimeout'] = 'Connection timeout';
$string['connecttimeoutdesc'] = 'Setup the time in seconds while moodle is trying to connect to opencast until timeout';

$string['opencast:externalapi'] = 'Access to tool_opencast webservices';
$string['opencast:instructor'] = 'Gives the role of an instructor in opencast';
$string['opencast:learner'] = 'Gives the role of a learner in opencast';

$string['needphp55orhigher'] = 'PHP Version 5.5 or higher is needed';
$string['wrongmimetypedetected'] = 'Wrong mimetype was detected, while trying to upload {$a->filename} from course {$a->coursename},
    You can only upload video files!';
$string['serverconnectionerror'] = 'There was a problem with the connection to the opencast server. Please check your credentials and your network settings.';

// Privacy API.
$string['privacy:metadata'] = 'The admin tool only provides API endpoints and general settings for the set of opencast plugin.
It saves, which opencast series belongs to which course, but it does not store any personal data.';
