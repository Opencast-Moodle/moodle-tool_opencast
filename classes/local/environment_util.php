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

/**
 * An environment util for the Opencast Moodle plugins.
 *
 * @package    tool_opencast
 * @copyright  2023 Matthias Kollenbroich, University of Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class environment_util {
    /**
     * Make this class not instantiable.
     */
    private function __construct() {
    }

    /**
     * Returns, whether the current application is a CLI application.
     *
     * @return bool Returns, true, if the current application is a CLI application,
     * and false otherwise.
     */
    public static function is_cli_application() : bool {
        return http_response_code() === false;
    }

    /**
     * Returns, whether the current application runs in the environment of a moodle-plugin-ci workflow,
     * namely, returns, whether the environment variable is_moodle_plugin_ci_workflow is defined.
     *
     * @return bool Returns, true, if the current application runs in the environment of a moodle-plugin-ci workflow,
     * and false otherwise.
     */
    public static function is_moodle_plugin_ci_workflow() : bool {
        return !(getenv('is_moodle_plugin_ci_workflow') === false);
    }
}
