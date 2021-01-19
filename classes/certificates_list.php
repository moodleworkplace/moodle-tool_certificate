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
 * Class certificates_list
 *
 * @package     tool_certificate
 * @copyright   2020 Mikel Martín <mikel@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

use tool_certificate\output\renderer;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Class certificates_list
 *
 * @package     tool_certificate
 * @copyright   2020 Mikel Martín <mikel@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificates_list extends \table_sql {
    /** @var string */
    protected $downloadparamname = 'download';
    /** @var bool */
    protected $canchangecategory = false;
    /** @var array */
    protected $cateogriescontextssql = [];

    /**
     * Sets up the table.
     */
    public function __construct() {
        parent::__construct('tool-certificate-templates');
        $this->attributes['class'] = 'tool-certificate-templates';

        $targetcategories = \core_course_category::make_categories_list('tool/certificate:manage');
        $this->canchangecategory = count($targetcategories) > 1;
        $this->cateogriescontextssql = template::get_visible_categories_contexts_sql();

        $columnsheaders = [
            'name' => get_string('name', 'tool_certificate'),
            'coursecategory' => get_string('coursecategory'),
        ];

        $filename = format_string('tool-certificate-templates');
        $this->is_downloading(optional_param($this->downloadparamname, 0, PARAM_ALPHA),
            $filename, get_string('certificatetemplates', 'tool_certificate'));

        if (!$this->is_downloading()) {
            $columnsheaders += ['actions' => \html_writer::span(get_string('actions'), 'sr-only')];
        }
        $this->define_columns(array_keys($columnsheaders));
        $this->define_headers(array_values($columnsheaders));

        $this->collapsible(false);
        $this->sortable(true, 'name');
        $this->no_sorting('actions');
        $this->pageable(true);
        $this->is_downloadable(true);
        $this->show_download_buttons_at([TABLE_P_BOTTOM]);

        $this->column_class('actions', 'text-right');
    }

    /**
     * Generate the name column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_name($row) {
        global $PAGE;
        $template = template::instance(0, $row);

        $sharedtag = '';
        if ($template->get_shared()) {
            $sharedtag = \html_writer::tag('span', get_string('shared', 'tool_certificate'),
                ['class' => 'badge badge-secondary ml-1']);
        }

        if (!$this->is_downloading()) {
            $renderer = $PAGE->get_renderer('tool_certificate');
            $name = $template->get_editable_name()->render($renderer) . $sharedtag;
        } else {
            if ($this->export_class_instance()->supports_html()) {
                $url = $template->edit_url();
                $name = \html_writer::link($url, $template->get_formatted_name()) . ' ' . $sharedtag;
            } else {
                $name = $template->get_formatted_name();
            }
        }

        return $name;
    }

    /**
     * Generate the course category column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_coursecategory($row) {
        $context = \context::instance_by_id($row->contextid);
        if ($context instanceof \context_system) {
            $catname = get_string('none');
        } else {
            if (!$this->is_downloading() || $this->export_class_instance()->supports_html()) {
                $url = new \moodle_url('/course/index.php', ['categoryid' => $context->instanceid]);
                $name = format_string($row->categoryname, false, ['context' => $context, 'escape' => false]);
                $catname = \html_writer::link($url, $name);
            } else {
                $catname = format_string($row->categoryname, false, ['context' => $context, 'escape' => false]);
            }
        }
        return $catname;
    }

    /**
     * Generate the actions column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_actions($row) {
        global $CFG, $OUTPUT;
        $actions = '';
        $template = template::instance($row->id);

        if ($template->can_manage()) {
            // Edit content.
            $link = new \moodle_url("/admin/tool/certificate/template.php",
                ['id' => $template->get_id()]);
            $icon = new \pix_icon('t/right', get_string('editcontent', 'tool_certificate'), 'core');
            $actions .= $OUTPUT->action_icon($link, $icon, null, []);
            // Edit details.
            $link = new \moodle_url('#');
            $icon = new \pix_icon('i/settings', get_string('editdetails', 'tool_certificate'), 'core');
            $attributes = [
                'data-action' => 'editdetails',
                'data-id' => $template->get_id(),
                'data-name' => $template->get_formatted_name()
            ];
            $actions .= $OUTPUT->action_icon($link, $icon, null, $attributes);
            // Preview.
            $link = new \moodle_url("/admin/tool/certificate/view.php",
                ['preview' => 1, 'templateid' => $template->get_id(), 'code' => 'previewing']);
            $icon = new \pix_icon('i/search', get_string('preview'));
            $actions .= $OUTPUT->action_icon($link, $icon, null, ['target' => '_blank']);
        }
        if ($template->can_view_issues()) {
            // View issues.
            $link = new \moodle_url('/admin/tool/certificate/certificates.php', ['templateid' => $template->get_id()]);
            $icon = new \pix_icon('a/view_list_active', get_string('certificatesissued', 'tool_certificate'), 'core');
            $actions .= $OUTPUT->action_icon($link, $icon, null, []);
        }
        if ($template->can_issue_to_anybody()) {
            // Issue certificate.
            $link = new \moodle_url('#');
            $icon = new \pix_icon('i/enrolusers', get_string('issuenewcertificate', 'tool_certificate'), 'core');
            $attributes = ['data-action' => 'issue', 'data-tid' => $template->get_id()];
            $actions .= $OUTPUT->action_icon($link, $icon, null, $attributes);
        }
        if ($template->can_manage()) {
            // Duplicate.
            $link = new \moodle_url('#');
            $icon = new \pix_icon('e/manage_files', get_string('duplicate'), 'core');
            $attributes = ['data-action' => 'duplicate', 'data-id' => $template->get_id(),
                'data-name' => $template->get_formatted_name(), 'data-selectcategory' => (int)$this->canchangecategory];
            $actions .= $OUTPUT->action_icon($link, $icon, null, $attributes);
            // Delete.
            $link = new \moodle_url('#');
            $icon = new \pix_icon('i/trash', get_string('delete'), 'core');
            $attributes = [
                'data-action' => 'delete',
                'data-id' => $template->get_id(),
                'data-name' => $template->get_formatted_name()
            ];
            $actions .= $OUTPUT->action_icon($link, $icon, null, $attributes);
        }

        return $actions;
    }

    /**
     * Query the reader.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     * @uses \tool_certificate\certificate
     */
    public function query_db($pagesize, $useinitialsbar = false) {
        $this->rawdata = $this->get_templates_for_table();
        $this->pagesize($pagesize, $this->count_templates_for_table());
    }

    /**
     * Download the data.
     *
     * @uses \tool_certificate\certificate
     */
    public function download() {
        \core\session\manager::write_close();
        $total = $this->count_templates_for_table();
        $this->out($total, false);
        exit;
    }

    /**
     * Returns visible templates.
     *
     * @return array
     */
    private function get_templates_for_table() {
        global $DB;

        [$ctxsql, $ctxparams] = $this->cateogriescontextssql;
        $sql = "SELECT c.id, c.name AS name, c.contextid, c.shared, ctx.id AS coursecategory, ctx.path, ctx.depth,
                    ctx.contextlevel, ctx.instanceid, ctx.locked, coursecat.name AS categoryname
                FROM {tool_certificate_templates} c
                JOIN {context} ctx
                ON ctx.id = c.contextid AND $ctxsql
                LEFT JOIN {course_categories} coursecat
                ON coursecat.id = ctx.instanceid
                ORDER BY {$this->get_sql_sort()}";
        if (!$this->is_downloading()) {
            return $DB->get_records_sql($sql, $ctxparams, $this->get_page_start(), $this->get_page_size());
        } else {
            return $DB->get_records_sql($sql, $ctxparams);
        }
    }

    /**
     * Returns visible templates count.
     *
     * @return int
     */
    private function count_templates_for_table() {
        global $DB;

        [$ctxsql, $ctxparams] = $this->cateogriescontextssql;
        $sql = "SELECT COUNT(c.id)
                FROM {tool_certificate_templates} c
                JOIN {context} ctx
                ON ctx.id = c.contextid AND $ctxsql
                LEFT JOIN {course_categories} coursecat
                ON coursecat.id = ctx.instanceid";

        return $DB->count_records_sql($sql, $ctxparams);
    }
}
