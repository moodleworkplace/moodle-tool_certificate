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
 * File containing tests for certificates datasource
 *
 * @package     tool_certificate
 * @copyright   2019 Moodle Pty Ltd <support@moodle.com>
 * @author      2019 Daniel Neis Araujo <danielneis@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_certificate\tool_reportbuilder\datasources\certificates;
use tool_tenant\tenancy;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the datasource certificates
 *
 * @package     tool_certificate
 * @covers      \tool_certificate\tool_reportbuilder\datasources\certificates
 * @copyright   2019 Moodle Pty Ltd <support@moodle.com>
 * @author      2019 Daniel Neis Araujo <danielneis@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_certificate_datasource_certificates_testcase extends advanced_testcase {

    /** @var tool_certificate_generator */
    protected $certgenerator;

    /**
     * Test set up.
     */
    public function setUp() {
        $this->resetAfterTest();
        $this->certgenerator = self::getDataGenerator()->get_plugin_generator('tool_certificate');

        // Create 2 certificates.
        $cert1name = 'Certificate 1';
        $certificate1 = $this->certgenerator->create_template((object)['name' => $cert1name]);
        $cert2name = 'Certificate 2';
        $certificate2 = $this->certgenerator->create_template((object)['name' => $cert2name]);
    }

    /**
     * Create a report
     *
     * @param int $tenantid
     * @param bool $adddefault
     * @return int
     */
    protected function create_report(int $tenantid, bool $adddefault): int {
        return $this->get_reportbuilder_generator()->create_report([
            'source' => certificates::class,
            'tenantid' => $tenantid,
            'adddefault' => (int) $adddefault
        ])->get_id();
    }

    /**
     * Stress testing - add all available columns, try all possible aggregation methods.
     *
     * @coversNothing
     */
    public function test_stress_aggregation(): void {
        $generator = $this->get_reportbuilder_generator();
        self::setAdminUser();

        // Create a report from the report_programs datasource without default columns/conditions.
        $reportid = $this->create_report(tenancy::get_tenant_id(), false);

        $generator->add_all_available_columns_to_report($reportid);
        $generator->datasource_stress_test_aggregation($reportid, $this);
    }

    /**
     * Stress testing - add all available conditions.
     *
     * @coversNothing
     */
    public function test_stress_conditions(): void {
        $generator = $this->get_reportbuilder_generator();

        // Create a report from the report_programs datasource without default columns/conditions.
        $reportid = $this->create_report(tenancy::get_tenant_id(), false);

        $generator->add_all_available_columns_to_report($reportid);
        $generator->datasource_stress_test_conditions($reportid, $this);
    }

    /**
     * Stress testing - add all available filters.
     *
     * @coversNothing
     */
    public function test_stress_filters(): void {
        $generator = $this->get_reportbuilder_generator();

        // Create a report from the report_programs datasource without default columns/conditions.
        $reportid = $this->create_report(tenancy::get_tenant_id(), false);

        $generator->add_all_available_columns_to_report($reportid);
        $generator->datasource_stress_test_filters($reportid, $this);
    }

    /**
     * Get report builder generator
     *
     * @return tool_reportbuilder_generator|component_generator_base
     * @throws coding_exception
     */
    protected function get_reportbuilder_generator(): tool_reportbuilder_generator {
        return self::getDataGenerator()->get_plugin_generator('tool_reportbuilder');
    }
}
