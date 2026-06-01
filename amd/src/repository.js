// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Javascript to handle maintenance mode in tool opencast.
 *
 * @module     tool_opencast/repository
 * @copyright  2026 Farbod Zamani Boroujeni (zamani@elan-ev.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';

export const jwtRefreshToken = (
    contextid,
    ocinstanceid,
    accesstoken,
) => fetchMany([{
    methodname: 'tool_opencast_jwt_refresh_token',
    args: {
        contextid,
        ocinstanceid,
        accesstoken,
    },
}])[0];
