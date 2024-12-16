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
 * Opencast API Response Exception.
 * This should be used to throw exception when a response is made, in order to digest the response from Opencast API
 * and to decide the best error message.
 *
 * @package    tool_opencast
 * @copyright  2024 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_opencast\exception;

use moodle_exception;

/**
 * Opencast API Response Exception.
 * This should be used to throw exception when a response is made, in order to digest the response from Opencast API
 * and to decide the best error message.
 *
 * @package    tool_opencast
 * @copyright  2024 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class opencast_api_response_exception extends moodle_exception {
    /**
     * Constructor of class opencast_api_response_exception.
     *
     * @param array $response the response array that must contain the following:
     *                        - reason: the reason for the exception
     *                        - code: the exception/error code
     * @param bool $replacemessage the flag to determine whether to replace the reason with message.
     */
    public function __construct(array $response, bool $replacemessage = true) {
        $reason = !empty($response['reason']) ? $response['reason'] : null;
        $errorkey = !empty($reason) ? $reason : 'exception_request_generic';
        $this->code = isset($response['code']) ? $response['code'] : 500;
        parent::__construct($errorkey, 'tool_opencast');
        // In case, the reason has already been set by middleware exception, we should show it as error message.
        if (!empty($reason) && $replacemessage) {
            $this->message = $reason;
        }
    }
}
