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
 * Persistable of seriesmapping
 *
 * @package    tool_opencast
 * @copyright  2018 Tobias Reischmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_opencast;
defined('MOODLE_INTERNAL') || die;

/**
 * Persistable of seriesmapping
 *
 * @package    tool_opencast
 * @copyright  2018 Tobias Reischmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class seriesmapping extends \core\persistent {

    /** Table name for the persistent. */
    const TABLE = 'tool_opencast_series';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {

        return array(
            'id' => array(
                'type' => PARAM_INT,
            ),
            'courseid' => array(
                'type' => PARAM_INT,
            ),
            'series' => array(
                'type' => PARAM_ALPHANUMEXT,
            ),
            'ocinstanceid' => array(
                'type' => PARAM_INT,
                'default' => function(){
                    global $DB;
                    $defaultinstance = $DB->get_record('tool_opencast_oc_instances', array('isdefault' => true));
                    return $defaultinstance->id;
                }
            ),
        );
    }
}