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
use Matrix\Exception;
use tool_opencast\empty_configuration_exception;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/lib/filelib.php');

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
     *
     * @return api|api_testable
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_instance($instanceid = null,
                                        $settings = array(),
                                        $customconfigs = array()) {

        if (self::use_test_api() === true) {
            return new api_testable();
        }

        return new api($instanceid, $settings, $customconfigs);
    }

    /**
     * Returns, whether the test api should be used.
     *
     * @return bool
     * @throws \dml_exception
     */
    private static function use_test_api() : bool {
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
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function __construct($instanceid = null,
                                $settings = array(),
                                $customconfigs = array()) {
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

        $this->setopt(array(
            'CURLOPT_TIMEOUT_MS' => $this->timeout,
            'CURLOPT_CONNECTTIMEOUT_MS' => $this->connecttimeout));
    }

    /**
     * Set curl timout in milliseconds
     * @param int $timeout curl timeout in milliseconds
     */
    public function set_timeout($timeout) {
        $this->timeout = $timeout;
        $this->setopt(array('CURLOPT_TIMEOUT_MS' => $this->timeout));
    }

    /**
     * Set curl connect timout in milliseconds
     * @param int $connecttimeout curl connect timeout in milliseconds
     */
    public function set_connecttimeout($connecttimeout) {
        $this->connecttimeout = $connecttimeout;
        $this->setopt(array('CURLOPT_CONNECTTIMEOUT_MS' => $this->connecttimeout));
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
    private function get_authentication_header($runwithroles = array()) {

        $options = array('CURLOPT_HEADER' => true);
        $this->setopt($options);

        // Restrict to Roles.
        if (!empty($runwithroles)) {
            $header[] = "X-RUN-WITH-ROLES: " . implode(', ', $runwithroles);
            $this->setHeader($header);
        }

        $basicauth = base64_encode($this->username . ":" . $this->password);

        $header = array();

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
    public function oc_get($resource, $runwithroles = array()) {
        $url = $this->baseurl . $resource;

        $this->resetHeader();
        $header = $this->get_authentication_header($runwithroles);
        $header[] = 'Content-Type: application/json';
        $this->setHeader($header);
        $this->setopt(array('CURLOPT_HEADER' => false));

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
    public function oc_post($resource, $params = array(), $runwithroles = array()) {

        $url = $this->baseurl . $resource;

        $this->resetHeader();
        $header = $this->get_authentication_header($runwithroles);
        $header[] = "Content-Type: multipart/form-data";
        $this->setHeader($header);
        $this->setopt(array('CURLOPT_HEADER' => false));

        $options['CURLOPT_POST'] = 1;

        if (is_array($params)) {
            $this->_tmp_file_post_params = array();
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
    public function oc_put($resource, $params = array(), $runwithroles = array()) {

        $url = $this->baseurl . $resource;

        $this->resetHeader();
        $header = $this->get_authentication_header($runwithroles);
        $this->setHeader($header);
        $this->setopt(array('CURLOPT_HEADER' => false));

        $options['CURLOPT_CUSTOMREQUEST'] = "PUT";
        if (is_array($params)) {
            $this->_tmp_file_post_params = array();
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
    public function oc_delete($resource, $params = array(), $runwithroles = array()) {

        $url = $this->baseurl . $resource;

        $this->resetHeader();
        $header = $this->get_authentication_header($runwithroles);
        $this->setHeader($header);
        $this->setopt(array('CURLOPT_HEADER' => false));

        $options['CURLOPT_CUSTOMREQUEST'] = "DELETE";
        if (is_array($params)) {
            $this->_tmp_file_post_params = array();
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

            $resource = '/api/version';

            $result = json_decode($this->oc_get($resource));

            if ($this->get_http_code() != 200) {
                throw new \moodle_exception('Opencast system not reachable.');
            }
            self::$supportedapilevel = $result->versions;
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
        $url = $this->baseurl;

        $this->resetHeader();

        // Define header array.
        $header = array();
        $header[] = 'Content-Type: application/json';
        $this->setHeader($header);
        $this->setopt(array('CURLOPT_HEADER' => false));

        // The "/api" resource endpoint returns key characteristics of the API such as the server name and the default version.
        $resource = $url . '/api';
        $this->get($resource);

        // If the connection fails or the Opencast instance could not be found, return the http code.
        $httpcode = $this->get_http_code();
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
        $userinfo = json_decode($this->oc_get('/api/info/me'));

        // If the credentials are invalid, return a corresponding http code.
        if (!$userinfo) {
            return 400; // Bad Request.
        }

        // If the connection fails or the Opencast instance could not be found, return the http code.
        $httpcode = $this->get_http_code();
        if ($httpcode === false) {
            $httpcode = 404; // Not Found.
        }
        if ($httpcode != 200) {
            return $httpcode;
        }

        return true;
    }
}
