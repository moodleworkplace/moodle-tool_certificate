// This file is part of the tool_certificate plugin for Moodle - http://moodle.org/
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
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'tool_certificate/modal_form', 'core/notification', 'core/str', 'core/ajax', 'core/toast'],
function($, ModalForm, Notification, Str, Ajax, Toast) {

    /**
     * Display modal form
     *
     * @param {jQuery} triggerElement
     * @param {String} title
     * @param {Object} args
     * @return {ModalForm}
     */
    var displayModal = function(triggerElement, title, args) {
        var modal = new ModalForm({
            formClass: 'tool_certificate\\form\\details',
            args: args,
            modalConfig: {title: title},
            saveButtonText: Str.get_string('save'),
            triggerElement: triggerElement,
        });
        return modal;
    };

    /**
     * Add template dialogue
     * @param {Event} e
     */
    var displayAddTemplate = function(e) {
        var contextid = $(e.currentTarget).data('contextid');
        e.preventDefault();
        var modal = displayModal($(e.currentTarget), Str.get_string('createtemplate', 'tool_certificate'),
            {id: 0, contextid: contextid});
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
        var modal = displayModal(el, Str.get_string('editcertificate', 'tool_certificate', name), {id: id});
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
        const target = $(e.currentTarget);
        var modal = new ModalForm({
            formClass: 'tool_certificate\\form\\certificate_issues',
            args: {tid: target.attr('data-tid')},
            modalConfig: {title: Str.get_string('issuecertificates', 'tool_certificate'), scrollable: false},
            saveButtonText: Str.get_string('save'),
            triggerElement: target,
        });
        modal.onSubmitSuccess = function(data) {
            data = parseInt(data, 10);
            if (data) {
                Str.get_strings([
                    {key: 'oneissuewascreated', component: 'tool_certificate'},
                    {key: 'aissueswerecreated', component: 'tool_certificate', param: data}
                ]).done(function(s) {
                    var str = data > 1 ? s[1] : s[0];
                    Toast.add(str);
                });
            } else {
                Str.get_string('noissueswerecreated', 'tool_certificate')
                    .done(function(s) {
                        Toast.add(s);
                    });
            }
        };
    };

    var duplicateMulticategory = function(e) {
        e.preventDefault();
        const target = $(e.currentTarget);
        const templateId = target.attr('data-id');
        var modal = new ModalForm({
            formClass: 'tool_certificate\\form\\category_selector',
            args: {id: templateId},
            modalConfig: {title: Str.get_string('confirm')},
            saveButtonText: Str.get_string('duplicate', 'tool_certificate'),
            triggerElement: target,
        });
        modal.onSubmitSuccess = function() {
            window.location.reload();
        };
    };

    var duplicateSinglecategory = function(e) {
        e.preventDefault();
        const templateId = $(e.currentTarget).attr('data-id');
        Str.get_strings([
            {key: 'confirm', component: 'moodle'},
            {key: 'duplicatetemplateconfirm', component: 'tool_certificate', param: $(e.currentTarget).attr('data-name')},
            {key: 'duplicate', component: 'tool_certificate'},
            {key: 'cancel', component: 'moodle'}
        ]).done(function(s) {
            Notification.confirm(s[0], s[1], s[2], s[3], function() {
                var promises = Ajax.call([
                    {methodname: 'tool_certificate_duplicate_template',
                        args: {id: templateId}}
                ]);
                promises[0].done(function() {
                    window.location.reload();
                }).fail(Notification.exception);
            });
        }).fail(Notification.exception);
    };

    var deleteTemplate = function(e) {
        e.preventDefault();
        const templateId = $(e.currentTarget).attr('data-id');
        Str.get_strings([
            {key: 'confirm', component: 'moodle'},
            {key: 'deletetemplateconfirm', component: 'tool_certificate', param: $(e.currentTarget).attr('data-name')},
            {key: 'delete', component: 'moodle'},
            {key: 'cancel', component: 'moodle'}
        ]).done(function(s) {
            Notification.confirm(s[0], s[1], s[2], s[3], function() {
                var promises = Ajax.call([
                    {methodname: 'tool_certificate_delete_template',
                        args: {id: templateId}}
                ]);
                promises[0].done(function() {
                    window.location.reload();
                }).fail(Notification.exception);
            });
        }).fail(Notification.exception);
    };

    return {
        /**
         * Init page
         */
        init: function() {
            // Add button is not inside a tab, so we can't use Tab.addButtonOnClick .
            $('body')
                .on('click', '[data-element="addbutton"]', displayAddTemplate)
                .on('click', '[data-action="editdetails"]', displayEditTemplate)
                .on('click', '[data-action="issue"]', displayIssue)
                .on('click', '[data-action="duplicate"][data-selectcategory="1"]', duplicateMulticategory)
                .on('click', '[data-action="duplicate"][data-selectcategory="0"]', duplicateSinglecategory)
                .on('click', '[data-action="delete"]', deleteTemplate);
        }
    };
});

