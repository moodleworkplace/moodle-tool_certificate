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

namespace tool_certificate\reportbuilder\local\entities;

use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\helpers\format;
use tool_certificate\reportbuilder\local\formatters\certificate as formatter;
use tool_certificate\template as certificate_template;

/**
 * Certificate template entity class implementation
 *
 * @package   tool_certificate
 * @copyright 2019 Moodle Pty Ltd <support@moodle.com>
 * @author    2022 Carlos Castillo <carlos.castillo@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template extends base {

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return [
            'tool_certificate_templates' => 'tct',
        ];
    }

    /**
     * Database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return array_keys($this->get_default_table_aliases());
    }

    /**
     * The default title for this entity in the list of columns/conditions/filters in the report builder
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('entitycertificate', 'tool_certificate');
    }

    /**
     * Initialise the entity
     *
     * @return base
     */
    public function initialise(): base {

        foreach ($this->get_all_columns() as $column) {
            $this->add_column($column);
        }

        foreach ($this->get_all_filters() as $filter) {
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }

        return $this;
    }

    /**
     * Returns list of all available columns
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        global $DB;
        $columns = [];
        $certificatetempalias = $this->get_table_alias('tool_certificate_templates');

        // Column name.
        $columns[] = (new column(
            'name',
            new lang_string('certificatetemplate', 'tool_certificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$certificatetempalias}.name")
            ->add_callback([formatter::class, 'format_string']);

        // Column numberofpages.
        $sql = "(SELECT COUNT(id) FROM {tool_certificate_pages} WHERE templateid = {$certificatetempalias}.id)";
        $numberofpagecolumn = (new column(
            'numberofpages',
            new lang_string('numberofpages', 'tool_certificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field($sql, 'numberofpages')
            ->set_groupby_sql("{$certificatetempalias}.id");

        if ($DB->get_dbfamily() === 'mssql') {
            $numberofpagecolumn->set_disabled_aggregation_all();
        }

        $columns[] = $numberofpagecolumn;

        // Column timecreated.
        $columns[] = (new column(
            'timecreated',
            new lang_string('timecreated', 'tool_certificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_field("{$certificatetempalias}.timecreated")
            ->add_callback([format::class, 'userdate']);

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $filters = [];

        $certificatetempalias = $this->get_table_alias('tool_certificate_templates');

        // Filter template timecreated.
        $filters[] = (new filter(
            date::class,
            'timecreated',
            new lang_string('timecreated', 'tool_certificate'),
            $this->get_entity_name(),
            "{$certificatetempalias}.timecreated"
        ))
            ->add_joins($this->get_joins());

        // Filter template name.
        $filters[] = (new filter(
            text::class,
            'name',
            new lang_string('certificatetemplatename', 'tool_certificate'),
            $this->get_entity_name(),
            "{$certificatetempalias}.name"
        ))
            ->add_joins($this->get_joins());

        // Filter templateselector.
        $filters[] = (new filter(
            select::class,
            'templateselector',
            new lang_string('certificatetemplate', 'tool_certificate'),
            $this->get_entity_name(),
            "{$certificatetempalias}.id"
        ))
            ->add_joins($this->get_joins())
            ->set_options_callback([certificate_template::class, 'get_visible_templates_list']);

        return $filters;
    }
}
