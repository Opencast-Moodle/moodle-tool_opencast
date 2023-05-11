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
 * AMD module for opencast connection test tool
 *
 * @module     tool_opencast/tool_testtool
 * @copyright  2021 Farbod Zamani (zamani@elan-ev.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
        'jquery',
        'core/ajax',
        'core/str',
        'core/modal_factory',
        'core/notification'],
    function($, Ajax, Str, ModalFactory, Notification) {

        /**
         * TestTool class.
         */
        var TestTool = function() {
            this.activateButton();
            this.registerClickEvent();
        };

        /**
         * Register button activation.
         */
        TestTool.prototype.activateButton = function() {
            $('.testtool-modal').each(function() {
                if ($(this).is(':visible') && $(this).hasClass('disabled')) {
                    $(this).removeAttr('disabled');
                    $(this).removeAttr('title');
                    $(this).removeClass('disabled btn-warning');
                    $(this).addClass('btn-secondary');
                }
            });
        };
        /**
         * Register event listener.
         */
        TestTool.prototype.registerClickEvent = function() {
            $('.testtool-modal').click(function(e) {
                e.preventDefault();
                var instanceid = $(e.target).data('instanceid');
                var suffix = (instanceid) ? '_' + instanceid : '';


                var apiurl = $('#admin-apiurl' + suffix).find('input').val();
                var apiusername = $('#admin-apiusername' + suffix).find('input').val();
                var apipassword = $('#admin-apipassword' + suffix).find('input').val();
                var apitimeout = $('#admin-apitimeout' + suffix).find('input').val();
                var apiconnecttimeout = $('#admin-apiconnecttimeout' + suffix).find('input').val();

                var args = {
                    'apiurl': apiurl,
                    'apiusername': apiusername,
                    'apipassword': apipassword,
                    'apitimeout': apitimeout,
                    'apiconnecttimeout': apiconnecttimeout
            };

                // Get options.
                var request = [{methodname: 'tool_opencast_connection_test_tool', args: args}];
                var promise = Ajax.call(request);

                var titlePromise = Str.get_string('testtoolurl', 'tool_opencast');
                var modalPromise = ModalFactory.create({type: ModalFactory.types.CANCEL});
                $.when(promise[0], titlePromise, modalPromise).then(function(connectionTestResponse, title, modal) {
                    modal.setTitle(title);
                    modal.setBody(connectionTestResponse.testresult);

                    modal.show();
                    return modal;
                }).catch(Notification.exception);
            });
        };

        return /** @alias tool_opencast/tool_testtool */ {

            /**
             * Initialise the module.
             *
             * @method init
             * @return {TestTool}
             */
            'init': function() {
                return new TestTool();
            }
        };
    });
