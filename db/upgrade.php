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
 * Upgrade.php for tool_opencast.
 *
 * @package    tool_opencast
 * @copyright  2018 Tobias Reischmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute opencast upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_tool_opencast_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2018013002) {

        // Define table tool_opencast_series to be created.
        $table = new xmldb_table('tool_opencast_series');

        // Adding fields to table tool_opencast_series.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('series', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table tool_opencast_series.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_course', XMLDB_KEY_FOREIGN_UNIQUE, array('courseid'), 'course', array('id'));

        // Conditionally launch create table for tool_opencast_series.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Opencast savepoint reached.
        upgrade_plugin_savepoint(true, 2018013002, 'error', 'opencast');
    }

    if ($oldversion < 2021062502) {
        // Create default instance.
        $ocinstance = new \stdClass();
        $ocinstance->id = 1;
        $ocinstance->name = 'Default';
        $ocinstance->isvisible = true;
        $ocinstance->isdefault = true;
        set_config('ocinstances', json_encode(array($ocinstance)), 'tool_opencast');

        // Add new field to series table.
        $table = new xmldb_table('tool_opencast_series');
        $field = new xmldb_field('ocinstanceid', XMLDB_TYPE_INTEGER, '10');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Use default series for current series.
        $DB->set_field('tool_opencast_series', 'ocinstanceid', 1);

        // Set instance field to not null.
        $field = new xmldb_field('ocinstanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $dbman->change_field_notnull($table, $field);

        // Opencast savepoint reached.
        upgrade_plugin_savepoint(true, 2021062502, 'tool', 'opencast');
    }

    return true;
}
