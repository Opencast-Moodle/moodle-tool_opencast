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

class OcAgentsApi extends OcRest
{
    const URI = '/api/agents';

    public function __construct($restClient) {
        // The Agents API is available since API version 1.1.0.
        parent::__construct($restClient);
    }

    /**
     * Returns a list of capture agents.
     *
     * @param int $limit (optional) The maximum number of results to return for a single request (Default value=0)
     * @param int $offset (optional) The index of the first result to return (Default value=0)
     *
     * @return array the response result ['code' => 200, 'body' => '{A (potentially empty) list of agents is returned}']
     */
    public function getAll($limit = 0, $offset = 0) {
        $query = [];
        if (!empty($limit)) {
            $query['limit'] = intval($limit);
        }
        if (!empty($offset)) {
            $query['offset'] = intval($offset);
        }
        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet(self::URI, $options);
    }

    /**
     * Returns a single capture agent.
     *
     * @param string $agentId The agent id
     *
     * @return array the response result ['code' => 200, 'body' => '{The agent is returned}']
     */
    public function get($agentId) {
        $uri = self::URI . "/$agentId";
        return $this->restClient->performGet($uri);
    }
}
