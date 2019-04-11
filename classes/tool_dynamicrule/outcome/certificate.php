<?php
// This file is part of Moodle - http://moodle.org/
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
 * This file contains the backend class for issue certificate outcome.
 *
 * @package    tool_certificate
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate\tool_dynamicrule\outcome;

defined('MOODLE_INTERNAL') || die;

/**
 * The backend class for issue certificate outcome
 *
 * @package    tool_certificate
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate extends \tool_dynamicrule\outcome_base {

    /**
     * Returns the title of the outcome
     *
     * @return string The title as formated string
     */
    public function get_title(): string {
        return get_string('outcomecertificate', 'tool_certificate');
    }

    /**
     * Adds outcome's elements to the given mform
     *
     * @param \MoodleQuickForm $mform The form to add elements to
     */
    public function get_config_form(\MoodleQuickForm $mform) {
        // TODO SP-611 move WS into this plugin.
        $options = [
            'ajax' => 'tool_program/form_certificate_selector',
            'multiple' => false,
            'class' => 'select_certificate',
        ];
        $mform->addElement('autocomplete', 'certificate', get_string('selectcertificate', 'tool_program'), [], $options);
        $mform->addHelpButton('certificate', 'selectcertificate', 'tool_program');
        $mform->addRule('certificate', get_string('required'), 'required', null, 'client');
    }

    /**
     * Validates the configform of the outcome
     *
     * @param array $data Data from the form
     * @return array Array with errors for each element
     */
    public function validate_config_form(array $data): array {
        $errors = [];
        return $errors;
    }

    /**
     * Apply this outcome on a given list of users
     *
     * @param array $userids The userids to apply the outcome to
     */
    public function apply_to_users(array $userids) {
        global $DB;

        // TODO SP-611 implement.
    }

    /**
     * Return the description for the outcome.
     *
     * @return string
     */
    public function get_description(): string {
        return get_string('outcomecertificatedescription', 'tool_certificate', $this->get_certificate_name());
    }

    /**
     * Return subject formatted.
     *
     * @return string
     */
    private function get_certificate_name(): string {
        // TODO SP-611 cache.
        global $DB;
        $cid = (int)$this->get_configdata()['certificate'];
        if ($cid) {
            $c = $DB->get_record_sql("SELECT * FROM {tool_certificate_templates} WHERE id=?", [$cid]);
            if ($c) {
                $options = ['context' => \context_system::instance(), 'escape' => false];
                return format_string($c->name, true, $options);
            }
        }
        return '';
    }

    /**
     * Check if certificate is not empty.
     *
     * @return bool
     */
    public function is_configuration_valid(): bool {
        return !empty($this->get_configdata()['certificate']);
    }
}
