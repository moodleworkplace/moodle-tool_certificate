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
 * Class issues_list
 *
 * @package     tool_certificate
 * @copyright   2020 Mikel Martín <mikel@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/tablelib.php');

/**
 * Class issues_list
 *
 * @package     tool_certificate
 * @copyright   2020 Mikel Martín <mikel@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issues_list extends \table_sql {
    /** @var string */
    protected $downloadparamname = 'download';
    /** @var template */
    protected $template;
    /** @var string[] The list of countries. */
    protected $countries;

    /**
     * Sets up the table.
     * @param template $template
     */
    public function __construct(\tool_certificate\template $template) {
        global $CFG;
        parent::__construct('tool-certificate-issues');
        $this->attributes['class'] = 'tool-certificate-issues';

        $this->template = $template;

        $columnsheaders = ['fullname' => get_string('fullname')];

        $context = \context_system::instance();
        if (!empty($CFG->showuseridentity) && has_capability('moodle/site:viewuseridentity', $context)) {
            $columnsheaders += certificate::get_user_extra_field_names($context);

            if (array_key_exists('country', $columnsheaders)) {
                $this->countries = get_string_manager()->get_list_of_countries(true);
            }
        }

        $columnsheaders += [
            'status' => get_string('status'),
            'expires' => get_string('expirydate', 'tool_certificate'),
            'timecreated' => get_string('issueddate', 'tool_certificate'),
            'code' => get_string('code', 'tool_certificate')
        ];

        $filename = format_string('tool-certificate-issues');
        $this->is_downloading(optional_param($this->downloadparamname, 0, PARAM_ALPHA),
            $filename, get_string('certificatesissues', 'tool_certificate'));

        if (!$this->is_downloading()) {
            $columnsheaders += ['actions' => \html_writer::span(get_string('actions'))];
        }
        $this->define_columns(array_keys($columnsheaders));
        $this->define_headers(array_values($columnsheaders));

        $this->collapsible(false);
        $this->sortable(true, 'timecreated', SORT_DESC);
        $this->no_sorting('code');
        $this->no_sorting('actions');
        $this->pagesize = 10;
        $this->pageable(true);
        $this->is_downloadable(true);
        $this->show_download_buttons_at([TABLE_P_BOTTOM]);

        $this->column_class('actions', 'text-right');
    }

    /**
     * Generate the fullname column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_fullname($row): string {
        global $OUTPUT;

        if (!$this->is_downloading()) {
            return $OUTPUT->user_picture($row) . ' ' . fullname($row);
        } else {
            return fullname($row);
        }
    }

    /**
     * Generate the country column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_country(\stdClass $row):string {
        return $this->countries[$row->country] ?? $row->country;
    }

    /**
     * Generate the status column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_status(\stdClass $row): string {
        $status = $row->expires && $row->expires <= time() ? 'expired' : 'valid';
        return get_string($status, 'tool_certificate');
    }

    /**
     * Generate the timecreated column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_timecreated($row) {
        return userdate($row->timecreated, get_string("strftimedatetime", "langconfig"));
    }

    /**
     * Generate the expires column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_expires($row) {
        if (!$row->expires) {
            return get_string('never');
        }
        return userdate($row->expires, get_string("strftimedatetime", "langconfig"));
    }

    /**
     * Generate the code column.
     *
     * @param string $row
     * @return string
     */
    public function col_code($row) {
        if (!$this->is_downloading() || $this->export_class_instance()->supports_html()) {
            $code = \html_writer::link(new \moodle_url('/admin/tool/certificate/index.php', ['code' => $row->code]),
                $row->code, ['title' => get_string('verify', 'tool_certificate')]);
        } else {
            $code = $row->code;
        }
        return $code;
    }

    /**
     * Generate the actions column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_actions($row) {
        global $OUTPUT;
        $actions = '';
        $template = $this->template;

        // View.
        $link = template::view_url($row->code);
        $icon = new \pix_icon('i/search', get_string('view'), 'core');
        $actions .= $OUTPUT->action_icon($link, $icon, null, ['target' => '_blank']);
        if ($template->can_issue($row->userid)) {
            // Regenerate file.
            $link = new \moodle_url('#');
            $icon = new \pix_icon('a/refresh', get_string('regenerateissuefile', 'tool_certificate'), 'core');
            $attributes = ['data-action' => 'regenerate', 'data-id' => $row->issueid];
            $actions .= $OUTPUT->action_icon($link, $icon, null, $attributes);
        }
        if ($template->can_issue($row->userid)) {
            // Revoke.
            $link = new \moodle_url('#');
            $icon = new \pix_icon('i/trash', get_string('revoke', 'tool_certificate'), 'core');
            $attributes = ['data-action' => 'revoke', 'data-id' => $row->issueid];
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
        if (!$this->is_downloading()) {
            $this->rawdata = certificate::get_issues_for_template($this->template->get_id(), $this->get_page_start(),
                $this->get_page_size(), $this->get_sql_sort());
        } else {
            $this->rawdata = certificate::get_issues_for_template($this->template->get_id(), null, null);
        }

        $this->pagesize($pagesize, certificate::count_issues_for_template($this->template->get_id()));
    }

    /**
     * Download the data.
     *
     * @uses \tool_certificate\certificate
     */
    public function download() {
        \core\session\manager::write_close();
        $total = certificate::count_issues_for_template($this->template->get_id());
        $this->out($total, false);
        exit;
    }
}
