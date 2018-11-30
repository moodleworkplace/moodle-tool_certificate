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
     * Test certificate template creation.
     */
    public function test_create() {
        global $DB;

        // There are no certificate templates in the beginning.
        $this->assertEquals(0, $DB->count_records('tool_certificate_templates'));

        // Create new certificate.
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $this->assertEquals(1, $DB->count_records('tool_certificate_templates'));

        // Create new certificate.
        $certificate2name = 'Certificate 2';
        $certificate2 = $this->get_generator()->create_template((object)['name' => $certificate2name]);
        $this->assertEquals(2, $DB->count_records('tool_certificate_templates'));

        $this->assertEquals($certificate2name, $certificate2->get_name());
        $this->assertEquals($certificate2name, $DB->get_field('tool_certificate_templates', 'name', ['id' => $certificate2->get_id()]));
    }

    /**
     * Test change a template name.
     */
    public function test_save() {
        // Create new certificate.
        $certname1 = 'Certificate 1';
        $certname2 = 'Certificate Updated';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname1]);
        $certificate1->save((object)['name' => $certname2]);
        $this->assertEquals($certname2, \tool_certificate\template::find_by_name($certname2)->get_name());
        $this->assertFalse(\tool_certificate\template::find_by_name($certname1));
    }

    /**
     * Find a certificate template given it's name.
     */
    public function test_find_by_name() {
        $certname = 'Certificate 1';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname]);
        $this->assertEquals($certname, \tool_certificate\template::find_by_name($certname)->get_name());
    }

    /**
     * Find a certificate template given it's id.
     */
    public function test_find_by_id() {
        $certname = 'Certificate 1';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname]);
        $this->assertEquals($certname, \tool_certificate\template::find_by_id($certificate1->get_id())->get_name());
    }

    /**
     * Test duplicate a template.
     */
    public function test_duplicate() {
        $certname = 'Certificate 1';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname]);
        $certificate2 = $certificate1->duplicate();
        $this->assertEquals($certname . ' (' . strtolower(get_string('duplicate', 'tool_certificate')) . ')', $certificate2->get_name());
        $this->assertFalse($certificate1->get_id() == $certificate2->get_id());
    }

    /**
     * Test delete an empty template.
     */
    public function test_delete_empty_template() {
        global $DB;
        $certname = 'Certificate 1';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname]);
        $certificate1->delete();
        $this->assertEquals(0, $DB->count_records('tool_certificate_templates'));
    }

    /**
     * Test add page to template.
     */
    public function test_add_page() {
        global $DB;
        $certname = 'Certificate 1';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname]);
        $certificate1->add_page();
        $this->assertEquals(1, $DB->count_records('tool_certificate_pages', ['templateid' => $certificate1->get_id()]));
    }

    /**
     * Test save page.
     */
    public function test_save_page() {
        global $DB;
        $certname = 'Certificate 1';
        $certificate1 = $this->get_generator()->create_template((object)['name' => $certname]);
        $pageid = $certificate1->add_page();
        $pagedata = (object)['tid' => $certificate1->get_id(),
                             'pagewidth_'.$pageid => 333, 'pageheight_'.$pageid => 444,
                             'pageleftmargin_'.$pageid => 333, 'pagerightmargin_'.$pageid => 444];
        $certificate1->save_page($pagedata);
        $this->assertTrue($DB->record_exists('tool_certificate_pages', ['templateid' => $certificate1->get_id(),
            'width' => 333, 'height' => 444]));
    }
}
