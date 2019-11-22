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
 * AMD module used when viewing the list of issued certificates
 *
 * @module     tool_certificate/issues-list
 * @package    tool_certificate
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'tool_wp/modal_form', 'tool_wp/tabs', 'core/notification', 'core/str', 'core/ajax', 'tool_wp/notification'],
function($, ModalForm, Tabs, Notification, Str, Ajax, WpNotification) {

    /**
     * Refresh the report without reloading page
     */
    var refreshReport = function() {
        var report = $("[data-region='system-report'] [data-region='data-report']");
        if (report) {
            // TODO use RELOADTABLEWITHOUTPAGINATION constant from tool_reportbuilder/reportbuilder_events.
            report.trigger('reportbuilder:reloadtablewithoutpagination');
        } else {
            window.location.reload();
        }
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
            modalConfig: {title: Str.get_string('issuenewcertificates', 'tool_certificate')},
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
                    WpNotification.addNotification({message: data > 1 ? s[1] : s[0], type: 'success'});
                });
                refreshReport();
            } else {
                Str.get_strings([
                    {key: 'noissueswerecreated', component: 'tool_certificate'}
                ]).done(function(s) {
                    WpNotification.addNotification({message: s[0], type: 'warning'});
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
        Str.get_strings([
            {key: 'confirm'},
            {key: 'revokecertificateconfirm', component: 'tool_certificate'},
            {key: 'revoke', component: 'tool_certificate'},
            {key: 'cancel'}
        ]).done(function(s) {
            Notification.confirm(s[0], s[1], s[2], s[3], function() {
                var promises = Ajax.call([
                    {methodname: 'tool_certificate_revoke_issue',
                        args: {id: $(e.currentTarget).attr('data-id')}}
                ]);
                promises[0].done(function() {
                    refreshReport();
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
            $('[data-tabs-element="addbutton"]').on('click', addIssue);
            $('[data-action="revoke"]').on('click', revokeIssue);
        }
    };
});
