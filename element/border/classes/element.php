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
 * This file contains the certificate element border's core interaction API.
 *
 * @package    certificateelement_border
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace certificateelement_border;

use tool_certificate\element_helper;

/**
 * The certificate element border's core interaction API.
 *
 * @package    certificateelement_border
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element extends \tool_certificate\element {

    /**
     * This function renders the form elements when adding a certificate element.
     *
     * @param \MoodleQuickForm $mform the edit_form instance
     */
    public function render_form_elements($mform) {
        // We want to define the width of the border.
        element_helper::render_form_element_width($mform, 'certificateelement_border');
        $mform->setDefault('width', 1);

        // The only other thing to define is the colour we want the border to be.
        element_helper::render_form_element_colour($mform);
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
        $colour = \TCPDF_COLORS::convertHTMLColorToDec($this->get_colour(), $colour);
        // Set double width because half of the width will be outside of the page.
        $pdf->SetLineStyle(array('width' => 2 * $this->get_data(), 'color' => $colour));
        $pdf->Line(0, 0, $pdf->getPageWidth(), 0);
        $pdf->Line($pdf->getPageWidth(), 0, $pdf->getPageWidth(), $pdf->getPageHeight());
        $pdf->Line(0, $pdf->getPageHeight(), $pdf->getPageWidth(), $pdf->getPageHeight());
        $pdf->Line(0, 0, 0, $pdf->getPageHeight());
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
        $html = '';
        $page = $this->get_page()->to_record();
        $width = $this->get_data();
        $style = 'position: absolute; background-color: ' . $this->get_colour() . '';
        $html .= \html_writer::tag('div', '',
            ['data-width' => $width, 'data-height' => $page->height, 'style' => $style,
                'data-posx' => 0, 'data-posy' => 0]);
        $html .= \html_writer::tag('div', '',
            ['data-width' => $width, 'data-height' => $page->height, 'style' => $style,
                'data-posx' => $page->width - $width, 'data-posy' => 0]);
        $html .= \html_writer::tag('div', '',
            ['data-width' => $page->width, 'data-height' => $width, 'style' => $style,
                'data-posx' => 0, 'data-posy' => 0]);
        $html .= \html_writer::tag('div', '',
            ['data-width' => $page->width, 'data-height' => $width, 'style' => $style,
                'data-posx' => 0, 'data-posy' => $page->height - $width]);

        return $html;
    }

    /**
     * Prepare data to pass to moodleform::set_data()
     *
     * @return \stdClass|array
     */
    public function prepare_data_for_form() {
        $record = parent::prepare_data_for_form();
        if ($this->get_data()) {
            $record->width = $this->get_data();
        }
        return $record;
    }

    /**
     * Handles saving the form elements created by this element.
     * Can be overridden if more functionality is needed.
     *
     * @param \stdClass $data the form data or partial data to be updated (i.e. name, posx, etc.)
     */
    public function save_form_data(\stdClass $data) {
        $data->data = $data->width;
        parent::save_form_data($data);
    }

    /**
     * Can element be dragged.
     * @return bool
     */
    public function is_draggable(): bool {
        return false;
    }
}
