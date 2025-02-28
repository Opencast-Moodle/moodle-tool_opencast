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

defined('MOODLE_INTERNAL') || die;


function tool_opencast_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('block/opencast:addactivity', $context)) {
        $url = new moodle_url('/admin/tool/opencast/index.php', array('courseid'=>$course->id));
        $navigation->add(get_string('pluginname', 'tool_opencast'), $url, navigation_node::TYPE_COURSE, null, null, new pix_icon('i/report', ''));
    }
}