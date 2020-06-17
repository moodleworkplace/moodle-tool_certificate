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
class tool_certificate_template_testcase extends advanced_testcase {

    /** @var tool_certificate_generator */
    protected $certgenerator;

    /**
     * Test set up.
     */
    public function setUp() {
        $this->resetAfterTest();
        $this->certgenerator = self::getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    /**
     * Get certificate generator
     * @return tool_certificate_generator
     */
    protected function get_generator() : tool_certificate_generator {
        return $this->getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    /**
     * Test create
     */
    public function test_create() {
        global $DB;

        // There are no certificate templates in the beginning.
        $this->assertEquals(0, $DB->count_records('tool_certificate_templates'));

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        // Create new certificate.
        $cert1name = 'Certificate 1';
        $certificate1 = $this->certgenerator->create_template((object)['name' => $cert1name]);
        $this->assertEquals(1, $DB->count_records('tool_certificate_templates'));

        $this->assertEquals($cert1name, $certificate1->get_name());
        $this->assertEquals(\context_system::instance(), $certificate1->get_context());

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_pop($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\tool_certificate\event\template_created', $event);
        $this->assertEquals(\context_system::instance(), $event->get_context());
        $this->assertEquals($certificate1->edit_url(), $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_description());

        // Create new certificate.
        $cert2name = 'Certificate 2';
        $certificate2 = $this->certgenerator->create_template((object)['name' => $cert2name]);
        $this->assertEquals(2, $DB->count_records('tool_certificate_templates'));

        $this->assertEquals($cert2name, $certificate2->get_name());
        $this->assertEquals($cert2name, $DB->get_field('tool_certificate_templates', 'name', ['id' => $certificate2->get_id()]));

        // Create certificate in a course category.
        $cat = $this->getDataGenerator()->create_category();
        $context = context_coursecat::instance($cat->id);
        $cert3name = 'Certificate 3';
        $certificate3 = $this->certgenerator->create_template((object)['name' => $cert3name, 'contextid' => $context->id]);
        $this->assertEquals(3, $DB->count_records('tool_certificate_templates'));
        $contextid = $DB->get_field('tool_certificate_templates', 'contextid', ['id' => $certificate3->get_id()]);
        $this->assertEquals($context->id, $contextid);
    }

    /**
     * Test save
     */
    public function test_save() {
        // Create new certificate.
        $certname1 = 'Certificate 1';
        $certname2 = 'Certificate Updated';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname1]);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        $certificate1->save((object)['name' => $certname2]);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_pop($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\tool_certificate\event\template_updated', $event);
        $this->assertEquals(\context_system::instance(), $event->get_context());
        $this->assertEquals($certificate1->edit_url(), $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_description());

        $this->assertEquals($certname2, \tool_certificate\template::find_by_name($certname2)->get_name());
        $this->assertFalse(\tool_certificate\template::find_by_name($certname1));
    }

    /**
     * Test find_by_name
     */
    public function test_find_by_name() {
        $certname = 'Certificate 1';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname]);
        $this->assertEquals($certname, \tool_certificate\template::find_by_name($certname)->get_name());
    }

    /**
     * Test find_by_id
     */
    public function test_find_by_id() {
        $certname = 'Certificate 1';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname]);
        $this->assertEquals($certname, \tool_certificate\template::instance($certificate1->get_id())->get_name());
    }

    /**
     * Test duplicate
     */
    public function test_duplicate() {
        $certname = 'Certificate 1';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname]);
        $certificate2 = $certificate1->duplicate();
        $expectedname = $certname . ' (copy)';
        $this->assertEquals($expectedname, $certificate2->get_name());
        $this->assertFalse($certificate1->get_id() == $certificate2->get_id());
    }

    /**
     * Test delete
     */
    public function test_delete() {
        global $DB;

        // Fist certificate without pages.
        $certname = 'Certificate 1';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname]);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        $certificate1->delete();

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_pop($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\tool_certificate\event\template_deleted', $event);
        $this->assertEquals(\context_system::instance(), $event->get_context());
        $this->assertEquals(\tool_certificate\template::manage_url(), $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_description());

        $this->assertEquals(0, $DB->count_records('tool_certificate_templates'));
        $this->assertEquals(0, $DB->count_records('tool_certificate_pages'));

        // Second certificate with pages.
        $certname = 'Certificate 2';
        $certificate2 = $this->get_generator()->create_template((object)['name' => $certname]);
        $this->get_generator()->create_page($certificate2);
        $this->get_generator()->create_page($certificate2);
        $certificate2 = \tool_certificate\template::instance($certificate2->get_id());

        $certificate2->delete();

        $this->assertEquals(0, $DB->count_records('tool_certificate_pages'));
        $this->assertEquals(0, $DB->count_records('tool_certificate_templates'));

        // Third certificate with issues.
        $certname = 'Certificate 3';
        $certificate3 = $this->get_generator()->create_template((object)['name' => $certname]);
        $user1 = $this->getDataGenerator()->create_user();

        $issueid1 = $certificate3->issue_certificate($user1->id);

        $certificate3->delete();

        $this->assertEquals(0, $DB->count_records('tool_certificate_issues'));
        $this->assertEquals(0, $DB->count_records('tool_certificate_templates'));
    }

    /**
     * Test add_page
     */
    public function test_add_page() {
        global $DB;
        $certname = 'Certificate 1';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname]);
        $this->get_generator()->create_page($certificate1);
        $this->assertEquals(1, $DB->count_records('tool_certificate_pages', ['templateid' => $certificate1->get_id()]));
    }

    /**
     * Test delete_page
     */
    public function test_delete_page() {
        global $DB;
        $certname = 'Certificate 1';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname]);
        $pageid1 = $this->get_generator()->create_page($certificate1)->get_id();
        $pageid2 = $this->get_generator()->create_page($certificate1)->get_id();
        $certificate1 = \tool_certificate\template::instance($certificate1->get_id());
        $this->assertEquals(2, $DB->count_records('tool_certificate_pages', ['templateid' => $certificate1->get_id()]));
        $certificate1->delete_page($pageid1);
        $this->assertEquals(1, $DB->count_records('tool_certificate_pages', ['templateid' => $certificate1->get_id()]));
        $certificate1->delete_page($pageid2);
        $this->assertEquals(0, $DB->count_records('tool_certificate_pages', ['templateid' => $certificate1->get_id()]));
    }

    /**
     * Test save_page
     */
    public function test_save_page() {
        global $DB;
        $certname = 'Certificate 1';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname]);
        $pageid = $this->get_generator()->create_page($certificate1)->get_id();
        $pagedata = (object)['tid' => $certificate1->get_id(),
                             'pagewidth_'.$pageid => 333, 'pageheight_'.$pageid => 444,
                             'pageleftmargin_'.$pageid => 333, 'pagerightmargin_'.$pageid => 444];
        $certificate1->save_page($pagedata);
        $this->assertTrue($DB->record_exists('tool_certificate_pages', ['templateid' => $certificate1->get_id(),
            'width' => 333, 'height' => 444]));
    }

    /**
     * Test issue_certificate
     */
    public function test_issue_certificate() {
        global $DB;

        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $messagessink = $this->redirectMessages();

        $issueid1 = $certificate1->issue_certificate($user1->id);

        $code1 = $DB->get_field('tool_certificate_issues', 'code', ['id' => $issueid1]);

        $events = $sink->get_events();
        $messages = $messagessink->get_messages();
        $sink->close();
        $messagessink->close();

        // There are two events: notification_viewed and certificate_issued.
        $this->assertCount(2, $events);
        $event = array_pop($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\tool_certificate\event\certificate_issued', $event);
        $this->assertEquals(\context_system::instance(), $event->get_context());
        $this->assertEquals(\tool_certificate\template::view_url($code1), $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_description());

        $this->assertEquals(1, $DB->count_records('tool_certificate_issues', ['templateid' => $certificate1->get_id()]));

        // Check issue notification.
        $issuenotification = reset($messages);
        $this->assertEquals($user1->id, $issuenotification->useridto);
        $this->assertEquals('tool_certificate', $issuenotification->component);
        $this->assertEquals('certificateissued', $issuenotification->eventtype);
        $this->assertEquals('Your certificate is available!', $issuenotification->subject);

        $certificate1->issue_certificate($user2->id);

        $this->assertEquals(2, $DB->count_records('tool_certificate_issues', ['templateid' => $certificate1->get_id()]));

        $this->assertEquals(1, $DB->count_records('tool_certificate_issues', ['templateid' => $certificate1->get_id(),
            'userid' => $user1->id]));
        $this->assertEquals(1, $DB->count_records('tool_certificate_issues', ['templateid' => $certificate1->get_id(),
            'userid' => $user2->id]));

        $certificate1->issue_certificate($user1->id);
        $this->assertEquals(2, $DB->count_records('tool_certificate_issues', ['templateid' => $certificate1->get_id(),
            'userid' => $user1->id]));

        $certificate1->issue_certificate($user2->id);
        $certificate1->issue_certificate($user2->id);

        $this->assertEquals(3, $DB->count_records('tool_certificate_issues', ['templateid' => $certificate1->get_id(),
            'userid' => $user2->id]));
    }

    /**
     * Test revoke_issue
     */
    public function test_revoke_issue() {
        global $DB;

        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $issueid1 = $certificate1->issue_certificate($user1->id);
        $issueid2 = $certificate1->issue_certificate($user2->id);
        $code1 = $DB->get_field('tool_certificate_issues', 'code', ['id' => $issueid1]);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        $certificate1->revoke_issue($issueid1);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_pop($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\tool_certificate\event\certificate_revoked', $event);
        $this->assertEquals(\context_system::instance(), $event->get_context());
        $moodlepage = new \moodle_url('/admin/tool/certificate/view.php', ['code' => $code1]);
        $this->assertEquals(\tool_certificate\template::view_url($code1), $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_description());

        $this->assertEquals(1, $DB->count_records('tool_certificate_issues', ['templateid' => $certificate1->get_id()]));

        $certificate1->revoke_issue($issueid2);

        $this->assertEquals(0, $DB->count_records('tool_certificate_issues', ['templateid' => $certificate1->get_id()]));
    }

    /**
     * Test move/remove template on category deletion.
     */
    public function test_delete_category_with_certificates() {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $roleid = create_role('Dummy role', 'dummyrole', 'dummy role description');
        $this->setUser($user);

        $cat1 = $this->getDataGenerator()->create_category();
        $cat2 = $this->getDataGenerator()->create_category();
        $cat3 = $this->getDataGenerator()->create_category();
        $cat1context = context_coursecat::instance($cat1->id);
        $cat2context = context_coursecat::instance($cat2->id);
        $cat3context = context_coursecat::instance($cat3->id);

        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1',
            'contextid' => $cat1context->id]);
        $certificate2 = $this->get_generator()->create_template((object)['name' => 'Certificate 2',
            'contextid' => $cat2context->id]);

        // Check 'can_course_category_delete' without capabilities.
        $this->assertFalse(tool_certificate_can_course_category_delete($cat1));
        // Add capabilities and check again.
        $this->get_generator()->assign_manage_capability($user->id, $roleid, $cat1context);
        $this->assertTrue(tool_certificate_can_course_category_delete($cat1));

        // Delete cat1 with all its content.
        $cat1->delete_full();
        // Check certificate1 was removed.
        $this->assertFalse($DB->record_exists(\tool_certificate\persistent\template::TABLE, ['id' => $certificate1->get_id()]));

        // Check 'can_course_category_delete_move' without capabilities.
        $this->assertFalse(tool_certificate_can_course_category_delete_move($cat2, $cat3));
        $this->get_generator()->assign_manage_capability($user->id, $roleid, $cat2context);
        $this->get_generator()->assign_manage_capability($user->id, $roleid, $cat3context);
        // Add capabilities and check again.
        $this->assertTrue(tool_certificate_can_course_category_delete_move($cat2, $cat3));

        // Delete cat2 moving content to cat3.
        $cat2->delete_move($cat3->id);
        // Check certificate2 in now in cat3.
        $this->assertEquals($cat3context->id, $DB->get_field(\tool_certificate\persistent\template::TABLE,
            'contextid', ['id' => $certificate2->get_id()]));
    }

    /**
     * Test category deletion for the purpose of callback behaviour with no certificates.
     */
    public function test_delete_category_with_no_certificates() {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $roleid = create_role('Dummy role', 'dummyrole', 'dummy role description');
        $this->setUser($user);

        $cat1 = $this->getDataGenerator()->create_category();
        $cat2 = $this->getDataGenerator()->create_category();
        $cat3 = $this->getDataGenerator()->create_category();
        $cat1context = context_coursecat::instance($cat1->id);
        $cat2context = context_coursecat::instance($cat2->id);
        $cat3context = context_coursecat::instance($cat3->id);

        // Check 'can_course_category_delete'.
        $this->assertTrue(tool_certificate_can_course_category_delete($cat1));

        // Delete cat1 with all its content.
        $cat1->delete_full();

        // Check 'can_course_category_delete_move'.
        $this->assertTrue(tool_certificate_can_course_category_delete_move($cat2, $cat3));

        // Delete cat2 moving content to cat3.
        $cat2->delete_move($cat3->id);
    }
}
