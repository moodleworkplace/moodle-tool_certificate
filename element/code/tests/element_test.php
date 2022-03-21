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
 * @package    certificateelement_code
 * @category   test
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace certificateelement_code;

use advanced_testcase;
use tool_certificate_generator;
use moodle_url;
use core_text;

/**
 * Unit tests for code element.
 *
 * @package    certificateelement_code
 * @group      tool_certificate
 * @covers     \certificateelement_code\element
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element_test extends advanced_testcase {

    /**
     * Test set up.
     */
    public function setUp(): void {
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
    public function test_render_html_content() {
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $pageid = $this->get_generator()->create_page($certificate1)->get_id();
        $e1 = $this->get_generator()->create_element($pageid, 'code',
            ['display' => \certificateelement_code\element::DISPLAY_CODE]);
        $e2 = $this->get_generator()->create_element($pageid, 'code',
            ['display' => \certificateelement_code\element::DISPLAY_CODELINK]);
        $e3 = $this->get_generator()->create_element($pageid, 'code',
            ['display' => \certificateelement_code\element::DISPLAY_URL]);
        $e4 = $this->get_generator()->create_element($pageid, 'code',
            ['display' => \certificateelement_code\element::DISPLAY_QRCODE]);

        // We don't know what the generated code will be, so match it's pattern.
        $coderegex = '([A-Za-z0-9]{12})';
        $urlregex = preg_quote(new moodle_url('/admin/tool/certificate/index.php')) .
            '\?code=' . $coderegex;

        // Display is DISPLAY_CODE.
        $e1output = strip_tags($e1->render_html(), '<a>');
        $this->assertEquals(1, preg_match('|^' . $coderegex  . '$|', $e1output));

        // Display is DISPLAY_CODELINK.
        $e2output = strip_tags($e2->render_html(), '<a>');
        $this->assertEquals(1, preg_match('|^\<a href="' . $urlregex . '"\>' . $coderegex  . '\</a\>$|', $e2output));

        // Display is DISPLAY_URL.
        $e3output = strip_tags($e3->render_html(), '<a>');
        $this->assertEquals(1, preg_match('|^' . $urlregex . '$|', $e3output));

        // Display is DISPLAY_QRCODE.
        $this->assertTrue(strpos($e4->render_html(), '<img') !== false);

        // Generate PDF for preview.
        $filecontents = $this->get_generator()->generate_pdf($certificate1, true);
        $filesize = core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 90000);

        // Generate PDF for issue.
        $issue = $this->get_generator()->issue($certificate1, $this->getDataGenerator()->create_user());
        $filecontents = $this->get_generator()->generate_pdf($certificate1, false, $issue);
        $filesize = core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 90000);
    }

    /**
     * Test save_unique_data
     */
    public function test_save_unique_data() {
        global $DB;
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $pageid = $this->get_generator()->create_page($certificate1)->get_id();
        $e = $this->get_generator()->new_element($pageid, 'code');
        $newdata = (object)['display' => \certificateelement_code\element::DISPLAY_CODE];
        $expected = json_encode($newdata);
        $e->save_form_data($newdata);
        $el = $DB->get_record('tool_certificate_elements', ['id' => $e->get_id()]);
        $this->assertEquals($expected, $el->data);
    }
}
