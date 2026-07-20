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
use tool_opencast\local\jwt_service;

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

    $newversion = 2023030100;
    if ($oldversion < $newversion) {
        if (remove_default_opencast_instance_settings_without_id() === false) {
            return false;
        }

        // Opencast savepoint reached.
        upgrade_plugin_savepoint(true, $newversion, 'tool', 'opencast');
    }

    // In this upgrade, we need to switch the url of existing Opencast H5P and HVP records.
    if ($oldversion < 2024111108) {
        // Up until now, both och5pcore and och5p only support default opencast instance!
        $defaultocinstanceid = settings_api::get_default_ocinstance()->id;
        $defaultapiurl = settings_api::get_apiurl($defaultocinstanceid);
        change_h5p_opencast_content_urls_with_proxy($defaultapiurl);
        change_hvp_opencast_content_urls_with_proxy($defaultapiurl);

        upgrade_plugin_savepoint(true, 2024111108, 'tool', 'opencast');
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

/**
 * Changes the path url of the Opencast H5P (Core) Interactive Video contents with proxy video serving.
 * @param string $defaultapiurl The default Opencast API Url.
 * @return void
 * @throws coding_exception
 */
function change_h5p_opencast_content_urls_with_proxy(string $defaultapiurl): void {
    global $DB;
    $params = ['machinename' => 'H5P.InteractiveVideo'];
    $records = get_h5p_hvp_interactive_video_entries('h5p_libraries', $params, 'h5p', 'mainlibraryid');
    if ($records) {
        foreach ($records as $record) {
            // First we try to get the course id out of stored file.
            $fs = get_file_storage();
            $file = $fs->get_file_by_hash($record->pathnamehash);
            if (!$file || !$file->get_contextid()) {
                continue;
            }
            $context = context::instance_by_id($file->get_contextid());
            $coursecontext = $context->get_course_context(false);
            if (!$coursecontext) {
                continue;
            }
            $courseid = (int) $coursecontext->instanceid;

            // Second, we get the jsoncontent and filtered right.
            $jsoncontent = null;
            if ($record?->jsoncontent) {
                $jsoncontent = json_decode($record->jsoncontent, true);
            }
            $filtered = null;
            if ($record?->filtered) {
                $filtered = json_decode($record->filtered, true);
            }

            if ($jsoncontent) {
                replace_interactive_video_content_paths($jsoncontent, $courseid, $defaultapiurl);

                $record->jsoncontent = json_encode($jsoncontent);
            }

            if ($filtered) {
                replace_interactive_video_content_paths($filtered, $courseid, $defaultapiurl);

                $record->filtered = json_encode($filtered);
            }

            $DB->update_record('h5p', $record);
        }
    }
}

/**
 * Changes the path url of the Opencast HVP (Plugin) Interactive Video contents with proxy video serving.
 * @param string $defaultapiurl The default Opencast API Url.
 * @return void
 * @throws coding_exception
 */
function change_hvp_opencast_content_urls_with_proxy(string $defaultapiurl): void {
    global $DB;
    $params = ['machine_name' => 'H5P.InteractiveVideo'];
    $records = get_h5p_hvp_interactive_video_entries('hvp_libraries', $params, 'hvp', 'main_library_id');
    if ($records) {
        foreach ($records as $record) {
            $courseid = (int) $record->course;

            $jsoncontent = null;
            if ($record?->json_content) {
                $jsoncontent = json_decode($record->json_content, true);
            }

            $filtered = null;
            if ($record?->filtered) {
                $filtered = json_decode($record->filtered, true);
            }

            if ($jsoncontent) {
                replace_interactive_video_content_paths($jsoncontent, $courseid, $defaultapiurl);

                $record->json_content = json_encode($jsoncontent);
            }

            if ($filtered) {
                replace_interactive_video_content_paths($filtered, $courseid, $defaultapiurl);

                $record->filtered = json_encode($filtered);
            }

            $DB->update_record('hvp', $record);
        }
    }
}

/**
 * Gets the H5P / HVP Interactive Video content records from database, based on Interactive Video libraray id.
 * @param string $libtablename The H5P library table name.
 * @param array $libparams The params to look for in the H5P library table.
 * @param string $maintablename The main H5P/HVP table name.
 * @param string $mainlibidcolname The library id column in the main table.
 * @return null|array The records based on librarby id(s)
 */
function get_h5p_hvp_interactive_video_entries(
    string $libtablename,
    array $libparams,
    string $maintablename,
    string $mainlibidcolname
): ?array {
    global $DB;
    if (!$DB->record_exists($libtablename, $libparams)) {
        return null;
    }
    $interactivevideolibs = $DB->get_records($libtablename, $libparams);
    if (!$interactivevideolibs) {
        return null;
    }

    $libraryids = array_column($interactivevideolibs, 'id');

    [$insql, $params] = $DB->get_in_or_equal(
        $libraryids,
        SQL_PARAMS_NAMED
    );

    $sql = "SELECT * FROM {" . $maintablename . "} WHERE $mainlibidcolname $insql";
    if (!$DB->record_exists_sql($sql, $params)) {
        return null;
    }

    return $DB->get_records_sql($sql, $params);
}

/**
 * Generates Opencast Proxy Video serving url.
 * @param string $rawurl The already stored raw url (usaully the static opencast video url).
 * @param int $courseid THe course id.
 * @param string $defaultapiurl The default Opencast API Url.
 * @return null|string The proxy url or null if the url is not an Opencast url.
 */
function generate_opencast_proxy_url(string $rawurl, int $courseid, string $defaultapiurl): ?string {
    $parsedurl = parse_url($rawurl);
    $baseurl = $parsedurl['scheme'] . '://' . $parsedurl['host'];
    // It is not an Opencast!
    if ($baseurl !== $defaultapiurl) {
        return null;
    }
    $path = $parsedurl['path'];
    $eventidregx = '/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i';
    $identifier = null;
    if (preg_match_all($eventidregx, $path, $matches)) {
        $identifier = $matches[0][0];
    }
    if (!empty($identifier) && !empty($courseid)) {
        $params = [
            'url' => $rawurl,
            'identifier' => $identifier,
            'courseid' => $courseid,
        ];
        $url = new \moodle_url(jwt_service::VIDEO_PROXY_URL_PATH, $params);
        return $url->out(false);
    }

    return null;
}


/**
 * Recursively replaces Opencast video file paths in interactive video content
 * with proxy URLs when the path belongs to the default Opencast instance.
 *
 * @param array $data The Interactive Video content structure to update.
 * @param int $courseid The Moodle course id associated with the content.
 * @param string $defaultapiurl The default Opencast API base URL.
 * @return void
 */
function replace_interactive_video_content_paths(array &$data, int $courseid, string $defaultapiurl): void {
    array_walk_recursive_with_parent($data, function (&$item, $key, &$parent) use ($courseid, $defaultapiurl) {
        // We only care about "path".
        if ($key !== 'path') {
            return;
        }

        // Check if sibling "mime" exists and is a video.
        if (
            isset($parent['mime']) &&
            str_starts_with($parent['mime'], 'video/')
        ) {
            $proxyurl = generate_opencast_proxy_url($item, $courseid, $defaultapiurl);
            if (!empty($proxyurl)) {
                $item = $proxyurl;
            }
        }
    });
}


/**
 * Walks an array recursively while also exposing each item's parent array.
 *
 * @param array $array The array to traverse.
 * @param callable $callback The callback to invoke for each value. It receives
 *      the value by reference, the current key, and the parent array.
 * @return void
 */
function array_walk_recursive_with_parent(array &$array, callable $callback): void {
    foreach ($array as $key => &$value) {
        if (is_array($value)) {
            array_walk_recursive_with_parent($value, $callback);
        }
        $callback($value, $key, $array);
    }
}

