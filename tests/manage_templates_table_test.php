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
 * File contains the unit tests for manage_templates_table class.
 *
 * @package    tool_certificate
 * @category   test
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for  manage_templates_table class.
 *
 * @package    tool_certificate
 * @group      tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_certificate_manage_templates_table_testcase extends advanced_testcase {

    /**
     * Test set up.
     */
    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Test query_db
     */
    public function test_query_db() {
        global $DB;

        // Create new certificate.
        $cert1name = 'Certificate 1';
        $certificate1 = \tool_certificate\template::create((object)['name' => $cert1name]);
        $table = new \tool_certificate\manage_templates_table();
        $table->query_db(10);
        $this->assertEquals(1, sizeof($table->rawdata));

        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
        $manager = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($managerrole->id, $manager->id);

        $this->setUser($manager);

        $table->query_db(10);
        $this->assertEquals(1, sizeof($table->rawdata));
    }
}
