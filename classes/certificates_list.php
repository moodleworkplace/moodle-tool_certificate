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
 * Class certificates_list
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

use tool_reportbuilder\report_action;
use tool_reportbuilder\report_column;
use tool_reportbuilder\system_report;
use tool_wp\db;

defined('MOODLE_INTERNAL') || die();

/**
 * Class certificates_list
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificates_list extends system_report {

    /** @var bool */
    protected $canchangecategory = false;

    /**
     * Initialise
     */
    protected function initialise() {
        global $DB;
        $this->set_columns();
        // Set main table. For certificates we want a custom tenant filter, so disable automatic one.
        $this->set_main_table('tool_certificate_templates', 'c', false);
        $context = $this->get_context();
        if ($context->contextlevel == CONTEXT_COURSE) {
            // We are inside the course, only display available contexts (parents).
            list($sql, $params) = $this->get_visible_contexts_for_course_sql();
        } else {
            list($sql, $params) = $this->get_visible_categories_contexts_sql();
            $targetcategories = \core_course_category::make_categories_list('tool/certificate:manage');
            $this->canchangecategory = count($targetcategories) > 1;
        }
        $this->add_base_join("JOIN {context} ctx
            ON ctx.id = c.contextid AND " . $sql, $params);
        $p3 = db::generate_param_name();
        $this->add_base_join("LEFT JOIN {course_categories} coursecat
            ON coursecat.id = ctx.instanceid AND ctx.contextlevel = :{$p3}",
            [$p3 => CONTEXT_COURSECAT]);
        $this->add_base_fields('c.id, c.name, c.contextid');
        $this->set_downloadable(false);
        $this->add_actions();
    }

    /**
     * Get template context
     *
     * @return bool|\context|\context_system
     */
    protected function get_context() {
        $contextid = $this->get_parameter('contextid', 0, PARAM_INT);
        if ($contextid && ($context = \context::instance_by_id($contextid)) && $context->contextlevel == CONTEXT_COURSE) {
            return $context;
        }
        return \context_system::instance();
    }

    /**
     * Subquery for visible contexts for a course
     *
     * @return array
     */
    protected function get_visible_contexts_for_course_sql() {
        global $DB;
        $context = $this->get_context();
        $ids = array_filter($context->get_parent_context_ids(true), function($contextid) {
            return permission::can_view_templates_in_context(\context::instance_by_id($contextid));
        });
        list($sql, $params) = $DB->get_in_or_equal($ids,
            SQL_PARAMS_NAMED, db::generate_param_name(), true, 0);
        return ['ctx.id ' . $sql, $params];
    }

    /**
     * Subquery for visible contexts for a category/system
     *
     * @return array
     */
    protected function get_visible_categories_contexts_sql() {
        global $DB;
        $contextids = permission::get_visible_categories_contexts(false);
        if ($contextids) {
            list($sql, $params) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, db::generate_param_name());
            return ['ctx.id '.$sql, $params];
        } else {
            return ['1=0', []];
        }
    }

    /**
     * Validates access to view this report with the given parameters
     *
     * @return bool
     */
    protected function can_view(): bool {
        $context = $this->get_context();
        if ($context instanceof \context_course) {
            return permission::can_view_templates_in_context($context);
        } else {
            return permission::can_view_admin_tree();
        }
    }

    /**
     * Set columns
     */
    protected function set_columns() {
        $this->annotate_entity('tool_certificate', new \lang_string('entitycertificate', 'tool_certificate'));

        $newcolumn = (new report_column(
            'name',
            new \lang_string('name', 'tool_certificate'),
            'tool_certificate'
        ))
            ->add_fields('c.name, c.id, c.contextid')
            ->set_is_default(true, 1)
            ->set_is_sortable(true, true);
        $newcolumn->add_callback(function($v, $row) {
            global $OUTPUT;
            $t = template::instance(0, $row);
            return $t->get_editable_name()->render($OUTPUT);
        });
        $this->add_column($newcolumn);

        $contextid = $this->get_parameter('contextid', 0, PARAM_INT);
        $iscourselisting = $contextid && \context::instance_by_id($contextid)->contextlevel == CONTEXT_COURSE;

        // Add 'Course category' column (for listings in the system level).
        $newcolumn = (new report_column(
            'coursecatname',
            new \lang_string('coursecategory', ''),
            'tool_certificate'
        ))
            ->set_is_default(true, 3)
            ->set_is_sortable(true)
            ->add_callback([$this, 'col_coursecat_name']);
        $columns = \context_helper::get_preload_record_columns('ctx');
        foreach ($columns as $fieldname => $alias) {
            $newcolumn->add_field($fieldname, $alias);
        }
        $newcolumn->add_field('coursecat.name', 'categoryname');
        $newcolumn->set_is_available(!$iscourselisting);
        $this->add_column($newcolumn);

        // Add 'Context' column (for course listings).
        $newcolumn = (new report_column(
            'contextname',
            new \lang_string('context', 'role'),
            'tool_certificate'
        ))
            ->set_is_default(true, 3)
            ->set_is_sortable(true)
            ->add_callback([$this, 'col_context_name']);
        $columns = \context_helper::get_preload_record_columns('ctx');
        foreach ($columns as $fieldname => $alias) {
            $newcolumn->add_field($fieldname, $alias);
        }
        $newcolumn->add_field('coursecat.name', 'categoryname');
        $newcolumn->set_is_available($iscourselisting);
        $this->add_column($newcolumn);
    }

    /**
     * Name of the report
     *
     * @return string
     */
    public static function get_name() {
        return get_string('managetemplates', 'tool_certificate');
    }

    /**
     * Formatter for the course category name
     *
     * @param mixed $value
     * @param \stdClass $template
     * @return string
     */
    public function col_coursecat_name($value, \stdClass $template) {
        \context_helper::preload_from_record($template);
        $context = \context::instance_by_id($value);
        if ($context instanceof \context_system) {
            return '-';
        } else {
            $url = new \moodle_url('/course/index.php', ['categoryid' => $context->instanceid]);
            $name = format_string($template->categoryname, false, ['context' => $context, 'escape' => false]);
            return \html_writer::link($url, $name);
        }
    }

    /**
     * Formatter for the course category name
     *
     * @param mixed $value
     * @param \stdClass $template
     * @return string
     */
    public function col_context_name($value, \stdClass $template) {
        \context_helper::preload_from_record($template);
        $context = \context::instance_by_id($value);
        if ($context instanceof \context_system) {
            return get_string('coresystem');
        } else if ($context instanceof \context_coursecat) {
            $url = new \moodle_url('/course/index.php', ['categoryid' => $context->instanceid]);
            $name = format_string($template->categoryname, false, ['context' => $context, 'escape' => false]);
            return \html_writer::link($url, get_string('category') . ': ' . $name);
        } else {
            return '';
        }
    }

    /**
     * Actions
     */
    protected function add_actions() {

        // Edit content.
        $editlink = new \moodle_url('/admin/tool/certificate/template.php', array('id' => ':id'));
        $icon = new \pix_icon('t/right', get_string('editcontent', 'tool_certificate'), 'core');
        $this->add_action((new report_action($editlink, $icon, []))
            ->add_callback(function($row) {
                return template::instance(0, $row)->can_manage();
            })
        );

        // Edit details.
        $editlink = new \moodle_url('#');
        $icon = new \pix_icon('i/settings', get_string('editdetails', 'tool_certificate'), 'core');
        $this->add_action(
            (new report_action($editlink, $icon, ['data-action' => 'editdetails', 'data-id' => ':id', 'data-name' => ':name']))
                ->add_callback(function($row) {
                    $t = template::instance(0, $row);
                    $row->name = $t->get_formatted_name();
                    return $t->can_manage();
                })
        );

        // Preview.
        $previewlink = new \moodle_url('/admin/tool/certificate/view.php',
            ['preview' => 1, 'templateid' => ':id', 'code' => 'previewing']);
        $icon = new \pix_icon('i/search', get_string('preview'), 'core');
        $this->add_action((new report_action($previewlink, $icon, []))
            ->add_callback(function($row) {
                return template::instance(0, $row)->can_manage();
            })
        );

        // View issue.
        $issueslink = new \moodle_url('/admin/tool/certificate/certificates.php', array('templateid' => ':id'));
        $issuesstr  = get_string('certificatesissued', 'tool_certificate');
        $icon = new \pix_icon('a/view_list_active', $issuesstr, 'core');
        $this->add_action((new report_action($issueslink, $icon, []))
            ->add_callback(function($row) {
                return template::instance(0, $row)->can_view_issues();
            })
        );

        // Issue.
        $newissuelink = new \moodle_url('#');
        $newissuestr  = get_string('issuenewcertificate', 'tool_certificate');
        $icon = new \pix_icon('i/enrolusers', $newissuestr, 'core');
        $this->add_action((new report_action($newissuelink, $icon, ['data-action' => 'issue', 'data-tid' => ':id']))
            ->add_callback(function($row) {
                return template::instance(0, $row)->can_issue_to_anybody();
            })
        );

        // Duplicate.
        $icon = new \pix_icon('e/manage_files', get_string('duplicate'), 'core');
        $this->add_action((new report_action(new \moodle_url('#'), $icon, ['data-action' => 'duplicate',
                'data-id' => ':id', 'data-name' => ':name', 'data-selectcategory' => (int)$this->canchangecategory]))
            ->add_callback(function($row) {
                $t = template::instance(0, $row);
                $row->name = $t->get_formatted_name();
                return $t->can_manage();
            })
        );

        // Delete.
        $icon = new \pix_icon('i/trash', get_string('delete'), 'core');
        $this->add_action((new report_action(new \moodle_url('#'), $icon,
                ['data-action' => 'delete', 'data-id' => ':id', 'data-name' => ':name']))
            ->add_callback(function($row) {
                $t = template::instance(0, $row);
                $row->name = $t->get_formatted_name();
                return $t->can_manage();
            })
        );

    }
}
