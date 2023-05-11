<?php 
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use OpencastApi\Opencast;

class OcWorkflowsApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $ocRestApi = new Opencast($config);
        $this->ocWorkflowsApi = $ocRestApi->workflowsApi;
        $this->ocEventsApi = $ocRestApi->eventsApi;
    }

    /**
     * @test
     * @dataProvider \Tests\DataProvider\WorkflowsApiDataProvider::getAllCases()
     */
    public function get_all_workflows($params): void
    {
        $this->markTestSkipped('Depricated Endpoint (removed from Opencast v12.x)');
        $response = $this->ocWorkflowsApi->getAll($params);

        $this->assertSame(200, $response['code'], 'Failure to get workflows list');
    }


    /**
     * @test
     */
    public function get_definition_run_update_delete_workflow(): void
    {
        $data = [];
        // Get event
        $response0 = $this->ocEventsApi->getAll();
        $this->assertSame(200, $response0['code'], 'Failure to get events for the workflows!');
        $events = $response0['body'];
        $event = $events[array_rand($events)];
        $this->assertNotEmpty($event);
        $data['event_identifier'] = $event->identifier;

        // Get workflow definitions.
        $response1 = $this->ocWorkflowsApi->getAllDefinitions();
        $this->assertSame(200, $response1['code'], 'Failure to get workflow definitions');
        $definitions = $response1['body'];
        $this->assertNotEmpty($definitions);

        // Get the single definition.
        $filter = array_filter($definitions, function ($wfd) {
            return $wfd->identifier == 'republish-metadata';
        });
        $definition = $filter[array_keys($filter)[0]];
        $response2 = $this->ocWorkflowsApi->getDefinition($definition->identifier, true, true);
        $this->assertSame(200, $response2['code'], 'Failure to get single workflow definition');
        $definition = $response2['body'];
        $this->assertNotEmpty($definition);
        $data['workflow_definition_identifier'] = $definition->identifier;

        
        // Create (run) Workflow.
        $response3 = $this->ocWorkflowsApi->run(
            $data['event_identifier'],
            $data['workflow_definition_identifier'],
        );
        $this->assertSame(201, $response3['code'], 'Failure to create (run) a workflow');
        $workflowId = $response3['body'];
        $this->assertNotEmpty($workflowId);

        // Get the workflow.
        $response4 = $this->ocWorkflowsApi->get($workflowId->identifier, true, true);
        $this->assertSame(200, $response4['code'], 'Failure to get a workflow');

        // Update workflow.
        $response5 = $this->ocWorkflowsApi->update($workflowId->identifier, 'stopped');
        $this->assertSame(200, $response5['code'], 'Failure to update a workflow');

        // Delete the workflow.
        $response6 = $this->ocWorkflowsApi->delete($workflowId->identifier);
        $this->assertSame(204, $response6['code'], 'Failure to delete a workflow');
    }
}
?>