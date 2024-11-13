<?php
namespace OpencastApi\Rest;

class OcSearch extends OcRest
{
    const URI = '/search';
    public $lucene = false; // By default false, main support for OC 16.

    public function __construct($restClient)
    {
        $restClient->registerHeaderException('Accept', self::URI);
        parent::__construct($restClient);
        if ($restClient->readFeatures('lucene')) {
            $this->lucene = true;
        }
    }


    /**
     * Search for episodes matching the query parameters as object (JSON) by default or XML (text) on demand.
     *
     * @param array $params the params to pass to the call: it must cointain the following:
     * $params = [
     *      'id' => '{The ID of the single episode to be returned, if it exists}',
     *      'q' => '{Any episode that matches this free-text query.}',
     *      'sid' => '{Any episode that belongs to specified series id.}',
     *      'sname' => '{ Any episode that belongs to specified series name (note that the specified series name must be unique).}',
     *      'sort' => '{The sort order. May include any of the following: DATE_CREATED, DATE_MODIFIED, TITLE, SERIES_ID, MEDIA_PACKAGE_ID, CREATOR, CONTRIBUTOR, LANGUAGE, LICENSE, SUBJECT, DESCRIPTION, PUBLISHER. Add '_DESC' to reverse the sort order (e.g. TITLE_DESC).}',
     *      'limit' => '{ The maximum number of items to return per page. (Default value=20)}',
     *      'offset' => '{The page number. (Default value=0)}',
     *      'admin' => '{Whether this is an administrative query (Default value=false)}',
     *      'sign' => '{If results are to be signed (Default value=true)}',
     * ]
     * @param string $format The output format (json or xml) of the response body. (Default value = 'json')
     *
     * @return array the response result ['code' => 200, 'body' => '{The search results, formatted as xml or json}']
     */
    public function getEpisodes($params = [], $format = '')
    {
        $uri = self::URI . "/episode.json";
        if (!empty($format) && strtolower($format) == 'xml') {
            $uri = str_replace('json', 'xml', $uri);
        }

        $query = [];
        if (array_key_exists('id', $params) && !empty($params['id'])) {
            $query['id'] = $params['id'];
        }
        if (array_key_exists('q', $params) && !empty($params['q'])) {
            $query['q'] = $params['q'];
        }
        if (array_key_exists('sid', $params) && !empty($params['sid'])) {
            $query['sid'] = $params['sid'];
        }
        if (array_key_exists('sname', $params) && !empty($params['sname'])) {
            $query['sname'] = $params['sname'];
        }
        if (array_key_exists('limit', $params) && !empty(intval($params['limit']))) {
            $query['limit'] = intval($params['limit']);
        }
        if (array_key_exists('offset', $params) && !empty(intval($params['offset']))) {
            $query['offset'] = intval($params['offset']);
        }
        if (array_key_exists('admin', $params) && is_bool($params['admin'])) {
            $query['admin'] = $params['admin'];
        }
        if (array_key_exists('sign', $params) && is_bool($params['sign'])) {
            $query['sign'] = $params['sign'];
        }


        // OC <= 15
        if ($this->lucene) {
            $sortsASC = [
                'DATE_CREATED', 'DATE_MODIFIED', 'TITLE', 'SERIES_ID',
                'MEDIA_PACKAGE_ID', 'CREATOR', 'CONTRIBUTOR', 'LANGUAGE',
                'LICENSE','SUBJECT','DESCRIPTION','PUBLISHER',
            ];
            $sortsDESC = array_map(function ($sort) {
                return "{$sort}_DESC";
            }, $sortsASC);

            $sorts = array_merge($sortsASC, $sortsDESC);

            if (array_key_exists('sort', $params) && !empty($params['sort']) &&
                in_array($params['sort'], $sorts)) {
                $query['sort'] = $params['sort'];
            }

        // OC >= 16
        } else {
            $sorts = [
                'modified', 'title', 'creator', 'contributor'
            ];

            $sortsASC = array_map(function ($sort) {
                return "{$sort} asc";
            }, $sorts);

            $sortsDESC = array_map(function ($sort) {
                return "{$sort} desc";
            }, $sorts);

            $sorts_list = array_merge($sorts, $sortsASC, $sortsDESC);

            if (array_key_exists('sort', $params) && !empty($params['sort']) &&
                in_array(strtolower($params['sort']), $sorts_list)) {
                $query['sort'] = strtolower($params['sort']);
            }
        }

        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }


    /**
     * Search a lucene query as object (JSON) by default or XML (text) on demand.
     *
     * INFO: This endpoint is removed in Opencast 16.
     *
     * @param array $params the params to pass to the call: it must cointain the following:
     * $params = [
     *      'q' => '{ The lucene query. }',
     *      'series' => '{ Include series in the search result. (Default value=false)}',
     *      'sort' => '{ The sort order. May include any of the following: DATE_CREATED, DATE_MODIFIED, TITLE, SERIES_ID, MEDIA_PACKAGE_ID, CREATOR, CONTRIBUTOR, LANGUAGE, LICENSE, SUBJECT, DESCRIPTION, PUBLISHER. Add '_DESC' to reverse the sort order (e.g. TITLE_DESC).}',
     *      'limit' => '{ The maximum number of items to return per page. (Default value=20)}',
     *      'offset' => '{The page number. (Default value=0)}',
     *      'admin' => '{Whether this is an administrative query (Default value=false)}',
     *      'sign' => '{If results are to be signed (Default value=true)}',
     * ]
     * @param string $format The output format (json or xml) of the response body. (Default value = 'json')
     *
     * @return array the response result ['code' => 200, 'body' => '{The search results, formatted as xml or json}']
     */
    public function getLucene($params = [], $format = '')
    {
        if (!$this->lucene) {
            return ['code' => 410, 'reason' => 'Lucene search endpoint is not available!'];
        }

        $uri = self::URI . "/lucene.json";
        if (!empty($format) && strtolower($format) == 'xml') {
            $uri = str_replace('json', 'xml', $uri);
        }

        $query = [];
        if (array_key_exists('q', $params) && !empty($params['q'])) {
            $query['q'] = $params['q'];
        }
        if (array_key_exists('series', $params) && is_bool($params['series'])) {
            $query['series'] = $params['series'];
        }
        if (array_key_exists('limit', $params) && !empty(intval($params['limit']))) {
            $query['limit'] = intval($params['limit']);
        }
        if (array_key_exists('offset', $params) && !empty(intval($params['offset']))) {
            $query['offset'] = intval($params['offset']);
        }
        if (array_key_exists('admin', $params) && is_bool($params['admin'])) {
            $query['admin'] = $params['admin'];
        }
        if (array_key_exists('sign', $params) && is_bool($params['sign'])) {
            $query['sign'] = $params['sign'];
        }

        $sortsASC = [
            'DATE_CREATED', 'DATE_MODIFIED', 'TITLE', 'SERIES_ID',
            'MEDIA_PACKAGE_ID', 'CREATOR', 'CONTRIBUTOR', 'LANGUAGE',
            'LICENSE','SUBJECT','DESCRIPTION','PUBLISHER',
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
     * Search for series matching the query parameters and returns JSON (object) by default or XML (text) on demand
     *
     * @param array $params the params to pass to the call: it must cointain the following:
     * $params = [
     *      'id' = '{The series ID. If the additional boolean parameter "episodes" is "true", the result set will include this series episodes.}'
     *      'q' => '{Any series that matches this free-text query. If the additional boolean parameter "episodes" is "true", the result set will include this series episodes.}',
     *      'episodes' => '{ Whether to include this series episodes. This can be used in combination with "id" or "q". (Default value=false)}',
     *      'sort' => '{ The sort order. May include any of the following: DATE_CREATED, DATE_MODIFIED, TITLE, SERIES_ID, MEDIA_PACKAGE_ID, CREATOR, CONTRIBUTOR, LANGUAGE, LICENSE, SUBJECT, DESCRIPTION, PUBLISHER. Add '_DESC' to reverse the sort order (e.g. TITLE_DESC). }',
     *      'limit' => '{ The maximum number of items to return per page. (Default value=20)}',
     *      'offset' => '{The page number. (Default value=0)}',
     *      'admin' => '{Whether this is an administrative query (Default value=false)}',
     *      'sign' => '{If results are to be signed (Default value=true)}',
     * ]
     * @param string $format The output format (json or xml) of the response body. (Default value = 'json')
     *
     * @return array the response result ['code' => 200, 'body' => '{The search results, formatted as xml or json}']
     */
    public function getSeries($params = [], $format = '')
    {
        $uri = self::URI . "/series.json";
        if (!empty($format) && strtolower($format) == 'xml') {
            $uri = str_replace('json', 'xml', $uri);
        }

        $query = [];
        if (array_key_exists('id', $params) && !empty($params['id'])) {
            $query['id'] = $params['id'];
        }
        if (array_key_exists('q', $params) && !empty($params['q'])) {
            $query['q'] = $params['q'];
        }
        if (array_key_exists('episodes', $params) && is_bool($params['episodes'])) {
            $query['episodes'] = $params['episodes'];
        }
        if (array_key_exists('limit', $params) && !empty(intval($params['limit']))) {
            $query['limit'] = intval($params['limit']);
        }
        if (array_key_exists('offset', $params) && !empty(intval($params['offset']))) {
            $query['offset'] = intval($params['offset']);
        }
        if (array_key_exists('admin', $params) && is_bool($params['admin'])) {
            $query['admin'] = $params['admin'];
        }
        if (array_key_exists('sign', $params) && is_bool($params['sign'])) {
            $query['sign'] = $params['sign'];
        }

        // OC <= 15
        if ($this->lucene) {
            $sortsASC = [
                'DATE_CREATED', 'DATE_MODIFIED', 'TITLE', 'SERIES_ID',
                'MEDIA_PACKAGE_ID', 'CREATOR', 'CONTRIBUTOR', 'LANGUAGE',
                'LICENSE','SUBJECT','DESCRIPTION','PUBLISHER',
            ];
            $sortsDESC = array_map(function ($sort) {
                return "{$sort}_DESC";
            }, $sortsASC);

            $sorts = array_merge($sortsASC, $sortsDESC);

            if (array_key_exists('sort', $params) && !empty($params['sort']) &&
                in_array($params['sort'], $sorts)) {
                $query['sort'] = $params['sort'];
            }

        // OC >= 16
        } else {
            $sorts = [
                'modified', 'title', 'creator', 'contributor'
            ];

            $sortsASC = array_map(function ($sort) {
                return "{$sort} asc";
            }, $sorts);

            $sortsDESC = array_map(function ($sort) {
                return "{$sort} desc";
            }, $sorts);

            $sorts_list = array_merge($sorts, $sortsASC, $sortsDESC);

            if (array_key_exists('sort', $params) && !empty($params['sort']) &&
                in_array(strtolower($params['sort']), $sorts_list)) {
                $query['sort'] = strtolower($params['sort']);
            }
        }

        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }
}
?>
