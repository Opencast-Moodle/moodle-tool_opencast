<?php
namespace OpencastApi\Rest;

class OcGroupsApi extends OcRest
{
    const URI = '/api/groups';

    public function __construct($restClient)
    {
        parent::__construct($restClient);
    }

    ## [Section 1]: General API endpoints.

    /**
     * Returns a list of groups.
     * 
     * @param array $params (optional) The list of query params to pass which can contain the followings:
     * [
     *      'sort' => (array) {an assiciative array for sorting e.g. ['name' => 'DESC', 'description' => '', 'role' => '']},
     *      'limit' => (int) {the maximum number of results to return},
     *      'offset' => (int) {the index of the first result to return},
     *      'filter' => (array) {an assiciative array for filtering e.g. ['name' => '{Groups where the name specified in the metadata field match}']},
     * ]     
     * 
     * @return array the response result ['code' => 200, 'body' => '{A (potentially empty) list of groups}']
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
            'sort', 'limit', 'offset', 'filter'
        ];

        foreach ($params as $param_name => $param_value) {
            if (in_array($param_name, $acceptableParams) && !array_key_exists($param_name, $query)) {
                $query[$param_name] = $param_value;
            }
        }

        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }

    /**
     * Returns a single group.
     * 
     * @param string $groupId the identifier of the group.
     * 
     * @return array the response result ['code' => 200, 'body' => '{The group}']
     */
    public function get($groupId)
    {
        $uri = self::URI . "/{$groupId}";
        return $this->restClient->performGet($uri);
    }

    /**
     * Creates a group.
     * 
     * @param string $name Group Name
     * @param string $description (optional) Group Description
     * @param array $roles (optional) list of roles
     * @param array $members (optional) list of members
     * 
     * @return array the response result ['code' => 201, 'reason' => 'CREATED'] (A new group is created)
     */
    public function create($name, $description = '', $roles = [], $members = [])
    {
        $uri = self::URI;

        $formData = [
            'name' => $name
        ];
        if (!empty($description)) {
            $formData['description'] = $description;
        }
        if (!empty($roles)) {
            $formData['roles'] = implode(',', $roles);
        }
        if (!empty($members)) {
            $formData['members'] = implode(',', $members);
        }

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Updates a group.
     * If any of form parameters are ommited, the respective fields of the group will not be changed.
     * 
     * @param string $groupId group id
     * @param string $name (optional) Group Name
     * @param string $description (optional) Group Description
     * @param array $roles (optional) list of roles
     * @param array $members (optional) list of members
     * 
     * @return array the response result ['code' => 201, 'reason' => 'CREATED'] (The group has been updated)
     */
    public function update($groupId, $name = '', $description = '', $roles = [], $members = [])
    {
        $uri = self::URI . "/{$groupId}";
        $formData = [
            'name' => $name
        ];
        if (!empty($description)) {
            $formData['description'] = $description;
        }
        if (!empty($roles)) {
            $formData['roles'] = implode(',', $roles);
        }
        if (!empty($members)) {
            $formData['members'] = implode(',', $members);
        }

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPut($uri, $options);
    }

    /**
     * Deletes a group.
     * 
     * @param string $groupId group id
     * 
     * @return array the response result ['code' => 204, 'reason' => 'No Content'] (The group has been deleted)
     */
    public function delete($groupId)
    {
        $uri = self::URI . "/{$groupId}";
        return $this->restClient->performDelete($uri);
    }

    ## End of: [Section 1]: General API endpoints.

    ## [Section 2]: Members.

    /**
     * Adds a member to a group.
     * 
     * @param string $groupId group id
     * @param string $member The username of the member to be added
     * 
     * @return array the response result:
     * ['code' => 204, 'reason' => 'No Content'] (The member has been added)
     * ['code' => 200, 'reason' => 'OK'] (The member was already member of the group)
     */
    public function addMember($groupId, $member)
    {
        $uri = self::URI . "/{$groupId}/members";
        $formData = [
            'member' => $member
        ];
        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Removes a member from a group
     * 
     * @param string $groupId group id
     * @param string $memberId member id
     * 
     * @return array the response result ['code' => 204, 'reason' => 'No Content'] (The member has been removed)
     */
    public function deleteMember($groupId, $memberId)
    {
        $uri = self::URI . "/{$groupId}/members/{$memberId}";
        return $this->restClient->performDelete($uri);
    }

    ## End of: [Section 2]: Members.
}
?>