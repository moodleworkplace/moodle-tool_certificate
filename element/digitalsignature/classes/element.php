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

namespace certificateelement_digitalsignature;

use tool_certificate\element_helper;

/**
 * The certificate element digital signature's core interaction API.
 *
 * @package    certificateelement_digitalsignature
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element extends \certificateelement_image\element {

    /**
     * @var array The file manager options for the certificate.
     */
    protected $signaturefilemanageroptions = array();

    /**
     * Constructor.
     */
    protected function __construct() {
        global $COURSE;

        $this->signaturefilemanageroptions = [
            'maxbytes' => $COURSE->maxbytes,
            'subdirs' => 0,
            'accepted_types' => ['.crt'],
            'maxfiles' => 1
        ];

        parent::__construct();
    }

    /**
     * This function renders the form elements when adding a certificate element.
     *
     * @param \MoodleQuickForm $mform the edit_form instance
     */
    public function render_form_elements($mform) {
        $mform->addElement('filemanager', 'image', get_string('uploadimage', 'tool_certificate'), '',
            $this->filemanageroptions);

        // Ensure that user hasn't uploaded a file and selected a shared image (should be neither or just one).
        if (element_helper::render_shared_image_picker_element($mform)) {
            $mform->addFormRule(function($data) {
                $draffiles = file_get_draft_area_info($data['image']);
                if ($draffiles['filesize'] && $data['fileid']) {
                    return ['image' => get_string('errormultipleimages', 'certificateelement_digitalsignature')];
                }
                return [];
            });
        }

        element_helper::render_form_element_width($mform, 'certificateelement_digitalsignature');
        element_helper::render_form_element_height($mform, 'certificateelement_digitalsignature');

        // If user isn't uploading a file or selecting a shared image, they must specify height and width.
        $mform->addFormRule(function($data) {
            $errors = [];
            $draftfiles = file_get_draft_area_info($data['image']);
            $noimage = !$draftfiles['filesize'] && empty($data['fileid']);
            if ($noimage && empty($data['width'])) {
                $errors['width'] = get_string('invalidwidth', 'tool_certificate');
            }
            if ($noimage && empty($data['height'])) {
                $errors['height'] = get_string('invalidheight', 'tool_certificate');
            }
            return $errors;
        });

        $mform->addElement('filemanager', 'signature', get_string('digitalsignature', 'certificateelement_digitalsignature'), '',
            $this->signaturefilemanageroptions);

        $mform->addElement('text', 'signaturename', get_string('signaturename', 'certificateelement_digitalsignature'));
        $mform->setType('signaturename', PARAM_TEXT);
        $mform->setDefault('signaturename', '');

        $mform->addElement('passwordunmask', 'signaturepassword',
            get_string('signaturepassword', 'certificateelement_digitalsignature'));
        $mform->setType('signaturepassword', PARAM_TEXT);
        $mform->setDefault('signaturepassword', '');

        $mform->addElement('text', 'signaturelocation', get_string('signaturelocation', 'certificateelement_digitalsignature'));
        $mform->setType('signaturelocation', PARAM_TEXT);
        $mform->setDefault('signaturelocation', '');

        $mform->addElement('text', 'signaturereason', get_string('signaturereason', 'certificateelement_digitalsignature'));
        $mform->setType('signaturereason', PARAM_TEXT);
        $mform->setDefault('signaturereason', '');

        $mform->addElement('text', 'signaturecontactinfo',
            get_string('signaturecontactinfo', 'certificateelement_digitalsignature'));
        $mform->setType('signaturecontactinfo', PARAM_TEXT);
        $mform->setDefault('signaturecontactinfo', '');

        element_helper::render_form_element_position($mform);
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
        $imageinfo = ($this->get_data() ? json_decode($this->get_data(), true) : [])
            + ['width' => 0, 'height' => 0];

        if (!$file = $this->get_file()) {
            // Outline of a box.
            $size = element_helper::calculate_image_size('none', ['width' => 140, 'height' => 140],
                (float)$imageinfo['width'], (float)$imageinfo['height']);
            return \html_writer::div('&nbsp;', 'm-0 p-0', ['style' => 'border: 1px dotted black;',
                'data-width' => $size['width'], 'data-height' => $size['height']]);
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
     * Handles saving the form elements created by this element.
     * Can be overridden if more functionality is needed.
     *
     * @param \stdClass $data the form data
     */
    public function save_form_data(\stdClass $data) {

        $data->data = $this->calculate_additional_data($data);

        \tool_certificate\element::save_form_data($data);

        // Handle file uploads.
        file_save_draft_area_files($data->image, $this->get_template()->get_context()->id,
            'tool_certificate', 'element', $this->get_id());

        file_save_draft_area_files($data->signature, $this->get_template()->get_context()->id,
            'tool_certificate', 'elementaux', $this->get_id());
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
            'signaturename' => $data->signaturename,
            'signaturepassword' => $data->signaturepassword,
            'signaturelocation' => $data->signaturelocation,
            'signaturereason' => $data->signaturereason,
            'signaturecontactinfo' => $data->signaturecontactinfo,
            'width' => !empty($data->width) ? (int) $data->width : 0,
            'height' => !empty($data->height) ? (int) $data->height : 0
        ];

        // Array of data we will be storing in the database.
        $fs = get_file_storage();

        if (!empty($data->fileid)) {
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

        if (!empty($data->signaturefileid)) {
            if ($signaturefile = $fs->get_file_by_id($data->signaturefileid)) {
                $arrtostore += [
                    'signaturecontextid' => $signaturefile->get_contextid(),
                    'signaturefilearea' => $signaturefile->get_filearea(),
                    'signatureitemid' => $signaturefile->get_itemid(),
                    'signaturefilepath' => $signaturefile->get_filepath(),
                    'signaturefilename' => $signaturefile->get_filename()
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

        $imageinfo = json_decode($this->get_data());

        if ($file = $this->get_file()) {
            element_helper::render_image($pdf, $this, $file, [], $imageinfo->width, $imageinfo->height);
        }

        if ($signaturefile = $this->get_signature_file()) {
            $location = make_request_directory() . '/target.crt';
            $signaturefile->copy_content_to($location);
            $info = [
                'Name' => $imageinfo->signaturename,
                'Location' => $imageinfo->signaturelocation,
                'Reason' => $imageinfo->signaturereason,
                'ContactInfo' => $imageinfo->signaturecontactinfo
            ];
            $pdf->setSignature('file://' . $location, '', $imageinfo->signaturepassword, '', 2, $info);
            $size = element_helper::calculate_image_size($file, [], $imageinfo->width, $imageinfo->height);
            $pdf->setSignatureAppearance($this->get_posx(), $this->get_posy(), $size['width'], $size['height']);
        }
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
            $keys = ['signaturename', 'signaturepassword', 'signaturelocation', 'signaturereason', 'signaturecontactinfo'];
            foreach ($keys as $key) {
                if (isset($dateinfo->$key)) {
                    $record->$key = $dateinfo->$key;
                }
            }
        }

        if ($this->get_id()) {
            // Load signature file.
            $draftitemid = file_get_submitted_draft_itemid('signature');
            $context = $this->get_template()->get_context();
            file_prepare_draft_area($draftitemid, $context->id, 'tool_certificate', 'elementaux',
                $this->get_id(), $this->signaturefilemanageroptions);
            $record->signature = $draftitemid;
        }
        return $record;
    }

    /**
     * Fetch stored file.
     *
     * @return \stored_file|bool stored_file instance if exists, null if not
     */
    public function get_signature_file() :? \stored_file {
        $fs = get_file_storage();

        $files = $fs->get_area_files($this->get_template()->get_context()->id,
            'tool_certificate', 'elementaux', $this->get_id(), '', false);
        if (count($files)) {
            return reset($files);
        }

        return null;
    }

    /**
     * Can be used as a background
     * @return bool
     */
    public function can_be_used_as_a_background() {
        return false;
    }
}
