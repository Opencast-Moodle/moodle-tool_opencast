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

class SeriesDataProvider {
    public static function getAllCases(): array {
        return [
            [[]],
            [['withacl' => true]],
            [['sort' => ['title' => 'DESC']]],
            [['limit' => 2]],
            [['offset' => 1]],
            [['filter' => ['title' => 'test']]],
        ];
    }

    public static function getAcl() {
        return '[{"allow":true,"role":"ROLE_ADMIN","action":"write"},{"allow":true,"role":"ROLE_ADMIN","action":"read"},{"allow":true,"role":"ROLE_GROUP_MH_DEFAULT_ORG_EXTERNAL_APPLICATIONS","action":"write"},{"allow":true,"role":"ROLE_GROUP_MH_DEFAULT_ORG_EXTERNAL_APPLICATIONS","action":"read"}]';
    }

    public static function getMetadata() {
        return '[{"label":"Opencast Series Dublincore","flavor":"dublincore\/series","fields":[{"id":"title","value":"PHP UNIT TEST_' . strtotime('now') . '_{update_replace}"},{"id":"subjects","value":["This is default subject"]},{"id":"description","value":"This is a default description for series"}]}]';
    }

    public static function getDCMetadata() {
        return '[{"id":"title","value":"PHP UNIT TEST_' . strtotime('now') . '_{update_replace}"},{"id":"subject","value":""},{"id":"description","value":"aaa"},{"id":"language","value":""},{"id":"rightsHolder","value":""},{"id":"license","value":""},{"id":"creator","value":[]},{"id":"contributor","value":[]}]';
    }

    public static function getTheme() {
        return '';
    }

    public static function getProperties() {
        return '{"ondemand": "true","live": "false"}';
    }
}
