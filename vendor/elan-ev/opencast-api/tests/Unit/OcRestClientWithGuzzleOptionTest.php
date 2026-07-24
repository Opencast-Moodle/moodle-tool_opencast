<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\OcTestCase;
use OpencastApi\Opencast;

#[\AllowDynamicProperties]
class OcRestClientWithGuzzleOptionTest extends OcTestCase
{
    private $ocBaseApi;
    private $ocEventApi;
    private $ocBaseApiFaulty;

    protected function setUp(): void
    {
        parent::setUp();
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $config['guzzle'] = [
            'debug' => true,
            'query' => ['limit' => 1],
            'auth' => [
                $config['username'], $config['password']
            ]
        ];
        $ocRestApi = new Opencast($config, [], false);
        $this->ocBaseApi = $ocRestApi->baseApi;
        $this->ocEventApi = $ocRestApi->eventsApi;

        $config['guzzle']['auth'] = [
            'faulty', 'faulty'
        ];
        $ocRestApiFaulty = new Opencast($config, [], false);
        $this->ocBaseApiFaulty = $ocRestApiFaulty->baseApi;
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
    public function get_events_overwrite_guzzle_option(): void
    {
        $response = $this->ocEventApi->getAll(['limit' => 4]);
        $this->assertSame(200, $response['code'], 'Failure to get events');
        $this->assertSame(4, count($response['body']), 'Failure to get specifically 4 events.');
    }

    /**
     * @test
     */
    public function get_faulty(): void
    {
        $response = $this->ocBaseApiFaulty->get();
        $this->assertSame(401, $response['code'], 'Failure to overwrite default option!');
    }

    /**
     * @test
     */
    public function get_no_auth(): void
    {
        $response = $this->ocBaseApi->noHeader()->get();
        $this->assertSame(200, $response['code'], 'Failure to get base info');
        $this->assertSame(true, is_object($response['body']), 'Failure to get base info correctly');
    }
}
?>
