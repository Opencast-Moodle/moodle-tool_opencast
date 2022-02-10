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
 * API for opencast
 *
 * @package    tool_opencast
 * @copyright  2018 Tobias Reischmann <tobias.reischmann@wi.uni-muenster.de>
 * @copyright  2017 Andreas Wagner, SYNERGY LEARNING
 * @author     Andreas Wagner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_opencast\local;

use local_chunkupload\chunkupload_form_element;
use local_chunkupload\local\chunkupload_file;
use tool_opencast\empty_configuration_exception;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/lib/filelib.php');

/**
 * Settings API for opencast
 *
 * @package    tool_opencast
 * @copyright  2021 Tamara Gunkel <tamara.gunkel@wi.uni-muenster.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings_api extends \curl {

    /**
     * Returns the api url of an Opencast instance.
     * @param int $ocinstanceid
     * @return string
     */
    public static function get_apiurl($ocinstanceid) {
        $ocinstances = self::get_ocinstances();
        $key = array_search(true, array_column($ocinstances, 'isdefault'));
        if ($ocinstances[$key]->id === $ocinstanceid) {
            return get_config('tool_opencast', 'apiurl');
        } else {
            return get_config('tool_opencast', 'apiurl_' . $ocinstanceid);
        }
    }

    /**
     * Return the settings of an Opencast instance.
     * @param int $ocinstanceid
     * @return mixed
     */
    public static function get_ocinstance($ocinstanceid) {
        $ocinstances = self::get_ocinstances();
        $key = array_search($ocinstanceid, array_column($ocinstances, 'id'));
        return $ocinstances[$key];
    }

    /**
     * Returns the default Opencast instance.
     * @return mixed|null
     */
    public static function get_default_ocinstance() {
        $ocinstances = self::get_ocinstances();
        $key = array_search(true, array_column($ocinstances, 'isdefault'));
        if ($key !== false) {
            return $ocinstances[$key];
        }
        return null;
    }

    /**
     * Returns all available Opencast instances.
     * @return mixed
     * @throws \dml_exception
     */
    public static function get_ocinstances() {
        return json_decode(get_config('tool_opencast', 'ocinstances'));
    }

    /**
     * Returns the number of available Opencast instances.
     * @return int|void
     * @throws \dml_exception
     */
    public static function num_ocinstances() {
        return count(self::get_ocinstances());
    }
}
