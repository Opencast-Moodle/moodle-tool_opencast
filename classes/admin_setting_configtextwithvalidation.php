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
 * Admin setting class for OC instances setting.
 *
 * @package    tool_opencast
 * @copyright  2021 Tamara Gunkel, University of Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_opencast;

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
        $failed = parent::write_setting($data);
        if (!$failed) {
            // Propagate changes to plugins.
            $adminroot = admin_get_root();
            $toolsettings = $adminroot->locate('tool_opencast');
            foreach ($toolsettings->get_children() as $child) {
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
                    $data = $setting->get_setting();
                    if (is_null($data)) {
                        $data = $setting->get_defaultsetting();
                        $setting->write_setting($data);
                    }
                }
            }
            return '';
        }
        return $failed;
    }
}
