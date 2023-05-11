<?php 
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use OpencastApi\Opencast;

class OcBaseApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $ocRestApi = new Opencast($config);
        $this->ocBaseApi = $ocRestApi->baseApi;
    }

    /**
     * @test
     */
    public function get(): void
    {
        $response = $this->ocBaseApi->get();
        $this->assertSame(200, $response['code'], 'Failure to get base info');
    }

    /**
     * @test
     */
    public function get_no_auth(): void
    {
        $response = $this->ocBaseApi->noHeader()->get();
        $this->assertSame(200, $response['code'], 'Failure to get base info');
    }

    /**
     * @test
     */
    public function get_dynamic_timeouts(): void
    {
        $response = $this->ocBaseApi->setRequestTimeout(10)->get();
        $this->assertSame(200, $response['code'], 'Failure to get base info');

        $response = $this->ocBaseApi->setRequestConnectionTimeout(1)->get();
        $this->assertSame(200, $response['code'], 'Failure to get base info');
    }

    /**
     * @test
     */
    public function get_user_info(): void
    {
        $response = $this->ocBaseApi->getUserInfo();
        $this->assertSame(200, $response['code'], 'Failure to get base info');
    }

    /**
     * @test
     */
    public function get_user_role(): void
    {
        $response = $this->ocBaseApi->getUserRole();
        $this->assertSame(200, $response['code'], 'Failure to get base info');
    }

    /**
     * @test
     */
    public function get_organization(): void
    {
        $response = $this->ocBaseApi->getOrg();
        $this->assertSame(200, $response['code'], 'Failure to get base info');
    }

    /**
     * @test
     */
    public function get_organization_properties(): void
    {
        $response = $this->ocBaseApi->getOrgProps();
        $this->assertSame(200, $response['code'], 'Failure to get base info');
    }

    /**
     * @test
     */
    public function get_engage_ui_url(): void
    {
        $response = $this->ocBaseApi->getOrgEngageUIUrl();
        $this->assertSame(200, $response['code'], 'Failure to get base info');
    }

    /**
     * @test
     */
    public function get_version(): void
    {
        $response = $this->ocBaseApi->getVersion();
        $this->assertSame(200, $response['code'], 'Failure to get base info');
    }

    /**
     * @test
     */
    public function get_default_version(): void
    {
        $response = $this->ocBaseApi->getDefaultVersion();
        $this->assertSame(200, $response['code'], 'Failure to get base info');
    }
}
?>