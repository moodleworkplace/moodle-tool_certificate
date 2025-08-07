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

declare(strict_types=1);

namespace tool_certificate\output;

use tool_certificate\my_certificates_table;

/**
 * Tests for Certificate manager
 *
 * @package    tool_certificate
 * @category   test
 * @copyright  2025 Tasio Bertomeu Gomez <tasio.bertomeu@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_certificate\output\mobile
 */
final class mobile_test extends \advanced_testcase {

    /**
     * Test for mobile_my_certificates_data()
     */
    public function test_mobile_my_certificates_data(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('show_shareonlinkedin', my_certificates_table::SHOW_LINK_TO_VERIFICATION_PAGE, 'tool_certificate');

        // Create a user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Create a certificate template.
        $template = (object)[
            'name' => 'Test template',
            'timecreated' => time(),
            'timemodified' => time(),
            'contextid' => \context_system::instance()->id,
        ];
        $templateid = $DB->insert_record('tool_certificate_templates', dataobject: $template);

        // Create certificate issues for the user.
        $issue = (object)[
            'userid' => $user->id,
            'courseid' => 1,
            'code' => 'TESTCODE',
            'expires' => 0,
            'templateid' => $templateid,
            'timecreated' => time(),
            'contextid' => \context_system::instance()->id,
        ];
        $issue2 = (object)[
            'userid' => $user->id,
            'code' => 'TESTCODE2',
            'expires' => 946684800,
            'templateid' => $templateid,
            'timecreated' => time(),
            'contextid' => \context_system::instance()->id,
        ];
        $DB->insert_record('tool_certificate_issues', $issue);
        $DB->insert_record('tool_certificate_issues', $issue2);

        // Call the mobile_my_certificates_data method.
        $result = mobile::mobile_my_certificates_data((int) $user->id);

        // Check the result.
        $this->assertIsArray($result);
        $this->assertEquals(true, $result['hascertificates']);
        $this->assertEquals(true, $result['canverify']);
        $this->assertEquals(true, $result['showshareonlinkedin']);
        $this->assertEquals('', $result['viewmore']);

        // Check the certificate issued first.
        $certificate = $result['certificates'][1];
        $this->assertEquals($template->name, $certificate['name']);
        $this->assertEquals('PHPUnit test site', $certificate['coursename']);
        $this->assertEquals($issue->expires, $certificate['expires']);
        $this->assertEquals(false, $certificate['isexpired']);
        $this->assertEquals($issue->code, $certificate['code']);
        $this->assertEquals(
            "https://www.example.com/moodle/admin/tool/certificate/index.php?code=TESTCODE",
            $certificate['verifyurl']
        );
        $this->assertEquals(
            "https://www.example.com/moodle/admin/tool/certificate/view.php?code=TESTCODE",
            $certificate['fileurl']
        );
        $expectedshareurl = "https://www.linkedin.com/profile/add?name=Test%20template&issueYear=" . date('Y')
            . "&issueMonth=" .date('m') ."&certId=TESTCODE&certUrl=https%3A%2F%2Fwww.example.com%2Fmoodle%2Fadmin%2F"
            . "tool%2Fcertificate%2Findex.php%3Fcode%3DTESTCODE";
        $this->assertEquals($expectedshareurl, $certificate['shareurl']);

        // Check the certificate issued last.
        $this->assertEquals(true, $result['certificates'][0]['isexpired']);
    }

    /**
     * Test for view_more_url()
     */
    public function test_view_more_url(): void {
        global $DB;
        $this->resetAfterTest();
        $certificates = [['id' => 1]];
        $user = $this->getDataGenerator()->create_user();
        $result = mobile::view_more_url($certificates, (int)$user->id);
        // Vier more should only be displayed when there are more certs than "certificate::ISSUES_PER_PAGE".
        $this->assertNull($result);

        // Create a certificate template.
        $template = (object)[
            'name' => 'Test template',
            'timecreated' => time(),
            'timemodified' => time(),
            'contextid' => \context_system::instance()->id,
        ];
        $templateid = $DB->insert_record('tool_certificate_templates', $template);
        // Create more certs than "certificate::ISSUES_PER_PAGE" for the user.
        for ($i = 0; $i < 21; $i++) {
            $issue = (object)[
                'userid' => $user->id,
                'code' => 'TESTCODE' . $i,
                'expires' => 0,
                'templateid' => $templateid,
                'timecreated' => time(),
                'contextid' => \context_system::instance()->id,
            ];
            $DB->insert_record('tool_certificate_issues', $issue);
        }
        $data = mobile::mobile_my_certificates_data((int)$user->id);
        $result = mobile::view_more_url($data['certificates'], (int)$user->id);
        $this->assertEquals('https://www.example.com/moodle/admin/tool/certificate/my.php', $result);
    }
}
