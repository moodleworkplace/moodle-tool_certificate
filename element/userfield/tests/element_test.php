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

namespace certificateelement_userfield;

use advanced_testcase;
use tool_certificate\element;
use tool_certificate_generator;
use core_text;

/**
 * Unit tests for userfield element.
 *
 * @package    certificateelement_userfield
 * @group      tool_certificate
 * @covers     \certificateelement_userfield\element
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
     *
     * @return tool_certificate_generator
     */
    protected function get_generator(): tool_certificate_generator {
        return $this->getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    /**
     * Test render process with PDF.
     */
    public function test_render(): void {
        $certificate1 = $this->get_generator()->create_template((object) ['name' => 'Certificate 1']);
        $pageid = $this->get_generator()->create_page($certificate1)->get_id();
        $user = $this->getDataGenerator()->create_user();
        // Create few elements.
        $this->get_generator()->create_element(
            $pageid,
            'userfield',
            ['name' => 'User email element', 'userfield' => 'email'],
        );
        // Create customfield element.
        $this->create_cf_certificate_element(
            'frogdesc',
            $pageid,
            $user->id,
        );

        // Generate PDF for preview.
        $filecontents = $this->get_generator()->generate_pdf($certificate1, true);
        $this->assertGreaterThan(30000, core_text::strlen($filecontents, '8bit'));

        // Generate PDF for issue.
        $issue = $this->get_generator()->issue($certificate1, $user);
        $filecontents = $this->get_generator()->generate_pdf($certificate1, false, $issue);
        $this->assertGreaterThan(30000, core_text::strlen($filecontents, '8bit'));
    }

    /**
     * Test render_html
     *
     * @param string $elementname
     * @param string $expected
     * @return void
     * @dataProvider data_provider_render_html
     */
    public function test_render_html(string $elementname, string $expected): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'User',
            'lastname' => 'Test(<script>alert("XSS")</script>)',
            'email' => 'user@example.com',
        ]);
        $pageid = $this->get_generator()->create_page(
            $this->get_generator()->create_template((object) ['name' => 'Certificate 1'])
        )->get_id();
        switch ($elementname) {
            case 'fullname':
                $element = $this->get_generator()->create_element(
                    $pageid,
                    'userfield',
                    ['userfield' => 'fullname'],
                );
                break;
            case 'email':
                $element = $this->get_generator()->create_element(
                    $pageid,
                    'userfield',
                    ['name' => 'User email element', 'userfield' => 'email'],
                );
                break;
            case 'frogdesc':
                $element = $this->create_cf_certificate_element(
                    'frogdesc',
                    $pageid,
                    $user->id,
                );
                break;
            default:
                $this->fail('Unknown element name ' . $elementname);
        }
        $this->setUser($user);
        $html = $element->render_html();
        // Check that expected content is present.
        $this->assertStringContainsString($expected, $html);
        // Then check that no script tags are present.
        $this->assertStringNotContainsString('script', $html);
    }

    /**
     * Data provider for test_render_html.
     *
     * @return array
     */
    public static function data_provider_render_html(): array {
        return [
            'fullname' => [
                'fullname', 'User Test',
            ],
            'email' => [
                'email', 'user@example.com',
            ],
            'frogdesc' => [
                'frogdesc', 'Gryffindor',
            ],
        ];
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

    /**
     * Helper function to create a custom profile field and corresponding certificate element.
     *
     * @param string $elementname The shortname of the custom profile field or user field.
     * @param int $pageid The ID of the certificate page.
     * @param int|null $userid
     * @return element
     */
    private function create_cf_certificate_element(
        string $elementname,
        int $pageid,
        ?int $userid = null
    ): \tool_certificate\element {
        global $DB, $USER, $CFG;

        if (!$userid) {
            $userid = $USER->id;
        }
        require_once($CFG->dirroot . '/user/profile/lib.php');

        // If the field is a standard user field, create it directly.
        $standardfields = ['firstname', 'lastname', 'email', 'fullname'];
        if (in_array($elementname, $standardfields, true)) {
            $params = ['userfield' => $elementname];
            if ($elementname === 'email') {
                $params['name'] = 'User email element';
            }
            return $this->get_generator()->create_element(
                $pageid,
                'userfield',
                $params,
            );
        }

        // Otherwise, create a custom profile field according to the name.
        switch ($elementname) {
            case 'frogdesc':
                $fielddef = [
                    'shortname' => 'frogdesc',
                    'name' => 'Description of frog',
                    'categoryid' => 1,
                    'datatype' => 'textarea',
                ];
                $fieldcontent = '<p>Gryffindor</p><script>alert("XSS")</script>';
                $certelementname = 'User custom field element';
                break;
            // ...add other custom fields here if needed...
            default:
                throw new \coding_exception('Unknown custom field: ' . $elementname);
        }

        $id1 = $DB->insert_record('user_info_field', $fielddef);

        // Create the corresponding certificate element.
        $element = $this->get_generator()->create_element(
            $pageid,
            'userfield',
            ['name' => $certelementname, 'userfield' => $id1],
        );
        $profiledata = (object) ['id' => $userid];
        $profiledata->{'profile_field_' . $fielddef['shortname']} = $fieldcontent;
        profile_save_data($profiledata);

        return $element;
    }
}
