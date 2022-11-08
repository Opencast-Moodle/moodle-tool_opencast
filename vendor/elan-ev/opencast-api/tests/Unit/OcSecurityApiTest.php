<?php 
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use OpencastApi\Opencast;

class OcSecurityApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $ocRestApi = new Opencast($config);
        $this->ocSecurityApi = $ocRestApi->securityApi;
    }

    /**
     * @test
     */
    public function sign(): void
    {
        $url = 'https://stable.opencast.org/';
        $validUntil = '2022-12-29T23:59:59Z';
        $response = $this->ocSecurityApi->sign($url, $validUntil);
        $this->assertSame(200, $response['code'], 'Failure to sign in security api');
    }
}
?>