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

use core_course_category;
use core_reportbuilder_generator;
use core_reportbuilder_testcase;
use tool_certificate\reportbuilder\datasource\templates as templatesource;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/reportbuilder/tests/helpers.php");

/**
 * Certificate template datasource tests.
 *
 * @covers     \tool_certificate\reportbuilder\datasource\templates
 * @package    tool_certificate
 * @copyright  2022 Moodle Pty Ltd <support@moodle.com>
 * @author     2022 Carlos Castillo <carlos.castillo@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class templates_test extends core_reportbuilder_testcase {

    /** @var core_reportbuilder_generator */
    protected $rbgenerator;
    /** @var \tool_certificate_generator */
    protected $certgenerator;

    /**
     * setUp.
     */
    public function setUp(): void {
        parent::setUp();
        $this->rbgenerator = self::getDataGenerator()->get_plugin_generator('core_reportbuilder');
        $this->certgenerator = self::getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    /**
     * Test certificate template datasource
     */
    public function test_certificate_template_datasource(): void {

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create 2 certificates.
        $newcategory = core_course_category::create(['name' => 'Certificate category']);
        $cert1 = ['name' => 'Certificate 1', 'categoryid' => $newcategory->id];
        $this->certgenerator->create_template((object)$cert1);
        $cert2 = ['name' => 'Certificate 2'];
        $this->certgenerator->create_template((object)$cert2);

        $report = $this->rbgenerator->create_report([
            'name' => 'RB certificate templates',
            'source' => templatesource::class,
            'default' => false,
        ]);

        // Add template name column to the report.
        $this->rbgenerator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'template:name']);
        // Add course category name column to the report.
        $this->rbgenerator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'course_category:name']);
        // Add template numberofpages column to the report.
        $this->rbgenerator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'template:numberofpages']);

        $content = $this->get_custom_report_content($report->get('id'));
        $this->assertCount(2, $content);

        // Set all expected certificate template values.
        $contentcerts = [
            [$cert1['name'], $newcategory->name, 0],
            [$cert2['name'], '', 0],
        ];
        $this->assertEqualsCanonicalizing($contentcerts, $content);

    }

    /**
     * Stress test datasource
     */
    public function test_stress_datasource(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $newcategory = core_course_category::create(['name' => 'Certificate category']);
        $cert = ['name' => 'Certificate 1', 'categoryid' => $newcategory->id];
        $this->certgenerator->create_template((object)$cert);

        $this->datasource_stress_test_columns(templates::class);
        $this->datasource_stress_test_columns_aggregation(templates::class);
        $this->datasource_stress_test_conditions(templates::class, 'template:name');
    }
}
