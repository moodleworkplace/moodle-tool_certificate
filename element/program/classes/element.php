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
 * This file contains the certificate element program's core interaction API.
 *
 * @package   certificateelement_program
 * @copyright 2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace certificateelement_program;

defined('MOODLE_INTERNAL') || die();

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

        // Get the possible date options.
        $options = [
            'certificationname' => get_string('displaycertificationname', 'certificateelement_program'),
            'completedcourses' => get_string('displaycompletedcourses', 'certificateelement_program'),
            'completiondate' => get_string('displaycompletiondate', 'certificateelement_program'),
            'programname' => get_string('displayprogramname', 'certificateelement_program')
        ];

        $mform->addElement('select', 'display', get_string('fieldoptions', 'certificateelement_program'), $options);
        $mform->addHelpButton('display', 'fieldoptions', 'certificateelement_program');

        parent::render_form_elements($mform);
    }

    /**
     * This will handle how form data will be saved into the data column in the
     * tool_certificate_elements table.
     *
     * @param \stdClass $data the form data
     * @return string the json encoded array
     */
    public function save_unique_data($data) {
        // Array of data we will be storing in the database.
        $arrtostore = array(
            'display' => $data->display,
        );

        // Encode these variables before saving into the DB.
        return json_encode($arrtostore);
    }

    /**
     * Sets the data on the form when editing an element.
     *
     * @param \MoodleQuickForm $mform the edit_form instance
     */
    public function definition_after_data($mform) {
        // Set the item and format for this element.
        if (!empty($this->get_data())) {
            $data = json_decode($this->get_data());

            $element = $mform->getElement('display');
            $element->setValue($data->display);
        }

        parent::definition_after_data($mform);
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
            $display = 'Dummy content for program element';
        } else if (($issue->component == 'tool_program') || ($issue->component == 'tool_certification')) {
            $display = $this->format_issue_data($issue->data);
        } else {
            $display = '';
        }
        \tool_certificate\element_helper::render_content($pdf, $this, $display);
    }

    /**
     * This function selects the field from issue data and formats it to be displayed.
     * @param string $issuedata The data field of an issue, as json encoded string
     * @return string The formated field to be displayed
     */
    public function format_issue_data($issuedata) {
        $data = json_decode($issuedata, true);
        switch ($this->data->display) {
            case 'certificationname':
                $display = format_string($data['certificationname']);
                break;
            case 'programname':
                $display = format_string($data['programname']);
                break;
            case 'completiondate':
                $display = userdate($data['completiondate'], get_string('strftimedatefullshort', 'langconfig'), 99, false);
                break;
            case 'completedcourses':
                $display = \html_writer::start_tag('ul');
                foreach ($data['completedcourses'] as $c) {
                    $display .= \html_writer::tag('li', $c);
                }
                $display .= \html_writer::end_tag('ul');
        }
        return $display;
    }

    /**
     * This function generates dummy data and formats it to be displayed for each field type.
     * @return string The formated field to be displayed
     */
    public function format_preview_data() {
        $data = json_decode($this->data, true);
        switch ($data['display']) {
            case 'certificationname':
                $display = format_string(get_string('previewcertificationname', 'certificateelement_program'));
                break;
            case 'programname':
                $display = format_string(get_string('previewprogramname', 'certificateelement_program'));
                break;
            case 'completiondate':
                $display = userdate(time(), get_string('strftimedatefullshort', 'langconfig'), 99, false);
                break;
            case 'completedcourses':
                $courses = ['A course example', 'Second course example', 'Yet another course completed'];
                $display = \html_writer::start_tag('ul');
                foreach ($courses as $c) {
                    $display .= \html_writer::tag('li', $c);
                }
                $display .= \html_writer::end_tag('ul');
                break;
        }
        return $display;
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
