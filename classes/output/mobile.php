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

namespace tool_certificate\output;

use tool_certificate\certificate;
use tool_certificate\template;
use tool_certificate\my_certificates_table;
use tool_certificate\permission;

/**
 * Mobile output class for tool_certificate module.
 *
 * @package     tool_certificate
 * @copyright   2025 Dani Palou <dani@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    /**
     * Returns the data required to display the user's certificates.
     *
     * @param  int $userid
     * @return array Array containing certificates data.
     */
    public static function mobile_my_certificates_data(int $userid): array {

        $certificates = [];
        // Since the mobile app does not support pagination, we aim to restrict the results to a single page.
        $rawdata = certificate::get_issues_for_user($userid, 0, certificate::ISSUES_PER_PAGE, 'timecreated DESC, id DESC');
        foreach ($rawdata as $issue) {
            $context = \context::instance_by_id($issue->contextid);
            $certificates[] = [
                'id' => $issue->id,
                'name' => format_string($issue->name, true, ['context' => $context]),
                'coursename' => format_string($issue->coursename, true, ['context' => $context]),
                'timecreated' => (int)$issue->timecreated,
                'expires' => (int)$issue->expires,
                'isexpired' => (int)$issue->expires && (int)$issue->expires <= time(),
                'code' => $issue->code,
                'verifyurl' => (new \moodle_url('/admin/tool/certificate/index.php', ['code' => $issue->code]))->out(),
                'fileurl' => (new \moodle_url('/admin/tool/certificate/view.php', ['code' => $issue->code]))->out(),
                'shareurl' => self::get_linkedin_url($issue),
            ];
        }

        return [
            'certificates' => $certificates,
            'hascertificates' => !empty($certificates),
            'canverify' => permission::can_verify(),
            'showshareonlinkedin' => self::show_share_on_linkedin($userid),
            'viewmore' => self::view_more_url($certificates, $userid),
        ];
    }

    /**
     * Returns the my certificates view for the mobile app.
     *
     * @param  array $args Arguments from tool_mobile_get_content WS
     * @return array       HTML, javascript and otherdata
     */
    public static function mobile_my_certificates_view(array $args): array {
        global $OUTPUT, $USER;

        $args = (object) $args;

        $userid = $args->userid ?: $USER->id;
        if (!permission::can_view_list((int)$userid)) {
            throw new \required_capability_exception(\context_system::instance(), 'tool/certificate:viewallcertificates',
                'nopermission', 'error');
        }

        $data = self::mobile_my_certificates_data((int) $userid);

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('tool_certificate/mobile_my_certificates_page', $data),
                ],
            ],
            'javascript' => '',
            'otherdata' => '',
            'files' => '',
        ];
    }

    /**
     * The mobile app doesn't support pagination. If there are more certificates than
     * can be displayed, return a URL to view them on the web.
     * Return null if all certificates are already visible.
     *
     * @param  array $certificates The list of certificate issues currently displayed.
     * @param  int $userid The user ID.
     * @return ?string The URL to view more certificates or null if not applicable.
     */
    public static function view_more_url(array $certificates, int $userid): ?string {
        if (count($certificates) < certificate::ISSUES_PER_PAGE) {
            return null;
        }
        $numofcerts = certificate::count_issues_for_user($userid);
        if ($numofcerts > certificate::ISSUES_PER_PAGE) {
            $url = new \moodle_url('/admin/tool/certificate/my.php');
            return $url->out();
        }
        return null;
    }

    /**
     * Returns the info and javascript needed to initialize my certificates in the app.
     *
     * @param  array $args Arguments from tool_mobile_get_content WS
     * @return array       HTML, javascript and otherdata
     */
    public static function mobile_my_certificates_init(array $args): array {
        global $CFG;

        return [
            'templates' => [],
            'javascript' => file_get_contents($CFG->dirroot . '/admin/tool/certificate/mobileapp/js/mycertificates_init.js'),
            'restrict' => [
                'courses' => [SITEID],
            ],
        ];
    }

    /**
     * Determines if the "Share on LinkedIn" button should be shown for a given user.
     *
     * @param int $useid The user ID to check.
     * @return bool True if the button should be shown, false otherwise.
     */
    private static function show_share_on_linkedin(int $useid): bool {
        global $USER;
        return $USER->id == $useid && (bool)get_config('tool_certificate', 'show_shareonlinkedin');
    }

    /**
     * Generates the LinkedIn Add to Profile URL for a given certificate issue.
     *
     * @param \stdClass $issue The certificate issue object.
     * @return string The LinkedIn Add to Profile URL.
     */
    private static function get_linkedin_url(\stdClass $issue): string {
        $params = [
            'name' => $issue->name,
            'issueYear' => date('Y', (int)$issue->timecreated),
            'issueMonth' => date('m', (int)$issue->timecreated),
            'certId' => $issue->code,
            'certUrl' => template::get_shareonlinkedincerturl($issue->code),
        ];

        if ((int)$issue->expires > 0) {
            $params['expirationYear'] = date('Y', (int)$issue->expires);
            $params['expirationMonth'] = date('m', (int)$issue->expires);
        }

        $organizationid = get_config('tool_certificate', 'linkedinorganizationid');
        if ($organizationid !== '') {
            $params['organizationId'] = $organizationid;
        }

        $url = new \moodle_url(my_certificates_table::LINKEDIN_ADD_TO_PROFILE_URL, $params);
        return $url->out(false);
    }
}
