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

class OcSecurityApi extends OcRest
{
    const URI = '/api/security';

    public function __construct($restClient) {
        parent::__construct($restClient);
    }

    /**
     * Returns a signed URL that can be played back for the indicated period of time,
     * while access is optionally restricted to the specified IP address.
     *
     * @param string $url The URL to be signed
     * @param string $validUntil The date and time until when the signed URL is valid (type of ISO 8602) e.g. "2018-03-11T13:23:51Z"
     * @param string $validSource The IP address from which the url can be accessed
     *
     * @return array the response result ['code' => 200, 'body' => '{The signed URL}']
     */
    public function sign($url, $validUntil = '', $validSource = '') {
        $uri = self::URI . "/sign";
        $formData = [
            'url' => $url,
        ];
        if (!empty($validUntil)) {
            $formData['valid-until'] = $validUntil;
        }
        if (!empty($validSource)) {
            $formData['valid-source'] = $validSource;
        }

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }
}
