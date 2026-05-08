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

class OcRestClientWithGuzzleOptionTest extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $config['guzzle'] = [
            'debug' => true,
            'query' => ['limit' => 1],
            'auth' => [
                $config['username'], $config['password'],
            ],
        ];
        $ocRestApi = new Opencast($config, [], false);
        $this->ocBaseApi = $ocRestApi->baseApi;
        $this->ocEventApi = $ocRestApi->eventsApi;

        $config['guzzle']['auth'] = [
            'faulty', 'faulty',
        ];
        $ocRestApiFaulty = new Opencast($config, [], false);
        $this->ocBaseApiFaulty = $ocRestApiFaulty->baseApi;
    }

    /**
     * @test
     */
    public function get(): void {
        $response = $this->ocBaseApi->get();
        $this->assertSame(200, $response['code'], 'Failure to get base info');
    }

    /**
     * @test
     */
    public function get_events_overwrite_guzzle_option(): void {
        $response = $this->ocEventApi->getAll(['limit' => 4]);
        $this->assertSame(200, $response['code'], 'Failure to get events');
        $this->assertSame(4, count($response['body']), 'Failure to get specifically 4 events.');
    }

    /**
     * @test
     */
    public function get_faulty(): void {
        $response = $this->ocBaseApiFaulty->get();
        $this->assertSame(401, $response['code'], 'Failure to overwrite default option!');
    }

    /**
     * @test
     */
    public function get_no_auth(): void {
        $response = $this->ocBaseApi->noHeader()->get();
        $this->assertSame(200, $response['code'], 'Failure to get base info');
        $this->assertSame(true, is_object($response['body']), 'Failure to get base info correctly');
    }
}
