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
 * Public API of the tool_opencast Plugin
 *
 *
 * @package    tool_opencast
 * @copyright  2025 Berthold Bußkamp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_opencast\local\apibridge;
use tool_opencast\local\series_form;
use tool_opencast\seriesmapping;

/**
 * Extend the navigation for the course page.
 *
 * @param navigation_node $navigation The navigation node to extend.
 * @param stdClass $course The course object.
 * @param context $context The context of the course.
 */
function tool_opencast_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('tool/opencast:addactivity', $context)) {
        $url = new moodle_url('/admin/tool/opencast/index.php', ['courseid' => $course->id]);
        $navigation->add(get_string('servicename', 'tool_opencast'), $url, navigation_node::TYPE_COURSE,
        null, null, new pix_icon('i/report', ''));
    }
}

/**
 * Get icon mapping for FontAwesome.
 *
 * @return array
 */
function tool_opencast_get_fontawesome_icon_map() {
    return [
        'tool_opencast:share' => 'fa-share-square',
        'tool_opencast:play' => 'fa-play-circle',
    ];
}

/**
 * Serve the create series form as a fragment.
 * @param object $args
 */
function tool_opencast_output_fragment_series_form($args) {
    global $CFG, $USER, $DB;

    $args = (object)$args;
    $context = $args->context;
    $o = '';

    $formdata = [];
    if (!empty($args->jsonformdata)) {
        parse_str($args->jsonformdata, $formdata);
        foreach ($formdata as $field => $value) {
            if ($value === "_qf__force_multiselect_submisstion") {
                $formdata[$field] = '';
            }
        }
    }

    list($ignored, $course) = get_context_info_array($context->id);

    require_capability('tool/opencast:createseriesforcourse', $context);

    $metadatacatalog = json_decode(get_config('tool_opencast', 'metadataseries_' . $args->ocinstanceid));
    // Make sure $metadatacatalog is array.
    $metadatacatalog = !empty($metadatacatalog) ? $metadatacatalog : [];
    if ($formdata) {
        $mform = new series_form(null, ['courseid' => $course->id,
            'ocinstanceid' => $args->ocinstanceid, 'metadata_catalog' => $metadatacatalog, ], 'post', '', null, true, $formdata);
        $mform->is_validated();
    } else if ($args->seriesid) {
        // Load stored series metadata.
        $apibridge = apibridge::get_instance($args->ocinstanceid);
        $ocseries = $apibridge->get_series_metadata($args->seriesid);
        $mform = new series_form(null, ['courseid' => $course->id, 'metadata' => $ocseries,
            'ocinstanceid' => $args->ocinstanceid, 'metadata_catalog' => $metadatacatalog, ]);
    } else {
        // Get user series defaults when the page is new.
        $userdefaultsrecord = $DB->get_record('tool_opencast_user_default', ['userid' => $USER->id]);
        $userdefaults = $userdefaultsrecord ? json_decode($userdefaultsrecord->defaults, true) : [];
        $userseriesdefaults = (!empty($userdefaults['series'])) ? $userdefaults['series'] : [];
        $mform = new series_form(null, ['courseid' => $course->id, 'ocinstanceid' => $args->ocinstanceid,
            'metadata_catalog' => $metadatacatalog, 'seriesdefaults' => $userseriesdefaults, ]);
    }

    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();

    return $o;
}


/**
 * Pre-delete course hook to cleanup any records with references to the deleted course.
 *
 * @param stdClass $course The deleted course
 */
function tool_opencast_pre_course_delete(stdClass $course) {
    $mappings = seriesmapping::get_records(['courseid' => $course->id]);
    foreach ($mappings as $mapping) {
        $mapping->delete();
    }
}
