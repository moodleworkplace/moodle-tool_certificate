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
 * @module     tool_certificate/certificates-list
 * @package    tool_certificate
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'tool_wp/modal_form', 'tool_wp/tabs', 'core/notification', 'core/str'],
function($, ModalForm, Tabs, Notification, Str) {

    /**
     * Add template dialogue
     * @param {Event} e
     */
    var displayIssue = function(e) {
        e.preventDefault();
        var modal = new ModalForm({
            formClass: 'tool_certificate\\form\\certificate_issues',
            args: {tid: $(e.currentTarget).attr('data-tid')},
            modalConfig: {title: Str.get_string('issuenewcertificates', 'tool_certificate')},
            saveButtonText: Str.get_string('save'),
            triggerElement: $(e.currentTarget),
        });
        modal.onSubmitSuccess = function() {
            window.location.reload();
        };
    };

    return {
        /**
         * Init page
         */
        init: function() {
            // Add button is not inside a tab, so we can't use Tab.addButtonOnClick .
            $('[data-tabs-element="addbutton"]').on('click', displayIssue);
        }
    };
});
