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
 * Tool opencast test data generator class
 *
 * @package tool_opencast
 * @copyright 2021 Tamara Gunkel, University of Münster
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tool opencast test data generator class
 *
 * @package tool_opencast
 * @copyright 2021 Tamara Gunkel, University of Münster
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_opencast_generator extends testing_module_generator {

    /**
     * Create a new series.
     *
     * @param array $data
     * @throws dml_exception
     */
    public function create_series($data) {
        global $DB;
        $courses = $DB->get_records('course', array('shortname' => $data['course']));

        $series = (object)[
            'courseid' => reset($courses)->id,
            'series' => $data['series'],
            'isdefault' => $data['isdefault'],
            'ocinstanceid' => $data['ocinstanceid']
        ];
        $DB->insert_record('tool_opencast_series', $series);
    }
}
