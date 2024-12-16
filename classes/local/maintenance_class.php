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

/**
 * Maintenance Helper class
 *
 * It is the brain of the maintenance system for Opencast Moodle plugins.
 *
 * @package    tool_opencast
 * @copyright  2024 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class maintenance_class {

    /** @var int Disable mode flag id. */
    const MODE_DISABLE = 0;

    /** @var int Read-only mode flag id. */
    const MODE_READONLY = 1;

    /** @var int Enable mode flag id. */
    const MODE_ENABLE = 2;

    /** @var string Mode config id. */
    const CONFIG_ID_MODE = 'maintenancemode';

    /** @var string Nofitication level config id. */
    const CONFIG_ID_NOTIFLEVEL = 'maintenancemode_notification_level';

    /** @var string Message config id. */
    const CONFIG_ID_MESSAGE = 'maintenancemode_message';

    /** @var string Start date config id. */
    const CONFIG_ID_STARTDATE = 'maintenancemode_startdate';

    /** @var string End date config id. */
    const CONFIG_ID_ENDDATE = 'maintenancemode_enddate';

    /** @var string The name of the plugin tool_opencast. */
    private const PLUGINNAME = 'tool_opencast';

    /** @var int The maintenance mode value. */
    private int $mode;

    /** @var string The notification level value. */
    private string $notiflevel;

    /** @var string The maintenance message value. */
    private string $message;

    /** @var \stdClass The start date value. */
    private \stdClass $startdate;

    /** @var \stdClass The end date value. */
    private \stdClass $enddate;

    /** @var int The id of the Opencast instance. */
    private int $ocinstanceid;

    /**
     * Constructor.
     *
     * @param int|null $ocinstanceid The id of the Opencast instance.
     *                               If null, the default Opencast instance will be used.
     */
    public function __construct(?int $ocinstanceid) {
        $this->ocinstanceid = $ocinstanceid ?? settings_api::get_default_ocinstance();
        $this->init();
    }

    /**
     * Initializes the requirements for the class.
     * This function contains all the setters of the class.
     *
     * @return void
     */
    private function init() {
        $this->set_mode();
        $this->set_notiflevel();
        $this->set_message();
        $this->set_startdate();
        $this->set_enddate();
    }

    /**
     * Sets the value of the maintenance mode.
     * Pulls the value from configs or sets a default value if not found.
     *
     * @return void
     */
    private function set_mode() {
        $this->mode = (int) (settings_api::get_maintenancemode($this->ocinstanceid) ?? self::MODE_DISABLE);
    }

    /**
     * Sets the value of the maintenance notification level.
     * Pulls the value from configs or sets a default value if not found.
     *
     * @return void
     */
    private function set_notiflevel() {
        $this->notiflevel =
            settings_api::get_maintenancenotiflevel($this->ocinstanceid) ?? \core\output\notification::NOTIFY_WARNING;
    }

    /**
     * Sets the value of the maintenance message.
     * Pulls the value from configs or sets a default value if not found.
     *
     * @return void
     */
    private function set_message() {
        $this->message = (string) settings_api::get_maintenancemessage($this->ocinstanceid) ?? '';
    }

    /**
     * Sets the value of the maintenance start date.
     * Pulls the value from configs or sets a default value if not found.
     *
     * @return void
     */
    private function set_startdate() {
        $startdate = new \stdClass();
        $configstr = settings_api::get_maintenancestartdate($this->ocinstanceid) ?? null;
        if (!empty($configstr)) {
            $startdate = json_decode($configstr);
        }
        $this->startdate = $startdate;
    }

    /**
     * Sets the value of the maintenance end date.
     * Pulls the value from configs or sets a default value if not found.
     *
     * @return void
     */
    private function set_enddate() {
        $enddate = new \stdClass();
        $configstr = settings_api::get_maintenancenddate($this->ocinstanceid) ?? null;
        if (!empty($configstr)) {
            $enddate = json_decode($configstr);
        }
        $this->enddate = $enddate;
    }

    /**
     * Retrieves maintenance mode value.
     *
     * @return int maintenance mode value
     */
    public function get_mode() {
        return $this->mode;
    }

    /**
     * Retrieves maintenance notification level value.
     *
     * @return string maintenance notification level value
     */
    public function get_notiflevel() {
        return $this->notiflevel;
    }

    /**
     * Retrieves maintenance message value.
     *
     * @return string maintenance message value
     */
    public function get_message() {
        return $this->message;
    }

    /**
     * Retrieves maintenance formatted message.
     *
     * @param string $format message format (default FORMAT_HTML)
     *
     * @return string maintenance message value
     */
    public function get_formatted_message($format = FORMAT_HTML) {
        return format_text($this->get_message(), $format);
    }

    /**
     * Retrieves maintenance start date value.
     *
     * @return \stdClass|null maintenance start date value or null if not set.
     */
    public function get_startdate() {
        return $this->startdate;
    }

    /**
     * Retrieves maintenance end date value.
     *
     * @return \stdClass|null maintenance end date value or null if not set.
     */
    public function get_enddate() {
        return $this->enddate;
    }

    /**
     * Checks if the maintenance mode is activated.
     *
     * The function checks the current mode and time range to determine if the maintenance mode is activated.
     * It considers the following conditions:
     *  - If the mode is disabled, it immediately returns false.
     *  - If there is no time range specified, it returns true,
     *      indicating that the maintenance mode is activated until further notice.
     *  - If the current time falls within the specified time range, it returns true.
     *  - If the current time is outside the specified time range, it returns false.
     *
     * @return bool True if maintenance mode is activated, false otherwise.
     */
    public function is_activated() {
        // If disabled, immediately return false.
        if ($this->mode === self::MODE_DISABLE) {
            return false;
        }

        // Decide whether to check for date and time range.

        $hastimerange = isset($this->startdate) && $this->startdate->enabled || isset($this->enddate) && $this->enddate->enabled;

        // If no time range specified, that means it is activated until further notice!
        if (!$hastimerange) {
            return true;
        }

        // Get the now time with correct timezone!
        $nowdatetime = new \DateTime('now', \core_date::get_user_timezone_object());
        $nowdtimestamp = $nowdatetime->getTimestamp();

        // Check if two sides of time range is enabled.
        if ($this->startdate->enabled && $this->enddate->enabled &&
            $nowdtimestamp >= $this->startdate->timestamp && $nowdtimestamp <= $this->enddate->timestamp) {
            return true;
        }

        // If only the start date is enabled, we check whether the current time is after the start date.
        if ($this->startdate->enabled && !$this->enddate->enabled && $nowdtimestamp >= $this->startdate->timestamp) {
            return true;
        }

        // If only the end date is enabled, we check whether the current time is before the end date.
        if (!$this->startdate->enabled && $this->enddate->enabled && $nowdtimestamp <= $this->enddate->timestamp) {
            return true;
        }

        // If none of the above conditions are met, then it is not activated!
        return false;
    }

    /**
     * Evaluates whether the access to resources is possible.
     *
     * This function is meant to be used either in decorated proxies or in the top level methods before performing call to Opencast.
     * It considers the following conditions:
     *  - If the maintenance is activated, if not everything is accessible.
     *  - Checks if the mode is Read-Only and the method that has been called is eligible to perform the operation.
     *
     * @param string $method The name of the method that is being called
     *
     * @return bool True if access is granted, false otherwise in case conditions are not satisfied.
     */
    public function can_access(string $method) {
        // Of course, if deactivated, we allow access.
        if (!$this->is_activated()) {
            return true;
        }

        // Landing here means, it is activated, and we now have to check for the Read-Only mode.
        // Read-Only mode means, only to perform methods that have word "get" in their name.
        if ($this->mode === self::MODE_READONLY && strpos(strtolower($method), 'get') !== false) {
            return true;
        }

        // In any case. we return false, meaning it is not accessible.
        return false;
    }

    /**
     * Decides how to bounce the access restriction.
     *
     * This function is meant to be used in the top level methods before performing call to Opencast.
     *
     * It simply checks the referer and/or initiator url path against the whitelist and/or blacklist in web calls,
     * and decides what to do with the call, either by redirecting, closing window, throwing exceptions or doing nothing!
     *
     * It considers the following conditions:
     *  - Web calls (checks if the call is from web):
     *    - Admins are not restricted except the admin/cron.php call!
     *    - When the call is in top level course area, it means that the system is loading the plugins, so we let it pass.
     *    - By ajax calls, e.g. from Repository or H5P extension plugins, we simply throw "access_denied_exception" exception.
     *    - In case no condition from above could be met, we either redirect the call back from where it came,
     *      or to the course view page, or simply close the current window in case we could not decide what to do!
     *
     *  - System calls (CLI):
     *    - If a call comes in from system level e.g CLI, we only throw exception.
     *
     * @return void
     * @throws moodle_exception|access_denied_exception
     * Redirection could happen.
     */
    public function decide_access_bounce() {
        global $CFG, $COURSE, $SITE;

        $isbahat = defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING;
        $iscli = defined('CLI_SCRIPT') && CLI_SCRIPT;
        $isphpunit = defined('PHPUNIT_TEST') && PHPUNIT_TEST;

        $wwwroot = $CFG->wwwroot;

        // Hanlde behat wwwroot.
        if ($isbahat && empty($wwwroot)) {
            $wwwroot = $CFG->behat_wwwroot;
        }

        $wwwrootparsed = parse_url($wwwroot);

        // Make sure path exists in wwwrootparsed.
        if (empty($wwwrootparsed) || !isset($wwwrootparsed['path'])) {
            $wwwrootparsed['path'] = '';
        }

        $iswebrequest = isset($_SERVER['REMOTE_ADDR']); // It is a web call when the REMOTE_ADDR is set in $_SERVER!

        // If it is a web request.
        if ($iswebrequest && !$iscli && !$isphpunit) {
            // We have to carefully decide whether to redirect back to the referer or the course page.
            // The reason for this check is to avoid redirecting loops!
            $referer = get_local_referer(false);
            $requestedfrom = parse_url($referer);
            $frompath = !empty($requestedfrom['path']) ? rtrim($requestedfrom['path'], '/') : '';

            $initiator = qualified_me();
            $requesttarget = parse_url($initiator);
            $tagetpath = !empty($requesttarget['path']) ? rtrim($requesttarget['path'], '/') : '';

            $whitelist = [];
            $whitelist[] = $wwwrootparsed['path'];
            $whitelist[] = $wwwrootparsed['path'] . '/course/view.php';
            $whitelist[] = $wwwrootparsed['path'] . '/my';
            $whitelist[] = $wwwrootparsed['path'] . '/my/courses.php';
            $whitelist[] = $wwwrootparsed['path'] . '/course';

            $blacklist = [];
            $blacklist['block_opencast'] = $wwwrootparsed['path'] . '/blocks/opencast'; // Match for block_opencast plugin.
            $blacklist['mod_opencast'] = $wwwrootparsed['path'] . '/mod/opencast'; // Match for mod_opencast plugin.
            $blacklist['modedit'] = $wwwrootparsed['path'] . '/course/modedit'; // Match for mod_opencast plugin.
            $blacklist['repository_opencast'] = $wwwrootparsed['path'] . '/repository'; // Match for repository_opencast plugin.
            $blacklist['admin_cron'] = $wwwrootparsed['path'] . '/admin/cron'; // Match for admin cron.
            $blacklist['admin_cron'] = $wwwrootparsed['path'] . '/local'; // Match for local plugins like och5p and och5pcore.

            // If admin and it is not admin cron page,
            // we let it pass to avoid interrupting any installation, configuration or upgrade.
            if (is_siteadmin() && strpos($frompath, $blacklist['admin_cron']) === false) {
                return ['code' => 404];
            }

            $fromblacklisted = $this->is_path_blacklisted($frompath, $blacklist);
            $targetblacklisted = $this->is_path_blacklisted($tagetpath, $blacklist);

            // Exception: Calls going up to course from blacklist, or nothing to do with blacklist, we do nothing!
            if ((!$fromblacklisted && !$targetblacklisted) || // Outside reaching or loading opencast.
                (in_array($tagetpath, $whitelist) && $fromblacklisted)) { // Going back from plugin to course or somewhere else
                return ['code' => 404];
            }

            // Is ajax or popup, we throw error to make sure the user gets the correct form of notification.
            if (is_in_popup() || (defined('AJAX_SCRIPT') && AJAX_SCRIPT) ||
                isset($initiator['path']) && strpos($requesttarget['path'], 'ajax') !== false) {
                throw new \core\exception\access_denied_exception('maintenance_exception_message', self::PLUGINNAME);
            }

            // We now have to decide whether to redirect back or close the window!

            // If the requested action is from any of the white lists, we will redirect back to the same url.
            if (in_array($frompath, $whitelist) && $targetblacklisted) {
                redirect($referer);
            }

            if ($COURSE && $SITE && $SITE->id != $COURSE->id) {
                $courseurl = new \moodle_url('/course/view.php', ['id' => $COURSE->id]);
                redirect($courseurl);
            }

            // In case any of the above conditions are not met, we close the window.
            close_window(0, true);
        }

        // If it is not yet returned, it has to throw an error, this should also cover requests coming from unit tests.
        throw new \moodle_exception('maintenance_exception_message', self::PLUGINNAME);
    }


    /**
     * Checks if a given path is blacklisted.
     *
     * This function determines whether the provided path matches any of the entries
     * in the blacklist array. It uses a case-sensitive partial string match.
     *
     * @param string $path The path to check against the blacklist.
     * @param array $blacklist An array of blacklisted path patterns.
     *
     * @return bool Returns true if the path matches any blacklist entry, false otherwise.
     */
    private function is_path_blacklisted(string $path, array $blacklist) {
        if (empty($path) || empty($blacklist)) {
            return false;
        }
        $filterred = array_filter($blacklist, function ($v, $k) use ($path) {
            return strpos($path, $v) !== false;
        }, ARRAY_FILTER_USE_BOTH);
        return !empty($filterred);
    }


    /**
     * Displays a notification message based on the maintenance mode settings.
     *
     * This function retrieves the formatted maintenance message, the notification level,
     * and checks if the maintenance mode is activated. It then uses the Moodle Page API
     * to load the 'tool_opencast/maintenance' JavaScript module and passes the necessary
     * parameters to display the notification.
     *
     * @global moodle_page $PAGE The global Moodle page object.
     * @return void
     */
    public function handle_notification_message_display() {
        global $PAGE;

        // Get the formatted message.
        $message = $this->get_formatted_message();
        // Fallback to a default maintenance message if the configured message somehow does not exist.
        if (empty($message)) {
            $message = get_string('maintenance_default_notification_message', self::PLUGINNAME);
        }

        // Get the level.
        $level = $this->get_notiflevel();

        // We notify only when it is activated!
        $notify = $this->is_activated();

        // Make sure that the $PAGE is ready for notifications js module load.
        if ($PAGE) {
            $PAGE->requires->js_call_amd('tool_opencast/maintenance', 'notification', [$message, $level, $notify]);
        }
    }

    /**
     * Updates the maintenance mode status fetched from opencast known as Synchronization!
     *
     * @param int $mode the mode to update
     *
     * @return bool whether the update was successful
     */
    public function update_mode_from_opencast(int $mode) {
        $result = false;
        if (in_array($mode, [self::MODE_DISABLE, self::MODE_ENABLE, self::MODE_READONLY])) {
            $result = set_config(self::CONFIG_ID_MODE . '_' . $this->ocinstanceid, $mode, self::PLUGINNAME);
        }
        return $result;
    }

    // STATIC HELPER METHODS!

    /**
     * Returns mode choice options for the selection.
     *
     * @return array choice options
     */
    public static function get_admin_settings_mode_choices() {
        return [
            self::MODE_DISABLE => get_string('maintenancemode_disable', self::PLUGINNAME),
            self::MODE_READONLY => get_string('maintenancemode_readonly', self::PLUGINNAME),
            self::MODE_ENABLE => get_string('maintenancemode_enable', self::PLUGINNAME),
        ];
    }

    /**
     * Returns notification level choice options for the selection.
     *
     * @return array choice options
     */
    public static function get_admin_settings_notiflevel_choices() {
        return [
            \core\output\notification::NOTIFY_WARNING => get_string('maintenancemode_notiflevel_warning', self::PLUGINNAME),
            \core\output\notification::NOTIFY_ERROR => get_string('maintenancemode_notiflevel_error', self::PLUGINNAME),
            \core\output\notification::NOTIFY_INFO => get_string('maintenancemode_notiflevel_info', self::PLUGINNAME),
            \core\output\notification::NOTIFY_SUCCESS => get_string('maintenancemode_notiflevel_success', self::PLUGINNAME),
        ];
    }

    /**
     * Gets the full configuration id of maintenance mode setting.
     *
     * @param int $ocintanceid the opencast instance id
     * @param bool $withpluginname flag to determine whether to prepend plugin name.
     *
     * @return string the configuration id
     */
    public static function get_mode_full_config_id(int $ocintanceid, bool $withpluginname = false) {
        return self::generate_config_id(self::CONFIG_ID_MODE, $ocintanceid, $withpluginname);
    }

    /**
     * Gets the full configuration id of maintenance notification level setting.
     *
     * @param int $ocintanceid the opencast instance id
     * @param bool $withpluginname flag to determine whether to prepend plugin name.
     *
     * @return string the configuration id
     */
    public static function get_notificationlevel_full_config_id(int $ocintanceid, bool $withpluginname = false) {
        return self::generate_config_id(self::CONFIG_ID_NOTIFLEVEL, $ocintanceid, $withpluginname);
    }

    /**
     * Gets the full configuration id of maintenance message setting.
     *
     * @param int $ocintanceid the opencast instance id
     * @param bool $withpluginname flag to determine whether to prepend plugin name.
     *
     * @return string the configuration id
     */
    public static function get_message_full_config_id(int $ocintanceid, bool $withpluginname = false) {
        return self::generate_config_id(self::CONFIG_ID_MESSAGE, $ocintanceid, $withpluginname);
    }

    /**
     * Gets the full configuration id of maintenance start date setting.
     *
     * @param int $ocintanceid the opencast instance id
     * @param bool $withpluginname flag to determine whether to prepend plugin name.
     *
     * @return string the configuration id
     */
    public static function get_startdate_full_config_id(int $ocintanceid, bool $withpluginname = false) {
        return self::generate_config_id(self::CONFIG_ID_STARTDATE, $ocintanceid, $withpluginname);
    }

    /**
     * Gets the full configuration id of maintenance end date setting.
     *
     * @param int $ocintanceid the opencast instance id
     * @param bool $withpluginname flag to determine whether to prepend plugin name.
     *
     * @return string the configuration id
     */
    public static function get_enddate_full_config_id(int $ocintanceid, bool $withpluginname = false) {
        return self::generate_config_id(self::CONFIG_ID_ENDDATE, $ocintanceid, $withpluginname);
    }

    /**
     * Generates the full configuration id by combining the id and the instance id and if requested appending plugin name.
     *
     * @param string $configid the configuration id
     * @param int $ocintanceid the opencast instance id
     * @param bool $withpluginname flag to determine whether to prepend plugin name.
     *
     * @return string the generated configuration id
     */
    private static function generate_config_id(string $configid, int $ocintanceid, bool $withpluginname) {
        $id = $configid . '_'. $ocintanceid;
        if ($withpluginname) {
            $id = self::PLUGINNAME. '/'. $id;
        }
        return $id;
    }

    /**
     * An auxiliary method to be used by the admin settings in order to validate the start and end dates.
     *
     * It gets the some requirements, prepares and returns the validation callable function.
     *
     * @param string $currentid the current setting id to compare with.
     * @param string $compsettingid the setting id to compare against.
     * @param string $compsettingstringname the string name of the setting to compare against.
     * @param string $compopr the comparision operator. currently used ">=" or "<="
     *
     * @return callable the validation callback function
     */
    public static function maintenance_datetime_validation(string $currentid, string $compsettingid,
                                                            string $compsettingstringname, string $compopr= '>=') {
        // Preparing various parameters to be used in the validation callback function.
        $compsettinglabel = get_string($compsettingstringname, self::PLUGINNAME);
        $errorstrings = [
            '>=' => get_string('maintenancemode_datetime_ge_error', self::PLUGINNAME, $compsettinglabel),
            '<=' => get_string('maintenancemode_datetime_le_error', self::PLUGINNAME, $compsettinglabel),
            'expired' => get_string('maintenancemode_datetime_expired_error', self::PLUGINNAME),
        ];
        $domain = [
            'startdate' => strpos($currentid, self::CONFIG_ID_STARTDATE) !== false,
            'enddate' => strpos($currentid, self::CONFIG_ID_ENDDATE) !== false,
        ];
        return function (array $data, array $datasubmitted) use ($compsettingid, $compopr, $errorstrings, $domain): string {
            // Get the current timestamp.
            $thistimestamp = (int) $data['timestamp'];

            // Regulate the expiration:
            // - If is it start date we allow the time in the past, by not checking any further!
            // - If is it end date we allow only the time in the future.
            if ($domain['enddate'] === true && $thistimestamp < time()) {
                return $errorstrings['expired'];
            }

            // Extract the data time of the other side to compare against from submitted data.
            $compsubmitteddata = array_filter($datasubmitted, function ($value, $key) use ($compsettingid) {
                return strpos($key, $compsettingid) !== false;
            }, ARRAY_FILTER_USE_BOTH);

            // If found, we proceed.
            if (!empty($compsubmitteddata)) {
                $compfiltereddata = [];
                // Extract only the datetime parameters.
                foreach ($compsubmitteddata[array_key_first($compsubmitteddata)] as $key => $value) {
                    if ($key === 'enabled' || $key === 'oldvalue') {
                        continue;
                    }
                    switch ($key) {
                        case 'year':
                            $compfiltereddata['year'] = intval($value);
                            break;
                        case 'month':
                            $compfiltereddata['month'] = intval($value);
                            break;
                        case 'day':
                            $compfiltereddata['day'] = intval($value);
                            break;
                        case 'hour':
                            $compfiltereddata['hour'] = intval($value);
                            break;
                        case 'minute':
                            $compfiltereddata['minute'] = intval($value);
                            break;
                        default:
                            break;
                    }
                }
                // If the compiled filtered array is not empty, we do the comparison.
                if (!empty($compfiltereddata)) {
                    $configtimestamp = make_timestamp(
                        $compfiltereddata['year'],
                        $compfiltereddata['month'],
                        $compfiltereddata['day'],
                        $compfiltereddata['hour'],
                        $compfiltereddata['minute']
                    );

                    // Compare the timestamps of both sides based on provided comparison operator.
                    if ($compopr === '>=' && $thistimestamp >= $configtimestamp) {
                        // Returning error message, if the condition is met.
                        return $errorstrings[$compopr];
                    } else if ($compopr === '<=' && $thistimestamp <= $configtimestamp) {
                        // Returning error message, if the condition is met.
                        return $errorstrings[$compopr];
                    }
                }
            }
            return '';
        };
    }
}
