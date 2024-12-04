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

/**
 * Admin setting class which is used to create a date time selector.
 *
 * @package    tool_opencast
 * @copyright  2024 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_configdatetimeselector extends \admin_setting {

    /** @var bool Flag to determine whether it is optional */
    private $optional;

    /** @var callable|null Validation function */
    protected $validatefunction = null;

    /**
     * Constructor
     * @param string $name setting unique ascii name
     * @param string $visiblename localised
     * @param string $description long localised info
     * @param int $defaultsetting the default timestamp
     * @param bool $optional whether the setting need to be optionally selected by an enable checkbox. Defaults to false
     */
    public function __construct($name, $visiblename, $description, $defaultsetting = 0, $optional = false) {
        parent::__construct($name, $visiblename, $description, $defaultsetting);
        $this->optional = $optional;
    }


    /**
     * Retrieves the current setting value for the date and time selector.
     *
     * This function reads the configuration, parses it, and returns an array
     * containing the date and time components, along with additional settings.
     *
     * @return array|null An array containing the following keys:
     *                    - year: The year (YYYY format)
     *                    - month: The month (01-12 format)
     *                    - day: The day of the month (1-31 format)
     *                    - hour: The hour (0-23 format)
     *                    - minute: The minute (0-59 format)
     *                    - timestamp: The Unix timestamp of the date and time
     *                    - optional: Whether the setting is optional
     *                    - enabled: Whether the setting is enabled (for optional settings)
     *                    Returns null if the configuration is empty.
     */
    public function get_setting() {
        $config = $this->config_read($this->name);
        if (empty($config)) {
            return null;
        }

        $config = json_decode($config);

        $configtimestamp = !empty($config->timestamp) ? (int) $config->timestamp : time();

        $configdatetime = usergetdate($configtimestamp);

        $settings = [
            'year' => $configdatetime['year'],
            'month' => $configdatetime['mon'],
            'day' => $configdatetime['mday'],
            'hour' => $configdatetime['hours'],
            'minute' => $configdatetime['minutes'],
            'timestamp' => $configdatetime[0],
            'optional' => $this->optional,
            'enabled' => (bool) $config->enabled,
        ];

        return $settings;
    }


    /**
     * Writes the setting to the configuration.
     *
     * This function processes the input data, make timestamp out of the given date info,
     * and saves the setting in a JSON-encoded format.
     *
     * @param array|mixed $data The input data to be processed and saved.
     *                          Expected to be an array containing date and time components.
     *
     * @return string Returns an empty string on success, or an error message on failure.
     *                If $data is not an array, an empty string is returned.
     */
    public function write_setting($data) {

        if (!is_array($data)) {
            return '';
        }

        $oldvalue = json_decode($data['oldvalue'], true);

        // In case the setting is optional and disabled, we only receive "oldvalue" parameter here.
        if (count($data) === 1 && !empty($data['oldvalue'])) {
            $data = $oldvalue;
            unset($data['enabled']); // When enabled is unset, that means it is disabled, so we force it here.
        }

        // Make timestamp out of data with make_timestamp method to ensure its integrity.
        $configtimestamp = make_timestamp($data['year'], $data['month'], $data['day'], $data['hour'], $data['minute']);

        $additionalsettings = [
            'timestamp' => $configtimestamp,
            'optional' => $this->optional,
        ];

        // Make sure that enabled setting is correctly recorded.
        if (isset($data['enabled'])) {
            $data['enabled'] = true;
        } else {
            $data['enabled'] = false;
        }

        // Here, before merge, we make sure that "oldvalues" parameter is not going to be stored.
        if (isset($data['oldvalue'])) {
            unset($data['oldvalue']);
        }

        $settings = array_merge($data, $additionalsettings);

        // Validate the new setting, if it is enabled.
        if ($settings['enabled'] == true) {
            $error = $this->validate_setting($settings);
            if (!empty($error)) {
                return $error;
            }
        }

        $result = $this->config_write($this->name, json_encode($settings));

        return ($result ? '' : get_string('errorsetting', 'admin'));
    }

    /**
     * Validate the setting. This uses the callback function if provided; subclasses could override
     * to carry out validation directly in the class.
     *
     * @param array $data New values being set
     * @return string Empty string if valid, or error message text
     */
    protected function validate_setting(array $data): string {
        // If validation function is specified, call it now.
        if ($this->validatefunction) {
            // For more accurate dependency validation, we pass the submitted form data to the validation function.
            $datasubmitted = (array) data_submitted();
            return call_user_func($this->validatefunction, $data, $datasubmitted);
        } else {
            return '';
        }
    }

    /**
     * Sets a validate function.
     *
     * The callback will be passed one parameter, the new setting value, and should return either
     * an empty string '' if the value is OK, or an error message if not.
     *
     * @param callable|null $validatefunction Validate function or null to clear
     */
    public function set_validate_function(?callable $validatefunction = null) {
        $this->validatefunction = $validatefunction;
    }

    /**
     * Returns XHTML time select fields
     *
     * @param array $data the current setting
     * @param string $query
     * @return string XHTML time select fields and wrapping div(s)
     */
    public function output_html($data, $query = '') {
        global $OUTPUT;

        // We have to get the setting from the get_setting function, otherwise the pass $data variable is insufficient.
        $setting = $this->get_setting();
        $default = $this->get_defaultsetting();
        if (is_array($default)) {
            $defaultinfo = userdate(intval($default), get_string('strftimedatetime', 'langconfig'));
        } else {
            $defaultinfo = NULL;
        }

        // Support internationalised calendars.
        $calendartype = \core_calendar\type_factory::get_calendar_instance();

        // Set the now datetime as default
        $savedtime = time();
        if (!empty($setting)) {
            $savedtime = intval($setting['timestamp']);
        }

        $dt = new \DateTime('@' . $savedtime, \core_date::get_user_timezone_object());
        $dttimestamp = $dt->getTimestamp();

        $getdatefields = $calendartype->timestamp_to_date_array($dttimestamp);
        $current = [
            'year' => $getdatefields['year'],
            'month' => $getdatefields['mon'],
            'day' => $getdatefields['mday'],
            'hour' => $getdatefields['hours'],
            'minute' => $getdatefields['minutes'],
        ];

        // To prevent bad data when it is a very fresh config setting.
        if (empty($setting)) {
            $setting = $current;
            $setting['enabled'] = false;
            $setting['optional'] = $this->optional;
        }

        // Time part is handled the same everywhere.
        $hours = array();
        for ($i = 0; $i <= 23; $i++) {
            $hours[$i] = sprintf("%02d", $i);
        }
        $minutes = array();
        for ($i = 0; $i < 60; $i += 5) {
            $minutes[$i] = sprintf("%02d", $i);
        }

        // List date fields.
        $fields = $calendartype->get_date_order($current['year'], $calendartype->get_max_year());

        // Add time fields - in RTL mode these are switched.
        $fields['split'] = '/';
        if (right_to_left()) {
            $fields['minute'] = $minutes;
            $fields['colon'] = ':';
            $fields['hour'] = $hours;
        } else {
            $fields['hour'] = $hours;
            $fields['colon'] = ':';
            $fields['minute'] = $minutes;
        }

        // Output all date fields.
        $spanattrs = [
            'class' => 'fdate_time_selector opencast_config_dt_selector',
            'data-settingid' => $this->get_id(),
        ];
        if ($this->optional) {
            $spanattrs['data-isoptional'] = true;
        }
        $html = \html_writer::start_tag('span', $spanattrs);

        // We record old value in a hidden input element, to avoid getting ignored when the config is optional but disabled.
        $html .= \html_writer::empty_tag('input',
            ['type' => 'hidden', 'name' => $this->get_element_name('oldvalue'), 'value' => json_encode($setting)]);

        // Now, we try to add (enabled/disabled) checkbox if the setting is optional.
        $html .= $this->add_optional_checkbox((bool) $setting['enabled']);

        // We then continue with rendering the date time select fields as well as calendar button.
        foreach ($fields as $field => $options) {
            if ($options === '/') {
                $html = rtrim($html);

                // In Gregorian calendar mode only, we support a date selector popup, reusing
                // code from form to ensure consistency.
                if ($calendartype->get_name() === 'gregorian') {
                    $image = $OUTPUT->pix_icon('i/calendar', get_string('calendar', 'calendar'), 'moodle');
                    $html .= ' ' . \html_writer::link('#', $image,
                        [
                            'name' => $this->get_element_name('calendar'),
                            'id' => $this->get_element_id('calendar'),
                        ]
                    );
                }
                continue;
            }
            if ($options === ':') {
                $html .= ': ';
                continue;
            }
            $html .= \html_writer::start_tag('label', ['for' => $this->get_element_id($field)]);
            $html .= \html_writer::span(get_string($field) . ' ', 'accesshide');
            $html .= \html_writer::start_tag('select',
                [
                    'class' => 'custom-select opencast-config-dt-select',
                    'name' => $this->get_element_name($field),
                    'id' => $this->get_element_id($field),
                ]
            );
            foreach ($options as $key => $value) {
                $params = ['value' => $key];
                if ($current[$field] == $key) {
                    $params['selected'] = 'selected';
                }
                $html .= \html_writer::tag('option', s($value), $params);
            }
            $html .= \html_writer::end_tag('select');
            $html .= \html_writer::end_tag('label');
            $html .= ' ';
        }
        $html = rtrim($html) . \html_writer::end_tag('span');

        return format_admin_setting($this, $this->visiblename, $html, $this->description,
            $this->get_id(), '', $defaultinfo, $query);
    }

    /**
     * Adds an optional checkbox to enable/disable the date time selector.
     *
     * This function generates HTML for a checkbox that allows users to enable or disable
     * the date time selector when it's set as optional. If the setting is not optional,
     * an empty string is returned.
     *
     * @param bool $configvalue The current enabled/disabled state of the checkbox
     * @return string HTML markup for the optional checkbox, or an empty string if not optional
     */
    private function add_optional_checkbox(bool $configvalue) {
        // If it is not optional, we don't show the checkbox, and consider it as always enabled.
        if (!$this->optional) {
            return '';
        }
        $html = \html_writer::start_tag('label',
            ['class' => 'form-check d-inline-block pr-2']
        );

        $checkboxattrs = [
            'id' => $this->get_enabled_element_id(),
            'class' => 'form-check-input'
        ];
        $checkboxlabelattrs = ['class' => 'mr-2'];
        $checkboxhtml = \html_writer::checkbox( $this->get_enabled_element_name(),
            '', $configvalue, '', $checkboxattrs, $checkboxlabelattrs);
        $checkboxhtml .= ' ' . get_string('enable');
        $html .= $checkboxhtml;
        $html .= \html_writer::end_tag('label');

        return $html;
    }

    /**
     * Get the name attribute for the enabled checkbox element.
     *
     * This method generates the name attribute for the checkbox that enables or disables
     * the date time selector when it's set as optional.
     *
     * @return string The name attribute for the enabled checkbox element
     */
    private function get_enabled_element_name() {
        return $this->get_element_name('enabled');
    }

    /**
     * Get the id attribute for the enabled checkbox element.
     *
     * This method generates the id attribute for the checkbox that enables or disables
     * the date time selector when it's set as optional.
     *
     * @return string The name attribute for the enabled checkbox element
     */
    private function get_enabled_element_id() {
        return $this->get_element_id('enabled');
    }

    /**
     * Generates a unique element ID by appending a suffix to the base ID.
     *
     * This method creates a unique identifier for HTML elements by combining
     * the base ID of the setting with a provided suffix.
     *
     * @param string $suffix The suffix to append to the base ID.
     * @return string The generated element ID.
     */
    private function get_element_id(string $suffix) {
        return $this->get_id() . '_' . $suffix;
    }

    /**
     * Generates a name for the element by appending a suffix to the full name of the setting.
     *
     * This method creates a name for HTML elements by combining
     * the full name of the setting with a provided suffix.
     *
     * @param string $suffix The suffix to append to the full setting name.
     * @return string The generated element name.
     */
    private function get_element_name(string $suffix) {
        return $this->get_full_name() . '[' . $suffix . ']';
    }
}
