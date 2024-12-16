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

namespace tool_opencast\local;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/lib/filelib.php');

/**
 * Settings API for opencast.
 *
 * This static class is used by the Opencast plugins, to fetch information about the settings of
 * the defined Opencast instances as well as of the plugin tool_opencast itself.
 * An Opencast instance is defined and configured with the admin settings of tool_opencast.
 *
 * @package    tool_opencast
 * @copyright  2022 Matthias Kollenbroich, University of Münster
 * @copyright  2021 Tamara Gunkel <tamara.gunkel@wi.uni-muenster.de>
 * @copyright  2018 Tobias Reischmann <tobias.reischmann@wi.uni-muenster.de>
 * @copyright  2017 Andreas Wagner, SYNERGY LEARNING
 * @author     Andreas Wagner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings_api {

    /**
     * Make this class not instantiable.
     */
    private function __construct() {
    }

    /**
     * Returns the version of the plugin tool_opencast as string
     * or false, if the corresponding config was not found.
     *
     * @return string|bool
     * The requested config as string or false, if the corresponding config was not found.
     *
     * @throws \dml_exception
     */
    public static function get_plugin_version() {
        return get_config('tool_opencast', 'version');
    }

    /**
     * Returns the api url of an Opencast instance as string
     * or false, if the corresponding config was not found.
     *
     * @param int $ocinstanceid
     * The id of the Opencast instance, for that the config is retrieved.
     *
     * @return string|bool
     * The requested config as string or false, if the corresponding config was not found.
     *
     * @throws \dml_exception
     */
    public static function get_apiurl(int $ocinstanceid) {
        return get_config('tool_opencast', 'apiurl_' . $ocinstanceid);
    }

    /**
     * Returns the api username of an Opencast instance as string
     * or false, if the corresponding config was not found.
     *
     * @param int $ocinstanceid
     * The id of the Opencast instance, for that the config is retrieved.
     *
     * @return string|bool
     * The requested config as string or false, if the corresponding config was not found.
     *
     * @throws \dml_exception
     */
    public static function get_apiusername(int $ocinstanceid) {
        return get_config('tool_opencast', 'apiusername_' . $ocinstanceid);
    }

    /**
     * Returns the api password of an Opencast instance as string
     * or false, if the corresponding config was not found.
     *
     * @param int $ocinstanceid
     * The id of the Opencast instance, for that the config is retrieved.
     *
     * @return string|bool
     * The requested config as string or false, if the corresponding config was not found.
     *
     * @throws \dml_exception
     */
    public static function get_apipassword(int $ocinstanceid) {
        return get_config('tool_opencast', 'apipassword_' . $ocinstanceid);
    }

    /**
     * Returns the api timeout of an Opencast instance as string
     * or false, if the corresponding config was not found.
     *
     * @param int $ocinstanceid
     * The id of the Opencast instance, for that the config is retrieved.
     *
     * @return string|bool
     * The requested config as string or false, if the corresponding config was not found.
     *
     * @throws \dml_exception
     */
    public static function get_apitimeout(int $ocinstanceid) {
        return get_config('tool_opencast', 'apitimeout_' . $ocinstanceid);
    }

    /**
     * Returns the api connecttimeout of an Opencast instance as string
     * or false, if the corresponding config was not found.
     *
     * @param int $ocinstanceid
     * The id of the Opencast instance, for that the config is retrieved.
     *
     * @return string|bool
     * The requested config as string or false, if the corresponding config was not found.
     *
     * @throws \dml_exception
     */
    public static function get_apiconnecttimeout(int $ocinstanceid) {
        return get_config('tool_opencast', 'apiconnecttimeout_' . $ocinstanceid);
    }

    /**
     * Returns the lticonsumerkey of an Opencast instance as string
     * or false, if the corresponding config was not found.
     *
     * @param int $ocinstanceid
     * The id of the Opencast instance, for that the config is retrieved.
     *
     * @return string|bool
     * The requested config as string or false, if the corresponding config was not found.
     *
     * @throws \dml_exception
     */
    public static function get_lticonsumerkey(int $ocinstanceid) {
        return get_config('tool_opencast', 'lticonsumerkey_' . $ocinstanceid);
    }

    /**
     * Returns the lticonsumersecret of an Opencast instance as string
     * or false, if the corresponding config was not found.
     *
     * @param int $ocinstanceid
     * The id of the Opencast instance, for that the config is retrieved.
     *
     * @return string|bool
     * The requested config as string or false, if the corresponding config was not found.
     *
     * @throws \dml_exception
     */
    public static function get_lticonsumersecret(int $ocinstanceid) {
        return get_config('tool_opencast', 'lticonsumersecret_' . $ocinstanceid);
    }

    /**
     * Get the maintenance mode for a specific Opencast instance.
     *
     * This function retrieves the maintenance mode setting for the given Opencast instance.
     *
     * @param int $ocinstanceid The ID of the Opencast instance to check the maintenance mode for.
     *
     * @return mixed The maintenance mode setting for the specified Opencast instance.
     *               Returns false if the setting is not found.
     *
     * @throws \dml_exception If there's an error retrieving the configuration.
     */
    public static function get_maintenancemode(int $ocinstanceid) {
        return get_config('tool_opencast', maintenance_class::get_mode_full_config_id($ocinstanceid));
    }

    /**
     * Get the maintenance notification level for a specific Opencast instance.
     *
     * This function retrieves the maintenance notification level setting for the given Opencast instance.
     *
     * @param int $ocinstanceid The ID of the Opencast instance to check the maintenance notification level for.
     *
     * @return mixed The maintenance notification level setting for the specified Opencast instance.
     *               Returns false if the setting is not found.
     *
     * @throws \dml_exception If there's an error retrieving the configuration.
     */
    public static function get_maintenancenotiflevel(int $ocinstanceid) {
        return get_config('tool_opencast', maintenance_class::get_notificationlevel_full_config_id($ocinstanceid));
    }

    /**
     * Get the maintenance message for a specific Opencast instance.
     *
     * This function retrieves the maintenance message setting for the given Opencast instance.
     *
     * @param int $ocinstanceid The ID of the Opencast instance to retrieve the maintenance message for.
     *
     * @return mixed The maintenance message setting for the specified Opencast instance.
     *               Returns false if the setting is not found.
     *
     * @throws \dml_exception If there's an error retrieving the configuration.
     */
    public static function get_maintenancemessage(int $ocinstanceid) {
        return get_config('tool_opencast', maintenance_class::get_message_full_config_id($ocinstanceid));
    }

    /**
     * Get the maintenance start date json string for a specific Opencast instance.
     *
     * This function retrieves the maintenance start date json string setting for the given Opencast instance.
     *
     * @param int $ocinstanceid The ID of the Opencast instance to retrieve the maintenance start date json string for.
     *
     * @return mixed The maintenance start date json string setting for the specified Opencast instance.
     *               Returns false if the setting is not found.
     *
     * @throws \dml_exception If there's an error retrieving the configuration.
     */
    public static function get_maintenancestartdate(int $ocinstanceid) {
        return get_config('tool_opencast', maintenance_class::get_startdate_full_config_id($ocinstanceid));
    }

    /**
     * Get the maintenance end date json string for a specific Opencast instance.
     *
     * This function retrieves the maintenance end date json string setting for the given Opencast instance.
     *
     * @param int $ocinstanceid The ID of the Opencast instance to retrieve the maintenance end date json string for.
     *
     * @return mixed The maintenance end date json string setting for the specified Opencast instance.
     *               Returns false if the setting is not found.
     *
     * @throws \dml_exception If there's an error retrieving the configuration.
     */
    public static function get_maintenancenddate(int $ocinstanceid) {
        return get_config('tool_opencast', maintenance_class::get_enddate_full_config_id($ocinstanceid));
    }

    /**
     * Return the Opencast instance for the passed Opencast instance id, if any.
     * If no Opencast instance with this id is configured, null is returned.
     *
     * @param int $ocinstanceid
     * The id of the requested Opencast instance.
     *
     * @return opencast_instance|null
     * The corresponding Opencast instance or null.
     */
    public static function get_ocinstance(int $ocinstanceid): ?opencast_instance {
        $ocinstances = self::get_ocinstances();

        foreach ($ocinstances as $ocinstance) {
            if (intval($ocinstance->id) === intval($ocinstanceid)) {
                return $ocinstance;
            }
        }

        return null;
    }

    /**
     * Returns the default Opencast instance, if any.
     * If no default Opencast instance is configured, null is returned.
     *
     * @return opencast_instance|null
     * The corresponding Opencast instance or null.
     */
    public static function get_default_ocinstance(): ?opencast_instance {
        $ocinstances = self::get_ocinstances();

        foreach ($ocinstances as $ocinstance) {
            if (boolval($ocinstance->isdefault) === true) {
                return $ocinstance;
            }
        }

        return null;
    }

    /**
     * Returns all configured Opencast instances as array.
     *
     * This array contains instances of the class opencast_instance only.
     *
     * @return array
     * All configured Opencast instances as array.
     */
    public static function get_ocinstances(): array {
        try {
            $ocinstancesconfig = get_config('tool_opencast', 'ocinstances');
        } catch (\dml_exception $exception) {
            return [];
        }

        $dynamicocinstances = json_decode($ocinstancesconfig);

        $ocinstances = [];
        foreach ($dynamicocinstances as $dynamicocinstance) {
            $ocinstances[] = new opencast_instance($dynamicocinstance);
        }

        return $ocinstances;
    }

    /**
     * Sets all configured Opencast instances to the passed Opencast instance,
     * namely, the passed Opencast instance will be the only configured Opencast instance
     * afterwards.
     *
     * @param \stdClass $dynamicocinstance
     * The Opencast instance, to that all configured Opencast instances are set to.
     */
    public static function set_ocinstances_to_ocinstance($dynamicocinstance): void {
        set_config('ocinstances', json_encode([$dynamicocinstance]), 'tool_opencast');
    }

    /**
     * Returns the number of configured Opencast instances.
     *
     * @return int
     * The number of configured Opencast instances.
     */
    public static function num_ocinstances(): int {
        return count(self::get_ocinstances());
    }
}
