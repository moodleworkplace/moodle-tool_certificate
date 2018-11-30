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
     * Return the list of possible fonts to use.
     */
    public static function get_fonts() {
        global $CFG;

        require_once($CFG->libdir . '/pdflib.php');

        $arrfonts = [];
        $pdf = new \pdf();
        $fontfamilies = $pdf->get_font_families();
        foreach ($fontfamilies as $fontfamily => $fontstyles) {
            foreach ($fontstyles as $fontstyle) {
                $fontstyle = strtolower($fontstyle);
                if ($fontstyle == 'r') {
                    $filenamewoextension = $fontfamily;
                } else {
                    $filenamewoextension = $fontfamily . $fontstyle;
                }
                $fullpath = \TCPDF_FONTS::_getfontpath() . $filenamewoextension;
                // Set the name of the font to null, the include next should then set this
                // value, if it is not set then the file does not include the necessary data.
                $name = null;
                // Some files include a display name, the include next should then set this
                // value if it is present, if not then $name is used to create the display name.
                $displayname = null;
                // Some of the TCPDF files include files that are not present, so we have to
                // suppress warnings, this is the TCPDF libraries fault, grrr.
                @include($fullpath . '.php');
                // If no $name variable in file, skip it.
                if (is_null($name)) {
                    continue;
                }
                // Check if there is no display name to use.
                if (is_null($displayname)) {
                    // Format the font name, so "FontName-Style" becomes "Font Name - Style".
                    $displayname = preg_replace("/([a-z])([A-Z])/", "$1 $2", $name);
                    $displayname = preg_replace("/([a-zA-Z])-([a-zA-Z])/", "$1 - $2", $displayname);
                }

                $arrfonts[$filenamewoextension] = $displayname;
            }
        }
        ksort($arrfonts);

        return $arrfonts;
    }

    /**
     * Return the list of possible font sizes to use.
     */
    public static function get_font_sizes() {
        // Array to store the sizes.
        $sizes = array();

        for ($i = 1; $i <= 200; $i++) {
            $sizes[$i] = $i;
        }

        return $sizes;
    }

    /**
     * Returns the total number of issues for a given template.
     *
     * @param int $templateid
     * @return int the number of issues
     */
    public static function get_number_of_issues_for_template($templateid) {
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

        $sql = "SELECT ci.id, ci.code, ci.emailed, ci.timecreated, ci.userid, ci.templateid, ci.expires,
                       t.name, " .
                       get_all_user_name_fields(true, 'u') . "
                  FROM {tool_certificate_templates} t
                  JOIN {tool_certificate_issues} ci
                    ON (ci.templateid = t.id)
                  JOIN {user} u
                    ON (u.id = ci.userid)
                 WHERE u.deleted = 0
                   AND t.id = :templateid
              ORDER BY :sort";

        $conditions = ['templateid' => $templateid, 'sort' => $sort];

        return $DB->get_records_sql($sql, $conditions, $limitfrom, $limitnum);
    }

    /**
     * Get number of certificates for a user.
     *
     * @param int $userid
     * @return int
     */
    public static function get_number_of_certificates_for_user(int $userid = 0): int {
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
     * Gets the certificates for the user.
     *
     * @param int $userid
     * @param int $limitfrom
     * @param int $limitnum
     * @param string $sort
     * @return array
     */
    public static function get_certificates_for_user($userid, $limitfrom, $limitnum, $sort = '') {
        global $DB;

        if (empty($sort)) {
            $sort = 'ci.timecreated DESC';
        }

        $sql = "SELECT ci.id, ci.expires, ci.code, ci.timecreated,
                       t.id as templateid, t.contextid, t.name
                  FROM {tool_certificate_templates} t
            INNER JOIN {tool_certificate_issues} ci
                    ON t.id = ci.templateid
                 WHERE ci.userid = :userid
              ORDER BY $sort";
            return $DB->get_records_sql($sql, array('userid' => $userid), $limitfrom, $limitnum);
    }

    /**
     * Issues a certificate to a user.
     *
     * @param int $templateid The ID of the template
     * @param int $userid The ID of the user to issue the certificate to
     * @param int $expires The timestamp when the certificate will expiry. Null if do not expires.
     * @param array $data Additional data that will json_encode'd and stored with the issue.
     * @param string $component The component the certificate was issued by.
     * @return int The ID of the issue
     */
    public static function issue_certificate($templateid, $userid, $expires = null, $data = [], $component = 'tool_certificate') {
        global $DB;

        $issue = new \stdClass();
        $issue->userid = $userid;
        $issue->templateid = $templateid;
        $issue->code = self::generate_code();
        $issue->emailed = 0;
        $issue->timecreated = time();
        $issue->expires = $expires;
        $issue->data = json_encode($data);
        $issue->component = $component;

        // Insert the record into the database.
        if ($issue->id = $DB->insert_record('tool_certificate_issues', $issue)) {
            \tool_certificate\event\certificate_issued::create_from_issue($issue)->trigger();
        }

        return $issue->id;
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
     * Returns the \context_module of a given certificate
     *
     * @param int $templateid
     * @return \context_module
     */
    public static function get_context($templateid) {
        return \context_system::instance();
    }

    /**
     * Deletes an issue of a certificate for a user.
     *
     * @param int $issueid
     */
    public static function revoke_issue($issueid) {
        global $DB;
        $issue = $DB->get_record('tool_certificate_issues', ['id' => $issueid]);
        $DB->delete_records('tool_certificate_issues', ['id' => $issueid]);
        \tool_certificate\event\certificate_revoked::create_from_issue($issue)->trigger();
    }

    /**
     * Deletes issues of a templateid. Used when deleting a template.
     *
     * @param int $templateid
     */
    public static function revoke_issues_by_templateid($templateid) {
        global $DB;
        $issues = $DB->get_records('tool_certificate_issues', ['templateid' => $templateid]);
        $DB->delete_records('tool_certificate_issues', ['templateid' => $templateid]);
        foreach ($issues as $issue) {
            \tool_certificate\event\certificate_revoked::create_from_issue($issue)->trigger();
        }
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
        $result->issues = array();

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
                 WHERE ci.code = :code
                   AND u.deleted = 0";

        // It is possible (though unlikely) that there is the same code for issued certificates.
        if ($issues = $DB->get_records_sql($sql, ['code' => $code])) {
            $result->success = true;
            $result->issues = $issues;
            foreach ($result->issues as $issue) {
                \tool_certificate\event\certificate_verified::create_from_issue($issue)->trigger();
            }
        } else {
            // Can't find it, let's say it's not verified.
            $result->success = false;
        }
        return $result;
    }
}
