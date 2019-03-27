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
 * This file contains the certificate element code's core interaction API.
 *
 * @package    certificateelement_code
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace certificateelement_code;

defined('MOODLE_INTERNAL') || die();

/**
 * The certificate element code's core interaction API.
 *
 * @package    certificateelement_code
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element extends \tool_certificate\element {

    /**
     * @var int Option to display only code
     */
    const DISPLAY_CODE = 1;

    /**
     * @var int Option to display code and a link
     */
    const DISPLAY_CODELINK = 2;

    /**
     * @var int Option to display verification URL
     */
    const DISPLAY_URL = 3;

    /**
     * This function renders the form elements when adding a certificate element.
     *
     * @param \MoodleQuickForm $mform the edit_form instance
     */
    public function render_form_elements($mform) {

        // Get the possible date options.
        $options = [];
        $options[self::DISPLAY_CODE] = get_string('displaycode', 'certificateelement_code');
        $options[self::DISPLAY_CODELINK] = get_string('displaycodelink', 'certificateelement_code');
        $options[self::DISPLAY_URL] = get_string('displayurl', 'certificateelement_code');

        $mform->addElement('select', 'display', get_string('dateitem', 'certificateelement_date'), $options);
        $mform->addHelpButton('display', 'display', 'certificateelement_code');

        parent::render_form_elements($mform);
    }

    /**
     * Handles saving the form elements created by this element.
     * Can be overridden if more functionality is needed.
     *
     * @param \stdClass $data the form data or partial data to be updated (i.e. name, posx, etc.)
     */
    public function save_form_data(\stdClass $data) {
        $data->data = json_encode(['display' => $data->display]);
        parent::save_form_data($data);
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
            $code = \tool_certificate\certificate::generate_code();
        } else {
            $code = $issue->code;
        }

        $data = json_decode($this->get_data());
        switch ($data->display) {
            case self::DISPLAY_CODE:
                $display = $code;
                break;
            case self::DISPLAY_CODELINK:
                $display = \html_writer::link(\tool_certificate\template::verification_url($code), $code);
                break;
            case self::DISPLAY_URL:
                $display = \tool_certificate\template::verification_url($code);
                break;
            default:
                $display = $code;
        }

        \tool_certificate\element_helper::render_content($pdf, $this, $display);
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
        $code = \tool_certificate\certificate::generate_code();

        // TODO this is different from render() !
        return \tool_certificate\element_helper::render_html_content($this, $code);
    }

    /**
     * Prepare data to pass to moodleform::set_data()
     *
     * @return \stdClass|array
     */
    public function prepare_data_for_form() {
        $record = parent::prepare_data_for_form();
        if ($this->get_data()) {
            $dateinfo = json_decode($this->get_data());
            $record->display = $dateinfo->display;
        }
        return $record;
    }
}
