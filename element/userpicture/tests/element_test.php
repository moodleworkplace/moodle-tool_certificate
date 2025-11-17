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

namespace certificateelement_userpicture;

use advanced_testcase;
use tool_certificate_generator;
use core_text;

/**
 * Unit tests for userpicture element.
 *
 * @package    certificateelement_userpicture
 * @group      tool_certificate
 * @covers     \certificateelement_userpicture\element
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class element_test extends advanced_testcase {

    /**
     * Test set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Get certificate generator
     * @return tool_certificate_generator
     */
    protected function get_generator(): tool_certificate_generator {
        return $this->getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    /**
     * Test render_html
     */
    public function test_render_html(): void {
        $this->setAdminUser();
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $pageid = $this->get_generator()->create_page($certificate1)->get_id();

        $element = ['name' => 'Test', 'width' => 0, 'height' => 0];
        $e = $this->get_generator()->create_element($pageid, 'userpicture', $element);
        $this->assertNotEmpty($e->render_html());

        $element = ['name' => 'Test', 'width' => 100, 'height' => 200];
        $e = $this->get_generator()->create_element($pageid, 'userpicture', $element);
        $this->assertTrue(strpos($e->render_html(), 'img') !== false);

        $element = ['name' => 'Test', 'width' => 0, 'height' => 200];
        $e = $this->get_generator()->create_element($pageid, 'userpicture', $element);
        $this->assertTrue(strpos($e->render_html(), 'width') !== false);
        $this->assertTrue(strpos($e->render_html(), 'height') !== false);

        $element = ['name' => 'Test', 'width' => 100, 'height' => 0];
        $e = $this->get_generator()->create_element($pageid, 'userpicture', $element);
        $this->assertTrue(strpos($e->render_html(), 'width') !== false);
        $this->assertTrue(strpos($e->render_html(), 'height') !== false);

        $element = ['name' => 'Test', 'width' => 0, 'height' => 0];
        $e = $this->get_generator()->create_element($pageid, 'userpicture', $element);
        $this->assertEquals(0, strpos($e->render_html(), '<img'));

        // Generate PDF for preview.
        $filecontents = $this->get_generator()->generate_pdf($certificate1, true);
        $this->assertGreaterThan(30000, core_text::strlen($filecontents, '8bit'));

        // Generate PDF for issue.
        $issue = $this->get_generator()->issue($certificate1, $this->getDataGenerator()->create_user());
        $filecontents = $this->get_generator()->generate_pdf($certificate1, false, $issue);
        $this->assertGreaterThan(30000, core_text::strlen($filecontents, '8bit'));
    }

    /**
     * Tests that the edit element form can be initiated without any errors
     */
    public function test_edit_element_form(): void {
        $this->setAdminUser();

        preg_match('|^certificateelement_(\w*)\\\\|', get_class($this), $matches);
        $form = $this->get_generator()->create_template_and_edit_element_form($matches[1]);
        $this->assertNotEmpty($form->render());
    }
}
