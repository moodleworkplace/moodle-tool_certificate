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
 * Issue new certificate for users.
 *
 * @package    tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate\form;

defined('MOODLE_INTERNAL') || die();

use tool_certificate\template;
use tool_wp\modal_form;

/**
 * Select tenant when duplicating a template.
 *
 * @package    tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tenant_selector extends modal_form {

    /** @var template */
    protected $template;

    /**
     * Get template
     *
     * @return template
     */
    protected function get_template() : template {
        if ($this->template === null) {
            $this->template = template::find_by_id($this->_ajaxformdata['id']);
        }
        return $this->template;
    }

    /**
     * Form definition.
     */
    public function definition() {
        $mform =& $this->_form;

        $mform->addElement('static', 'confirmmessage', '',
            get_string('duplicatetemplateconfirm', 'tool_certificate',
                $this->get_template()->get_formatted_name()));

        if (has_capability('tool/certificate:manageforalltenants', \context_system::instance())) {
            $tenants = \tool_tenant\tenancy::get_tenants();
            $options = [0 => get_string('shared', 'tool_certificate')];
            foreach ($tenants as $tenant) {
                $options[$tenant->id] = format_string($tenant->name, true, ['context' => \context_system::instance()]);
            }
            $mform->addElement('select', 'tenantid', get_string('selecttenant', 'tool_certificate'), $options);
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();
    }

    /**
     * Check if current user has access to this form, otherwise throw exception
     *
     * Sometimes permission check may depend on the action and/or id of the entity.
     * If necessary, form data is available in $this->_ajaxformdata
     */
    public function require_access() {
        if (!$this->get_template()->can_duplicate()) {
            throw new \required_capability_exception(\context_system::instance(), 'tool/certificate:manage',
                'nopermissions', 'error');
        }
    }

    /**
     * Process the form submission
     *
     * This method can return scalar values or arrays that can be json-encoded, they will be passed to the caller JS.
     *
     * @param \stdClass $data
     */
    public function process(\stdClass $data) {
        $this->get_template()->duplicate($data->tenantid);
    }

    /**
     * Load in existing data as form defaults
     *
     * Can be overridden to retrieve existing values from db by entity id and also
     * to preprocess editor and filemanager elements
     */
    public function set_data_for_modal() {
        $this->set_data($this->get_template()->to_record());
    }
}
