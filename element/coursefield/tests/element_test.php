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
 * Unit tests for coursefield element.
 *
 * @package    certificateelement_coursefield
 * @category   test
 * @copyright  2020 Mikel Martín <mikel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for coursefield element.
 *
 * @package    certificateelement_coursefield
 * @group      tool_certificate
 * @copyright  2020 Mikel Martín <mikel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_certificate_coursefield_element_test_testcase extends advanced_testcase {

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
     * Test render_html
     */
    public function test_render_html() {
        global $USER, $DB, $CFG;

        require_once($CFG->dirroot.'/user/profile/lib.php');

        $this->setAdminUser();
        $dg = $this->getDataGenerator();

        $catid = $dg->create_custom_field_category([])->get('id');
        $cfid = $dg->create_custom_field(['categoryid' => $catid, 'type' => 'text', 'shortname' => 'credits'])->get('id');

        $course1 = $dg->create_course([
            'shortname' => 'C01',
            'fullname' => 'Course 01',
            'summary' => 'Course Summary',
            'summaryformat' => FORMAT_MOODLE,
            'customfield_credits' => '20',
        ]);

        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1', 'courseid' => $course1->id]);
        $pageid = $this->get_generator()->create_page($certificate1)->get_id();
        $element1 = $this->get_generator()->create_element($pageid, 'coursefield',
            (object)['coursefield' => 'fullname', 'name' => 'Course name']);
        $element2 = $this->get_generator()->create_element($pageid, 'coursefield',
            (object)['coursefield' => 'shortname', 'name' => 'Course shortname']);
        $element3 = $this->get_generator()->create_element($pageid, 'coursefield',
            (object)['coursefield' => 'summary', 'name' => 'Course summary']);
        $element4 = $this->get_generator()->create_element($pageid, 'coursefield',
            (object)['coursefield' => $cfid, 'name' => 'Course credits']);

        $this->assertEquals('fullname', strip_tags($element1->render_html()));
        $this->assertEquals('shortname', strip_tags($element2->render_html()));
        $this->assertEquals('summary', strip_tags($element3->render_html()));
        $this->assertEquals($cfid, strip_tags($element4->render_html()));

        // Generate PDF for preview.
        $filecontents = $this->get_generator()->generate_pdf($certificate1, true);
        $filesize = core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 70000);

        // Generate PDF for issue.
        $issue = $this->get_generator()->issue($certificate1, $this->getDataGenerator()->create_user());
        $filecontents = $this->get_generator()->generate_pdf($certificate1, false, $issue);
        $filesize = core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 70000);
    }
}
