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
 * The table that displays the templates in a given context.
 *
 * @package    tool_certificate
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

use tool_tenant\tenancy;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/tablelib.php');

/**
 * Class for the table that displays the templates in a given context.
 *
 * @package    tool_certificate
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_templates_table extends \table_sql {

    /**
     * @var \context $context
     */
    protected $context;

    /**
     * @var bool $canissue
     */
    protected $canissue;

    /**
     * @var bool $canmanage
     */
    protected $canmanage;

    /**
     * Sets up the table.
     *
     * @param \context $context
     */
    public function __construct($context) {
        parent::__construct('tool_certificate_manage_templates_table');

        $columns = [
            'name',
            'actions'
        ];

        $headers = [
            get_string('name'),
            ''
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->collapsible(false);
        $this->sortable(true);

        $this->context = $context;

        $this->canissue = has_capability('tool/certificate:issue', \context_system::instance());
        $this->canmanage = has_capability('tool/certificate:manage', \context_system::instance());
        $this->canmanagealltenants = has_capability('tool/certificate:manageforalltenants', \context_system::instance());
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
     * Generate the actions column.
     *
     * @param \stdClass $template
     * @return string
     */
    public function col_actions($template) {
        global $OUTPUT;

        $actions = '';

        if ($this->canmanagealltenants || ($this->canmanage && $template->tenantid == tenancy::get_tenant_id())) {

            $editlink = new \moodle_url('/admin/tool/certificate/edit.php', array('tid' => $template->id));
            $actions .= $OUTPUT->action_icon($editlink, new \pix_icon('t/edit', get_string('edit')));

            $duplicatelink = new \moodle_url('/admin/tool/certificate/manage_templates.php',
                array('tid' => $template->id, 'action' => 'duplicate', 'sesskey' => sesskey()));

            $actions .= $OUTPUT->action_icon($duplicatelink, new \pix_icon('t/copy', get_string('duplicate')), null,
                array('class' => 'action-icon duplicate-icon'));

            $deletelink = new \moodle_url('/admin/tool/certificate/manage_templates.php',
                array('tid' => $template->id, 'action' => 'delete', 'sesskey' => sesskey()));

            $actions .= $OUTPUT->action_icon($deletelink, new \pix_icon('t/delete', get_string('delete')), null,
                array('class' => 'action-icon delete-icon'));

            $templateobj = new template($template);
            $previewlink = $templateobj->preview_url();
            $actions .= $OUTPUT->action_icon($previewlink, new \pix_icon('t/preview', get_string('preview')), null,
                array('class' => 'action-icon preview-icon'));

        }

        $issueslink = new \moodle_url('/admin/tool/certificate/certificates.php', array('templateid' => $template->id));
        $issuesstr  = get_string('certificatesissued', 'tool_certificate');

        $actions .= $OUTPUT->action_icon($issueslink, new \pix_icon('t/viewdetails', $issuesstr));

        if ($this->canissue) {
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

        $total = $DB->count_records('tool_certificate_templates', array('contextid' => $this->context->id));

        $this->pagesize($pagesize, $total);

        $this->rawdata = $DB->get_records('tool_certificate_templates', array('contextid' => $this->context->id),
            $this->get_sql_sort(), '*', $this->get_page_start(), $this->get_page_size());

        // Set initial bars.
        if ($useinitialsbar) {
            $this->initialbars($total > $pagesize);
        }
    }
}
