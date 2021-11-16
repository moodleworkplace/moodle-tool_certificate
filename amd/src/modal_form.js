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
 * Display a form in a modal dialogue
 *
 * @module     tool_certificate/modal_form
 * @copyright  2018 Mitxel Moriana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'core/modal_factory',
    'core/modal_events',
    'core/ajax',
    'core/notification',
    'core/yui',
    'core/event',
    'core/str'
], function($, ModalFactory, ModalEvents, Ajax, Notification, Y, Event, Str) {
    /**
     * Constructor
     *
     * Shows the required form inside a modal dialogue
     *
     * @param {Object} config Parameters for the list. See defaultParameters above for examples.
     * @param {String} config.formClass PHP class name that handles the form (should extend \tool_certificate\modal_form )
     * @param {Object} config.modalConfig modal config - title, type, etc. By default type is set
     *              to ModalFactory.types.SAVE_CANCEL
     * @param {Object} config.args Arguments for the initial form rendering
     * @param {$} config.triggerElement trigger element for a modal form
     */
    var ModalForm = function(config) {
        this.config = config;
        this.config.modalConfig = this.config.modalConfig || {};
        this.config.modalConfig.type = this.config.modalConfig.type || ModalFactory.types.SAVE_CANCEL;
        this.config.args = this.config.args || {};
        this.init();
    };

    /**
     * @var {Object} config
     */
    ModalForm.prototype.config = {};

    /**
     * @var {Modal} modal
     */
    ModalForm.prototype.modal = null;

    /**
     * Initialise the class.
     *
     * @private
     */
    ModalForm.prototype.init = function() {
        var requiredStrings = [
            {key: 'collapseall', component: 'moodle'},
            {key: 'expandall', component: 'moodle'}
        ];

        // Ensure strings required for shortforms are always available.
        M.util.js_pending('tool_certificate_modal_form_init');
        Str.get_strings(requiredStrings)
            .then(function() {
                // We don't attach trigger element to modal here to avoid MDL-70395.
                // We normally initialise ModalForm as result of some event
                // on trigger element, so new listener is not required.
                return ModalFactory.create(this.config.modalConfig);
            }.bind(this))
            .then(function(modal) {
                // Keep a reference to the modal.
                this.modal = modal;

                // We need to make sure that the modal already exists when we render the form. Some form elements
                // such as date_selector inspect the existing elements on the page to find the highest z-index.
                this.modal.setBody(this.getBody($.param(this.config.args)));

                // Forms are big, we want a big modal.
                this.modal.setLarge();

                // After successfull submit, when we press "Cancel" or close the dialogue by clicking on X in the top right corner.
                this.modal.getRoot().on(ModalEvents.hidden, function() {
                    // Notify listeners that the form is about to be submitted (this will reset atto autosave).
                    Event.notifyFormSubmitAjax(this.modal.getRoot().find('form')[0], true);

                    // Destroy the modal.
                    this.modal.destroy();

                    // Reset form-change-checker.
                    this.resetDirtyFormState();

                    // Focus on the trigger element that actually launched the modal.
                    if (this.config.triggerElement !== null) {
                        this.config.triggerElement.focus();
                    }
                }.bind(this));

                // Add the class to the modal dialogue.
                this.modal.getModal().addClass('tool-wp-modal-form-dialogue');

                // We catch the press on submit buttons in the forms.
                this.modal.getRoot().on('click', 'form input[type=submit][data-no-submit]', this.noSubmitButtonPressed.bind(this));

                // We catch the form submit event and use it to submit the form with ajax.
                this.modal.getRoot().on('submit', 'form', this.submitFormAjax.bind(this));

                // Change the text for the save button.
                if (typeof this.config.saveButtonText !== 'undefined' &&
                    typeof this.modal.setSaveButtonText !== 'undefined') {
                    this.modal.setSaveButtonText(this.config.saveButtonText);
                }

                this.onInit();

                this.modal.show();
                M.util.js_complete('tool_certificate_modal_form_init');
                return this.modal;
            }.bind(this))
            .fail(Notification.exception);
    };

    /**
     * On initialisation of a modal dialogue. Caller may override.
     */
    ModalForm.prototype.onInit = function() {
        // We catch the modal save event, and use it to submit the form inside the modal.
        // Triggering a form submission will give JS validation scripts a chance to check for errors.
        this.modal.getRoot().on(ModalEvents.save, this.submitForm.bind(this));
    };

    /**
     * @param {String} formDataString form data in format of a query string
     * @method getBody
     * @private
     * @return {Promise}
     */
    ModalForm.prototype.getBody = function(formDataString) {
        var promise = $.Deferred();
        var params = {
            formdata: formDataString,
            form: this.config.formClass
        };
        M.util.js_pending('tool_certificate_modal_form_body');
        Ajax.call([{
            methodname: 'tool_certificate_modal_form',
            args: params
        }])[0]
            .then(function(response) {
                promise.resolve(response.html, processCollectedJavascript(response.javascript));
                M.util.js_complete('tool_certificate_modal_form_body');
                return null;
            })
            .fail(function(ex) {
                promise.reject(ex);
            });
        return promise.promise();
    };

    /**
     * On form submit. Caller may override
     *
     * @param {Object} response Response received from the form's "process" method
     * @return {Object}
     */
    ModalForm.prototype.onSubmitSuccess = function(response) {
        // By default this function does nothing. Return here is irrelevant, it is only present to make eslint happy.
        return response;
    };

    /**
     * On form validation error. Caller may override
     *
     * @return {mixed}
     */
    ModalForm.prototype.onValidationError = function() {
        // By default this function does nothing. Return here is irrelevant, it is only present to make eslint happy.
        return undefined;
    };

    /**
     * On exception during form processing. Caller may override
     *
     * @param {Object} exception
     */
    ModalForm.prototype.onSubmitError = function(exception) {
        Notification.exception(exception);
    };

    /**
     * Reset "dirty" form state (warning if there are changes)
     */
    ModalForm.prototype.resetDirtyFormState = function() {
        Y.use('moodle-core-formchangechecker', function() {
            M.core_formchangechecker.reset_form_dirty_state();
        });
    };

    /**
     * Click on a "submit" button that is marked in the form as registerNoSubmitButton()
     *
     * @method submitButtonPressed
     * @private
     * @param {Event} e Form submission event.
     */
    ModalForm.prototype.noSubmitButtonPressed = function(e) {
        e.preventDefault();

        Event.notifyFormSubmitAjax(this.modal.getRoot().find('form')[0], true);

        // Add the button name to the form data and submit it.
        var formData = this.modal.getRoot().find('form').serialize(),
            el = $(e.currentTarget);
        formData = formData + '&' + encodeURIComponent(el.attr('name')) + '=' + encodeURIComponent(el.attr('value'));
        this.modal.setBody(this.getBody(formData));
    };

    /**
     * Validate form elements
     * @return {boolean} true if client-side validation has passed, false if there are errors
     */
    ModalForm.prototype.validateElements = function() {
        Event.notifyFormSubmitAjax(this.modal.getRoot().find('form')[0]);

        // Now the change events have run, see if there are any "invalid" form fields.
        var invalid = $.merge(
            this.modal.getRoot().find('[aria-invalid="true"]'),
            this.modal.getRoot().find('.error')
        );

        // If we found invalid fields, focus on the first one and do not submit via ajax.
        if (invalid.length) {
            invalid.first().focus();
            return false;
        }

        return true;
    };

    /**
     * Disable buttons during form submission
     */
    ModalForm.prototype.disableButtons = function() {
        this.modal.getFooter().find('[data-action]').attr('disabled', true);
    };

    /**
     * Enable buttons after form submission (on validation error)
     */
    ModalForm.prototype.enableButtons = function() {
        this.modal.getFooter().find('[data-action]').removeAttr('disabled');
    };

    /**
     * Private method
     *
     * @method submitFormAjax
     * @private
     * @param {Event} e Form submission event.
     */
    ModalForm.prototype.submitFormAjax = function(e) {
        // We don't want to do a real form submission.
        e.preventDefault();

        // If we found invalid fields, focus on the first one and do not submit via ajax.
        if (!this.validateElements()) {
            return;
        }
        this.disableButtons();

        // Convert all the form elements values to a serialised string.
        var formData = this.modal.getRoot().find('form').serialize();

        // Now we can continue...
        Ajax.call([{
            methodname: 'tool_certificate_modal_form',
            args: {
                formdata: formData,
                form: this.config.formClass
            }
        }])[0]
            .then(function(response) {
                if (!response.submitted) {
                    // Form was not submitted, it could be either because validation failed or because no-submit button was pressed.
                    var promise = $.Deferred();
                    promise.resolve(response.html, processCollectedJavascript(response.javascript));
                    this.modal.setBody(promise.promise());
                    this.enableButtons();
                    this.onValidationError();
                } else {
                    // Form was submitted properly. Hide the modal and execute callback.
                    var data = JSON.parse(response.data);
                    this.modal.hide();
                    this.onSubmitSuccess(data);
                }
                return null;
            }.bind(this))
            .fail(this.onSubmitError.bind(this));
    };

    /**
     * This triggers a form submission, so that any mform elements can do final tricks
     * before the form submission is processed.
     *
     * @method submitForm
     * @param {Event} e Form submission event.
     * @private
     */
    ModalForm.prototype.submitForm = function(e) {
        e.preventDefault();
        this.modal.getRoot().find('form').submit();
    };

    /**
     * Converts the JS that was received from collecting JS requirements on the $PAGE so it can be added to the existing page.
     *
     * Copied from core/fragment
     *
     * @param {string} js
     * @return {string}
     */
    const processCollectedJavascript = function(js) {
        var jsNodes = $(js);
        var allScript = '';
        jsNodes.each(function(index, scriptNode) {
            scriptNode = $(scriptNode);
            var tagName = scriptNode.prop('tagName');
            if (tagName && (tagName.toLowerCase() === 'script')) {
                if (scriptNode.attr('src')) {
                    // We only reload the script if it was not loaded already.
                    var exists = false;
                    $('script').each(function(index, s) {
                        if ($(s).attr('src') === scriptNode.attr('src')) {
                            exists = true;
                        }
                        return !exists;
                    });
                    if (!exists) {
                        allScript += ' { ';
                        allScript += ' node = document.createElement("script"); ';
                        allScript += ' node.type = "text/javascript"; ';
                        allScript += ' node.src = decodeURI("' + encodeURI(scriptNode.attr('src')) + '"); ';
                        allScript += ' document.getElementsByTagName("head")[0].appendChild(node); ';
                        allScript += ' } ';
                    }
                } else {
                    allScript += ' ' + scriptNode.text();
                }
            }
        });
        return allScript;
    };

    return ModalForm;
});
