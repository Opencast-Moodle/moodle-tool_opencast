<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use OpencastApi\Opencast;
use \OpencastApi\Mock\OcMockHanlder;

class OcStatisticsApiTestMock extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $mockResponse = \Tests\DataProvider\SetupDataProvider::getMockResponses('api_statistics');
        if (empty($mockResponse)) {
            $this->markTestIncomplete('No mock responses for statistics api could be found!');
        }
        $mockHandler = OcMockHanlder::getHandlerStackWithPath($mockResponse);
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $config['handler'] = $mockHandler;
        $ocRestApi = new Opencast($config, [], false);
        $this->ocStatisticsApi = $ocRestApi->statisticsApi;
    }

    /**
     * @test
     */
    public function get_all_providers(): void
    {
        $response = $this->ocStatisticsApi->getAllProviders();
        $this->assertSame(200, $response['code'], 'Failure to get providers list');
    }

    /**
     * @test
     */
    public function get_provider(): void
    {
        $response = $this->ocStatisticsApi->getProvider('a-timeseries-provider');

        $this->assertContains($response['code'], [200, 404], 'Failure to get provider');
    }
}
?>
