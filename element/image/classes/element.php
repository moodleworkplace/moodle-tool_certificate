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
 * This file contains the certificate element image's core interaction API.
 *
 * @package    certificateelement_image
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace certificateelement_image;

use tool_certificate\element_helper;

defined('MOODLE_INTERNAL') || die();

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

    /** @var bool $istext This is a text element, it has font, color and width limiter */
    protected $istext = false;

    /**
     * Constructor.
     */
    protected function __construct() {
        global $COURSE;

        $this->filemanageroptions = array(
            'maxbytes' => $COURSE->maxbytes,
            'subdirs' => 1,
            'accepted_types' => 'image'
        );

        parent::__construct();
    }

    /**
     * This function renders the form elements when adding a certificate element.
     *
     * @param \MoodleQuickForm $mform the edit_form instance
     */
    public function render_form_elements($mform) {
        $mform->addElement('select', 'fileid', get_string('image', 'certificateelement_image'), self::get_images());

        element_helper::render_form_element_width($mform, 'certificateelement_image');
        element_helper::render_form_element_height($mform, 'certificateelement_image');

        $mform->addElement('filemanager', 'certificateimage', get_string('uploadimage', 'tool_certificate'), '',
            $this->filemanageroptions);

        parent::render_form_elements($mform);
    }

    /**
     * Handles saving the form elements created by this element.
     * Can be overridden if more functionality is needed.
     *
     * @param \stdClass $data the form data
     */
    public function save(\stdClass $data) {
        // Handle file uploads.
        if (property_exists($data, 'certificateimage')) {
            \tool_certificate\certificate::upload_files($data->certificateimage,
                $this->get_template()->get_context()->id);
        }

        if (property_exists($data, 'height')) {
            $data->data = $this->calculate_additional_data($data);
        }

        parent::save($data);
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
        // If there is no element data, we have nothing to display.
        if (empty($this->get_data())) {
            return;
        }

        $imageinfo = json_decode($this->get_data());

        // If there is no file, we have nothing to display.
        if (empty($imageinfo->filename)) {
            return;
        }

        if ($file = $this->get_file()) {
            element_helper::render_image($pdf, $this, $file, [], $imageinfo->width, $imageinfo->height);
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
        // If there is no element data, we have nothing to display.
        if (empty($this->get_data())) {
            return '';
        }

        $imageinfo = json_decode($this->get_data());

        // If there is no file, we have nothing to display.
        if (empty($imageinfo->filename)) {
            return '';
        }

        // Get the image.
        $fs = get_file_storage();
        if ($file = $fs->get_file($imageinfo->contextid, 'tool_certificate', $imageinfo->filearea, $imageinfo->itemid,
                $imageinfo->filepath, $imageinfo->filename)) {
            $url = \moodle_url::make_pluginfile_url($file->get_contextid(), 'tool_certificate', 'image', $file->get_itemid(),
                $file->get_filepath(), $file->get_filename());
            $fileimageinfo = $file->get_imageinfo();

            return element_helper::render_image_html($url, $fileimageinfo, (float)$imageinfo->width, (float)$imageinfo->height);
        }
        return '';
    }

    /**
     * Sets the data on the form when editing an element.
     *
     * @param \MoodleQuickForm $mform the edit_form instance
     */
    public function definition_after_data($mform) {
        global $COURSE, $SITE;

        // Set the image, width and height for this element.
        if (!empty($this->get_data())) {
            $imageinfo = json_decode($this->get_data());
            if (!empty($imageinfo->filename)) {
                if ($file = $this->get_file()) {
                    $element = $mform->getElement('fileid');
                    $element->setValue($file->get_id());
                }
            }

            if (isset($imageinfo->width) && $mform->elementExists('width')) {
                $element = $mform->getElement('width');
                $element->setValue($imageinfo->width);
            }

            if (isset($imageinfo->height) && $mform->elementExists('height')) {
                $element = $mform->getElement('height');
                $element->setValue($imageinfo->height);
            }
        }

        // Set the context.
        if ($COURSE->id == $SITE->id) {
            $context = \context_system::instance();
        } else {
            $context = \context_course::instance($COURSE->id);
        }

        // Editing existing instance - copy existing files into draft area.
        $draftitemid = file_get_submitted_draft_itemid('certificateimage');
        file_prepare_draft_area($draftitemid, $context->id, 'tool_certificate', 'image', 0, $this->filemanageroptions);
        $element = $mform->getElement('certificateimage');
        $element->setValue($draftitemid);

        parent::definition_after_data($mform);
    }

    /**
     * Fetch stored file.
     *
     * @return \stored_file|bool stored_file instance if exists, false if not
     */
    public function get_file() {
        $imageinfo = json_decode($this->get_data());

        $fs = get_file_storage();

        return $fs->get_file($imageinfo->contextid, 'tool_certificate', $imageinfo->filearea, $imageinfo->itemid,
            $imageinfo->filepath, $imageinfo->filename);
    }

    /**
     * Return the list of possible images to use.
     *
     * @return array the list of images that can be used
     */
    public static function get_images() {
        global $COURSE;

        // Create file storage object.
        $fs = get_file_storage();

        // The array used to store the images.
        $arrfiles = array();
        // Loop through the files uploaded in the system context.
        if ($files = $fs->get_area_files(\context_system::instance()->id, 'tool_certificate', 'image', false, 'filename', false)) {
            foreach ($files as $hash => $file) {
                $arrfiles[$file->get_id()] = $file->get_filename();
            }
        }
        // Loop through the files uploaded in the course context.
        if ($files = $fs->get_area_files(\context_course::instance($COURSE->id)->id, 'tool_certificate', 'image', false,
            'filename', false)) {
            foreach ($files as $hash => $file) {
                $arrfiles[$file->get_id()] = $file->get_filename();
            }
        }

        \core_collator::asort($arrfiles);
        $arrfiles = array('0' => get_string('noimage', 'tool_certificate')) + $arrfiles;

        return $arrfiles;
    }
}
