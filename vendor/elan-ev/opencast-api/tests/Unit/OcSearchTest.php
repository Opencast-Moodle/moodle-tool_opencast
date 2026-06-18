<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\OcTestCase;
use OpencastApi\Opencast;
use OpencastApi\Util\OcUtils;

#[\AllowDynamicProperties]
class OcSearchTest extends OcTestCase
{
    private $ocSearch;

    protected function setUp(): void
    {
        parent::setUp();
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $ocRestApi = new Opencast($config, [], false);
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
     */
    public function find_mediapackage_using_ocutils() {
        $params = ['id' => 'ID-spring'];
        $response = $this->ocSearch->getEpisodes($params);
        $this->assertSame(200, $response['code'], 'Failure to search episode for OcUtils');
        $mediapackage = OcUtils::findValueByKey($response['body'], 'mediapackage');
        $this->assertNotEmpty($mediapackage, 'Cannot extract mediapackage from response using "OcUtils::findValueByKey"');
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

    /**
     * @test
     * @dataProvider \Tests\DataProvider\SearchDataProvider::getLuceneQueryCases()
     */
    public function get_lucenes($params, $format): void
    {
        $response = $this->ocSearch->getLucene($params, $format);
        $this->assertContains($response['code'], [200, 410], 'Failure to create an event');
    }
}
?>
