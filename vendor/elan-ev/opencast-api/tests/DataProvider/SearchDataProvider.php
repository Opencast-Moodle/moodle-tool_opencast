<?php
namespace Tests\DataProvider;

class SearchDataProvider {

    public static function getEpisodeQueryCases(): array
    {
        return [
            [[], 'json'],
            [['id' => 'ID-spring'], ''],
            [['sid' => '8010876e-1dce-4d38-ab8d-24b956e3d8b7'], ''],
            [['sname' => 'HUB_LOCAL_TEST'], ''],
            [['sort' => 'modified asc'], ''],
            [['offset' => 1], ''],
            [['limit' => 1], ''],
            [['admin' => true], ''],
            [['sign' => true], ''],
        ];
    }

    public static function getLuceneQueryCases(): array
    {
        return [
            [[], 'json'],
            [[], 'xml'],
            [[], 'XML'],
            [['series' => true], ''],
            [['sort' => 'DATE_CREATED_DESC'], ''],
            [['offset' => 1], ''],
            [['limit' => 1], ''],
            [['admin' => true], ''],
            [['sign' => true], ''],
        ];
    }

    public static function getSeriesQueryCases(): array
    {
        return [
            [[], 'json'],
            [['id' => '8010876e-1dce-4d38-ab8d-24b956e3d8b7'], ''],
            [['episodes' => true], ''],
            [['sort' => 'modified desc'], ''],
            [['offset' => 1], ''],
            [['limit' => 1], ''],
            [['admin' => true], ''],
            [['sign' => true], ''],
        ];
    }
}
?>
