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
 * AMD module used when viewing the list of templates
 *
 * @module     tool_certificate/templates-list
 * @package    tool_certificate
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'tool_wp/modal_form', 'tool_wp/tabs', 'core/notification', 'core/str'],
function($, ModalForm, Tabs, Notification, Str) {

    /**
     * Display modal form
     *
     * @param {jQuery} triggerElement
     * @param {String} title
     * @param {Number} id
     * @return {ModalForm}
     */
    var displayModal = function(triggerElement, title, id) {
        var modal = new ModalForm({
            formClass: 'tool_certificate\\form\\details',
            args: {id: id},
            modalConfig: {title: title},
            saveButtonText: Str.get_string('save'),
            triggerElement: triggerElement,
        });
        // Override onInit() function to change the text for the save button.
        var oldInit = modal.onInit;
        modal.onInit = function() {
            this.modal.setSaveButtonText(Str.get_string('save'));
            oldInit.bind(this)();
        };
        return modal;
    };

    /**
     * Add template dialogue
     * @param {Event} e
     */
    var displayAddTemplate = function(e) {
        e.preventDefault();
        var modal = displayModal($(e.currentTarget), Str.get_string('createtemplate', 'tool_certificate'), 0);
        modal.onSubmitSuccess = function(url) {
            window.location.href = url;
        };
    };

    /**
     * Edit template dialogue
     * @param {Event} e
     */
    var displayEditTemplate = function(e) {
        e.preventDefault();
        var el = $(e.currentTarget),
            id = el.attr('data-id'),
            name = el.attr('data-name');
        var modal = displayModal(el, Str.get_string('editcertificate', 'tool_certificate', name), id);
        modal.onSubmitSuccess = function() {
            window.location.reload();
        };
    };

    /**
     * Add template dialogue
     * @param {Event} e
     */
    var displayIssue = function(e) {
        e.preventDefault();
        new ModalForm({
            formClass: 'tool_certificate\\form\\certificate_issues',
            args: {tid: $(e.currentTarget).attr('data-tid')},
            modalConfig: {title: Str.get_string('issuenewcertificates', 'tool_certificate')},
            saveButtonText: Str.get_string('save'),
            triggerElement: $(e.currentTarget),
        });
        // No action on submit.
    };

    return {
        /**
         * Init page
         */
        init: function() {
            // Add button is not inside a tab, so we can't use Tab.addButtonOnClick .
            $('[data-tabs-element="addbutton"]').on('click', displayAddTemplate);
            $('[data-action="editdetails"]').on('click', displayEditTemplate);
            $('[data-action="issue"]').on('click', displayIssue);
        }
    };
});
