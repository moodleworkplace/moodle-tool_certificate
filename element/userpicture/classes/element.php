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
 * This file contains the certificate element userpicture's core interaction API.
 *
 * @package    certificateelement_userpicture
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace certificateelement_userpicture;

use tool_certificate\element_helper;

/**
 * The certificate element userpicture's core interaction API.
 *
 * @package    certificateelement_userpicture
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element extends \tool_certificate\element {

    /**
     * This function renders the form elements when adding a certificate element.
     *
     * @param \MoodleQuickForm $mform the edit_form instance
     */
    public function render_form_elements($mform) {
        element_helper::render_form_element_width($mform, 'certificateelement_userpicture');
        element_helper::render_form_element_height($mform, 'certificateelement_userpicture');

        element_helper::render_form_element_position($mform);
    }

    /**
     * Handles saving the form elements created by this element.
     * Can be overridden if more functionality is needed.
     *
     * @param \stdClass $data the form data or partial data to be updated (i.e. name, posx, etc.)
     */
    public function save_form_data(\stdClass $data) {
        $data->data = json_encode(['width' => (int) $data->width, 'height' => (int) $data->height]);
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
        global $CFG;
        $imageinfo = @json_decode($this->get_data(), true) + ['width' => 0, 'height' => 0];

        // Get files in the user icon area.
        $context = \context_user::instance($user->id);
        $files = get_file_storage()->get_area_files($context->id, 'user', 'icon', 0, 'filename', false);
        $file = reset($files);

        // Show image if we found one.
        if ($file) {
            element_helper::render_image($pdf, $this, $file, [], $imageinfo['width'], $imageinfo['height']);
        } else if ($preview) { // Can't find an image, but we are in preview mode then display default pic.
            $location = $CFG->dirroot . '/pix/u/f1.png';
            element_helper::render_image($pdf, $this, $location,
                ['width' => 100, 'height' => 100], $imageinfo['width'], $imageinfo['height']);
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
        global $PAGE, $USER;
        $imageinfo = @json_decode($this->get_data(), true) + ['width' => 0, 'height' => 0];

        // Get the image.
        $userpicture = new \user_picture($USER);
        $userpicture->size = 1;
        $url = $userpicture->get_url($PAGE)->out(false);
        $strpictureof = get_string('pictureof', '', fullname($userpicture->user, true));

        return element_helper::render_image_html($url, ['width' => 100, 'height' => 100],
            $imageinfo['width'], $imageinfo['height'], $strpictureof);
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
            $record->width = $dateinfo->width;
            $record->height = $dateinfo->height;
        }
        return $record;
    }
}
