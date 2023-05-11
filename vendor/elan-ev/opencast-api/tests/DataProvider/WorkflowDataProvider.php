<?php 
namespace Tests\DataProvider;

class WorkflowDataProvider {
    
    public static function getDummyMediapackage(): string
    {
        return file_get_contents(__DIR__ . '/../DataProvider/test_files/ingest.xml');
    }
}
?>