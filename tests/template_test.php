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
use context_coursecat;
use context_system;

/**
 * Unit tests for the certificate class.
 *
 * @package    tool_certificate
 * @covers     \tool_certificate\template
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template_test extends advanced_testcase {

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
        // Store template id before deletion.
        $certificate1id = $certificate1->get_id();

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
        $this->assertEquals($certificate1id, $event->objectid);
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
     * Test generate_pdf with multilang text and 'issuelang' setting.
     */
    public function test_generate_pdf() {
        // Enable multilang filter.
        filter_set_global_state('multilang', TEXTFILTER_ON);
        filter_set_applies_to_strings('multilang', true);
        $this->add_spanish_language_pack();

        // Create a user with spanish language.
        $user1 = $this->getDataGenerator()->create_user(['lang' => 'es']);

        // Create a template with multilang text.
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $pageid = $this->get_generator()->create_page($certificate1)->get_id();
        $multilangtext = '<span lang="es" class="multilang">Enhorabuena</span>'
            . '<span lang="en" class="multilang">Congratulations</span>';
        $this->get_generator()->create_element($pageid, 'text', ['text' => $multilangtext]);

        // Generate the certificate pdf file.
        $issue = $this->get_generator()->issue($certificate1, $user1);
        $template = \tool_certificate\template::instance($certificate1->get_id());
        $filecontents = $template->generate_pdf(false, $issue, true);

        // Check that pdf contains english text.
        $this->assertTrue($this->pdf_contains_text('Congratulations', $filecontents));
        $this->assertFalse($this->pdf_contains_text('Enhorabuena', $filecontents));

        // Now activate 'issuelang' setting.
        set_config('issuelang', 1, 'tool_certificate');

        // Generate the certificate pdf file.
        $issue = $this->get_generator()->issue($certificate1, $user1);
        $template = \tool_certificate\template::instance($certificate1->get_id());
        $filecontents = $template->generate_pdf(false, $issue, true);

        // Check that pdf contains spanish text.
        $this->assertTrue($this->pdf_contains_text('Enhorabuena', $filecontents));
        $this->assertFalse($this->pdf_contains_text('Contratulations', $filecontents));
    }

    /**
     * Test issue_certificate
     */
    public function test_issue_certificate() {
        global $DB;

        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'User', 'lastname' => '01']);
        $user2 = $this->getDataGenerator()->create_user();

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $messagessink = $this->redirectMessages();

        $issue1 = $this->get_generator()->issue($certificate1, $user1);

        $events = $sink->get_events();
        $messages = $messagessink->get_messages();
        $sink->close();
        $messagessink->close();

        // Check userfullname data is stored.
        $userfullname = @json_decode($issue1->data, true)['userfullname'];
        $this->assertEquals('User 01', $userfullname);

        // There are two events: notification_viewed and certificate_issued.
        $this->assertCount(2, $events);
        $event = array_pop($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\tool_certificate\event\certificate_issued', $event);
        $this->assertEquals(\context_system::instance(), $event->get_context());
        $this->assertEquals(\tool_certificate\template::view_url($issue1->code), $event->get_url());
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

        // Check issue file was created.
        $fs = get_file_storage();
        $this->assertTrue($fs->file_exists(\context_system::instance()->id, 'tool_certificate', 'issues',
            $issue1->id, '/', $issue1->code . '.pdf'));

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

        // Test issue_certificate with courseid.
        $course = $this->getDataGenerator()->create_course();
        // Using dummy component name.
        $issueid = $certificate1->issue_certificate($user2->id, null, [], 'mod_myawesomecert', $course->id);
        $issue = $DB->get_record('tool_certificate_issues', ['id' => $issueid]);

        $this->assertEquals($certificate1->get_id(), $issue->templateid);
        $this->assertEquals($course->id, $issue->courseid);

        $this->assertEquals(4, $DB->count_records('tool_certificate_issues', ['templateid' => $certificate1->get_id(),
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
     * Test create_issue_file
     */
    public function test_create_issue_file() {
        // Create the certificate.
        $certificate = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);

        // Issue certificate.
        $user = $this->getDataGenerator()->create_user();
        $issue = $this->get_generator()->issue($certificate, $user);

        // Check issue file already exists after issuing certificate.
        $fs = get_file_storage();
        $file = $fs->get_file(\context_system::instance()->id, 'tool_certificate', 'issues',
            $issue->id, '/', $issue->code . '.pdf');
        $this->assertNotFalse($file);

        $file->delete();

        // Check create new issue file.
        $file = $certificate->create_issue_file($issue);
        $this->assertEquals($issue->id, $file->get_itemid());

        // Regenerate issue file.
        $file2 = $certificate->create_issue_file($issue, true);

        // Check new file was created for issue.
        $issuefile = $fs->get_file(\context_system::instance()->id, 'tool_certificate', 'issues',
            $issue->id, '/', $issue->code . '.pdf');
        $this->assertEquals($issue->id, $file2->get_itemid());
        $this->assertEquals($issuefile->get_id(), $file2->get_id());

        // Try to create an existing issue file.
        $this->expectException('stored_file_creation_exception');
        $certificate->create_issue_file($issue);
    }

    /**
     * Test get_issue_file
     */
    public function test_get_issue_file() {
        // Create the certificate.
        $certificate = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);

        // Issue certificate.
        $user = $this->getDataGenerator()->create_user();
        $issue = $this->get_generator()->issue($certificate, $user);

        // Check issue file already exists after issuing certificate.
        $fs = get_file_storage();
        $this->assertTrue($fs->file_exists(\context_system::instance()->id, 'tool_certificate', 'issues',
            $issue->id, '/', $issue->code . '.pdf'));

        $issuefile = $certificate->get_issue_file($issue);
        $this->assertEquals($issue->id, $issuefile->get_itemid());

        // Check issue file is recreated after deletion.
        $issuefile->delete();
        $this->assertFalse($fs->file_exists(\context_system::instance()->id, 'tool_certificate', 'issues',
            $issue->id, '/', $issue->code . '.pdf'));
        $issuefile = $certificate->get_issue_file($issue);
        $this->assertEquals($issue->id, $issuefile->get_itemid());
    }

    /**
     * Test get_visible_categories_contexts_sql
     */
    public function test_get_visible_categories_contexts_sql() {
        $this->setAdminUser();
        $cat1 = $this->getDataGenerator()->create_category();
        $cat2 = $this->getDataGenerator()->create_category();
        $cat3 = $this->getDataGenerator()->create_category();

        $this->get_generator()->create_template((object)['name' => 'Template 1',
            'contextid' => context_coursecat::instance($cat1->id)->id]);
        $this->get_generator()->create_template((object)['name' => 'Template 2',
            'contextid' => context_coursecat::instance($cat2->id)->id]);
        $this->get_generator()->create_template((object)['name' => 'Template 2',
            'contextid' => context_system::instance()->id]);

        [$sql, $params] = \tool_certificate\template::get_visible_categories_contexts_sql();

        // Check 3 contexts are returned: cat1, cat2 and system.
        $this->assertNotEmpty($sql);
        $this->assertEqualsCanonicalizing([
            context_system::instance()->id,
            context_coursecat::instance($cat1->id)->id,
            context_coursecat::instance($cat2->id)->id,
        ], $params);
    }

    /**
     * Test move_files_to_new_context
     */
    public function test_move_files_to_new_context() {
        $this->setAdminUser();

        $cat1 = $this->getDataGenerator()->create_category();
        $cat1context = context_coursecat::instance($cat1->id);
        $template1 = $this->get_generator()->create_template((object)['name' => 'Template 1',
            'contextid' => context_coursecat::instance($cat1->id)->id]);
        $page1 = $this->get_generator()->create_page($template1);
        $imageelement = $this->get_generator()->create_element($page1->get_id(), 'image');

        // Create a dummy image file for element in the current template context (category1).
        $fs = get_file_storage();
        $filerecord = [
            'contextid' => $template1->get_context()->id,
            'component' => 'tool_certificate',
            'filearea' => 'element',
            'itemid' => $imageelement->get_id(),
            'filepath' => '/',
            'filename' => 'image.png'
        ];
        $file = $fs->create_file_from_string($filerecord, 'Awesome photography');
        $filecontent = $file->get_content();

        // Sanity check. image file is in category1 context.
        $imageelementfiles = $fs->get_area_files($cat1context->id, 'tool_certificate', 'element',
            $imageelement->get_id(), '', false);
        $this->assertEquals($filecontent, reset($imageelementfiles)->get_content());

        // Move template files to category2 context.
        $cat2 = $this->getDataGenerator()->create_category();
        $cat2context = context_coursecat::instance($cat2->id);
        $template = \tool_certificate\template::instance($template1->get_id());
        $template->move_files_to_new_context($cat2context->id);

        // Check image file is not in category1 context.
        $imageelementfiles = $fs->get_area_files($cat1context->id, 'tool_certificate', 'element',
            $imageelement->get_id(), '', false);
        $this->assertEmpty($imageelementfiles);
        // Check image file is now in category2 context.
        $imageelementfiles = $fs->get_area_files($cat2context->id, 'tool_certificate', 'element',
            $imageelement->get_id(), '', false);
        $this->assertEquals($filecontent, reset($imageelementfiles)->get_content());
    }

    /**
     * Checks if the PDF file contains a given string
     *
     * @param string $text string to search
     * @param string $filecontents contents of the PDF file
     * @return bool
     */
    private function pdf_contains_text(string $text, string $filecontents): bool {
        global $CFG;
        require_once($CFG->dirroot.'/lib/tcpdf/tcpdf_parser.php');
        $parser = new \TCPDF_PARSER($filecontents);
        list($xref, $data) = $parser->getParsedData();

        $searchtext = 'Td [(';
        for ($i = 0; $i < strlen($text); $i++) {
            $searchtext .= chr(0) . substr($text, $i, 1);
        }
        $searchtext .= ')]';
        $found = false;
        array_walk_recursive($data, function($value, $key) use ($searchtext, &$found) {
            $found = $found || (!empty($value) && strpos((string)$value, $searchtext) !== false);
        });
        return $found;
    }

    /**
     * Simulate the presence of Spanish language pack for multilang test purpose.
     */
    private function add_spanish_language_pack(): void {
        global $CFG;
        $langfolder = $CFG->dataroot . '/lang/es';
        check_dir_exists($langfolder);
        $langconfig = "<?php\n\$string['parentlanguage'] = 'en';";
        file_put_contents($langfolder . '/langconfig.php', $langconfig);
    }
}
