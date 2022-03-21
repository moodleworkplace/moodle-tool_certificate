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
 * File containing tests for functions in lib.php
 *
 * @package     tool_certificate
 * @category    test
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

use advanced_testcase;
use tool_certificate_generator;
use context_coursecat;
use \tool_certificate\persistent\element;
use \tool_certificate\persistent\page;
use \tool_certificate\persistent\template;

/**
 * Tests for functions in lib.php
 *
 * @package    tool_certificate
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lib_test extends advanced_testcase {
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
    protected function get_certificate_generator() : tool_certificate_generator {
        return $this->getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    /**
     * Test tool_certificate_can_course_category_delete.
     *
     * @covers ::tool_certificate_can_course_category_delete
     */
    public function test_can_course_category_delete() {
        $user = $this->getDataGenerator()->create_user();
        $roleid = create_role('Dummy role', 'dummyrole', 'dummy role description');
        $this->setUser($user);

        $cat1 = $this->getDataGenerator()->create_category(['name' => 'Cat1']);
        $cat2 = $this->getDataGenerator()->create_category(['name' => 'Cat2', 'parent' => $cat1->id]);
        $cat3 = $this->getDataGenerator()->create_category(['name' => 'Cat3', 'parent' => $cat2->id]);
        $cat4 = $this->getDataGenerator()->create_category(['name' => 'Cat4', 'parent' => $cat1->id]);

        $template1 = $this->get_certificate_generator()->create_template((object)['name' => 'Certificate 1',
            'contextid' => $cat1->get_context()->id]);
        $template2 = $this->get_certificate_generator()->create_template((object)['name' => 'Certificate 2',
            'contextid' => $cat3->get_context()->id]);

        /*
         * Now we have
         * $category1
         *      $template1
         *      $category2
         *          $category3
         *              $template2
         *      $category4
         * structure.
         */

        // Check 'can_course_category_delete' without capabilities in a category with templates.
        $this->assertFalse(tool_certificate_can_course_category_delete($cat1));
        // Check 'can_course_category_delete' without capabilities in a category without templates.
        $this->assertTrue(tool_certificate_can_course_category_delete($cat4));

        // Add capabilities and check again in a category with templates.
        $this->get_certificate_generator()->assign_manage_capability($user->id, $roleid, $cat1->get_context());
        $this->assertTrue(tool_certificate_can_course_category_delete($cat1));
    }

    /**
     * Test tool_certificate_can_course_category_delete_move.
     *
     * @covers ::tool_certificate_can_course_category_delete_move
     */
    public function test_can_course_category_delete_move() {
        $user = $this->getDataGenerator()->create_user();
        $roleid = create_role('Dummy role', 'dummyrole', 'dummy role description');
        $this->setUser($user);

        $cat1 = $this->getDataGenerator()->create_category(['name' => 'Cat1']);
        $cat2 = $this->getDataGenerator()->create_category(['name' => 'Cat2', 'parent' => $cat1->id]);
        $cat3 = $this->getDataGenerator()->create_category(['name' => 'Cat3', 'parent' => $cat2->id]);
        $cat4 = $this->getDataGenerator()->create_category(['name' => 'Cat4', 'parent' => $cat1->id]);

        $template1 = $this->get_certificate_generator()->create_template((object)['name' => 'Certificate 1',
            'contextid' => $cat1->get_context()->id]);
        $template2 = $this->get_certificate_generator()->create_template((object)['name' => 'Certificate 2',
            'contextid' => $cat3->get_context()->id]);

        /*
         * Now we have
         * $category1
         *      $template1
         *      $category2
         *          $category3
         *              $template2
         *      $category4
         * structure.
         */

        // Check 'can_course_category_delete_move' without capabilities in a category with templates.
        $this->assertFalse(tool_certificate_can_course_category_delete_move($cat3, $cat2));
        // Check 'can_course_category_delete_move' without capabilities in a category without templates.
        $this->assertTrue(tool_certificate_can_course_category_delete_move($cat4, $cat2));

        // Add capabilities in deleted categoty and check again in a category with templates.
        $this->get_certificate_generator()->assign_manage_capability($user->id, $roleid, $cat3->get_context());
        $this->assertFalse(tool_certificate_can_course_category_delete_move($cat3, $cat2));

        // Add capabilities also in destination categoty and check again in a category with templates.
        $this->get_certificate_generator()->assign_manage_capability($user->id, $roleid, $cat2->get_context());
        $this->assertTrue(tool_certificate_can_course_category_delete_move($cat3, $cat2));
    }

    /**
     * Test move/remove template on category deletion.
     *
     * @covers ::tool_certificate_can_course_category_delete
     * @covers ::tool_certificate_can_course_category_delete_move
     */
    public function test_delete_category_with_certificates() {
        $roleid = create_role('Dummy role', 'dummyrole', 'dummy role description');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $cat1 = $this->getDataGenerator()->create_category();
        $cat2 = $this->getDataGenerator()->create_category();
        $cat3 = $this->getDataGenerator()->create_category();
        $cat1context = context_coursecat::instance($cat1->id);
        $cat2context = context_coursecat::instance($cat2->id);
        $cat3context = context_coursecat::instance($cat3->id);

        // Create certificates with pages and elements (including files) so we can check they are moved/deleted correctly.
        $certificate1 = $this->get_certificate_generator()->create_template((object)[
            'name' => 'Certificate 1',
            'contextid' => $cat1context->id,
        ]);
        $certificatepage1 = $this->get_certificate_generator()->create_page($certificate1->get_id());
        $certificateelement1 = $this->get_certificate_generator()->create_element($certificatepage1->get_id(), 'text');

        $certificate2 = $this->get_certificate_generator()->create_template((object)[
            'name' => 'Certificate 2',
            'contextid' => $cat2context->id,
        ]);
        $certificatepage2 = $this->get_certificate_generator()->create_page($certificate2->get_id());
        $certificateelement2 = $this->get_certificate_generator()->create_element($certificatepage2->get_id(), 'image');

        $fs = get_file_storage();

        $filerecord = [
            'contextid' => $certificate2->get_context()->id,
            'component' => 'tool_certificate',
            'filearea' => 'element',
            'itemid' => $certificateelement2->get_id(),
            'filepath' => '/',
            'filename' => 'image.png'
        ];
        $fs->create_file_from_string($filerecord, 'Cat');

        // Check 'can_course_category_delete' without capabilities.
        $this->assertFalse(tool_certificate_can_course_category_delete($cat1));

        // Add capabilities and check again.
        $this->get_certificate_generator()->assign_manage_capability($user->id, $roleid, $cat1context);
        $this->assertTrue(tool_certificate_can_course_category_delete($cat1));

        // Delete cat1 with all its content.
        $cat1->delete_full();

        // Check certificate1, plus page and element, were all removed.
        $this->assertFalse(template::record_exists($certificate1->get_id()));
        $this->assertFalse(page::record_exists_select('templateid = ?', [$certificate1->get_id()]));
        $this->assertFalse(element::record_exists_select('pageid = ?', [$certificatepage1->get_id()]));

        // Check 'can_course_category_delete_move' without capabilities.
        $this->assertFalse(tool_certificate_can_course_category_delete_move($cat2, $cat3));

        // Add capabilities and check again.
        $this->get_certificate_generator()->assign_manage_capability($user->id, $roleid, $cat2context);
        $this->get_certificate_generator()->assign_manage_capability($user->id, $roleid, $cat3context);
        $this->assertTrue(tool_certificate_can_course_category_delete_move($cat2, $cat3));

        // Delete cat2 moving content to cat3.
        $cat2->delete_move($cat3->id);

        // Check certificate2 in now in cat3, along with the element file it contains.
        $certificatemoved = new template($certificate2->get_id());
        $this->assertEquals($cat3context->id, $certificatemoved->get('contextid'));

        $certificatemovedfiles = $fs->get_area_files($cat3context->id, 'tool_certificate', 'element',
            $certificateelement2->get_id(), 'filename', false);
        $this->assertCount(1, $certificatemovedfiles);
    }

    /**
     * Test category deletion for the purpose of callback behaviour with no certificates.
     *
     * @covers ::tool_certificate_can_course_category_delete
     * @covers ::tool_certificate_can_course_category_delete_move
     */
    public function test_delete_category_with_no_certificates() {
        $user = $this->getDataGenerator()->create_user();
        $roleid = create_role('Dummy role', 'dummyrole', 'dummy role description');
        $this->setUser($user);

        $cat1 = $this->getDataGenerator()->create_category();
        $cat2 = $this->getDataGenerator()->create_category();
        $cat3 = $this->getDataGenerator()->create_category();
        $cat1context = context_coursecat::instance($cat1->id);
        $cat2context = context_coursecat::instance($cat2->id);
        $cat3context = context_coursecat::instance($cat3->id);

        // Check 'can_course_category_delete'.
        $this->assertTrue(tool_certificate_can_course_category_delete($cat1));

        // Delete cat1 with all its content.
        $cat1->delete_full();

        // Check 'can_course_category_delete_move'.
        $this->assertTrue(tool_certificate_can_course_category_delete_move($cat2, $cat3));

        // Delete cat2 moving content to cat3.
        $cat2->delete_move($cat3->id);
    }
}
