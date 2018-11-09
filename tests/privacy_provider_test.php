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
 * Privacy provider tests.
 *
 * @package    tool_certificate
 * @copyright  2018 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_certificate\privacy\provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider tests class.
 *
 * @package    tool_certificate
 * @copyright  2018 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_certificate_privacy_provider_testcase extends \core_privacy\tests\provider_testcase {

    /**
     * Test for provider::get_contexts_for_userid().
     */
    public function test_get_contexts_for_userid() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        // Add a template to the site.
        $template1 = \tool_certificate\template::create('Site template', context_system::instance()->id);

        // Another template that has no issued certificates.
        $template2 = \tool_certificate\template::create('No issues template', context_system::instance()->id);

        // Create a user who will be issued a certificate.
        $user = $this->getDataGenerator()->create_user();

        // Issue the certificate.
        $this->create_certificate_issue($template1->get_id(), $user->id);

        // Check the context supplied is correct.
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);

        $contextformodule = $contextlist->current();
        $this->assertEquals($contextformodule->id, \context_system::instance()->id);
    }

    /**
     * Test for provider::export_user_data().
     */
    public function test_export_for_context() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        // Add a template to the site.
        $template = \tool_certificate\template::create('Site template', context_system::instance()->id);

        // Create users who will be issued a certificate.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->create_certificate_issue($template->get_id(), $user1->id);
        $this->create_certificate_issue($template->get_id(), $user1->id);
        $this->create_certificate_issue($template->get_id(), $user2->id);

        // Export all of the data for the context for user 1.
        $context = \context_system::instance();
        $this->export_context_data_for_user($user1->id, $context, 'tool_certificate');
        $writer = \core_privacy\local\request\writer::with_context($context);

        $this->assertTrue($writer->has_any_data());

        $data = $writer->get_data();
        $this->assertCount(2, $data->issues);

        $issues = $data->issues;
        foreach ($issues as $issue) {
            $this->assertArrayHasKey('code', $issue);
            $this->assertArrayHasKey('timecreated', $issue);
        }
    }

    /**
     * Test for provider::delete_data_for_all_users_in_context().
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        // Add a template to the site.
        $template1 = \tool_certificate\template::create('Site template', context_system::instance()->id);
        $template2 = \tool_certificate\template::create('Second template', context_system::instance()->id);

        // Create users who will be issued a certificate.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->create_certificate_issue($template1->get_id(), $user1->id);
        $this->create_certificate_issue($template1->get_id(), $user2->id);

        $this->create_certificate_issue($template2->get_id(), $user1->id);
        $this->create_certificate_issue($template2->get_id(), $user2->id);

        // Before deletion, we should have 2 issued certificates for the first certificate.
        $count = $DB->count_records('tool_certificate_issues', ['templateid' => $template1->get_id()]);
        $this->assertEquals(2, $count);

        // Delete data based on context.
        $context = \context_system::instance();
        provider::delete_data_for_all_users_in_context($context);

        // After deletion, the issued certificates for all templates should have been deleted.
        $count = $DB->count_records('tool_certificate_issues');
        $this->assertEquals(0, $count);
    }

    /**
     * Test for provider::delete_data_for_user().
     */
    public function test_delete_data_for_user() {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        $template = \tool_certificate\template::create('Site template', context_system::instance()->id);

        // Create users who will be issued a certificate.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->create_certificate_issue($template->get_id(), $user1->id);
        $this->create_certificate_issue($template->get_id(), $user2->id);

        // Before deletion we should have 2 issued certificates.
        $count = $DB->count_records('tool_certificate_issues', ['templateid' => $template->get_id()]);
        $this->assertEquals(2, $count);

        $context = \context_system::instance();
        $contextlist = new \core_privacy\local\request\approved_contextlist($user1, 'tool_certificate', [$context->id]);
        provider::delete_data_for_user($contextlist);

        // After deletion, the issued certificates for the first user should have been deleted.
        $count = $DB->count_records('tool_certificate_issues', ['templateid' => $template->get_id(), 'userid' => $user1->id]);
        $this->assertEquals(0, $count);

        // Check the issue for the other user is still there.
        $templateissue = $DB->get_records('tool_certificate_issues');
        $this->assertCount(1, $templateissue);
        $lastissue = reset($templateissue);
        $this->assertEquals($user2->id, $lastissue->userid);
    }

    /**
     * Mimicks the creation of a template issue.
     *
     * There is no API we can use to insert an template issue, so we
     * will simply insert directly into the database.
     *
     * @param int $templateid
     * @param int $userid
     */
    protected function create_certificate_issue(int $templateid, int $userid) {
        global $DB;

        static $i = 1;

        $templateissue = new stdClass();
        $templateissue->templateid = $templateid;
        $templateissue->userid = $userid;
        $templateissue->code = \tool_certificate\certificate::generate_code();
        $templateissue->timecreated = time() + $i;

        // Insert the record into the database.
        $DB->insert_record('tool_certificate_issues', $templateissue);

        $i++;
    }
}
