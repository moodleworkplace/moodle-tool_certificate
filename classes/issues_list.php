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
 * Class issues_list
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

use tool_reportbuilder\report_column;
use tool_reportbuilder\system_report;

defined('MOODLE_INTERNAL') || die();

/**
 * Class issues_list
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issues_list extends system_report {

    /** @var \tool_certificate\template */
    protected $template;

    /**
     * Initialise the report
     */
    protected function initialise() {
        $templateid = $this->get_parameter('templateid', 0, PARAM_INT);
        $this->template = \tool_certificate\template::find_by_id($templateid);
        if (!$this->template->can_view_issues()) {
            print_error('issueormanagenotallowed', 'tool_certificate');
        }
        parent::initialise();
        $this->set_main_table('tool_certificate_issues', 'i');
        $this->set_main_filter('templateid', $this->template->get_id());
    }

    /**
     * Columns definitions
     */
    protected function set_columns() {
        // Column "fullname".
        $newcolumn = new report_column(
            'fullname',
            get_string('fullname'),
            'tool_certificate_issues',
            'tool_certificate',
            1,
            'INNER JOIN {user} u ON u.id = i.userid',
            array("'fullname' AS fullname, "  . get_all_user_name_fields(true, 'u'), ''),
            null,
            true,
            false,
            false
        );
        $newcolumn->add_callback([\tool_reportbuilder\local\helpers\format::class, 'fullname']);
        $this->add_column($newcolumn);

        // Column "awarded".
        $newcolumn = new report_column(
            'timecreated',
            get_string('receiveddate', 'tool_certificate'),
            'tool_certificate_issues',
            'tool_certificate',
            1,
            '',
            array('i.timecreated', ''),
            null,
            true,
            false,
            true
        );
        $newcolumn->add_callback([\tool_reportbuilder\local\helpers\format::class, 'userdate']);
        $this->add_column($newcolumn);

        // Column "expires".
        $newcolumn = new report_column(
            'expires',
            get_string('expires', 'tool_certificate'),
            'tool_certificate_issues',
            'tool_certificate',
            1,
            '',
            array('i.expires', ''),
            null,
            true,
            false,
            true
        );
        $newcolumn->add_callback([$this, 'col_expires']);
        $this->add_column($newcolumn);

        // Column "code".
        $newcolumn = new report_column(
            'code',
            get_string('code', 'tool_certificate'),
            'tool_certificate_issues',
            'tool_certificate',
            1,
            '',
            array('i.code', ''),
            null,
            true,
            false,
            true
        );
        $newcolumn->add_callback([$this, 'col_code']);
        $this->add_column($newcolumn);

        // Column "file".
        // TODO this column should not be added if we download report.
        $newcolumn = new report_column(
            'download',
            get_string('file'),
            'tool_certificate_issues',
            'tool_certificate',
            1,
            '',
            array('i.code', ''),
            'download',
            true,
            false,
            true
        );
        $newcolumn->add_callback([$this, 'col_download']);
        $this->add_column($newcolumn);

        if ($this->template->can_issue()) {
            $newcolumn = new report_column(
                'revoke',
                get_string('revoke', 'tool_certificate'),
                'tool_certificate_issues',
                'tool_certificate',
                1,
                '',
                array('i.id', ''),
                'revoke',
                true,
                false,
                true
            );
            $newcolumn->add_callback([$this, 'col_revoke']);
            $this->add_column($newcolumn);
        }

    }

    /**
     * Report name
     * @return string
     */
    public static function get_name() {
        return get_string('certificates', 'tool_certificate');
    }

    /**
     * Generate the certificate expires column.
     *
     * @param int $expires
     * @return string
     */
    public function col_expires($expires) {
        if (!$expires) {
            return get_string('never');
        }
        $column = userdate($expires);
        if ($expires && $expires <= time()) {
            $column .= \html_writer::tag('span', get_string('expired', 'tool_certificate'),
                ['class' => 'badge badge-secondary']);
        }
        return $column;
    }

    /**
     * Generate the code column.
     *
     * @param string $code
     * @return string
     */
    public function col_code($code) {
        return \html_writer::link(new \moodle_url('/admin/tool/certificate/index.php', ['code' => $code]),
            $code, ['title' => get_string('verify', 'tool_certificate')]);
    }

    /**
     * Generate the download column.
     *
     * @param string $code
     * @return string
     */
    public function col_download($code) {
        global $OUTPUT;

        // TODO is this correct icon for download?
        $icon = new \pix_icon('a/wp-search', get_string('view'), 'theme');
        $link = template::view_url($code);

        return $OUTPUT->action_link($link, '', null, null, $icon);
    }

    /**
     * Generate the revoke column.
     *
     * @param int $id
     * @return string
     */
    public function col_revoke($id) {
        global $OUTPUT;

        $icon = new \pix_icon('a/wp-trash', get_string('revoke', 'tool_certificate'), 'theme');
        $link = new \moodle_url('/admin/tool/certificate/certificates.php',
            ['issueid' => $id, 'sesskey' => sesskey(), 'revokecert' => '1']);

        return $OUTPUT->action_link($link, '', null, ['class' => 'delete-icon'], $icon);
    }

}
