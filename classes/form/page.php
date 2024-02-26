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

use context;
use core_form\dynamic_form;
use moodle_url;
use tool_certificate\template;

/**
 * Class page
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page extends dynamic_form {

    /** @var \tool_certificate\page */
    protected $page;

    /**
     * Template getter
     * @return template
     */
    protected function get_template(): template {
        return $this->get_page()->get_template();
    }

    /**
     * Get page
     *
     * @return \tool_certificate\page
     */
    protected function get_page(): \tool_certificate\page {
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
        $mform = $this->_form;
        $mform->setDisableShortforms();
        // Add empty header for consistency.
        $mform->addElement('header', 'hdr', '');

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

        $group = [];
        $group[] =& $mform->createElement('text', 'width', get_string('pagewidth', 'tool_certificate'));
        $group[] =& $mform->createElement('static', 'widthmetric', '', get_string('milimeter', 'tool_certificate'));
        $mform->addElement('group', 'widthgroup', get_string('pagewidth', 'tool_certificate'), $group, ' ', false);
        $mform->setType('width', PARAM_INT);
        $mform->addHelpButton('widthgroup', 'pagewidth', 'tool_certificate');

        $group = [];
        $group[] =& $mform->createElement('text', 'height', get_string('pageheight', 'tool_certificate'));
        $group[] =& $mform->createElement('static', 'heightmetric', '', get_string('milimeter', 'tool_certificate'));
        $mform->addElement('group', 'heightgroup', get_string('pageheight', 'tool_certificate'), $group, ' ', false);
        $mform->setType('height', PARAM_INT);
        $mform->addHelpButton('heightgroup', 'pageheight', 'tool_certificate');

        $group = [];
        $group[] =& $mform->createElement('text', 'leftmargin', get_string('leftmargin', 'tool_certificate'));
        $group[] =& $mform->createElement('static', 'leftmarginmetric', '', get_string('milimeter', 'tool_certificate'));
        $mform->addElement('group', 'leftmargingroup', get_string('leftmargin', 'tool_certificate'), $group, ' ', false);
        $mform->setType('leftmargin', PARAM_INT);
        $mform->addHelpButton('leftmargingroup', 'leftmargin', 'tool_certificate');

        $group = [];
        $group[] =& $mform->createElement('text', 'rightmargin', get_string('rightmargin', 'tool_certificate'));
        $group[] =& $mform->createElement('static', 'rightmarginmetric', '', get_string('milimeter', 'tool_certificate'));
        $mform->addElement('group', 'rightmargingroup', get_string('rightmargin', 'tool_certificate'), $group, ' ', false);
        $mform->setType('rightmargin', PARAM_INT);
        $mform->addHelpButton('rightmargingroup', 'rightmargin', 'tool_certificate');

        $mform->addFormRule(function($data, $files) {
            $errors = [];
            if (!is_numeric($data['width']) || (int)$data['width'] <= 0) {
                $errors['widthgroup'] = get_string('invalidwidth', 'tool_certificate');
            }
            if (!is_numeric($data['height']) || (int)$data['height'] <= 0) {
                $errors['heightgroup'] = get_string('invalidheight', 'tool_certificate');
            }
            if ((int)$data['leftmargin'] < 0) {
                $errors['leftmargingroup'] = get_string('invalidmargin', 'tool_certificate');
            }
            if ((int)$data['rightmargin'] < 0) {
                $errors['rightmargingroup'] = get_string('invalidmargin', 'tool_certificate');
            }
            return $errors;
        });
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
     * @return void
     */
    public function process_dynamic_submission(): void {
        $this->get_page()->save($this->get_data());
    }

    /**
     * Load in existing data as form defaults
     *
     * Can be overridden to retrieve existing values from db by entity id and also
     * to preprocess editor and filemanager elements
     */
    public function set_data_for_dynamic_submission(): void {
        $this->set_data($this->get_page()->to_record());
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
            'id' => $this->get_page()->get_id(),
        ]);
    }
}
