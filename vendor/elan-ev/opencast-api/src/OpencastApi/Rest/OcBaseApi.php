<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace OpencastApi\Rest;

class OcBaseApi extends OcRest
{
    const USER_INFO_URI = '/api/info/me';
    const ORGANIZATION_INFO_URI = '/api/info/organization';
    const VERSION_URI = '/api/version';

    public function __construct($restClient) {
        parent::__construct($restClient);
    }

    /**
     * Returns key characteristics of the API such as the server name and the default version.
     *
     * @return array the response result ['code' => 200, 'body' => '{The api information is returned.}']
     */
    public function get() {
        return $this->restClient->performGet('/api');
    }

    /**
     * Returns information on the logged in user.
     *
     * @return array the response result ['code' => 200, 'body' => '{The api user information is returned.}']
     */
    public function getUserInfo() {
        return $this->restClient->performGet(self::USER_INFO_URI);
    }

    /**
     * Returns current user's roles.
     *
     * @return array the response result ['code' => 200, 'body' => '{The set of roles is returned. }']
     */
    public function getUserRole() {
        return $this->restClient->performGet(self::USER_INFO_URI . '/roles');
    }

    /**
     * Returns the current organization.
     *
     * @return array the response result ['code' => 200, 'body' => '{The organization details are returned.}']
     */
    public function getOrg() {
        return $this->restClient->performGet(self::ORGANIZATION_INFO_URI);
    }

    /**
     * Returns the current organization's properties.
     *
     * @return array the response result ['code' => 200, 'body' => '{The organization properties are returned.}']
     */
    public function getOrgProps() {
        return $this->restClient->performGet(self::ORGANIZATION_INFO_URI . '/properties');
    }

    /**
     * Returns the engage ui url property.
     *
     * @return array the response result ['code' => 200, 'body' => '{The engage ui url is returned.}']
     */
    public function getOrgEngageUIUrl() {
        return $this->restClient->performGet(self::ORGANIZATION_INFO_URI . '/properties/engageuiurl');
    }

    /**
     * Returns a list of available version as well as the default version.
     *
     * @return array the response result ['code' => 200, 'body' => '{The version list is returned}']
     */
    public function getVersion() {
        return $this->restClient->performGet(self::VERSION_URI);
    }

    /**
     * Returns the default version.
     *
     * @return array the response result ['code' => 200, 'body' => '{The default version is returned}']
     */
    public function getDefaultVersion() {
        return $this->restClient->performGet(self::VERSION_URI . '/default');
    }
}
