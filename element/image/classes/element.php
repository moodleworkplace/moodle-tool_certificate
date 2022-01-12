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
 * This file contains the certificate element image's core interaction API.
 *
 * @package    certificateelement_image
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace certificateelement_image;

use tool_certificate\element_helper;

/**
 * The certificate element image's core interaction API.
 *
 * @package    certificateelement_image
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element extends \tool_certificate\element {

    /**
     * @var array The file manager options.
     */
    protected $filemanageroptions = array();

    /**
     * Constructor.
     */
    protected function __construct() {
        global $COURSE;

        $this->filemanageroptions = array(
            'maxbytes' => $COURSE->maxbytes,
            'subdirs' => 0,
            'accepted_types' => 'web_image',
            'maxfiles' => 1
        );

        parent::__construct();
    }

    /**
     * Add option to use the image as a background
     * @return bool
     */
    protected function can_be_used_as_a_background() {
        return true;
    }

    /**
     * This function renders the form elements when adding a certificate element.
     *
     * @param \MoodleQuickForm $mform the edit_form instance
     */
    public function render_form_elements($mform) {
        $mform->addElement('filemanager', 'image', get_string('uploadimage', 'tool_certificate'), '',
            $this->filemanageroptions);

        if (element_helper::render_shared_image_picker_element($mform)) {
            $mform->addFormRule(function($data) {
                $draffiles = file_get_draft_area_info($data['image']);
                if ((!$draffiles['filesize'] && !$data['fileid']) || ($draffiles['filesize'] && $data['fileid'])) {
                    return ['image' => get_string('imagerequired', 'certificateelement_image')];
                }
                return [];
            });
        } else {
            $mform->addRule('image', get_string('required'), 'required', null, 'client');
        }

        if ($this->can_be_used_as_a_background()) {
            $mform->addElement('advcheckbox', 'isbackground', '', get_string('isbackground', 'certificateelement_image'));
        }

        element_helper::render_form_element_width($mform, 'certificateelement_image');
        element_helper::render_form_element_height($mform, 'certificateelement_image');

        element_helper::render_form_element_position($mform);

        if ($this->can_be_used_as_a_background()) {
            $mform->hideIf('width', 'isbackground', 'checked');
            $mform->hideIf('height', 'isbackground', 'checked');
            $mform->hideIf('posx', 'isbackground', 'checked');
            $mform->hideIf('posy', 'isbackground', 'checked');
        }

    }

    /**
     * Handles saving the form elements created by this element.
     * Can be overridden if more functionality is needed.
     *
     * @param \stdClass $data the form data
     */
    public function save_form_data(\stdClass $data) {
        $data->data = $this->calculate_additional_data($data);

        parent::save_form_data($data);

        // Handle file uploads.
        file_save_draft_area_files($data->image, $this->get_template()->get_context()->id,
            'tool_certificate', 'element', $this->get_id());
    }

    /**
     * This will handle how form data will be saved into the data column in the
     * tool_certificate_elements table.
     *
     * @param \stdClass $data the form data
     * @return string the json encoded array
     */
    private function calculate_additional_data($data) {
        $arrtostore = [
            'width' => !empty($data->width) ? (int) $data->width : 0,
            'height' => !empty($data->height) ? (int) $data->height : 0
        ];
        if ($this->can_be_used_as_a_background()) {
            $arrtostore['isbackground'] = !empty($data->isbackground);
        }

        if (!empty($data->fileid)) {
            // Array of data we will be storing in the database.
            $fs = get_file_storage();
            if ($file = $fs->get_file_by_id($data->fileid)) {
                $arrtostore += [
                    'contextid' => $file->get_contextid(),
                    'filearea' => $file->get_filearea(),
                    'itemid' => $file->get_itemid(),
                    'filepath' => $file->get_filepath(),
                    'filename' => $file->get_filename(),
                ];
            }
        }

        return json_encode($arrtostore);
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
        if (!$file = $this->get_file()) {
            return;
        }
        $imageinfo = @json_decode($this->get_data(), true) + ['width' => 0, 'height' => 0];
        if ($this->is_background()) {
            $page = $this->get_page()->to_record();
            $imageinfo['width'] = $page->width;
            $imageinfo['height'] = $page->height;
        }

        element_helper::render_image($pdf, $this, $file, [], $imageinfo['width'], $imageinfo['height']);
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
        if ($this->is_background()) {
            $page = $this->get_page()->to_record();
            $imageinfo['width'] = $page->width;
            $imageinfo['height'] = $page->height;
        } else {
            $imageinfo = ($this->get_data() ? json_decode($this->get_data(), true) : [])
                + ['width' => 0, 'height' => 0];
        }

        if (!$file = $this->get_file()) {
            // Broken file icon.
            $url = $OUTPUT->image_url('brokenfile', 'tool_certificate')->out(false);
            $fileimageinfo = ['width' => 140, 'height' => 140];
        } else {
            // Link to the file.
            $url = \moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(),
                $file->get_itemid(), $file->get_filepath(), $file->get_filename());
            $fileimageinfo = $file->get_imageinfo();
        }

        return element_helper::render_image_html($url, $fileimageinfo, (float)$imageinfo['width'], (float)$imageinfo['height'],
            $this->get_display_name());
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
            $keys = ['width', 'height', 'isbackground'];
            foreach ($keys as $key) {
                if (isset($dateinfo->$key)) {
                    $record->$key = $dateinfo->$key;
                }
            }
        }

        if ($this->get_id()) {
            // Load element image.
            $draftitemid = file_get_submitted_draft_itemid('image');
            $context = $this->get_template()->get_context();
            file_prepare_draft_area($draftitemid, $context->id, 'tool_certificate', 'element',
                $this->get_id(), $this->filemanageroptions);
            $record->image = $draftitemid;
        }

        if ($file = $this->get_shared_file()) {
            $record->fileid = $file->get_id();
        }

        return $record;
    }

    /**
     * Fetch stored file.
     *
     * @return \stored_file|bool stored_file instance if exists, false if not
     */
    public function get_file() : ?\stored_file {

        $files = get_file_storage()->get_area_files($this->get_template()->get_context()->id,
            'tool_certificate', 'element', $this->get_id(), '', false);
        if (count($files)) {
            return reset($files);
        }

        return $this->get_shared_file();
    }

    /**
     * Fetch shared file
     *
     * @return null|\stored_file
     */
    public function get_shared_file() : ?\stored_file {
        $imageinfo = json_decode($this->get_data());
        if (!empty($imageinfo->filename) &&
                $file = get_file_storage()->get_file($imageinfo->contextid, 'tool_certificate', $imageinfo->filearea,
                    $imageinfo->itemid, $imageinfo->filepath, $imageinfo->filename)) {

            return $file;
        }
        return null;
    }

    /**
     * Is element draggable
     * @return bool
     */
    public function is_background(): bool {
        if ($this->can_be_used_as_a_background()) {
            $imageinfo = @json_decode($this->get_data(), true);
            return !empty($imageinfo['isbackground']);
        }
        return false;
    }

    /**
     * Is element draggable
     * @return bool
     */
    public function is_draggable(): bool {
        return !$this->is_background();
    }

    /**
     * Override get posx
     * @return int
     */
    public function get_posx() {
        return $this->is_background() ? 0 : parent::get_posx();
    }

    /**
     * Override get posy
     * @return int
     */
    public function get_posy() {
        return $this->is_background() ? 0 : parent::get_posy();
    }
}
