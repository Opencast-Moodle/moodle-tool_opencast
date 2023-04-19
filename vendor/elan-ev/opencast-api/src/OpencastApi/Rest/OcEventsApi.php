<?php
namespace OpencastApi\Rest;

class OcEventsApi extends OcRest
{
    const URI = '/api/events';

    public function __construct($restClient)
    {
        parent::__construct($restClient);
    }

    ## [Section 1]: General API endpoints.

    /**
     * Returns a list of events. 
     * By setting the optional sign parameter to true, the method will pre-sign distribution urls if signing is turned on in Opencast.
     * Remember to consider the maximum validity of signed URLs when caching this response.
     * 
     * @param array $params (optional) The list of query params to pass which can contain the followings:
     * [
     *      'sign' => (boolean) {Whether public distribution urls should be signed.},
     *      'withacl' => (boolean) {Whether the acl metadata should be included in the response.},
     *      'withmetadata' => (boolean) {Whether the metadata catalogs should be included in the response. },
     *      'withscheduling' => (boolean) {Whether the scheduling information should be included in the response. (version 1.1.0 and higher)},
     *      'withpublications' => (boolean) {Whether the publication ids and urls should be included in the response.},
     *      'onlyWithWriteAccess' => (boolean) {Whether only to get the events to which we have write access.},
     *      'sort' => (array) {an assiciative array for sorting e.g. ['title' => 'DESC']},
     *      'limit' => (int) {the maximum number of results to return},
     *      'offset' => (int) {the index of the first result to return},
     *      'filter' => (array) {an assiciative array for filtering e.g. ['is_part_of' => '{series id}']},
     * ]
     * 
     * @return array the response result ['code' => 200, 'body' => 'A (potentially empty) array list of events is returned.']
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
            'sign', 'withacl', 'withmetadata', 'withpublications',
            'onlyWithWriteAccess', 'sort', 'limit', 'offset', 'filter'
        ];

        if ($this->restClient->hasVersion('1.1.0')) {
            $acceptableParams[] = 'withscheduling';
        }

        foreach ($params as $param_name => $param_value) {
            if (in_array($param_name, $acceptableParams) && !array_key_exists($param_name, $query)) {
                $query[$param_name] = $param_value;
            }
        }

        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }

    /**
     * Returns the list of events in a series.
     * 
     * @param string $seriesId the identifier for a series
     * @param array $params (optional) The list of query params to pass which can contain the followings:
     * [
     *      'sign' => (boolean) {Whether public distribution urls should be signed.},
     *      'withacl' => (boolean) {Whether the acl metadata should be included in the response.},
     *      'withmetadata' => (boolean) {Whether the metadata catalogs should be included in the response. },
     *      'withscheduling' => (boolean) {Whether the scheduling information should be included in the response.  (version 1.1.0 and higher)},
     *      'withpublications' => (boolean) {Whether the publication ids and urls should be included in the response.},
     *      'onlyWithWriteAccess' => (boolean) {Whether only to get the events to which we have write access.},
     *      'sort' => (array) {an assiciative array for sorting e.g. ['title' => 'DESC']},
     *      'limit' => (int) {the maximum number of results to return},
     *      'offset' => (int) {the index of the first result to return},
     *      'filter' => (array) {an assiciative array for filtering e.g. ['is_part_of' => '{series id}']},
     * ]
     * 
     * @return array the response result ['code' => 200, 'body' => 'A (potentially empty) array list of events in a series is returned.']
     */
    public function getBySeries($seriesId, $params = [])
    {
        $filter = isset($params['filter']) ? $params['filter'] : [];
        $filter['is_part_of'] = $seriesId;
        $params['filter'] = $filter;

        return $this->getAll($params);
    }
    
    /**
     * Returns a single event.
     * By setting the optional sign parameter to true, the method will pre-sign distribution urls if signing is turned on in Opencast.
     * Remember to consider the maximum validity of signed URLs when caching this response.
     * 
     * @param string $eventId the identifier of the event.
     * @param array $params (optional) The list of query params to pass which can contain the followings:
     * [
     *      'sign' => (boolean) {Whether public distribution urls should be signed.},
     *      'withacl' => (boolean) {Whether the acl metadata should be included in the response.},
     *      'withmetadata' => (boolean) {Whether the metadata catalogs should be included in the response. },
     *      'withscheduling' => (boolean) {Whether the scheduling information should be included in the response. (version 1.1.0 and higher)},
     *      'withpublications' => (boolean) {Whether the publication ids and urls should be included in the response.}
     * ]
     * 
     * @return array the response result ['code' => 200, 'body' => 'The event (Object)']
     */
    public function get($eventId, $params = [])
    {
        $uri = self::URI . "/{$eventId}";
        $query = [];

        $acceptableParams = [
            'sign', 'withacl', 'withmetadata', 'withpublications'
        ];

        if ($this->restClient->hasVersion('1.1.0')) {
            $acceptableParams[] = 'withscheduling';
        }

        foreach ($params as $param_name => $param_value) {
            if (in_array($param_name, $acceptableParams) && !array_key_exists($param_name, $query)) {
                $query[$param_name] = $param_value;
            }
        }

        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }

    /**
     * Creates an event by sending metadata, access control list, processing instructions and files in a multipart request.
     * 
     * @param string|array $acls A collection of roles with their possible action
     * @param string|array $metadata Event metadata
     * @param string|array $processing Processing instructions task configuration
     * @param string|array $scheduling (optional) Scheduling information (version 1.1.0 and higher)
     * @param file $presenterFile (optional) Presenter movie track
     * @param file $presentationFile (optional) Presentation movie track
     * @param file $audioFile (optional) Audio track
     * @param callable $progressCallable (optional) Defines a function to invoke when transfer progress is made. The function accepts the following positional arguments:
     * function (
     *      $downloadTotal: the total number of bytes expected to be downloaded, zero if unknown,
     *      $downloadedBytes: the number of bytes downloaded so far,
     *      $uploadTotal: the total number of bytes expected to be uploaded,
     *      $uploadedBytes: the number of bytes uploaded so far
     * )
     * 
     * @return array the response result ['code' => 201, 'body' => '{A new event is created and its identifier is returned}', 'location' => '{the url of new event'}]
     */
    public function create($acls, $metadata, $processing, $scheduling = '', $presenterFile = null, $presentationFile = null, $audioFile = null, $progressCallable = null)
    {
        $uri = self::URI;

        $formData = [
            'acl' => $acls,
            'metadata' => $metadata,
            'processing' => $processing,
        ];
        if (!empty($scheduling) && $this->restClient->hasVersion('1.1.0')) {
            $formData['scheduling'] = $scheduling;
        }
        if (!empty($presenterFile)) {
            $formData['presenter'] = $presenterFile;
        }
        if (!empty($presentationFile)) {
            $formData['presentation'] = $presentationFile;
        }
        if (!empty($audioFile)) {
            $formData['audio'] = $audioFile;
        }

        $options = $this->restClient->getMultiPartFormParams($formData);
        if (!empty($progressCallable)) {
            $options['progress'] = $progressCallable;
        }

        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Updates an event.
     * 
     * @param string $eventId the event identifier
     * @param string|array $acls (optional) A collection of roles with their possible action
     * @param string|array $metadata (optional) Event metadata
     * @param string|array $processing (optional) Processing instructions task configuration
     * @param string|array $scheduling (optional) Scheduling information (version 1.1.0 and higher)
     * @param file $presenterFile (optional) Presenter movie track
     * @param file $presentationFile (optional) Presentation movie track
     * @param file $audioFile (optional) Audio track
     * 
     * @return array the response result ['code' => 204, 'reason' => 'NO CONTENT'] (The event has been updated)
     */
    public function update($eventId, $acls = '', $metadata = '', $processing = '', $scheduling = '', $presenterFile = null, $presentationFile = null, $audioFile = null)
    {
        $uri = self::URI . "/{$eventId}";
        $formData = [];
        if (!empty($acls)) {
            $formData['acls'] = $acls;
        }
        if (!empty($metadata)) {
            $formData['metadata'] = $metadata;
        }
        if (!empty($processing)) {
            $formData['processing'] = $processing;
        }
        if (!empty($scheduling) && $this->restClient->hasVersion('1.1.0')) {
            $formData['scheduling'] = $scheduling;
        }
        if (!empty($presenterFile)) {
            $formData['presenter'] = $presenterFile;
        }
        if (!empty($presentationFile)) {
            $formData['presentation'] = $presentationFile;
        }
        if (!empty($audioFile)) {
            $formData['audio'] = $audioFile;
        }

        $options = $this->restClient->getMultiPartFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Retracts possible publications and deletes an event.
     * Since version 1.6.0 published events will be retracted by this endpoint,
     * if you use a version previous to 1.6.0 don't call this endpoint before retracting published events.
     * 
     * @param string $eventId the event identifier
     * 
     * @return array the response result ['code' => 204, 'reason' => 'NO CONTENT'] (The event has been deleted)
     */
    public function delete($eventId)
    {
        $uri = self::URI . "/{$eventId}";
        return $this->restClient->performDelete($uri);
    }

    ## End of [Section 1]: General API endpoints.

    ## [Section 2]: Access Policy.

    /**
     * Returns an event's access policy.
     * 
     * @param string $eventId the event identifier
     * 
     * @return array the response result ['code' => 200, 'body' => '{The access control list for the specified event (Object)}']
     */
    public function getAcl($eventId)
    {
        $uri = self::URI . "/{$eventId}/acl";
        return $this->restClient->performGet($uri);
    }

    /**
     * Update an event's access policy.
     * 
     * @param string $eventId the event identifier
     * @param string|array $acl Access policy
     * 
     * @return array the response result ['code' => 204, 'reason' => 'NO CONTENT'] (The access control list for the specified event is updated)
     */
    public function updateAcl($eventId, $acl)
    {
        $uri = self::URI . "/{$eventId}/acl";
        $formData['acl'] = $acl;
        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPut($uri, $options);
    }

    /**
     * Grants permission to execute action on the specified event to any user with role role.
     * Note that this is a convenience method to avoid having to build and post a complete access control list.
     * 
     * @param string $eventId the event identifier 
     * @param string $action The action that is allowed to be executed
     * @param string $role The role that is granted permission
     * 
     * @return array the response result ['code' => 204, 'reason' => 'NO CONTENT'] (The permission has been created in the access control list of the specified event)
     */
    public function addSingleAcl($eventId, $action, $role)
    {
        $uri = self::URI . "/{$eventId}/acl/{$action}";
        $params['role'] = $role;
        $options = $this->restClient->getFormParams($params);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Removes all ACLs for the event.
     * @param string $eventId the event identifier
     * 
     * @return array the response result ['code' => 204, 'reason' => 'NO CONTENT'] (The access control list for the specified event is updated)
     */
    public function emptyAcl($eventId)
    {
        $acl = [];
        return $this->updateAcl($eventId, $acl);
    }

    /**
     * Revokes permission to execute action on the specified event from any user with specified role.
     * 
     * @param string $eventId the event identifier
     * @param string $action The action that is no longer allowed to be executed
     * @param string $role The role that is no longer granted permission
     * 
     * @return array the response result ['code' => 204, 'reason' => 'NO CONTENT'] (The permission has been revoked from the access control list of the specified event)
     */
    public function deleteSingleAcl($eventId, $action, $role)
    {
        $uri = self::URI . "/{$eventId}/acl/{$action}/{$role}";
        return $this->restClient->performDelete($uri);
    }

    ## End of [Section 2]: Access Policy.

    ## [Section 3]: Media.
    
    /**
     * Returns the complete set of media tracks.
     * @param string $eventId the event identifier
     *
     * @return array the response result ['code' => 200, 'body' => '{The list of media tracks is returned}']
     */
    public function getMedia($eventId)
    {
        $uri = self::URI . "/{$eventId}/media";
        return $this->restClient->performGet($uri);
    }

    /**
     * Adds the given track to the given flavor in the event. It does not start a workflow.
     * @param string $eventId the event identifier
     * @param string $flavor Denotes type and subtype, e.g. 'captions/source+en'
     * @param file $track The track file
     * @param boolean $overwriteExisting If true, all other tracks in the specified flavor are REMOVED (Default: false)
     *
     * @return array the response result ['code' => 200, 'body' => '{The track was added successfully.}']
     */
    public function addTrack($eventId, $flavor, $track, $overwriteExisting = false)
    {
        $uri = self::URI . "/{$eventId}/track";
        $formData['flavor'] = $flavor;
        $formData['track'] = $track;
        $formData['overwriteExisting'] = $overwriteExisting;

        $options = $this->restClient->getMultiPartFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    ## End of [Section 3]: Media.

    ## [Section 4]: Metadata.

    /**
     * Returns the event's metadata (if type is defined of the specified type).
     * For a metadata catalog there is the flavor such as 'dublincore/episode' and this is the unique type.
     * 
     * @param string $eventId the event identifier
     * @param string $type (optional) The type of metadata to get
     * 
     * @return array the response result ['code' => 200, 'body' => '{The metadata collection (array)}']
     */
    public function getMetadata($eventId, $type = '')
    {
        $uri = self::URI . "/{$eventId}/metadata";
        $query = [];
        if (!empty($type)) {
            $query['type'] = $type;
        }
        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }

    /**
     * Update the metadata with the matching type of the specified event.
     * For a metadata catalog there is the flavor such as dublincore/episode and this is the unique type.
     * 
     * @param string $eventId the event identifier
     * @param string $type The type of metadata to update
     * @param string|array $metadata Event metadata
     * 
     * @return array the response result ['code' => 200, 'reason' => 'OK'] (The metadata of the given namespace has been updated)
     */
    public function updateMetadata($eventId, $type, $metadata)
    {
        $uri = self::URI . "/{$eventId}/metadata";
        $query['type'] = $type;
        $formData = [
            'metadata' => $metadata,
        ];
        $queryOpt = $this->restClient->getQueryParams($query);
        $formOpt = $this->restClient->getFormParams($formData);

        $options = array_merge($queryOpt, $formOpt);
        return $this->restClient->performPut($uri, $options);
    }

    /**
     * Delete the metadata namespace catalog of the specified event. This will remove all fields and values of the catalog.
     * Note that the metadata catalog of type dublincore/episode cannot be deleted.
     * 
     * @param string $eventId the event identifier
     * @param string $type The type of metadata to delete
     * 
     * @return array the response result ['code' => 204, 'reason' => 'No Content'] (The metadata of the given namespace has been delete)
     */
    public function deleteMetadata($eventId, $type)
    {
        $uri = self::URI . "/{$eventId}/metadata";
        $query['type'] = $type;
        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performDelete($uri, $options);
    }

    ## End of [Section 4]: Metadata.

    ## [Section 5]: Publications.

    /**
     * Returns an event's list of publications.
     * 
     * @param string $eventId the event identifier
     * @param boolean $sign (optional) Whether publication urls (version 1.7.0 or higher) and distribution urls should be pre-signed.
     * 
     * @return array the response result ['code' => 200, 'body' => '{The list of publications}']
     */
    public function getPublications($eventId, $sign = false)
    {
        $uri = self::URI . "/{$eventId}/publications";
        $query['sign'] = $sign;
        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }

    /**
     * Returns a single publication.
     * 
     * @param string $eventId the event identifier
     * @param string $publicationId the publication id
     * @param boolean $sign (optional) Whether publication urls (version 1.7.0 or higher) and distribution urls should be pre-signed.
     * 
     * @return array the response result ['code' => 200, 'body' => '{The track details}']
     */
    public function getSinglePublication($eventId, $publicationId ,$sign = false)
    {
        $uri = self::URI . "/{$eventId}/publications/{$publicationId}";
        $query['sign'] = $sign;
        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }

    ## End of [Section 5]: Publications.

    ## [Section 6]: Scheduling Information.

    /**
     * Returns an event's scheduling information.
     * Available since API version 1.1.0.
     * 
     * @param string $eventId the event identifier
     * 
     * @return array the response result ['code' => 200, 'body' => '{The scheduling information for the specified event}']
     */
    public function getScheduling($eventId)
    {
        if (!$this->restClient->hasVersion('1.1.0')) {
            return [
                'code' => 403,
                'reason' => 'API Version (>= 1.1.0) is required'
            ];
        }
        $uri = self::URI . "/{$eventId}/scheduling";
        return $this->restClient->performGet($uri);
    }

    /**
     * Update the scheduling information of the event.
     * Available since API version 1.1.0.
     * 
     * @param string $eventId the event identifier
     * @param string|array $scheduling The scheduling information.
     * @param boolean $allowConflict (optional) Allow conflicts when updating scheduling.
     * 
     * @return array the response result ['code' => 204, 'reason' => 'NO CONTENT'] (The scheduling information for the specified event is updated)
     */
    public function updateScheduling($eventId, $scheduling, $allowConflict = false)
    {
        if (!$this->restClient->hasVersion('1.1.0')) {
            return [
                'code' => 403,
                'reason' => 'API Version (>= 1.1.0) is required'
            ];
        }
        
        $uri = self::URI . "/{$eventId}/scheduling";

        $formData = [
            'scheduling' => $scheduling
        ];

        if ($this->restClient->hasVersion('1.2.0') && is_bool($allowConflict)) {
            $formData['allowConflict'] = $allowConflict;
        }

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPut($uri, $options);
    }

    ## End of [Section 6]: Scheduling Information.
}
?>