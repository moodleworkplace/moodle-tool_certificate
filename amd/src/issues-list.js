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
 * AMD module used when viewing the list of issued certificates
 *
 * @module     tool_certificate/issues-list
 * @package    tool_certificate
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery',
        'tool_certificate/modal_form',
        'core/notification',
        'core/str',
        'core/ajax',
        'core/toast'],
function($,
         ModalForm,
         Notification,
         Str,
         Ajax,
         Toast) {

    const SELECTORS = {
        ADDISSUE: "[data-element='addbutton']",
        REGENERATEFILE: "[data-action='regenerate']",
        REVOKEISSUE: "[data-action='revoke']"
    };

    /**
     * Add issue dialogue
     * @param {Event} e
     */
    var addIssue = function(e) {
        e.preventDefault();
        var modal = new ModalForm({
            formClass: 'tool_certificate\\form\\certificate_issues',
            args: {tid: $(e.currentTarget).attr('data-tid')},
            modalConfig: {title: Str.get_string('issuecertificates', 'tool_certificate'), scrollable: false},
            saveButtonText: Str.get_string('save'),
            triggerElement: $(e.currentTarget),
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
                window.location.reload();
            } else {
                Str.get_string('noissueswerecreated', 'tool_certificate')
                    .done(function(s) {
                        Toast.add(s);
                    });
            }
        };
    };

    /**
     * Revoke issue
     * @param {Event} e
     */
    var revokeIssue = function(e) {
        e.preventDefault();
        e.stopPropagation();
        Str.get_strings([
            {key: 'confirm', component: 'moodle'},
            {key: 'revokecertificateconfirm', component: 'tool_certificate'},
            {key: 'revoke', component: 'tool_certificate'},
            {key: 'cancel', component: 'moodle'}
        ]).done(function(s) {
            Notification.confirm(s[0], s[1], s[2], s[3], function() {
                var promises = Ajax.call([
                    {methodname: 'tool_certificate_revoke_issue',
                        args: {id: $(e.currentTarget).attr('data-id')}}
                ]);
                promises[0].done(function() {
                    window.location.reload();
                }).fail(Notification.exception);
            });
        }).fail(Notification.exception);
    };

    /**
     * Revoke issue
     * @param {Event} e
     */
    var regenerateIssueFile = function(e) {
        e.preventDefault();
        e.stopPropagation();
        Str.get_strings([
            {key: 'confirm', component: 'moodle'},
            {key: 'regeneratefileconfirm', component: 'tool_certificate'},
            {key: 'regenerate', component: 'tool_certificate'},
            {key: 'cancel', component: 'moodle'}
        ]).done(function(s) {
            Notification.confirm(s[0], s[1], s[2], s[3], function() {
                var promises = Ajax.call([
                    {methodname: 'tool_certificate_regenerate_issue_file',
                        args: {id: $(e.currentTarget).attr('data-id')}}
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
                .on('click', SELECTORS.ADDISSUE, addIssue)
                .on('click', SELECTORS.REVOKEISSUE, revokeIssue)
                .on('click', SELECTORS.REGENERATEFILE, regenerateIssueFile);
        }
    };
});
