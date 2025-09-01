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
 * Add new transcription to the event
 *
 * @package    tool_opencast
 * @copyright  2022 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_opencast\local\addtranscription_form;
use tool_opencast\local\apibridge;
use tool_opencast\local\attachment_helper;
use core\output\notification;
use tool_opencast\local\settings_api;

require_once('../../../config.php');

global $PAGE, $OUTPUT, $CFG, $SITE;

require_once($CFG->dirroot . '/repository/lib.php');

$identifier = required_param('video_identifier', PARAM_ALPHANUMEXT);
$courseid = required_param('courseid', PARAM_INT);
$ocinstanceid = optional_param('ocinstanceid', settings_api::get_default_ocinstance()->id, PARAM_INT);

$indexurl = new moodle_url('/admin/tool/opencast/index.php', ['courseid' => $courseid, 'ocinstanceid' => $ocinstanceid]);
$redirecturl = new moodle_url('/admin/tool/opencast/managetranscriptions.php',
    ['video_identifier' => $identifier, 'courseid' => $courseid, 'ocinstanceid' => $ocinstanceid]);
$baseurl = new moodle_url('/admin/tool/opencast/addtranscriptions.php',
    ['video_identifier' => $identifier, 'courseid' => $courseid, 'ocinstanceid' => $ocinstanceid]);
$PAGE->set_url($baseurl);

require_login($courseid, false);

$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('servicename', 'tool_opencast'));
$PAGE->set_heading(get_string('servicename', 'tool_opencast'));
$PAGE->navbar->add(get_string('servicename', 'tool_opencast'), $indexurl);
$PAGE->navbar->add(get_string('managetranscriptions', 'tool_opencast'), $redirecturl);
$PAGE->navbar->add(get_string('addnewtranscription', 'tool_opencast'), $baseurl);

// Capability check.
$coursecontext = context_course::instance($courseid);
require_capability('tool/opencast:addvideo', $coursecontext);

$apibridge = apibridge::get_instance($ocinstanceid);
$video = $apibridge->get_opencast_video($identifier);

$transcriptionmanagementenabled = (bool) get_config('tool_opencast', 'enablemanagetranscription_' . $ocinstanceid);
$transcriptionlanguagesconfig = get_config('tool_opencast', 'transcriptionlanguages_' . $ocinstanceid);

if (!$transcriptionmanagementenabled || empty($transcriptionlanguagesconfig)) {
    redirect($redirecturl,
        get_string('transcriptionmanagementdisabled', 'tool_opencast'), null, notification::NOTIFY_ERROR);
}

if ($video->error || $video->video->processing_state != 'SUCCEEDED') {
    redirect($redirecturl,
        get_string('unabletoaddnewtranscription', 'tool_opencast'), null, notification::NOTIFY_ERROR);
}

$addtranscriptionform = new addtranscription_form(null,
    ['courseid' => $courseid, 'identifier' => $identifier, 'ocinstanceid' => $ocinstanceid]);

if ($addtranscriptionform->is_cancelled()) {
    redirect($redirecturl);
}

if ($data = $addtranscriptionform->get_data()) {
    $languagesarray = json_decode($transcriptionlanguagesconfig) ?? [];
    $storedlanguagefiles = [];
    foreach ($languagesarray as $language) {
        if (empty($language->key)) {
            continue;
        }
        $languagename = !empty($language->value) ? format_string($language->value) : $language->key;

        $fileelm = "transcription_file_{$language->key}";
        if (property_exists($data, $fileelm)) {
            $storedfile = $addtranscriptionform->save_stored_file($fileelm, $coursecontext->id,
                'tool_opencast', attachment_helper::OC_FILEAREA_ATTACHMENT, $data->{$fileelm});
            if (isset($storedfile) && $storedfile) {
                $storedlanguagefiles[$language->key] = $storedfile;
            }
        }
    }

    if (empty($storedlanguagefiles)) {
        redirect($redirecturl, get_string('transcriptionnothingtoupload', 'tool_opencast'), null, notification::NOTIFY_INFO);
    }

    $result = attachment_helper::upload_transcription_captions_set($storedlanguagefiles, $ocinstanceid, $identifier);

    // No matter the result, we remove the files from file storage.
    foreach ($storedlanguagefiles as $storedfile) {
        attachment_helper::remove_single_transcription_file($storedfile->get_itemid());
    }

    $message = get_string('transcriptionuploadsuccessedall', 'tool_opencast');
    $status = notification::NOTIFY_SUCCESS;

    if (!$result) {
        $message = get_string('transcriptionuploadfailedall', 'tool_opencast');
        $status = notification::NOTIFY_ERROR;
    }

    redirect($redirecturl, $message, null, $status);
}

/** @var tool_opencast_renderer $renderer */
$renderer = $PAGE->get_renderer('tool_opencast');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('addnewtranscription', 'tool_opencast'));
$addtranscriptionform->display();
echo $OUTPUT->footer();
