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

use tool_certificate\template;
use tool_certificate\modal_form;

defined('MOODLE_INTERNAL') || die();

/**
 * Class page
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page extends modal_form {

    /** @var \tool_certificate\page */
    protected $page;

    /**
     * Template getter
     * @return template
     */
    protected function get_template() : template {
        return $this->get_page()->get_template();
    }

    /**
     * Get page
     *
     * @return \tool_certificate\page
     */
    protected function get_page() : \tool_certificate\page {
        if ($this->page === null) {
            if (!empty($this->_ajaxformdata['id'])) {
                $this->page = \tool_certificate\page::instance((int)$this->_ajaxformdata['id']);
            } else {
                $template = template::instance($this->_ajaxformdata['templateid']);
                $this->page = $template->new_page();
            }
        }
        return $this->page;
    }

    /**
     * Form definition
     */
    public function definition() {

        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'templateid');
        $mform->setType('templateid', PARAM_INT);

        self::add_page_elements($mform);
    }

    /**
     * Add page elements to the form
     *
     * @param \MoodleQuickForm $mform
     */
    public static function add_page_elements(\MoodleQuickForm $mform) {

        $mform->addElement('text', 'width', get_string('pagewidth', 'tool_certificate'));
        $mform->setType('width', PARAM_INT);
        $mform->addRule('width', null, 'required', null, 'client');
        $mform->addRule('width', null, 'numeric', null, 'client');
        $mform->addHelpButton('width', 'pagewidth', 'tool_certificate');

        $mform->addElement('text', 'height', get_string('pageheight', 'tool_certificate'));
        $mform->setType('height', PARAM_INT);
        $mform->addRule('height', null, 'required', null, 'client');
        $mform->addRule('height', null, 'numeric', null, 'client');
        $mform->addHelpButton('height', 'pageheight', 'tool_certificate');

        $mform->addElement('text', 'leftmargin', get_string('leftmargin', 'tool_certificate'));
        $mform->setType('leftmargin', PARAM_INT);
        $mform->addHelpButton('leftmargin', 'leftmargin', 'tool_certificate');

        $mform->addElement('text', 'rightmargin', get_string('rightmargin', 'tool_certificate'));
        $mform->setType('rightmargin', PARAM_INT);
        $mform->addHelpButton('rightmargin', 'rightmargin', 'tool_certificate');

        $mform->addFormRule(function($data, $files) {
            $errors = [];
            if (!is_numeric($data['width']) || (int)$data['width'] <= 0) {
                $errors['width'] = get_string('invalidwidth', 'tool_certificate');
            }
            if (!is_numeric($data['height']) || (int)$data['height'] <= 0) {
                $errors['height'] = get_string('invalidheight', 'tool_certificate');
            }
            if ((int)$data['leftmargin'] < 0) {
                $errors['leftmargin'] = get_string('invalidmargin', 'tool_certificate');
            }
            if ((int)$data['rightmargin'] < 0) {
                $errors['rightmargin'] = get_string('invalidmargin', 'tool_certificate');
            }
            return $errors;
        });
    }

    /**
     * Check if current user has access to this form, otherwise throw exception
     *
     * Sometimes permission check may depend on the action and/or id of the entity.
     * If necessary, form data is available in $this->_ajaxformdata
     */
    public function require_access() {
        $this->get_template()->require_can_manage();
    }

    /**
     * Process the form submission
     *
     * This method can return scalar values or arrays that can be json-encoded, they will be passed to the caller JS.
     *
     * @param \stdClass $data
     */
    public function process(\stdClass $data) {
        $this->get_page()->save($data);
    }

    /**
     * Load in existing data as form defaults
     *
     * Can be overridden to retrieve existing values from db by entity id and also
     * to preprocess editor and filemanager elements
     */
    public function set_data_for_modal() {
        $this->set_data($this->get_page()->to_record());
    }
}
