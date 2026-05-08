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
use OpencastApi\Mock\OcMockHanlder;

class OcSearchTestMock extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        $mockResponse = \Tests\DataProvider\SetupDataProvider::getMockResponses('search');
        if (empty($mockResponse)) {
            $this->markTestIncomplete('No mock responses for search could be found!');
        }
        $mockHandler = OcMockHanlder::getHandlerStackWithPath($mockResponse);
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $config['handler'] = $mockHandler;
        $ocRestApi = new Opencast($config, [], false);
        $this->ocSearch = $ocRestApi->search;
    }

    /**
     * @test
     */
    public function get_eposides(): void {
        $params = ['sid' => '8010876e-1dce-4d38-ab8d-24b956e3d8b7'];
        $response = $this->ocSearch->getEpisodes($params);
        $this->assertSame(200, $response['code'], 'Failure to search episode');
    }

    /**
     * @test
     */
    public function get_lucenes(): void {
        $params = ['series' => true];
        $response = $this->ocSearch->getLucene($params);
        $this->assertContains($response['code'], [200, 410], 'Failure to create an event');
    }

    /**
     * @test
     */
    public function get_series(): void {
        $params = ['episodes' => true];
        $response = $this->ocSearch->getSeries($params);
        $this->assertSame(200, $response['code'], 'Failure to search series');
    }
}
