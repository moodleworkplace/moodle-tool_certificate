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
 * File contains the unit tests for the certificate class.
 *
 * @package    tool_certificate
 * @category   test
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for the certificate class.
 *
 * @package    tool_certificate
 * @group      tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_certificate_cerficate_testcase extends advanced_testcase {

    /**
     * Test set up.
     */
    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Get tenant generator
     * @return tool_tenant_generator
     */
    protected function get_generator() : tool_certificate_generator {
        return $this->getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    /**
     * Test count issues for template.
     */
    public function test_count_issues_for_template() {
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $certificate1->issue_certificate($user1->id);

        $this->assertEquals(1, \tool_certificate\certificate::count_issues_for_template($certificate1->get_id()));

        $certificate1->issue_certificate($user1->id);
        $this->assertEquals(2, \tool_certificate\certificate::count_issues_for_template($certificate1->get_id()));

        $certificate1->issue_certificate($user2->id);
        $this->assertEquals(3, \tool_certificate\certificate::count_issues_for_template($certificate1->get_id()));
    }

    /**
     * Test count issues for user.
     */
    public function test_count_issues_for_user() {
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $certificate1->issue_certificate($user1->id);

        $this->assertEquals(1, \tool_certificate\certificate::count_issues_for_user($user1->id));

        $certificate1->issue_certificate($user1->id);
        $this->assertEquals(2, \tool_certificate\certificate::count_issues_for_user($user1->id));

        $certificate1->issue_certificate($user2->id);
        $this->assertEquals(1, \tool_certificate\certificate::count_issues_for_user($user2->id));

        $certificate1->issue_certificate($user2->id);
        $this->assertEquals(2, \tool_certificate\certificate::count_issues_for_user($user1->id));
        $this->assertEquals(2, \tool_certificate\certificate::count_issues_for_user($user2->id));

        $certificate1->issue_certificate($user2->id);
        $this->assertEquals(2, \tool_certificate\certificate::count_issues_for_user($user1->id));
        $this->assertEquals(3, \tool_certificate\certificate::count_issues_for_user($user2->id));
    }

    /**
     * Test get issues for user.
     */
    public function test_get_issues_for_user() {
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $user1 = $this->getDataGenerator()->create_user();
        $this->assertEquals(0, count(\tool_certificate\certificate::get_issues_for_user($user1->id, 0, 100)));

        $certificate1->issue_certificate($user1->id);
        $issues = \tool_certificate\certificate::get_issues_for_user($user1->id, 0, 100);
        $this->assertEquals(1, count($issues));
        $firstissue = reset($issues);
        $this->assertEquals($certificate1->get_id(), $firstissue->templateid);
        $this->assertEquals($user1->id, $firstissue->userid);

        $certificate1->issue_certificate($user1->id);

        $issues = \tool_certificate\certificate::get_issues_for_user($user1->id, 0, 100);
        $this->assertEquals(2, count($issues));
        $firstissue = reset($issues);
        $this->assertEquals($certificate1->get_id(), $firstissue->templateid);
        $this->assertEquals($user1->id, $firstissue->userid);

        $secondissue = next($issues);
        $this->assertEquals($certificate1->get_id(), $firstissue->templateid);
        $this->assertEquals($user1->id, $secondissue->userid);
        $this->assertFalse($firstissue->id == $secondissue->id);
    }

    /**
     * Test verify
     */
    public function test_verify() {
        global $DB;

        $this->setAdminUser();
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $user1 = $this->getDataGenerator()->create_user();
        $issueid1 = $certificate1->issue_certificate($user1->id);

        $code1 = $DB->get_field('tool_certificate_issues', 'code', ['id' => $issueid1]);

        // First, an invalid code must not trigger event.
        $sink = $this->redirectEvents();

        $result = \tool_certificate\certificate::verify('invalidCode1');

        $events = $sink->get_events();
        $this->assertCount(0, $events);

        $this->assertFalse($result->success);
        $this->assertTrue(empty($result->issues));

        // A valid code will trigger the event.
        $sink = $this->redirectEvents();

        $result = \tool_certificate\certificate::verify($code1);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_pop($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\tool_certificate\event\certificate_verified', $event);
        $this->assertEquals(\context_system::instance(), $event->get_context());
        $this->assertEquals(\tool_certificate\template::verification_url($code1), $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_description());

        $this->assertTrue($result->success);
        $this->assertEquals($result->issue->id, $issueid1);
    }

    /**
     * Test generate code.
     */
    public function test_generate_code() {
        $code1 = \tool_certificate\certificate::generate_code();
        $code2 = \tool_certificate\certificate::generate_code();
        $this->assertFalse($code1 == $code2);
    }
}
