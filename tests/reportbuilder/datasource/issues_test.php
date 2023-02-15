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
use tool_certificate\reportbuilder\datasource\issues as issuesource;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/reportbuilder/tests/helpers.php");

/**
 * Certificate template datasource tests.
 *
 * @covers     \tool_certificate\reportbuilder\datasource\issues
 * @package    tool_certificate
 * @copyright  2022 Moodle Pty Ltd <support@moodle.com>
 * @author     2022 Carlos Castillo <carlos.castillo@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issues_test extends core_reportbuilder_testcase {

    /** @var core_reportbuilder_generator */
    protected $rbgenerator;
    /** @var \tool_certificate_generator */
    protected $certgenerator;

    /**
     * setUp.
     */
    public function setUp(): void {
        $this->rbgenerator = self::getDataGenerator()->get_plugin_generator('core_reportbuilder');
        $this->certgenerator = self::getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    /**
     * Test certificate template datasource
     */
    public function test_certificate_issues_datasource(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create 2 certificates.
        $cert1 = ['name' => 'Certificate 1'];
        $certificate1 = $this->certgenerator->create_template((object)$cert1);
        $cert2 = ['name' => 'Certificate 2'];
        $certificate2 = $this->certgenerator->create_template((object)$cert2);

        // Create 2 users and issue a certificate of each template.
        $user1 = self::getDataGenerator()->create_user(['firstname' => 'User', 'lastname' => 'Cert 1']);
        $issueid1 = $certificate1->issue_certificate((int)$user1->id);

        $user2 = self::getDataGenerator()->create_user(['firstname' => 'User', 'lastname' => 'Cert 2']);
        $issueid2 = $certificate2->issue_certificate((int)$user2->id);

        $report = $this->rbgenerator->create_report([
            'name' => 'RB certificate issues',
            'source' => issuesource::class,
            'default' => false,
        ]);

        // Add template name column to the report.
        $this->rbgenerator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'template:name']);
        // Add course category name column to the report.
        $this->rbgenerator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'issue:code']);
        // Add template numberofpages column to the report.
        $this->rbgenerator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'user:fullname']);

        $content = $this->get_custom_report_content($report->get('id'));
        $this->assertCount(2, $content);

        // Set all expected certificate issues values.
        $issuecode1 = $DB->get_record('tool_certificate_issues', ['id' => $issueid1]);
        $issuecode2 = $DB->get_record('tool_certificate_issues', ['id' => $issueid2]);
        $contentcerts = [
            [$cert1['name'], $issuecode1->code, $user1->firstname . ' '.$user1->lastname],
            [$cert2['name'], $issuecode2->code, $user2->firstname . ' '.$user2->lastname]
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
        $certificate = $this->certgenerator->create_template((object)$cert);

        // Create user and issue a certificate.
        $user = self::getDataGenerator()->create_user(['firstname' => 'User', 'lastname' => 'Cert 1']);
        $certificate->issue_certificate((int)$user->id);

        $this->datasource_stress_test_columns(issues::class);
        $this->datasource_stress_test_columns_aggregation(issues::class);
        $this->datasource_stress_test_conditions(issues::class, 'issue:timecreated');
    }

}
