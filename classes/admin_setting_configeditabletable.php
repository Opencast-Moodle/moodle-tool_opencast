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
 * Admin setting class which is used to create an editable table.
 *
 * @package    tool_opencast
 * @copyright  2021 Tamara Gunkel, University of Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_opencast;

defined('MOODLE_INTERNAL') || die();

/**
 * Admin setting class which is used to create an editable table.
 *
 * @package    tool_opencast
 * @copyright  2021 Tamara Gunkel, University of Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_configeditabletable extends \admin_setting
{
    /** @var string Id of the div tag */
    private $divid;

    /**
     * Not a setting, just an editable table.
     * @param string $name Setting name
     * @param string $divid Id of the div tag
     */
    public function __construct($name, $divid) {
        $this->nosave = true;
        $this->divid = $divid;
        parent::__construct($name, '', '', '');
    }

    /**
     * Always returns true
     *
     * @return bool Always returns true
     */
    public function get_setting() {
        return true;
    }

    /**
     * Always returns true
     *
     * @return bool Always returns true
     */
    public function get_defaultsetting() {
        return true;
    }

    /**
     * Never write settings
     *
     * @param mixed $data Gets converted to str for comparison against yes value
     * @return string Always returns an empty string
     */
    public function write_setting($data) {
        // Do not write any setting.
        return '';
    }

    /**
     * Returns an HTML string
     *
     * @param string $data
     * @param string $query
     * @return string Returns an HTML string
     */
    public function output_html($data, $query = '') {
        return '<div class="row justify-content-end"><div class="mt-3 col-sm-9 p-0" id="' . $this->divid .
            '"></div></div><button class="btn btn-primary mt-3 float-right" type="button" id="addrow-' . $this->divid . '">' .
            get_string('addinstance', 'tool_opencast') . '</button>';
    }
}
