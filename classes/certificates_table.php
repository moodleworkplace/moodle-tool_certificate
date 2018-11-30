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
 * The report that displays the certificates the user has throughout the site.
 *
 * @package    tool_certificate
 * @copyright  2018 Daniel Neis <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/tablelib.php');

/**
 * Class for the report that displays all the certificates for a given templateid.
 *
 * @package    tool_certificate
 * @copyright  2018 Daniel Neis <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificates_table extends \table_sql {

    /**
     * @var int $templateid The user id
     */
    protected $templateid;

    /**
     * Sets up the table.
     *
     * @param int $templateid
     * @param string|null $download The file type, null if we are not downloading
     */
    public function __construct($templateid, $download = null) {
        parent::__construct('tool_certificate_certificates_table');

        $this->templateid = $templateid;
        $this->template = \tool_certificate\template::find_by_id($templateid);

        $columns = array(
            'userfullname',
            'timecreated',
            'expires',
            'code',
        );
        $headers = array(
            get_string('fullname'),
            get_string('receiveddate', 'tool_certificate'),
            get_string('expires', 'tool_certificate'),
            get_string('code', 'tool_certificate'),
        );

        // Check if we were passed a filename, which means we want to download it.
        if ($download) {
            $this->is_downloading($download, 'certificate-report');
        }

        if (!$this->is_downloading()) {
            $columns[] = 'download';
            $headers[] = get_string('file');

            if ($this->template->can_issue()) {
                $columns[] = 'revoke';
                $headers[] = get_string('revoke', 'tool_certificate');
            }
        }

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->collapsible(false);
        $this->sortable(true);
        $this->no_sorting('code');
        $this->no_sorting('download');
        $this->no_sorting('revoke');
        $this->is_downloadable(true);
    }

    /**
     * Generate the name column.
     *
     * @param \stdClass $issue
     * @return string
     */
    public function col_userfullname($issue) {
        return fullname($issue);
    }

    /**
     * Generate the certificate time created column.
     *
     * @param \stdClass $issue
     * @return string
     */
    public function col_timecreated($issue) {
        return userdate($issue->timecreated);
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
     * Generate the revoke column.
     *
     * @param \stdClass $issue
     * @return string
     */
    public function col_revoke($issue) {
        global $OUTPUT;

        $icon = new \pix_icon('t/delete', get_string('revoke', 'tool_certificate'));
        $link = new \moodle_url('/admin/tool/certificate/certificates.php',
            array('issueid' => $issue->id, 'templateid' => $issue->templateid, 'sesskey' => sesskey(), 'revokecert' => '1'));

        return $OUTPUT->action_link($link, '', null, ['class' => 'delete-icon'], $icon);
    }

    /**
     * Query the reader.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        $total = certificate::count_issues_for_template($this->templateid);

        $this->pagesize($pagesize, $total);

        $this->rawdata = certificate::get_issues_for_template($this->templateid, $this->get_page_start(),
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
        $total = certificate::count_issues_for_template($this->templateid);
        $this->out($total, false);
        exit;
    }
}
