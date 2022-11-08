<?php 
namespace Tests\DataProvider;

class SeriesDataProvider {
    
    public static function getAllCases(): array
    {
        return [
            [[]],
            [['withacl' => true]],
            [['sort' => ['title' => 'DESC']]],
            [['limit' => 2]],
            [['offset' => 1]],
            [['filter' => ['title' => 'test']]],
        ];
    }

    public static function getAcl()
    {
        return '[{"allow":true,"role":"ROLE_ADMIN","action":"write"},{"allow":true,"role":"ROLE_ADMIN","action":"read"},{"allow":true,"role":"ROLE_GROUP_MH_DEFAULT_ORG_EXTERNAL_APPLICATIONS","action":"write"},{"allow":true,"role":"ROLE_GROUP_MH_DEFAULT_ORG_EXTERNAL_APPLICATIONS","action":"read"}]';
    }

    public static function getMetadata()
    {
        return '[{"label":"Opencast Series Dublincore","flavor":"dublincore\/series","fields":[{"id":"title","value":"PHP UNIT TEST_' . strtotime('now') . '_{update_replace}"},{"id":"subjects","value":["This is default subject"]},{"id":"description","value":"This is a default description for series"}]}]';
    }

    public static function getDCMetadata()
    {
        return '[{"id":"title","value":"PHP UNIT TEST_' . strtotime('now') . '_{update_replace}"},{"id":"subject","value":""},{"id":"description","value":"aaa"},{"id":"language","value":""},{"id":"rightsHolder","value":""},{"id":"license","value":""},{"id":"creator","value":[]},{"id":"contributor","value":[]}]';
    }

    public static function getTheme()
    {
        return '';
    }

    public static function getProperties()
    {
        return '{"ondemand": "true","live": "false"}';
    }
}
?>