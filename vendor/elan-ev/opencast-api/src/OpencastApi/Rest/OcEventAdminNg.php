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

class OcEventAdminNg extends OcRest
{
    const URI = '/admin-ng/event';

    public function __construct($restClient) {
        $restClient->registerHeaderException('Accept', self::URI);
        parent::__construct($restClient);
    }

    /**
     * Delete a single event.
     *
     * @param string $eventId The id of the event to delete.
     *
     * @return array the response result ['code' => 200, 'reason' => 'OK'] (OK if the event has been deleted.)
     */
    public function delete($eventId) {
        $uri = self::URI . "/{$eventId}";
        return $this->restClient->performDelete($uri);
    }
}
