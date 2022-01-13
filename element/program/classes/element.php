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
 * This file contains the certificate element program's core interaction API.
 *
 * @package   certificateelement_program
 * @copyright 2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace certificateelement_program;

use core_customfield\data_controller;
use core_customfield\field_controller;
use tool_certificate\customfield\issue_handler;

/**
 * The certificate element program's core interaction API.
 *
 * @package   certificateelement_program
 * @copyright 2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element extends \tool_certificate\element {

    /**
     * This function renders the form elements when adding a certificate element.
     *
     * @param \MoodleQuickForm $mform the edit_form instance
     */
    public function render_form_elements($mform) {
        $handler = issue_handler::create();
        $handler->create_custom_fields_if_not_exist();

        $fields = $handler->get_fields();
        $options = ['' => ''];
        $textareas = [];
        foreach ($fields as $field) {
            if ($handler->can_view($field, 0)) {
                $options[$field->get('shortname')] = $field->get_formatted_name();
                if ($field->get('type') === 'textarea') {
                    $textareas[] = $field->get('shortname');
                }
            }
        }

        $mform->addElement('select', 'display', get_string('fieldoptions', 'certificateelement_program'), $options);
        $mform->addHelpButton('display', 'fieldoptions', 'certificateelement_program');
        $mform->addRule('display', null, 'required');

        parent::render_form_elements($mform);

        foreach ($textareas as $key) {
            $mform->hideIf('refpoint', 'display', 'eq', $key);
        }
    }

    /**
     * Handles saving the form elements created by this element.
     * Can be overridden if more functionality is needed.
     *
     * @param \stdClass $data the form data or partial data to be updated (i.e. name, posx, etc.)
     */
    public function save_form_data(\stdClass $data) {
        $data->data = json_encode(['display' => $data->display]);
        $field = issue_handler::create()->find_field_by_shortname($data->display);
        if ($field && $field->get('type') === 'textarea') {
            $data->refpoint = 0;
        }
        parent::save_form_data($data);
    }

    /**
     * Prepare data to pass to moodleform::set_data()
     *
     * @return \stdClass|array
     */
    public function prepare_data_for_form() {
        $record = parent::prepare_data_for_form();
        if (!empty($this->get_data())) {
            $dateinfo = json_decode($this->get_data());
            $record->display = $dateinfo->display;
        }
        return $record;
    }

    /**
     * Handles rendering the element on the pdf.
     *
     * @param \pdf $pdf the pdf object
     * @param bool $preview true if it is a preview, false otherwise
     * @param \stdClass $user the user we are rendering this for
     * @param \stdClass $issue the issue we are rendering
     */
    public function render($pdf, $preview, $user, $issue) {
        global $DB;
        if ($preview) {
            $display = $this->format_preview_data();
        } else {
            $display = $this->format_issue_data($issue);
        }
        \tool_certificate\element_helper::render_content($pdf, $this, $display);
    }

    /**
     * This function selects the field from issue data and formats it to be displayed.
     * @param \stdClass $issue
     * @return string The formated field to be displayed
     */
    public function format_issue_data($issue) {
        $thisdata = json_decode($this->get_data(), true);
        $customfields = issue_handler::create()->get_instance_data($issue->id, true);
        foreach ($customfields as $data) {
            if ($data->get_field()->get('shortname') === $thisdata['display']) {
                return $data->export_value();
            }
        }
        return '';
    }

    /**
     * This function generates dummy data and formats it to be displayed for each field type.
     * @return string The formated field to be displayed
     */
    public function format_preview_data() {
        $data = json_decode($this->get_data(), true);
        if ($field = issue_handler::create()->find_field_by_shortname($data['display'])) {
            $value = $field->get_configdata_property('previewvalue');
            $fielddata = data_controller::create(0, null, $field);
            $fielddata->set($fielddata->datafield(), $value);
            $fielddata->set('id', -1);
            if ($expvalue = $fielddata->export_value()) {
                return $fielddata->export_value();
            }
        }
        return $data['display'];
    }

    /**
     * Render the element in html.
     *
     * This function is used to render the element when we are using the
     * drag and drop interface to position it.
     *
     * @return string the html
     */
    public function render_html() {
        return \tool_certificate\element_helper::render_html_content($this, $this->format_preview_data());
    }
}
