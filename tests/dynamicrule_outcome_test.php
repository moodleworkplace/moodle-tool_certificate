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
 * File contains the unit tests for outcome\certificate class.
 *
 * @package    tool_certificate
 * @category   test
 * @copyright  2019 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for outcome\certificate  class.
 *
 * @package    tool_certificate
 * @group      tool_certificate
 * @copyright  2019 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_certificate_outcome_certificate_testcase extends advanced_testcase {

    /**
     * Set up
     */
    public function setUp() {
        $this->resetAfterTest();
        $this->certgenerator = self::getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    /**
     * Get dynamic rule generator
     *
     * @return tool_dynamicrule_generator
     */
    protected function get_generator(): tool_dynamicrule_generator {
        return $this->getDataGenerator()->get_plugin_generator('tool_dynamicrule');
    }

    /**
     * Test get_title
     */
    public function test_get_title() {
        $outcome = new \tool_certificate\tool_dynamicrule\outcome\certificate();
        $this->assertNotEmpty($outcome->get_title());
    }

    /**
     * Test get_category
     */
    public function test_get_category() {
        $outcome = new \tool_certificate\tool_dynamicrule\outcome\certificate();
        $this->assertEquals(get_string('pluginname', 'tool_certificate'), $outcome->get_category());
    }

    /**
     * Test apply_to_users
     */
    public function test_apply_to_users() {
        global $DB;

        $rule0 = $this->get_generator()->create_rule();

        $configdata = ['currentdate' => time(), 'operator' => 'after'];
        \tool_dynamicrule\tool_dynamicrule\condition\current_date::create($rule0->id, $configdata);

        $certificate = $this->certgenerator->create_template((object)['name' => 'Test template']);

        $configdata = ['certificate' => $certificate->get_id()];
        $outcome = \tool_certificate\tool_dynamicrule\outcome\certificate::create($rule0->id, $configdata);

        $userids = [$this->getDataGenerator()->create_user(), $this->getDataGenerator()->create_user()];
        $outcome->apply_to_users($userids);

        $this->assertEquals(2, $DB->count_records('tool_certificate_issues'));
    }

    /**
     * Test get_description.
     */
    public function test_get_description() {

        $rule0 = $this->get_generator()->create_rule();

        $name = 'Test certificate 1';
        $certificate = $this->certgenerator->create_template((object)['name' => $name]);

        $configdata = ['certificate' => $certificate->get_id()];
        $outcome = \tool_certificate\tool_dynamicrule\outcome\certificate::create($rule0->id, $configdata);

        $str = get_string('outcomecertificatedescription', 'tool_certificate', $name);
        $this->assertEquals($str, $outcome->get_description());
    }
}
