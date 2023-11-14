<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use OpencastApi\Opencast;
use \OpencastApi\Mock\OcMockHanlder;

class OcSysinfoTestMock extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $mockResponse = \Tests\DataProvider\SetupDataProvider::getMockResponses('sysinfo');
        if (empty($mockResponse)) {
            $this->markTestIncomplete('No mock responses for sysinfo could be found!');
        }
        $mockHandler = OcMockHanlder::getHandlerStackWithPath($mockResponse);
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $config['handler'] = $mockHandler;
        $ocRestApi = new Opencast($config, [], false);
        $this->ocSysinfo = $ocRestApi->sysinfo;
    }

    /**
     * @test
     */
    public function get_version(): void
    {
        $responseAll = $this->ocSysinfo->getVersion();
        $this->assertSame(200, $responseAll['code'], 'Failure to get version bundle');

        $responseOpencast = $this->ocSysinfo->getVersion('opencast');
        $this->assertSame(200, $responseOpencast['code'], 'Failure to get version bundle with opencast prefix');
    }
}
?>
