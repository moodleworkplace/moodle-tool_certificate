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

namespace tool_certificate;

use advanced_testcase;
use tool_certificate_generator;

/**
 * Unit tests for the webservices.
 *
 * @package    tool_certificate
 * @group      tool_certificate
 * @category   test
 * @covers     \tool_certificate\external\issues
 * @copyright  2018 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external_test extends advanced_testcase {

    /** @var tool_certificate_generator */
    protected $certgenerator;

    /**
     * Test set up.
     */
    public function setUp(): void {
        $this->resetAfterTest();
        $this->certgenerator = self::getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    /**
     * Test the delete_issue web service.
     */
    public function test_delete_issue() {
        global $DB;

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a certificate template.
        $template = $this->certgenerator->create_template((object)['name' => 'Certificate 1']);

        // Create two users.
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();

        // Enrol them into the course.
        $this->getDataGenerator()->enrol_user($student1->id, $course->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course->id);

        // Issue them both certificates.
        $i1 = $template->issue_certificate($student1->id);
        $i2 = $template->issue_certificate($student2->id);

        $this->assertEquals(2, $DB->count_records('tool_certificate_issues'));

        \tool_certificate\external\issues::revoke_issue($i2);

        $issues = $DB->get_records('tool_certificate_issues');
        $this->assertCount(1, $issues);

        $issue = reset($issues);
        $this->assertEquals($student1->id, $issue->userid);
    }

    /**
     * Test the delete_issue web service.
     */
    public function test_delete_issue_no_login() {
        global $DB;

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a certificate template.
        $template = $this->certgenerator->create_template((object)['name' => 'Certificate 1']);

        // Create two users.
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();

        // Enrol them into the course.
        $this->getDataGenerator()->enrol_user($student1->id, $course->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course->id);

        // Issue them both certificates.
        $i1 = $template->issue_certificate($student1->id);
        $i2 = $template->issue_certificate($student2->id);

        $this->assertEquals(2, $DB->count_records('tool_certificate_issues'));

        // Try and delete without logging in.
        $this->expectException('require_login_exception');
        \tool_certificate\external\issues::revoke_issue($i2);
    }

    /**
     * Test the delete_issue web service.
     */
    public function test_delete_issue_no_capability() {
        global $DB;

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a certificate template.
        $template = $this->certgenerator->create_template((object)['name' => 'Certificate 1']);

        // Create two users.
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();

        $this->setUser($student1);

        // Enrol them into the course.
        $this->getDataGenerator()->enrol_user($student1->id, $course->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course->id);

        // Issue them both certificates.
        $i1 = $template->issue_certificate($student1->id);
        $i2 = $template->issue_certificate($student2->id);

        $this->assertEquals(2, $DB->count_records('tool_certificate_issues'));

        // Try and delete without the required capability.
        $this->expectException('required_capability_exception');
        \tool_certificate\external\issues::revoke_issue($i2);
    }

    /**
     * Test regenerate_issue_file
     */
    public function test_regenerate_issue_file() {
        global $DB;

        $this->setAdminUser();

        // Create the certificate.
        $certificate = $this->certgenerator->create_template((object)['name' => 'Certificate 1']);

        // Issue certificate.
        $user = $this->getDataGenerator()->create_user(['firstname' => 'User', 'lastname' => '01']);
        $issue = $this->certgenerator->issue($certificate, $user);

        // Check issue userfullname data.
        $userfullname = $data = @json_decode($issue->data, true)['userfullname'];
        $this->assertEquals('User 01', $userfullname);

        // Check issue file already exists after issuing certificate.
        $fs = get_file_storage();
        $file = $fs->get_file(\context_system::instance()->id, 'tool_certificate', 'issues',
            $issue->id, '/', $issue->code . '.pdf');
        $this->assertNotFalse($file);

        // Change user name.
        $DB->update_record('user', (object) ['id' => $user->id, 'lastname' => '02']);

        // Regenerate issue file.
        \tool_certificate\external\issues::regenerate_issue_file($issue->id);

        // Check new file was created for issue.
        $newfile = $fs->get_file(\context_system::instance()->id, 'tool_certificate', 'issues',
            $issue->id, '/', $issue->code . '.pdf');
        $this->assertNotEquals($file->get_id(), $newfile->get_id());
        $this->assertEquals($issue->id, $newfile->get_itemid());

        // Check issue userfullname data was updated.
        $issue = $DB->get_record('tool_certificate_issues', ['id' => $issue->id]);
        $userfullname = @json_decode($issue->data, true)['userfullname'];
        $this->assertEquals('User 02', $userfullname);
    }
}
