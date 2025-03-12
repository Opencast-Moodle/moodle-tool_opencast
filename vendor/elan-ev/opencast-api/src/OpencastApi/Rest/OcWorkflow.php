<?php
namespace OpencastApi\Rest;

class OcWorkflow extends OcRest
{
    const URI = '/workflow';

    public function __construct($restClient)
    {
        $restClient->registerHeaderException('Accept', self::URI);
        parent::__construct($restClient);
    }

    /**
     * Get the configuration panel for a specific workflow
     *
     * @param string $definitionId (optional) The workflow definition identifier
     *
     * @return array the response result ['code' => 200, 'body' => '{The HTML workflow configuration panel}']
     */
    public function getConfigurationPanel($definitionId = '')
    {
        $uri = self::URI . "/configurationPanel";

        $query = [];
        if (!empty($definitionId)) {
            $query['definitionId'] = $definitionId;
        }

        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }

    /**
     * Returns the number of workflow instances in a specific state and operation
     *
     * @param string $state (optional) The workflow state
     * @param string $operation (optional) The current operation
     *
     * @return array the response result ['code' => 200, 'body' => '{The number of workflow instances}']
     */
    public function getCount($state = '', $operation = '')
    {
        $uri = self::URI . "/count";

        $query = [];
        if (!empty($state)) {
            $query['state'] = $state;
        }
        if (!empty($operation)) {
            $query['operation'] = $operation;
        }

        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }

    /**
     *Returns a single workflow definition as JSON by default or XML on demand
     *
     * @param string $definitionId The workflow definition identifier
     * @param string $format (optional) The output format (json or xml) of the response body. (Default value = 'json')
     *
     * @return array the response result ['code' => 200, 'body' => '{The workflow definition (object JSON| text XML)}']
     */
    public function getSingleDefinition($definitionId, $format = '')
    {
        $uri = self::URI . "/definition/{$definitionId}.json";
        if (!empty($format) && strtolower($format) == 'xml') {
            $uri = str_replace('.json', '.xml', $uri);
        }

        return $this->restClient->performGet($uri);
    }

    /**
     * List all available workflow definitions as JSON or XML on demand (default would be JSON)
     *
     * @param string $format (optional) The output format (json or xml) of the response body. (Default value = 'json')
     *
     * @return array the response result ['code' => 200, 'body' => '{The workflow definitions (object JSON| text XML)}']
     */
    public function getDefinitions($format = '')
    {
        $uri = self::URI . "/definitions.json";
        if (!empty($format) && strtolower($format) == 'xml') {
            $uri = str_replace('.json', '.xml', $uri);
        }

        return $this->restClient->performGet($uri);
    }

    /**
     * List all registered workflow operation handlers (implementations).
     *
     * @return array the response result ['code' => 200, 'body' => '{A JSON (object) representation of the registered workflow operation handlers}']
     */
    public function getHandlers()
    {
        $uri = self::URI . "/handlers.json";
        return $this->restClient->performGet($uri);
    }

    /**
     * Get all workflow state mappings JSON (Object)
     *
     * @return array the response result ['code' => 200, 'body' => '{A JSON (object) representation of the workflow state mappings }']
     */
    public function getStateMappings()
    {
        $uri = self::URI . "/statemappings.json";
        return $this->restClient->performGet($uri);
    }

    /**
     * Get a specific workflow instance as JSON (Object) by default or XLM (text).
     *
     * @param string $instanceId The workflow instance identifier
     * @param string $format (optional) The output format (json or xml) of the response body. (Default value = 'json')
     *
     * @return array the response result ['code' => 200, 'body' => '{A JSON (object) | XML (text) representation of the workflow instance }']
     */
    public function getInstance($instanceId, $format = '')
    {
        $uri = self::URI . "/instance/{$instanceId}.json";
        if (!empty($format) && strtolower($format) == 'xml') {
            $uri = str_replace('.json', '.xml', $uri);
        }
        return $this->restClient->performGet($uri);
    }

    /**
     * (Danger!) Permenantly removes a workflow instance including all its child jobs.
     * In most circumstances, /stop is what you should use.
     *
     * @param string $instanceId The workflow instance identifier
     * @param boolean $force (optional) If the workflow status should be ignored and the workflow removed anyway (Default value=false)
     *
     * @return array the response result ['code' => 204, 'reason' => 'No Content'] (If workflow instance could be removed successfully, no content is returned)
     */
    public function removeInstance($instanceId, $force = false)
    {
        $uri = self::URI . "/remove/{$instanceId}";

        $query = [];
        if (is_bool($force)) {
            $query['force'] = $force;
        }

        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performDelete($uri, $options);
    }

    /**
     * Replaces a suspended workflow instance with an updated version, and resumes the workflow.
     *
     * @param string $instanceId The workflow instance identifier
     * @param array|string $mediapackage (Optional) The new Mediapackage
     * @param array|string $properties (Optional) Properties
     *
     * @return array the response result ['code' => 200, 'body' => '{An XML (as text) representation of the updated and resumed workflow instance}']
     */
    public function replaceAndresume($instanceId, $mediapackage = '', $properties = '')
    {
        $uri = self::URI . "/replaceAndresume";

        $formData = [
            'id' => $instanceId
        ];
        if (!empty($mediapackage)) {
            $formData['mediapackage'] = $mediapackage;
        }
        if (!empty($properties)) {
            $formData['properties'] = $properties;
        }

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Resumes a suspended workflow instance.
     *
     * @param string $instanceId The workflow instance identifier
     *
     * @return array the response result ['code' => 200, 'body' => '{An XML (as text) representation of the resumed workflow instance.}']
     */
    public function resume($instanceId)
    {
        $uri = self::URI . "/resume";

        $formData = [
            'id' => $instanceId
        ];

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Start a new workflow instance.
     *
     * @param string $definition The workflow definition ID or an XML representation of a workflow definition
     * @param string $mediapackage The XML representation of a mediapackage
     * @param string $parent (Optional) An optional parent workflow instance identifier
     * @param string|array $properties (Optional) An optional set of key=value properties
     *
     * @return array the response result ['code' => 200, 'body' => '{An XML (as text) representation of the new workflow instance.}']
     */
    public function start($definition, $mediapackage, $parent = '', $properties = '')
    {
        $uri = self::URI . "/start";

        $formData = [
            'definition' => $definition,
            'mediapackage' => $mediapackage
        ];
        if (!empty($parent)) {
            $formData['parent'] = $parent;
        }
        if (!empty($properties)) {
            $formData['properties'] = $properties;
        }

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Stops a workflow instance.
     *
     * @param string $instanceId The workflow instance identifier
     *
     * @return array the response result ['code' => 200, 'body' => '{An XML (as text) representation of the stopped workflow instance.}']
     */
    public function stop($instanceId)
    {
        $uri = self::URI . "/stop";

        $formData = [
            'id' => $instanceId
        ];

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Suspends a workflow instance.
     *
     * @param string $identifier The workflow instance identifier
     *
     * @return array the response result ['code' => 200, 'body' => '{An XML (as text) representation of the suspended workflow instance.}']
     */
    public function suspend($instanceId)
    {
        $uri = self::URI . "/suspend";

        $formData = [
            'id' => $instanceId
        ];

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Updates a workflow instance.
     *
     * @param string $workflow The XML representation of the workflow instance.
     *
     * @return array the response result ['code' => 204, 'body' => '', 'reason' => 'No Content'] (Workflow instance updated)
     */
    public function update($workflow)
    {
        $uri = self::URI . "/update";

        $formData = [
            'workflow' => $workflow
        ];

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }
}
?>
