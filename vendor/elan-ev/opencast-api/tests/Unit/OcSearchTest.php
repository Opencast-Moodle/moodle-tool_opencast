<?php 
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use OpencastApi\Opencast;

class OcSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $ocRestApi = new Opencast($config);
        $this->ocSearch = $ocRestApi->search;
    }

    /**
     * @test
     * @dataProvider \Tests\DataProvider\SearchDataProvider::getEpisodeQueryCases()
     */
    public function get_eposides($params, $format): void
    {
        $response = $this->ocSearch->getEpisodes($params, $format);
        $this->assertSame(200, $response['code'], 'Failure to search episode');
    }

    /**
     * @test
     * @dataProvider \Tests\DataProvider\SearchDataProvider::getLuceneQueryCases()
     */
    public function get_lucenes($params, $format): void
    {
        $response = $this->ocSearch->getLucene($params, $format);
        $this->assertSame(200, $response['code'], 'Failure to search lucene');
    }

    /**
     * @test
     * @dataProvider \Tests\DataProvider\SearchDataProvider::getSeriesQueryCases()
     */
    public function get_series($params, $format): void
    {
        $response = $this->ocSearch->getSeries($params, $format);
        $this->assertSame(200, $response['code'], 'Failure to search series');
    }
}
?>