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

    /** @var string version api version to apply for */
    public $version = '1.9.0';

    /** @var string the username. */
    private string $username;
    /** @var string the password. */
    private string $password;
    /** @var string the timeout. */
    private string $timeout;
    /** @var string the connecttimeout. */
    private string $connecttimeout;
    /** @var string the baseurl. */
    private string $baseurl;

    /**
     * Constructor of the Opencast Test API.
     *
     * @param int|null $instanceid Opencast instance id.
     * @param boolean $enableingest whether to enable ingest upload.
     *
     * @throws \dml_exception
     */
    public function __construct($instanceid = null, $enableingest = false) {
        // Needed to persist responses across requests.
        $this->jsonresponses = json_decode(get_config('tool_opencast', 'api_testable_responses'), true);
        if (empty($this->jsonresponses)) {
            throw new \moodle_exception('notestingjsonresponses', 'tool_opencast');
        }

        $instanceid = intval($instanceid);
        $defaultocinstance = settings_api::get_default_ocinstance();
        if ($defaultocinstance === null) {
            throw new \dml_exception('dmlreadexception', null,
                'No default Opencast instance is defined.');
        }

        $storedconfigocinstanceid = !$instanceid ? $defaultocinstance->id : $instanceid;

        $this->username = settings_api::get_apiusername($storedconfigocinstanceid);
        $this->password = settings_api::get_apipassword($storedconfigocinstanceid);
        $this->timeout = settings_api::get_apitimeout($storedconfigocinstanceid);
        $this->connecttimeout = settings_api::get_apiconnecttimeout($storedconfigocinstanceid);
        $this->baseurl = settings_api::get_apiurl($storedconfigocinstanceid);

        $config = [
            'url' => $this->baseurl,
            'username' => $this->username,
            'password' => $this->password,
            'timeout' => (intval($this->timeout) / 1000),
            'connect_timeout' => (intval($this->connecttimeout) / 1000),
            'version' => $this->version,
        ];

        $handler = \OpencastApi\Mock\OcMockHanlder::getHandlerStackWithPath($this->jsonresponses);
        if (empty($handler) || !is_callable($handler)) {
            throw new \moodle_exception('nomockhandler', 'tool_opencast');
        }
        $config['handler'] = $handler;
        $this->opencastapi = new \OpencastApi\Opencast($config, [], $enableingest);
        $this->opencastrestclient = new \OpencastApi\Rest\OcRestClient($config);
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
     * Create and store a response for a http call.
     * @param string $resource Resource to which the response is added
     * @param string $method Http method
     * @param int $status The http status code to be returned
     * @param string $body The response body to be returned
     * @param string $params The params send by request to check for more precise call
     * @param array $headers The response headers to be returned
     * @param string $version The response protocol version to be returned
     * @param string $reason The response Reason phrase (when empty a default will be used based on the status code)
     */
    public static function add_json_response($resource, $method, $status = 200, $body = null, $params = '', $headers = [],
        $version = '', $reason = null) {
        $jsonresponses = json_decode(get_config('tool_opencast', 'api_testable_responses'), true);
        if (!is_array($jsonresponses)) {
            $jsonresponses = [];
        }
        if (!array_key_exists($resource, $jsonresponses)) {
            $jsonresponses[$resource] = [];
        }
        if (!isset($jsonresponses[$resource][strtoupper($method)])) {
            $jsonresponses[$resource][strtoupper($method)] = [];
        }
        $responseobject = compact('status', 'body', 'version', 'reason', 'params', 'headers');
        $jsonresponses[$resource][strtoupper($method)][] = $responseobject;
        set_config('api_testable_responses', json_encode($jsonresponses), 'tool_opencast');
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
