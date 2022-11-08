<?php
namespace OpencastApi\Rest;

class OcSysinfo extends OcRest
{
    const URI = '/sysinfo';

    public function __construct($restClient)
    {
        $restClient->registerHeaderException('Accept', self::URI);
        parent::__construct($restClient);
    }

    /**
     * Return the common OSGi build version and build number of all bundles matching the given prefix.
     *
     * @param string $prefix (optional) The bundle name prefixes to check. Defaults to 'opencast'.
     *
     * @return array the response result ['code' => 200, 'body' => '{An object of version structure}']
     */
    public function getVersion($prefix = '')
    {
        $uri = self::URI . "/bundles/version";

        $query = [];
        if (!empty($prefix)) {
            $query['prefix'] = $prefix;
        }

        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }
}
?>