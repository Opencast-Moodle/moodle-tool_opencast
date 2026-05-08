<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace Tests\DataProvider;
use GuzzleHttp\Psr7;

class IngestDataProvider {
    public static function getAllCases(): array {
        return [
            [false, false, [], 0, 0, []],
            [true, false, [], 0, 0, []],
            [true, true, [], 0, 0, []],
            [true, true, ['workflow_definition_identifier' => 'DESC'], 0, 0, []],
            [true, true, ['workflow_definition_identifier' => 'DESC'], 2, 0, []],
            [true, true, ['workflow_definition_identifier' => 'DESC'], 2, 1, []],
            [true, true, [], 0, 0, ['workflow_definition_identifier' => 'fast']],
        ];
    }

    public static function getDCCatalog() {
        return file_get_contents(__DIR__   . '/test_files/dublincore-episode.xml');
    }

    public static function getEpisodeXMLFile() {
        return Psr7\Utils::tryFopen(__DIR__   . '/test_files/dublincore-episode.xml', 'r');
    }

    public static function getPresentationFile() {
        return Psr7\Utils::tryFopen(__DIR__   . '/test_files/video_test.mp4', 'r');
    }

    public static function getPresentationUrl() {
        return '';
    }

    public static function getPresenterFile() {
        return Psr7\Utils::tryFopen(__DIR__   . '/test_files/video_test.mp4', 'r');
    }

    public static function getPresenterUrl() {
        return '';
    }

    public static function getEpisodeAclXMLFile() {
        return Psr7\Utils::tryFopen(__DIR__   . '/test_files/xacml-episode.xml', 'r');
    }

    public static function getCatalogURL() {
        return '';
    }

    public static function getAttachmentURL() {
        return '';
    }

    public static function getTrackURL() {
        return '';
    }
}
