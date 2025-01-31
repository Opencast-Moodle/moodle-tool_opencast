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
 * @module     tool_opencast/tool_settings
 * @copyright  2021 Tamara Gunkel, University of Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Tabulator from 'tool_opencast/tabulator';
import $ from 'jquery';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import * as str from 'core/str';
import Notification from "core/notification";

export const init = (rolesinputid, metadatainputid, metadataseriesinputid, transcriptionflavorinputid, ocinstanceid) => {

    // Load strings
    var strings = [
        {key: 'name', component: 'tool_opencast'},
        {key: 'isvisible', component: 'tool_opencast'},
        {key: 'addinstance', component: 'tool_opencast'},
        {key: 'delete_instance', component: 'tool_opencast'},
        {key: 'delete_instance_confirm', component: 'tool_opencast'},
        {key: 'delete', component: 'moodle'},
        {key: 'isdefault', component: 'tool_opencast'},
        {key: 'heading_role', component: 'tool_opencast'},
        {key: 'heading_actions', component: 'tool_opencast'},
        {key: 'heading_permanent', component: 'tool_opencast'},
        {key: 'delete_role', component: 'tool_opencast'},
        {key: 'delete_confirm_role', component: 'tool_opencast'},
        {key: 'delete_metadata', component: 'tool_opencast'},
        {key: 'delete_confirm_metadata', component: 'tool_opencast'},
        {key: 'heading_name', component: 'tool_opencast'},
        {key: 'heading_datatype', component: 'tool_opencast'},
        {key: 'heading_description', component: 'tool_opencast'},
        {key: 'heading_required', component: 'tool_opencast'},
        {key: 'heading_readonly', component: 'tool_opencast'},
        {key: 'heading_params', component: 'tool_opencast'},
        {key: 'heading_defaultable', component: 'tool_opencast'},
        {key: 'transcription_flavor_key', component: 'tool_opencast'},
        {key: 'transcription_flavor_value', component: 'tool_opencast'},
        {key: 'transcription_flavor_delete', component: 'tool_opencast'},
        {key: 'transcription_flavor_confirm_delete', component: 'tool_opencast'},
        {key: 'readonly_disabled_tooltip_text', component: 'tool_opencast'},
        {key: 'heading_batchable', component: 'tool_opencast'},
    ];
    str.get_strings(strings).then(function(jsstrings) {

        // We need to check and apply the transcription section first,
        // because it might be rendered in different sections (additional features)
        var hastranscription = false;
        var transcriptionflavorinput = $('#' + transcriptionflavorinputid);
        if (transcriptionflavorinput.is(':visible')) {
            hastranscription = true;
            transcriptionflavorinput.parent().hide();
            transcriptionflavorinput.parent().next().hide(); // Default value.
        }
        // Transcription flavor.
        // We run this part if only the transcription is available.
        if (hastranscription) {
            // Because flavors are introduced in a way that it needs to take its value from the default,
            // and the input value is not set via an upgrade, therefore, we would need to introduce a new
            // way of extracting defaults and put it as its value.
            extractDefaults(transcriptionflavorinput);
            var transcriptionflavoroptions = new Tabulator("#transcriptionflavorsoptions_" + ocinstanceid, {
                data: JSON.parse(transcriptionflavorinput.val()),
                layout: "fitColumns",
                dataChanged: function(data) {
                    data = data.filter(value => value.key && value.value);
                    transcriptionflavorinput.val(JSON.stringify(data));
                },
                columns: [
                    {title: jsstrings[15], field: "key", headerSort: false, editor: "input", widthGrow: 1},
                    {title: jsstrings[16], field: "value", headerSort: false, editor: "input", widthGrow: 1},
                    {
                        title: "",
                        width: 40,
                        headerSort: false,
                        hozAlign: "center",
                        formatter: function() {
                            return '<i class="icon fa fa-trash fa-fw"></i>';
                        },
                        cellClick: function(e, cell) {
                            ModalFactory.create({
                                type: ModalFactory.types.SAVE_CANCEL,
                                title: jsstrings[17],
                                body: jsstrings[18]
                            })
                                .then(function(modal) {
                                    modal.setSaveButtonText(jsstrings[17]);
                                    modal.getRoot().on(ModalEvents.save, function() {
                                        cell.getRow().delete();
                                    });
                                    modal.show();
                                    return;
                                }).catch(Notification.exception);
                        }
                    }
                ],
            });

            $('#addrow-transcriptionflavorsoptions_' + ocinstanceid).click(function() {
                transcriptionflavoroptions.addRow({'key': '', 'value': ''});
            });
        }

        // Style hidden input.
        var rolesinput = $('#' + rolesinputid);
        rolesinput.parent().hide();
        rolesinput.parent().next().hide(); // Default value.

        // Don't create tables if they are not visible.
        if (!rolesinput.length) {
            return;
        }

        var metadatainput = $('#' + metadatainputid);
        metadatainput.parent().hide();
        metadatainput.parent().next().hide(); // Default value.

        var metadataseriesinput = $('#' + metadataseriesinputid);
        metadataseriesinput.parent().hide();
        metadataseriesinput.parent().next().hide(); // Default value.

        var rolestable = new Tabulator("#rolestable_" + ocinstanceid, {
            data: JSON.parse(rolesinput.val()),
            layout: "fitColumns",
            dataChanged: function(data) {
                data = data.filter(value => value.rolename);
                rolesinput.val(JSON.stringify(data));
            },
            columns: [
                {
                    title: jsstrings[0], field: "rolename", editor: "input", widthGrow: 4, cellEdited: function(cell) {
                        if (cell.getData().rolename.includes('[USERNAME]') || cell.getData().rolename.includes('[USERNAME_LOW]') ||
                            cell.getData().rolename.includes('[USERNAME_UP]')) {
                            // Tick permanent checkbox.
                            cell.getRow().update({'permanent': 1});
                            cell.getRow().getCell("permanent").getElement().getElementsByTagName("input")[0].disabled = true;
                        } else {
                            cell.getRow().getCell("permanent").getElement().getElementsByTagName("input")[0].disabled = false;
                        }
                    }
                },
                {title: jsstrings[1], field: "actions", editor: "input", widthGrow: 1},
                {
                    title: jsstrings[2],
                    field: "permanent",
                    hozAlign: "center",
                    widthGrow: 0,
                    formatter: function(cell) {
                        var input = document.createElement('input');
                        input.type = 'checkbox';
                        input.style.cursor = 'pointer';
                        input.checked = cell.getValue();
                        input.addEventListener('click', function() {
                            cell.getRow().update({'permanent': $(this).prop('checked') ? 1 : 0});
                        });

                        if (cell.getData().rolename.includes('[USERNAME]') || cell.getData().rolename.includes('[USERNAME_LOW]') ||
                            cell.getData().rolename.includes('[USERNAME_UP]')) {
                            input.disabled = true;
                        }

                        return input;
                    }
                },
                {
                    title: "",
                    width: 40,
                    headerSort: false,
                    hozAlign: "center",
                    formatter: function() {
                        return '<i class="icon fa fa-trash fa-fw"></i>';
                    },
                    cellClick: function(e, cell) {
                        ModalFactory.create({
                            type: ModalFactory.types.SAVE_CANCEL,
                            title: jsstrings[3],
                            body: jsstrings[4]
                        })
                            .then(function(modal) {
                                modal.setSaveButtonText(jsstrings[3]);
                                modal.getRoot().on(ModalEvents.save, function() {
                                    cell.getRow().delete();
                                });
                                modal.show();
                                return;
                            }).catch(Notification.exception);
                    }
                }
            ],
        });

        $('#addrow-rolestable_' + ocinstanceid).click(function() {
            rolestable.addRow({'rolename': '', 'actions': '', 'permanent': 0});
        });

        var metadatatable = new Tabulator("#metadatatable_" + ocinstanceid, {
            data: JSON.parse(metadatainput.val()),
            layout: "fitColumns",
            movableRows: true,
            rowMoved: function() {
                // Order by row position
                var data = metadatatable.getRows().map(row => row.getData());
                data = data.filter(value => value.name);
                metadatainput.val(JSON.stringify(data));
            },
            dataChanged: function() {
                // Order by row position
                var data = metadatatable.getRows().map(row => row.getData());
                data = data.filter(value => value.name);
                $('#' + metadatainputid).val(JSON.stringify(data));
            },
            columns: [
                {
                    title: jsstrings[7] + '   ' + $('#helpbtnname_' + ocinstanceid).html(),
                    field: "name",
                    editor: "input",
                    widthGrow: 1,
                    headerSort: false
                },
                {
                    title: jsstrings[8], field: "datatype", widthGrow: 1, headerSort: false, editor: "select", editorParams:
                        {
                            values: {
                                'text': 'String (text)',
                                'select': 'Drop Down (select)',
                                'autocomplete': 'Arrays (autocomplete)',
                                'textarea': 'Long Text (textarea)',
                                'date_time_selector': 'Date Time Selector (datetime)'
                            }
                        }
                },
                {
                    title: jsstrings[9] + '   ' + $('#helpbtndescription_' + ocinstanceid).html(),
                    field: "description",
                    editor: "textarea",
                    widthGrow: 2,
                    headerSort: false
                },
                {
                    title: jsstrings[10],
                    field: "required", hozAlign: "center", widthGrow: 0, headerSort: false, formatter:

                        function(cell) {
                            var input = document.createElement('input');
                            input.type = 'checkbox';
                            input.style.cursor = 'pointer';
                            input.checked = cell.getValue();
                            input.addEventListener('click', function() {
                                var checked = $(this).prop('checked');
                                cell.getRow().update({'required': checked ? 1 : 0});
                                // Make readonly disabled if this item is required.
                                var readonlyelm = cell.getRow().getCell("readonly").getElement();
                                var nodelist = readonlyelm.querySelectorAll('.readonly-checkbox');
                                if (checked && nodelist.length) {
                                    if (cell.getRow().getData()?.readonly) {
                                        nodelist[0].click();
                                    }
                                    nodelist[0].setAttribute('title', jsstrings[19]);
                                    nodelist[0].style.cursor = 'not-allowed';
                                    nodelist[0].disabled = true;
                                } else {
                                    nodelist[0].removeAttribute('title');
                                    nodelist[0].style.cursor = 'pointer';
                                    nodelist[0].disabled = false;
                                }
                            });

                            return input;
                        }
                },
                {
                    title: jsstrings[11] + '   ' + $('#helpbtnreadonly_' + ocinstanceid).html(),
                    field: "readonly", hozAlign: "center", widthGrow: 0, headerSort: false, formatter:
                        function(cell) {
                            if (cell.getRow().getCell("name").getValue() == 'title') {
                                return null;
                            }
                            var isrequired = cell.getRow().getData()?.required;
                            var input = document.createElement('input');
                            input.type = 'checkbox';
                            input.style.cursor = 'pointer';
                            input.classList.add('readonly-checkbox');
                            input.checked = isrequired ? false : cell.getValue();
                            if (isrequired) {
                                input.setAttribute('title', jsstrings[19]);
                                input.style.cursor = 'not-allowed';
                                input.disabled = true;
                            } else {
                                input.removeAttribute('title');
                                input.style.cursor = 'pointer';
                                input.disabled = false;
                            }
                            input.addEventListener('click', function() {
                                // Check if required is enabled.
                                if (cell.getRow().getData()?.required) {
                                    // If required is enabled, we disable this checkbox.
                                    $(this).prop('checked', false);
                                } else {
                                    // Otherwise, we provide the checkbox with normal input.
                                    cell.getRow().update({'readonly': $(this).prop('checked') ? 1 : 0});
                                }
                            });

                            return input;
                        }
                },
                {
                    title: jsstrings[12] + '   ' + $('#helpbtnparams_' + ocinstanceid).html(),
                    field: "param_json",
                    editor: "textarea",
                    widthGrow: 2,
                    headerSort: false
                },
                {
                    title: jsstrings[13] + '   ' + $('#helpbtndefaultable_' + ocinstanceid).html(),
                    field: "defaultable", hozAlign: "center", widthGrow: 0, headerSort: false, formatter:
                        function(cell) {
                            if (cell.getRow().getCell("name").getValue() == 'title') {
                                return null;
                            }
                            var input = document.createElement('input');
                            input.type = 'checkbox';
                            input.style.cursor = 'pointer';
                            input.checked = cell.getValue();
                            input.addEventListener('click', function() {
                                cell.getRow().update({'defaultable': $(this).prop('checked') ? 1 : 0});
                            });

                            return input;
                        }
                },
                {
                    title: jsstrings[20] + '   ' + $('#helpbtnbatchable_' + ocinstanceid).html(),
                    field: "batchable", hozAlign: "center", widthGrow: 0, headerSort: false, formatter:
                        function(cell) {
                            if (cell.getRow().getCell("name").getValue() == 'title') {
                                return null;
                            }
                            var input = document.createElement('input');
                            input.type = 'checkbox';
                            input.style.cursor = 'pointer';
                            input.checked = cell.getValue();
                            input.addEventListener('click', function() {
                                cell.getRow().update({'batchable': $(this).prop('checked') ? 1 : 0});
                            });

                            return input;
                        }
                },
                {
                    title: "", width: 40, headerSort: false, hozAlign: "center", formatter:
                        function() {
                            return '<i class="icon fa fa-trash fa-fw"></i>';
                        },
                    cellClick: function(e, cell) {
                        ModalFactory.create({
                            type: ModalFactory.types.SAVE_CANCEL,
                            title: jsstrings[5],
                            body: jsstrings[6]
                        })
                            .then(function(modal) {
                                modal.setSaveButtonText(jsstrings[14]);
                                modal.getRoot().on(ModalEvents.save, function() {
                                    cell.getRow().delete();
                                });
                                modal.show();
                                return;
                            }).catch(Notification.exception);
                    }
                }
            ],
        });

        $('#addrow-metadatatable_' + ocinstanceid).click(function() {
            metadatatable.addRow({'datatype': 'text', 'required': 0, 'readonly': 0, 'param_json': null});
        });

        var metadataseriestable = new Tabulator("#metadataseriestable_" + ocinstanceid, {
            data: JSON.parse(metadataseriesinput.val()),
            layout: "fitColumns",
            movableRows: true,
            rowMoved: function() {
                // Order by row position
                var data = metadataseriestable.getRows().map(row => row.getData());
                data = data.filter(value => value.name);
                metadataseriesinput.val(JSON.stringify(data));
            },
            dataChanged: function() {
                // Order by row position
                var data = metadataseriestable.getRows().map(row => row.getData());
                data = data.filter(value => value.name);
                $('#' + metadataseriesinputid).val(JSON.stringify(data));
            },
            columns: [
                {
                    title: jsstrings[7] + '   ' + $('#helpbtnname_' + ocinstanceid).html(),
                    field: "name",
                    editor: "input",
                    widthGrow: 1,
                    headerSort: false
                },
                {
                    title: jsstrings[8], field: "datatype", widthGrow: 1, headerSort: false, editor: "select", editorParams:
                        {
                            values: {
                                'text': 'String (text)',
                                'select': 'Drop Down (select)',
                                'autocomplete': 'Arrays (autocomplete)',
                                'textarea': 'Long Text (textarea)',
                                'date_time_selector': 'Date Time Selector (datetime)'
                            }
                        }
                },
                {
                    title: jsstrings[9] + '   ' + $('#helpbtndescription_' + ocinstanceid).html(),
                    field: "description",
                    editor: "textarea",
                    widthGrow: 2,
                    headerSort: false
                },
                {
                    title: jsstrings[10],
                    field: "required", hozAlign: "center", widthGrow: 0, headerSort: false, formatter:

                        function(cell) {
                            var input = document.createElement('input');
                            input.type = 'checkbox';
                            input.style.cursor = 'pointer';
                            input.checked = cell.getValue();
                            input.addEventListener('click', function() {
                                var checked = $(this).prop('checked');
                                cell.getRow().update({'required': checked ? 1 : 0});
                                // Make readonly disabled if this item is required.
                                var readonlyelm = cell.getRow().getCell("readonly").getElement();
                                var nodelist = readonlyelm.querySelectorAll('.readonly-checkbox');
                                if (checked && nodelist.length) {
                                    if (cell.getRow().getData()?.readonly) {
                                        nodelist[0].click();
                                    }
                                    nodelist[0].setAttribute('title', jsstrings[19]);
                                    nodelist[0].style.cursor = 'not-allowed';
                                    nodelist[0].disabled = true;
                                } else {
                                    nodelist[0].removeAttribute('title');
                                    nodelist[0].style.cursor = 'pointer';
                                    nodelist[0].disabled = false;
                                }
                            });

                            return input;
                        }
                },
                {
                    title: jsstrings[11] + '   ' + $('#helpbtnreadonly_' + ocinstanceid).html(),
                    field: "readonly", hozAlign: "center", widthGrow: 0, headerSort: false, formatter:
                        function(cell) {
                            if (cell.getRow().getCell("name").getValue() == 'title') {
                                return null;
                            }
                            var isrequired = cell.getRow().getData()?.required;
                            var input = document.createElement('input');
                            input.type = 'checkbox';
                            input.style.cursor = 'pointer';
                            input.classList.add('readonly-checkbox');
                            input.checked = isrequired ? false : cell.getValue();
                            if (isrequired) {
                                input.setAttribute('title', jsstrings[19]);
                                input.style.cursor = 'not-allowed';
                                input.disabled = true;
                            } else {
                                input.removeAttribute('title');
                                input.style.cursor = 'pointer';
                                input.disabled = false;
                            }
                            input.addEventListener('click', function() {
                                // Check if required is enabled.
                                if (cell.getRow().getData()?.required) {
                                    // If required is enabled, we disable this checkbox.
                                    $(this).prop('checked', false);
                                } else {
                                    // Otherwise, we provide the checkbox with normal input.
                                    cell.getRow().update({'readonly': $(this).prop('checked') ? 1 : 0});
                                }
                            });

                            return input;
                        }
                },
                {
                    title: jsstrings[12] + '   ' + $('#helpbtnparams_' + ocinstanceid).html(),
                    field: "param_json",
                    editor: "textarea",
                    widthGrow: 2,
                    headerSort: false
                },
                {
                    title: jsstrings[13] + '   ' + $('#helpbtndefaultable_' + ocinstanceid).html(),
                    field: "defaultable", hozAlign: "center", widthGrow: 0, headerSort: false, formatter:
                        function(cell) {
                            if (cell.getRow().getCell("name").getValue() == 'title') {
                                return null;
                            }
                            var input = document.createElement('input');
                            input.type = 'checkbox';
                            input.style.cursor = 'pointer';
                            input.checked = cell.getValue();
                            input.addEventListener('click', function() {
                                cell.getRow().update({'defaultable': $(this).prop('checked') ? 1 : 0});
                            });

                            return input;
                        }
                },
                {
                    title: "", width: 40, headerSort: false, hozAlign: "center", formatter:
                        function() {
                            return '<i class="icon fa fa-trash fa-fw"></i>';
                        },
                    cellClick: function(e, cell) {
                        ModalFactory.create({
                            type: ModalFactory.types.SAVE_CANCEL,
                            title: jsstrings[5],
                            body: jsstrings[6]
                        })
                            .then(function(modal) {
                                modal.setSaveButtonText(jsstrings[14]);
                                modal.getRoot().on(ModalEvents.save, function() {
                                    cell.getRow().delete();
                                });
                                modal.show();
                                return;
                            }).catch(Notification.exception);
                    }
                }
            ],
        });

        $('#addrow-metadataseriestable_' + ocinstanceid).click(function() {
            metadataseriestable.addRow({'datatype': 'text', 'required': 0, 'readonly': 0, 'param_json': null});
        });

        /**
         * Gets the default input value and replace it with actual value if it values are not initialised
         *
         * @param {object} input
         */
        function extractDefaults(input) {
            var value = input.val();
            if (value == '') {
                var defaultstext = input.parent().next().text();
                defaultstext = defaultstext != '' ?
                    defaultstext.slice(defaultstext.indexOf('['), defaultstext.lastIndexOf(']') + 1) : '';
                if (defaultstext != '') {
                    input.val(defaultstext);
                }
            }
        }

        // Style hidden input.
        var instancesinput = $('#' + instancesinputid);

        if (!instancesinput.length) {
            return;
        }

        instancesinput.parent().hide();
        instancesinput.parent().next().hide(); // Default value.

        var instancestable = new Tabulator("#instancestable", {
            data: JSON.parse(instancesinput.val()),
            layout: "fitColumns",
            dataChanged: function(data) {
                instancesinput.val(JSON.stringify(data));
            },
            columns: [
                {title: 'ID', field: "id", widthGrow: 0},
                {title: jsstrings[0], field: "name", editor: "input", widthGrow: 2},
                {
                    title: jsstrings[1],
                    field: "isvisible",
                    hozAlign: "center",
                    widthGrow: 0,
                    formatter: function(cell) {
                        var input = document.createElement('input');
                        input.type = 'checkbox';
                        input.checked = cell.getValue();
                        input.addEventListener('click', function() {
                            cell.getRow().update({'isvisible': $(this).prop('checked') ? 1 : 0});
                        });
                        return input;
                    }
                },
                {
                    title: jsstrings[6],
                    field: "isdefault",
                    hozAlign: "center",
                    widthGrow: 0,
                    formatter: function(cell) {
                        var input = document.createElement('input');
                        input.type = 'checkbox';
                        input.checked = cell.getValue();
                        input.addEventListener('click', function() {
                            cell.getRow().update({'isdefault': $(this).prop('checked') ? 1 : 0});
                        });
                        return input;
                    }
                },
                {
                    title: "",
                    width: 40,
                    headerSort: false,
                    hozAlign: "center",
                    formatter: function() {
                        return '<i class="icon fa fa-trash fa-fw"></i>';
                    },
                    cellClick: function(e, cell) {
                        ModalFactory.create({
                            type: ModalFactory.types.SAVE_CANCEL,
                            title: jsstrings[3],
                            body: jsstrings[4]
                        })
                            .then(function(modal) {
                                modal.setSaveButtonText(jsstrings[5]);
                                modal.getRoot().on(ModalEvents.save, function() {
                                    cell.getRow().delete();
                                });
                                modal.show();
                                return;
                            }).catch(Notification.exception);
                    }
                }
            ],
        });

        $('#addrow-instancestable').click(function() {
            var instances = JSON.parse(instancesinput.val());
            var ids = instances.map(x => x.id);
            ids.sort();
            var nextid = 0;
            var i;

            if (ids.includes(1)) {
                for (i = 0; i < ids.length; i++) {
                    let nextElem = i + 1;
                    if (nextElem === ids.length) {
                        nextid = ids[i] + 1;
                    } else if (ids[nextElem] !== ids[i] + 1) {
                        nextid = ids[i] + 1;
                        break;
                    }
                }
            } else {
                nextid = 1;
            }

            instancestable.addRow({'id': nextid, 'isvisible': false, 'isdefault': false});
        });
        return;
    }).catch(Notification.exception);
};
