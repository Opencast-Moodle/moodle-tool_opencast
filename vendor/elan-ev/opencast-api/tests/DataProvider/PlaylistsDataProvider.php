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

class PlaylistsDataProvider {
    public static function getAllCases(): array {
        return [
            [[]],
            [['sort' => 'updated:DESC']],
            [['limit' => 2]],
            [['offset' => 1]],
        ];
    }

    public static function getPlaylist() {
        return '{"title":"Opencast Playlist","description":"PHP UNIT TEST_' . strtotime('now') . '_{update_replace}","creator":"Opencast","entries":[{"contentId":"ID-about-opencast","type":"EVENT"}],"accessControlEntries":[{"allow":true,"role":"ROLE_USER_BOB","action":"read"}]}';
    }

    public static function getEntries() {
        return json_decode('[{"contentId":"ID-about-opencast","type":"EVENT"},{"contentId":"ID-3d-print","type":"EVENT"}]');
    }
}
