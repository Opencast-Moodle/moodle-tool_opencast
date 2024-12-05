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

namespace tool_opencast\proxy;

use OpencastApi\Rest\OcRest;
use tool_opencast\local\maintenance_class;

/**
 * A decorated proxy class to wrap around the Opencast API services.
 *
 * This proxy class is meant to have more local control over the overall system app interaction with Opencast API Library.
 * Its main purpose is to apply a top layer controller such as maintenance checkers.
 *
 * @package    tool_opencast
 * @copyright  2024 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class decorated_opencastapi_service {

    /** @var OcRest|null The Opencast API service */
    public ?OcRest $apiservice;

    /** @var maintenance_class|null The maintenance class */
    private ?maintenance_class $maintenance;

    /**
     * Constructor
     * @param OcRest|null $apiservice The Opencast API service
     * @param maintenance_class|null $maintenance The maintenance class
     */
    public function __construct(?OcRest $apiservice = null, ?maintenance_class $maintenance = null) {
        $this->apiservice = $apiservice;
        $this->maintenance = $maintenance;
    }

    /**
     * Magic method to handle method calls on the decorated proxy object.
     *
     * If the maintenance class is set and the current method is not allowed, it will restrict access.
     * Otherwise, it will call the actual Opencast API service method.
     *
     * @param string $method The name of the method
     * @param array $args The arguments for the method
     *
     * @return mixed|void The decorated proxy object or the response object obtained from original service.
     *                    or void if it is in maintenance mode.
     */
    public function __call(string $method, array $args) {
        // Maintenance feature checker.
        if (!empty($this->maintenance) && !$this->maintenance->can_access($method)) {
            $this->maintenance->decide_access_bounce();
            return;
        }
        $response = call_user_func_array([$this->apiservice, $method], $args);

        // Handle recursive.
        if ($response === $this->apiservice) {
            return $this;
        }

        return $response;
    }
}
