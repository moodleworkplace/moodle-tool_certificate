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
 * File for certificate_entity class
 *
 * @package   tool_certificate
 * @copyright 2019 Moodle Pty Ltd <support@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license   Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */

namespace tool_certificate\tool_reportbuilder\entities;

use tool_reportbuilder\constants;
use tool_reportbuilder\entity_base;
use tool_reportbuilder\report_column;
use tool_reportbuilder\report_filter;
use tool_reportbuilder\local\filter\date_condition;
use tool_reportbuilder\local\filter\date_filter;
use tool_reportbuilder\local\filter\text;
use tool_reportbuilder\local\filter\select;
use tool_reportbuilder\local\helpers\format;

defined('MOODLE_INTERNAL') || die();

/**
 * Columns, filters and conditions that defines the certificate template and can be reused in any datasource
 *
 * @package   tool_certificate
 * @copyright 2019 Moodle Pty Ltd <support@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license   Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */
class certificate_template extends entity_base {

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return ['tool_certificate_templates' => 'tct'];
    }

    /**
     * The default machine-readable name for this entity that will be used in the internal names of the columns/filters
     *
     * @return string
     */
    protected function get_default_entity_name(): string {
        return 'tool_certificate_template';
    }

    /**
     * The default title for this entity in the list of columns/conditions/filters in the report builder
     *
     * @return \lang_string
     */
    protected function get_default_entity_title(): \lang_string {
        return new \lang_string('entitycertificate', 'tool_certificate');
    }

    /**
     * Executed when entity is added to the datasource or system report
     */
    public function add_to_report() {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        $conditions = $this->get_filters_or_conditions(true);
        foreach ($conditions as $condition) {
            $this->add_condition($condition);
        }

        $filters = $this->get_filters_or_conditions(false);
        foreach ($filters as $filter) {
            $this->add_filter($filter);
        }
    }

    /**
     * Returns list of all available columns
     *
     * @return report_column[]
     */
    protected function get_all_columns(): array {
        global $DB;
        $columns = [];
        $tablealias = $this->get_table_alias('tool_certificate_templates');

        $newcolumn = (new report_column(
            'name',
            new \lang_string('certificatetemplate', 'tool_certificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(constants::DB_TYPE_TEXT)
            ->add_field("$tablealias.name")
            ->add_callback([format::class, 'format_string']);
        $columns[] = $newcolumn;

        $sql = "(SELECT COUNT(id) FROM {tool_certificate_pages} WHERE templateid = $tablealias.id)";
        $newcolumn = (new report_column(
            'numberofpages',
            new \lang_string('numberofpages', 'tool_certificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(constants::DB_TYPE_NUMBER)
            ->add_field($sql, 'numberofpages')
            ->set_groupby_sql("$tablealias.id");
        if ($DB->get_dbfamily() === 'mssql') {
            columns::disable_column_aggregation($newcolumn);
        }
        $columns[] = $newcolumn;

        // Column timecreated.
        $newcolumn = (new report_column(
            'timecreated',
            new \lang_string('timecreated', 'tool_certificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(constants::DB_TYPE_TIMESTAMP)
            ->add_field("$tablealias.timecreated")
            ->add_callback([format::class, 'userdate'])
            ->add_aggregation_callback('min', [format::class, 'userdate'])
            ->add_aggregation_callback('max', [format::class, 'userdate']);
        $columns[] = $newcolumn;

        $newcolumn = (new report_column(
            'coursecatname',
            new \lang_string('coursecategory', ''),
            $this->get_entity_name()
        ))
            ->add_callback([$this, 'col_coursecat_name']);
        $catcolumns = \context_helper::get_preload_record_columns('ctx');
        foreach ($catcolumns as $fieldname => $alias) {
            $newcolumn->add_field($fieldname, $alias);
        }
        $newcolumn->add_field('coursecat.name', 'categoryname');
        $columns[] = $newcolumn;

        $newcolumn = (new report_column(
            'coursecatnamewithlink',
            new \lang_string('coursecategorywithlink', 'tool_certificate'),
            $this->get_entity_name()
        ))
            ->add_callback([$this, 'col_coursecat_name_with_link']);
        $catcolumns = \context_helper::get_preload_record_columns('ctx');
        foreach ($catcolumns as $fieldname => $alias) {
            $newcolumn->add_field($fieldname, $alias);
        }
        $newcolumn->add_field('coursecat.name', 'categoryname');
        $columns[] = $newcolumn;

        return $columns;
    }

    /**
     * Filters/conditions for certificates.
     *
     * @param bool $iscondition
     * @return array
     */
    protected function get_filters_or_conditions(bool $iscondition): array {
        $filters = [];

        $tablealias = $this->get_table_alias('tool_certificate_templates');

        $filters[] = (new report_filter(
            $iscondition ? date_condition::class : date_filter::class,
            'timecreated',
            new \lang_string('timecreated', 'tool_certificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_field_sql("$tablealias.timecreated");

        // Filter Template name.
        $filters[] = (new report_filter(
            text::class,
            'name',
            new \lang_string('certificatetemplatename', 'tool_certificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_field_sql("$tablealias.name");

        // Filter Template selector.
        $filters[] = (new report_filter(
            select::class,
            'templateselector',
            new \lang_string('certificatetemplate', 'tool_certificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_field_sql("$tablealias.id")
            ->set_options(\tool_certificate\template::get_visible_templates_list());

        $filters[] = (new report_filter(
            select::class,
            'coursecategory',
            new \lang_string('coursecategory', ''),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_field_sql("coursecat.id")
            ->set_options(\core_course_category::make_categories_list());

        return $filters;
    }

    /**
     * Formatter for the course category name
     *
     * @param mixed $value
     * @param \stdClass $template
     * @return string
     */
    public function col_coursecat_name_with_link($value, \stdClass $template) {
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
    public function col_coursecat_name($value, \stdClass $template) {
        \context_helper::preload_from_record($template);
        $context = \context::instance_by_id($value);
        if ($context instanceof \context_system) {
            return '-';
        } else {
            return format_string($template->categoryname, false, ['context' => $context, 'escape' => false]);
        }
    }
}
