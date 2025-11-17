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
final class issues_test extends core_reportbuilder_testcase {

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

        // Add some cohort data.
        $cohort = $this->getDataGenerator()->create_cohort(['name' => 'My cohort']);
        cohort_add_member($cohort->id, $user1->id);

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
        // Add cohort name column to the report.
        $this->rbgenerator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'cohort:name']);

        $content = $this->get_custom_report_content($report->get('id'));
        $this->assertCount(2, $content);

        // Set all expected certificate issues values.
        $issuecode1 = $DB->get_record('tool_certificate_issues', ['id' => $issueid1]);
        $issuecode2 = $DB->get_record('tool_certificate_issues', ['id' => $issueid2]);
        $contentcerts = [
            [$cert1['name'], $issuecode1->code, $user1->firstname . ' '.$user1->lastname, $cohort->name],
            [$cert2['name'], $issuecode2->code, $user2->firstname . ' '.$user2->lastname, ''],
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

        // Add some cohort data.
        $cohort = $this->getDataGenerator()->create_cohort(['name' => 'My cohort']);
        cohort_add_member($cohort->id, $user->id);

        $this->datasource_stress_test_columns(issues::class);
        $this->datasource_stress_test_columns_aggregation(issues::class);
        $this->datasource_stress_test_conditions(issues::class, 'issue:timecreated');
    }

    /**
     * Test condition checking permission to access certificate template.
     */
    public function test_condition_template_permission(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create 2 certificates in different categories.
        $cat1 = self::getDataGenerator()->create_category();
        $cat2 = self::getDataGenerator()->create_category();
        $cert1 = ['name' => 'Certificate 1', 'categoryid' => $cat1->id];
        $certificate1 = $this->certgenerator->create_template((object)$cert1);
        $cert2 = ['name' => 'Certificate 2', 'categoryid' => $cat2->id];
        $certificate2 = $this->certgenerator->create_template((object)$cert2);

        // Create 2 users and issue a certificate of each template.
        $user1 = self::getDataGenerator()->create_user(['firstname' => 'User', 'lastname' => 'Cert 1']);
        $issueid1 = $certificate1->issue_certificate((int)$user1->id);

        $user2 = self::getDataGenerator()->create_user(['firstname' => 'User', 'lastname' => 'Cert 2']);
        $issueid2 = $certificate2->issue_certificate((int)$user2->id);

        // Create a user who has capability to view templates in the second category.
        $manager = self::getDataGenerator()->create_user();
        $managerroleid = $DB->get_field_select('role', 'id', 'shortname = ?', ['manager']);
        self::getDataGenerator()->role_assign($managerroleid, $manager->id, \context_coursecat::instance($cat2->id)->id);

        // Create report.
        $report = $this->rbgenerator->create_report([
            'name' => 'RB certificate issues',
            'source' => issuesource::class,
            'default' => false,
        ]);

        // Add template name column to the report.
        $this->rbgenerator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'template:name']);
        // Add template numberofpages column to the report.
        $this->rbgenerator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'user:fullname']);
        // Add condition to check permissions but do not set the value.
        $this->rbgenerator->create_condition(
            ['reportid' => $report->get('id'), 'uniqueidentifier' => 'template:templatepermission']);

        // Manager will see both issues.
        $this->setUser($manager);
        $content = $this->get_custom_report_content($report->get('id'));
        $this->assertCount(2, $content);

        // Set the value for the condition to check permissions.
        $reportobj = \core_reportbuilder\manager::get_report_from_persistent($report);
        $reportobj->set_condition_values(['template:templatepermission_operator' => 1]);

        // Now manager can only see one certificate issue.
        $content = $this->get_custom_report_content($report->get('id'));
        $this->assertCount(1, $content);
    }

    public function test_upgrade_add_permission_condition_to_reports(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/certificate/db/upgradelib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create certificates and issues.
        $cat = self::getDataGenerator()->create_category();
        $certificate1 = $this->certgenerator->create_template((object)['name' => 'Cert1']);
        $certificate2 = $this->certgenerator->create_template((object)['name' => 'Cert2', 'categoryid' => $cat->id]);
        $user1 = self::getDataGenerator()->create_user(['firstname' => 'Wally', 'lastname' => 'X']);
        $user2 = self::getDataGenerator()->create_user(['firstname' => 'Wendy', 'lastname' => 'Y']);
        $certificate1->issue_certificate((int)$user1->id);
        $certificate1->issue_certificate((int)$user2->id);
        $certificate2->issue_certificate((int)$user1->id);
        $certificate2->issue_certificate((int)$user2->id);

        // Create a user who has capability to view templates in the second category.
        $manager = self::getDataGenerator()->create_user();
        $managerroleid = $DB->get_field_select('role', 'id', 'shortname = ?', ['manager']);
        self::getDataGenerator()->role_assign($managerroleid, $manager->id, \context_coursecat::instance($cat->id)->id);

        /** @var \core_reportbuilder_generator $rbgenerator */
        $rbgenerator = self::getDataGenerator()->get_plugin_generator('core_reportbuilder');

        // Create two reports from the issues source, one with conditions and one without.
        $report1 = $rbgenerator->create_report([
            'name' => 'Where is Wally?',
            'source' => issuesource::class,
            'default' => false,
        ]);
        $rbgenerator->create_column(['reportid' => $report1->get('id'), 'uniqueidentifier' => 'template:name']);
        $rbgenerator->create_column(['reportid' => $report1->get('id'), 'uniqueidentifier' => 'user:fullname']);
        $rbgenerator->create_condition(['reportid' => $report1->get('id'), 'uniqueidentifier' => 'user:firstname']);
        $instance = \core_reportbuilder\manager::get_report_from_persistent($report1);
        $instance->set_condition_values(["user:firstname_operator" => "3", "user:firstname_value" => "Wally"]);

        $report2 = $rbgenerator->create_report([
            'name' => 'All issues',
            'source' => issuesource::class,
            'default' => false,
        ]);
        $rbgenerator->create_column(['reportid' => $report2->get('id'), 'uniqueidentifier' => 'template:name']);
        $rbgenerator->create_column(['reportid' => $report2->get('id'), 'uniqueidentifier' => 'user:fullname']);

        // Admin can see all issues. Manager can also see all issues.
        $content1 = $this->get_custom_report_content($report1->get('id'));
        $this->assertCount(2, $content1);
        $content2 = $this->get_custom_report_content($report2->get('id'));
        $this->assertCount(4, $content2);

        $this->setUser($manager);
        $content1 = $this->get_custom_report_content($report1->get('id'));
        $this->assertCount(2, $content1);
        $content2 = $this->get_custom_report_content($report2->get('id'));
        $this->assertCount(4, $content2);

        // Run upgrade script. It will add a condition that users can only see issues for
        // templates they have permission to access.
        tool_certificate_upgrade_add_permission_condition_to_reports();
        \core_reportbuilder\manager::reset_caches();

        // Admin can still see all users and manager can still see only the ones in the second category.
        $this->setAdminUser();
        $content1 = $this->get_custom_report_content($report1->get('id'));
        $this->assertCount(2, $content1);
        $content2 = $this->get_custom_report_content($report2->get('id'));
        $this->assertCount(4, $content2);

        $this->setUser($manager);
        $content1 = $this->get_custom_report_content($report1->get('id'));
        $this->assertCount(1, $content1);
        $content2 = $this->get_custom_report_content($report2->get('id'));
        $this->assertCount(2, $content2);
    }
}
