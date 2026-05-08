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

class StatisticsDataProvider {
    public static function getAllCases(): array {
        return [
            [[], false],
            [[], true],
            [['resourceType' => 'episode'], false],
            [['resourceType' => 'episode'], true],
            [['resourceType' => 'series'], false],
            [['resourceType' => 'series'], true],
            [['resourceType' => 'organization'], false],
            [['resourceType' => 'organization'], true],
        ];
    }

    public static function getProviderId(): array {
        return [
            ['a-timeseries-provider'],
        ];
    }

    public static function getStatisticalData(): array {
        return [
            ['[{"provider":{"identifier":"a-statistics-provider"},"parameters":{"resourceId":"93213324-5d29-428d-bbfd-369a2bae6700"}},{"provider":{"identifier":"a-timeseries-provider"},"parameters":{"resourceId":"23413432-5a15-328e-aafe-562a2bae6800","from":"2019-04-10T13:45:32Z","to":"2019-04-12T00:00:00Z","dataResolution":"daily"}}]'],
        ];
    }

    public static function getStatisticalDataCVS(): array {
        return [
            ['[]', [], 0, 0],
            ['[{"parameters":{"resourceId":"mh_default_org","detailLevel":"EPISODE","from":"2018-12-31T23:00:00.000Z","to":"2019-12-31T22:59:59.999Z","dataResolution":"YEARLY"},"provider":{"identifier":"organization.views.sum.influx","resourceType":"organization"}}]', [], 0, 0],
            ['[{"parameters":{"resourceId":"mh_default_org","detailLevel":"EPISODE","from":"2018-12-31T23:00:00.000Z","to":"2019-12-31T22:59:59.999Z","dataResolution":"YEARLY"},"provider":{"identifier":"organization.views.sum.influx","resourceType":"organization"}}]', [], 2, 0],
            ['[{"parameters":{"resourceId":"mh_default_org","detailLevel":"EPISODE","from":"2018-12-31T23:00:00.000Z","to":"2019-12-31T22:59:59.999Z","dataResolution":"YEARLY"},"provider":{"identifier":"organization.views.sum.influx","resourceType":"organization"}}]', [], 4, 1],
            ['[{"parameters":{"resourceId":"mh_default_org","detailLevel":"EPISODE","from":"2018-12-31T23:00:00.000Z","to":"2019-12-31T22:59:59.999Z","dataResolution":"YEARLY"},"provider":{"identifier":"organization.views.sum.influx","resourceType":"organization"}}]', ['presenters' => 'Hans Dampf'], 0, 0],
        ];
    }
}
