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
     * @var int Option to display QR Code with verification URL
     */
    const DISPLAY_QRCODE = 4;

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
        $options[self::DISPLAY_QRCODE] = get_string('displayqrcode', 'certificateelement_code');

        $mform->addElement('select', 'display', get_string('display', 'certificateelement_code'), $options);
        $mform->addHelpButton('display', 'display', 'certificateelement_code');
        $mform->setDefault('display', self::DISPLAY_QRCODE);

        parent::render_form_elements($mform);
        $mform->setDefault('width', 35);
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
     * Formats a code according to current display value
     *
     * @param string $code
     * @return string
     */
    protected function format_code($code) {
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

        return $display;
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
        if ($preview) {
            $code = \tool_certificate\certificate::generate_code($user->id);
        } else {
            $code = $issue->code;
        }

        if (json_decode($this->get_data())->display == self::DISPLAY_QRCODE) {
            $this->render_qrcode($pdf, $code);
        } else {
            \tool_certificate\element_helper::render_content($pdf, $this, $this->format_code($code));
        }
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
        global $OUTPUT;

        $data = json_decode($this->get_data(), true);

        if ($data['display'] == self::DISPLAY_QRCODE) {
            $url = $OUTPUT->image_url('qrcode', 'tool_certificate')->out(false);
            $w = $this->get_width();
            $imageinfo = $data + ['width' => $w, 'height' => $w];

            $html = \tool_certificate\element_helper::render_image_html($url, $imageinfo,
                (float)$imageinfo['width'], (float)$imageinfo['height'], $this->get_display_name());
        } else {
            $code = \tool_certificate\certificate::generate_code();
            $html = \tool_certificate\element_helper::render_html_content($this, $this->format_code($code));
        }

        return $html;
    }

    /**
     * Put a QR code in cerficate pdf object
     *
     * @param pdf $pdf The pdf object
     * @param string $code The certificate code
     */
    protected function render_qrcode($pdf, $code) {

        $style = [
            'border' => 0,
            'vpadding' => 'auto',
            'hpadding' => 'auto',
            'fgcolor' => array(0, 0, 0),
            'bgcolor' => array(255, 255, 255),
            'module_width' => 1,
            'module_height' => 1
        ];

        $codeurl = new \moodle_url("/admin/tool/certificate/index.php", ['code' => $code]);

        $x = $this->get_posx();
        $y = $this->get_posy();
        $w = $this->get_width();
        $refpoint = $this->get_refpoint();

        // Adjust X depending on the current refpoint.
        if ($refpoint == \tool_certificate\element_helper::CUSTOMCERT_REF_POINT_TOPRIGHT) {
            $x = $x - $w;
        } else if ($refpoint == \tool_certificate\element_helper::CUSTOMCERT_REF_POINT_TOPCENTER) {
            $x = $x - $w / 2;
        }
        $w += 0.0001;

        $pdf->setCellPaddings(0, 0, 0, 0);
        $pdf->write2DBarcode($codeurl->out(false), 'QRCODE,M', $x, $y, $w, $w, $style, 'N');
        $pdf->SetXY($x, $y + 49);
        $pdf->SetFillColor(255, 255, 255);
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

    /**
     * Returns the width.
     *
     * @return int
     */
    public function get_width(): int {
        $width = $this->persistent->get('width');
        return $width > 0 ? $width : 35;
    }
}
