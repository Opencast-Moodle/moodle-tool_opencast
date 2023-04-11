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
 * API used for testing
 *
 * @package    tool_opencast
 * @copyright  2021 Tamara Gunkel <tamara.gunkel@wi.uni-muenster.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_opencast\local;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/admin/tool/opencast/vendor/autoload.php');
/**
 * API used for testing
 *
 * @package    tool_opencast
 * @copyright  2021 Tamara Gunkel <tamara.gunkel@wi.uni-muenster.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api_testable extends api {

    /** @var array array of json responses per endpoint */
    private $jsonresponses;

    /** @var int http status code of last request */
    private $httpcode;


    /**
     * Constructor of the Opencast Test API.
     * @throws \dml_exception
     */
    public function __construct() {
        // Needed to persist responses across requests.
        $this->jsonresponses = json_decode(get_config('block_opencast', 'api_testable_responses'), true);
        if (!$this->jsonresponses) {
            $this->jsonresponses = [];
        }

        $this->username         = 'opencast';
        $this->password         = 'admin';
        $this->timeout          = 2000;
        $this->connecttimeout   = 1000;
        $this->baseurl          = 'http://testapi:8080';
    }

    /**
     * Get http status code
     * @return bool|int status code or false if not available.
     */
    public function get_http_code() {
        return $this->httpcode;
    }

    /**
     * Get json responses as array
     * @return array json responses.
     */
    public function get_json_responses() {
        return $this->jsonresponses;
    }


    /**
     * Create a response for a http call.
     * @param string $resource Resource to which the response is added
     * @param string $method Http method
     * @param int $status The http status code to be returned
     * @param array $headers The response headers to be returned
     * @param string $body The response body to be returned
     * @param string $params The params send by request to check for more precise call
     * @param string $version The response protocol version to be returned
     * @param string $reason The response Reason phrase (when empty a default will be used based on the status code)
     */
    public function add_json_response($resource, $method, $status = 200, $body = null, $params = '',
        $version = '', $reason = null) {
        if (!array_key_exists($resource, $this->jsonresponses)) {
            $this->jsonresponses[$resource] = array();
        }
        if (!isset($this->jsonresponses[$resource][strtoupper($method)])) {
            $this->jsonresponses[$resource][strtoupper($method)] = array();
        }
        $responseobject = compact('status', 'body', 'version', 'reason', 'params');
        $this->jsonresponses[$resource][strtoupper($method)][] = $responseobject;
        set_config('api_testable_responses', json_encode($this->jsonresponses), 'block_opencast');
    }

    /**
     * set the mock responses and instantiate the Rest Api.
     */
    public function set_opencastapi_mock_responses() {
        $config = [
            'url' => $this->baseurl,
            'username' => $this->username,
            'password' => $this->password,
            'timeout' => (intval($this->timeout) / 1000),
            'connect_timeout' => (intval($this->connecttimeout) / 1000),
        ];
        $responses = $this->jsonresponses ?? [];
        $handler = \OpencastApi\Mock\OcMockHanlder::getHandlerStackWithPath($responses);
        if (empty($handler) || !is_callable($handler) || empty($responses)) {
            throw new \moodle_exception('nomockhandler', 'tool_opencast');
        }
        $config['handler'] = $handler;
        $this->opencastapi = new \OpencastApi\Opencast($config);
        $this->opencastrestclient = new \OpencastApi\Rest\OcRestClient($config);
    }

    /**
     * Fake a GET call to opencast API.
     *
     * @param string $resource path of the resource.
     * @param array $runwithroles if set, the request is executed within opencast assuming the user has
     * the specified roles.
     * @return string JSON String of result.
     * @throws \moodle_exception
     */
    public function oc_get($resource, $runwithroles = array()) {
        if (array_key_exists($resource, $this->jsonresponses)) {

            if (array_key_exists('get', $this->jsonresponses[$resource])) {
                $this->httpcode = 200;
                return $this->jsonresponses[$resource]['get'];
            }
        }

        $this->httpcode = 404;
        return false;
    }

    /**
     * Fake a POST call to opencast API.
     *
     * @param string $resource path of the resource.
     * @param array $params post parameters.
     * @param array $runwithroles if set, the request is executed within opencast assuming the user has
     * the specified roles.
     * @return string JSON String of result.
     * @throws \moodle_exception
     */
    public function oc_post($resource, $params = array(), $runwithroles = array()) {
        $postresource = $resource . '_' . join(',', array_keys($params));

        if (array_key_exists($postresource, $this->jsonresponses)) {
            if (array_key_exists('post', $this->jsonresponses[$postresource])) {
                $this->httpcode = 201;
                return $this->jsonresponses[$postresource]['post'];
            }
        }

        $this->httpcode = 404;
        return false;
    }

    /**
     * Fake a PUT call to opencast API.
     *
     * @param string $resource path of the resource.
     * @param array $params array of parameters.
     * @param array $runwithroles if set, the request is executed within opencast assuming the user has
     * the specified roles.
     * @return string JSON String of result.
     * @throws \moodle_exception
     */
    public function oc_put($resource, $params = array(), $runwithroles = array()) {
        $putchanges = file_get_contents(__DIR__ . "/../../../../../blocks/opencast/tests/fixtures/api_calls/put/put_changes.json");
        $putchanges = json_decode($putchanges, true);

        if (array_key_exists($resource, $putchanges)) {
            // Load new response.
            if (array_key_exists('method', $putchanges[$resource])) {
                if ($putchanges[$resource]['method'] == 'get') {
                    $apicall = file_get_contents(__DIR__ . "/../../../../../blocks/opencast/tests/fixtures/api_calls/get/" .
                        $putchanges[$resource]['file']);
                    $apicall = json_decode($apicall);
                    $this->add_json_response($apicall->resource, 'get', json_encode($apicall->response));
                }
            }
            $this->httpcode = $putchanges[$resource]['http_code'];
            return true;
        }

        $this->httpcode = 400;
        return false;
    }

    /**
     * Fake a DELETE call to opencast API.
     *
     * @param string $resource path of the resource.
     * @param array $params array of parameters.
     * @param array $runwithroles if set, the request is executed within opencast assuming the user has
     * the specified roles.
     * @return string JSON String of result.
     * @throws \moodle_exception
     */
    public function oc_delete($resource, $params = array(), $runwithroles = array()) {
        $this->httpcode = 204;
        return true;
    }

    /**
     * Checks if the opencast version support a certain version of the External API.
     * Always returns true for testing purposes.
     *
     * @param string $level level to check for
     * @return boolean whether the given api $level is supported.
     * @throws \moodle_exception
     */
    public function supports_api_level($level) {
        return true;
    }

    /**
     * Checks if the Opencast API URL is reachable and there is an Opencast instance running on that URL.
     *
     * @return boolean whether the API URL is reachable or not.
     */
    public function connection_test_url() {
        return true;
    }
}
