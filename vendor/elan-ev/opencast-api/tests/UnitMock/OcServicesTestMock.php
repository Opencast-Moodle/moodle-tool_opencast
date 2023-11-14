<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use OpencastApi\Opencast;
use \OpencastApi\Mock\OcMockHanlder;

class OcServicesTestMock extends TestCase
{
    protected function setUp(): void
    {
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
    public function get_services(): void
    {
        $response = $this->ocServices->getServiceJSON(
            'org.opencastproject.ingest'
        );
        $this->assertSame(200, $response['code'], 'Failure to get services list');
    }

    /**
     * @test
     */
    public function get_all_services(): void
    {
        $response = $this->ocServices->getServiceJSON();
        $this->assertSame(200, $response['code'], 'Failure to get services list');
    }
}
?>
