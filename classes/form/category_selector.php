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
 * Issue new certificate for users.
 *
 * @package    tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate\form;

use context;
use core_form\dynamic_form;
use moodle_url;
use tool_certificate\template;

/**
 * Select category when duplicating a template.
 *
 * @package    tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category_selector extends dynamic_form {

    /** @var template */
    protected $template;

    /**
     * Get template
     *
     * @return template
     */
    protected function get_template(): template {
        if ($this->template === null) {
            $this->template = template::instance($this->_ajaxformdata['id']);
        }
        return $this->template;
    }

    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;
        $mform->setDisableShortforms();
        $mform->addElement('header', 'hdr', '');

        $mform->addElement('static', 'confirmmessage', '',
            get_string('duplicatetemplateconfirm', 'tool_certificate',
                $this->get_template()->get_formatted_name()));

        if ($categoryoptions = $this->get_category_options()) {
            $mform->addElement('select', 'categoryid', get_string('coursecategory'), $categoryoptions);
            $mform->setType('categoryid', PARAM_INT);
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
    }

    /**
     * Get list of categories where user can manage templates
     *
     * @return array
     */
    protected function get_category_options() {
        $template = $this->get_template();
        if (!in_array($template->get_context()->contextlevel, [CONTEXT_COURSECAT, CONTEXT_SYSTEM])) {
            // Not possible to edit category of a template that is defined on any other level.
            return [];
        }

        $options = \core_course_category::make_categories_list('tool/certificate:manage');
        $systemcontext = \context_system::instance();
        if (has_capability('tool/certificate:manage', $systemcontext)) {
            $options = [0 => get_string('none')] + $options;
        }
        return $options;
    }

    /**
     * Returns context where this form is used
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return \context_system::instance();
    }

    /**
     * Check if current user has access to this form, otherwise throw exception
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
     */
    public function process_dynamic_submission(): void {
        $data = $this->get_data();
        $context = !empty($data->categoryid) ? \context_coursecat::instance($data->categoryid) : null;
        $this->get_template()->duplicate($context);
    }

    /**
     * Load in existing data as form defaults
     *
     * Can be overridden to retrieve existing values from db by entity id and also
     * to preprocess editor and filemanager elements
     */
    public function set_data_for_dynamic_submission(): void {
        $data = $this->get_template()->to_record();
        if ($this->get_template()->get_context()->contextlevel == CONTEXT_COURSECAT) {
            $data->categoryid = $this->get_template()->get_context()->instanceid;
        }
        $this->set_data($data);
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     * This is used in the form elements sensitive to the page url, such as Atto autosave in 'editor'
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/admin/tool/certificate/manage_templates.php');
    }
}
