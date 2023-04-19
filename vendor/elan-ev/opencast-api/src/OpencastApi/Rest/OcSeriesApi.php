<?php
namespace OpencastApi\Rest;

class OcSeriesApi extends OcRest
{
    const URI = '/api/series';

    public function __construct($restClient)
    {
        parent::__construct($restClient);
    }

    ## [Section 1]: General API endpoints.

    /**
     * Returns a list of series.
     * 
     * @param array $params (optional) The list of query params to pass which can contain the followings:
     * [
     *      'onlyWithWriteAccess' => (boolean) {Whether only to get the series to which we have write access. },
     *      'withacl' => (boolean) {Whether the acl should be included in the response (version 1.5.0 and higher)},
     *      'sort' => (array) {an assiciative array for sorting e.g. ['title' => 'DESC']},
     *      'limit' => (int) {the maximum number of results to return},
     *      'offset' => (int) {the index of the first result to return},
     *      'filter' => (array) {an assiciative array for filtering e.g. ['title' => '{series title}']},
     * ]
     * 
     * @return array the response result ['code' => 200, 'body' => '{A (potentially empty) list of series }']
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
            'sort', 'limit', 'offset', 'filter', 'onlyWithWriteAccess'
        ];

        if ($this->restClient->hasVersion('1.5.0')) {
            $acceptableParams[] = 'withacl';
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
     * Returns the series matching the query parameters as JSON (array).
     *
     * @param array $params (optional) The list of query params to pass which can contain the followings:
     * [
     *      'q' => '{Free text search}',
     *      'edit' => '(boolean){Whether this query should return only series that are editable}',
     *      'fuzzyMatch' => '(boolean){Whether the seriesId can be used for a partial match. The default is an exact match}',
     *      'seriesId' => '{The series identifier}',
     *      'seriesTitle' => '{The series title}',
     *      'creator' => '{The series creator}',
     *      'contributor' => '{The series contributor}',
     *      'publisher' => '{The series publisher}',
     *      'rightsholder' => '{The series rights holder}',
     *      'createdfrom' => '{Filter results by created from (yyyy-MM-dd'T'HH:mm:ss'Z') }',
     *      'createdto' => '{Filter results by created to (yyyy-MM-dd'T'HH:mm:ss'Z')) }',
     *      'language' => '{The series language}',
     *      'license' => '{The series license}',
     *      'subject' => '{The series subject}',
     *      'abstract' => '{The series abstract}',
     *      'description' => '{The series description}',
     *      'sort' => '{The sort order. May include any of the following: TITLE, SUBJECT, CREATOR, PUBLISHER, CONTRIBUTOR, ABSTRACT, DESCRIPTION, CREATED, AVAILABLE_FROM, AVAILABLE_TO, LANGUAGE, RIGHTS_HOLDER, SPATIAL, TEMPORAL, IS_PART_OF, REPLACES, TYPE, ACCESS, LICENCE. Add '_DESC' to reverse the sort order (e.g. TITLE_DESC)}',
     *      'startPage' => '{The page offset}',
     *      'count' => '{Results per page (max 100)}',
     * ]
     *
     * @return array the response result ['code' => 200, 'body' => '{the series search results as JSON (array)}']
     */
    public function getAllFullTextSearch($params = [])
    {
        $uri = self::URI . "/series.json";

        $query = [];
        $acceptableParams = [
            'q', 'edit', 'fuzzyMatch', 'seriesId', 'seriesTitle',
            'creator', 'contributor', 'publisher', 'rightsholder', 'createdfrom',
            'createdto', 'language', 'license', 'subject', 'abstract',
            'description', 'startPage', 'count'
        ];
        foreach ($params as $param_name => $param_value) {
            if (in_array($param_name, $acceptableParams)) {
                if ((($param_name == 'edit' || $param_name == 'fuzzyMatch') && is_bool($param_value)) || !empty($param_value)) {
                    if ($param_name == 'count') {
                        $param_value = intval($param_value);
                        $param_value = ($param_value <= 100) ?: 100;
                    }
                    $query[$param_name] = $param_value;
                }
            }
        }
        $sortsASC = [
            'TITLE', 'SUBJECT', 'CREATOR', 'PUBLISHER',
            'CONTRIBUTOR', 'ABSTRACT', 'DESCRIPTION', 'CREATED',
            'AVAILABLE_FROM', 'AVAILABLE_TO','LANGUAGE', 'RIGHTS_HOLDER',
            'SPATIAL', 'TEMPORAL', 'IS_PART_OF', 'REPLACES', 'TYPE',
            'ACCESS', 'LICENCE'
        ];
        $sortsDESC = array_map(function ($sort) {
            return "{$sort}_DESC";
        }, $sortsASC);

        $sorts = array_merge($sortsASC, $sortsDESC);
        
        if (array_key_exists('sort', $params) && !empty($params['sort']) &&
            in_array($params['sort'], $sorts)) {
            $query['sort'] = $params['sort'];
        }

        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }
    
    /**
     * Returns a single series.
     * 
     * @param string $seriesId the identifier of the series.
     * @param boolean $withacl (optional) Whether the acl should be included in the response (version 1.5.0 and higher)
     * 
     * @return array the response result ['code' => 200, 'body' => '{The series (object)}']
     */
    public function get($seriesId, $withacl = false)
    {
        $uri = self::URI . "/{$seriesId}";

        $query = [];
        if (is_bool($withacl) && $this->restClient->hasVersion('1.5.0')) {
            $query['withacl'] = $withacl;
        }
        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }

    /**
     * Creates a series.
     * 
     * @param string|array $metadata Series metadata
     * @param string|array $acls A collection of roles with their possible action
     * @param string $theme (optional) The theme ID to be applied to the series
     * 
     * @return array the response result ['code' => 201, 'reason' => 'Created', 'body' => '{The identifier of new series (object)}', 'location' => '{the url}']
     */
    public function create($metadata, $acls , $theme = '')
    {
        $formData = [
            'metadata' => $metadata,
            'acl' => $acls
        ];
        if (!empty($theme)) {
            $formData['theme'] = $theme;
        }
        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost(self::URI, $options);
    }

    /**
     * Deletes a series
     * 
     * @param string $seriesId the series identifier
     * 
     * @return array the response result ['code' => 204, 'reason' => 'No Content'] (The series has been deleted.)
     */
    public function delete($seriesId)
    {
        $uri = self::URI . "/{$seriesId}";
        return $this->restClient->performDelete($uri);
    }

    ## End of [Section 1]: General API endpoints.

    ## [Section 2]: Metadata.

    /**
     * Returns a series' metadata of all types or returns a series' metadata collection of the given type when the query string parameter type is specified. 
     * For each metadata catalog there is a unique property called the flavor such as dublincore/series so the type in this example would be 'dublincore/series'
     * 
     * @param string $seriesId the series identifier
     * @param string $type (optional) The type of metadata to return
     * 
     * @return array the response result ['code' => 200, 'body' => '{The series' metadata}']
     */
    public function getMetadata($seriesId, $type = '')
    {
        $uri = self::URI . "/{$seriesId}/metadata";

        $query = [];
        if (!empty($type)) {
            $query['type'] = $type;
        }

        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }

    /**
     * Update all series metadata.
     *
     * @param string $seriesId the series identifier
     * @param string|array $metadata Series metadata
     *
     * @return array the response result ['code' => 200, 'reason' => 'OK'] (The series' metadata have been updated.)
     */
    public function updateAllMetadata($seriesId, $metadata)
    {
        $uri = self::URI . "/{$seriesId}";

        $formData = [
            'metadata' => $metadata,
        ];

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPut($uri, $options);
    }

    /**
     * Update a series' metadata of the given type.
     * For a metadata catalog there is the flavor such as 'dublincore/series' and this is the unique type.
     * 
     * @param string $seriesId the series identifier
     * @param string|array $metadata Series metadata
     * @param string $type (optional) The type of metadata to get (Default: "dublincore/series")
     * 
     * @return array the response result ['code' => 200, 'reason' => 'OK'] (The series' metadata have been updated.)
     */
    public function updateMetadata($seriesId, $metadata, $type = 'dublincore/series')
    {
        $uri = self::URI . "/{$seriesId}/metadata";

        $formData = [
            'metadata' => $metadata,
        ];
        $formOpt = $this->restClient->getFormParams($formData);

        $query = [
            'type' => $type
        ];

        $queryOpt = $this->restClient->getQueryParams($query);

        $options = array_merge($queryOpt, $formOpt);
        return $this->restClient->performPut($uri, $options);
    }

    /**
     * Deletes a series' metadata catalog of the given type.
     * All fields and values of that catalog will be deleted.
     * 
     * @param string $seriesId the series identifier
     * @param string $type The type of metadata to delete
     * 
     * @return array the response result ['code' => 204, 'reason' => 'No Content'] (The metadata have been deleted)
     */
    public function deleteMetadata($seriesId, $type)
    {
        $uri = self::URI . "/{$seriesId}/metadata";
        $query = [
            'type' => $type
        ];

        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performDelete($uri, $options);
    }

    ## End of [Section 2]: Metadata.

    ## [Section 3]: Access Policy.

    /**
     * Returns a series' access policy.
     * 
     * @param string $seriesId the series identifier
     * 
     * @return array the response result ['code' => 200, 'body' => '{The series' access policy}']
     */
    public function getAcl($seriesId)
    {
        $uri = self::URI . "/{$seriesId}/acl";
        return $this->restClient->performGet($uri);
    }

    /**
     * Updates a series' access policy.
     * Note that the existing access policy of the series will be overwritten.
     * 
     * @param string $seriesId the series identifier
     * @param string|array $acls Access policy to be applied
     * @param boolean $override (optional) Whether the episode Acl of all events of this series should be removed (Default value: false) (version 1.2.0 or heigher)
     * 
     * @return array the response result ['code' => 200, 'reason' => 'OK'] (The access control list for the specified series is updated)
     */
    public function updateAcl($seriesId, $acls, $override = false)
    {
        $uri = self::URI . "/{$seriesId}/acl";
        $formData['acl'] = $acls;

        if (is_bool($override) && $this->restClient->hasVersion('1.2.0')) {
            $formData['override'] = $override;
        }

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPut($uri, $options);
    }

    /**
     * Removes all Acls for the series.
     * 
     * @param string $seriesId the series identifier
     * @param boolean $override (optional) Whether the episode Acl of all events of this series should be removed (Default value: false) (version 1.2.0 or heigher)
     * 
     * @return array the response result ['code' => 200, 'reason' => 'OK'] (The access control list for the specified series is updated)
     */
    public function emptyAcl($seriesId, $override = false)
    {
        $acls = [];
        return $this->updateAcl($seriesId, $acls, $override);
    }

    ## End of [Section 3]: Access Policy.

    ## [Section 4]: Properties.
    
    /**
     * Returns a series' properties.
     * 
     * @param string $seriesId the series identifier
     *
     * @return array the response result ['code' => 200, 'body' => '{The series' properties}']
     */
    public function getProperties($seriesId)
    {
        $uri = self::URI . "/{$seriesId}/properties";
        return $this->restClient->performGet($uri);
    }

    /**
     * Add or update properties of a series.
     * The request can be used to add new properties and/or update existing properties.
     * Properties not included in the request are not affected.
     * 
     * @param string $seriesId the series identifier
     * @param string|array $properties List of properties to be assigned to the series
     * 
     * @return array the response result ['code' => 200, 'body' => '{The added/updated series' properties}']
     */
    public function updateProperties($seriesId, $properties)
    {
        $uri = self::URI . "/{$seriesId}/properties";
        $formData['properties'] = $properties;
        
        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPut($uri, $options);
    }

    /**
     * Add or update properties of a series.
     * The request can be used to add new properties and/or update existing properties.
     * Properties not included in the request are not affected.
     * 
     * @param string $seriesId the series identifier
     * @param string|array $properties List of properties to be assigned to the series
     * 
     * @return array the response result ['code' => 200, 'body' => '{The added/updated series' properties}']
     */
    public function addProperties($seriesId, $properties)
    {
        return $this->updateProperties($seriesId, $properties);
    }

    ## End of [Section 4]: Properties.
}
?>