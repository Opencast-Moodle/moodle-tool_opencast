<?php 
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use OpencastApi\Opencast;
use \OpencastApi\Mock\OcMockHanlder;

class OcSeriesApiTestMock extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $mockResponse = \Tests\DataProvider\SetupDataProvider::getMockResponses('api_series');
        if (empty($mockResponse)) {
            $this->markTestIncomplete('No mock responses for series api could be found!');
        }
        $mockHandler = OcMockHanlder::getHandlerStackWithPath($mockResponse);
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $config['handler'] = $mockHandler;
        $ocRestApi = new Opencast($config);
        $this->ocSeriesApi = $ocRestApi->seriesApi;
    }

    /**
     * @test
     */
    public function get_all_series(): void
    {
        $response = $this->ocSeriesApi->getAll();
        $this->assertSame(200, $response['code'], 'Failure to get series list');
    }

    /**
     * @test
     */
    public function get_all_series_with_roles(): void
    {
        $params = [
            'onlyWithWriteAccess' => true
        ];
        $response = $this->ocSeriesApi->runWithRoles(['ROLE_ADMIN'])->getAll($params);

        $this->assertSame(200, $response['code'], 'Failure to get series list');
    }

    /**
     * @test
     */
    public function empty_created_id(): string
    {
        $createdSeriesIdentifier = '';
        $this->assertEmpty($createdSeriesIdentifier);

        return $createdSeriesIdentifier;
    }

    /**
     * @test
     * @depends empty_created_id
     */
    public function create_get_series(string $identifier): string
    {
        // Create Series.
        $response1 = $this->ocSeriesApi->create(
            \Tests\DataProvider\SeriesDataProvider::getMetadata(),
            \Tests\DataProvider\SeriesDataProvider::getAcl(),
            \Tests\DataProvider\SeriesDataProvider::getTheme(),
        );
        $this->assertSame(201, $response1['code'], 'Failure to create a series');

        $identifier = $response1['body']->identifier;

        // Get the series.
        $response2 = $this->ocSeriesApi->get($identifier, true);
        $this->assertSame(200, $response2['code'], 'Failure to get a series');

        $this->assertNotEmpty($identifier);
        return $identifier;
    }

    /**
     * @test
     * @depends create_get_series
     */
    public function get_update_delete_series_metadata(string $identifier): string
    {
        // Get metadata.
        $response1 = $this->ocSeriesApi->getMetadata($identifier);
        $this->assertSame(200, $response1['code'], 'Failure to get series metadata');
        $metadataAll = $response1['body'];
        $this->assertNotEmpty($metadataAll);
        
        // Update all metadata.
        $metadata = str_replace(
            '{update_replace}',
            'ALL: UPDATED ON: ' . strtotime('now'),
            json_encode($metadataAll)
        );
        $response3 = $this->ocSeriesApi->updateAllMetadata($identifier, $metadata);
        $this->assertSame(200, $response3['code'], 'Failure to update series metadata');

        // Get type metadata.
        $type = 'dublincore/series';
        $response2 = $this->ocSeriesApi->getMetadata($identifier, $type);
        $this->assertSame(200, $response2['code'], 'Failure to get type series metadata');
        $metadata = $response2['body'];
        $this->assertNotEmpty($metadata);

        // Update type metadata.
        $dcMetadata = str_replace(
            '{update_replace}',
            '(UPDATED WITH TYPE)',
            \Tests\DataProvider\SeriesDataProvider::getDCMetadata()
        );

        $response4 = $this->ocSeriesApi->updateMetadata($identifier, $dcMetadata, $type);
        $this->assertSame(200, $response4['code'], 'Failure to update series metadata');
        
        // Delete metadata.
        $response5 = $this->ocSeriesApi->deleteMetadata($identifier, $type);
        $this->assertSame(403, $response5['code'], 'Failure to delete type metadata of a series');

        $this->assertNotEmpty($identifier);
        return $identifier;
    }

    /**
     * @test
     * @depends get_update_delete_series_metadata
     */
    public function get_update_delete_acls(string $identifier): string
    {
        // Get ACL.
        $response1 = $this->ocSeriesApi->getAcl($identifier);
        $this->assertSame(200, $response1['code'], 'Failure to get ACLs of a series');

        $acls = $response1['body'];
        $this->assertNotEmpty($acls);

        // Delete all acls.
        $response2 =  $this->ocSeriesApi->emptyAcl($identifier);
        $this->assertSame(200, $response2['code'], 'Failure to delete ACLs of a series');

        // Prepare to update acls.
        $response3 = $this->ocSeriesApi->updateAcl($identifier, $acls);
        $this->assertSame(200, $response3['code'], 'Failure to update ACLs of a series');

        $this->assertNotEmpty($identifier);
        return $identifier;
    }

    /**
     * @test
     * @depends get_update_delete_acls
     */
    public function add_get_update_properties(string $identifier): string
    {
        // Add Properties.
        $response1 = $this->ocSeriesApi->addProperties(
            $identifier,
            \Tests\DataProvider\SeriesDataProvider::getProperties()
        );
        $this->assertSame(200, $response1['code'], 'Failure to add Properties to a series');
        $properties = $response1['body'];
        $this->assertNotEmpty($properties);

        // Get Properties.
        $response2 =  $this->ocSeriesApi->getProperties($identifier);
        $this->assertSame(200, $response2['code'], 'Failure to get Properties of a series');
        $property = $response2['body'];
        $this->assertNotEmpty($property);

        // Update Properties.
        $property->theme = 1000;
        $response3 = $this->ocSeriesApi->updateProperties($identifier, $property);
        $this->assertSame(200, $response3['code'], 'Failure to update Properties of a series');
        $properties = $response3['body'];
        $this->assertNotEmpty($properties);

        $this->assertNotEmpty($identifier);
        return $identifier;
    }

    /**
     * @test
     * @depends add_get_update_properties
     */
    public function delete_series(string $identifier): void
    {
        $response = $this->ocSeriesApi->delete($identifier);
        $this->assertSame(204, $response['code'], 'Failure to delete a series');
    }
}
?>