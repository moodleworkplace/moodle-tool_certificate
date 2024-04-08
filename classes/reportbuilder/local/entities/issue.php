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
use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\helpers\format;
use tool_certificate\reportbuilder\local\filters\status;
use tool_certificate\reportbuilder\local\formatters\certificate as formatter;
use tool_certificate\permission;

/**
 * Certificate issue entity class implementation
 *
 * @package   tool_certificate
 * @copyright 2019 Moodle Pty Ltd <support@moodle.com>
 * @author    2022 Carlos Castillo <carlos.castillo@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issue extends base {

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return ['tool_certificate_issues' => 'tci'];
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
        return new lang_string('entitycertificateissue', 'tool_certificate');
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
        $columns = [];
        $certificateissuealias = $this->get_table_alias('tool_certificate_issues');

        // Column certificate code.
        $columns[] = (new column(
            'code',
            new lang_string('code', 'tool_certificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_is_sortable(true)
            ->set_is_available(permission::can_verify())
            ->add_field("{$certificateissuealias}.code");

        // Column certificate code with link.
        $columns[] = (new column(
            'codewithlink',
            new lang_string('codewithlink', 'tool_certificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_is_sortable(true)
            ->set_is_available(permission::can_verify())
            ->add_field("{$certificateissuealias}.code")
            ->add_callback([formatter::class, 'code_with_link']);

        // Column certificate issue timecreated.
        $columns[] = (new column(
            'timecreated',
            new lang_string('issueddate', 'tool_certificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_field("{$certificateissuealias}.timecreated")
            ->add_callback([format::class, 'userdate']);

        // Column certificate expires.
        $columns[] = (new column(
            'expires',
            new lang_string('expirydate', 'tool_certificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_field("{$certificateissuealias}.expires")
            ->add_callback([formatter::class, 'certificate_issued_expires']);

        // Column status.
        $columns[] = (new column(
            'status',
            new lang_string('status', 'tool_certificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_is_sortable(true)
            ->add_field("{$certificateissuealias}.expires")
            ->add_callback([formatter::class, 'certificate_issued_status']);

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $filters = [];

        $certificateissuealias = $this->get_table_alias('tool_certificate_issues');

        // Filter issue status.
        $filters[] = (new filter(
            status::class,
            'status',
            new lang_string('status', 'tool_certificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_field_sql("(CASE WHEN ({$certificateissuealias}.expires > 0 AND
                {$certificateissuealias}.expires <= " . time() . ") THEN 1 ELSE 0 END)");

        // Filter issue time created.
        $filters[] = (new filter(
            date::class,
            'timecreated',
            new lang_string('timecreated', 'tool_certificate'),
            $this->get_entity_name(),
            "{$certificateissuealias}.timecreated"
        ))
            ->add_joins($this->get_joins());

        // Filter issue expires.
        $filters[] = (new filter(
            date::class,
            'expires',
            new lang_string('expirydate', 'tool_certificate'),
            $this->get_entity_name(),
            "{$certificateissuealias}.expires"
        ))
            ->add_joins($this->get_joins());

        // Filter archived status.
        $filters[] = (new filter(
            boolean_select::class,
            'archived',
            new lang_string('archived', 'tool_certificate'),
            $this->get_entity_name(),
            "{$certificateissuealias}.archived"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
