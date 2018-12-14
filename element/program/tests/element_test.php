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
 * Unit tests for code element.
 *
 * @package    certificateelement_program
 * @category   test
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for code element.
 *
 * @package    certificateelement_program
 * @group      tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_certificate_program_element_test_testcase extends advanced_testcase {

    /**
     * Test set up.
     */
    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Get certificate generator
     * @return tool_certificate_generator
     */
    protected function get_generator() : tool_certificate_generator {
        return $this->getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    /**
     * Test format_preview_data
     */
    public function test_format_preview_data() {
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $pageid = $certificate1->add_page();
        $element = $certificate1->new_element_for_page_id($pageid, 'program');
        $element->data = json_encode(['display' => 'certificationname']);
        $e = \tool_certificate\element_factory::get_element_instance($element);
        $certificationstr = get_string('previewcertificationname', 'certificateelement_program');
        $this->assertTrue(strpos($e->format_preview_data(), $certificationstr) >= 0);

        $element->data = json_encode(['display' => 'programname']);
        $e = \tool_certificate\element_factory::get_element_instance($element);
        $this->assertTrue(strpos($e->format_preview_data(), get_string('previewprogramname', 'certificateelement_program')) >= 0);

        $element->data = json_encode(['display' => 'completiondate']);
        $e = \tool_certificate\element_factory::get_element_instance($element);
        $this->assertFalse(empty($e->format_preview_data()));

        $element->data = json_encode(['display' => 'completedcourses']);
        $e = \tool_certificate\element_factory::get_element_instance($element);
        $this->assertFalse(empty($e->format_preview_data()));
    }

    /**
     * Test format_preview_data
     */
    public function test_format_issue_data() {
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $pageid = $certificate1->add_page();
        $element = $certificate1->new_element_for_page_id($pageid, 'program');
        $element->data = json_encode(['display' => 'certificationname']);
        $e = \tool_certificate\element_factory::get_element_instance($element);

        $user1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $data = ['certificationname' => 'Certification 1', 'programname' => 'Program 1', 'completiondate' => time(),
                 'completedcourses' => [
                   $course1->id => $course1->fullname,
                   $course2->id => $course2->fullname,
                 ]];
        $issueid = $certificate1->issue_certificate($user1->id, null, $data, 'tool_program');

        $encodeddata = json_encode($data);

        $element->data = json_encode(['display' => 'certificationname']);
        $e = \tool_certificate\element_factory::get_element_instance($element);
        $this->assertEquals($data['certificationname'], $e->format_issue_data($encodeddata));

        $element->data = json_encode(['display' => 'programname']);
        $e = \tool_certificate\element_factory::get_element_instance($element);
        $this->assertEquals($data['programname'], $e->format_issue_data($encodeddata));

        $element->data = json_encode(['display' => 'completiondate']);
        $e = \tool_certificate\element_factory::get_element_instance($element);
        $this->assertEquals(userdate($data['completiondate'], get_string('strftimedate', 'langconfig'), 99, false),
                            $e->format_issue_data($encodeddata));

        $element->data = json_encode(['display' => 'completedcourses']);
        $e = \tool_certificate\element_factory::get_element_instance($element);
        $this->assertTrue(strpos($data['completedcourses'][$course1->id], $e->format_issue_data($encodeddata)) >= 0);
        $this->assertTrue(strpos($data['completedcourses'][$course2->id], $e->format_issue_data($encodeddata)) >= 0);
    }
}
