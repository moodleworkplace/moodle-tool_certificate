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

use tool_certificate_generator;

/**
 * Tests for functions in /classes/my_certificates_table.php
 *
 * @package    tool_certificate
 * @covers     \tool_certificate\my_certificates_table
 * @copyright  2022 Frederik Pytlick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class my_certificates_table_test extends \advanced_testcase {
    /**
     * Test set up.
     */
    public function setUp(): void {
        $this->resetAfterTest();
    }

    /**
     * Test Name column
     */
    public function test_col_name(): void {
        /** @var tool_certificate_generator $certificategenerator */
        $certificategenerator = $this->getDataGenerator()->get_plugin_generator('tool_certificate');
        $user = self::getDataGenerator()->create_user();
        $course = self::getDataGenerator()->create_course();

        $template = $certificategenerator->create_template((object)['name' => 'Certificate 1']);
        // Generate row data with a certificate issue without courseid.
        $rowdata1 = $certificategenerator->issue($template, $user, null, []);
        // Generate row data with a certificate issue with courseid of a non-existing course.
        $rowdata2 = $certificategenerator->issue($template, $user, null, [], 'tool_certificate', 999);
        // Generate row data with a certificate issue with correct courseid.
        $rowdata3 = $certificategenerator->issue($template, $user, null, [], 'tool_certificate', $course->id);
        // Add extra attributes needed for rowdata.
        foreach ([$rowdata1, $rowdata2, $rowdata3] as $rowdata) {
            $rowdata->name = $template->get_formatted_name();
            $rowdata->contextid = \context_system::instance()->id;
        }

        $table = new my_certificates_table($user->id);
        $this->assertEquals($template->get_formatted_name(), $table->col_name($rowdata1));
        $this->assertEquals($template->get_formatted_name(), $table->col_name($rowdata2));
        $this->assertEquals($template->get_formatted_name() . ' - ' . $course->fullname, $table->col_name($rowdata3));
    }

    /**
     * Test LinkedIn column
     *
     * @dataProvider col_linkedin_provider
     *
     * @param object $issue
     * @param array $params
     * @param ?int $organizationid
     * @param int $showshareonlinkedin
     * @return void
     * @throws \moodle_exception
     */
    public function test_col_linkedin($issue, $params, $organizationid, $showshareonlinkedin): void {
        if ($organizationid) {
            set_config('linkedinorganizationid', $organizationid, 'tool_certificate');
        }

        if ($showshareonlinkedin) {
            set_config('show_shareonlinkedin', $showshareonlinkedin, 'tool_certificate');
        }

        $user = self::getDataGenerator()->create_user();
        $issue->userid = $user->id;

        $table = new my_certificates_table($user->id);

        $anchortag = $table->col_linkedin($issue);

        $link = $this->get_href_value_from_anchor_tag($anchortag);
        $url = new \moodle_url(my_certificates_table::LINKEDIN_ADD_TO_PROFILE_URL, $params);

        self::assertEquals($url->out(false), $link);
    }

    /**
     * Data provider for test_col_linkedin()
     *
     * @return array[]
     */
    public static function col_linkedin_provider(): array {
        return [
            [(object)[
                'id' => '6',
                'expires' => '0',
                'code' => '0123456789SS',
                'timecreated' => 1634376554,
                'templateid' => '1',
                'contextid' => '1',
                'name' => 'Certificate demo template',
            ], [
                'name' => 'Certificate demo template',
                'issueYear' => '2021',
                'issueMonth' => '10',
                'certId' => '0123456789SS',
                'certUrl' => 'https://www.example.com/moodle/admin/tool/certificate/index.php?code=0123456789SS',
            ], null, my_certificates_table::SHOW_LINK_TO_VERIFICATION_PAGE, ],
            [(object)[
                'id' => '6',
                'expires' => '0',
                'code' => '0123456789SS',
                'timecreated' => 1500370154,
                'templateid' => '1',
                'contextid' => '1',
                'name' => 'Certificate demo template',
            ], [
                'name' => 'Certificate demo template',
                'issueYear' => '2017',
                'issueMonth' => '07',
                'certId' => '0123456789SS',
                'certUrl' => 'https://www.example.com/moodle/admin/tool/certificate/index.php?code=0123456789SS',
                'organizationId' => '123',
            ], 123, my_certificates_table::SHOW_LINK_TO_VERIFICATION_PAGE, ],
            [(object)[
                'id' => '6',
                'expires' => '123',
                'code' => '0123456789SS',
                'timecreated' => 1568626154,
                'templateid' => '1',
                'contextid' => '1',
                'name' => 'Certificate demo template',
            ], [
                'name' => 'Certificate demo template',
                'issueYear' => '2019',
                'issueMonth' => '09',
                'certId' => '0123456789SS',
                'certUrl' => 'https://www.example.com/moodle/admin/tool/certificate/index.php?code=0123456789SS',
                'expirationYear' => '1970',
                'expirationMonth' => '01',
            ], null, my_certificates_table::SHOW_LINK_TO_VERIFICATION_PAGE, ],
            [(object)[
                'id' => '6',
                'expires' => '123',
                'code' => '0123456789SS',
                'timecreated' => 1705573754,
                'templateid' => '1',
                'contextid' => '1',
                'name' => 'Certificate demo template',
            ], [
                'name' => 'Certificate demo template',
                'issueYear' => '2024',
                'issueMonth' => '01',
                'certId' => '0123456789SS',
                'certUrl' => 'https://www.example.com/moodle/admin/tool/certificate/index.php?code=0123456789SS',
                'expirationYear' => '1970',
                'expirationMonth' => '01',
                'organizationId' => '123',
            ], 123, my_certificates_table::SHOW_LINK_TO_VERIFICATION_PAGE, ],
            [(object)[
                'id' => '6',
                'expires' => '0',
                'code' => '0123456789SS',
                'timecreated' => 1634376554,
                'templateid' => '1',
                'contextid' => '1',
                'name' => 'Certificate demo template',
            ], [
                'name' => 'Certificate demo template',
                'issueYear' => '2021',
                'issueMonth' => '10',
                'certId' => '0123456789SS',
                'certUrl' => 'https://www.example.com/moodle/admin/tool/certificate/view.php?code=0123456789SS',
            ], null, my_certificates_table::SHOW_LINK_TO_CERTIFICATE_PAGE, ],
            [(object)[
                'id' => '6',
                'expires' => '0',
                'code' => '0123456789SS',
                'timecreated' => 1500370154,
                'templateid' => '1',
                'contextid' => '1',
                'name' => 'Certificate demo template',
            ], [
                'name' => 'Certificate demo template',
                'issueYear' => '2017',
                'issueMonth' => '07',
                'certId' => '0123456789SS',
                'certUrl' => 'https://www.example.com/moodle/admin/tool/certificate/view.php?code=0123456789SS',
                'organizationId' => '123',
            ], 123, my_certificates_table::SHOW_LINK_TO_CERTIFICATE_PAGE, ],
            [(object)[
                'id' => '6',
                'expires' => '123',
                'code' => '0123456789SS',
                'timecreated' => 1568626154,
                'templateid' => '1',
                'contextid' => '1',
                'name' => 'Certificate demo template',
            ], [
                'name' => 'Certificate demo template',
                'issueYear' => '2019',
                'issueMonth' => '09',
                'certId' => '0123456789SS',
                'certUrl' => 'https://www.example.com/moodle/admin/tool/certificate/view.php?code=0123456789SS',
                'expirationYear' => '1970',
                'expirationMonth' => '01',
            ], null, my_certificates_table::SHOW_LINK_TO_CERTIFICATE_PAGE, ],
            [(object)[
                'id' => '6',
                'expires' => '123',
                'code' => '0123456789SS',
                'timecreated' => 1705573754,
                'templateid' => '1',
                'contextid' => '1',
                'name' => 'Certificate demo template',
            ], [
                'name' => 'Certificate demo template',
                'issueYear' => '2024',
                'issueMonth' => '01',
                'certId' => '0123456789SS',
                'certUrl' => 'https://www.example.com/moodle/admin/tool/certificate/view.php?code=0123456789SS',
                'expirationYear' => '1970',
                'expirationMonth' => '01',
                'organizationId' => '123',
            ], 123, my_certificates_table::SHOW_LINK_TO_CERTIFICATE_PAGE, ],
        ];
    }

    /**
     * Gets the href attribute value from an anchor tag
     *
     * @param string $anchortag
     * @return string
     */
    private function get_href_value_from_anchor_tag($anchortag): string {
        $matches = null;

        preg_match('/<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\1/', $anchortag, $matches);

        return $matches[2];
    }
}
