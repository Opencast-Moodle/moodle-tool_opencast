<?php 
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use OpencastApi\Opencast;
use \OpencastApi\Mock\OcMockHanlder;

class OcAgentsApiTestMock extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $mockResponse = \Tests\DataProvider\SetupDataProvider::getMockResponses('api_agents');
        if (empty($mockResponse)) {
            $this->markTestIncomplete('No mock responses for agents api could be found!');
        }

        $mockHandler = OcMockHanlder::getHandlerStackWithPath($mockResponse);
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $config['handler'] = $mockHandler;
        $ocRestApi = new Opencast($config);

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