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
 * Unit tests for userfield element.
 *
 * @package    certificateelement_userfield
 * @category   test
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for userfield element.
 *
 * @package    certificateelement_userfield
 * @group      tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_certificate_userfield_element_test_testcase extends advanced_testcase {

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
        global $USER, $DB, $CFG;

        require_once($CFG->dirroot.'/user/profile/lib.php');

        $this->setAdminUser();

        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $pageid = $certificate1->add_page();
        $element = $certificate1->new_element_for_page_id($pageid, 'userfield');

        $formdata = (object)['name' => 'User email element', 'data' => 'email'];
        $e = $this->get_generator()->new_element($pageid, 'userfield', $formdata);

        $this->assertTrue(strpos($e->render_html(), '@') !== false);

        // Add a custom field of textarea type.
        $id1 = $DB->insert_record('user_info_field', array(
                'shortname' => 'frogdesc', 'name' => 'Description of frog', 'categoryid' => 1,
                'datatype' => 'textarea'));

        $formdata = (object)['name' => 'User custom field element', 'data' => $id1];
        $e = $this->get_generator()->new_element($pageid, 'userfield', $formdata);

        profile_save_data((object)['id' => $USER->id, 'profile_field_frogdesc' => 'Gryffindor']);

        $this->assertTrue(strpos($e->render_html(), 'Gryffindor') !== false);
    }
}
