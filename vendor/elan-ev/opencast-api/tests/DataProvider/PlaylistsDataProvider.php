<?php
namespace Tests\DataProvider;

class PlaylistsDataProvider {

    public static function getAllCases(): array
    {
        return [
            [[]],
            [['sort' => 'updated:DESC']],
            [['limit' => 2]],
            [['offset' => 1]],
        ];
    }

    public static function getPlaylist()
    {
        return '{"title":"Opencast Playlist","description":"PHP UNIT TEST_' . strtotime('now') . '_{update_replace}","creator":"Opencast","entries":[{"contentId":"ID-about-opencast","type":"EVENT"}],"accessControlEntries":[{"allow":true,"role":"ROLE_USER_BOB","action":"read"}]}';
    }

    public static function getEntries()
    {
        return json_decode('[{"contentId":"ID-about-opencast","type":"EVENT"},{"contentId":"ID-3d-print","type":"EVENT"}]');
    }
}
?>
