<?php
namespace OpencastApi\Rest;

class OcWorkflowsApi extends OcRest
{
    const URI = '/api/workflows';
    const URI_SECTION_2 = '/api/workflow-definitions';

    public function __construct($restClient)
    {
        // The Workflow API is available since API version 1.1.0.
        parent::__construct($restClient);
    }

    ## [Section 1]: General API endpoints.

    /**
     * Returns a list of workflow instances.
     * 
     * @param array $params (optional) The list of query params to pass which can contain the followings:
     * [
     *      'withoperations' => '{Whether the workflow operations should be included in the response}',
     *      'withconfiguration' => '{Whether the workflow configuration should be included in the response}',
     *      'sort' => '{an assiciative array for sorting e.g. ['event_identifier' => 'DESC']}',
     *      'limit' => '{the maximum number of results to return}',
     *      'offset' => '{the index of the first result to return}',
     *      'filter' => '{an assiciative array for filtering e.g. ['state' => '{Workflow instances that are in this state}']}',
     * ]     
     * 
     * @return array the response result ['code' => 200, 'body' => '{A (potentially empty) list of workflow instances}']
     * @deprecated since v1.3 because this endpoint is removed from Opencast Verison 12.x, we will no longer support it here.
     */
    public function getAll($params = [])
    {
        $uri = self::URI;

        $query = [];
        if (isset($params['filter']) && is_array($params['filter']) && !empty($params['filter'])) {
            $query['filter'] = $this->convertArrayToFiltering($params['filter']);
        }
        if (isset($params['sort']) && is_array($params['sort']) && !empty($params['sort'])) {
            $query['sort'] = $this->convertArrayToSorting($params['sort']);
        }

        $acceptableParams = [
            'sort', 'limit', 'offset', 'filter', 'withoperations', 'withconfiguration'
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
     * Returns a single workflow instance.
     * 
     * @param string $workflowInstanceId The workflow instance id 
     * @param boolean $withoperations (optional) Whether the workflow operations should be included in the response (Default value=false)
     * @param boolean $withconfiguration (optional) Whether the workflow configuration should be included in the response (Default value=false)
     * 
     * @return array the response result ['code' => 200, 'body' => '{ The workflow instance}']
     */
    public function get($workflowInstanceId, $withoperations = false, $withconfiguration = false)
    {
        $uri = self::URI . "/{$workflowInstanceId}";

        $query = [];
        if (is_bool($withoperations)) {
            $query['withoperations'] = $withoperations;
        }
        if (is_bool($withconfiguration)) {
            $query['withconfiguration'] = $withconfiguration;
        }
        
        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }

    /**
     * Creates (runs) a workflow instance agianst an event.
     * 
     * @param string $eventIdentifier The event identifier this workflow should run against
     * @param string $definitionIdentifier The identifier of the workflow definition to use
     * @param string|array $configuration (optional) The optional configuration for this workflow
     * @param boolean $withoperations (optional) Whether the workflow operations should be included in the response (Default value=false)
     * @param boolean $withconfiguration (optional) Whether the workflow configuration should be included in the response (Default value=false)
     * 
     * @return array the response result ['code' => 201, 'body' => '{A new workflow is created and its identifier as Object is returned}', 'location' => 'The url']
     */
    public function run($eventIdentifier, $definitionIdentifier, $configuration = [], $withoperations = false, $withconfiguration = false)
    {
        $uri = self::URI;

        $formData = [
            'event_identifier' => $eventIdentifier,
            'workflow_definition_identifier' => $definitionIdentifier,
        ];
        if (!empty($configuration)) {
            $formData['configuration'] = $configuration;
        }
        $formOpt = $this->restClient->getFormParams($formData);

        $query = [];
        if (is_bool($withoperations)) {
            $query['withoperations'] = $withoperations;
        }
        if (is_bool($withconfiguration)) {
            $query['withconfiguration'] = $withconfiguration;
        }
        $queryOpt = $this->restClient->getQueryParams($query);

        $options = array_merge($formOpt, $queryOpt);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Updates a workflow instance.
     * 
     * @param string $workflowInstanceId The workflow instance id 
     * @param string $state (optional) The optional state transition for this workflow
     * @param string|array $configuration (optional) The optional configuration for this workflow
     * @param bool $withoperations (optional) Whether the workflow operations should be included in the response (Default value=false)
     * @param bool $withconfiguration (optional) Whether the workflow configuration should be included in the response (Default value=false)
     * 
     * @return array the response result ['code' => 200, 'reason' => 'OK'] (updated)
     */
    public function update($workflowInstanceId, $state = '', $configuration = [], $withoperations = false, $withconfiguration = false)
    {
        $uri = self::URI . "/{$workflowInstanceId}";

        $formData = [];
        if (!empty($state)) {
            $formData['state'] = $state;
        }
        if (!empty($configuration)) {
            $formData['configuration'] = $configuration;
        }
        $formOpt = $this->restClient->getFormParams($formData);

        $query = [];
        if (is_bool($withoperations)) {
            $query['withoperations'] = $withoperations;
        }
        if (is_bool($withconfiguration)) {
            $query['withconfiguration'] = $withconfiguration;
        }
        $queryOpt = $this->restClient->getQueryParams($query);

        $options = array_merge($formOpt, $queryOpt);
        return $this->restClient->performPut($uri, $options);
    }

    /**
     * Deletes a workflow instance.
     * 
     * @param string $workflowInstanceId The workflow instance id 
     * 
     * @return array the response result ['code' => 204, 'reason' => 'No Content'] (deleted)
     */
    public function delete($workflowInstanceId)
    {
        $uri = self::URI . "/{$workflowInstanceId}";
        return $this->restClient->performDelete($uri);
    }

    ## End of [Section 1]: General API endpoints.

    ## [Section 2]: Workflow definitions.

    /**
     * Returns a list of workflow definitions.
     * 
     * @param array $params (optional) The list of query params to pass which can contain the followings:
     * [
     *      'withoperations' => '{(boolean) Whether the workflow operations should be included in the response}',
     *      'withconfigurationpanel' => '{(boolean) Whether the workflow configuration panel should be included in the response}',
     *      'sort' => '{an assiciative array for sorting e.g. ['title' => 'DESC']}',
     *      'limit' => '{the maximum number of results to return}',
     *      'offset' => '{the index of the first result to return}',
     *      'filter' => '{an assiciative array for filtering e.g. ['tag' => '{Workflow definitions where the tag is included}']}',
     * ]     
     * 
     * @return array the response result ['code' => 200, 'body' => '{A (potentially empty) list of workflow definitions}']     
     */
    public function getAllDefinitions($params = [])
    {
        $uri = self::URI_SECTION_2;

        $query = [];
        if (isset($params['filter']) && is_array($params['filter']) && !empty($params['filter'])) {
            $query['filter'] = $this->convertArrayToFiltering($params['filter']);
        }
        if (isset($params['sort']) && is_array($params['sort']) && !empty($params['sort'])) {
            $query['sort'] = $this->convertArrayToSorting($params['sort']);
        }

        $acceptableParams = [
            'sort', 'limit', 'offset', 'filter', 'withoperations', 'withconfigurationpanel'
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
     * Returns a single workflow definition.
     * 
     * @param string $workflowDefinitionId the identifier of the workflow definition.
     * @param boolean $withoperations (optional) Whether the workflow operations should be included in the response (Default value=false)
     * @param boolean $withconfigurationpanel (optional) Whether the workflow configuration should be included in the response (Default value=false)
     * 
     * @return array the response result ['code' => 200, 'body' => '{ The workflow definition is returned as JSON object}']
     */
    public function getDefinition($workflowDefinitionId, $withoperations = false, $withconfigurationpanel = false)
    {
        $uri = self::URI_SECTION_2 . "/{$workflowDefinitionId}";

        $query = [];
        if (is_bool($withoperations)) {
            $query['withoperations'] = $withoperations;
        }
        if (is_bool($withconfigurationpanel)) {
            $query['withconfigurationpanel'] = $withconfigurationpanel;
        }
        
        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }

    ## End of [Section 2]: Workflow definitions.
}
?>