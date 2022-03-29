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

namespace certificateelement_image;

use advanced_testcase;
use tool_certificate_generator;
use core_text;

/**
 * Unit tests for image element.
 *
 * @package    certificateelement_image
 * @group      tool_certificate
 * @covers     \certificateelement_image\element
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

    public function test_render_html_content() {
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $pageid = $this->get_generator()->create_page($certificate1)->get_id();
        $e = $this->get_generator()->create_element($pageid, 'image');
        // TODO: make a better test.
        $this->assertNotEmpty($e->render_html());

        // Generate PDF for preview.
        $filecontents = $this->get_generator()->generate_pdf($certificate1, true);
        $filesize = core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 70000);

        // Generate PDF for issue.
        $issue = $this->get_generator()->issue($certificate1, $this->getDataGenerator()->create_user(), time() + YEARSECS);
        $filecontents = $this->get_generator()->generate_pdf($certificate1, false, $issue);
        $filesize = core_text::strlen($filecontents);
        $this->assertTrue($filesize > 30000 && $filesize < 70000);
    }
}
