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
use tool_reportbuilder\local\helpers\columns;
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
        return ['tool_certificate_templates' => 'tct',
            'course_categories' => 'coursecat'];
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
        $coursecatalias = $this->get_table_alias('course_categories');

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
            ->add_joins($this->get_joins())
            ->add_field("COALESCE($coursecatalias.name, :nonestr)", 'categoryname', ['nonestr' => get_string('none')])
            ->add_aggregation_fields('count', "$coursecatalias.id")
            ->set_groupby_sql("$coursecatalias.id,$coursecatalias.name");
        $columns[] = $newcolumn;

        $str = '<span>{{name}}</span data-category="{{id}}">';
        list($sql, $params) = \tool_reportbuilder\db::sql_string_with_placeholders($str, [
            '{{id}}' => "COALESCE($coursecatalias.id, 0)",
            '{{name}}' => "COALESCE($coursecatalias.name, :nonestr)"
        ]);
        $params += ['nonestr' => get_string('none')];

        $fieldname = 'coursecatnamewithlink';
        $newcolumn = (new report_column(
            $fieldname,
            new \lang_string('coursecategorywithlink', 'tool_certificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_field($sql, $fieldname, $params)
            ->add_aggregation_fields('count', "$coursecatalias.id")
            ->add_callback([$this, 'categoryname_replace_all'])
            ->add_aggregation_callback('groupconcat', [$this, 'categoryname_replace_all'])
            ->add_aggregation_callback('groupconcatdistinct', [$this, 'categoryname_replace_all'])
            ->set_groupby_sql("$coursecatalias.id,$coursecatalias.name");

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

        // Filter Category selector.
        $coursecatalias = $this->get_table_alias('course_categories');
        $filters[] = (new report_filter(
            select::class,
            'coursecategory',
            new \lang_string('coursecategory', ''),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_field_sql("COALESCE($coursecatalias.id, 0)")
            ->set_options_callback(function () {
                return [0 => get_string('none')] + \core_course_category::make_categories_list();
            });

        return $filters;
    }

    /**
     * Formats a category name or a list of comma-separated names to add links
     *
     * @param string $value
     * @param \stdClass $row
     * @return null|string|string[]
     */
    public static function categoryname_replace_all($value, $row) {
        return preg_replace_callback('#<span>([^<]*?)</span data-category="(\d*)">#',
            function($matches) {
                return self::categoryname_replace_one($matches[1], $matches[2]);
            }, $value);
    }

    /**
     * Formats a category name to add link
     *
     * @param string $name
     * @param int $id
     * @return string
     */
    protected static function categoryname_replace_one($name, $id) {
        if (!$id) {
            return get_string('none');
        }
        $url = new \moodle_url('/course/index.php', ['categoryid' => $id]);
        $name = format_string($name, false, ['context' => \context_system::instance(), 'escape' => false]);
        return \html_writer::link($url, $name);
    }
}
