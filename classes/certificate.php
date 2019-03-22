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
 * Provides functionality needed by certificate activities.
 *
 * @package    tool_certificate
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

use tool_tenant\tenancy;

defined('MOODLE_INTERNAL') || die();

/**
 * Class certificate.
 *
 * Helper functionality for certificates.
 *
 * @package    tool_certificate
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate {

    /**
     * @var int the number of issues that will be displayed on each page in the report
     *      If you want to display all certificates on a page set this to 0.
     */
    const CUSTOMCERT_PER_PAGE = '50';

    /**
     * Handles uploading an image for the certificate module.
     *
     * @param int $draftitemid the draft area containing the files
     * @param int $contextid the context we are storing this image in
     * @param string $filearea indentifies the file area.
     */
    public static function upload_files($draftitemid, $contextid, $filearea = 'image') {
        global $CFG;

        // Save the file if it exists that is currently in the draft area.
        require_once($CFG->dirroot . '/lib/filelib.php');
        file_save_draft_area_files($draftitemid, \context_system::instance()->id, 'tool_certificate', $filearea, 0);
    }

    /**
     * Returns the total number of issues for a given template.
     *
     * @param int $templateid
     * @return int the number of issues
     */
    public static function count_issues_for_template($templateid) {
        global $DB;
        if ($templateid > 0) {
            $conditions = ['templateid' => $templateid];
        } else {
            $conditions = [];
        }
        return $DB->count_records('tool_certificate_issues', $conditions);
    }

    /**
     * Get the certificate issues for a given templateid, paginated.
     *
     * @param int $templateid
     * @param int $limitfrom
     * @param int $limitnum
     * @param string $sort
     * @return array
     */
    public static function get_issues_for_template($templateid, $limitfrom, $limitnum, $sort = '') {
        global $DB;

        if (empty($sort)) {
            $sort = 'ci.timecreated DESC';
        }

        $conditions = ['templateid' => $templateid];

        if (\tool_certificate\template::can_issue_or_manage_all_tenants()) {
            $tenantjoin = '';
            $tenantwhere = ' u.deleted = 0';
        } else {
            list($tenantjoin, $tenantwhere, $tenantparams) = \tool_tenant\tenancy::get_users_sql();
            $conditions = array_merge($conditions, $tenantparams);
        }

        $sql = "SELECT ci.id, ci.code, ci.emailed, ci.timecreated, ci.userid, ci.templateid, ci.expires,
                       t.name, " .
                       get_all_user_name_fields(true, 'u') . "
                  FROM {tool_certificate_templates} t
                  JOIN {tool_certificate_issues} ci
                    ON (ci.templateid = t.id)
                  JOIN {user} u
                    ON (u.id = ci.userid)
                       {$tenantjoin}
                 WHERE t.id = :templateid
                   AND {$tenantwhere}
              ORDER BY {$sort}";

        return $DB->get_records_sql($sql, $conditions, $limitfrom, $limitnum);
    }

    /**
     * Get number of certificates for a user.
     *
     * @param int $userid
     * @return int
     */
    public static function count_issues_for_user(int $userid = 0): int {
        global $DB;

        $sql = "SELECT COUNT(*)
                  FROM {tool_certificate_templates} t
            INNER JOIN {tool_certificate_issues} ci
                    ON t.id = ci.templateid";

        $params = [];
        if ($userid > 0) {
            $sql .= " WHERE ci.userid = :userid";
            $params['userid'] = $userid;
        }
        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Get the certificates issues for the given userid.
     *
     * @param int $userid
     * @param int $limitfrom
     * @param int $limitnum
     * @param string $sort
     * @return array
     */
    public static function get_issues_for_user($userid, $limitfrom, $limitnum, $sort = '') {
        global $DB;

        if (empty($sort)) {
            $sort = 'ci.timecreated DESC';
        }

        $sql = "SELECT ci.id, ci.expires, ci.code, ci.timecreated, ci.userid,
                       t.id as templateid, t.contextid, t.name
                  FROM {tool_certificate_templates} t
            INNER JOIN {tool_certificate_issues} ci
                    ON t.id = ci.templateid
                 WHERE ci.userid = :userid
              ORDER BY {$sort}";
            return $DB->get_records_sql($sql, array('userid' => $userid), $limitfrom, $limitnum);
    }

    /**
     * Generates a 10-digit code of random letters and numbers.
     *
     * @return string
     */
    public static function generate_code() {
        global $DB;

        $uniquecodefound = false;
        $code = random_string(10);
        while (!$uniquecodefound) {
            if (!$DB->record_exists('tool_certificate_issues', array('code' => $code))) {
                $uniquecodefound = true;
            } else {
                $code = random_string(10);
            }
        }

        return $code;
    }

    /**
     * Verify if a certificate exists given a code
     *
     * @param string $code The code to verify
     * @return \stdClass An structure with success bool attribute and the issue, if found
     */
    public static function verify($code) {
        global $DB;

        $result = new \stdClass();

        $conditions = ['code' => $code];

        if (\tool_certificate\template::can_issue_or_manage_all_tenants() ||
                \tool_certificate\template::can_verify_for_all_tenants()) {
            $tenantjoin = '';
            $tenantwhere = ' u.deleted = 0';
        } else {
            list($tenantjoin, $tenantwhere, $tenantparams) = \tool_tenant\tenancy::get_users_sql();
            $conditions = array_merge($conditions, $tenantparams);
        }

        $userfields = get_all_user_name_fields(true, 'u');

        $sql = "SELECT ci.id, ci.templateid, ci.code, ci.emailed, ci.timecreated,
                       ci.expires, ci.data, ci.component,
                       u.id as userid, {$userfields},
                       t.name as certificatename,
                       t.contextid
                  FROM {tool_certificate_templates} t
                  JOIN {tool_certificate_issues} ci
                    ON t.id = ci.templateid
                  JOIN {user} u
                    ON ci.userid = u.id
                       {$tenantjoin}
                 WHERE ci.code = :code
                   AND {$tenantwhere}";

        $result->success = false;
        if ($issue = $DB->get_record_sql($sql, $conditions)) {
            $template = \tool_certificate\template::instance($issue->templateid);
            if ($template->can_verify()) {
                $result->success = true;
                $result->issue = $issue;
                \tool_certificate\event\certificate_verified::create_from_issue($issue)->trigger();
            }
        }
        return $result;
    }
}
