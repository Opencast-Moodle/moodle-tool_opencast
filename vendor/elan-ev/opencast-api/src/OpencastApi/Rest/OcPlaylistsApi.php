<?php
namespace OpencastApi\Rest;

class OcPlaylistsApi extends OcRest
{
    const URI = '/api/playlists';

    public function __construct($restClient)
    {
        // The Playlist API is available since API version 1.11.0.
        parent::__construct($restClient);
    }

    ## [Section 1]: General API endpoints.

    /**
     * Get playlists.
     * Playlists that you do not have read access to will not show up.
     *
     * @param array $params (optional) The list of query params to pass which can contain the followings:
     * [
     *      'limit' => (int) {The maximum number of results to return for a single request},
     *      'offset' => (int) {The index of the first result to return},
     *      'sort' => {The sort criteria. A criteria is specified by a case-sensitive sort name and the order separated by a colon (e.g. updated:ASC). Supported sort names: 'updated'. Use the order ASC to sort ascending or DESC to sort descending.}
     * ]
     *
     * @return array the response result ['code' => 200, 'body' => '{A (potentially empty) list of playlists}']
     */
    public function getAll($params = [])
    {
        $uri = self::URI;

        $query = [];

        if (array_key_exists('limit', $params) && !empty($params['limit'])) {
            $query['limit'] = $params['limit'];
        }
        if (array_key_exists('offset', $params) && !empty($params['offset'])) {
            $query['offset'] = $params['offset'];
        }

        $supportedSortNames = ['updated'];
        $supportedSorts= [];
        foreach ($supportedSortNames as $sortName) {
            $supportedSorts[] = "$sortName:ASC";
            $supportedSorts[] = "$sortName:DESC";
        }

        if (array_key_exists('sort', $params) && !empty($params['sort']) &&
            in_array($params['sort'], $supportedSorts)) {
            $query['sort'] = $params['sort'];
        }

        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }

    /**
     * Get a playlist.
     *
     * @param string $playlistId the identifier of the playlist
     *
     * @return array the response result ['code' => 200, 'body' => '{The playlist (object)}']
     */
    public function get($playlistId)
    {
        $uri = self::URI . "/{$playlistId}";
        return $this->restClient->performGet($uri);
    }

    /**
     * Creates a playlist.
     *
     * @param string|array $playlist A playlist
     *
     * @return array the response result ['code' => 201, 'body' => '{The new playlist (object)}']
     */
    public function create($playlist)
    {
        $formData = [
            'playlist' => $playlist,
        ];

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost(self::URI, $options);
    }

    /**
     * Updates a playlist.
     *
     * @param string $playlistId the identifier of the playlist
     * @param string|array $playlist the updated playlist
     *
     * @return array the response result ['code' => 200, 'body' => '{The updated playlist (object)}']
     */
    public function update($playlistId, $playlist)
    {
        $uri = self::URI . "/{$playlistId}";

        $formData = [
            'playlist' => $playlist,
        ];

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPut($uri, $options);
    }

    /**
     * Removes a playlist.
     *
     * @param string $playlistId the identifier of the playlist
     *
     * @return array the response result ['code' => 200, 'body' => '{The removed playlist (object)}']
     */
    public function delete($playlistId)
    {
        $uri = self::URI . "/{$playlistId}";
        return $this->restClient->performDelete($uri);
    }

    ## End of [Section 1]: General API endpoints.

    ## [Section 2]: Entries.

    /**
     * Updates the entries of a playlist
     *
     * @param string $playlistId the identifier of the playlist
     * @param string|array $playlistEntries the playlist entries
     *
     * @return array the response result ['code' => 200, 'body' => '{The updated playlist (object)}']
     */
    public function updateEntries($playlistId, $playlistEntries)
    {
        $uri = self::URI . "/{$playlistId}";

        $formData = [
            'playlist' => [
                'entries' => $playlistEntries
            ]
        ];

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPut($uri, $options);
    }

    /**
     * Removes all entries of the playlist
     *
     * @param string $playlistId the identifier of the playlist
     *
     * @return array the response result ['code' => 200, 'body' => '{The updated playlist (object)}']
     */
    public function emptyEntries($playlistId)
    {
        return $this->updateEntries($playlistId, []);
    }

    ## End of [Section 2]: Entries.
}
?>
