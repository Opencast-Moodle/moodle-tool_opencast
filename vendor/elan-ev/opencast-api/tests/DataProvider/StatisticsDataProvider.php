<?php 
namespace Tests\DataProvider;

class StatisticsDataProvider {
    
    public static function getAllCases(): array
    {
        return [
            [[], false],
            [[], true],
            [['resourceType' => 'episode'], false],
            [['resourceType' => 'episode'], true],
            [['resourceType' => 'series'], false],
            [['resourceType' => 'series'], true],
            [['resourceType' => 'organization'], false],
            [['resourceType' => 'organization'], true],
        ];
    }

    public static function getProviderId(): array
    {
        return [
            ['a-timeseries-provider']
        ];
    }

    public static function getStatisticalData(): array
    {
        return [
            ['[{"provider":{"identifier":"a-statistics-provider"},"parameters":{"resourceId":"93213324-5d29-428d-bbfd-369a2bae6700"}},{"provider":{"identifier":"a-timeseries-provider"},"parameters":{"resourceId":"23413432-5a15-328e-aafe-562a2bae6800","from":"2019-04-10T13:45:32Z","to":"2019-04-12T00:00:00Z","dataResolution":"daily"}}]']
        ];
    }

    public static function getStatisticalDataCVS(): array
    {
        return [
            ['[]', [], 0, 0],
            ['[{"parameters":{"resourceId":"mh_default_org","detailLevel":"EPISODE","from":"2018-12-31T23:00:00.000Z","to":"2019-12-31T22:59:59.999Z","dataResolution":"YEARLY"},"provider":{"identifier":"organization.views.sum.influx","resourceType":"organization"}}]', [], 0, 0],
            ['[{"parameters":{"resourceId":"mh_default_org","detailLevel":"EPISODE","from":"2018-12-31T23:00:00.000Z","to":"2019-12-31T22:59:59.999Z","dataResolution":"YEARLY"},"provider":{"identifier":"organization.views.sum.influx","resourceType":"organization"}}]', [], 2, 0],
            ['[{"parameters":{"resourceId":"mh_default_org","detailLevel":"EPISODE","from":"2018-12-31T23:00:00.000Z","to":"2019-12-31T22:59:59.999Z","dataResolution":"YEARLY"},"provider":{"identifier":"organization.views.sum.influx","resourceType":"organization"}}]', [], 4, 1],
            ['[{"parameters":{"resourceId":"mh_default_org","detailLevel":"EPISODE","from":"2018-12-31T23:00:00.000Z","to":"2019-12-31T22:59:59.999Z","dataResolution":"YEARLY"},"provider":{"identifier":"organization.views.sum.influx","resourceType":"organization"}}]', ['presenters' => 'Hans Dampf'], 0, 0]
        ];
    }
}
?>