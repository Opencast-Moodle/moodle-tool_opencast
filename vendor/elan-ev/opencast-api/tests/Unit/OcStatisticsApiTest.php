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

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use OpencastApi\Opencast;

class OcStatisticsApiTest extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $ocRestApi = new Opencast($config, [], false);
        $this->ocStatisticsApi = $ocRestApi->statisticsApi;
    }

    /**
     * @test
     * @dataProvider \Tests\DataProvider\StatisticsDataProvider::getAllCases()
     */
    public function get_all_providers($filter, $withparameters): void {
        $response = $this->ocStatisticsApi->getAllProviders($filter, $withparameters);

        $this->assertSame(200, $response['code'], 'Failure to get providers list');
    }

    /**
     * @test
     * @dataProvider \Tests\DataProvider\StatisticsDataProvider::getProviderId()
     */
    public function get_provider($identifier): void {
        $response = $this->ocStatisticsApi->getProvider($identifier);

        $this->assertContains($response['code'], [200, 404], 'Failure to get provider');
    }

    /**
     * @test
     * @dataProvider \Tests\DataProvider\StatisticsDataProvider::getStatisticalData()
     */
    public function get_statistical_data($data): void {
        $this->markTestSkipped('currently skipped as the resources are not completed');
        $response = $this->ocStatisticsApi->getStatisticalData($data);

        $this->assertContains($response['code'], [200, 404], 'Failure to get statistical data');
    }

    /**
     * currently disabled as the resources are not completed
     * @test
     * @dataProvider \Tests\DataProvider\StatisticsDataProvider::getStatisticalDataCVS()
     */
    public function get_statistical_data_cvs($data, $filter, $limit, $offset): void {
        $this->markTestSkipped('currently skipped as the resources are not completed');
        $response = $this->ocStatisticsApi->getStatisticalDataCSV($data, $filter, $limit, $offset);

        $this->assertContains($response['code'], [200, 404], 'Failure to get statistical data cvs');
    }
}
