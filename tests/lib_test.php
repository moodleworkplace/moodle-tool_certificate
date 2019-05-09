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
 * File containing tests for functions in lib.php
 *
 * @package     tool_certificate
 * @category    test
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for functions in lib.php
 *
 * @package    tool_certificate
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_certificate_lib_testcase extends advanced_testcase {

    /**
     * Test for callback 'wp_registration_stats'
     */
    public function test_registration_get_site_info() {
        global $CFG;
        $this->resetAfterTest(true);
        $origsiteinfo = $siteinfo = ['moodlerelease' => $CFG->release, 'url' => $CFG->wwwroot];
        component_class_callback('tool_wp\\registration', 'site_info', [&$siteinfo, false]);
        $appended = array_diff_key($siteinfo, $origsiteinfo);

        $this->assertNotNull($appended['wpcertificates']);
        $this->assertNotNull($appended['wpcertificatesissues']);

        $siteinfo = $origsiteinfo;
        component_class_callback('tool_wp\\registration', 'site_info', [&$siteinfo, true]);
    }
}
