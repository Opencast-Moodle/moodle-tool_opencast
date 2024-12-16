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
 * @module     tool_opencast/maintenance
 * @copyright  2024 Farbod Zamani Boroujeni (zamani@elan-ev.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Ajax from 'core/ajax';
import * as Notification from 'core/notification';
import * as Str from 'core/str';
import * as Toast from 'core/toast';

/**
 * Initializes the tool maintenance js module.
 */
export const init = () => {
    // Load strings
    var strings = [
        {key: 'maintenancemode_modal_sync_confirmation_title', component: 'tool_opencast'},
        {key: 'maintenancemode_modal_sync_confirmation_text', component: 'tool_opencast'},
        {key: 'maintenancemode_modal_sync_confirmation_btn', component: 'tool_opencast'},
        {key: 'maintenancemode_modal_sync_error_title', component: 'tool_opencast'},
        {key: 'maintenancemode_modal_sync_error_noinstance_message', component: 'tool_opencast'},
        {key: 'maintenancemode_modal_sync_failed', component: 'tool_opencast'},
        {key: 'maintenancemode_modal_sync_succeeded', component: 'tool_opencast'},
    ];
    Str.get_strings(strings).then(function(jsstrings) {
        // Required functionality for admin_setting_configdatetimeselector.
        const datetimeselectors = document.querySelectorAll('.form-setting .opencast_config_dt_selector');
        datetimeselectors.forEach((dtblock) => {
            if (dtblock?.dataset?.isoptional) {
                const enablingelement = document.getElementById(`${dtblock.dataset.settingid}_enabled`);
                const initialvalue = enablingelement?.checked ?? false;
                const selects = dtblock.querySelectorAll(`.opencast-config-dt-select`);
                selects.forEach((select) => {
                    select.disabled = !initialvalue;
                });
                enablingelement.addEventListener('change', (event) => {
                    selects.forEach((select) => {
                        select.disabled = !event.target.checked;
                    });
                });
            }
        });

        // Sync Button.
        const syncbtns = document.querySelectorAll('.maintenance-sync-btn');
        syncbtns.forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const ocinstanceid = e.target?.dataset?.ocinstanceid;
                if (!ocinstanceid) {
                    Notification.alert(jsstrings[3], jsstrings[4]);
                    return;
                }

                Notification.confirm(
                    jsstrings[0], jsstrings[1], jsstrings[2], null,
                    () => performSync(ocinstanceid, jsstrings)
                );
            });
            // Make the button accessible to use after the listener is added.
            btn.removeAttribute('disabled');
            btn.removeAttribute('title');
            btn.classList.remove('disabled');
            btn.classList.remove('btn-warning');
            btn.classList.add('btn-primary');
        });

        return;
    }).catch(Notification.exception);
};

/**
 * Perform sync request via Ajax call.
 * @param {int} ocinstanceid
 * @param {array} jsstrings
 */
const performSync = (ocinstanceid, jsstrings) => {
    if (!ocinstanceid) {
        return;
    }
    Ajax.call([{
        methodname: 'tool_opencast_maintenance_sync',
        args: {ocinstanceid: ocinstanceid},
    }])[0]
    .then((data) => {
        if (!data?.status) {
            Toast.add(jsstrings[5], {type: 'danger'});
            return;
        }
        Toast.add(jsstrings[6], {type: 'success'});
        reloadWithDelay();
        return;
    })
    .catch((error) => Notification.exception(error));
};

/**
 * Reloads the current page with a delay.
 * @param {int} delay default 3000 ms
 */
const reloadWithDelay = (delay = 3000) => {
    setTimeout(() => {
        window.location.reload();
    }, delay);
};

/**
 * Opencast Tool maintenance notification handler.
 *
 * It is used to make sure that there is only one maintenance notification printed at a time.
 *
 * @param {string} message
 * @param {string} level
 * @param {bool} notify
 */
export const notification = (message, level, notify) => {
    if (!window?.ocMaintenanceNotified && notify) {
        Notification.addNotification({
            message: message,
            type: level
        });
        window.ocMaintenanceNotified = true;
    }
};
