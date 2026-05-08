<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use OpencastApi\Opencast;

class OcWorkflowsApiTest extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $ocRestApi = new Opencast($config, [], false);
        $this->ocWorkflowsApi = $ocRestApi->workflowsApi;
        $this->ocEventsApi = $ocRestApi->eventsApi;
    }

    /**
     * @test
     */
    public function get_definition_run_update_delete_workflow(): void {
        $data = [];
        // Get event
        $response0 = $this->ocEventsApi->getAll(
            ['withpublications' => true]
        );
        $this->assertSame(200, $response0['code'], 'Failure to get events for the workflows!');
        $events = $response0['body'];
        $event = null;
        foreach ($events as $ev) {
            if ($ev->status === "EVENTS.EVENTS.STATUS.PROCESSED") {
                $event = $ev;
                break;
            }
        }
        if (empty($event)) {
            $this->markTestSkipped('No proper event found to apply workflow!');
        }
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
        sleep(1);

        // Get the workflow.
        $response4 = $this->ocWorkflowsApi->get($workflowId->identifier, true, true);
        $this->assertSame(200, $response4['code'], 'Failure to get a workflow');
        sleep(1);

        // Update workflow.
        $response5 = $this->ocWorkflowsApi->update($workflowId->identifier, 'stopped');
        $this->assertSame(200, $response5['code'], 'Failure to update a workflow');
        sleep(1);

        // Delete the workflow.
        $response6 = $this->ocWorkflowsApi->delete($workflowId->identifier);
        $this->assertSame(204, $response6['code'], 'Failure to delete a workflow');
        sleep(1);
    }

    /**
     * @test
     * @dataProvider \Tests\DataProvider\WorkflowsApiDataProvider::getAllDefinitionsCases()
     */
    public function get_all_definitions($params): void {
        $response = $this->ocWorkflowsApi->getAllDefinitions($params);
        $this->assertSame(200, $response['code'], 'Failure to get workflows list');
    }

    /**
     * @test
     * This test is meant to check the integrity of the response body, to make sure it contains the correct properties.
     */
    public function get_single_definition_with_parameters(): void {
        $response = $this->ocWorkflowsApi->getDefinition(
            'fast',
            true,
            true,
            true
        );
        $this->assertSame(200, $response['code'], 'Failure to get "fast" workflow');
        $bodyArray = json_decode(json_encode($response['body']), true);
        $this->assertNotEmpty($bodyArray, 'Response body array is empty');

        // Check for operations
        $this->assertArrayHasKey('operations', $bodyArray, 'No configuration_panel is defined');

        // Check for config panel
        $this->assertArrayHasKey('configuration_panel', $bodyArray, 'No configuration_panel is defined');

        // Check for config panel json
        $this->assertArrayHasKey('configuration_panel_json', $bodyArray, 'No configuration_panel_json is defined');

        // Check for title
        $this->assertArrayHasKey('title', $bodyArray, 'No configuration_panel_json is defined');

        // Check for tags
        $this->assertArrayHasKey('tags', $bodyArray, 'No configuration_panel_json is defined');

        // Check for description
        $this->assertArrayHasKey('description', $bodyArray, 'No configuration_panel_json is defined');

        // Check for identifier
        $this->assertArrayHasKey('identifier', $bodyArray, 'No configuration_panel_json is defined');
    }
}
