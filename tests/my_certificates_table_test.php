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

/**
 * Tests for functions in /classes/my_certificates_table.php
 *
 * @package    tool_certificate
 * @covers     \tool_certificate\my_certificates_table
 * @copyright  2022 Frederik Pytlick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class my_certificates_table_test extends \advanced_testcase {
    /**
     * Test set up.
     */
    public function setUp(): void {
        $this->resetAfterTest();
    }

    /**
     * Test LinkedIn column
     *
     * @dataProvider col_linkedin_provider
     *
     * @param object $issue
     * @param array $params
     * @param ?int $organizationid
     * @return void
     * @throws \moodle_exception
     */
    public function test_col_linkedin($issue, $params, $organizationid): void {
        if ($organizationid) {
            set_config('linkedinorganizationid', $organizationid, 'tool_certificate');
        }

        $user = self::getDataGenerator()->create_user();
        $issue->userid = $user->id;

        $table = new my_certificates_table($user->id);

        $anchortag = $table->col_linkedin($issue);

        $link = $this->get_href_value_from_anchor_tag($anchortag);
        $url = new \moodle_url(\tool_certificate\my_certificates_table::LINKEDIN_ADD_TO_PROFILE_URL, $params);

        self::assertEquals($url->out(false), $link);
    }

    /**
     * Data provider for test_col_linkedin()
     *
     * @return array[]
     */
    public function col_linkedin_provider(): array {
        return [
            [(object)[
                'id' => '6',
                'expires' => '0',
                'code' => '0123456789SS',
                'timecreated' => 1634376554,
                'templateid' => '1',
                'contextid' => '1',
                'name' => 'Certificate demo template'
            ], [
                'name' => 'Certificate demo template',
                'issueYear' => '2021',
                'issueMonth' => '10',
                'certId' => '0123456789SS',
                'certUrl' => 'https://www.example.com/moodle/admin/tool/certificate/index.php?code=0123456789SS'
            ], null],
            [(object)[
                'id' => '6',
                'expires' => '0',
                'code' => '0123456789SS',
                'timecreated' => 1500370154,
                'templateid' => '1',
                'contextid' => '1',
                'name' => 'Certificate demo template'
            ], [
                'name' => 'Certificate demo template',
                'issueYear' => '2017',
                'issueMonth' => '07',
                'certId' => '0123456789SS',
                'certUrl' => 'https://www.example.com/moodle/admin/tool/certificate/index.php?code=0123456789SS',
                'organizationId' => '123',
            ], 123],
            [(object)[
                'id' => '6',
                'expires' => '123',
                'code' => '0123456789SS',
                'timecreated' => 1568626154,
                'templateid' => '1',
                'contextid' => '1',
                'name' => 'Certificate demo template'
            ], [
                'name' => 'Certificate demo template',
                'issueYear' => '2019',
                'issueMonth' => '09',
                'certId' => '0123456789SS',
                'certUrl' => 'https://www.example.com/moodle/admin/tool/certificate/index.php?code=0123456789SS',
                'expirationYear' => '1970',
                'expirationMonth' => '01'
            ], null],
            [(object)[
                'id' => '6',
                'expires' => '123',
                'code' => '0123456789SS',
                'timecreated' => 1705573754,
                'templateid' => '1',
                'contextid' => '1',
                'name' => 'Certificate demo template'
            ], [
                'name' => 'Certificate demo template',
                'issueYear' => '2024',
                'issueMonth' => '01',
                'certId' => '0123456789SS',
                'certUrl' => 'https://www.example.com/moodle/admin/tool/certificate/index.php?code=0123456789SS',
                'expirationYear' => '1970',
                'expirationMonth' => '01',
                'organizationId' => '123',
            ], 123]
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
