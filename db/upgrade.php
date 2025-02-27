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

use tool_opencast\local\settings_api;

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
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_course', XMLDB_KEY_FOREIGN_UNIQUE, ['courseid'], 'course', ['id']);

        // Conditionally launch create table for tool_opencast_series.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Opencast savepoint reached.
        upgrade_plugin_savepoint(true, 2018013002, 'error', 'opencast');
    }

    if ($oldversion < 2021091200) {
        // Architecture change: Multiple series per course.
        $table = new xmldb_table('tool_opencast_series');
        $field = new xmldb_field('isdefault', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1, 'series');

        // Conditionally launch add field default.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Remove unique key.
        $dbman->drop_key($table, new xmldb_key('fk_course', XMLDB_KEY_FOREIGN_UNIQUE, ['courseid'], 'course', ['id']));

        // Check that each course has only exactly one series.
        $sql = "SELECT courseid, COUNT(id) FROM {tool_opencast_series} GROUP BY courseid ";
        $courseentries = $DB->get_records_sql($sql);
        foreach ($courseentries as $entry) {
            if (intval($entry->count) > 1) {
                // This should not happen. But if it does, simply select the first one as default.
                // 1. Set all to 0.
                $DB->set_field('tool_opencast_series', 'isdefault', 0, ['courseid' => $entry->courseid]);

                // 2. Set one to 1.
                $records = $DB->get_records('tool_opencast_series');
                $firstrecord = array_values($records)[0];
                $firstrecord->isdefault = 1;
                $DB->update_record('tool_opencast_series', $firstrecord);
            }
        }

        // Architecture change: Multiple OC instances.
        // Create default instance.
        $ocinstance = new \stdClass();
        $ocinstance->id = 1;
        $ocinstance->name = 'Default';
        $ocinstance->isvisible = true;
        $ocinstance->isdefault = true;
        settings_api::set_ocinstances_to_ocinstance($ocinstance);

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

        // Add new foreign key and unique constraint.
        $table->add_key('fk_course', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('unq_course_series_ocinstance', XMLDB_KEY_UNIQUE, ['courseid', 'ocinstanceid', 'series']);

        // Opencast savepoint reached.
        upgrade_plugin_savepoint(true, 2021091200, 'tool', 'opencast');
    }

    if ($oldversion < 2021102700) {
        $columns = $DB->get_columns('tool_opencast_series');
        $isdefaultfield = $columns['isdefault'];

        if ($isdefaultfield->__get("type") == "bytea") {
            // Changing type of field isdefault on table tool_opencast_series to int.
            $table = new xmldb_table('tool_opencast_series');
            $oldfield = new xmldb_field('isdefault', XMLDB_TYPE_BINARY);
            $dbman->rename_field($table, $oldfield, 'isdefault_old');

            $newfield = new xmldb_field('isdefault', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0, 'series');
            $dbman->add_field($table, $newfield);

            // Loop through records because casting in sql depends on database type.
            foreach ($DB->get_records('tool_opencast_series') as $record) {
                if ($record->isdefault_old) {
                    $record->isdefault = 1;
                    $DB->update_record('tool_opencast_series', $record);
                }
            }

            // Launch change of type for field isdefault.
            $dbman->drop_field($table, new xmldb_field('isdefault_old'));
        }

        // Opencast savepoint reached.
        upgrade_plugin_savepoint(true, 2021102700, 'tool', 'opencast');
    }

    if ($oldversion < 2023030100) {
        if (remove_default_opencast_instance_settings_without_id() === false) {
            return false;
        }

        // Opencast savepoint reached.
        upgrade_plugin_savepoint(true, $newversion, 'tool', 'opencast');
    }

    $newversion = 2025020600;
    if ($oldversion < $newversion) {
        $DB->execute("UPDATE {config_plugins} SET plugin='tool_opencast' WHERE plugin = 'block_opencast' AND name != 'version' AND name NOT LIKE '%limitvideos%'");
    }

    return true;
}

/**
 * Removes the settings of the default Opencast instance without an id in their names
 * from the database and adds those settings with the corresponding id in their names
 * and their previous values to the database again.
 *
 * @return bool
 * Returns true, if this update of the database was successful, and false otherwise.
 */
function remove_default_opencast_instance_settings_without_id(): bool {
    $helpersettingsname = 'apiurl';
    $pluginname = 'tool_opencast';

    // Check, if settings without an id in their names exist (for the default Opencast instance).
    $foundoldsetting = get_config($pluginname, $helpersettingsname);
    if ($foundoldsetting === false) {
        return true;
    }

    // Fetch the default Opencast instance, if any.
    $defaultocinstance = settings_api::get_default_ocinstance();
    if ($defaultocinstance === null) {
        return true;
    }

    $defaultocinstanceid = $defaultocinstance->id;

    try {
        replace_default_opencast_instance_setting_without_id($defaultocinstanceid, 'apiurl');
        replace_default_opencast_instance_setting_without_id($defaultocinstanceid, 'apiusername');
        replace_default_opencast_instance_setting_without_id($defaultocinstanceid, 'apipassword');
        replace_default_opencast_instance_setting_without_id($defaultocinstanceid, 'lticonsumerkey');
        replace_default_opencast_instance_setting_without_id($defaultocinstanceid, 'lticonsumersecret');
        replace_default_opencast_instance_setting_without_id($defaultocinstanceid, 'apitimeout');
        replace_default_opencast_instance_setting_without_id($defaultocinstanceid, 'apiconnecttimeout');
    } catch (\dml_exception $exception) {
        return false;
    }

    return true;
}

/**
 * Removes the passed setting of the default Opencast instance without an id in its name
 * from the database and adds that setting with the passed id in its name
 * and its previous value to the database again.
 *
 * @param int $defaultinstanceid
 * The Opencast instance id of the default Opencast instance.
 *
 * @param string $name
 * The name of the setting to replace (without the Opencast instance id).
 *
 * @throws \dml_exception
 */
function replace_default_opencast_instance_setting_without_id(int $defaultinstanceid,
                                                              string $name): void {
    $pluginname = 'tool_opencast';

    $value = get_config($pluginname, $name);
    if ($value === false) {
        throw new \dml_exception('dmlreadexception');
    }

    if (unset_config($name, $pluginname) === false) {
        throw new \dml_exception('dmlwriteexception');
    }

    set_config($name . '_' . $defaultinstanceid, $value, $pluginname);
}
