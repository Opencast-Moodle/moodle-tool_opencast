<?php
namespace OpencastApi\Rest;

class OcStatisticsApi extends OcRest
{
    const URI = '/api/statistics';

    public function __construct($restClient)
    {
        // The Statistics API is available since API version 1.3.0.
        parent::__construct($restClient);
    }

    /**
     * Returns a list of statistics providers.
     * NOTE: Currently, only the "timeseries" type is supported!
     * 
     * @param array $filter (optional) an assiciative array for filtering e.g. ['resourceType' => '{resource type}']
     * @param boolean $withparameters (optional) Whether support parameters should be included in the response
     * 
     * @return array the response result ['code' => 200, 'body' => '{the requested statistics providers as JSON (array) }']
     */
    public function getAllProviders($filter = [], $withparameters = false)
    {
        $uri = self::URI . "/providers";
        $query = [];
        if (is_array($filter) && !empty($filter)) {
            $query['filter'] = $this->convertArrayToFiltering($filter);
        }
        if (is_bool($withparameters)) {
            $query['withparameters'] = $withparameters;
        }
        
        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }

    /**
     * Returns a statistics provider.
     * 
     * @param string $providerId The identifier of the statistics provider
     * @param boolean $withparameters (optional) Whether support parameters should be included in the response
     * 
     * @return array the response result ['code' => 200, 'body' => '{The requested statistics provider}']
     */
    public function getProvider($providerId, $withparameters = false)
    {
        $uri = self::URI . "/providers/{$providerId}";
        $query = [];
        if (is_bool($withparameters)) {
            $query['withparameters'] = $withparameters;
        }
        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }
    
    /**
     * Returns the statistical data based on the query posted
     * 
     * @param array $data A JSON array describing the queries to be executed 
     * 
     * @return array the response result ['code' => 200, 'body' => '{The statistical data as JSON (array)}']
     */
    public function getStatisticalData($data)
    {
        $uri = self::URI . "/data/query";
        $formData['data'] = $data;

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Retrieves statistical data in csv format.
     * 
     * Note that limit and offset relate to the resource here, not CSV lines.
     * There can be multiple lines in a CSV for a resource, e.g. an event.
     * However, you cannot limit by lines, but only by e.g. events.
     * 
     * @param array $data A JSON array describing the statistics queries to request
     * @param array $params (optional) The list of form params to pass which can contain the followings:
     * [
     *      'limit' => (int) {the maximum number of results to return},
     *      'offset' => (int) {the index of the first result to return},
     *      'filter' => (array) {an assiciative array for filtering. All standard dublin core meta data fields are filterable.},
     * ] 
     * 
     * @return array the response result ['code' => 200, 'body' => '{The requested statistics csv export}']
     */
    public function getStatisticalDataCSV($data, $params = [])
    {
        if (!$this->restClient->hasVersion('1.4.0')) {
            return [
                'code' => 403,
                'reason' => 'API Version (>= 1.4.0) is required'
            ];
        }

        $uri = self::URI . "/data/export.csv";

        $formData = [
            'data' => $data,
        ];

        if (isset($params['filter']) && is_array($params['filter']) && !empty($params['filter'])) {
            $formData['filter'] = $this->convertArrayToFiltering($params['filter']);
        }

        $acceptableParams = [
            'limit', 'offset', 'filter'
        ];

        foreach ($params as $param_name => $param_value) {
            if (in_array($param_name, $acceptableParams) && !array_key_exists($param_name, $formData)) {
                $formData[$param_name] = $param_value;
            }
        }
        
        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }
}
?>