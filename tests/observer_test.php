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

/**
 * Tests for functions in observer.php
 *
 * @package     tool_certificate
 * @covers      \tool_certificate_observer
 * @copyright   2020 Mikel Martín <mikel@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer_test extends advanced_testcase {
    /**
     * Test setup
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
     * Test issues with courseid are removed when course is deleted.
     *
     * @return void
     */
    public function test_course_deleted() {
        global $DB;

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $user1 = $this->getDataGenerator()->create_and_enrol($course1);
        $user2 = $this->getDataGenerator()->create_and_enrol($course1);

        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Template 01']);
        $certificate2 = $this->get_generator()->create_template((object)['name' => 'Template 02']);
        // Using dummy component name.
        $certificate1->issue_certificate($user1->id, null, [], 'mod_myawesomecert', $course1->id);
        $certificate2->issue_certificate($user1->id, null, [], 'mod_myawesomecert', $course1->id);
        $certificate2->issue_certificate($user2->id, null, [], 'mod_myawesomecert', $course1->id);

        $certificate1->issue_certificate($user1->id, null, [], 'mod_myawesomecert', $course2->id);

        $this->assertEquals(3, $DB->count_records('tool_certificate_issues', ['courseid' => $course1->id]));
        $this->assertEquals(1, $DB->count_records('tool_certificate_issues', ['courseid' => $course2->id]));

        ob_start();
        delete_course($course1);
        ob_end_clean();

        $this->assertEmpty($DB->count_records('tool_certificate_issues', ['courseid' => $course1->id]));
        $this->assertEquals(1, $DB->count_records('tool_certificate_issues', ['courseid' => $course2->id]));
    }
}
