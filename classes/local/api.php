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
 * API for opencast
 *
 * @package    tool_opencast
 * @copyright  2018 Tobias Reischmann <tobias.reischmann@wi.uni-muenster.de>
 * @copyright  2017 Andreas Wagner, SYNERGY LEARNING
 * @author     Andreas Wagner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_opencast\local;

use local_chunkupload\local\chunkupload_file;
use tool_opencast\empty_configuration_exception;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/lib/filelib.php');
require_once($CFG->dirroot . '/admin/tool/opencast/vendor/autoload.php');
/**
 * API for opencast
 *
 * @package    tool_opencast
 * @copyright  2018 Tobias Reischmann <tobias.reischmann@wi.uni-muenster.de>
 * @copyright  2017 Andreas Wagner, SYNERGY LEARNING
 * @author     Andreas Wagner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api extends \curl {

    /** @var string the api username */
    private $username;
    /** @var string the api password */
    private $password;
    /** @var int the curl timeout in milliseconds */
    private $timeout = 2000;
    /** @var int the curl connecttimeout in milliseconds */
    private $connecttimeout = 1000;
    /** @var string the api baseurl */
    private $baseurl;
    /** @var \OpencastApi\Opencast the opencast endpoints instance */
    public $opencastapi;
    /** @var \OpencastApi\Rest\OcRestClient the opencast REST Client instance */
    public $opencastrestclient;
    /** @var \tool_opencast\local\maintenance_class the maintenance class instance */
    public $maintenance;

    /** @var array array of supported api levels */
    private static $supportedapilevel;

    /**
     * Returns the sortparam string
     * @param array $params
     * @return string
     */
    public static function get_sort_param($params) {
        if (empty($params)) {
            return '';
        }

        foreach ($params as $key => $sortorder) {
            $sortdir = (SORT_ASC == $sortorder) ? 'ASC' : 'DESC';
            return "&sort={$key}:" . $sortdir;
        }
        return '';
    }

    /**
     * Returns the COURSE_ACL_ROLE-prfix
     * @return string
     */
    public static function get_course_acl_role_prefix() {
        return "ROLE_GROUP_MOODLE_COURSE_";
    }

    /**
     * Returns the course ACL role for the given course
     * @param int $courseid the courseid
     * @return string the acl role
     */
    public static function get_course_acl_role($courseid) {
        return  self::get_course_acl_role_prefix(). $courseid;
    }

    /**
     * Returns the course ACL group identifier for the given course
     * @param int $courseid the courseid
     * @return string the course ACL group identifier
     */
    public static function get_course_acl_group_identifier($courseid) {
        return "moodle_course_" . $courseid;
    }

    /**
     * Returns the course ACL group name for the given course
     * @param int $courseid the course id
     * @return string the course ACL group name
     */
    public static function get_course_acl_group_name($courseid) {
        return "Moodle_Course_" . $courseid;
    }

    /**
     * Returns the course series title prefix
     * @return string the course series title prefix
     */
    public static function get_courses_series_title_prefix() {
        return "Course_Series_";
    }

    /**
     * Returns the course series title for a given course
     * @param int $courseid the courseid
     * @return string the course series title
     */
    public static function get_courses_series_title($courseid) {
        return self::get_courses_series_title_prefix() . $courseid;
    }

    /**
     * Returns the real api or test api depending on the environment.
     *
     * @param int|null $instanceid
     * Opencast instance id.
     *
     * @param array $settings
     * @param array $customconfigs
     * @param boolean $enableingest whether to enable ingest upload.
     *
     * @return api|api_testable
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_instance($instanceid = null,
                                        $settings = [],
                                        $customconfigs = [],
                                        $enableingest = false) {

        if (self::use_test_api() === true) {
            $apitestable = new api_testable($instanceid, $enableingest);
            return $apitestable;
        }

        return new api($instanceid, $settings, $customconfigs, $enableingest);
    }

    /**
     * Returns, whether the test api should be used.
     *
     * @return bool
     * @throws \dml_exception
     */
    private static function use_test_api(): bool {
        if (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING) {
            $defaultocinstance = settings_api::get_default_ocinstance();
            if ($defaultocinstance === null) {
                return false;
            }

            $defaultocinstanceapiurl = settings_api::get_apiurl($defaultocinstance->id);
            if ($defaultocinstanceapiurl === false) {
                return false;
            }

            if ($defaultocinstanceapiurl === 'http://testapi:8080') {
                return true;
            }
        }

        return false;
    }

    /**
     * Constructor of the Opencast API.
     *
     * @param int|null $instanceid
     * Opencast instance id.
     *
     * @param array $settings
     * Additional curl settings.
     *
     * @param array $customconfigs
     * Custom api config.
     *
     * @param boolean $enableingest whether to enable ingest upload.
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function __construct($instanceid = null,
                                $settings = [],
                                $customconfigs = [],
                                $enableingest = false) {
        // Allow access to local ips.
        $settings['ignoresecurity'] = true;
        parent::__construct($settings);

        $instanceid = intval($instanceid);

        // If there is no custom configs to set, we go for the stored configs.
        if (empty($customconfigs)) {
            $defaultocinstance = settings_api::get_default_ocinstance();
            if ($defaultocinstance === null) {
                throw new \dml_exception('dmlreadexception', null,
                    'No default Opencast instance is defined.');
            }

            $storedconfigocinstanceid = !$instanceid ? $defaultocinstance->id : $instanceid;

            $this->username         = settings_api::get_apiusername($storedconfigocinstanceid);
            $this->password         = settings_api::get_apipassword($storedconfigocinstanceid);
            $this->timeout          = settings_api::get_apitimeout($storedconfigocinstanceid);
            $this->connecttimeout   = settings_api::get_apiconnecttimeout($storedconfigocinstanceid);
            $this->baseurl          = settings_api::get_apiurl($storedconfigocinstanceid);
            $this->maintenance      = new maintenance_class($storedconfigocinstanceid);

            if (empty($this->username)) {
                throw new empty_configuration_exception('apiusernameempty', 'tool_opencast');
            }

            if (empty($this->password)) {
                throw new empty_configuration_exception('apipasswordempty', 'tool_opencast');
            }
        } else {
            // When user wanted to use the api class but not with the stored configs.
            if (array_key_exists('apiurl', $customconfigs)) {
                $this->baseurl = $customconfigs['apiurl'];
            }

            if (array_key_exists('apiusername', $customconfigs)) {
                $this->username = $customconfigs['apiusername'];
            }

            if (array_key_exists('apipassword', $customconfigs)) {
                $this->password = $customconfigs['apipassword'];
            }

            if (array_key_exists('apitimeout', $customconfigs)) {
                $this->timeout = $customconfigs['apitimeout'];
            }

            if (array_key_exists('apiconnecttimeout', $customconfigs)) {
                $this->connecttimeout = $customconfigs['apiconnecttimeout'];
            }
        }

        // If the admin omitted the protocol part, add the HTTPS protocol on-the-fly.
        if (!preg_match('/^https?:\/\//', $this->baseurl)) {
            $this->baseurl = 'https://'.$this->baseurl;
        }

        // The base url is a must and cannot be empty, so we check its existence for both scenarios.
        if (empty($this->baseurl)) {
            throw new empty_configuration_exception('apiurlempty', 'tool_opencast');
        }

        $this->setopt([
            'CURLOPT_TIMEOUT_MS' => $this->timeout,
            'CURLOPT_CONNECTTIMEOUT_MS' => $this->connecttimeout, ]);

        $config = [
            'url' => $this->baseurl,
            'username' => $this->username,
            'password' => $this->password,
            'timeout' => (intval($this->timeout) / 1000),
            'connect_timeout' => (intval($this->connecttimeout) / 1000),
        ];
        $this->opencastapi = $this->decorate_opencast_api_services($config, [], $enableingest);
        $this->opencastrestclient = new \tool_opencast\proxy\decorated_opencastapi_rest_client($config, $this->maintenance);

        // We notify the maintenance directly in constructor, to cover almost every external use of this class.
        $this->notify_maintenance();
    }

    /**
     * Decorates the Opencast API services with maintenance-aware proxy.
     *
     * This function creates a new Opencast API instance and wraps each of its services
     * with a decorated proxy that is aware of the maintenance status.
     *
     * @param array $config The configuration array for the Opencast API.
     * @param array $engageconfig Optional. The engage configuration array for the Opencast API. Default is an empty array.
     * @param bool $enableingest Optional. Whether to enable ingest functionality. Default is false.
     *
     * @return \OpencastApi\Opencast A decorated instance of the Opencast API with maintenance-aware service proxy.
     */
    private function decorate_opencast_api_services(
        array $config,
        array $engageconfig = [],
        bool $enableingest = false
    ): \OpencastApi\Opencast {
        $decoratedopencastapi = new \OpencastApi\Opencast($config, $engageconfig, $enableingest);
        $classvars = get_object_vars($decoratedopencastapi);
        foreach (array_keys($classvars) as $name) {
            $decoratedopencastapi->{$name} =
                new \tool_opencast\proxy\decorated_opencastapi_service($decoratedopencastapi->{$name}, $this->maintenance);
        }
        return $decoratedopencastapi;
    }

    /**
     * Notifies about maintenance status and handles maintenance message display.
     *
     * This function checks if maintenance is set and, if so, handles the display
     * of maintenance notification messages.
     *
     * @return void
     */
    private function notify_maintenance() {

        // When the maintenance is not set, we do nothing!
        if (empty($this->maintenance)) {
            return;
        }

        // We now handle maintenance messages notification display.
        $this->maintenance->handle_notification_message_display();
    }

    /**
     * Set curl timout in milliseconds
     * @param int $timeout curl timeout in milliseconds
     */
    public function set_timeout($timeout) {
        $this->timeout = $timeout;
        $this->setopt(['CURLOPT_TIMEOUT_MS' => $this->timeout]);
    }

    /**
     * Set curl connect timout in milliseconds
     * @param int $connecttimeout curl connect timeout in milliseconds
     */
    public function set_connecttimeout($connecttimeout) {
        $this->connecttimeout = $connecttimeout;
        $this->setopt(['CURLOPT_CONNECTTIMEOUT_MS' => $this->connecttimeout]);
    }

    /**
     * Set base url.
     * @param string $baseurl
     */
    public function set_baseurl($baseurl) {
        $this->baseurl = $baseurl;
    }

    /**
     * Get http status code
     *
     * @return int|boolean status code or false if not available.
     */
    public function get_http_code() {

        $info = $this->get_info();
        if (!isset($info['http_code'])) {
            return false;
        }
        return $info['http_code'];
    }

    /**
     * Get an digest authentication header.
     * @param array $runwithroles if set, the request is executed within opencast assuming the user has
     * the specified roles.
     *
     * @return array of authentification headers
     * @throws \moodle_exception
     */
    private function get_authentication_header($runwithroles = []) {

        $options = ['CURLOPT_HEADER' => true];
        $this->setopt($options);

        // Restrict to Roles.
        if (!empty($runwithroles)) {
            $header[] = "X-RUN-WITH-ROLES: " . implode(', ', $runwithroles);
            $this->setHeader($header);
        }

        $basicauth = base64_encode($this->username . ":" . $this->password);

        $header = [];

        $header[] = sprintf(
            'Authorization: Basic %s', $basicauth
        );

        return $header;
    }

    /**
     * Do a GET call to opencast API.
     *
     * @param string $resource path of the resource.
     * @param array $runwithroles if set, the request is executed within opencast assuming the user has
     * the specified roles.
     * @return string JSON String of result.
     * @throws \moodle_exception
     */
    public function oc_get($resource, $runwithroles = []) {

        // Check for maintenance first.
        if (!empty($this->maintenance) && !$this->maintenance->can_access(__FUNCTION__)) {
            $this->maintenance->decide_access_bounce();
            return;
        }

        $url = $this->baseurl . $resource;

        $this->resetHeader();
        $header = $this->get_authentication_header($runwithroles);
        $header[] = 'Content-Type: application/json';
        $this->setHeader($header);
        $this->setopt(['CURLOPT_HEADER' => false]);

        return $this->get($url);
    }

    /**
     * Opencast needs a fileextension for uploaded file, so add a postname
     * (which the core curl module does NOT) to curl_file.
     *
     * @param object|\stored_file $storedfile stored file to be uploaded.
     * @param string $key key identifier within the post params array of the stored file.
     * @throws \moodle_exception
     */
    private function add_postname($storedfile, $key) {

        $curlfile = $this->_tmp_file_post_params[$key];

        // Ensure that file is uploaded as a curl file (PHP 5 > 5.5.0 is needed).
        if (!$curlfile instanceof \CURLFile) {
            throw new \moodle_exception('needphp55orhigher', 'tool_opencast');
        }

        // Extracting filename from $file->file_record->source, make sure to have a string filename!
        $source = @unserialize($storedfile->get_source());
        $filename = '';
        if (is_object($source)) {
            $filename = $source->source;
        } else {
            // If source is not a serialised object, it is a string containing only the filename.
            $filename = $storedfile->get_source();
        }
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        // If extension is empty, add extension base on mimetype.
        if (empty($extension)) {
            $extension = mimeinfo_from_type('extension', $storedfile->get_mimetype());
            $filename .= '.' . $extension;
        }

        // Check mimetype.
        $mimetype = mimeinfo('type', $filename);

        $curlfile->postname = $filename;
        $curlfile->mime = $mimetype;
    }

    /**
     * Opencast needs a fileextension for uploaded file, so add a postname
     * (which the core curl module does NOT) to curl_file.
     *
     * @param chunkupload_file $file chunkupload file to be uploaded.
     * @param string $key key identifier within the post params array of the stored file.
     * @throws \moodle_exception
     */
    private function add_postname_chunkupload($file, $key) {

        $curlfile = $this->_tmp_file_post_params[$key];

        // Ensure that file is uploaded as a curl file (PHP 5 > 5.5.0 is needed).
        if (!$curlfile instanceof \CURLFile) {
            throw new \moodle_exception('needphp55orhigher', 'tool_opencast');
        }

        $filename = $file->get_filename();

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        // If extension is empty, add extension base on mimetype.
        if (empty($extension)) {
            $mimetype = file_storage::mimetype_from_file($file->get_fullpath());
            $extension = mimeinfo_from_type('extension', $mimetype);
            $filename .= '.' . $extension;
        }

        // Check mimetype.
        $mimetype = mimeinfo('type', $filename);

        $curlfile->postname = $filename;
        $curlfile->mime = $mimetype;
    }

    /**
     * Do a POST call to opencast API.
     *
     * @param string $resource path of the resource.
     * @param array $params post parameters.
     * @param array $runwithroles if set, the request is executed within opencast assuming the user has
     * the specified roles.
     * @return string JSON String of result.
     * @throws \moodle_exception
     */
    public function oc_post($resource, $params = [], $runwithroles = []) {

        // Check for maintenance first.
        if (!empty($this->maintenance) && !$this->maintenance->can_access(__FUNCTION__)) {
            $this->maintenance->decide_access_bounce();
            return;
        }

        $url = $this->baseurl . $resource;

        $this->resetHeader();
        $header = $this->get_authentication_header($runwithroles);
        $header[] = "Content-Type: multipart/form-data";
        $this->setHeader($header);
        $this->setopt(['CURLOPT_HEADER' => false]);

        $options['CURLOPT_POST'] = 1;

        if (is_array($params)) {
            $this->_tmp_file_post_params = [];
            foreach ($params as $key => $value) {
                if ($value instanceof \stored_file) {
                    $value->add_to_curl_request($this, $key);
                    $this->add_postname($value, $key);
                } else if (class_exists('\local_chunkupload\local\chunkupload_file') &&
                    $value instanceof \local_chunkupload\local\chunkupload_file) {
                        $value->add_to_curl_request($this, $key);
                        $this->add_postname_chunkupload($value, $key);
                } else {
                    $this->_tmp_file_post_params[$key] = $value;
                }
            }
            $options['CURLOPT_POSTFIELDS'] = $this->_tmp_file_post_params;
            unset($this->_tmp_file_post_params);
        } else {
            // The raw post data.
            $options['CURLOPT_POSTFIELDS'] = $params;
        }
        return $this->request($url, $options);
    }

    /**
     * Do a PUT call to opencast API.
     *
     * @param string $resource path of the resource.
     * @param array $params array of parameters.
     * @param array $runwithroles if set, the request is executed within opencast assuming the user has
     * the specified roles.
     * @return string JSON String of result.
     * @throws \moodle_exception
     */
    public function oc_put($resource, $params = [], $runwithroles = []) {

        // Check for maintenance first.
        if (!empty($this->maintenance) && !$this->maintenance->can_access(__FUNCTION__)) {
            $this->maintenance->decide_access_bounce();
            return;
        }

        $url = $this->baseurl . $resource;

        $this->resetHeader();
        $header = $this->get_authentication_header($runwithroles);
        $this->setHeader($header);
        $this->setopt(['CURLOPT_HEADER' => false]);

        $options['CURLOPT_CUSTOMREQUEST'] = "PUT";
        if (is_array($params)) {
            $this->_tmp_file_post_params = [];
            foreach ($params as $key => $value) {
                $this->_tmp_file_post_params[$key] = $value;
            }
            $options['CURLOPT_POSTFIELDS'] = $this->_tmp_file_post_params;
            unset($this->_tmp_file_post_params);
        } else {
            // The raw post data.
            $options['CURLOPT_POSTFIELDS'] = $params;
        }

        return $this->request($url, $options);
    }

    /**
     * Do a DELETE call to opencast API.
     *
     * @param string $resource path of the resource.
     * @param array $params array of parameters.
     * @param array $runwithroles if set, the request is executed within opencast assuming the user has
     * the specified roles.
     * @return string JSON String of result.
     * @throws \moodle_exception
     */
    public function oc_delete($resource, $params = [], $runwithroles = []) {

        // Check for maintenance first.
        if (!empty($this->maintenance) && !$this->maintenance->can_access(__FUNCTION__)) {
            $this->maintenance->decide_access_bounce();
            return;
        }

        $url = $this->baseurl . $resource;

        $this->resetHeader();
        $header = $this->get_authentication_header($runwithroles);
        $this->setHeader($header);
        $this->setopt(['CURLOPT_HEADER' => false]);

        $options['CURLOPT_CUSTOMREQUEST'] = "DELETE";
        if (is_array($params)) {
            $this->_tmp_file_post_params = [];
            foreach ($params as $key => $value) {
                $this->_tmp_file_post_params[$key] = $value;
            }
            $options['CURLOPT_POSTFIELDS'] = $this->_tmp_file_post_params;
            unset($this->_tmp_file_post_params);
        } else {
            // The raw post data.
            $options['CURLOPT_POSTFIELDS'] = $params;
        }

        return $this->request($url, $options);
    }

    /**
     * Checks if the opencast version support a certain version of the External API.
     * This is necessary for the decision, which opencast endpoints are used throughout this class.
     * @param string $level level to check for
     * @return boolean whether the given api $level is supported.
     * @throws \moodle_exception
     */
    public function supports_api_level($level) {
        if (!self::$supportedapilevel) {

            $response = $this->opencastapi->baseApi->getVersion();

            if ($response['code'] != 200) {
                throw new \moodle_exception('Opencast system not reachable.');
            }
            $versions = $response['body'];
            self::$supportedapilevel = $versions->versions;
        }
        return is_array(self::$supportedapilevel) && in_array($level, self::$supportedapilevel);
    }

    /**
     * Checks if the Opencast API URL is reachable and there is an Opencast instance running on that URL.
     *
     * @return int|boolean http status code, if the API URL is not reachable or an Opencast instance
     * is not running on that URL, and true otherwise.
     */
    public function connection_test_url() {
        // The "/api" resource endpoint returns key characteristics of the API such as the server name and the default version.
        $response = $this->opencastapi->baseApi->noHeader()->get();
        // If the connection fails or the Opencast instance could not be found, return the http code.
        $httpcode = $response['code'];
        if ($httpcode === false) {
            $httpcode = 404; // Not Found.
        }
        if ($httpcode != 200) {
            return $httpcode;
        }

        return true;
    }

    /**
     * Checks if the Opencast API username and password is valid.
     *
     * @return int|boolean http status code, if the API URL is not reachable, an Opencast instance
     * is not running on that URL or the credentials are invalid, and true otherwise.
     */
    public function connection_test_credentials() {
        // The "/api" resource endpoint returns information on the logged in user.
        $response = $this->opencastapi->baseApi->getUserInfo();
        $userinfo = $response['body'];

        // If the credentials are invalid, return a corresponding http code.
        if (!$userinfo) {
            return 400; // Bad Request.
        }

        // If the connection fails or the Opencast instance could not be found, return the http code.
        $httpcode = $response['code'];
        if ($httpcode === false) {
            $httpcode = 404; // Not Found.
        }
        if ($httpcode != 200) {
            return $httpcode;
        }

        return true;
    }

    /**
     * Synchronizes the maintenance status with Opencast.
     *
     * This function attempts to retrieve the maintenance status from Opencast
     * and update the local maintenance mode accordingly. It is an experimental
     * feature as the corresponding functionality may not yet exist in Opencast.
     *
     * @return bool Returns true if the maintenance mode was successfully updated,
     *              false if the update failed or the required properties/methods
     *              are not available.
     */
    public function sync_maintenance_with_opencast() {
        // This an experimental feature, because the feature does not exist in Opencast yet.
        if ($this->maintenance && $this->opencastapi && property_exists($this->opencastapi->baseApi, 'getMaintenance')) {
            $response = $this->opencastapi->baseApi->getMaintenance();
            if ($response['code'] != 200) {
                return false;
            }
            $maintenanceobj = $response['body'];
            $inmaintenance = isset($maintenanceobj->in_maintenance) ? (bool) $maintenanceobj->in_maintenance : false;
            $readonly = isset($maintenanceobj->read_only) ? (bool) $maintenanceobj->read_only : false;
            $maintenancemode = $inmaintenance ? maintenance_class::MODE_ENABLE : maintenance_class::MODE_DISABLE;
            if ($inmaintenance && $readonly) {
                $maintenancemode = maintenance_class::MODE_READONLY;
            }
            return $this->maintenance->update_mode_from_opencast($maintenancemode);
        }

        return false;
    }
}
