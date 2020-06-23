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
 * File containing tests for export/import certificate templates mapper class
 *
 * @package     tool_certificate
 * @category    test
 * @copyright   2020 Moodle Pty Ltd <support@moodle.com>
 * @author      2020 Paul Holden <paulh@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license     Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */

namespace tool_certificate\tool_wp\mapper;

use context_coursecat;
use tool_tenant\manager;
use tool_tenant\tenancy;
use tool_wp\local\exportimport\helper;

/**
 * Test class
 *
 * @package     tool_certificate
 * @group       tool_certificate
 * @category    test
 * @covers      \tool_certificate\tool_wp\mapper\tool_certificate_templates
 * @copyright   2020 Moodle Pty Ltd <support@moodle.com>
 * @author      2020 Paul Holden <paulh@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license     Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */
class certificate_mapper_testcase extends \advanced_testcase {

    /**
     * Test mapper returns mapping data correctly for given certificate template
     */
    public function test_get_mapping_data_for_workplace_export() {
        $this->resetAfterTest();

        $certificate = $this->get_plugin_generator()->create_template(['name' => 'My certificate'])->to_record();

        $mapper = helper::find_mapper_for_entity('tool_certificate_templates', helper::get_all_mappers());
        $this->assertInstanceOf(tool_certificate_templates::class, $mapper);

        $data = $mapper->get_mapping_data_for_workplace_export($certificate->id);
        $this->assertEquals([
            'id' => $certificate->id,
            'name' => $certificate->name,
        ], $data);
    }

    /**
     * Test the mapper class successfully locates existing certificates
     */
    public function test_locate_mapping_success(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $certificate = $this->get_plugin_generator()->create_template(['name' => 'My certificate'])->to_record();

        $mapping = $this->get_workplace_generator()->locate_mapping('tool_certificate_templates', ['name' => $certificate->name]);
        $this->assertEquals([$certificate->id, [], [], true], $mapping);
    }


    /**
     * Test the mapper class successfully locates existing certificates where a certificate with the same name also exists in a
     * category that the user cannot access
     */
    public function test_locate_mapping_success_duplicate_name(): void {
        global $DB;

        $this->resetAfterTest();

        // Skip tests if tenant plugin not present.
        if (!class_exists(tenancy::class)) {
            $this->markTestSkipped('Plugin tool_tenant not installed, skipping');
        }

        // Create a certificate in another category.
        $othercategory = $this->getDataGenerator()->create_category();
        $othercontext = context_coursecat::instance($othercategory->id);
        $othercertificate = $this->get_plugin_generator()->create_template([
            'name' => 'My certificate',
            'contextid' => $othercontext->id,
        ])->to_record();

        // Create user, set them as manager for the default tenant category.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $category = $this->getDataGenerator()->create_category();
        (new manager())->update_tenant(tenancy::get_tenant_id(), (object) ['categoryid' => $category->id]);

        $managerrole = $DB->get_record('role', ['shortname' => 'manager']);
        $context = context_coursecat::instance($category->id);
        $this->getDataGenerator()->role_assign($managerrole->id, $user->id, $context);

        // Create a certificate in the default tenant category.
        $certificate = $this->get_plugin_generator()->create_template([
            'name' => $othercertificate->name,
            'contextid' => $context->id,
        ])->to_record();

        $mapping = $this->get_workplace_generator()->locate_mapping('tool_certificate_templates', ['name' => $certificate->name]);
        $this->assertEquals([$certificate->id, [], [], true], $mapping);
    }

    /**
     * Data provider for testing non-matching certificates
     *
     * @see test_locate_mapping_error
     *
     * @return array
     */
    public function locate_mapping_error_provider(): array {
        return [
            ['My certificate', ['name' => 'My other certificate']],
            ['My certificate', ['name' => 'My certificate'], false],
        ];
    }

    /**
     * Test mapper returns errors for non-matching certificates
     *
     * @param string $name
     * @param array $identifier
     * @param bool $adminuser
     *
     * @dataProvider locate_mapping_error_provider
     */
    public function test_locate_mapping_error(string $name, array $identifier, bool $adminuser = true): void {
        $this->resetAfterTest();
        if ($adminuser) {
            $this->setAdminUser();
        }

        $this->get_plugin_generator()->create_template(['name' => $name])->to_record();

        list($certificateid, $notices, $errors, $validated) =
            $this->get_workplace_generator()->locate_mapping('tool_certificate_templates', $identifier);

        $this->assertNull($certificateid);
        $this->assertEmpty($notices);
        $this->assertCount(1, $errors);
        $this->assertEquals("Certificate '{$identifier['name']}' was not found", reset($errors));
        $this->assertFalse($validated);
    }

    /**
     * Returns the plugin generator
     *
     * @return \tool_certificate_generator
     */
    protected function get_plugin_generator(): \tool_certificate_generator {
        return $this->getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    /**
     * Returns the tenant generator
     *
     * @return \tool_tenant_generator
     */
    protected function get_tenant_generator(): \tool_tenant_generator {
        return $this->getDataGenerator()->get_plugin_generator('tool_tenant');
    }

    /**
     * Returns the workplace generator
     *
     * @return \tool_wp_generator
     */
    protected function get_workplace_generator(): \tool_wp_generator {
        return $this->getDataGenerator()->get_plugin_generator('tool_wp');
    }
}