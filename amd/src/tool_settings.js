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
 * Javascript to initialise the opencast tool settings.
 *
 * @package    tool_opencast
 * @copyright  2021 Tamara Gunkel, University of Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Tabulator from 'block_opencast/tabulator';
import $ from 'jquery';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import * as str from 'core/str';

export const init = (instancesinputid) => {

    // Load strings
    var strings = [
        {key: 'name', component: 'tool_opencast'},
        {key: 'isvisible', component: 'tool_opencast'},
        {key: 'addinstance', component: 'tool_opencast'},
        {key: 'delete_instance', component: 'tool_opencast'},
        {key: 'delete_instance_confirm', component: 'tool_opencast'},
        {key: 'delete', component: 'moodle'}
    ];
    str.get_strings(strings).then(function (jsstrings) {
        // Style hidden input.
        var instancesinput = $('#' + instancesinputid);

        if(!instancesinput.length) {
            return;
        }

        instancesinput.parent().hide();
        instancesinput.parent().next().hide(); // Default value.

        var instancestable = new Tabulator("#instancestable", {
            data: JSON.parse(instancesinput.val()),
            layout: "fitColumns",
            dataChanged: function (data) {
                // TODO make sure that there is at least one.
             //    data = data.filter(value => value.name); // todo warn user instead if name is empty
                instancesinput.val(JSON.stringify(data));
            },
            columns: [
                {title: 'ID', field: "id", widthGrow: 0},
                // TODO allow admins to change the ID (should only be used for importing settings)
                {title: jsstrings[0], field: "name", editor: "input", widthGrow: 2,},
                {
                    title: jsstrings[1],
                    field: "isvisible",
                    hozAlign: "center",
                    widthGrow: 0,
                    formatter: function (cell) {
                        var input = document.createElement('input');
                        input.type = 'checkbox';
                        input.checked = cell.getValue();
                        input.addEventListener('click', function () {
                            cell.getRow().update({'isvisible': $(this).prop('checked') ? 1 : 0});
                        });
                        return input;
                    }
                },
                {
                    title: "",
                    width: 40,
                    headerSort: false,
                    hozAlign: "center",
                    formatter: function () {
                        return '<i class="icon fa fa-trash fa-fw"></i>';
                    },
                    cellClick: function (e, cell) {
                        ModalFactory.create({
                            type: ModalFactory.types.SAVE_CANCEL,
                            title: jsstrings[3],
                            body: jsstrings[4]
                        })
                            .then(function (modal) {
                                modal.setSaveButtonText(jsstrings[5]);
                                modal.getRoot().on(ModalEvents.save, function () {
                                    cell.getRow().delete();
                                });
                                modal.show();
                            });
                    }
                }
            ],
        });

        $('#addrow-instancestable').click(function () {
            var instances = JSON.parse(instancesinput.val());
            var id = 0;
            instances.forEach(function (x) {
                if (x.id > id) {
                    id = x.id;
                }
            });
            instancestable.addRow({'id': id + 1, 'isvisible': false, 'isdefault': false});
        });
    });
};

