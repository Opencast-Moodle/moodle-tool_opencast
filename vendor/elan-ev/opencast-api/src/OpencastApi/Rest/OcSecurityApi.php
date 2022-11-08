<?php
namespace OpencastApi\Rest;

class OcSecurityApi extends OcRest
{
    const URI = '/api/security';

    public function __construct($restClient)
    {
        parent::__construct($restClient);
    }

    /**
     * Returns a signed URL that can be played back for the indicated period of time,
     * while access is optionally restricted to the specified IP address.
     * 
     * @param string $url The URL to be signed
     * @param string $validUntil The date and time until when the signed URL is valid (type of ISO 8602) e.g. "2018-03-11T13:23:51Z"
     * @param string $validSource The IP address from which the url can be accessed
     * 
     * @return array the response result ['code' => 200, 'body' => '{The signed URL}']
     */
    public function sign($url, $validUntil = '', $validSource = '')
    {
        $uri = self::URI . "/sign";
        $formData = [
            'url' => $url
        ];
        if (!empty($validUntil)) {
            $formData['valid-until'] = $validUntil;
        }
        if (!empty($validSource)) {
            $formData['valid-source'] = $validSource;
        }

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }
}
?>