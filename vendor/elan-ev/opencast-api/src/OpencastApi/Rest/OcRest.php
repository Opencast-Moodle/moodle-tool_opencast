<?php
namespace OpencastApi\Rest;

abstract class OcRest {
    /** @var OcRestClient */
    protected $restClient;

    public function __construct($restClient)
    {
        $this->restClient = $restClient;
    }

    /**
     * Converts the array of sorting into comma-separated list of sort criteria "Sort:Attr"
     *
     * @param array $sort the array of sorting params
     *
     * @return string consumable sorting string
     */
    protected function convertArrayToSorting($sort) {
        return implode(',', array_map(function (string $k, string $v) {
            return "{$k}:{$v}";
        }, array_keys($sort), array_values($sort)));
    }

    /**
     * Converts the array of filtering into comma-separated list of filter criteria "Filter:value"
     *
     * @param array $filters the array of filters
     *
     * @return string consumable filtering string
     */
    protected function convertArrayToFiltering($filters) {
        return implode(',', array_map(function (string $k, $v) {
            $filter = '';
            if (is_array($v)) {
                $filterArray = [];
                foreach ($v as $filter_value) {
                    $filterArray[] = "{$k}:{$filter_value}";
                }
                $filter = implode(',', $filterArray);
            } else {
                $filter = "{$k}:{$v}";
            }
            return $filter;
        }, array_keys($filters), array_values($filters)));
    }

    /**
     * To perform the request with disposable X-RUN-WITH-ROLES header.
     *
     * @param array $roles an array of roles to run with
     *
     * @return object $this the class object where the call is requested from.
     */
    public function runWithRoles($roles = [])
    {
        if (!empty($roles)) {
            $roles = is_array($roles) ? implode(', ', $roles) : $roles;
            $this->restClient->registerAdditionalHeader('X-RUN-WITH-ROLES', $roles);
        }
        return $this;
    }

    /**
     * To perform the request with disposable X-RUN-AS-USER header.
     *
     * @param string $user the user to run the request with
     *
     * @return object $this the class object where the call is requested from.
     */
    public function runAsUser($user)
    {
        $user = trim($user);
        if (!empty($user)) {
            $this->restClient->registerAdditionalHeader('X-RUN-AS-USER', $user);
        }
        return $this;
    }

    /**
     * To perform the request without header.
     *
     * @return object $this the class object where the call is requested from.
     */
    public function noHeader()
    {
        $this->restClient->enableNoHeader();
        return $this;
    }

    /**
     * Sets a timeout for a single request
     *
     * @param int $timeout the timeout in seconds (Default = 0)
     *
     * @return object $this the class object where the call is requested from.
     */
    public function setRequestTimeout($timeout = 0)
    {
        $this->restClient->setRequestTimeout($timeout);
        return $this;
    }

    /**
     * Sets a connection timeout for a single request
     *
     * @param int $connectionTimeout the connection timeout in seconds (Default = 0)
     *
     * @return object $this the class object where the call is requested from.
     */
    public function setRequestConnectionTimeout($connectionTimeout = 0)
    {
        $this->restClient->setRequestConnectionTimeout($connectionTimeout);
        return $this;
    }
}
?>
