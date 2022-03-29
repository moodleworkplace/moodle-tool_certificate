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
 * Unit tests for code element.
 *
 * @package    certificateelement_program
 * @category   test
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace certificateelement_program;

use advanced_testcase;
use tool_certificate_generator;
use core_text;
use stdClass;

/**
 * Unit tests for code element.
 *
 * @package    certificateelement_program
 * @group      tool_certificate
 * @covers     \certificateelement_program\element
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element_test extends advanced_testcase {

    /**
     * Test set up.
     */
    public function setUp(): void {
        $this->resetAfterTest();
        \tool_certificate\customfield\issue_handler::reset_caches();
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
        $pageid = $this->get_generator()->create_page($certificate1)->get_id();
        $element = new stdClass();
        \tool_certificate\customfield\issue_handler::create()->ensure_field_exists('certificationname', 'text',
            'Certification name preview', true, 'Certification name preview');
        $element->data = json_encode(['display' => 'certificationname']);
        /** @var \certificateelement_program\element $e */
        $e = $this->get_generator()->new_element($pageid, 'program', $element);
        $this->assertTrue(strpos($e->format_preview_data(), 'Certification name preview') >= 0);

        \tool_certificate\customfield\issue_handler::create()->ensure_field_exists('programname', 'text',
            'Program name preview', true, 'Program name preview');
        $element->data = json_encode(['display' => 'programname']);
        $e = $this->get_generator()->new_element($pageid, 'program', $element);
        $this->assertTrue(strpos($e->format_preview_data(), 'Program name preview') >= 0);

        $element->data = json_encode(['display' => 'completiondate']);
        $e = $this->get_generator()->new_element($pageid, 'program', $element);
        $this->assertFalse(empty($e->format_preview_data()));

        $element->data = json_encode(['display' => 'completedcourses']);
        $e = $this->get_generator()->new_element($pageid, 'program', $element);
        $this->assertFalse(empty($e->format_preview_data()));
    }

    /**
     * Test format_issue_data
     */
    public function test_format_issue_data() {
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $pageid = $this->get_generator()->create_page($certificate1)->get_id();
        $element = new stdClass();

        // Create issue customfields.
        $handler = \tool_certificate\customfield\issue_handler::create();
        $handler->ensure_field_exists('certificationname', 'text',
            'Certification name preview', true, 'Certification name preview');
        $handler->ensure_field_exists('programname', 'text', 'Program name', true, 'Program name preview');
        $handler->ensure_field_exists('programcompletiondate', 'date', 'Program completion date', true,
            userdate(strtotime(date('Y-01-01')), get_string('strftimedatefullshort')), ['includetime' => false]);
        $handler->ensure_field_exists('programcompletedcourses', 'textarea', 'Courses completed in program', true,
            '<ul><li>C01</li><li>C02</li><li>C03</li></ul>'
        );

        $element->data = json_encode(['display' => 'certificationname']);
        /** @var \certificateelement_program\element $e */
        $e = $this->get_generator()->new_element($pageid, 'program', $element);

        $user1 = $this->getDataGenerator()->create_user();

        $data = ['certificationname' => 'Certification 1', 'programname' => 'Program 1', 'programcompletiondate' => '1/2/12',
                 'programcompletedcourses' => '<p>Course1,<br>Course2</p>'];
        $issueid = $certificate1->issue_certificate($user1->id, null, $data, 'tool_program');
        $issue = (object)['id' => $issueid];

        $element->data = json_encode(['display' => 'certificationname']);
        /** @var \certificateelement_program\element $e */
        $e = $this->get_generator()->new_element($pageid, 'program', $element);
        $this->assertEquals($data['certificationname'], $e->format_issue_data($issue));

        $element->data = json_encode(['display' => 'programname']);
        $e = $this->get_generator()->new_element($pageid, 'program', $element);
        $this->assertEquals($data['programname'], $e->format_issue_data($issue));

        $element->data = json_encode(['display' => 'programcompletiondate']);
        $e = $this->get_generator()->new_element($pageid, 'program', $element);
        $this->assertEquals('1/2/12', $e->format_issue_data($issue));

        $element->data = json_encode(['display' => 'programcompletedcourses']);
        $e = $this->get_generator()->new_element($pageid, 'program', $element);
        $this->assertEquals('<p>Course1,<br />Course2</p>', $e->format_issue_data($issue));
    }

    /**
     * Test save_unique_data
     */
    public function test_save_unique_data() {
        global $DB;
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $pageid = $this->get_generator()->create_page($certificate1)->get_id();
        $e = $this->get_generator()->new_element($pageid, 'program');
        $newdata = (object)['display' => 'certificationname'];
        $expected = json_encode($newdata);
        $e->save_form_data($newdata);
        $el = $DB->get_record('tool_certificate_elements', ['id' => $e->get_id()]);
        $this->assertEquals($expected, $el->data);
    }

    /**
     * Test rendering
     */
    public function test_render_content() {
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $pageid = $this->get_generator()->create_page($certificate1)->get_id();
        foreach (['programname', 'certificationname', 'completiondate', 'programcompletedcourses'] as $displaytype) {
            $formdata = ['display' => $displaytype];
            $e = $this->get_generator()->create_element($pageid, 'program', $formdata);
            $this->assertNotEmpty($e->render_html());
        }

        // Generate PDF for preview.
        $filecontents = $this->get_generator()->generate_pdf($certificate1, true);
        $filesize = core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 90000);

        // Generate PDF for issue.
        $issue = $this->get_generator()->issue($certificate1, $this->getDataGenerator()->create_user(),
            null, ['programname' => 'P', 'certificationname' => 'C', 'programcompletiondate' => '1/1/11',
                'programcompletedcourses' => 'list'], 'tool_certification');
        $filecontents = $this->get_generator()->generate_pdf($certificate1, false, $issue);
        $filesize = core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 90000);
    }
}
