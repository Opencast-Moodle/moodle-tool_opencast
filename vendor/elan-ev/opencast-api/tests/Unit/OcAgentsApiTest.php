<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use OpencastApi\Opencast;

class OcAgentsApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $ocRestApi = new Opencast($config, [], false);

        $this->ocAgentsApi = $ocRestApi->agentsApi;
    }

    /**
     * @test
     */
    public function get_agents(): void
    {
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
?>
