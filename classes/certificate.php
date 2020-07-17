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

use tool_wp\db;

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

        $usersquery = self::get_users_subquery();

        $sql = "SELECT ci.id, ci.code, ci.emailed, ci.timecreated, ci.userid, ci.templateid, ci.expires,
                       t.name, " .
                       get_all_user_name_fields(true, 'u') . "
                  FROM {tool_certificate_templates} t
                  JOIN {tool_certificate_issues} ci
                    ON (ci.templateid = t.id)
                  JOIN {user} u
                    ON (u.id = ci.userid)
                 WHERE t.id = :templateid
                   AND {$usersquery}
              ORDER BY {$sort}";

        return $DB->get_records_sql($sql, $conditions, $limitfrom, $limitnum);
    }

    /**
     * Returns the total number of course issues for a given template and course.
     *
     * @param int $templateid
     * @param int $courseid
     * @param string $component
     * @param int|null $groupmode
     * @param int|null $groupid
     * @return int the number of issues
     */
    public static function count_issues_for_course(int $templateid, int $courseid, string $component, ?int $groupmode,
            ?int $groupid) {
        global $DB;

        $params = [
            'templateid' => $templateid,
            'courseid' => $courseid,
            'component' => $component
        ];

        if ($groupmode) {
            [$groupmodequery, $groupmodeparams] = self::get_groupmode_subquery($courseid, $groupmode, $groupid);
            $params += $groupmodeparams;

            $sql = "SELECT COUNT(u.id) as count
                  FROM {user} u
            INNER JOIN {tool_certificate_issues} ci
                    ON u.id = ci.userid
                 WHERE ci.templateid = :templateid
                    AND ci.courseid = :courseid
                    AND ci.component = :component
                    $groupmodequery";

            return $DB->count_records_sql($sql, $params);
        } else {
            return $DB->count_records('tool_certificate_issues', $params);
        }
    }

    /**
     * Get the course certificate issues for a given templateid, courseid, paginated.
     *
     * @param int $templateid
     * @param int $courseid
     * @param string $component
     * @param int|null $groupmode
     * @param int|null $groupid
     * @param int $limitfrom
     * @param int $limitnum
     * @param string $sort
     * @return array
     */
    public static function get_issues_for_course(int $templateid, int $courseid, string $component, ?int $groupmode, ?int $groupid,
            int $limitfrom, int $limitnum, string $sort = ''): array {
        global $DB;

        if (empty($sort)) {
            $sort = 'ci.timecreated DESC';
        }

        $params = ['templateid' => $templateid, 'courseid' => $courseid, 'component' => $component];
        $groupmodequery = '';
        if ($groupmode) {
            [$groupmodequery, $groupmodeparams] = self::get_groupmode_subquery($courseid, $groupmode, $groupid);
            $params += $groupmodeparams;
        }

        $usersquery = self::get_users_subquery();
        $extrafields = get_extra_user_fields(\context_course::instance($courseid));
        $userfields = \user_picture::fields('u', $extrafields);
        $sql = "SELECT ci.id as issueid, ci.code, ci.emailed, ci.timecreated, ci.userid, ci.templateid, ci.expires,
                       t.name, ci.courseid, $userfields
                  FROM {tool_certificate_templates} t
                  JOIN {tool_certificate_issues} ci
                    ON (ci.templateid = t.id) AND (ci.courseid = :courseid) AND (component = :component)
                  JOIN {user} u
                    ON (u.id = ci.userid)
                 WHERE t.id = :templateid
                   AND $usersquery
                   $groupmodequery
              ORDER BY {$sort}";

        return $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
    }

    /**
     * Get groupmode subquery
     *
     * @param int $courseid
     * @param int $groupmode
     * @param int $groupid
     * @return array
     */
    private static function get_groupmode_subquery(int $courseid, int $groupmode, int $groupid) {
        global $DB, $USER;

        $context = \context_course::instance($courseid);
        [$groupmodequery, $groupmodeparams] = ['', []];

        $canaccessallgroups = has_capability('moodle/site:accessallgroups', $context);
        $currentgroup = $groupid;

        if ($groupmode && $currentgroup) {
            if (!$canaccessallgroups) {
                if (isguestuser()) {
                    return ['', []];
                }
                // The user must belong to the group.
                $usersgroups = groups_get_all_groups($courseid, $USER->id);
                if ($usersgroups) {
                    if (!isset($usersgroups[$currentgroup])) {
                        return ['', []];
                    }
                } else {
                    return ['', []];
                }
            }

            $groupusers = array_keys(groups_get_members($currentgroup, 'u.*'));
            if (empty($groupusers)) {
                return ['', []];
            }

            [$sql, $params] = $DB->get_in_or_equal($groupusers, SQL_PARAMS_NAMED, 'grp');
            $groupmodequery .= "AND u.id $sql ";
            $groupmodeparams += $params;
        }
        return [$groupmodequery, $groupmodeparams];
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

        $result = (object)['success' => false];
        if (!$code) {
            return $result;
        }

        $conditions = ['code' => $code];

        $sql = "SELECT ci.id, ci.templateid, ci.code, ci.emailed, ci.timecreated,
                       ci.expires, ci.data, ci.component, ci.courseid,
                       ci.userid,
                       t.name as certificatename,
                       t.contextid
                  FROM {tool_certificate_templates} t
                  JOIN {tool_certificate_issues} ci
                    ON t.id = ci.templateid
                 WHERE ci.code = :code";

        if ($issue = $DB->get_record_sql($sql, $conditions)) {
            $result->success = true;
            $result->issue = $issue;
            \tool_certificate\event\certificate_verified::create_from_issue($issue)->trigger();
        }
        return $result;
    }

    /**
     * Certificates selector.
     *
     * @param string $search
     * @return array
     */
    public static function get_potential_certificates(string $search): array {
        // TODO WP-1212 add tests that teanantadmins can only see their own certificates in the DR outcome.
        global $DB;
        $ids = permission::get_visible_categories_contexts();
        if (!$ids) {
            return [];
        }
        list($sql, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, db::generate_param_name());

        $query = "SELECT *
                    FROM {tool_certificate_templates}
                   WHERE contextid " . $sql;

        $i = 0;
        foreach (preg_split('/ +/', trim($search), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $i++;
            $query .= ' AND (' . $DB->sql_like('name', ":search{$i}1", false, false) . ')';
            $params += ["search{$i}1" => '%' . $word . '%'];
        }

        $result = $DB->get_records_sql($query, $params);

        // We apply format string to the name.
        if (!empty($result)) {
            foreach ($result as $res) {
                $res->name = format_string($res->name, true,
                    ['context' => \context_system::instance(), 'escape' => false]);
            }
        }

        return $result;
    }

    /**
     * Helps to build SQL to retrieve users that can be displayed to the current user
     *
     * If tool_tenant is installed - adds a tenant filter
     *
     * @uses \tool_tenant\tenancy::get_users_subquery
     *
     * @param string $usertablealias
     * @return string
     */
    public static function get_users_subquery(string $usertablealias = 'u') : string {
        return component_class_callback('tool_tenant\\tenancy', 'get_users_subquery',
            [true, false, $usertablealias.'.id'], $usertablealias.'.deleted=0');
    }

    /**
     * Get templates count for course category and its child categories.
     *
     * @param \core_course_category $category
     * @return int
     */
    public static function count_templates_in_category(\core_course_category $category): int {
        global $DB;

        $ctx = $category->get_context();

        $select = "(id = ? OR (".$DB->sql_like('path', '?').")) AND contextlevel = ?";
        $params = [$ctx->id, $ctx->path.'/%', CONTEXT_COURSECAT];
        $contexts = $DB->get_records_select('context', $select, $params);

        list($insql, $inparams) = $DB->get_in_or_equal(array_keys($contexts));
        return \tool_certificate\persistent\template::count_records_select("contextid $insql", $inparams);
    }
}
