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
 * The table that displays the templates for management.
 *
 * @package    tool_certificate
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/tablelib.php');

/**
 * Class for the table that displays the templates for management.
 *
 * @package    tool_certificate
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_templates_table extends \table_sql {

    /**
     * Sets up the table.
     */
    public function __construct() {
        parent::__construct('tool_certificate_manage_templates_table');

        $columns = ['name', 'actions'];

        $headers = [get_string('name'), ''];

        if (\tool_certificate\template::can_issue_or_manage_all_tenants()) {
            $columns[] = 'tenant';
            $headers[] = get_string('tenant', 'tool_certificate');
        }

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->collapsible(false);
        $this->sortable(true);
    }

    /**
     * Generate the name column.
     *
     * @param \stdClass $template
     * @return string
     */
    public function col_name($template) {
        return $template->name;
    }

    /**
     * Generate the column with name of tenant or indicating the template is shared.
     * @param stdclass $template The template record
     * @return string
     */
    public function col_tenant($template) {
        if ($template->tenantid == 0) {
            return get_string('shared', 'tool_certificate');
        } else {
            return format_string($template->tenantname);
        }
    }

    /**
     * Generate the actions column.
     *
     * @param \stdClass $template
     * @return string
     */
    public function col_actions($template) {
        global $OUTPUT;

        $actions = '';

        $templateobj = new template($template);
        if ($templateobj->can_duplicate()) {
            $duplicatelink = new \moodle_url('/admin/tool/certificate/manage_templates.php',
                array('tid' => $template->id, 'action' => 'duplicate', 'sesskey' => sesskey()));

            $actions .= $OUTPUT->action_icon($duplicatelink, new \pix_icon('t/copy', get_string('duplicate')), null,
                array('class' => 'action-icon duplicate-icon'));

        }
        if ($templateobj->can_manage()) {

            $editlink = new \moodle_url('/admin/tool/certificate/edit.php', array('tid' => $template->id));
            $actions .= $OUTPUT->action_icon($editlink, new \pix_icon('t/edit', get_string('edit')));

            $deletelink = new \moodle_url('/admin/tool/certificate/manage_templates.php',
                array('tid' => $template->id, 'action' => 'delete', 'sesskey' => sesskey()));

            $actions .= $OUTPUT->action_icon($deletelink, new \pix_icon('t/delete', get_string('delete')), null,
                array('class' => 'action-icon delete-icon'));

            $previewlink = $templateobj->preview_url();
            $actions .= $OUTPUT->action_icon($previewlink, new \pix_icon('t/preview', get_string('preview')), null,
                array('class' => 'action-icon preview-icon'));

        }

        $issueslink = new \moodle_url('/admin/tool/certificate/certificates.php', array('templateid' => $template->id));
        $issuesstr  = get_string('certificatesissued', 'tool_certificate');

        $actions .= $OUTPUT->action_icon($issueslink, new \pix_icon('t/viewdetails', $issuesstr));

        if ($templateobj->can_issue()) {
            $newissuelink = new \moodle_url('/admin/tool/certificate/issue.php', array('templateid' => $template->id));
            $newissuestr  = get_string('issuenewcertificate', 'tool_certificate');
            $actions .= $OUTPUT->action_icon($newissuelink, new \pix_icon('t/add', $newissuestr));
        }

        return $actions;
    }

    /**
     * Query the reader.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        $sql = 'SELECT t.*, te.name as tenantname
                  FROM {tool_certificate_templates} t
             LEFT JOIN {tool_tenant} te
                    ON (te.id = t.tenantid)';

        $params = [];

        if (!\tool_certificate\template::can_issue_or_manage_all_tenants()) {
            $sql .= "WHERE te.id = :tenantid
                        OR t.tenantid = 0";
            $params['tenantid'] = \tool_tenant\tenancy::get_tenant_id();
        }
        $total = $DB->count_records('tool_certificate_templates', $params);

        $this->pagesize($pagesize, $total);

        $this->rawdata = $DB->get_records_sql($sql, $params, $this->get_page_start(), $this->get_page_size());

        // Set initial bars.
        if ($useinitialsbar) {
            $this->initialbars($total > $pagesize);
        }
    }
}
