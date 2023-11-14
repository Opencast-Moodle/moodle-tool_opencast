<?php
namespace OpencastApi\Rest;

class OcListProvidersApi extends OcRest
{
    const URI = '/api/listproviders';

    public function __construct($restClient)
    {
        // The ListProviders API is available since API version 1.10.0.
        parent::__construct($restClient);
    }

    /**
     * Returns a list of listproviders.
     *
     * @return array the response result ['code' => 200, 'body' => '{The listproviders are returned as a list.}']
     */
    public function getProviders()
    {
        if (!$this->restClient->hasVersion('1.10.0')) {
            return [
                'code' => 403,
                'reason' => 'API Version (>= 1.10.0) is required'
            ];
        }
        $uri = self::URI . "/providers.json";
        return $this->restClient->performGet($uri);
    }

    /**
     * Provides key-value list from the given listprovider.
     *
     * @param string $source The provide source name.
     *
     * @return array the response result ['code' => 200, 'body' => '{The key-value list are returned as a JSON object.}']
     */
    public function getList($source)
    {
        if (!$this->restClient->hasVersion('1.10.0')) {
            return [
                'code' => 403,
                'reason' => 'API Version (>= 1.10.0) is required'
            ];
        }
        $uri = self::URI . "/{$source}.json";
        return $this->restClient->performGet($uri);
    }
}
?>
