<?php
namespace OpencastApi\Rest;

class OcSeries extends OcRest
{
    const URI = '/series';

    public function __construct($restClient)
    {
        $restClient->registerHeaderException('Accept', self::URI);
        parent::__construct($restClient);
    }

    /**
     *  Returns the number of series
     *
     * @return array the response result ['code' => 200, 'body' => '{The number of series}']
     */
    public function getCount()
    {
        $uri = self::URI . "/count";
        return $this->restClient->performGet($uri);
    }

    /**
     * Returns the access control list for the series with the given identifier as JSON (Object) by default or XLM (text).
     *
     * @param string $seriesId The series identifier 
     * @param string $format (optional) The output format (json or xml) of the response body. (Default value = 'json')
     *
     * @return array the response result ['code' => 200, 'body' => '{The access control list as JSON (Object) or XML (text)}']
     */
    public function getAcl($seriesId, $format = '')
    {
        $uri = self::URI . "/{$seriesId}/acl.json";
        if (!empty($format) && strtolower($format) == 'xml') {
            $uri = str_replace('.json', '.xml', $uri);
        }

        return $this->restClient->performGet($uri);
    }

    /**
     * Returns a list of identifier and title of all series
     * @return array the response result ['code' => 200, 'body' => '{JSON (object) list of identifier and title of all series}']
     * @deprecated since version v1.3, removed from Opencast Version 12
     */
    public function getTitles()
    {
        $uri = self::URI . "/allSeriesIdTitle.json";
        return $this->restClient->performGet($uri);
    }

    /**
     * Returns the series with the given identifier as JSON (Object) by default or XLM (text).
     *
     * @param string $seriesId The series identifier
     * @param string $format (optional) The output format (json or xml) of the response body. (Default value = 'json')
     *
     * @return array the response result ['code' => 200, 'body' => '{the series dublin core as JSON (Object) or XML (text) document}']
     */
    public function get($seriesId, $format = '')
    {
        $uri = self::URI . "/{$seriesId}.json";
        if (!empty($format) && strtolower($format) == 'xml') {
            $uri = str_replace('.json', '.xml', $uri);
        }

        return $this->restClient->performGet($uri);
    }
    
    /**
     * Returns the series element
     *
     * @param string $seriesId The series identifier
     * @param string $elementType The element type. This is equal to the subtype of the media type of this element: series/
     *
     * @return array the response result ['code' => 200, 'body' => '{The data of the series element}']
     */
    public function getElement($seriesId, $elementType)
    {
        $uri = self::URI . "/{$seriesId}/elements/{$elementType}";
        return $this->restClient->performGet($uri);
    }

    /**
     * Returns all the element types of a series
     *
     * @param string $seriesId The series identifier
     *
     * @return array the response result ['code' => 200, 'body' => '{JSON (array) with all the types of elements of the given series}']
     */
    public function getElements($seriesId)
    {
        $uri = self::URI . "/{$seriesId}/elements.json";
        return $this->restClient->performGet($uri);
    }

    /**
     * Returns the series properties
     *
     * @param string $seriesId The series identifier
     *
     * @return array the response result ['code' => 200, 'body' => '{JSON (array) list of series properties}']
     */
    public function getProperties($seriesId)
    {
        $uri = self::URI . "/{$seriesId}/properties.json";
        return $this->restClient->performGet($uri);
    }

    /**
     * Returns a series property value
     *
     * @param string $seriesId The series identifier
     * @param string $propertyName Name of series property 
     *
     * @return array the response result ['code' => 200, 'body' => '{JSON (object) series property value}']
     */
    public function getProperty($seriesId, $propertyName)
    {
        $uri = self::URI . "/{$seriesId}/property/{$propertyName}.json";
        return $this->restClient->performGet($uri);
    }

    /**
     * Returns the series matching the query parameters as JSON (array) by default or XLM (text).
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
     * @param string $format (optional) The output format (json or xml) of the response body. (Default value = 'json')
     *
     * @return array the response result ['code' => 200, 'body' => '{the series search results as JSON (array) or XML (text)}']
     * @deprecated Deprecated since version 1.3 - since it is removed from Opencast Verrsion 12.
     */
    public function getAll($params = [], $format = '')
    {
        $uri = self::URI . "/series.json";
        if (!empty($format) && strtolower($format) == 'xml') {
            $uri = str_replace('.json', '.xml', $uri);
        }

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
     * Deletes a series
     *
     * @param string $seriesId The series identifier
     *
     * @return array the response result ['code' => 204, 'reason' => 'No Content'] (The series was deleted.)
     */
    public function delete($seriesId)
    {
        $uri = self::URI . "/{$seriesId}";
        return $this->restClient->performDelete($uri);
    }

    /**
     * Deletes a series element
     * 
     * @param string $seriesId The series identifier
     * @param string $elementType The element type 
     *
     * @return array the response result ['code' => 204, 'reason' => 'No Content'] (Series element deleted)
     */
    public function deleteElement($seriesId, $elementType)
    {
        $uri = self::URI . "/{$seriesId}/elements/{$elementType}";
        return $this->restClient->performDelete($uri);
    }

    /**
     * Deletes a series property
     * 
     * @param string $seriesId The series identifier
     * @param string $propertyName Name of series property
     *
     * @return array the response result ['code' => 204, 'reason' => 'No Content'] (The series property has been deleted)
     */
    public function deleteProperty($seriesId, $propertyName)
    {
        $uri = self::URI . "/{$seriesId}/property/{$propertyName}";
        return $this->restClient->performDelete($uri);
    }

    /**
     * Updates the access control list for a series
     *
     * @param string $seriesId The series identifier
     * @param string|object $acl The access control list for the series
     * @param boolean $override (optional) If true the series ACL will take precedence over any existing episode ACL (Default value=false)
     *
     * @return array the response result:
     * ['code' => 201, 'reason' => 'Created'] (The access control list has been created)
     * ['code' => 204, 'reason' => 'No Content'] (The access control list has been updated)
     */
    public function updateAcl($seriesId, $acl, $override = false)
    {
        $uri = self::URI . "/{$seriesId}/accesscontrol";

        $formData = [
            'acl' => $acl
        ];
        if (is_bool($override)) {
            $formData['override'] = $override;
        }

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Updates a series
     *
     * @param array $params (optional) The list of form params to pass which can contain the followings:
     * [
     *      'series' => {The series document. Will take precedence over metadata fields as string in XML DublinCore format},
     *      'acl' => {The access control list for the series as string in XML format},
     *      'abstract' => {Series metadata value},
     *      'accessRights' => {Series metadata value},
     *      'available' => {Series metadata value},
     *      'contributor' => {Series metadata value},
     *      'coverage' => {Series metadata value},
     *      'created' => {Series metadata value},
     *      'creator' => {Series metadata value},
     *      'date' => {Series metadata value},
     *      'description' => {Series metadata value},
     *      'extent' => {Series metadata value},
     *      'format' => {Series metadata value},
     *      'identifier' => {Series metadata value},
     *      'isPartOf' => {Series metadata value},
     *      'isReferencedBy' => {Series metadata value},
     *      'isReplacedBy' => {Series metadata value},
     *      'language' => {Series metadata value},
     *      'license' => {Series metadata value},
     *      'publisher' => {Series metadata value},
     *      'relation' => {Series metadata value},
     *      'replaces' => {Series metadata value},
     *      'rights' => {Series metadata value},
     *      'rightsHolder' => {Series metadata value},
     *      'source' => {Series metadata value},
     *      'spatial' => {Series metadata value},
     *      'subject' => {Series metadata value},
     *      'temporal' => {Series metadata value},
     *      'title' => {Series metadata value},
     *      'type' => {Series metadata value},
     * ]
     * @param boolean $override (optional) If true the series ACL will take precedence over any existing episode ACL (Default value=false)
     *
     * @return array the response result:
     * ['code' => 201, 'reason' => 'Created'] (series created)
     * ['code' => 204, 'reason' => 'No Content'] (series updated) 
     * 
     */
    public function update($params, $override = false)
    {
        $uri = self::URI;

        $formData = [];
        $acceptableParams = [
            'series', 'acl', 'abstract', 'accessRights', 'available',
            'contributor', 'coverage', 'created', 'creator', 'date',
            'description', 'extent', 'format', 'identifier', 'isPartOf',
            'isReferencedBy', 'isReplacedBy', 'language', 'license', 'publisher',
            'relation', 'replaces', 'rights', 'rightsHolder', 'source',
            'spatial', 'subject', 'temporal', 'title', 'type'
        ];
        foreach ($params as $param_name => $param_value) {
            if (in_array($param_name, $acceptableParams) && !empty($param_value)) {
                $formData[$param_name] = $param_value;
            }
        }
        if (is_bool($override)) {
            $formData['override'] = $override;
        }

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Updates an existing series element
     *
     * @param string $seriesId The series identifier
     * @param string $elementType The element type
     *
     * @return array the response result:
     * ['code' => 201, 'reason' => 'Created'] (Series element created)
     * ['code' => 204, 'reason' => 'No Content'] (Series element updated)
     */
    public function updateElement($seriesId, $elementType)
    {
        $uri = self::URI . "/{$seriesId}/elements/{$elementType}";
        return $this->restClient->performPut($uri);
    }

    /**
     * Updates a series property
     *
     * @param string $seriesId The series identifier
     * @param string $name The property's name
     * @param string $value The property's value
     *
     * @return array the response result ['code' => 204, 'reason' => 'No Content'] (property has been updated.)
     */
    public function updateProperty($seriesId, $name, $value)
    {
        $uri = self::URI . "/{$seriesId}/property";

        $formData = [
            'name' => $name,
            'value' => $value
        ];

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }
}
?>