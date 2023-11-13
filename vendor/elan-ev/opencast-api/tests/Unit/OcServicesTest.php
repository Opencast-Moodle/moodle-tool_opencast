<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use OpencastApi\Opencast;

class OcServicesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
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
