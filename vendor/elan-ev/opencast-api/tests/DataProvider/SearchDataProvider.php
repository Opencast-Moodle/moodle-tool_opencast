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

class SearchDataProvider {
    public static function getEpisodeQueryCases(): array {
        return [
            [[], 'json'],
            [['id' => 'ID-spring'], ''],
            [['sid' => '8010876e-1dce-4d38-ab8d-24b956e3d8b7'], ''],
            [['sname' => 'HUB_LOCAL_TEST'], ''],
            [['sort' => 'modified asc'], ''],
            [['offset' => 1], ''],
            [['limit' => 1], ''],
            [['admin' => true], ''],
            [['sign' => true], ''],
        ];
    }

    public static function getLuceneQueryCases(): array {
        return [
            [[], 'json'],
            [[], 'xml'],
            [[], 'XML'],
            [['series' => true], ''],
            [['sort' => 'DATE_CREATED_DESC'], ''],
            [['offset' => 1], ''],
            [['limit' => 1], ''],
            [['admin' => true], ''],
            [['sign' => true], ''],
        ];
    }

    public static function getSeriesQueryCases(): array {
        return [
            [[], 'json'],
            [['id' => '8010876e-1dce-4d38-ab8d-24b956e3d8b7'], ''],
            [['episodes' => true], ''],
            [['sort' => 'modified desc'], ''],
            [['offset' => 1], ''],
            [['limit' => 1], ''],
            [['admin' => true], ''],
            [['sign' => true], ''],
        ];
    }
}
