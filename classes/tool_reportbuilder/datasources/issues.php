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
 * Class certificate issues datasource
 *
 * @package   tool_certificate
 * @copyright 2019 Moodle Pty Ltd <support@moodle.com>
 * @author    2019 Daniel Neis Araujo <danielneis@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license   Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */

namespace tool_certificate\tool_reportbuilder\datasources;

use tool_reportbuilder\datasource;
use tool_reportbuilder\local\entities\user;
use tool_reportbuilder\local\helpers\columns;
use tool_reportbuilder\report_column;
use tool_reportbuilder\report_filter;
use tool_certificate\tool_reportbuilder\entities\certificate_template;
use tool_certificate\tool_reportbuilder\entities\certificate_issue;

defined('MOODLE_INTERNAL') || die();

/**
 * Class issues
 *
 * @package   tool_certificate
 * @copyright 2019 Moodle Pty Ltd <support@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license   Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */
class issues extends datasource {

    /**
     * Initialise report
     */
    protected function initialise(): void {
        // Set main table. For certificates we want a custom tenant filter, so disable automatic one.
        $this->set_main_table('tool_certificate_templates', 'tct', false);
        list($sql, $params) = $this->get_visible_categories_contexts_sql();
        $targetcategories = \core_course_category::make_categories_list('tool/certificate:manage');
        $this->canchangecategory = count($targetcategories) > 1;
        $this->add_base_join("JOIN {context} ctx
            ON ctx.id = tct.contextid AND " . $sql, $params);
        $p3 = \tool_wp\db::generate_param_name();
        $this->add_base_join("LEFT JOIN {course_categories} coursecat
            ON coursecat.id = ctx.instanceid AND ctx.contextlevel = :{$p3}",
            [$p3 => CONTEXT_COURSECAT]);
        $this->add_base_join('JOIN {tool_certificate_issues} tci ON tct.id = tci.templateid');
        $this->add_base_join('JOIN {user} u ON tci.userid = u.id');
        list($join, $where, $params) = \tool_tenant\tenancy::get_users_sql('u');
        $this->add_base_join($join);
        $this->add_base_condition_sql($where, $params);

        $this->add_base_condition_simple('u.deleted', 0);

        $this->set_downloadable(true);
        $this->set_columns();
    }

    /**
     * Set the columns available for the report and the definition of each.
     *
     */
    protected function set_columns(): void {
        $this->add_entity(new certificate_template());
        $this->add_entity(new certificate_issue());
        $this->add_entity(new user());

        if ($column = $this->get_column('tool_certificate_template:name')) {
            $column->set_is_default(true, 1);
            $column->set_is_sortable(true, true);
        }
        if ($column = $this->get_column('user:fullnamewithlink')) {
            $column->set_is_default(true, 2);
            $column->set_is_sortable(true, true);
        }
        if ($column = $this->get_column('tool_certificate_issue:timecreated')) {
            $column->set_is_default(true, 3);
            $column->set_is_sortable(true, true);
        }
        if ($column = $this->get_column('tool_certificate_issue:expires')) {
            $column->set_is_default(true, 4);
            $column->set_is_sortable(true, true);
        }
        if ($column = $this->get_column('tool_certificate_issue:codewithlink')) {
            $column->set_is_default(true, 5);
            $column->set_is_sortable(true, true);
        }

        // Add default filters.
        $filters = $this->get_filters();
        $filters['tool_certificate_template:templateselector']->set_is_default(true, [1]);
        $filters['tool_certificate_issue:timecreated']->set_is_default(true);
        $filters['tool_certificate_issue:expires']->set_is_default(true);
        $filters['user:fullname']->set_is_default(true);
    }

    /**
     * Get the visible name of the report.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('certificatesissues', 'tool_certificate');
    }

    /**
     * Subquery for visible contexts for a category/system
     *
     * @return array
     */
    protected function get_visible_categories_contexts_sql() {
        global $DB;
        $contextids = \tool_certificate\permission::get_visible_categories_contexts(false);
        if ($contextids) {
            list($sql, $params) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, \tool_wp\db::generate_param_name());
            return ['ctx.id '.$sql, $params];
        } else {
            return ['1=0', []];
        }
    }
}
