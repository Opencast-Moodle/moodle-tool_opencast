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
 * Opencast JWT Service.
 *
 * @module     tool_opencast/tool_jwt_service
 * @copyright  2026 Farbod Zamani (zamani@elan-ev.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {jwtRefreshToken} from './repository';
import * as Notification from 'core/notification';
import * as Str from 'core/str';

const extractBaseUrl = (url) => {
    let urlObj = new URL(url);
    return `${urlObj.protocol}//${urlObj.hostname}` + (urlObj.port ? ':' + urlObj.port : '');
};

const extractJwtFromUrl = (url) => {
    const urlObj = new URL(url);
    return urlObj.searchParams.get('jwt') ?? null;
};

const postNotification = (message, type) => {
    Notification.addNotification({
        message: message,
        type: type,
    });
};

const sendInitMessage = async(contextid, ocinstanceid, identifier, iframe, initialJwt, jsstrings) => {
    const newAccessToken = await refreshAccessToken(contextid, ocinstanceid, initialJwt);
    if (!newAccessToken) {
        postNotification(jsstrings[1], 'error');
        window.console.error('OC JWT Service: failed to get refresh token!');
    }
    iframe.contentWindow.postMessage(
        {
            type: "oc-event-jwt",
            event: identifier,
            jwt: newAccessToken,
        },
        origin
    );
};

const validateIframeMessageRefreshToken = (ev, origin, identifier) => {
    if (origin !== ev.origin) {
        return false;
    }

    if (
        typeof ev.data === "object"
        && (ev.data.type && ev.data.type === "oc-event-jwt-request")
        && (ev.data.event && typeof ev.data.event === 'string')
    ) {
        if (ev.data.event !== identifier) {
            return false;
        }
    } else {
        return false;
    }

    return true;
};

const registerListeners = (contextid, ocinstanceid, identifier, iframe, jsstrings) => {
    const origin = extractBaseUrl(iframe.src);
    const initialJwt = extractJwtFromUrl(iframe.src);
    window.console.info('OC JWT Service: Registering Listeners...');
    sendInitMessage(contextid, ocinstanceid, identifier, iframe, initialJwt, jsstrings);

    window.addEventListener("message", async(ev) => {
        window.console.info('OC JWT Service: got message from iframe.');
        if (!validateIframeMessageRefreshToken(ev, origin, identifier)) {
            postNotification(jsstrings[2], 'error');
            window.console.error('OC JWT Service: iframe message is invalid!');
            return;
        }
        window.console.info('OC JWT Service: Message validated!');
        const lastJwt = ev.data?.jwt ?? initialJwt;
        const newAccessToken = await refreshAccessToken(contextid, ocinstanceid, lastJwt);
        if (!newAccessToken) {
            postNotification(jsstrings[1], 'error');
            window.console.error('OC JWT Service: failed to get refresh token!');
        }

        ev.source.postMessage(
            {
                type: "oc-event-jwt",
                event: identifier,
                jwt: newAccessToken,
            },
            origin
        );
        window.console.info('OC JWT Service: Message Sent Back to iframe.');
    });
};

const refreshAccessToken = async(contextid, ocinstanceid, jwt) => {
    try {
        const response = await jwtRefreshToken(contextid, ocinstanceid, jwt);
        const data = JSON.parse(response);
        return data.accesstoken ?? '';
    } catch (error) {
        window.console.error(error);
        return false;
    }
};

export const initIframeRefreshToken = (contextid, ocinstanceid, iframeid, identifier) => {
    // Load strings
    var strings = [
        {key: 'jwt_error_unabletofindiframeplayer', component: 'tool_opencast'},
        {key: 'jwt_error_refreshtokenfailed', component: 'tool_opencast'},
        {key: 'jwt_error_iframetokenrequestfailed', component: 'tool_opencast'},
    ];
    Str.get_strings(strings).then(jsstrings => {
        const iframe = document.getElementById(iframeid);
        if (!iframe || !iframe?.src) {
            postNotification(jsstrings[0], 'error');
        }
        registerListeners(contextid, ocinstanceid, identifier, iframe, jsstrings);
        return;
    }).catch(Notification.exception);
};

export const submitRedirectForm = (formId = 'jwtRedirectForm', timeout = 0) => {
    let form = document.getElementById(formId);
    if (form) {
        setTimeout(() => {
            form.submit();
        }, timeout);
    }
};
