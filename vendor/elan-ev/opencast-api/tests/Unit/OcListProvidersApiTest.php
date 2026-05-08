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

class OcListProvidersApiTest extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        $config = \Tests\DataProvider\SetupDataProvider::getConfig('1.10.0');
        $ocRestApi = new Opencast($config, [], false);

        $this->ocListProvidersApi = $ocRestApi->listProvidersApi;
    }

    /**
     * @test
     */
    public function get_providers_and_provider_list(): void {
        $response = $this->ocListProvidersApi->getProviders();
        $this->assertSame(200, $response['code'], 'failure to get providers list');
        $providers = $response['body'];
        if (!empty($providers) && is_array($providers)) {
            $providers = count($providers) == 1 ? reset($providers) : $providers;
            $provider = $providers[array_rand($providers)];
            $responseList = $this->ocListProvidersApi->getList($provider);
            $this->assertSame(200, $responseList['code'], 'failure to get provider list');
        } else {
            $this->markTestIncomplete('No provider to complete the test!');
        }
    }
}
