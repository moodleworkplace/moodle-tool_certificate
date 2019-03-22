<?php
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
 * Class details
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate\form;

use tool_certificate\template;
use tool_wp\modal_form;

defined('MOODLE_INTERNAL') || die();

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
    protected function get_template() : ?template {
        if ($this->template === null && !empty($this->_ajaxformdata['id'])) {
            $this->template = template::instance($this->_ajaxformdata['id']);
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

        if (template::can_issue_or_manage_all_tenants()) {
            $tenants = \tool_tenant\tenancy::get_tenants();
            $options = [0 => get_string('shared', 'tool_certificate')];
            foreach ($tenants as $tenant) {
                $options[$tenant->id] = format_string($tenant->name, true, ['context' => \context_system::instance()]);
            }
            $mform->addElement('select', 'tenantid', get_string('selecttenant', 'tool_certificate'), $options);

            if ($this->get_template()) {
                $mform->freeze('tenantid');
            }
        }
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
        if ($template = $this->get_template()) {
            $template->require_manage();
        } else {
            if (!template::can_create()) {
                print_error('createnotallowed', 'tool_certificate');
            }
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
        if (!$this->get_template()) {
            $this->template = template::create($data);
            $this->template->add_page();
        } else {
            $this->template->save($data);
        }
        $url = new \moodle_url('/admin/tool/certificate/edit.php', ['tid' => $this->template->get_id()]);
        return $url->out(false);
    }

    /**
     * Load in existing data as form defaults
     *
     * Can be overridden to retrieve existing values from db by entity id and also
     * to preprocess editor and filemanager elements
     */
    public function set_data_for_modal() {
        if ($template = $this->get_template()) {
            $this->set_data([
                'id' => $this->template->get_id(),
                'name' => $this->template->get_name(),
                'tenantid' => $this->template->get_tenant_id()]);
        }
    }
}
