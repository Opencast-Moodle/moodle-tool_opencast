<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use OpencastApi\Opencast;
use \OpencastApi\Mock\OcMockHanlder;

class OcSearchTestMock extends TestCase
{
    protected function setUp(): void
    {
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
    public function get_eposides(): void
    {
        $params = ['sid' => '8010876e-1dce-4d38-ab8d-24b956e3d8b7'];
        $response = $this->ocSearch->getEpisodes($params);
        $this->assertSame(200, $response['code'], 'Failure to search episode');
    }

    /**
     * @test
     */
    public function get_lucenes(): void
{
        $params = ['series' => true];
        $response = $this->ocSearch->getLucene($params);
        $this->assertContains($response['code'], [200, 410], 'Failure to create an event');
    }

    /**
     * @test
     */
    public function get_series(): void
    {
        $params = ['episodes' => true];
        $response = $this->ocSearch->getSeries($params);
        $this->assertSame(200, $response['code'], 'Failure to search series');
    }
}
?>
