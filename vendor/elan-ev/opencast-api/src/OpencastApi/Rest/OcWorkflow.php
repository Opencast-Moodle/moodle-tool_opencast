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
     * Returns the workflow statistics as JSON (Object) by default or XLM (text)
     * 
     * @param string $format (optional) The output format (json or xml) of the response body. (Default value = 'json')
     *
     * @return array the response result ['code' => 200, 'body' => '{A JSON (object) | XML (text) representation of the workflow statistics }']
     * @deprecated from version v1.3 and will be removed in v1.4
     */
    public function getStatistics($format = '')
    {
        $uri = self::URI . "/statistics.json";
        if (!empty($format) && strtolower($format) == 'xml') {
            $uri = str_replace('.json', '.xml', $uri);
        }
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
     * List all workflow instances matching the query parameters as JSON (Object) by default or XLM (text).
     *
     * @param array $params (optional) The list of query params to pass which can contain the followings:
     * [
     *      'state' => '{Filter results by workflows' current state}',
     *      'q' => '{Filter results by free text query}',
     *      'seriesId' => '{ Filter results by series identifier }',
     *      'seriesTitle' => '{ Filter results by series title }',
     *      'creator' => '{ Filter results by the mediapackage's creator }',
     *      'contributor' => '{Filter results by the mediapackage's contributor}',
     *      'fromdate' => '{Filter results by workflow start date}',
     *      'todate' => '{Filter results by workflow start date}',
     *      'language' => '{Filter results by mediapackage's language.}',
     *      'license' => '{Filter results by mediapackage's license}',
     *      'title' => '{Filter results by mediapackage's title}',
     *      'subject' => '{Filter results by mediapackage's subject}',
     *      'workflowdefinition' => '{Filter results by workflow definition}',
     *      'mp' => '{Filter results by mediapackage identifier.}',
     *      'op' => '{ Filter results by workflows' current operation}',
     *      'sort' => '{The sort order. May include any of the following: DATE_CREATED, TITLE, SERIES_TITLE, SERIES_ID, MEDIA_PACKAGE_ID, WORKFLOW_DEFINITION_ID, CREATOR, CONTRIBUTOR, LANGUAGE, LICENSE, SUBJECT. Add '_DESC' to reverse the sort order (e.g. TITLE_DESC)}',
     *      'startPage' => '{(Default value=0): The paging offset }',
     *      'count' => '{(Default value=0): The number of results to return.}',
     *      'compact' => '{Whether to return a compact version of the workflow instance, with mediapackage elements, workflow and workflow operation configurations and non-current operations removed}',
     * ]
     * @param string $format (optional) The output format (json or xml) of the response body. (Default value = 'json')
     *
     * @return array the response result ['code' => 200, 'body' => '{A JSON (object) | XML (text) representation of the workflow set }']
     * @deprecated from version v1.3 and will be removed in v1.4
     */
    public function getInstances($params = [], $format = '')
    {
        $uri = self::URI . "/instances.json";
        if (!empty($format) && strtolower($format) == 'xml') {
            $uri = str_replace('.json', '.xml', $uri);
        }

        $query = [];

        $sortsASC = [
            'DATE_CREATED', 'TITLE', 'SERIES_TITLE', 'SERIES_ID',
            'MEDIA_PACKAGE_ID', 'WORKFLOW_DEFINITION_ID', 'CREATOR', 'CONTRIBUTOR',
            'LANGUAGE', 'LICENSE','SUBJECT'
        ];
        $sortsDESC = array_map(function ($sort) {
            return "{$sort}_DESC";
        }, $sortsASC);
        
        $sorts = array_merge($sortsASC, $sortsDESC);
        
        if (array_key_exists('sort', $params) && !empty($params['sort']) &&
            in_array($params['sort'], $sorts)) {
            $query['sort'] = $params['sort'];
        }

        $acceptableParams = [
            'state', 'q', 'seriesId', 'seriesTitle', 'creator', 'contributor',
            'fromdate', 'todate', 'language', 'license', 'title', 'subject',
            'workflowdefinition', 'mp', 'op', 'startPage', 'count', 'compact'
        ];

        foreach ($params as $param_name => $param_value) {
            if (in_array($param_name, $acceptableParams) && !array_key_exists($param_name, $query)) {
                $query[$param_name] = $param_value;
            }
        }
        
        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
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