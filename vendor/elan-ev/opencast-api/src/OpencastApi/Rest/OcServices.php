<?php 
namespace OpencastApi\Rest;

class OcServices extends OcRest
{
    const URI = '/services';

    public function __construct($restClient)
    {
        $restClient->registerHeaderException('Accept', self::URI);
        parent::__construct($restClient);
    }

    
    /**
     * Returns a service registraton or list of available service registrations as object (JSON) by default or XML (text) on demand.
     * 
     * @param string $serviceType (optional) The service type identifier
     * @param string $host (optional) The host, including the http(s) protocol
     * @param string $format (optional) The output format (json or xml) of the response body. (Default value = 'json')
     * 
     * @return array the response result ['code' => 200, 'body' => '{the available service, formatted as xml or json}']
     */
    public function getServiceJSON($serviceType = '', $host = '', $format = '')
    {
        $uri = self::URI . '/services.json';
        if (!empty($format) && strtolower($format) == 'xml') {
            $uri = str_replace('json', 'xml', $uri);
        }

        $query = [];
        if (!empty($serviceType)) {
            $query['serviceType'] = $serviceType;
        }
        if (!empty($host)) {
            $query['host'] = $host;
        }
        
        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }
}
?>