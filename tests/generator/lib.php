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

 use tool_opencast\local\upload_helper;

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
        $courses = $DB->get_records('course', ['shortname' => $data['course']]);

        $series = (object)[
            'courseid' => reset($courses)->id,
            'series' => $data['series'],
            'isdefault' => $data['isdefault'],
            'ocinstanceid' => $data['ocinstanceid'],
        ];
        $DB->insert_record('tool_opencast_series', $series);
    }

        /**
     * Creates a file.
     * @param null $record
     * @return stored_file
     * @throws file_exception
     * @throws moodle_exception
     * @throws stored_file_creation_exception
     */
    public function create_file($record = null) {
        global $USER;

        if (!isset($record['courseid'])) {
            throw new moodle_exception('course id missing');
        }

        $record['contextid'] = context_course::instance($record['courseid'])->id;
        $record['component'] = 'tool_opencast';

        $record['filearea'] = upload_helper::OC_FILEAREA;
        $record['itemid'] = 0;

        if (!isset($record['filepath'])) {
            $record['filepath'] = '/';
        }

        if (!isset($record['filename'])) {
            $record['filename'] = 'test.mp4';
        }

        if (!isset($record['userid'])) {
            $record['userid'] = $USER->id;
        }

        $record['source'] = 'Copyright stuff';

        if (!isset($record['author'])) {
            $record['author'] = fullname($USER);
        }

        if (!isset($record['license'])) {
            $record['license'] = 'cc';
        }

        if (!isset($record['filecontent'])) {
            throw new moodle_exception('file is missing');
        }

        $fs = get_file_storage();
        return $fs->create_file_from_string($record, $record['filecontent']);
    }
}
