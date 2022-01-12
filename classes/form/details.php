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
 * Class details
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate\form;

use tool_certificate\permission;
use tool_certificate\template;
use tool_certificate\modal_form;

/**
 * Class details
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class details extends modal_form {

    /** @var template */
    protected $template;

    /**
     * Template getter
     * @return template
     */
    protected function get_template() : template {
        $id = $this->optional_param('id', 0, PARAM_INT);
        $contextid = $this->optional_param('contextid', \context_system::instance()->id, PARAM_INT);
        if ($this->template === null) {
            $obj = null;
            if (!$id) {
                $obj = (object)['contextid' => $contextid];
            }
            $this->template = template::instance($id, $obj);
        }
        return $this->template;
    }

    /**
     * Form definition
     */
    public function definition() {

        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('name', 'tool_certificate'), 'maxlength="255"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');

        if ($categoryoptions = $this->get_category_options()) {
            $mform->addElement('select', 'categoryid', get_string('coursecategory', ''), $categoryoptions);
            $mform->setType('categoryid', PARAM_INT);
        } else {
            $mform->addElement('hidden', 'contextid');
        }

        $mform->addElement('advcheckbox', 'shared', get_string('availableincourses', 'tool_certificate'));
        $mform->addHelpButton('shared', 'availableincourses', 'tool_certificate');
        $mform->setDefault('shared', 1);

        if (!$this->get_template()->get_id()) {
            page::add_page_elements($mform);
        }
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
     * Some basic validation.
     *
     * @param array $data
     * @param array $files
     * @return array the errors that were found
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (\core_text::strlen($data['name']) > 255) {
            $errors['name'] = get_string('nametoolong', 'tool_certificate');
        }
        return $errors;
    }

    /**
     * Check if current user has access to this form, otherwise throw exception
     *
     * Sometimes permission check may depend on the action and/or id of the entity.
     * If necessary, form data is available in $this->_ajaxformdata
     */
    public function require_access() {
        if ($this->get_template()->get_id()) {
            $this->get_template()->require_can_manage();
        } else {
            permission::require_can_create();
        }
    }

    /**
     * Process the form submission
     *
     * This method can return scalar values or arrays that can be json-encoded, they will be passed to the caller JS.
     *
     * @param \stdClass $data
     * @return mixed
     */
    public function process(\stdClass $data) {
        global $CFG;
        require_once($CFG->dirroot.'/course/lib.php');

        if (isset($data->categoryid)) {
            $data->contextid = get_category_or_system_context($data->categoryid)->id;
        }
        unset($data->categoryid);
        if (!$this->get_template()->get_id()) {
            $this->template = template::create($data);
            $this->template->new_page()->save($data);
        } else {
            if ($data->contextid !== $this->template->get_context()->id) {
                // Move template files to the new context if the context has changed.
                $this->template->move_files_to_new_context($data->contextid);
            }
            $this->template->save($data);
        }
        $url = new \moodle_url('/admin/tool/certificate/template.php', ['id' => $this->template->get_id()]);
        return $url->out(false);
    }

    /**
     * Load in existing data as form defaults
     *
     * Can be overridden to retrieve existing values from db by entity id and also
     * to preprocess editor and filemanager elements
     */
    public function set_data_for_modal() {
        $template = $this->get_template();
        if ($template->get_id()) {
            $this->set_data([
                'id' => $this->template->get_id(),
                'name' => $this->template->get_name(),
                'shared' => $this->template->get_shared(),
                'categoryid' => $this->template->get_category_id()]);
        } else {
            $data = template::instance()->new_page()->to_record();
            unset($data->id, $data->templateid);
            $data->contextid = $this->optional_param('contextid', null, PARAM_INT);
            $this->set_data($data);
        }
    }
}
