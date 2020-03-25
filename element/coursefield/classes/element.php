<?php
// This file is part of the tool_certificate for Moodle - http://moodle.org/
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
 * This file contains the certificate element coursefield's core interaction API.
 *
 * @package    certificateelement_coursefield
 * @copyright  2020 Mikel Martín <mikel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace certificateelement_coursefield;

defined('MOODLE_INTERNAL') || die();

/**
 * The certificate element coursefield's core interaction API.
 *
 * @package    certificateelement_coursefield
 * @copyright  2020 Mikel Martín <mikel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element extends \tool_certificate\element {

    /**
     * This function renders the form elements when adding a certificate element.
     *
     * @param \MoodleQuickForm $mform the edit_form instance
     */
    public function render_form_elements($mform) {
        // Get the course fields.
        $coursefields = [
            'fullname' => get_string('fullnamecourse'),
            'shortname' => get_string('shortnamecourse'),
            'idnumber' => get_string('idnumbercourse'),
            'summary' => get_string('summary'),
        ];

        // Get the course custom fields.
        $customfields = [];
        $handler = \core_course\customfield\course_handler::create();
        foreach ($handler->get_fields() as $field) {
            $customfields[$field->get('id')] = $field->get_formatted_name();
        }
        $fields = $coursefields + $customfields;

        // Create the select box where the user field is selected.
        $mform->addElement('select', 'coursefield', get_string('coursefield', 'certificateelement_coursefield'), $fields);
        $mform->setType('coursefield', PARAM_ALPHANUM);
        $mform->addHelpButton('coursefield', 'coursefield', 'certificateelement_coursefield');

        parent::render_form_elements($mform);
    }

    /**
     * Handles saving the form elements created by this element.
     * Can be overridden if more functionality is needed.
     *
     * @param \stdClass $data the form data or partial data to be updated (i.e. name, posx, etc.)
     */
    public function save_form_data(\stdClass $data) {
        $data->data = $data->coursefield;
        parent::save_form_data($data);
    }

    /**
     * Returns a field value
     *
     * @param \stdClass $course the course we are rendering this for
     * @param bool $preview
     * @return string
     */
    protected function get_course_field_value(\stdClass $course, bool $preview): string {
        // The course field to display.
        $field = $this->get_data();

        if ($preview) {
            $value = $field;
        } else {
            if (is_number($field)) { // Must be a custom course profile field.
                $handler = \core_course\customfield\course_handler::create();
                $data = $handler->get_instance_data($course->id, true);
                if (!empty($data[$field])) {
                    $value = $data[$field]->get('value');
                }
            } else if (!empty($course->$field)) {
                $value = $course->$field;
            }
        }
        return format_string($value ?? '', true, ['context' => \context_system::instance()]);
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
        global $DB, $COURSE;

        if ($preview) {
            $coursefield = $this->get_course_field_value($COURSE, $preview);
        } else {
            if (isset($issue->courseid) && $DB->record_exists('course', ['id' => $issue->courseid])) {
                $coursefield = $this->get_course_field_value(get_course($issue->courseid), $preview);
            }
        }
        $value = format_string($coursefield ?? '', true, ['context' => \context_system::instance()]);
        \tool_certificate\element_helper::render_content($pdf, $this, $value);
    }

    /**
     * Render the element in html.
     *
     * This function is used to render the element when we are using the
     * drag and drop interface to position it.
     *
     * @return string
     */
    public function render_html(): string {
        global $COURSE;

        $value = $this->get_course_field_value($COURSE, true);
        $value = format_string($value, true, ['context' => \context_system::instance()]);
        return \tool_certificate\element_helper::render_html_content($this, $value);
    }

    /**
     * Prepare data to pass to moodleform::set_data()
     *
     * @return \stdClass|array
     */
    public function prepare_data_for_form() {
        $record = parent::prepare_data_for_form();
        if (!empty($this->get_data())) {
            $record->coursefield = $this->get_data();
        }
        return $record;
    }
}
