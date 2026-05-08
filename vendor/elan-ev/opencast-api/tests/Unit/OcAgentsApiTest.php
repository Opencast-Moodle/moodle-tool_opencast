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

class OcAgentsApiTest extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $ocRestApi = new Opencast($config, [], false);

        $this->ocAgentsApi = $ocRestApi->agentsApi;
    }

    /**
     * @test
     */
    public function get_agents(): void {
        $responseAll = $this->ocAgentsApi->getAll(4, 0);
        $this->assertSame(200, $responseAll['code'], 'failure to get agent list');
        $agents = $responseAll['body'];
        if (!empty($agents)) {
            $agent = $agents[array_rand($agents)];
            $responseOne = $this->ocAgentsApi->get($agent->agent_id);
            $this->assertSame(200, $responseOne['code'], 'failure to get agent');
        } else {
            $this->markTestIncomplete('No agents to complete the test!');
        }
    }
}
