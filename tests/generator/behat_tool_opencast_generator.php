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
 * Behat data generator for tool_opencast.
 *
 * @package   tool_opencast
 * @category  test
 * @copyright 2021 Tamara Gunkel, University of Münster
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Behat data generator for tool_opencast.
 *
 * @copyright 2021 Tamara Gunkel, University of Münster
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_opencast_generator extends behat_generator_base {

    protected function get_creatable_entities(): array {
        return [
            'series' => [
                'singular' => 'series',
                'datagenerator' => 'series',
                'required' => ['course', 'series', 'isdefault', 'ocinstanceid']
            ],
        ];
    }
}
