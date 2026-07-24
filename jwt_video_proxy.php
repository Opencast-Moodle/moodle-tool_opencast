<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * JWT Video Proxy for serving videos with a possibility of an interception to attach access tokens.
 * Serving video happens with a soft redirect.
 * @package    tool_opencast
 * @copyright  2026 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_opencast\local\settings_api;
use tool_opencast\local\jwt_service;

define('NO_DEBUG_DISPLAY', true);
require(__DIR__ . '/../../../config.php');
require_login();

$videoencodedurl = required_param('url', PARAM_URL);
$identifier   = required_param('identifier', PARAM_ALPHANUMEXT);
$courseid   = required_param('courseid', PARAM_INT);

$ocinstanceid = optional_param(
    'ocinstanceid',
    settings_api::get_default_ocinstance()->id,
    PARAM_INT
);
$duration = optional_param('duration', 0, PARAM_INT);

$context = context_course::instance($courseid);
require_capability('tool/opencast:learner', $context);

// We check if the requested video url to proxy is matching the api url of the instance.
$apiurl = settings_api::get_apiurl($ocinstanceid);
$parsedapiurl = (array) parse_url($apiurl);
$parsedvideoencodedurl = (array) parse_url($videoencodedurl);
if (
    $parsedapiurl['scheme'] !== $parsedvideoencodedurl['scheme'] ||
    $parsedapiurl['host'] !== $parsedvideoencodedurl['host']
) {
    throw new moodle_exception('jwt_error_nomatchingurl', 'tool_opencast');
}

$basicapi = \tool_opencast\local\api::get_instance($ocinstanceid, [], [], false, false);

$extendedduration = (int) settings_api::get_jwt_video_proxy_token_duration($ocinstanceid) ??
    jwt_service::get_suggested_video_proxy_token_duration();
if ($duration && $duration > $extendedduration) {
    $extendedduration = $duration * 3;
}

$finalvideourl = $basicapi?->jwtservice?->attach_jwt_url_param_event(
    $videoencodedurl,
    $identifier,
    [],
    $extendedduration
) ?? $videoencodedurl;

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Location: ' . $finalvideourl, true, 303);
exit;
