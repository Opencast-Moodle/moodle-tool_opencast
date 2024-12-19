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

namespace tool_opencast\settings;

use admin_category;
use core_plugin_manager;

/**
 * Admin setting class for OC instances setting.
 *
 * @package    tool_opencast
 * @copyright  2021 Tamara Gunkel, University of Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_configtextwithvalidation extends \admin_setting_configtext {

    /** @var string the old value of the this setting. */
    private $oldvalue;

    /** @var string the newly saved value of the this setting. */
    private $newlysavedvalue;

    /**
     * Validate data before storage
     * @param mixed $data
     * @return bool|string true if ok string if error found
     */
    public function validate($data) {
        $parentvalidated = parent::validate($data);
        if ($parentvalidated == true) {
            $ocinstances = json_decode($data);
            $isdefault = array_column($ocinstances, 'isdefault');
            if (array_sum($isdefault) !== 1) {
                return get_string('errornumdefaultinstances', 'tool_opencast');
            }
        }
        return $parentvalidated;
    }

    /**
     * Write settings and propagate changes to plugins.
     * @param mixed $data
     * @return bool|\lang_string|mixed|string empty string if successful, otherwise error message
     */
    public function write_setting($data) {
        // Recording the old settings, before writing new settings.
        $this->oldvalue = $this->get_setting();

        $failed = parent::write_setting($data);
        if (!$failed) {

            // Record newly saved settings that pass the validation, to work with later on.
            $this->newlysavedvalue = $this->get_setting();

            // Catching what is saved in terms of instance ids, to avoid propagating deleted ones!
            $newocinstanceids = $this->get_new_instance_ids();

            // Propagate changes to plugins.
            $adminroot = admin_get_root();
            $toolsettings = $adminroot->locate('tool_opencast');
            foreach ($toolsettings->get_children() as $child) {
                // Making sure that setting is current.
                if (!$this->is_setting_current($child->name, $newocinstanceids)) {
                    continue;
                }

                if (substr($child->name, 0, 28) == 'tool_opencast_configuration_') {
                    foreach ($child->settings as $name => $setting) {
                        $data = $setting->get_setting();
                        if (is_null($data)) {
                            $data = $setting->get_defaultsetting();
                            $setting->write_setting($data);
                        }
                    }
                }
            }

            // Block settings.
            if (core_plugin_manager::instance()->get_plugin_info('block_opencast')) {
                $blocksettings = $adminroot->locate('block_opencast');
                foreach ($blocksettings->get_children() as $category) {
                    // Making sure that category is current.
                    if (!$this->is_setting_current($category->name, $newocinstanceids)) {
                        continue;
                    }
                    if ($category instanceof admin_category) {
                        foreach ($category->get_children() as $child) {
                            foreach ($child->settings as $name => $setting) {
                                $data = $setting->get_setting();
                                if (is_null($data)) {
                                    $data = $setting->get_defaultsetting();
                                    if (!is_null($data)) {
                                        $setting->write_setting($data);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Activity settings.
            if (core_plugin_manager::instance()->get_plugin_info('mod_opencast')) {
                $modsettings = $adminroot->locate('modsettingopencast');
                foreach ($modsettings->settings as $name => $setting) {
                    // Making sure that setting is current.
                    if (!$this->is_setting_current($name, $newocinstanceids)) {
                        continue;
                    }
                    $data = $setting->get_setting();
                    if (is_null($data)) {
                        $data = $setting->get_defaultsetting();
                        $setting->write_setting($data);
                    }
                }
            }

            // Perform setting deletions for those instances that do not exist!
            // Since the main purpose of this function is already fulfilled, therefore we don't need to catch any errors during
            // deletion, in order to prevent further confusion.
            $this->perform_ocinstance_deletion();

            return '';
        }
        return $failed;
    }

    /**
     * Extracts the IDs of deleted Opencast instances.
     *
     * This method compares the old and new values of Opencast instance IDs and identifies
     * which IDs have been deleted. It returns an array of deleted Opencast instance IDs.
     *
     * @return array An array of deleted Opencast instance IDs.
     */
    private function extract_deleted_ocinstances(): array {
        $deletedocinstanceids = [];
        if (!empty($this->oldvalue)) {
            try {
                $oldocinstanceids = array_column(json_decode($this->oldvalue), 'id');

                $newocinstanceids = $this->get_new_instance_ids();

                // If there are ids in the old settings, but not in the new, then it mean those ids are being deleted.
                $deletedocinstanceids = array_diff($oldocinstanceids, $newocinstanceids);
            } catch (\moodle_exception $e) {
                // Something went wrong during all the decoding and differing arrays, then return empty array,
                // to avoid unwanted deletions.
                $deletedocinstanceids = [];
            }
        }
        return $deletedocinstanceids;
    }

    /**
     * Checks if the given setting name corresponds to a current instance ID.
     *
     * This method extracts the instance ID from the setting name using a regular expression.
     * It then checks if the extracted instance ID is present in the provided list of current instance IDs.
     *
     * @param string $settingname The name of the setting to check.
     * @param array $currentinstanceids An array of current instance IDs to validate against.
     * @return bool Returns true if the setting name corresponds to a current instance ID, false otherwise.
     */
    private function is_setting_current(string $settingname, array $currentinstanceids): bool {
        if (preg_match('#_(\d+)$#', $settingname, $matches)) {
            $settinginstanceid = intval($matches[1]);
            if ($settinginstanceid && !in_array($settinginstanceid, $currentinstanceids)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Retrieves the new instance IDs from the newly saved value.
     *
     * This method attempts to decode the JSON-encoded newly saved value and extract the 'id' fields
     * from it. If the newly saved value is empty or an error occurs during decoding, an empty array
     * is returned.
     *
     * @return array An array of new instance IDs.
     */
    private function get_new_instance_ids(): array {
        $newocinstanceids = [];
        try {
            if (!empty($this->newlysavedvalue)) {
                $newocinstanceids = array_column(json_decode($this->newlysavedvalue), 'id');
            }
        } catch (\Throwable $th) {
            $newocinstanceids = [];
        }
        return $newocinstanceids;
    }

    /**
     * Deletes the Opencast instance configurations from the database.
     *
     * This method performs the deletion of Opencast instance configurations when instances are removed.
     * It ensures that the deletion process is only executed when there are actual deletions to prevent
     * unnecessary processing.
     *
     * @return void
     */
    private function perform_ocinstance_deletion(): void {
        global $DB;

        // If there are no old values, we don't need to perform the deletion, as there are no instances to delete.
        if (empty($this->oldvalue)) {
            return;
        }

        // We check and extract deleted instances to ensure that the following laborious procedure
        // is only executed when deletions occur. This prevents unnecessary processing for other operations.

        $deletedocinstanceids = $this->extract_deleted_ocinstances();

        // Nothing to delete, we return the process.
        if (empty($deletedocinstanceids)) {
            return;
        }

        // Setting plugins to loop through and find configs to delete.
        $plugins = [
            'tool_opencast',
            'block_opencast',
            'mod_opencast',
            'filter_opencast',
        ];

        // Preparing the (where clause) query for select.
        $select = $DB->sql_equal('plugin', ':plugin') . " AND " . $DB->sql_like('name', ':confignamelike');

        // First level of looping is to go through the deleted instances.
        foreach ($deletedocinstanceids as $id) {

            // In here we need to set the params value and determine the config name based on the ocinstance id.
            $params = [
                'confignamelike' => '%_' . $id,
            ];

            // Second level of looping is to go through the plugins.
            foreach ($plugins as $plugin) {

                // In here we can determine the plugin name.
                $params['plugin'] = $plugin;

                // Now that the information to get the plugin configuration related to deleted instance is ready,
                // we perform the select records query.
                $selectedpluginconfigs = $DB->get_records_select('config_plugins', $select, $params, '', 'name');

                // If we found something, we proceed to the deletion by using "unset_config".
                if (!empty($selectedpluginconfigs)) {
                    foreach ($selectedpluginconfigs as $configrecord) {
                        if (!empty($configrecord->name)) {
                            // Very important to use "unset_config", because of taking care of cached configuration as well.
                            unset_config($configrecord->name, $plugin);
                        }
                    }
                }
            }
        }
    }
}
