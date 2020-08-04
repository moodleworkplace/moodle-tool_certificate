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
 * File containing tests for functions in db/upgradelib.php
 *
 * @package     tool_certificate
 * @category    test
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for functions in db/upgradelib.php
 *
 * @package    tool_certificate
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_certificate_upgradelib_testcase extends advanced_testcase {

    /** @var string */
    protected $temptable = null;

    /**
     * After test ends
     */
    protected function tearDown() {
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
     * Test for function tool_certificate_upgrade_remove_tenant_field()
     */
    public function test_tool_certificate_upgrade_remove_tenant_field() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/admin/tool/certificate/db/upgradelib.php');

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
     */
    public function test_tool_certificate_upgrade_move_data_to_customfields() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/admin/tool/certificate/db/upgradelib.php');

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
     */
    public function test_tool_certificate_upgrade_store_fullname_in_data() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/admin/tool/certificate/db/upgradelib.php');

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
}
