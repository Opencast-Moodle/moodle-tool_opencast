<?php 
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use OpencastApi\Opencast;

class OcStatisticsApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $ocRestApi = new Opencast($config);
        $this->ocStatisticsApi = $ocRestApi->statisticsApi;
    }

    /**
     * @test
     * @dataProvider \Tests\DataProvider\StatisticsDataProvider::getAllCases()
     */
    public function get_all_providers($filter, $withparameters): void
    {
        $response = $this->ocStatisticsApi->getAllProviders($filter, $withparameters);

        $this->assertSame(200, $response['code'], 'Failure to get providers list');
    }

    /**
     * @test
     * @dataProvider \Tests\DataProvider\StatisticsDataProvider::getProviderId()
     */
    public function get_provider($identifier): void
    {
        $response = $this->ocStatisticsApi->getProvider($identifier);

        $this->assertContains($response['code'], [200, 404], 'Failure to get provider');
    }

    /**
     * @test
     * @dataProvider \Tests\DataProvider\StatisticsDataProvider::getStatisticalData()
     */
    public function get_statistical_data($data): void
    {
        $this->markTestSkipped('currently skipped as the resources are not completed');
        $response = $this->ocStatisticsApi->getStatisticalData($data);

        $this->assertContains($response['code'], [200, 404], 'Failure to get statistical data');

    }

    /**
     * currently disabled as the resources are not completed
     * @test
     * @dataProvider \Tests\DataProvider\StatisticsDataProvider::getStatisticalDataCVS()
     */
    public function get_statistical_data_cvs($data, $filter, $limit, $offset): void
    {
        $this->markTestSkipped('currently skipped as the resources are not completed');
        $response = $this->ocStatisticsApi->getStatisticalDataCSV($data, $filter, $limit, $offset);

        $this->assertContains($response['code'], [200, 404], 'Failure to get statistical data cvs');
    }
}
?>