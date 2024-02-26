<?php
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
 * This file contains the form for handling editing a certificate element.
 *
 * @package    tool_certificate
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

use context;
use core_form\dynamic_form;
use moodle_url;

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/certificate/includes/colourpicker.php');

\MoodleQuickForm::registerElementType('certificate_colourpicker',
    $CFG->dirroot . '/' . $CFG->admin . '/tool/certificate/includes/colourpicker.php',
    'moodlequickform_tool_certificate_colourpicker');

/**
 * The form for handling editing a certificate element.
 *
 * @package    tool_certificate
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_element_form extends dynamic_form {

    /**
     * @var \tool_certificate\element The element object.
     */
    protected $element;

    /** @var template */
    protected $template;

    /**
     * Get template
     *
     * @return template
     */
    protected function get_template(): template {
        return $this->get_element()->get_template();
    }

    /**
     * Get element
     *
     * @return element
     */
    protected function get_element(): element {
        if ($this->element === null) {
            if (!empty($this->_ajaxformdata['id'])) {
                $this->element = element::instance($this->_ajaxformdata['id']);
            } else {
                $this->element = element::instance(0, (object)['pageid' => $this->_ajaxformdata['pageid'],
                    'element' => $this->_ajaxformdata['element'], ]);
            }
        }
        return $this->element;
    }

    /**
     * Form definition.
     */
    public function definition() {
        $mform =& $this->_form;

        // Empty header that will not be displayed but at the same time advanced elements will work.
        $mform->addElement('header', 'general', '');
        $mform->setDisableShortforms(true);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'pageid');
        $mform->setType('pageid', PARAM_INT);

        $mform->addElement('hidden', 'element');
        $mform->setType('element', PARAM_ALPHANUMEXT);

        // Add the field for the name of the element, needed for all elements.
        $mform->addElement('text', 'name', get_string('elementname', 'tool_certificate'), 'maxlength="255"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addHelpButton('name', 'elementname', 'tool_certificate');

        $this->get_element()->render_form_elements($mform);
    }

    /**
     * Fill in the current page data for this certificate.
     */
    public function definition_after_data() {
        $this->get_element()->definition_after_data($this->_form);
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array the errors that were found
     */
    public function validation($data, $files) {
        return $this->get_element()->validate_form_elements($data, $files);
    }

    /**
     * Returns context where this form is used
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return $this->get_template()->get_context();
    }

    /**
     * Checks if current user has access to this form, otherwise throws exception
     *
     * Sometimes permission check may depend on the action and/or id of the entity.
     * If necessary, form data is available in $this->_ajaxformdata or
     * by calling $this->optional_param()
     */
    protected function check_access_for_dynamic_submission(): void {
        $this->get_template()->require_can_manage();
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * This method can return scalar values or arrays that can be json-encoded, they will be passed to the caller JS.
     *
     * @return \stdClass
     */
    public function process_dynamic_submission(): \stdClass {
        $data = $this->get_data();
        $this->get_element()->save_form_data($data);
        $data = $this->get_element()->to_record();
        // TODO use exporter instead.
        $data->html = $this->get_element()->render_html();
        $data->name = format_string($data->name);
        return $data;
    }

    /**
     * Load in existing data as form defaults
     *
     * Can be overridden to retrieve existing values from db by entity id and also
     * to preprocess editor and filemanager elements
     */
    public function set_data_for_dynamic_submission(): void {
        $this->set_data($this->get_element()->prepare_data_for_form());
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     * This is used in the form elements sensitive to the page url, such as Atto autosave in 'editor'
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/admin/tool/certificate/template.php', [
            'id' => $this->get_template()->get_id(),
        ]);
    }
}
