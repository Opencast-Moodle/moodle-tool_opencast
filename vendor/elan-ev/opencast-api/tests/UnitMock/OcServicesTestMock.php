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

class OcServicesTestMock extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        $mockResponse = \Tests\DataProvider\SetupDataProvider::getMockResponses('services');
        if (empty($mockResponse)) {
            $this->markTestIncomplete('No mock responses for services could be found!');
        }
        $mockHandler = OcMockHanlder::getHandlerStackWithPath($mockResponse);
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $config['handler'] = $mockHandler;
        $ocRestApi = new Opencast($config, [], false);
        $this->ocServices = $ocRestApi->services;
    }

    /**
     * @test
     */
    public function get_services(): void {
        $response = $this->ocServices->getServiceJSON(
            'org.opencastproject.ingest'
        );
        $this->assertSame(200, $response['code'], 'Failure to get services list');
    }

    /**
     * @test
     */
    public function get_all_services(): void {
        $response = $this->ocServices->getServiceJSON();
        $this->assertSame(200, $response['code'], 'Failure to get services list');
    }
}
