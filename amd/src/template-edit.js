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
define(['jquery', 'tool_wp/modal_form', 'tool_wp/tabs', 'core/notification', 'core/str', 'core/config'],
function($, ModalForm, Tabs, Notification, Str, Config) {
    var editReportDetailsHandler = function(e) {
        e.preventDefault();
        var el = $(e.currentTarget),
            id = el.attr('data-id'),
            name = el.attr('data-name');

        var modal = new ModalForm({
            formClass: 'tool_certificate\\form\\details',
            args: {id: id},
            modalConfig: {title: Str.get_string('editcertificate', 'tool_certificate', name)},
            contextId: Config.contextid,
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

    return {
        init: function() {
            // Add button is not inside a tab, so we can't use Tab.addButtonOnClick .
            $('[data-action="editdetails"]').on('click', editReportDetailsHandler);
        }
    };
});
