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
 * API Middlewares for Opencast API client.
 *
 * @package    tool_opencast
 * @copyright  2024 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_opencast\api\middleware;

use GuzzleHttp\Exception\ConnectException;
use Psr\Http\Message\ResponseInterface;
use tool_opencast\exception\opencast_api_http_errors_exception;

/**
 * API Middlewares for Opencast API client.
 *
 * @package    tool_opencast
 * @copyright  2024 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api_middlewares {
    /**
     * Middleware that throws exceptions for both 4xx and 5xx error as well as the cURL errors.
     *
     * @return callable(callable): callable Returns a function that accepts the next handler.
     */
    public static function http_errors(): callable {
        return static function (callable $handler): callable {
            return static function ($request, array $options) use ($handler) {
                // To get the 4xx and 5xx http errors, we need to check if the "http_errors" option is set.
                $onfulfilled = empty($options['http_errors']) ? null :
                    static function (ResponseInterface $response) use ($request) {
                        $code = $response->getStatusCode();
                        if ($code < 400) {
                            return $response;
                        }
                        $exceptionstringkey = \sprintf("exception_request_%s", $code);
                        if (!get_string_manager()->string_exists($exceptionstringkey, 'tool_opencast')) {
                            $exceptionstringkey = 'exception_request_generic';
                        }
                        throw new opencast_api_http_errors_exception($exceptionstringkey, $code);
                    };

                // This on rejected function would only get invoked if there is a connection error, mostly to catch cURL errors.
                $onrejected = static function (\RuntimeException|string $reason) {
                    // No reason of any kind, we directly throw generic exception message.
                    if (empty($reason)) {
                        throw new opencast_api_http_errors_exception('exception_request_generic', 500);
                    }

                    // As default we assume the generic exception messages and code.
                    $reasonstring = get_string('exception_connect_generic', 'tool_opencast');
                    $code = 500;

                    // When the exception is of type ConnectException, we extract the reason string and code.
                    if ($reason instanceof ConnectException) {
                        $reasonstring = $reason->getMessage();
                        $code = $reason->getCode();
                    } else if (is_string($reason)) {
                        // Otherwise, if the reason is a string, we take that as the reason.
                        $reasonstring = $reason;
                    }

                    // In case the error is cURL, we try to make it more human readable.
                    if (preg_match('/cURL error (\d+):/', $reasonstring, $matches)) {
                        $curlerrornum = (int) $matches[1];
                        $reasonstring = curl_strerror($curlerrornum);
                    }

                    // At the end, we append the reason string to the "exception_connect" string!
                    $exceptionmessage = get_string('exception_connect', 'tool_opencast', $reasonstring);
                    // Throw the exception with message replacement, as we already got the message text.
                    throw new opencast_api_http_errors_exception($exceptionmessage, $code, true);
                };

                // Finally, we pass the above callable closures as promise fulfillment and rejection handlers.
                return $handler($request, $options)->then($onfulfilled, $onrejected);
            };
        };
    }
}
