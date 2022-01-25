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

use tool_certificate\template;
use tool_certificate\modal_form;
use tool_certificate\certificate as certificate_manager;

/**
 * Certificate issues form class.
 *
 * @package    tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate_issues extends modal_form {

    /** @var template */
    protected $template;

    /**
     * Get template
     *
     * @return template
     */
    protected function get_template() : template {
        if ($this->template === null) {
            $this->template = template::instance($this->_ajaxformdata['tid']);
        }
        return $this->template;
    }

    /**
     * Definition of the form with user selector and expiration time to issue certificates.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'tid');
        $mform->setType('tid', PARAM_INT);

        // Users.
        $options = array(
            'ajax' => 'tool_certificate/form-potential-user-selector',
            'multiple' => true,
            'data-itemid' => $this->get_template()->get_id()
        );
        $selectstr = get_string('selectuserstoissuecertificatefor', 'tool_certificate');
        $mform->addElement('autocomplete', 'users', $selectstr, array(), $options);

        // Expiry date.
        certificate_manager::add_expirydate_to_form($mform);
    }

    /**
     * Check if current user has access to this form, otherwise throw exception
     *
     * Sometimes permission check may depend on the action and/or id of the entity.
     * If necessary, form data is available in $this->_ajaxformdata
     */
    public function require_access() {
        if (!$this->get_template()->can_issue_to_anybody()) {
            throw new \moodle_exception('issuenotallowed', 'tool_certificate');
        }
    }

    /**
     * Process the form submission
     *
     * This method can return scalar values or arrays that can be json-encoded, they will be passed to the caller JS.
     *
     * @param \stdClass $data
     * @return int number of issues created
     */
    public function process(\stdClass $data) {
        $i = 0;
        $expirydate = certificate_manager::calculate_expirydate($data->expirydatetype, $data->expirydateabsolute,
            $data->expirydaterelative);
        foreach ($data->users as $userid) {
            if ($this->get_template()->can_issue($userid)) {
                $result = $this->get_template()->issue_certificate($userid, $expirydate);
                if ($result) {
                    $i++;
                }
            }
        }
        return $i;
    }

    /**
     * Load in existing data as form defaults
     *
     * Can be overridden to retrieve existing values from db by entity id and also
     * to preprocess editor and filemanager elements
     */
    public function set_data_for_modal() {
        $this->set_data(['tid' => $this->_ajaxformdata['tid']]);
    }
}
