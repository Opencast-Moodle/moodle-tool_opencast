<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use OpencastApi\Opencast;

class OcListProvidersApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $config = \Tests\DataProvider\SetupDataProvider::getConfig('1.10.0');
        $ocRestApi = new Opencast($config, [], false);

        $this->ocListProvidersApi = $ocRestApi->listProvidersApi;
    }

    /**
     * @test
     */
    public function get_providers_and_provider_list(): void
    {
        $response = $this->ocListProvidersApi->getProviders();
        $this->assertSame(200, $response['code'], 'failure to get providers list');
        $providers = $response['body'];
        if (!empty($providers) && is_array($providers)) {
            $providers = count($providers) == 1 ? reset($providers) : $providers;
            $provider = $providers[array_rand($providers)];
            $responseList = $this->ocListProvidersApi->getList($provider);
            $this->assertSame(200, $responseList['code'], 'failure to get provider list');
        } else {
            $this->markTestIncomplete('No provider to complete the test!');
        }
    }
}
?>
