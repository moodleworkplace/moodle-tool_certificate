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

namespace tool_certificate\reportbuilder\local\systemreports;

use core\output\inplace_editable;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\report\action;
use core_reportbuilder\system_report;
use html_writer;
use lang_string;
use moodle_url;
use pix_icon;
use stdClass;
use tool_certificate\certificate;
use tool_certificate\permission;
use tool_certificate\persistent\template as templatepersistent;
use tool_certificate\reportbuilder\local\entities\template;
use tool_certificate\reportbuilder\local\formatters\certificate as certificateformatter;

/**
 * Certificates system report implementation
 *
 * @package   tool_certificate
 * @copyright 2022 Moodle Pty Ltd <support@moodle.com>
 * @author    2022 Odei Alba <odei.alba@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class templates extends system_report {

    /** @var templatepersistent */
    protected $lasttemplate;
    /** @var bool */
    protected $canchangecategory;

    /**
     * Initialise report, we need to set the main table, load our entities and set columns/filters
     */
    protected function initialise(): void {
        // Our main entity, it contains all of the column definitions that we need.
        $entitymain = new template();
        $entitymainalias = $entitymain->get_table_alias('tool_certificate_templates');

        $this->set_main_table('tool_certificate_templates', $entitymainalias);
        $this->add_entity($entitymain);

        // Add categories/tool_certificate_templates joins/entity.
        if (class_exists(\core_course\reportbuilder\local\entities\course_category::class)) {
            // Class was renamed in Moodle LMS 4.1.
            $coursecatentity = new \core_course\reportbuilder\local\entities\course_category();
        } else {
            $coursecatentity = new \core_course\local\entities\course_category();
        }
        $coursecatentityalias = $coursecatentity->get_table_alias('course_categories');
        $contextalias = database::generate_alias();

        $this->add_join("JOIN {context} {$contextalias} ON {$contextalias}.id = {$entitymainalias}.contextid");
        $this->add_join("LEFT JOIN {course_categories} {$coursecatentityalias}
            ON {$coursecatentityalias}.id = {$contextalias}.instanceid");

        $this->add_entity($coursecatentity);

        // Any columns required by actions should be defined here to ensure they're always available.
        $this->add_base_fields("{$entitymainalias}." . implode(", {$entitymainalias}.",
                array_diff(array_keys(templatepersistent::properties_definition()), ['usermodified'])));

        // Add report base condition where templates are present and visible to user.
        [$sql, $params] = certificate::get_visible_categories_contexts_sql("{$entitymainalias}.contextid");
        $this->add_base_condition_sql($sql, $params);

        $targetcategories = \core_course_category::make_categories_list('tool/certificate:manage');
        $this->canchangecategory = count($targetcategories) > 1;

        $this->add_columns($entitymainalias);
        $this->add_filters();
        $this->add_actions();

        $this->set_downloadable(true);
        $this->set_initial_sort_column('template:name', SORT_ASC);
    }

    /**
     * Validates access to view this report
     *
     * @return bool
     */
    protected function can_view(): bool {
        return permission::can_view_admin_tree();
    }

    /**
     * Adds the columns we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     *
     * @param string $entitymainalias
     */
    public function add_columns(string $entitymainalias): void {
        $columns = [
            'template:name',
            'course_category:name',
        ];

        $this->add_columns_from_entities($columns);

        // Add inplaceeditable, visible icon and shared badge (if needed) to certificate name column.
        if ($column = $this->get_column('template:name')) {
            $column
                ->set_title(new lang_string('name', 'tool_certificate'))
                ->set_callback([$this, 'nameeditable'])
                ->add_field("{$entitymainalias}.shared")
                ->add_callback([certificateformatter::class, 'append_shared_badge']);
        }

        // Add link to category name column.
        if ($column = $this->get_column('course_category:name')) {
            $column
                ->set_title(new lang_string('coursecategory'))
                ->set_callback([certificateformatter::class, 'course_category_name'])
                ->add_callback([$this, 'coursecategoryname']);
        }
    }

    /**
     * Adds the filters we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_filters(): void {
        $filters = [
            'template:name',
            'course_category:name',
        ];

        $this->add_filters_from_entities($filters);
    }

    /**
     * Add the system report actions. An extra column will be appended to each row, containing all actions added here
     *
     * Note the use of ":id" placeholder which will be substituted according to actual values in the row
     */
    protected function add_actions(): void {
        // Preview.
        $this->add_action((new action(
            new moodle_url('/admin/tool/certificate/view.php', ['templateid' => ':id', 'preview' => 1, 'code' => 'previewing']),
            new pix_icon('i/search', ''),
            [
                'target' => '_blank',
            ],
            false,
            new lang_string('preview')
        ))->add_callback(function() {
            return $this->lasttemplate->can_manage();
        }));

        // Issue certificate.
        $this->add_action((new action(
            new moodle_url('#'),
            new pix_icon('i/enrolusers', ''),
            [
                'data-action' => 'issue',
                'data-tid' => ':id',
            ],
            false,
            new lang_string('issuecertificates', 'tool_certificate')
        ))->add_callback(function() {
            return $this->lasttemplate->can_issue_to_anybody();
        }));

        // Duplicate.
        $this->add_action((new action(
            new moodle_url('#'),
            new pix_icon('t/copy', ''),
            [
                'data-action' => 'duplicate',
                'data-id' => ':id',
                'data-name' => ':name',
                'data-selectcategory' => (int) $this->canchangecategory,
            ],
            false,
            new lang_string('duplicate')
        ))->add_callback(function() {
            return $this->lasttemplate->can_manage();
        }));

        // Delete.
        $this->add_action((new action(
            new moodle_url('#'),
            new pix_icon('i/trash', ''),
            [
                'data-action' => 'delete',
                'data-id' => ':id',
                'data-name' => ':name',
            ],
            false,
            new lang_string('delete')
        ))->add_callback(function() {
            return $this->lasttemplate->can_manage();
        }));
    }

    /**
     * Remembers the current certificate template
     *
     * @param stdClass $row
     */
    public function row_callback(stdClass $row): void {
        $this->lasttemplate = new templatepersistent(0, $row);
    }

    /**
     * Column name name with inplace editable.
     *
     * @return string
     */
    public function nameeditable(): string {
        global $PAGE;
        $template = $this->lasttemplate;

        $name = $template->get_formatted_name();
        if ($template->can_manage()) {
            $name = html_writer::link($template->edit_url(), $name);

            $value = $template->get('name');
            $edithint = get_string('edittemplatename', 'tool_certificate');
            $editlabel = get_string('newvaluefor', 'form', $template->get_formatted_name());

            $inlineeditable = new inplace_editable('tool_certificate', 'templatename',
                $template->get('id'), true, $name, $value, $edithint, $editlabel);

            $name = $inlineeditable->render($PAGE->get_renderer('core'));
        } else if ($template->can_view_issues()) {
            $name = html_writer::link($template->view_issues_url(), $name);
        }

        return $name;
    }

    /**
     * Column category name with link.
     *
     * @param string $catname
     * @param stdClass $category
     * @return string
     */
    public function coursecategoryname(string $catname, stdClass $category): string {
        if (empty($catname) || empty(trim($category->id))) {
            return get_string('none');
        }
        $url = new moodle_url('/course/index.php', ['categoryid' => $category->id]);

        return html_writer::link($url, $catname);
    }
}
