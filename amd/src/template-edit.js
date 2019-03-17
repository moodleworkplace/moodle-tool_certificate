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
 * AMD module used when editing a single template
 *
 * @module     tool_certificate/template-edit
 * @package    tool_certificate
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'tool_wp/modal_form', 'tool_wp/tabs', 'core/notification', 'core/str', 'core/ajax'],
function($, ModalForm, Tabs, Notification, Str, Ajax) {
    var editReportDetailsHandler = function(e) {
        e.preventDefault();
        var el = $(e.currentTarget),
            id = el.attr('data-id'),
            name = el.attr('data-name');

        var modal = new ModalForm({
            formClass: 'tool_certificate\\form\\details',
            args: {id: id},
            modalConfig: {title: Str.get_string('editcertificate', 'tool_certificate', name)},
            triggerElement: el,
        });
        // Override onInit() function to change the text for the save button.
        var oldInit = modal.onInit;
        modal.onInit = function() {
            this.modal.setSaveButtonText(Str.get_string('save'));
            oldInit.bind(this)();
        };
        modal.onSubmitSuccess = function() {
            window.location.reload();
        };
    };

    var deleteElement = function(e) {
        e.preventDefault();
        Str.get_strings([
            {key: 'confirm'},
            {key: 'deleteelementconfirm', component: 'tool_certificate', param: $(e.currentTarget).attr('data-name')},
            {key: 'delete'},
            {key: 'cancel'}
        ]).done(function(s) {
            Notification.confirm(s[0], s[1], s[2], s[3], function() {
                var promises = Ajax.call([
                    {methodname: 'tool_certificate_delete_element',
                        args: {id: $(e.currentTarget).attr('data-id')}}
                ]);
                promises[0].done(function() {
                    // TODO reload only list of elements.
                    window.location.reload();
                }).fail(Notification.exception);
            });
        }).fail(Notification.exception);
    };

    return {
        init: function() {
            // Add button is not inside a tab, so we can't use Tab.addButtonOnClick .
            $('[data-action="editdetails"]').on('click', editReportDetailsHandler);
            $('[data-action="deleteelement"]').on('click', deleteElement);
        }
    };
});
