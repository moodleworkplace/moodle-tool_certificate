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
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Notification from 'core/notification';
import {get_strings as getStrings, get_string as getString} from 'core/str';
import Ajax from 'core/ajax';
import ModalForm from 'tool_certificate/modal_form';
import Toast from 'core/toast';
import {refreshTableContent, getFilters, setFilters} from 'core_table/dynamic';
import * as DynamicTableSelectors from 'core_table/local/dynamic/selectors';

const SELECTORS = {
    ADDISSUE: "[data-element='addbutton']",
    REGENERATEFILE: "[data-action='regenerate']",
    REVOKEISSUE: "[data-action='revoke']",
    GROUPFORM: ".groupselector form",
    GROUPSELECTOR: "select[name='group']"
};

/**
 * Add issue dialogue
 * @param {Element} element
 */
const addIssue = function(element) {
    var modal = new ModalForm({
        formClass: 'tool_certificate\\form\\certificate_issues',
        args: {tid: element.dataset.tid},
        modalConfig: {title: getString('issuecertificates', 'tool_certificate'), scrollable: false},
        saveButtonText: getString('save'),
        triggerElement: element,
    });
    modal.onSubmitSuccess = function(data) {
        data = parseInt(data, 10);
        if (data) {
            getStrings([
                {key: 'oneissuewascreated', component: 'tool_certificate'},
                {key: 'aissueswerecreated', component: 'tool_certificate', param: data}
            ]).done(function(s) {
                var str = data > 1 ? s[1] : s[0];
                Toast.add(str);
            });
            reloadReport();
        } else {
            getString('noissueswerecreated', 'tool_certificate')
                .done(function(s) {
                    Toast.add(s);
                });
        }
    };
};

/**
 * Revoke issue
 * @param {Element} element
 */
const revokeIssue = function(element) {
    getStrings([
        {key: 'confirm', component: 'moodle'},
        {key: 'revokecertificateconfirm', component: 'tool_certificate'},
        {key: 'revoke', component: 'tool_certificate'},
        {key: 'cancel', component: 'moodle'}
    ]).done(function(s) {
        Notification.confirm(s[0], s[1], s[2], s[3], function() {
            var promises = Ajax.call([
                {methodname: 'tool_certificate_revoke_issue',
                    args: {id: element.dataset.id}}
            ]);
            promises[0].done(function() {
                reloadReport();
            }).fail(Notification.exception);
        });
    }).fail(Notification.exception);
};

/**
 * Revoke issue
 * @param {Element} element
 */
const regenerateIssueFile = function(element) {
    getStrings([
        {key: 'confirm', component: 'moodle'},
        {key: 'regeneratefileconfirm', component: 'tool_certificate'},
        {key: 'regenerate', component: 'tool_certificate'},
        {key: 'cancel', component: 'moodle'}
    ]).done(function(s) {
        Notification.confirm(s[0], s[1], s[2], s[3], function() {
            var promises = Ajax.call([
                {methodname: 'tool_certificate_regenerate_issue_file',
                    args: {id: element.dataset.id}}
            ]);
            promises[0].done(function() {
                reloadReport();
            }).fail(Notification.exception);
        });
    }).fail(Notification.exception);
};

/**
 * Reload report
 */
var reloadReport = function() {
    const report = document.querySelector(DynamicTableSelectors.main.region);
    refreshTableContent(report).catch(Notification.exception);
};

/**
 * Change group and refresh table
 * @param {Event} e
 */
const changeGroup = function(e) {
    const report = document.querySelector(DynamicTableSelectors.main.region);
    let filters = getFilters(report);
    let params = JSON.parse(filters.filters.parameters.values[0]);
    params.groupid = e.target.value;
    filters.filters.parameters.values[0] = JSON.stringify(params);
    setFilters(report, filters);
};

/**
 * Init page
 */
export function init() {
    document.addEventListener('click', event => {

        // Add issue.
        const addIssueElement = event.target.closest(SELECTORS.ADDISSUE);
        if (addIssueElement) {
            event.preventDefault();
            addIssue(addIssueElement);
        }

        // Revoke issue.
        const revokeIssueElement = event.target.closest(SELECTORS.REVOKEISSUE);
        if (revokeIssueElement) {
            event.preventDefault();
            revokeIssue(revokeIssueElement);
        }

        // Regenerate file.
        const regenerateFileElement = event.target.closest(SELECTORS.REGENERATEFILE);
        if (regenerateFileElement) {
            event.preventDefault();
            regenerateIssueFile(regenerateFileElement);
        }
    });

    const groupform = document.querySelector(SELECTORS.GROUPFORM);
    if (groupform) {
        // Flush existing event listeners.
        const node = groupform.cloneNode(true);
        groupform.replaceWith(node);
        // Add event handler.
        node.querySelector(SELECTORS.GROUPSELECTOR).addEventListener('change', changeGroup);
    }
}
