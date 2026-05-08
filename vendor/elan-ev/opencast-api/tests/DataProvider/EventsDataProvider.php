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

class EventsDataProvider {
    public static function getAllCases(): array {
        return [
            [[]],
            [['sign' => true]],
            [['withacl' => true]],
            [['withmetadata' => true]],
            [['withpublications' => true]],
            [['withscheduling' => true]],
            [['includeInternalPublication' => true]],
            [['sort' => ['title' => 'DESC']]],
            [['limit' => 2]],
            [['offset' => 1, 'limit' => 2]],
            [['filter' => ['title' => 'test']]],
        ];
    }

    public static function getBySeriesCases(): array {
        return [
            ['ID-openmedia-opencast'],
        ];
    }

    public static function createEventCases(): array {
        return [
            [self::getAcls(), self::getMetadata('presenter'), self::getProcessing(), '', self::getPresenterFile(), self::getPresentationFile(), self::getAudioFile()],
        ];
    }

    public static function getAcls() {
        return '[{"allow":true,"role":"ROLE_ADMIN","action":"write"},{"allow":true,"role":"ROLE_ADMIN","action":"read"},{"allow":true,"role":"ROLE_GROUP_MH_DEFAULT_ORG_EXTERNAL_APPLICATIONS","action":"write"},{"allow":true,"role":"ROLE_GROUP_MH_DEFAULT_ORG_EXTERNAL_APPLICATIONS","action":"read"}]';
    }

    public static function getMetadata($title) {
        return '[{"label":"Opencast Event Dublincore","flavor":"dublincore\/episode","fields":[{"id":"title","value":"PHP UNIT TEST_' . strtotime('now') . '_' . strtoupper($title) . '_{update_replace}"},{"id":"subjects","value":["This is default subject"]},{"id":"description","value":"This is a default description for video"},{"id":"startDate","value":"' . date('Y-m-d') . '"},{"id":"startTime","value":"' . date('H:i:s') . 'Z"}]}]';
    }

    public static function getProcessing() {
        return '{"workflow":"schedule-and-upload","configuration":{"flagForCutting":"false","flagForReview":"false","publishToEngage":"true","publishToHarvesting":"false","straightToPublishing":"true"}}';
    }

    public static function getPresentationFile() {
        return Psr7\Utils::tryFopen(__DIR__   . '/test_files/video_test.mp4', 'r');
    }

    public static function getPresenterFile() {
        return Psr7\Utils::tryFopen(__DIR__   . '/test_files/video_test.mp4', 'r');
    }

    public static function getAudioFile() {
        return Psr7\Utils::tryFopen(__DIR__   . '/test_files/audio_test.mp3', 'r');
    }

    public static function getVttFile($lang = 'de', $overwrite = false) {
        $lang = strtolower($lang);
        $overwitestr = $overwrite ? '_overwrite' : '';
        $filename = "/test_files/video_test{$overwitestr}_{$lang}.vtt";
        return Psr7\Utils::tryFopen(__DIR__   . $filename, 'r');
    }
}
