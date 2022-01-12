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

namespace tool_certificate;

use advanced_testcase;
use tool_certificate_generator;
use context_system;
use context_course;

/**
 * Tests for functions in lib.php
 *
 * @package     tool_certificate
 * @copyright   2020 Mikel Martín <mikel@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class permission_test extends advanced_testcase {
    /**
     * @var \stdClass
     */
    private $course1;
    /**
     * @var \tool_certificate\template
     */
    private $template1;
    /**
     * @var \tool_certificate\template
     */
    private $template3;
    /**
     * @var \tool_certificate\template
     */
    private $template5;

    /**
     * Get certificate generator
     * @return tool_certificate_generator
     */
    protected function get_generator() : tool_certificate_generator {
        return $this->getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    /**
     * Set up
     */
    public function setUp(): void {
        // Create category tree.
        $cat1 = $this->getDataGenerator()->create_category();
        $cat2 = $this->getDataGenerator()->create_category(['parent' => $cat1->id]);
        $cat3 = $this->getDataGenerator()->create_category(['parent' => $cat2->id]);

        // Create templates.
        $this->template1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $this->template2 = $this->get_generator()->create_template((object)['name' => 'Certificate 2']);
        $this->template3 = $this->get_generator()->create_template((object)['name' => 'Certificate 3', 'categoryid' => $cat1->id]);
        $this->template4 = $this->get_generator()->create_template((object)['name' => 'Certificate 4', 'categoryid' => $cat2->id]);
        $this->template5 = $this->get_generator()->create_template((object)['name' => 'Certificate 5', 'categoryid' => $cat3->id]);

        // Create course.
        $this->course1 = $this->getDataGenerator()->create_course(['category' => $cat3->id]);

        /*
         * Now we have
         * System context
         *      $template1
         *      $template2
         *      $category1
         *          $template3
         *          $category2
         *              $template2
         *              $category3
         *                  $template3
         *                  $template4
         * structure.
         */

        $this->resetAfterTest();
    }

    /**
     * Test for get_visible_templates as admin user.
     * @covers \tool_certificate\permission::get_visible_categories_contexts
     */
    public function test_get_visible_templates_as_admin() {
        $this->setAdminUser();

        // Check admin user can see all the templates.
        $visibletemplates = \tool_certificate\permission::get_visible_templates(context_system::instance());
        $this->assertCount(5, $visibletemplates);
    }

    /**
     * Test for get_visible_templates as teacher user.
     * @covers \tool_certificate\permission::get_visible_categories_contexts
     */
    public function test_get_visible_templates_as_teacher() {
        // Creater user with role 'editingteacher'.
        $user1 = $this->getDataGenerator()->create_and_enrol($this->course1, 'editingteacher');
        $this->setUser($user1);

        $visibletemplates = \tool_certificate\permission::get_visible_templates(context_course::instance($this->course1->id));

        // Sanity check.
        $this->assertCount(0, $visibletemplates);

        // Update template1 to 'shared'.
        (new \tool_certificate\persistent\template($this->template1->get_id(), (object)['shared' => true]))->save();
        $visibletemplates = \tool_certificate\permission::get_visible_templates(context_course::instance($this->course1->id));
        // Check that template1 is now visible for the user.
        $this->assertCount(1, $visibletemplates);
        $this->assertEquals($this->template1->get_name(), $visibletemplates[$this->template1->get_id()]->name);

        // Update template3 to 'shared'.
        (new \tool_certificate\persistent\template($this->template3->get_id(), (object)['shared' => true]))->save();
        $visibletemplates = \tool_certificate\permission::get_visible_templates(context_course::instance($this->course1->id));
        // Check that template1 and template3 are now visible for the user.
        $this->assertCount(2, $visibletemplates);
        $this->assertEquals($this->template3->get_name(), $visibletemplates[$this->template3->get_id()]->name);

        // Update template5 to 'shared'.
        (new \tool_certificate\persistent\template($this->template5->get_id(), (object)['shared' => true]))->save();
        $visibletemplates = \tool_certificate\permission::get_visible_templates(context_course::instance($this->course1->id));
        // Check that template1, template3 and template5 are now visible for the user.
        $this->assertCount(3, $visibletemplates);
        $this->assertEquals($this->template5->get_name(), $visibletemplates[$this->template5->get_id()]->name);

        // Update template1 to not 'shared'.
        (new \tool_certificate\persistent\template($this->template1->get_id(), (object)['shared' => false]))->save();
        $visibletemplates = \tool_certificate\permission::get_visible_templates(context_course::instance($this->course1->id));
        // Check that template1 is not visible for the user now.
        $this->assertCount(2, $visibletemplates);
        $this->assertArrayNotHasKey($this->template1->get_id(), $visibletemplates);
    }
}
