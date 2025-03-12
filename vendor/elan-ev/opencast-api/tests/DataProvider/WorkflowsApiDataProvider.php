<?php
namespace Tests\DataProvider;

class WorkflowsApiDataProvider {

    public static function getAllDefinitionsCases(): array
    {
        return [
            [[]],
            [['withoperations' => true]],
            [['withconfigurationpanel' => true]],
            [['withconfigurationpaneljson' => true]],
            [['sort' => ['identifier' => 'DESC']]],
            [['limit' => 2]],
            [['offset' => 1]],
            [['filter' => ['tag' => 'schedule']]],
        ];
    }
}
?>
