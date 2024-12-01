<?php
namespace OpencastApi\Rest;

class OcIngest extends OcRest
{
    const URI = '/ingest';

    public function __construct($restClient)
    {
        $restClient->registerHeaderException('Accept', self::URI);
        parent::__construct($restClient);
    }

    /**
     * Create an empty media package.
     *
     * @return array the response result ['code' => 200, 'body' => '{XML (text) media package}']
     */
    public function createMediaPackage()
    {
        $uri = self::URI . '/createMediaPackage';
        return $this->restClient->performGet($uri);
    }

    /**
     * Add a metadata catalog to a given media package using an input stream
     *
     * @param string $mediaPackage The media package
     * @param string $flavor The kind of media catalog
     * @param object $file The metadata catalog file
     * @param string $tags (optional) The tags of the attachment
     *
     * @return array the response result ['code' => 200, 'body' => '{XML (text) augmented media package}']
     */
    public function addCatalog($mediaPackage, $flavor, $file, $tags = '')
    {
        $uri = self::URI . '/addCatalog';

        $formData = [
            'mediaPackage' => $mediaPackage,
            'flavor' => $flavor,
        ];

        if (!empty($tags)) {
            $formData['tags'] = $tags;
        }

        $formData['BODY'] = $file;

        $options = $this->restClient->getMultiPartFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Add a metadata catalog to a given media package using an URL
     *
     * @param string $mediaPackage The media package
     * @param string $flavor The kind of catalog
     * @param string $url The location of the catalog
     * @param string $tags (optional) The tags of the attachment
     *
     * @return array the response result ['code' => 200, 'body' => '{XML (text) augmented media package}']
     */
    public function addCatalogUrl($mediaPackage, $flavor, $url, $tags = '')
    {
        $uri = self::URI . '/addCatalog';

        $formData = [
            'mediaPackage' => $mediaPackage,
            'flavor' => $flavor,
        ];

        if (!empty($tags)) {
            $formData['tags'] = $tags;
        }

        $formData['url'] = $url;

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Add a dublincore episode catalog to a given media package
     *
     * @param string $mediaPackage The media package
     * @param string $dublinCore DublinCore catalog
     * @param string $flavor (optional) DublinCore Flavor (Default value=dublincore/episode)
     *
     * @return array the response result ['code' => 200, 'body' => '{XML (text) augmented media package}']
     */
    public function addDCCatalog($mediaPackage, $dublinCore, $flavor = '')
    {
        $uri = self::URI . '/addDCCatalog';

        $formData = [
            'mediaPackage' => $mediaPackage,
            'dublinCore' => $dublinCore,
        ];
        if (!empty($flavor)) {
            $formData['flavor'] = $flavor;
        }

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Add an attachment to a given media package using an input stream
     *
     * @param string $mediaPackage The media package
     * @param string $flavor The kind of attachment
     * @param object $file The attachment file
     * @param string $tags (optional) The tags of the attachment
     *
     * @return array the response result ['code' => 200, 'body' => '{XML (text) augmented media package}']
     */
    public function addAttachment($mediaPackage, $flavor, $file, $tags = '')
    {
        $uri = self::URI . '/addAttachment';

        $formData = [
            'mediaPackage' => $mediaPackage,
            'flavor' => $flavor,
        ];

        if (!empty($tags)) {
            $formData['tags'] = $tags;
        }

        $formData['BODY'] = $file;

        $options = $this->restClient->getMultiPartFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Add an attachment to a given media package using an URL
     *
     * @param string $mediaPackage The media package
     * @param string $flavor The kind of attachment
     * @param string $url The location of the attachment
     * @param string $tags (optional) The tags of the attachment
     *
     * @return array the response result ['code' => 200, 'body' => '{XML (text) augmented media package}']
     */
    public function addAttachmentUrl($mediaPackage, $flavor, $url, $tags = '')
    {
        $uri = self::URI . '/addAttachment';

        $formData = [
            'mediaPackage' => $mediaPackage,
            'flavor' => $flavor,
        ];

        if (!empty($tags)) {
            $formData['tags'] = $tags;
        }

        $formData['url'] = $url;

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Create and ingest media package from media tracks with additional Dublin Core metadata.
     * It is mandatory to set a title for the recording. This can be done with the 'title' form field or by supplying a DC catalog with a title included.
     * (deprecated*) The identifier of the newly created media package will be taken from the identifier field or the episode DublinCore catalog (deprecated*).
     * If no identifier is set, a new random UUIDv4 will be generated. This endpoint is not meant to be used by capture agents for scheduled recordings. Its primary use is for manual ingests with command line tools like cURL.
     *
     * Multiple tracks can be ingested by using multiple form fields. It is important to always set the flavor of the next media file before sending the media file itself.
     *
     * (*) The special treatment of the identifier field is deprecated and may be removed in future versions without further notice in favor of a random UUID generation to ensure uniqueness of identifiers.
     *
     *
     * @param array $flavor (optional) The kind of media track. This has to be specified prior to each media track (default: "presenter/source"):
     * @param array $file (partially optional) media track file, could be null if mediaUri in $params is defined.
     * @param array $params (optional) The available form params to send the list possible values:
     *  [
     *      'abstract' => '', // Episode metadata value.
     *      'accessRights' => '', // Episode metadata value.
     *      'available' => '', // Episode metadata value.
     *      'contributor' => '', // Episode metadata value.
     *      'coverage' => '', // Episode metadata value.
     *      'created' => '', // Episode metadata value.
     *      'creator' => '', // Episode metadata value.
     *      'date' => '', // Episode metadata value.
     *      'description' => '', // Episode metadata value.
     *      'extent' => '', // Episode metadata value.
     *      'format' => '', // Episode metadata value.
     *      'identifier' => '', // Episode metadata value.
     *      'isPartOf' => '', // Episode metadata value.
     *      'isReferencedBy' => '', // Episode metadata value.
     *      'isReplacedBy' => '', // Episode metadata value.
     *      'language' => '', // Episode metadata value.
     *      'license' => '', // Episode metadata value.
     *      'publisher' => '', // Episode metadata value.
     *      'relation' => '', // Episode metadata value.
     *      'replaces' => '', // Episode metadata value.
     *      'rights' => '', // Episode metadata value.
     *      'rightsHolder' => '', // Episode metadata value.
     *      'source' => '', // Episode metadata value.
     *      'spatial' => '', // Episode metadata value.
     *      'subject' => '', // Episode metadata value.
     *      'temporal' => '', // Episode metadata value.
     *      'temporal' => '', // Episode metadata value.
     *      'title' => '', // Episode metadata value.
     *      'type' => '', // Episode metadata value.
     *      'episodeDCCatalogUri' => '', // URL of episode DublinCore Catalog.
     *      'episodeDCCatalog' => '', // URL of episode DublinCore Catalog.
     *      'seriesDCCatalogUri' => '', // URL of series DublinCore Catalog
     *      'seriesDCCatalog' => '', // Series DublinCore Catalog
     *      'acl' => '', // Access control list in XACML or JSON form
     *      'tag' => '', // Tag of the next media file
     *      'mediaUri' => '', // URL of a media track file
     * ]
     *
     * @param string $wdID (optional) Workflow definition id
     *
     * @return array the response result ['code' => 200, 'body' => '{Ingest successful. Returns workflow instance as XML (text)']
     */
    public function addMediaPackage($flavor = 'presenter/source', $file = null, $params = [], $wdID = '')
    {
        $uri = self::URI . '/addMediaPackage';
        if (!empty($wdID)) {
            $uri .= "/{$wdID}";
        }

        $formData = [
            'flavor' => $flavor
        ];

        if (!empty($file)) {
            $formData['attached_file.mp4'] = $file;
        }

        if (!empty($params)) {
            $formData = array_merge($formData, $params);
        }

        if (isset($formData['attached_file.mp4'])) {
            if (isset($formData['mediaUri']) && !empty($formData['mediaUri'])) {
                unset($formData['mediaUri']);
            }
            $options = $this->restClient->getMultiPartFormParams($formData);
        } else {
            $options = $this->restClient->getFormParams($formData);
        }

        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Create media package from a compressed file containing a manifest.xml document and all media tracks, metadata catalogs and attachments
     *
     * @param object $zipFile The compressed (application/zip) media package file
     * @param string $workflowDefinitionId Workflow definition id
     * @param string $workflowInstanceId (optional) The workflow instance ID to associate with this zipped mediapackage
     *
     * @return array the response result ['code' => 200, 'reason' => 'OK'] (The zipped media package is uploaded)
     */
    public function addZippedMediaPackage($zipFile, $workflowDefinitionId, $workflowInstanceId = '')
    {
        $uri = self::URI . "/addZippedMediaPackage/{$workflowDefinitionId}";

        $formData = [
            'mediaPackage.zip' => $zipFile,
        ];

        if (!empty($workflowDefinitionId) && !empty($workflowInstanceId)) {
            $formData['workflowInstanceId'] = $workflowInstanceId;
        }

        $options = $this->restClient->getMultiPartFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Create an empty media package with ID. Overrides Existing Mediapackage
     *
     * @param string $id The Id for the new Mediapackage
     *
     * @return array the response result ['code' => 200, 'body' => '{XML (text) media package}']
     */
    public function createMediaPackageWithID($id)
    {
        $uri = self::URI . "/createMediaPackageWithID/{$id}";
        return $this->restClient->performPut($uri);
    }

    /**
     * Discard a media package
     *
     * @param string $mediaPackage Given media package to be destroyed
     *
     * @return array the response result ['code' => 200, 'reason' => 'OK']
     */
    public function discardMediaPackage($mediaPackage)
    {
        $uri = self::URI . "/discardMediaPackage";
        $formData['mediaPackage'] = $mediaPackage;

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Add a partial media track to a given media package using an input stream
     *
     * @param string $mediaPackage The XML media package as string
     * @param string $flavor The kind of media track
     * @param object $file The media track file
     * @param int $startTime The start time in milliseconds
     *
     * @return array the response result ['code' => 200, 'body' => '{XML (text) augmented media package}']
     */
    public function addPartialTrack($mediaPackage, $flavor, $file, $startTime = 0)
    {
        $uri = self::URI . "/addPartialTrack";

        $formData = [
            'mediaPackage' => $mediaPackage,
            'flavor' => $flavor,
            'startTime' => $startTime,
            'BODY' => $file
        ];

        $options = $this->restClient->getMultiPartFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Add a partial media track to a given media package using an URL
     *
     * @param string $mediaPackage The XML media package as string
     * @param string $flavor The kind of media track
     * @param string $url The location of the media
     * @param int $startTime The start time in milliseconds
     *
     * @return array the response result ['code' => 200, 'body' => '{XML (text) augmented media package}']
     */
    public function addPartialTrackUrl($mediaPackage, $flavor, $url, $startTime = 0)
    {
        $uri = self::URI . "/addPartialTrack";

        $formData = [
            'mediaPackage' => $mediaPackage,
            'flavor' => $flavor,
            'startTime' => $startTime,
            'url' => $url
        ];

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Add a media track to a given media package using an input stream
     *
     * @param string $mediaPackage The media package
     * @param string $flavor The kind of media track
     * @param object $file The media track file
     * @param string $tags (optional) The Tags of the media track
     * @param callable $progressCallable (optional) Defines a function to invoke when transfer progress is made. The function accepts the following positional arguments:
     * function (
     *      $downloadTotal: the total number of bytes expected to be downloaded, zero if unknown,
     *      $downloadedBytes: the number of bytes downloaded so far,
     *      $uploadTotal: the total number of bytes expected to be uploaded,
     *      $uploadedBytes: the number of bytes uploaded so far
     * )
     *
     * @return array the response result ['code' => 200, 'body' => '{XML (text) augmented media package}']
     */
    public function addTrack($mediaPackage, $flavor, $file, $tags = '', $progressCallable = null)
    {
        $uri = self::URI . "/addTrack";

        $formData = [
            'mediaPackage' => $mediaPackage,
            'flavor' => $flavor,
        ];

        if (!empty($tags)) {
            $formData['tags'] = $tags;
        }

        $formData['BODY'] = $file;

        $options = $this->restClient->getMultiPartFormParams($formData);
        if (!empty($progressCallable)) {
            $options['progress'] = $progressCallable;
        }

        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Add a media track to a given media package using an URL
     *
     * @param string $mediaPackage The media package
     * @param string $flavor The kind of media track
     * @param string $url The location of the media
     * @param string $tags (optional) The Tags of the media track
     *
     * @return array the response result ['code' => 200, 'body' => '{XML (text) augmented media package}']
     */
    public function addTrackUrl($mediaPackage, $flavor, $url, $tags = '')
    {
        $uri = self::URI . "/addTrack";

        $formData = [
            'mediaPackage' => $mediaPackage,
            'flavor' => $flavor,
        ];

        if (!empty($tags)) {
            $formData['tags'] = $tags;
        }

        $formData['url'] = $url;

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Ingest the completed media package into the system
     * NOTE: In addition to the documented form parameters, workflow parameters are accepted as well.
     *
     * @param string $mediaPackage The media package
     * @param string $workflowDefinitionId (optional) Workflow definition id
     * @param string $workflowInstanceId (optional) The workflow instance ID to associate this ingest with scheduled events.
     * @param array $workflowConfiguration Workflow configuration
     *
     * @return array the response result ['code' => 200, 'body' => '{XML (text) media package}']
     */
    public function ingest($mediaPackage, $workflowDefinitionId = '', $workflowInstanceId = '', $workflowConfiguration = [])
    {
        $uri = self::URI . "/ingest";
        if (!empty($workflowDefinitionId) && empty($workflowInstanceId)) {
            $uri .= "/{$workflowDefinitionId}";
        }

        $formData = [
            'mediaPackage' => $mediaPackage,
        ];

        if (!empty($workflowDefinitionId) && !empty($workflowInstanceId)) {
            $formData['workflowDefinitionId'] = $workflowDefinitionId;
            $formData['workflowInstanceId'] = $workflowInstanceId;
        }

        if (!empty($workflowConfiguration)) {
            // Adding workflow configuration params into the form data one by one.
            foreach ($workflowConfiguration as $config => $value) {
                $formData[$config] = $value;
            }
        }

        $options = $this->restClient->getFormParams($formData);

        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Schedule an event based on the given media package
     *
     * @param string $mediaPackage The media package
     * @param string $workflowDefinitionId (optional) Workflow definition id
     *
     * @return array the response result ['code' => 201, 'reason' => 'Created'] (Event scheduled)
     */
    public function schedule($mediaPackage, $workflowDefinitionId = '')
    {
        $uri = self::URI . "/schedule";
        if (!empty($workflowDefinitionId)) {
            $uri .= "/{$workflowDefinitionId}";
        }

        $formData = [
            'mediaPackage' => $mediaPackage,
        ];

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }
}
?>
