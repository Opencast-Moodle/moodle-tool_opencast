<?php 
namespace Tests\DataProvider;

class WorkflowsApiDataProvider {
    
    public static function getAllCases(): array
    {
        return [
            [[]],
            [['withoperations' => true]],
            [['withconfiguration' => true]],
            [['sort' => ['workflow_definition_identifier' => 'DESC']]],
            [['limit' => 2]],
            [['offset' => 1]],
            [['filter' => ['workflow_definition_identifier' => 'fast']]],
        ];
    }
}
?>