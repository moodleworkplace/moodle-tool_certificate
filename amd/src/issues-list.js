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
import ModalForm from 'core_form/modalform';
import {add as toastAdd} from 'core/toast';
import {refreshTableContent, getFilters, setFilters} from 'core_table/dynamic';
import * as DynamicTableSelectors from 'core_table/local/dynamic/selectors';
import * as reportSelectors from 'core_reportbuilder/local/selectors';
import * as tableEvents from 'core_table/local/dynamic/events';
import Pending from 'core/pending';

const SELECTORS = {
    ADDISSUE: "[data-element='addbutton']",
    REGENERATEFILE: "[data-action='regenerate']",
    REVOKEISSUE: "[data-action='revoke']",
    GROUPFORM: ".groupselector form",
    GROUPSELECTOR: "select[name='group']",
    BULKACTIONSFORM: 'form#cert-bulk-action-form',
    // The data-toggle attribute was renamed in version 5.1.
    // Retain support for both the new and legacy selectors to ensure compatibility.
    CHECKEDCHECKBOXES: '[data-togglegroup="report-select-all"][data-toggle="target"]:checked, ' +
                       '[data-togglegroup="report-select-all"][data-toggle="slave"]:checked',
};

/**
 * Add issue dialogue
 * @param {Element} element
 */
const addIssue = function(element) {
    const modal = new ModalForm({
        formClass: 'tool_certificate\\form\\certificate_issues',
        args: {tid: element.dataset.tid},
        modalConfig: {title: getString('issuecertificates', 'tool_certificate'), scrollable: false},
        saveButtonText: getString('save'),
        returnFocus: element,
    });
    modal.addEventListener(modal.events.FORM_SUBMITTED, event => {
        const issuescreated = parseInt(event.detail, 10);
        if (issuescreated > 1) {
            toastAdd(getString('aissueswerecreated', 'tool_certificate', issuescreated));
            reloadReport();
        } else if (issuescreated === 1) {
            toastAdd(getString('oneissuewascreated', 'tool_certificate'));
            reloadReport();
        } else {
            toastAdd(getString('noissueswerecreated', 'tool_certificate'));
        }
    });
    modal.show();
};

/**
 * Revoke issue
 * @param {Element} element
 */
const revokeIssue = function(element) {
    let pendingPromise;
    const triggerElement = element.closest('.dropdown').querySelector('.dropdown-toggle');
    getStrings([
        {key: 'confirm', component: 'moodle'},
        {key: 'revokecertificateconfirm', component: 'tool_certificate'},
        {key: 'revoke', component: 'tool_certificate'},
    ]).then(([title, question, saveLabel]) => {
        return Notification.saveCancelPromise(title, question, saveLabel, {triggerElement});
    }).then(() => {
        pendingPromise = new Pending('tool_certificate/revokeIssue');
        return Ajax.call([
            {methodname: 'tool_certificate_revoke_issue', args: {id: element.dataset.id}}
        ])[0];
    }).then(() => {
        reloadReport();
        return pendingPromise.resolve();
    }).catch((e) => {
        if (e.type === 'modal-save-cancel:cancel') {
            // Clicked cancel.
            return;
        }
        Notification.exception(e);
    });
};

/**
 * Regenerate certificate.
 * @param {Element} element
 */
const regenerateIssueFile = function(element) {
    const triggerElement = element.closest('.dropdown').querySelector('.dropdown-toggle');

    showModalForm(
        'tool_certificate\\form\\certificate_renew',
        {issueids: element.dataset.id, actiontype: 'regeneratesingleuser'},
        {title: getString('regenerateselected', 'tool_certificate'), scrollable: false},
        getString('regenerate', 'tool_certificate'),
        triggerElement,
        getString('regeneratesinglenotification', 'tool_certificate')
    );
};

/**
 * Reload report
 * @returns {Promise}
 */
var reloadReport = function() {
    const report = document.querySelector(DynamicTableSelectors.main.region);
    return refreshTableContent(report).catch(Notification.exception);
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
 * Show modal form for different actions
 *
 * @param {String} formClass
 * @param {Object} args
 * @param {Object} modalConfig
 * @param {String} saveButtonText
 * @param {Element} triggerElement
 * @param {String} message
 */
const showModalForm = (formClass, args, modalConfig, saveButtonText, triggerElement, message) => {
    const modal = new ModalForm({
        formClass: formClass,
        args: args,
        modalConfig: modalConfig,
        saveButtonText: saveButtonText,
        returnFocus: triggerElement,
    });

    modal.addEventListener(modal.events.FORM_SUBMITTED, () => {
        message.then(string => {
            return Notification.addNotification({
                type: 'success',
                message: string
            });
        }).catch(Notification.exception);
        reloadReport();
    });

    modal.show();
};

/**
 * Enable or disable bulk actions selector on report
 *
 * Enables the selector when at least one checkbox has been checked.
 *
 */
const enableDisableBulkActionSelector = () => {
    const formSelector = SELECTORS.BULKACTIONSFORM;
    // Toggle the bulk action selector based on whether any checkbox is selected.
    const bulkactionselector = document.querySelector(formSelector + ' select');
    if (bulkactionselector) {
        const checkboxesChecked = document.querySelectorAll(SELECTORS.CHECKEDCHECKBOXES);
        bulkactionselector.disabled = checkboxesChecked.length === 0;
    }
};

/**
 * Perform selected action on the bulk selector
 */
const bulkSelectorPerformAction = () => {
    const bulkactionselector = document.querySelector(SELECTORS.BULKACTIONSFORM + ' select');

    // Get user ids from all checkboxes that are checked.
    const checkboxesChecked = document.querySelectorAll(SELECTORS.CHECKEDCHECKBOXES);
    const issueids = [...checkboxesChecked].map(element => element.closest('tr').querySelector('a[data-id]').dataset.id);

    // Check if the user has selected the option to regenerate all the selected certificates on the bulk selector.
    if (bulkactionselector.value === 'regenerateall') {
        const title = checkboxesChecked.length > 1 ?
            getString('regenerateall', 'tool_certificate') :
            getString('regenerateselected', 'tool_certificate');
        showModalForm(
            'tool_certificate\\form\\certificate_renew',
            {issueids: issueids.join(','), actiontype: 'regenerateselectedusers'},
            {title: title, scrollable: false},
            getString('regenerate', 'tool_certificate'),
            bulkactionselector,
            getString('regeneratenotification', 'tool_certificate')
        );
    }

    // Reset dropdown.
    bulkactionselector.value = '0';
};

/**
 * Init page
 */
export function init() {
    enableDisableBulkActionSelector();
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

    document.addEventListener('change', (event) => {
        // Select/deselect bulk checkboxes.
        const toggleGroup = event.target.closest('[data-togglegroup="report-select-all"]');
        if (toggleGroup) {
            enableDisableBulkActionSelector();
        }

        // Change on bulk action selector.
        const changeBulkActionSelector = event.target.closest(SELECTORS.BULKACTIONSFORM + ' select');
        if (changeBulkActionSelector) {
            bulkSelectorPerformAction();
        }
    });

    // This is needed to reset checkboxes/dropdown after performing an action.
    document.addEventListener(tableEvents.tableContentRefreshed, event => {
        const reportElement = event.target.closest(reportSelectors.regions.report);
        if (reportElement) {
            enableDisableBulkActionSelector();
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
