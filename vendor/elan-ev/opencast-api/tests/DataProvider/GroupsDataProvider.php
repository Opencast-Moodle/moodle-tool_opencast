<?php 
namespace Tests\DataProvider;

class GroupsDataProvider {
    
    public static function getAllCases(): array
    {
        return [
            [['name' => 'DESC'], 0, 0, []],
            [[], 4, 0, []],
            [[], 2, 2, []],
            [[], 0, 0, ['name' => '"phpunit_testing_group"']],
        ];
    }
}
?>