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
 * Class issues
 *
 * @package     tool_certificate
 * @copyright   2018 Daniel Neis
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate\external;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

/**
 * Class issues
 *
 * @package     tool_certificate
 * @copyright   2018 Daniel Neis
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issues extends \external_api {

    /**
     * Returns the delete_issue() parameters.
     *
     * @return \external_function_parameters
     */
    public static function revoke_issue_parameters() {
        return new \external_function_parameters(
            array(
                'id' => new \external_value(PARAM_INT, 'The issue id'),
            )
        );
    }

    /**
     * Handles deleting a certificate issue.
     *
     * @param int $issueid The issue id.
     */
    public static function revoke_issue($issueid) {
        global $DB;

        $params = self::validate_parameters(self::revoke_issue_parameters(), ['id' => $issueid]);

        $issue = $DB->get_record('tool_certificate_issues', ['id' => $params['id']], '*', MUST_EXIST);
        $template = \tool_certificate\template::instance($issue->templateid);

        // Make sure the user has the required capabilities.
        $context = \context_course::instance($issue->courseid, IGNORE_MISSING) ?: $template->get_context();
        self::validate_context($context);

        if (!$template->can_revoke($issue->userid, $context)) {
            throw new \required_capability_exception($template->get_context(), 'tool/certificate:issue', 'nopermissions', 'error');
        }

        // Delete the issue.
        $template->revoke_issue($issueid);
    }

    /**
     * Returns the revoke_issue result value.
     *
     * @return \external_value
     */
    public static function revoke_issue_returns() {
        return null;
    }

    /**
     * Returns the regenerate_issue_file() parameters.
     *
     * @return \external_function_parameters
     */
    public static function regenerate_issue_file_parameters() {
        return new \external_function_parameters(
            array(
                'id' => new \external_value(PARAM_INT, 'The issue id'),
            )
        );
    }

    /**
     * Handles regenerating a certificate issue file.
     *
     * @param int $issueid The issue id.
     */
    public static function regenerate_issue_file($issueid) {
        global $DB;

        $params = self::validate_parameters(self::regenerate_issue_file_parameters(), ['id' => $issueid]);

        $issue = $DB->get_record('tool_certificate_issues', ['id' => $params['id']], '*', MUST_EXIST);

        // Make sure the user has the required capabilities.
        $context = \context_system::instance();
        self::validate_context($context);
        $template = \tool_certificate\template::instance($issue->templateid);
        if (!$template->can_issue($issue->userid)) {
            throw new \required_capability_exception($template->get_context(), 'tool/certificate:issue', 'nopermissions', 'error');
        }

        // Regenerate the issue file.
        $template->create_issue_file($issue, true);
        // Update issue userfullname data.
        if ($user = $DB->get_record('user', ['id' => $issue->userid])) {
            $issuedata = @json_decode($issue->data, true);
            $issuedata['userfullname'] = fullname($user);
            $issue->data = json_encode($issuedata);
            $DB->update_record('tool_certificate_issues', $issue);
        }
    }

    /**
     * Returns the regenerate_issue_file result value.
     *
     * @return \external_value
     */
    public static function regenerate_issue_file_returns() {
        return null;
    }

}
