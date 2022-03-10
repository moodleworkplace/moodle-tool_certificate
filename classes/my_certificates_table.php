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
 * The report that displays the certificates the user has throughout the site.
 *
 * @package    tool_certificate
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/tablelib.php');

/**
 * Class for the report that displays the certificates the user has throughout the site.
 *
 * @package    tool_certificate
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class my_certificates_table extends \table_sql {

    /**
     * @var int $userid The user id
     */
    protected $userid;

    /**
     * The add to profile LinkedIn URL
     */
    public const LINKEDIN_ADD_TO_PROFILE_URL = 'https://www.linkedin.com/profile/add';

    /**
     * Sets up the table.
     *
     * @param int $userid
     * @param string|null $download The file type, null if we are not downloading
     */
    public function __construct($userid, $download = null) {
        parent::__construct('tool_certificate_my_certificates_table');
        $this->userid = $userid;

        $columns = array(
            'name',
            'timecreated',
            'expires',
        );
        $headers = array(
            get_string('name'),
            get_string('issueddate', 'tool_certificate'),
            get_string('expirydate', 'tool_certificate'),
        );

        if (permission::can_verify()) {
            $columns[] = 'code';
            $headers[] = get_string('code', 'tool_certificate');
        }

        // Check if we were passed a filename, which means we want to download it.
        if ($download) {
            $this->is_downloading($download, 'certificate-report');
        }

        if (!$this->is_downloading()) {
            $columns[] = 'download';
            $headers[] = get_string('file');
        }

        if ($this->show_share_on_linkedin()) {
            $columns[] = 'linkedin';
            $headers[] = get_string('shareonlinkedin', 'tool_certificate');
            $this->no_sorting('linkedin');
        }

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->collapsible(false);
        $this->sortable(true);
        $this->no_sorting('code');
        $this->no_sorting('download');
        $this->is_downloadable(true);
    }

    /**
     * Generate the name column.
     *
     * @param \stdClass $certificate
     * @return string
     */
    public function col_name($certificate) {
        $context = \context::instance_by_id($certificate->contextid);

        $column = format_string($certificate->name, true, ['context' => $context]);

        return $column;
    }

    /**
     * Generate the certificate time created column.
     *
     * @param \stdClass $certificate
     * @return string
     */
    public function col_timecreated($certificate) {
        return userdate($certificate->timecreated);
    }

    /**
     * Generate the certificate expires column.
     *
     * @param \stdClass $certificate
     * @return string
     */
    public function col_expires($certificate) {
        if (!$certificate->expires) {
            return get_string('never');
        }
        $column = userdate($certificate->expires);
        if ($certificate->expires && $certificate->expires <= time()) {
            $column .= \html_writer::tag('span', get_string('expired', 'tool_certificate'), ['class' => 'badge badge-secondary']);
        }
        return $column;
    }

    /**
     * Generate the code column.
     *
     * @param \stdClass $issue
     * @return string
     */
    public function col_code($issue) {
        return \html_writer::link(new \moodle_url('/admin/tool/certificate/index.php', ['code' => $issue->code]),
                                  $issue->code, ['title' => get_string('verify', 'tool_certificate')]);
    }

    /**
     * Generate the download column.
     *
     * @param \stdClass $issue
     * @return string
     */
    public function col_download($issue) {
        global $OUTPUT;

        $icon = new \pix_icon('download', get_string('view'), 'tool_certificate');
        $link = template::view_url($issue->code);

        return $OUTPUT->action_link($link, '', null, null, $icon);
    }

    /**
     * Query the reader.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        $total = certificate::count_issues_for_user($this->userid);

        $this->pagesize($pagesize, $total);

        $this->rawdata = certificate::get_issues_for_user($this->userid, $this->get_page_start(),
            $this->get_page_size(), $this->get_sql_sort());

        // Set initial bars.
        if ($useinitialsbar) {
            $this->initialbars($total > $pagesize);
        }
    }

    /**
     * Download the data.
     */
    public function download() {
        \core\session\manager::write_close();
        $total = certificate::count_issues_for_user($this->userid);
        $this->out($total, false);
        exit;
    }

    /**
     * Generate the LinkedIn column
     *
     * @param \stdClass $issue
     * @return string
     */
    public function col_linkedin($issue) {
        global $OUTPUT;

        $params = [
            'name' => $issue->name,
            'issueYear' => date('Y', $issue->timecreated),
            'issueMonth' => date('m', $issue->timecreated),
            'certId' => $issue->code,
            'certUrl' => template::verification_url($issue->code)
        ];

        if ($issue->expires !== '0') {
            $params['expirationYear'] = date('Y', $issue->expires);
            $params['expirationMonth'] = date('m', $issue->expires);
        }

        $organizationid = get_config('tool_certificate', 'linkedinorganizationid');
        if ($organizationid !== '') {
            $params['organizationId'] = $organizationid;
        }

        $icon = new \pix_icon('linkedin', get_string('shareonlinkedin', 'tool_certificate'), 'tool_certificate');
        $link = new \moodle_url(self::LINKEDIN_ADD_TO_PROFILE_URL, $params);

        return $OUTPUT->action_link($link, '', null, [
            'target' => '_blank',
            'class' => 'd-flex'
        ], $icon);
    }

    /**
     * Whether the LinkedIn column be shown
     *
     * @return bool
     */
    private function show_share_on_linkedin() {
        global $USER;
        return $USER->id == $this->userid && get_config('tool_certificate', 'show_shareonlinkedin');
    }
}
