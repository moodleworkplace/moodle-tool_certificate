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

namespace certificateelement_date;

use advanced_testcase;
use tool_certificate_generator;
use core_text;

/**
 * Unit tests for date element.
 *
 * @package    certificateelement_date
 * @group      tool_certificate
 * @covers     \certificateelement_date\element
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
    public function test_render_html() {
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $pageid = $this->get_generator()->create_page($certificate1)->get_id();
        $formdata = ['name' => 'Date element', 'dateitem' => \certificateelement_date\element::CUSTOMCERT_DATE_ISSUE,
            'dateformat' => 'strftimedateshort'];
        $e = $this->get_generator()->create_element($pageid, 'date', $formdata);
        $this->assertNotEmpty($e->render_html());

        $formdata['dateitem'] = \certificateelement_date\element::CUSTOMCERT_DATE_EXPIRY;
        $formdata['dateformat'] = 'strftimedateshort';
        $e = $this->get_generator()->create_element($pageid, 'date', $formdata);
        $this->assertNotEmpty($e->render_html());

        // Generate PDF for preview.
        $filecontents = $this->get_generator()->generate_pdf($certificate1, true);
        $filesize = core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 90000);

        // Generate PDF for issue.
        $issue = $this->get_generator()->issue($certificate1, $this->getDataGenerator()->create_user(), time() + YEARSECS);
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
        $e = $this->get_generator()->new_element($pageid, 'date');
        $newdata = (object)['dateitem' => \certificateelement_date\element::CUSTOMCERT_DATE_ISSUE,
                            'dateformat' => 'strftimedate'];
        $expected = json_encode($newdata);
        $e->save_form_data($newdata);
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
