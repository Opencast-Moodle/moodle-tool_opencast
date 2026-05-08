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

class OcServices extends OcRest
{
    const URI = '/services';

    public function __construct($restClient) {
        $restClient->registerHeaderException('Accept', self::URI);
        parent::__construct($restClient);
    }


    /**
     * Returns a service registraton or list of available service registrations as object (JSON) by default or XML (text) on demand.
     *
     * @param string $serviceType (optional) The service type identifier
     * @param string $host (optional) The host, including the http(s) protocol
     * @param string $format (optional) The output format (json or xml) of the response body. (Default value = 'json')
     *
     * @return array the response result ['code' => 200, 'body' => '{the available service, formatted as xml or json}']
     */
    public function getServiceJSON($serviceType = '', $host = '', $format = '') {
        $uri = self::URI . '/services.json';
        if (!empty($format) && strtolower($format) == 'xml') {
            $uri = str_replace('json', 'xml', $uri);
        }

        $query = [];
        if (!empty($serviceType)) {
            $query['serviceType'] = $serviceType;
        }
        if (!empty($host)) {
            $query['host'] = $host;
        }

        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }
}
