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
 * Unit tests for date element.
 *
 * @package    certificateelement_date
 * @category   test
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for date element.
 *
 * @package    certificateelement_date
 * @group      tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_certificate_date_element_test_testcase extends advanced_testcase {

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
     * Test render_html
     */
    public function test_render_html() {
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $pageid = $certificate1->add_page();
        $data = json_encode(['dateitem' => \certificateelement_date\element::CUSTOMCERT_DATE_ISSUE, 'dateformat' => 0]);
        $formdata = (object)['name' => 'Date element', 'data' => $data];
        $e = $this->get_generator()->new_element($pageid, 'date', $formdata);
        $this->assertFalse(empty($e->render_html()));

        $data = json_encode(['dateitem' => \certificateelement_date\element::CUSTOMCERT_DATE_EXPIRY, 'dateformat' => 0]);
        $formdata->data = $data;
        $e = $this->get_generator()->new_element($pageid, 'date', $formdata);
        $this->assertFalse(empty($e->render_html()));
    }

    /**
     * Test save_unique_data
     */
    public function test_save_unique_data() {
        global $DB;
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $pageid = $certificate1->add_page();
        $e = $this->get_generator()->new_element($pageid, 'date');
        $newdata = (object)['dateitem' => \certificateelement_date\element::CUSTOMCERT_DATE_ISSUE,
                            'dateformat' => 'strftimedate'];
        $expected = json_encode($newdata);
        $e->save($newdata);
        $el = $DB->get_record('tool_certificate_elements', ['id' => $e->get_id()]);
        $this->assertEquals($expected, $el->data);
    }

    /**
     * Test get_date_formats
     */
    public function test_get_date_formats() {
        $this->assertFalse(empty(\certificateelement_date\element::get_date_formats()));
    }
}
