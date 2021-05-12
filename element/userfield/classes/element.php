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
 * This file contains the certificate element userfield's core interaction API.
 *
 * @package    certificateelement_userfield
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace certificateelement_userfield;

defined('MOODLE_INTERNAL') || die();

/**
 * The certificate element userfield's core interaction API.
 *
 * @package    certificateelement_userfield
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
        $displayname = function($field) {
            global $CFG;
            if ($CFG->version < 2021050700) {
                // Moodle 3.9-3.10.
                return get_user_field_name($field);
            } else {
                // Moodle 3.11 and above.
                return \core_user\fields::get_display_name($field);
            }
        };

        // Get the user profile fields.
        $userfields = array(
            'fullname' => $displayname('fullname'),
            'firstname' => $displayname('firstname'),
            'lastname' => $displayname('lastname'),
            'email' => $displayname('email'),
            'city' => $displayname('city'),
            'country' => $displayname('country'),
            'url' => $displayname('url'),
            'icq' => $displayname('icq'),
            'skype' => $displayname('skype'),
            'aim' => $displayname('aim'),
            'yahoo' => $displayname('yahoo'),
            'msn' => $displayname('msn'),
            'idnumber' => $displayname('idnumber'),
            'institution' => $displayname('institution'),
            'department' => $displayname('department'),
            'phone1' => $displayname('phone1'),
            'phone2' => $displayname('phone2'),
            'address' => $displayname('address')
        );
        // Get the user custom fields.
        $arrcustomfields = \availability_profile\condition::get_custom_profile_fields();
        $customfields = array();
        foreach ($arrcustomfields as $key => $customfield) {
            $customfields[$customfield->id] = $key;
        }
        // Combine the two.
        $fields = $userfields + $customfields;

        // Create the select box where the user field is selected.
        $mform->addElement('select', 'userfield', get_string('userfield', 'certificateelement_userfield'), $fields);
        $mform->setType('userfield', PARAM_ALPHANUM);
        $mform->addHelpButton('userfield', 'userfield', 'certificateelement_userfield');

        parent::render_form_elements($mform);
    }

    /**
     * Handles saving the form elements created by this element.
     * Can be overridden if more functionality is needed.
     *
     * @param \stdClass $data the form data or partial data to be updated (i.e. name, posx, etc.)
     */
    public function save_form_data(\stdClass $data) {
        $data->data = $data->userfield;
        parent::save_form_data($data);
    }

    /**
     * Returns a field value
     *
     * @param \stdClass $user the user we are rendering this for
     */
    protected function get_user_field_value($user) {
        global $CFG, $DB;

        // The user field to display.
        $field = $this->get_data();

        if ($field === 'fullname') {
            return fullname($user);
        } else if (is_number($field)) { // Must be a custom user profile field.
            if ($record = $DB->get_record('user_info_field', array('id' => $field))) {
                $file = $CFG->dirroot . '/user/profile/field/' . $record->datatype . '/field.class.php';
                if (file_exists($file)) {
                    require_once($CFG->dirroot . '/user/profile/lib.php');
                    require_once($file);
                    $class = "profile_field_{$record->datatype}";
                    /** @var \profile_field_base $pfield */
                    $pfield = new $class($record->id, $user->id);
                    return $pfield->display_data();
                }
            }
        } else if (!empty($user->$field)) { // Field in the user table.
            return \core_user::clean_field($user->$field, $field);
        }
        return '';
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
        // The value to display on the PDF.
        $value = $this->get_user_field_value($user);

        $value = format_string($value, true, ['context' => \context_system::instance()]);
        \tool_certificate\element_helper::render_content($pdf, $this, $value);
    }

    /**
     * Render the element in html.
     *
     * This function is used to render the element when we are using the
     * drag and drop interface to position it.
     */
    public function render_html() {
        global $USER;

        // The value to display - we always want to show a value here so it can be repositioned.
        $value = $this->get_user_field_value($USER);
        $value = strlen($value) ? $value : $this->get_data();

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
            $record->userfield = $this->get_data();
        }
        return $record;
    }
}
