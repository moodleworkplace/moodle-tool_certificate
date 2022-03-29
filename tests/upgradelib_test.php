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
use tool_tenant_generator;
use context_coursecat;
use context_system;
use xmldb_table;

/**
 * Tests for functions in db/upgradelib.php
 *
 * @package    tool_certificate
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upgradelib_test extends advanced_testcase {

    /** @var string */
    protected $temptable = null;

    public static function setUpBeforeClass(): void {
        global $CFG;

        require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/certificate/db/upgradelib.php');
    }

    /**
     * After test ends
     */
    protected function tearDown(): void {
        global $DB;
        if ($this->temptable) {
            $DB->get_manager()->drop_table($this->temptable);
        }
        parent::tearDown();
    }

    /**
     * Returns the tenant generator
     *
     * @return tool_tenant_generator
     */
    protected function get_tenant_generator(): tool_tenant_generator {
        return $this->getDataGenerator()->get_plugin_generator('tool_tenant');
    }

    /**
     * Get certificate generator
     * @return tool_certificate_generator
     */
    protected function get_generator() : tool_certificate_generator {
        return $this->getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    /**
     * Test for function tool_certificate_upgrade_remove_tenant_field()
     *
     * @covers ::tool_certificate_upgrade_remove_tenant_field
     */
    public function test_tool_certificate_upgrade_remove_tenant_field() {
        global $DB;

        // Skip tests if tool_tenant is not present.
        if (!class_exists('tool_tenant\tenancy')) {
            $this->markTestSkipped('Plugin tool_tenant not installed, skipping');
        }

        $this->resetAfterTest();
        $tablename = 'tool_certificate_temp_templ';
        $syscontextid = context_system::instance()->id;

        // Create a temp table.
        $dbman = $DB->get_manager();
        $this->temptable = $table = new xmldb_table($tablename);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('tenantid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_temp_table($table);

        $cat = $this->getDataGenerator()->create_category(['name' => 'CAT1']);
        $catcontextid = context_coursecat::instance($cat->id)->id;
        $tenant1 = $this->get_tenant_generator()->create_tenant(['categoryid' => $cat->id]);
        $tenant2 = $this->get_tenant_generator()->create_tenant();

        $DB->insert_record($tablename, ['tenantid' => 0, 'contextid' => 0]);
        $DB->insert_record($tablename, ['tenantid' => $tenant1->id, 'contextid' => 0]);
        $DB->insert_record($tablename, ['tenantid' => $tenant2->id, 'contextid' => 0]);

        tool_certificate_upgrade_remove_tenant_field($tablename);

        $results = $DB->get_fieldset_sql("SELECT contextid FROM {".$tablename."} ORDER BY id", []);
        $this->assertEquals([$syscontextid, $catcontextid, $syscontextid], $results);
    }

    /**
     * Tests for tool_certificate_upgrade_move_data_to_customfields()
     *
     * @covers ::tool_certificate_upgrade_move_data_to_customfields
     */
    public function test_tool_certificate_upgrade_move_data_to_customfields() {
        global $DB;

        $this->resetAfterTest();
        \tool_certificate\customfield\issue_handler::create()->delete_all();
        $tablename = 'tool_certificate_issues_tmp';

        $this->temptable = $table = new xmldb_table($tablename);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('component', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $DB->get_manager()->create_temp_table($table);

        $context = context_system::instance();
        $templateid = $DB->insert_record('tool_certificate_templates', (object)['name' => 'Template 01',
            'contextid' => $context->id]);

        $id1 = $DB->insert_record($tablename, (object)['component' => 'tool_dynamicrule', 'templateid' => $templateid,
            'data' => '{"certificationname":"","programname":"","completiondate":"","completedcourses":[]}']);
        $id2 = $DB->insert_record($tablename, (object)['component' => 'tool_dynamicrule', 'templateid' => $templateid,
            'data' => '{"certificationname":"My cert","programname":"My prog","completiondate":"1546344000",' .
                '"completedcourses":["a","b"]}']);
        $id3 = $DB->insert_record($tablename, (object)['component' => 'tool_dynamicrule', 'templateid' => $templateid,
            'data' => json_encode(['coursename' => 'X'])]);

        // Create tool_program issue customfields manually if tool_program is not available.
        if (!class_exists('\\tool_program\\program')) {
            $handler = \tool_certificate\customfield\issue_handler::create();
            $handler->ensure_field_exists('programname', 'text', 'Program name', true, 'Program name preview');
            $handler->ensure_field_exists('programcompletiondate', 'date', 'Program completion date', true,
                userdate(strtotime(date('Y-01-01')), get_string('strftimedatefullshort')), ['includetime' => false]);
            $handler->ensure_field_exists('programcompletedcourses', 'textarea', 'Courses completed in program', true,
                '<ul><li>C01</li><li>C02</li><li>C03</li></ul>'
            );
        }
        // Create tool_certification issue customfields manually if tool_certification is not available.
        if (!class_exists('\\tool_certification\\certification')) {
            $handler = \tool_certificate\customfield\issue_handler::create();
            $handler->ensure_field_exists('certificationname', 'text', 'Certification name', true, 'Certification name preview');
        }

        tool_certificate_upgrade_move_data_to_customfields($tablename);

        $handler = \tool_certificate\customfield\issue_handler::create();

        $data1 = $handler->export_instance_data_object($id1);
        $this->assertEquals(null, $data1->programcompletedcourses);

        $data2 = $handler->export_instance_data_object($id2);
        $this->assertEquals('My cert', $data2->certificationname);
        $this->assertEquals('My prog', $data2->programname);
        $this->assertEquals('1/01/19', $data2->programcompletiondate);
        $this->assertEquals('<ul><li>a</li><li>b</li></ul>', $data2->programcompletedcourses);

        $this->assertEquals('[]', $DB->get_field($tablename, 'data', ['id' => $id1]));
        $this->assertEquals('[]', $DB->get_field($tablename, 'data', ['id' => $id2]));
        $this->assertEquals('{"coursename":"X"}', $DB->get_field($tablename, 'data', ['id' => $id3]));
    }

    /**
     * Tests for tool_certificate_upgrade_store_fullname_in_data()
     *
     * @covers ::tool_certificate_upgrade_store_fullname_in_data
     */
    public function test_tool_certificate_upgrade_store_fullname_in_data() {
        global $DB;

        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'User', 'lastname' => '01']);
        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'User', 'lastname' => '02']);
        $user3 = $this->getDataGenerator()->create_user(['firstname' => 'User', 'lastname' => '03']);

        $tablename = 'tool_certificate_issues_tmp';

        $this->temptable = $table = new xmldb_table($tablename);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $DB->get_manager()->create_temp_table($table);

        $id1 = $DB->insert_record($tablename, (object) ['userid' => $user1->id, 'data' => '{}']);
        $id2 = $DB->insert_record($tablename, (object) ['userid' => $user2->id, 'data' => '{}']);
        $id3 = $DB->insert_record($tablename, (object) ['userid' => $user3->id, 'data' => '{"userfullname":"User 03"}']);

        tool_certificate_upgrade_store_fullname_in_data($tablename);

        $issue1 = $DB->get_record($tablename, ['id' => $id1]);
        $issue2 = $DB->get_record($tablename, ['id' => $id2]);
        $issue3 = $DB->get_record($tablename, ['id' => $id3]);

        $this->assertEquals('{"userfullname":"User 01"}', $issue1->data);
        $this->assertEquals('{"userfullname":"User 02"}', $issue2->data);
        $this->assertEquals('{"userfullname":"User 03"}', $issue3->data);
    }

    /**
     * Test for tool_certificate_delete_certificates_with_missing_context()
     *
     * @covers ::tool_certificate_delete_certificates_with_missing_context
     */
    public function test_tool_certificate_delete_certificates_with_missing_context() {
        global $DB;
        $this->resetAfterTest();

        // Create certificate with pages, elements, and issues.
        $othercategory = $this->getDataGenerator()->create_category();
        $othercontext = context_coursecat::instance($othercategory->id);
        $certificate1 = $this->get_generator()->create_template([
            'name' => 'My certificate',
            'contextid' => $othercontext->id,
        ]);
        $page1 = $this->get_generator()->create_page($certificate1);
        $page2 = $this->get_generator()->create_page($certificate1);
        $this->get_generator()->create_element($page1->get_id(), 'text', ['text' => 'Text element for page 1']);
        $this->get_generator()->create_element($page2->get_id(), 'text', ['text' => 'Text element for page 2']);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $certificate1->issue_certificate($user1->id);
        $certificate1->issue_certificate($user2->id);

        // Sanity check.
        $this->assertEquals(1, $DB->count_records('tool_certificate_templates'));
        $this->assertEquals(2, $DB->count_records('tool_certificate_pages'));
        $this->assertEquals(2, $DB->count_records('tool_certificate_elements'));
        $this->assertEquals(2, $DB->count_records('tool_certificate_issues'));

        // Delete context and go through upgrade.
        $DB->delete_records('context', ['id' => $othercontext->id]);
        tool_certificate_delete_certificates_with_missing_context();

        // Test all related data cleanup.
        $this->assertEquals(0, $DB->count_records('tool_certificate_templates'));
        $this->assertEquals(0, $DB->count_records('tool_certificate_pages'));
        $this->assertEquals(0, $DB->count_records('tool_certificate_elements'));
        $this->assertEquals(0, $DB->count_records('tool_certificate_issues'));
    }

    /**
     * Test for test_tool_certificate_delete_orphaned_issue_files()
     *
     * @covers ::tool_certificate_delete_orphaned_issue_files
     */
    public function test_tool_certificate_delete_orphaned_issue_files() {
        global $DB;
        $this->resetAfterTest();
        $systemcontext = \context_system::instance();
        $fs = get_file_storage();

        // Create certificate, users and issues.
        $certificate1 = $this->get_generator()->create_template([
            'name' => 'My certificate',
            'contextid' => $systemcontext->id,
        ]);
        $user = $this->getDataGenerator()->create_user();
        $issueid = $certificate1->issue_certificate($user->id);

        // Sanity check.
        $this->assertEquals(1, $DB->count_records('tool_certificate_templates'));
        $files = $fs->get_area_files($systemcontext->id, 'tool_certificate', 'issues', $issueid, '', false);
        $this->assertCount(1, $files);

        // Go through upgrade and check file was not removed.
        tool_certificate_delete_orphaned_issue_files();
        $files = $fs->get_area_files($systemcontext->id, 'tool_certificate', 'issues', $issueid, '', false);
        $this->assertCount(1, $files);

        // Delete issue record and go through upgrade.
        $DB->delete_records('tool_certificate_issues', ['id' => $issueid]);
        tool_certificate_delete_orphaned_issue_files();

        // Check file was removed.
        $files = $fs->get_area_files($systemcontext->id, 'tool_certificate', 'issues', $issueid, '', true);
        $this->assertCount(0, $files);
    }

    /**
     * Test for tool_certificate_fix_orphaned_template_element_files()
     *
     * @covers ::tool_certificate_fix_orphaned_template_element_files
     */
    public function test_tool_certificate_fix_orphaned_template_element_files() {
        $this->resetAfterTest();

        $fs = get_file_storage();
        $cat1 = $this->getDataGenerator()->create_category();
        $cat1context = context_coursecat::instance($cat1->id);
        $cat2 = $this->getDataGenerator()->create_category();
        $cat2context = context_coursecat::instance($cat2->id);
        // Create a template in category2.
        $template1 = $this->get_generator()->create_template((object)['name' => 'Template 1',
            'contextid' => context_coursecat::instance($cat2->id)->id]);
        $page1 = $this->get_generator()->create_page($template1);

        $imageelement1 = $this->get_generator()->create_element($page1->get_id(), 'image');

        // Create a dummy orphaned image file for element1 in a wrong template context (category1).
        $file1record = ['contextid' => $cat1context->id, 'component' => 'tool_certificate', 'filearea' => 'element',
            'itemid' => $imageelement1->get_id(), 'filepath' => '/', 'filename' => 'image1.png'];
        $file1 = $fs->create_file_from_string($file1record, 'Awesome photography');
        $file1content = $file1->get_content();

        // Sanity check. image file1 is in wrong category1 context.
        $imageelementfiles = $fs->get_area_files($cat1context->id, 'tool_certificate', 'element',
            $imageelement1->get_id(), '', false);
        $this->assertEquals($file1content, reset($imageelementfiles)->get_content());

        $imageelement2 = $this->get_generator()->create_element($page1->get_id(), 'image');

        // Create a dummy orphaned image file for element2 in a wrong template context (category1).
        $file2record = ['contextid' => $cat1context->id, 'component' => 'tool_certificate', 'filearea' => 'element',
            'itemid' => $imageelement2->get_id(), 'filepath' => '/', 'filename' => 'image2.png'];
        $file2 = $fs->create_file_from_string($file2record, 'Even more awesome photography');
        $file2content = $file2->get_content();

        // Create a dummy image file for element2 in the correct template context (category1), so we can check that upgrade script
        // is just removing the old file, and not trying to move it.
        $file3record = ['contextid' => $cat2context->id, 'component' => 'tool_certificate', 'filearea' => 'element',
            'itemid' => $imageelement2->get_id(), 'filepath' => '/', 'filename' => 'image2.png'];
        $file3 = $fs->create_file_from_string($file3record, 'Even more awesome photography');
        $file3content = $file3->get_content();

        // Go through upgrade.
        tool_certificate_fix_orphaned_template_element_files();

        // Check element1 image file is not in category1 context.
        $imageelementfiles = $fs->get_area_files($cat1context->id, 'tool_certificate', 'element',
            $imageelement1->get_id(), '', false);
        $this->assertEmpty($imageelementfiles);
        // Check element1 image file is now in category2 context.
        $imageelementfiles = $fs->get_area_files($cat2context->id, 'tool_certificate', 'element',
            $imageelement1->get_id(), '', false);
        $this->assertEquals($file1content, reset($imageelementfiles)->get_content());

        // Check element2 image file was removed from category1 context.
        $imageelementfiles = $fs->get_area_files($cat1context->id, 'tool_certificate', 'element',
            $imageelement2->get_id(), '', false);
        $this->assertEmpty($imageelementfiles);
        // Check element2 image file is still in category2 context.
        $imageelementfiles = $fs->get_area_files($cat2context->id, 'tool_certificate', 'element',
            $imageelement2->get_id(), '', false);
        $this->assertEquals($file3content, reset($imageelementfiles)->get_content());
    }
}
