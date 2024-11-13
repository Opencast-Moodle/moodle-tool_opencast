<?php
namespace OpencastApi;

use OpencastApi\Rest\OcRestClient;
use OpencastApi\Rest\OcIngest;

class Opencast
{
    /** @var OcRestClient the rest client */
    private $restClient;

    /** @var OcRestClient the engage node rest client */
    private $engageRestClient;

    // PHP 8.2 deprecates the creation of dynamic class properties.
    // We also need to avoid the type declaration to provide the possibility of using Decorate proxy.

    /** @var \OpencastApi\Rest\OcAgentsApi $agentsApi */
    public $agentsApi;

    /** @var \OpencastApi\Rest\OcBaseApi $baseApi */
    public $baseApi;

    /** @var \OpencastApi\Rest\OcCaptureAdmin $captureAdmin */
    public $captureAdmin;

    /** @var \OpencastApi\Rest\OcEventAdminNg $eventAdminNg */
    public $eventAdminNg;

    /** @var \OpencastApi\Rest\OcEventsApi $eventsApi */
    public $eventsApi;

    /** @var \OpencastApi\Rest\OcGroupsApi $groupsApi */
    public $groupsApi;

    /** @var \OpencastApi\Rest\OcPlaylistsApi $playlistsApi */
    public $playlistsApi;

    /** @var \OpencastApi\Rest\OcRecordings $recordings */
    public $recordings;

    /** @var \OpencastApi\Rest\OcSearch $search */
    public $search;

    /** @var \OpencastApi\Rest\OcSecurityApi $securityApi */
    public $securityApi;

    /** @var \OpencastApi\Rest\OcSeriesApi $seriesApi */
    public $seriesApi;

    /** @var \OpencastApi\Rest\OcSeries $series */
    public $series;

    /** @var \OpencastApi\Rest\OcServices $services */
    public $services;

    /** @var \OpencastApi\Rest\OcStatisticsApi $statisticsApi */
    public $statisticsApi;

    /** @var \OpencastApi\Rest\OcSysinfo $sysinfo */
    public $sysinfo;

    /** @var \OpencastApi\Rest\OcWorkflow $agentsApi */
    public $workflow;

    /** @var \OpencastApi\Rest\OcWorkflowsApi $workflowsApi */
    public $workflowsApi;

    /** @var \OpencastApi\Rest\OcIngest $ingest */
    public $ingest;

    /** @var \OpencastApi\Rest\OcListProvidersApi $listProvidersApi */
    public $listProvidersApi;

    /*
        $config = [
            'url' => 'https://develop.opencast.org/',       // The API url of the opencast instance (required)
            'username' => 'admin',                          // The API username. (required)
            'password' => 'opencast',                       // The API password. (required)
            'timeout' => 0,                                 // The API timeout. In seconds (default 0 to wait indefinitely). (optional)
            'connect_timeout' => 0                          // The API connection timeout. In seconds (default 0 to wait indefinitely) (optional)
            'version' => null                               // The API Version. (Default null). (optional)
            'handler' => null                               // The callable Handler or HandlerStack. (Default null). (optional)
            'features' => null                              // A set of additional features [e.g. lucene search]. (Default null). (optional)
        ]

        $engageConfig = [
            'url' => 'https://develop.opencast.org/',       // The API url of the opencast instance (required)
            'username' => 'admin',                          // The API username. (required)
            'password' => 'opencast',                       // The API password. (required)
            'timeout' => 0,                                 // The API timeout. In seconds (default 0 to wait indefinitely). (optional)
            'connect_timeout' => 0                          // The API connection timeout. In seconds (default 0 to wait indefinitely) (optional)
            'version' => null                               // The API Version. (Default null). (optional)
            'handler' => null                               // The callable Handler or HandlerStack. (Default null). (optional)
            'features' => null                              // A set of additional features [e.g. lucene search]. (Default null). (optional)
        ]
    */
    /**
     * constructor
     * @param array $config Configuration
     * @param array $engageConfig Enage node Configuration
     * @param boolean $enableingest whether to load ingest or not (Default true)
     */
    public function __construct($config, $engageConfig = [], $enableingest = true)
    {
        $this->restClient = new OcRestClient($config);
        $this->setEngageRestClient($config, $engageConfig);
        $this->setEndpointProperties($config, $enableingest);
    }

    private function setEndpointProperties($config, $enableingest)
    {
        foreach(glob(__DIR__   . '/Rest/*.php') as $classPath) {

            $className = basename($classPath, '.php');
            $fullClassName = "\\OpencastApi\\Rest\\{$className}";
            $propertyName = lcfirst(str_replace('Oc', '', $className));
            $client = $this->restClient;

            if (in_array($className, $this->excludeFilters())) {
                continue;
            }

            if (in_array($className, $this->engageFilters())) {
                $client = $this->engageRestClient;
            }

            // Make sure the property is declared properly!
            if (property_exists($this, $propertyName)) {
                $this->{$propertyName} = new $fullClassName($client);
            }
        }

        if ($enableingest) {
            // NOTE: services must be instantiated before calling setIngest method!
            $this->setIngestProperty($config);
        }
    }

    private function excludeFilters()
    {
        return [
            'OcRest',
            'OcRestClient',
            'OcIngest'
        ];
    }

    private function engageFilters()
    {
        return [
            'OcSearch'
        ];
    }

    private function setEngageRestClient($config, $engageConfig)
    {
        if (!isset($engageConfig['url'])) {
            $engageConfig['url'] = $config['url'];
        }
        if (!isset($engageConfig['username'])) {
            $engageConfig['username'] = $config['username'];
        }
        if (!isset($engageConfig['password'])) {
            $engageConfig['password'] = $config['password'];
        }
        if (!isset($engageConfig['timeout']) && isset($config['timeout'])) {
            $engageConfig['timeout'] = $config['timeout'];
        }
        if (!isset($engageConfig['version']) && isset($config['version'])) {
            $engageConfig['version'] = $config['version'];
        }
        if (!isset($engageConfig['handler']) && isset($config['handler'])) {
            $engageConfig['handler'] = $config['handler'];
        }
        if (!isset($engageConfig['features']) && isset($config['features'])) {
            $engageConfig['features'] = $config['features'];
        }
        $this->engageRestClient = new OcRestClient($engageConfig);
    }

    private function setIngestProperty($config)
    {
        if (!property_exists($this, 'services')) {
            return;
        }
        $servicesJson = $this->services->getServiceJSON('org.opencastproject.ingest');
        if (!empty($servicesJson['body']) && property_exists($servicesJson['body'], 'services')) {
            $service = $servicesJson['body']->services->service;
            if (is_array($service)) {
                // Choose random ingest service.
                $ingestService = $service[array_rand($service)];
            } else {
                // There is only one.
                $ingestService = $service;
            }

            $ingestClient = $this->restClient;
            if ($config['url'] != $ingestService->host) {
                $config['url'] = $ingestService->host;
                $ingestClient = new OcRestClient($config);
            }

            // Make sure ingest property exists!
            if (property_exists($this, 'ingest')) {
                $this->ingest = new OcIngest($ingestClient);
            }
        }
    }

    public function __debugInfo()
    {
        return [];
    }
}
?>
