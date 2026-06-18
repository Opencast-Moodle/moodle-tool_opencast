<?php
namespace OpencastApi\Rest;

class OcInfo extends OcRest
{
    const URI = '/info';

    public function __construct($restClient)
    {
        $restClient->registerHeaderException('Accept', self::URI);
        parent::__construct($restClient);
    }

    /**
     * Returns information about the current user
     *
     * @return array the response result ['code' => 200, 'body' => '{The current user information is returned.}']
     */
    public function getInfoMeJson()
    {
        return $this->restClient->performGet(self::URI . '/me.json');
    }
}
?>
