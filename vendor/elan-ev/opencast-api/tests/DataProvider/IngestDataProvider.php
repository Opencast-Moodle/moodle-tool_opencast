<?php 
namespace Tests\DataProvider;
use GuzzleHttp\Psr7;

class IngestDataProvider {
    
    public static function getAllCases(): array
    {
        return [
            [false, false, [], 0, 0, []],
            [true, false, [], 0, 0, []],
            [true, true, [], 0, 0, []],
            [true, true, ['workflow_definition_identifier' => 'DESC'], 0, 0, []],
            [true, true, ['workflow_definition_identifier' => 'DESC'], 2, 0, []],
            [true, true, ['workflow_definition_identifier' => 'DESC'], 2, 1, []],
            [true, true, [], 0, 0, ['workflow_definition_identifier' => 'fast']],
        ];
    }

    public static function getDCCatalog()
    {
        return file_get_contents(__DIR__   . '/test_files/dublincore-episode.xml');
    }

    public static function getEpisodeXMLFile()
    {
        return Psr7\Utils::tryFopen(__DIR__   . '/test_files/dublincore-episode.xml', 'r');
    }

    public static function getPresentationFile()
    {
        return Psr7\Utils::tryFopen(__DIR__   . '/test_files/video_test.mp4', 'r');
    }

    public static function getPresentationUrl()
    {
        return '';
    }

    public static function getPresenterFile()
    {
        return Psr7\Utils::tryFopen(__DIR__   . '/test_files/video_test.mp4', 'r');
    }

    public static function getPresenterUrl()
    {
        return '';
    }

    public static function getEpisodeAclXMLFile()
    {
        return Psr7\Utils::tryFopen(__DIR__   . '/test_files/xacml-episode.xml', 'r');
    }

    public static function getCatalogURL()
    {
        return '';
    }

    public static function getAttachmentURL()
    {
        return '';
    }

    public static function getTrackURL()
    {
        return '';
    }
}
?>