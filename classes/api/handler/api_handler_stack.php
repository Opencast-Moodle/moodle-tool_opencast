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
 * API Handler Stack class for Opencast API services.
 *
 * @package    tool_opencast
 * @copyright  2024 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_opencast\api\handler;

use GuzzleHttp\HandlerStack;
use tool_opencast\api\middleware\api_middlewares;

/**
 * API Handler Stack class for Opencast API services.
 *
 * @package    tool_opencast
 * @copyright  2024 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api_handler_stack {

    /** @var HandlerStack $handlerstack */
    private HandlerStack $handlerstack;

    /**
     * Constructor of class api_handler_stack.
     */
    public function __construct() {
        // Get the default Guzzle Handlers.
        $this->handlerstack = HandlerStack::create();
        $this->register_custom_handlers();
    }

    /**
     * Registers custom handlers to the Guzzle HandlerStack.
     *
     * This method initially removes the default 'http_errors' handler and adds a custom handler
     * for handling HTTP errors using the 'api_middlewares::http_errors()' middleware.
     *
     * @return void
     */
    private function register_custom_handlers() {
        // As for http errors, we use a custom handler.
        $this->handlerstack->remove('http_errors');
        $this->handlerstack->unshift(api_middlewares::http_errors(), 'tool_opencast_http_errors');
    }

    /**
     * Adds a new handler to the handler stack.
     *
     * This function adds a new middleware handler to the existing handler stack.
     * The handler can be added either at the beginning or end of the stack.
     *
     * @param callable $middleware The middleware function to be added to the stack.
     * @param string $name The name of the middleware for identification.
     * @param bool $first Optional. If true, adds the middleware to the beginning of the stack. Default is true.
     *
     * @return bool Returns true if the handler was successfully added to the stack.
     *
     * @throws moodle_exception If the handler stack is empty or the handler cannot be added.
     */
    public function add_handler_to_stack(callable $middleware, string $name, bool $first = true): bool {
        if (!empty($this->handlerstack)) {
            if ($first) {
                $this->handlerstack->unshift($middleware, $name);
            } else {
                $this->handlerstack->push($middleware, $name);
            }
            return true;
        }
        throw new moodle_exception('exception_code_unabletoaddhandler', 'tool_opencast');
    }

    /**
     * Removes a handler from the handler stack.
     *
     * This function attempts to remove a handler with the specified name from the handler stack.
     * If the handler is found and successfully removed, it returns true. Otherwise, it returns false.
     *
     * @param string $name The name of the handler to be removed from the stack.
     *
     * @return bool Returns true if the handler was successfully removed, false otherwise.
     */
    public function remove_handler_from_stack($name): bool {
        $isremoved = false;
        try {
            if ($this->handlerstack && $this->handlerstack->findByName($name) !== false) {
                $this->handlerstack->remove($name);
                $isremoved = true;
            }
        } catch (\Throwable $th) {
            return false;
        }
        return $isremoved;
    }

    /**
     * Retrieves the current handler stack.
     *
     * This method returns the HandlerStack object that contains all the registered middleware handlers.
     *
     * @return HandlerStack The current handler stack containing all registered middleware handlers.
     */
    public function get_handler_stack() {
        return $this->handlerstack;
    }
}
