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

require_once($CFG->dirroot . '/lib/filelib.php');

class api extends \curl {

    private $username;
    private $password;

    public static function get_sort_param($params) {

        if (empty($params)) {
            return '';
        }

        foreach ($params as $key => $sortorder) {
            $sortdir = (SORT_ASC == $sortorder) ? 'ASC' : 'DESC';
            return "&sort={$key}:" . $sortdir;
        }
    }

    public static function get_course_acl_role_prefix() {
        return "ROLE_GROUP_MOODLE_COURSE_";
    }

    public static function get_course_acl_role($courseid) {
        return  self::get_course_acl_role_prefix(). $courseid;
    }

    public static function get_course_acl_group_identifier($courseid) {
        return "moodle_course_" . $courseid;
    }

    public static function get_course_acl_group_name($courseid) {
        return "Moodle_Course_" . $courseid;
    }

    public static function get_courses_series_title_prefix() {
        return "Course_Series_";
    }

    public static function get_courses_series_title($courseid) {
        return self::get_courses_series_title_prefix() . $courseid;
    }

    public function __construct($username, $password, $timeout = 30, $settings = array()) {
        parent::__construct($settings);
        $this->username = $username;
        $this->password = $password;
        $this->timeout = $timeout;
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
     *
     * @param string $method http method to be used, e.g. 'POST' or 'GET'.
     * @param string $url url of the requested ressouce.
     * @param array $runwithroles if set, the request is executed within opencast assuming the user has
     * the specified roles.
     * @return array of authentification headers
     * @throws \moodle_exception
     */
    private function get_authentication_header($method, $url, $runwithroles = array()) {

        $options = array('CURLOPT_HEADER' => true);
        $this->setopt($options);

        $header = array();
        $header[] = "X-Requested-Auth: Digest";
        // Restrict to Roles.
        if (!empty($runwithroles)) {
            $header[] = "X-RUN-WITH-ROLES: " . implode(', ', $runwithroles);
        }

        $this->setHeader($header);
        $this->setopt('CURLOPT_CONNECTTIMEOUT', $this->timeout);

        $authresponse = $this->get($url);

        $matches = [];
        preg_match('/WWW-Authenticate: Digest (.*)/', $authresponse, $matches);

        if (empty($matches)) {
            throw new \moodle_exception('authenticationrequestfailed', 'tool_opencast');
        }

        $authheaders = explode(',', $matches[1]);

        $parsed = array();
        foreach ($authheaders as $pair) {
            $vals = explode('=', $pair);
            $parsed[trim($vals[0])] = trim($vals[1], '" ');
        }

        $realm = (isset($parsed['realm'])) ? $parsed['realm'] : "";
        $nonce = (isset($parsed['nonce'])) ? $parsed['nonce'] : "";
        $opaque = (isset($parsed['opaque'])) ? $parsed['opaque'] : "";

        $authenticate1 = md5($this->username . ":" . $realm . ":" . $this->password);
        $authenticate2 = md5($method . ":" . $url);
        $response = md5($authenticate1 . ":" . $nonce . ":" . $authenticate2);

        $header = array();
        $header[] = sprintf(
            'Authorization: Digest username="%s", realm="%s", nonce="%s", opaque="%s", uri="%s", response="%s"',
            $this->username, $realm, $nonce, $opaque, $url, $response
        );

        return $header;
    }

    /**
     * Do a GET call to opencast API.
     *
     * @param string $url
     * @param array $runwithroles if set, the request is executed within opencast assuming the user has
     * the specified roles.
     * @return string JSON String of result.
     * @throws \moodle_exception
     */
    public function oc_get($url, $runwithroles = array()) {

        $header = $this->get_authentication_header('GET', $url, $runwithroles);
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

        $filename = $storedfile->get_source();
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        // If extension is empty, add extension base on mimetype.
        if (empty($extension)) {
            $extension = mimeinfo_from_type('extension', $storedfile->get_mimetype());
            $filename .= '.' . $extension;
        }

        // Check mimetype.
        $mimetype = mimeinfo('type', $filename);
        list($mediatype, $subtype) = explode('/', $mimetype);

        if ($mediatype != 'video') {

            $contextid = $storedfile->get_contextid();
            $context = \context::instance_by_id($contextid);
            list($context, $course, $cm) = get_context_info_array($context);

            $info = new \stdClass();
            $info->coursename = $course->fullname . "(ID: {$course->id})";
            $info->filename = $filename;
            throw new \moodle_exception('wrongmimetypedetected', 'tool_opencast', $info);
        }

        $curlfile->postname = $filename;
        $curlfile->mime = $mimetype;
    }

    /**
     * Do a POST call to opencast API.
     *
     * @param string $url
     * @param array $params post parameters.
     * @param array $runwithroles if set, the request is executed within opencast assuming the user has
     * the specified roles.
     * @return string JSON String of result.
     * @throws \moodle_exception
     */
    public function oc_post($url, $params = array(), $runwithroles = array()) {

        $header = $this->get_authentication_header('POST', $url, $runwithroles);

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
     * @param string $url url of requested resource.
     * @param array $params array of parameters.
     * @param array $runwithroles if set, the request is executed within opencast assuming the user has
     * the specified roles.
     * @return string JSON String of result.
     * @throws \moodle_exception
     */
    public function oc_put($url, $params = array(), $runwithroles = array()) {

        $header = $this->get_authentication_header('PUT', $url, $runwithroles);
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

}
