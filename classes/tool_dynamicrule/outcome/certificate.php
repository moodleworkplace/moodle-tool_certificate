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

use tool_certificate\customfield\issue_handler;
use tool_certificate\permission;
use tool_wp\exporter_base;
use tool_wp\importer_base;

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
        $options = [
            'ajax' => 'tool_certificate/form_certificate_selector',
            'multiple' => false,
            'class' => 'select_certificate',
            'valuehtmlcallback' => [$this, 'get_certificate_name']
        ];
        $selected = $this->get_selected();
        $mform->addElement('autocomplete', 'certificate', get_string('selectcertificate', 'tool_certificate'), $selected, $options);
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
     * @param array $users The users objects to apply the outcome to
     */
    public function apply_to_users(array $users) {
        // TODO SP-611 implement.
        // There is a challenge here because we need to know information about the program or course
        // that was completed in the conditions for this certificate to be issued.

        // TODO add tests.
        $issuedataall = $this->get_data_from_conditions(issue_handler::create()->get_all_fields_shortnames(), $users);

        $template = \tool_certificate\template::instance($this->get_certificateid());

        foreach ($users as $user) {
            $issuedata = $issuedataall[$user->id];
            $expirydate = null;
            if (isset($issuedata['expirydatetimestamp']) && $issuedata['expirydatetimestamp'] > 0) {
                $expirydate = $issuedata['expirydatetimestamp'];
            }
            $template->issue_certificate($user->id, $expirydate, $issuedata, 'tool_dynamicrule');
        }
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
    public function get_certificate_name(): string {
        global $DB;
        if ($cid = $this->get_certificateid()) {
            if ($c = $DB->get_field_sql("SELECT name FROM {tool_certificate_templates} WHERE id = ?", [$cid])) {
                $options = ['context' => \context_system::instance(), 'escape' => false];
                return format_string($c, true, $options);
            }
        }
        return '';
    }

    /**
     * Check if certificate exists.
     *
     * @return bool
     */
    public function is_configuration_valid(): bool {
        return \tool_certificate\persistent\template::record_exists($this->get_certificateid());
    }

    /**
     * Return configured certificate id.
     *
     * @return int
     */
    public function get_certificateid() {
        if (isset($this->get_configdata()['certificate'])) {
            $b = (int)$this->get_configdata()['certificate'];
        } else {
            $b = null;
        }
        return $b;
    }

    /**
     * Return id and name of selected certificates.
     *
     * @return array
     */
    private function get_selected(): array {
        if ($id = $this->get_certificateid()) {
            $selected = [$id => $this->get_certificate_name()];
        } else {
            $selected = [];
        }
        return $selected;
    }

    /**
     * If the current user is able to add this outcome.
     *
     * @return bool
     */
    public function user_can_add(): bool {
        if (\tool_certificate\permission::can_issue_to_anybody(\context_system::instance())) {
            return true;
        }
        // This is not 100% hit, this function returns the contexts where user is able to VIEW templates but we
        // need ones there he can issue. However it is the same method that is used in the
        // tool_certificate_potential_certificate_selector Web service, the one that certifications
        // selector uses.
        // If user selects template they can't issue in they'll get an error raised by user_can_edit().
        $contexts = permission::get_visible_categories_contexts();
        return !empty($contexts);
    }

    /**
     * If the current user is able to edit this outcome.
     *
     * @param array $configdata
     * @return bool
     */
    public function user_can_edit(array $configdata): bool {
        $template = \tool_certificate\template::instance($configdata['certificate']);
        return $template->can_issue_to_anybody();
    }

    /**
     * Add certificate outcome field mapping during export
     *
     * @param exporter_base $exporter
     */
    public function add_exporter_mapping(exporter_base $exporter): void {
        $exporter->add_mapping('certificate', $this->get_certificateid());
    }

    /**
     * Get certificate outcome field mapping during import
     *
     * @param importer_base $importer
     */
    public function get_importer_mapping(importer_base $importer): void {
        $configdata = $this->get_configdata();
        $configdata['certificate'] = $importer->get_mapping('certificate', $this->get_certificateid(), IGNORE_MISSING) ?? 0;

        $this->update_configdata($configdata);
    }
}
