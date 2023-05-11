<?php 
namespace Tests\DataProvider;
use GuzzleHttp\Psr7;

class EventsDataProvider {
    
    public static function getAllCases(): array
    {
        return [
            [[]],
            [['sign' => true]],
            [['withacl' => true]],
            [['withmetadata' => true]],
            [['withpublications' => true]],
            [['withscheduling' => true]],
            [['sort' => ['title' => 'DESC']]],
            [['limit' => 2]],
            [['offset' => 1, 'limit' => 2]],
            [['filter' => ['title' => 'test']]],
        ];
    }

    public static function getBySeriesCases(): array
    {
        return [
            ['ID-openmedia-opencast'],
        ];
    }

    public static function createEventCases(): array
    {
        return [
            [self::getAcls(), self::getMetadata('presenter'), self::getProcessing(), '', self::getPresenterFile(), self::getPresentationFile(), self::getAudioFile()]
        ];
    }

    public static function getAcls()
    {
        return '[{"allow":true,"role":"ROLE_ADMIN","action":"write"},{"allow":true,"role":"ROLE_ADMIN","action":"read"},{"allow":true,"role":"ROLE_GROUP_MH_DEFAULT_ORG_EXTERNAL_APPLICATIONS","action":"write"},{"allow":true,"role":"ROLE_GROUP_MH_DEFAULT_ORG_EXTERNAL_APPLICATIONS","action":"read"}]';
    }

    public static function getMetadata($title)
    {
        return '[{"label":"Opencast Series Dublincore","flavor":"dublincore\/episode","fields":[{"id":"title","value":"PHP UNIT TEST_' . strtotime('now') . '_' . strtoupper($title) . '_{update_replace}"},{"id":"subjects","value":["This is default subject"]},{"id":"description","value":"This is a default description for video"},{"id":"startDate","value":"' . date('Y-m-d') . '"},{"id":"startTime","value":"' . date('H:i:s') . 'Z"}]}]';
    }

    public static function getProcessing()
    {
        return '{"workflow":"schedule-and-upload","configuration":{"flagForCutting":"false","flagForReview":"false","publishToEngage":"true","publishToHarvesting":"false","straightToPublishing":"true"}}';
    }

    public static function getPresentationFile()
    {
        return Psr7\Utils::tryFopen(__DIR__   . '/test_files/video_test.mp4', 'r');
    }

    public static function getPresenterFile()
    {
        return Psr7\Utils::tryFopen(__DIR__   . '/test_files/video_test.mp4', 'r');
    }

    public static function getAudioFile()
    {
        return Psr7\Utils::tryFopen(__DIR__   . '/test_files/audio_test.mp3', 'r');
    }

    public static function getVttFile()
    {
        return Psr7\Utils::tryFopen(__DIR__   . '/test_files/video_test_de.vtt', 'r');
    }
}
?>