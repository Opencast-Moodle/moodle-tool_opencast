<?php 
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use OpencastApi\Opencast;

class OcWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $config = \Tests\DataProvider\SetupDataProvider::getConfig();
        $ocRestApi = new Opencast($config);
        $this->ocWorkflow = $ocRestApi->workflow;
    }

    /**
     * @test
     */
    public function getters_test(): void
    {
        // Get all Definitions
        $responseGetDefinitions = $this->ocWorkflow->getDefinitions();
        $this->assertSame(200, $responseGetDefinitions['code'], 'Failure to get workflow definitions');
        $definitions = $responseGetDefinitions['body']->definitions->definition;
        $this->assertNotEmpty($definitions);

        // Get single definition.
        $definition = $definitions[array_rand($definitions)];
        $responseGetSingleDefinition = $this->ocWorkflow->getSingleDefinition($definition->id);
        $this->assertSame(200, $responseGetSingleDefinition['code'], 'Failure to get single workflow definition');
        $definition = $responseGetSingleDefinition['body']->definition;
        $this->assertNotEmpty($definition);

        // Get Count
        $responseGetCount = $this->ocWorkflow->getCount();
        $this->assertSame(200, $responseGetCount['code'], 'Failure to get count');

        // Get Configuration Panel
        $responseGetConfigurationPanel = $this->ocWorkflow->getConfigurationPanel($definition->id);
        $this->assertSame(200, $responseGetConfigurationPanel['code'], 'Failure to get Configuration Panel');

        // Get Handlers
        $responseGetHandlers = $this->ocWorkflow->getHandlers();
        $this->assertSame(200, $responseGetHandlers['code'], 'Failure to get Handlers');

        // Get State Mappings
        $responseGetStateMappings = $this->ocWorkflow->getStateMappings();
        $this->assertSame(200, $responseGetStateMappings['code'], 'Failure to get State Mappings');

        // Get statistics @depricated
        // $responseGetStatistics = $this->ocWorkflow->getStatistics();
        // $this->assertSame(200, $responseGetStatistics['code'], 'Failure to get statistics');

        // Get All Instances  @depricated
        // $responseGetInstances = $this->ocWorkflow->getInstances();
        // $this->assertSame(200, $responseGetInstances['code'], 'Failure to get workflow Instances');
        // $instances = $responseGetInstances['body']->workflows->workflow;
        // $this->assertNotEmpty($instances);

        // Get Single Instance
        $dummyInstanceId = 1234567890;
        $responseGetInstance = $this->ocWorkflow->getInstance($dummyInstanceId);
        $this->assertContains($responseGetInstance['code'], [200, 404], 'Failure to get single workflow Instance');
        if ($responseGetInstance['code'] == 200) {
            $instance = $responseGetInstance['body']->workflow;
            $this->assertNotEmpty($instance);
        }
    }

    /**
     * @test
     */
    public function setters_test(): void
    {
        // start
        $mediaPackage = \Tests\DataProvider\WorkflowDataProvider::getDummyMediapackage();
        $workflowDefinitionId = 'schedule-and-upload';
        $responseStart = $this->ocWorkflow->start($workflowDefinitionId, $mediaPackage);
        $this->assertSame(200, $responseStart['code'], 'Failure to start Workflow');
        $workflow = new \SimpleXMLElement($responseStart['body'], 0, false);
        $workflowInstanceId = (string) $workflow['id'][0];
        $this->assertNotEmpty($workflowInstanceId);

        // suspend
        $responseSuspend = $this->ocWorkflow->suspend($workflowInstanceId);
        $this->assertSame(200, $responseSuspend['code'], 'Failure to suspend Workflow');
        $workflow = new \SimpleXMLElement($responseSuspend['body'], 0, false);
        $workflowInstanceId = (string) $workflow['id'][0];
        $this->assertNotEmpty($workflowInstanceId);

        // resume
        $responseResume = $this->ocWorkflow->resume($workflowInstanceId);
        $this->assertSame(200, $responseResume['code'], 'Failure to perform resume Workflow');
        $workflow = new \SimpleXMLElement($responseResume['body'], 0, false);
        $workflowInstanceId = (string) $workflow['id'][0];
        $this->assertNotEmpty($workflowInstanceId);

        // stop
        $responseStop = $this->ocWorkflow->stop($workflowInstanceId);
        $this->assertSame(200, $responseStop['code'], 'Failure to stop Workflow');
        $workflow = new \SimpleXMLElement($responseStop['body'], 0, false);
        $workflowStr = $responseStop['body'];
        $workflowInstanceId = (string) $workflow['id'][0];
        $this->assertNotEmpty($workflowInstanceId);

        // update
        $responseUpdate = $this->ocWorkflow->update($workflowStr);
        $this->assertContains($responseUpdate['code'], [204, 500], 'Failure to update Workflow');

        // replaceAndresume
        $responseReplaceAndresume = $this->ocWorkflow->replaceAndresume($workflowInstanceId);
        $this->assertContains($responseReplaceAndresume['code'], [200, 409], 'Failure to perform replaceAndresume Workflow');
        if ($responseReplaceAndresume['code'] == 200) {
            $workflow = new \SimpleXMLElement($responseReplaceAndresume['body'], 0, false);
            $workflowInstanceId = (string) $workflow['id'][0];
            $this->assertNotEmpty($workflowInstanceId);
        }

        // delete
        $responseDelete = $this->ocWorkflow->removeInstance($workflowInstanceId);
        $this->assertSame(204, $responseDelete['code'], 'Failure to delete Workflow');
    }


}
?>