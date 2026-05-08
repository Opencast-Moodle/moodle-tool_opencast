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

class OcSysinfo extends OcRest
{
    const URI = '/sysinfo';

    public function __construct($restClient) {
        $restClient->registerHeaderException('Accept', self::URI);
        parent::__construct($restClient);
    }

    /**
     * Return the common OSGi build version and build number of all bundles matching the given prefix.
     *
     * @param string $prefix (optional) The bundle name prefixes to check. Defaults to 'opencast'.
     *
     * @return array the response result ['code' => 200, 'body' => '{An object of version structure}']
     */
    public function getVersion($prefix = '') {
        $uri = self::URI . "/bundles/version";

        $query = [];
        if (!empty($prefix)) {
            $query['prefix'] = $prefix;
        }

        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }
}
