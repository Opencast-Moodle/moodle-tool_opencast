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
    private $timeout;
    private $baseurl;

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

    /**
     * Constructor of the Opencast API.
     * @param array $settings additional curl settings.
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function __construct($settings = array()) {
        parent::__construct($settings);

        $this->username = get_config('tool_opencast', 'apiusername');
        $this->password = get_config('tool_opencast', 'apipassword');;
        $this->timeout = get_config('tool_opencast', 'apitimeout');;
        $this->baseurl = get_config('tool_opencast', 'apiurl');
        if (empty($this->baseurl)) {
            throw new \moodle_exception('apiurlempty', 'tool_opencast');
        }

        if (empty($this->username)) {
            throw new \moodle_exception('apiusernameempty', 'tool_opencast');
        }

        if (empty($this->password)) {
            throw new \moodle_exception('apipasswordempty', 'tool_opencast');
        }
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

            $this->setopt('CURLOPT_CONNECTTIMEOUT', $this->timeout);

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
     * @param string $resource path of the resource.
     * @param array $params post parameters.
     * @param array $runwithroles if set, the request is executed within opencast assuming the user has
     * the specified roles.
     * @return string JSON String of result.
     * @throws \moodle_exception
     */
    public function oc_post($resource, $params = array(), $runwithroles = array()) {

        $url = $this->baseurl . $resource;

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
                } else {
                    $this->_tmp_file_post_params[$key] = rawurlencode($value);
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

}
