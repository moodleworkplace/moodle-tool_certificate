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

declare(strict_types=1);

namespace tool_certificate\form;

use context;
use core_form\dynamic_form;
use moodle_url;
use tool_certificate\task\regenerate_certificates;
use tool_certificate\template;

/**
 * Certificate renew form class.
 *
 * @package   tool_certificate
 * @copyright 2025 Moodle Pty Ltd <support@moodle.com>
 * @author    2025 Tasio Bertomeu Gomez <tasio.bertomeu@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate_renew extends dynamic_form {
    /**
     * Definition of the form to issue certificates.
     */
    public function definition(): void {
        $mform = $this->_form;
        $mform->setDisableShortforms();
        // Add empty header for consistency.
        $mform->addElement('header', 'hdr', '');
        $mform->addElement('hidden', 'actiontype', $this->optional_param('actiontype', '', PARAM_ALPHA));
        $mform->setType('actiontype', PARAM_ALPHA);
        $mform->addElement('hidden', 'templateid', $this->optional_param('templateid', '', PARAM_INT));
        $mform->setType('templateid', PARAM_INT);
        $mform->addElement('hidden', 'issueids', $this->optional_param('issueids', '', PARAM_SEQUENCE));
        $mform->setType('issueids', PARAM_SEQUENCE);
        $mform->addElement('html', $this->get_html());
        $mform->addElement('advcheckbox', 'sendnotification', null, $this->get_checkbox_text());
        $mform->addHelpButton('sendnotification', 'sendnotificationaffectedusers', 'tool_certificate');
    }

    /**
     * Get the appropriate HTML for the modal based on action type.
     *
     * @return string
     */
    private function get_html(): string {
        switch ($this->optional_param('actiontype', '', PARAM_ALPHA)) {
            case 'regenerateall':
                return get_string('regeneratefileconfirmallusers', 'tool_certificate');
            case 'regenerateselectedusers':
                return get_string('regeneratefileconfirmselectedusers', 'tool_certificate');
            case 'regeneratesingleuser':
            default:
                return get_string('regeneratefileconfirmsingleuser', 'tool_certificate');
        }
    }

    /**
     * Get the appropriate text for the send notification checkbox based on action type.
     *
     * @return string
     */
    private function get_checkbox_text(): string {
        $actiontype = $this->optional_param('actiontype', '', PARAM_ALPHA);

        if ($actiontype === 'regenerateall' || $actiontype === 'regenerateselectedusers') {
            return get_string('sendnotificationaffectedusers', 'tool_certificate');
        }

        return get_string('sendnotificationsingleuser', 'tool_certificate');
    }

    /**
     * Returns context where this form is used
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return \context_system::instance();
    }

    /**
     * Check if current user has access to this form, otherwise throw exception
     *
     * Sometimes permission check may depend on the action and/or id of the entity.
     * If necessary, form data is available in $this->_ajaxformdata or
     * by calling $this->optional_param()
     */
    protected function check_access_for_dynamic_submission(): void {
        global $DB;

        $templateid = $this->optional_param('templateid', 0, PARAM_INT);
        // When selecting users from the list, the template ID is not provided but we can get it from one of the selected user.
        if ($templateid === 0) {
            $issueids = explode(",", $this->optional_param('issueids', '', PARAM_RAW));
            $issue = $DB->get_record('tool_certificate_issues', ['id' => $issueids[0]], '*', MUST_EXIST);
            $templateid = (int)$issue->templateid;
        }
        $template = template::instance($templateid);
        if (!$template->can_issue_to_anybody()) {
            throw new \moodle_exception('issuenotallowed', 'tool_certificate');
        }
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * This method can return scalar values or arrays that can be json-encoded, they will be passed to the caller JS.
     *
     * @return void
     */
    public function process_dynamic_submission(): void {
        global $DB;
        $data = $this->get_data();

        // If we are regenerating a single user, do it now.
        if ($data->actiontype === 'regeneratesingleuser') {
            $issue = $DB->get_record('tool_certificate_issues', ['id' => $data->issueids], '*', MUST_EXIST);
            $template = template::instance((int)$issue->templateid);
            $template->create_issue_file($issue, true, (bool) $data->sendnotification);
        } else {
            regenerate_certificates::queue(
                $data->actiontype,
                $data->templateid,
                explode(",", $data->issueids),
                (bool) $data->sendnotification,
            );
        }
    }

    /**
     * Load in existing data as form defaults
     *
     * Can be overridden to retrieve existing values from db by entity id and also
     * to preprocess editor and filemanager elements
     */
    public function set_data_for_dynamic_submission(): void {
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     * This is used in the form elements sensitive to the page url, such as Atto autosave in 'editor'
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        global $DB;

        $path = '/admin/tool/certificate/manage_templates.php';
        $templateid = $this->optional_param('templateid', 0, PARAM_INT);
        if ($this->optional_param('actiontype', '', PARAM_ALPHA) !== 'regenerateall') {
            $path = '/admin/tool/certificate/certificates.php';
            $issueids = explode(",", $this->optional_param('issueids', '', PARAM_RAW));
            $issue = $DB->get_record('tool_certificate_issues', ['id' => $issueids[0]], '*', MUST_EXIST);
            $templateid = (int)$issue->templateid;
        }

        return new moodle_url($path, ['templateid' => $templateid]);
    }
}
