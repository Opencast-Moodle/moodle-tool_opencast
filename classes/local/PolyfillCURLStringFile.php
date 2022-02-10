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
 * Polyfill for CURLStringFile (since PHP 8.0).
 *
 * @package    tool_opencast
 * @copyright  2021 Tamara Gunkel <tamara.gunkel@wi.uni-muenster.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_opencast\local;

/**
 * Polyfill for CURLStringFile (since PHP 8.0).
 *
 * @package    tool_opencast
 * @copyright  2021 Tamara Gunkel <tamara.gunkel@wi.uni-muenster.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class PolyfillCURLStringFile extends \CURLFile {
    /** @var string File data */
    private $data;

    /**
     * Creates the file.
     * @param string $data
     * @param string $postname
     * @param string $mime
     */
    public function __construct(string $data, string $postname, string $mime = 'application/octet-stream') {
        $this->data = $data;
        parent::__construct('data://application/octet-stream;base64,' . base64_encode($data), $mime, $postname);
    }

    /**
     * Set a new file value.
     * @param string $name
     * @param string $value
     */
    public function __set(string $name, $value): void {
        if ('data' === $name) {
            $this->name = 'data://application/octet-stream;base64,' . base64_encode($value);
        }

        $this->{$name} = $value;
    }

    /**
     * Check if data is set.
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool {
        return isset($this->{$name});
    }

    /**
     * Returns data.
     * @param string $name
     * @return mixed
     */
    public function __get(string $name) {
        return $this->{$name};
    }
}
