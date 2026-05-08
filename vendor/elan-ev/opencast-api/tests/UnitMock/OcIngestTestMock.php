<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use OpencastApi\Opencast;
use OpencastApi\Mock\OcMockHanlder;

class OcIngestTestMock extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        $mockResponse = \Tests\DataProvider\SetupDataProvider::getMockResponses('ingests');
        if (empty($mockResponse)) {
            $this->markTestIncomplete('No mock responses for ingests could be found!');
        }
        $mockHandler = OcMockHanlder::getHandlerStackWithPath($mockResponse);
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $config['handler'] = $mockHandler;
        $ocRestApi = new Opencast($config, [], true);
        $this->ocIngest = $ocRestApi->ingest;
    }

    /**
     * @test
     */
    public function empty_ingest_data(): array {
        $ingestData = [];
        $this->assertEmpty($ingestData);

        return $ingestData;
    }

    /**
     * @test
     * @depends empty_ingest_data
     */
    public function get_mediapackage_create_series(array $ingestData): array {

        $responseCreateMediaPackage = $this->ocIngest->createMediaPackage();
        $this->assertSame(200, $responseCreateMediaPackage['code'], 'Failure to get mediaPackage');
        $mediaPackage = $responseCreateMediaPackage['body'];
        $this->assertNotEmpty($mediaPackage);
        $ingestData['mediaPackage'] = $mediaPackage;

        $this->assertNotEmpty($ingestData);
        return $ingestData;
    }


    /**
     * @test
     * @depends get_mediapackage_create_series
     */
    public function add_catalog_all(array $ingestData): array {
        $flavor = 'dublincore/episode';

        // Add Catalog with file
        if ($episodeXmlFile = \Tests\DataProvider\IngestDataProvider::getEpisodeXMLFile()) {
            $responseAddCatalogFile = $this->ocIngest->addCatalog($ingestData['mediaPackage'], $flavor, $episodeXmlFile);
            $this->assertSame(200, $responseAddCatalogFile['code'], 'Failure to add catalog file ingest');
            $mediaPackage = $responseAddCatalogFile['body'];
            $this->assertNotEmpty($mediaPackage);
        }

        // Add DC Catalog.
        if ($dcCatalog = \Tests\DataProvider\IngestDataProvider::getDCCatalog()) {
            $responseAddDCCatalog = $this->ocIngest->addDCCatalog($ingestData['mediaPackage'], $dcCatalog);
            $this->assertSame(200, $responseAddDCCatalog['code'], 'Failure to add DC catalog ingest');
            $mediaPackage = $responseAddDCCatalog['body'];
            $this->assertNotEmpty($mediaPackage);
        }

        // Add Catalog with url
        if ($url = \Tests\DataProvider\IngestDataProvider::getCatalogURL()) {
            $responseAddCatalogUrl = $this->ocIngest->addCatalogUrl($ingestData['mediaPackage'], $flavor, $url);
            $this->assertSame(200, $responseAddCatalogUrl['code'], 'Failure to add catalog url ingest');
            $mediaPackage = $responseAddCatalogUrl['body'];
            $this->assertNotEmpty($mediaPackage);
        }

        $ingestData['mediaPackage'] = $mediaPackage;

        $this->assertNotEmpty($ingestData);
        return $ingestData;
    }

    /**
     * @test
     * @depends add_catalog_all
     */
    public function add_presenter_track(array $ingestData): array {
        $flavor = 'presenter/source';
        // Add track file
        $responseAddTrackPresenter = $this->ocIngest->addTrack(
            $ingestData['mediaPackage'],
            $flavor,
            \Tests\DataProvider\IngestDataProvider::getPresenterFile(),
            '',
            [$this, 'progressCallback']
        );
        $this->assertSame(200, $responseAddTrackPresenter['code'], 'Failure to add presenter track ingest');
        $mediaPackage = $responseAddTrackPresenter['body'];
        $this->assertNotEmpty($mediaPackage);

        // Add track url
        if ($url = \Tests\DataProvider\IngestDataProvider::getPresenterUrl()) {
            $responseAddTrackPresenterUrl = $this->ocIngest->addTrackUrl(
                $ingestData['mediaPackage'],
                $flavor,
                $url
            );
            $this->assertSame(200, $responseAddTrackPresenterUrl['code'], 'Failure to add presenter track url ingest');
            $mediaPackage = $responseAddTrackPresenterUrl['body'];
            $this->assertNotEmpty($mediaPackage);
        }

        $ingestData['mediaPackage'] = $mediaPackage;

        $this->assertNotEmpty($ingestData);
        return $ingestData;
    }

    public function progressCallback($downloadSize, $downloaded, $uploadSize, $uploaded) {
        set_time_limit(0);// Reset time limit for big files
        static $previous_progress = 0;
        $progress = 0;
        if ($uploadSize > 0) {
            $progress = round(($uploaded / $uploadSize) * 100);
        }
        if ($progress > $previous_progress) {
            $previous_progress = $progress;
            file_put_contents(__DIR__ . '/../Results/progress_ingest.txt', $progress);
        }
    }

    /**
     * @test
     * @depends add_presenter_track
     */
    public function add_presentation_track(array $ingestData): array {
        $flavor = 'presentation/source';
        $responseAddTrackPresentation = $this->ocIngest->addTrack(
            $ingestData['mediaPackage'],
            $flavor,
            \Tests\DataProvider\IngestDataProvider::getPresentationFile()
        );
        $this->assertSame(200, $responseAddTrackPresentation['code'], 'Failure to add presentation track ingest');
        $mediaPackage = $responseAddTrackPresentation['body'];
        $this->assertNotEmpty($mediaPackage);

        // Add track url
        if ($url = \Tests\DataProvider\IngestDataProvider::getPresentationUrl()) {
            $responseAddTrackPresentationUrl = $this->ocIngest->addTrackUrl(
                $ingestData['mediaPackage'],
                $flavor,
                $url
            );
            $this->assertSame(200, $responseAddTrackPresentationUrl['code'], 'Failure to add presentation track url ingest');
            $mediaPackage = $responseAddTrackPresentationUrl['body'];
            $this->assertNotEmpty($mediaPackage);
        }

        $ingestData['mediaPackage'] = $mediaPackage;

        $this->assertNotEmpty($ingestData);
        return $ingestData;
    }

    /**
     * @test
     * @depends add_presentation_track
     */
    public function add_attachment_all(array $ingestData): array {
        $flavor = 'security/xacml+episode';
        // Add attachment file
        if ($episodeAclXmlFile = \Tests\DataProvider\IngestDataProvider::getEpisodeAclXMLFile()) {
            $responseAddAttachment = $this->ocIngest->addAttachment($ingestData['mediaPackage'], $flavor, $episodeAclXmlFile);
            $this->assertSame(200, $responseAddAttachment['code'], 'Failure to add attachment file ingest');
            $mediaPackage = $responseAddAttachment['body'];
            $this->assertNotEmpty($mediaPackage);
        }

        // Add attachment url
        if ($url = \Tests\DataProvider\IngestDataProvider::getAttachmentURL()) {
            $responseAddAttachmentUrl = $this->ocIngest->addAttachmentUrl($ingestData['mediaPackage'], $flavor, $url);
            $this->assertSame(200, $responseAddAttachmentUrl['code'], 'Failure to add attachment url ingest');
            $mediaPackage = $responseAddAttachmentUrl['body'];
            $this->assertNotEmpty($mediaPackage);
        }

        $ingestData['mediaPackage'] = $mediaPackage;

        $this->assertNotEmpty($ingestData);
        return $ingestData;
    }

    /**
     * @test
     * @depends add_attachment_all
     */
    public function ingest(array $ingestData): void {
        $workflowDefinitionId = 'schedule-and-upload';
        $responseIngest = $this->ocIngest->ingest($ingestData['mediaPackage'], $workflowDefinitionId);
        $this->assertSame(200, $responseIngest['code'], 'Failure to ingest');
        $mediaPackage = $responseIngest['body'];
        $this->assertNotEmpty($mediaPackage);
        $ingestData['mediaPackage'] = $mediaPackage;
    }
}
