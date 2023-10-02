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

namespace tool_certificate\reportbuilder\datasource;

use core_reportbuilder\datasource;
use tool_certificate\certificate;
use tool_certificate\reportbuilder\local\entities\template;
use tool_certificate\reportbuilder\local\formatters\certificate as formatter;

/**
 * Class templates datasource
 *
 * @package   tool_certificate
 * @copyright 2019 Moodle Pty Ltd <support@moodle.com>
 * @author    2019 Daniel Neis Araujo <danielneis@gmail.com>
 * @author    2022 Carlos Castillo <carlos.castillo@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class templates extends datasource {

    /**
     * Initialise report
     */
    protected function initialise(): void {

        // Add certificate template entity.
        $certificatetemplentity = new template();
        $certificatetemplentityname = $certificatetemplentity->get_entity_name();
        $certificatetempl = $certificatetemplentity->get_table_alias('tool_certificate_templates');
        $this->add_entity($certificatetemplentity);

        // Set main table.
        $this->set_main_table('tool_certificate_templates', $certificatetempl);

        // Add course category joins/entity.
        if (class_exists(\core_course\reportbuilder\local\entities\course_category::class)) {
            // Class was renamed in Moodle LMS 4.1.
            $coursecatentity = new \core_course\reportbuilder\local\entities\course_category();
        } else {
            $coursecatentity = new \core_course\local\entities\course_category();
        }
        $coursecatentityname = $coursecatentity->get_entity_name();
        $coursecategories = $coursecatentity->get_table_alias('course_categories');
        $coursecategoryjoins = [
            "JOIN {context} ctx ON ctx.id = {$certificatetempl}.contextid",
            "LEFT JOIN {course_categories} {$coursecategories} ON {$coursecategories}.id = ctx.instanceid",
        ];
        $this->add_entity($coursecatentity
            ->add_joins($coursecategoryjoins));

        // Add report base condition where templates are present and visible to user.
        [$sql, $params] = certificate::get_visible_categories_contexts_sql("{$certificatetempl}.contextid");
        $this->add_base_condition_sql($sql, $params);

        // Add certificate template entity columns/filters/conditions.
        $this->add_columns_from_entity($certificatetemplentityname);
        $this->add_filters_from_entity($certificatetemplentityname);
        $this->add_conditions_from_entity($certificatetemplentityname);

        // Add course category entity columns/filters/conditions.
        $this->add_columns_from_entity($coursecatentityname);
        $this->add_filters_from_entity($coursecatentityname);
        $this->add_conditions_from_entity($coursecatentityname);

        // Change course_category:name/path entity default callback,
        // since in certificate template category isn't mandatory.
        if ($categoryname = $this->get_column('course_category:name')) {
            $categoryname->set_callback([formatter::class, 'course_category_name']);
        }

        if ($categorypath = $this->get_column('course_category:path')) {
            $categorypath->set_callback([formatter::class, 'course_category_path']);
        }
    }

    /**
     * Get the visible name of the report.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('certificatetemplates', 'tool_certificate');
    }

    /**
     * Return the columns that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        return [
            'template:name',
            'course_category:name',
            'template:timecreated',
        ];
    }

    /**
     * Return the filters that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return [
            'template:name',
            'course_category:name',
            'template:timecreated',
        ];
    }

    /**
     * Return the conditions that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [];
    }
}
