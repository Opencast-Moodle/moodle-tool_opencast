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
 * Opencast API HTTP Errors Exception.
 * This is the exception mostly to be used in middlewares to find and replace the error message.
 *
 * @package    tool_opencast
 * @copyright  2024 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_opencast\exception;

use moodle_exception;

/**
 * Opencast API HTTP Errors Exception.
 * This is the exception mostly to be used in middlewares to find and replace the error message.
 *
 * @package    tool_opencast
 * @copyright  2024 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class opencast_api_http_errors_exception extends moodle_exception {
    /**
     * Constructor of class opencast_api_http_errors_exception.
     *
     * @param string $errorkey the error string key
     * @param int $errorcodenum the error code
     * @param bool $replacemessage the flag to determine whether to replace the errorkey with message.
     */
    public function __construct(string $errorkey, int $errorcodenum, bool $replacemessage = false) {
        $this->code = $errorcodenum;
        parent::__construct($errorkey, 'tool_opencast');
        if ($replacemessage) {
            $this->message = $errorkey;
        }
    }
}
