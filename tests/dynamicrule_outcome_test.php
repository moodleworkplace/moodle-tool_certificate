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

use tool_certificate\tool_dynamicrule\outcome\certificate;

/**
 * Unit tests for outcome\certificate  class.
 *
 * @package    tool_certificate
 * @group      tool_certificate
 * @copyright  2019 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_certificate_outcome_certificate_testcase extends advanced_testcase {

    /** @var tool_certificate_generator */
    protected $certgenerator;

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
        $outcome = certificate::instance();
        $this->assertNotEmpty($outcome->get_title());
    }

    /**
     * Test get_category
     */
    public function test_get_category() {
        $outcome = certificate::instance();
        $this->assertEquals(get_string('pluginname', 'tool_certificate'), $outcome->get_category());
    }

    /**
     * Test apply_to_users
     */
    public function test_apply_to_users() {
        global $DB;

        $rule0 = $this->get_generator()->create_rule();
        $this->get_generator()->create_condition_alwaystrue($rule0->id);

        $certificate = $this->certgenerator->create_template((object)['name' => 'Test template']);

        $configdata = ['certificate' => $certificate->get_id()];
        $outcome = certificate::create($rule0->id, $configdata);

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
        $outcome = certificate::create($rule0->id, $configdata);

        $str = get_string('outcomecertificatedescription', 'tool_certificate', $name);
        $this->assertEquals($str, $outcome->get_description());
    }

    /**
     * Test is_configuration_valid
     */
    public function test_is_configuration_valid() {
        $rule0 = $this->get_generator()->create_rule();
        $certificate = $this->certgenerator->create_template((object)['name' => 'Test template']);
        $configdata = ['certificate' => $certificate->get_id()];
        $outcome = certificate::create($rule0->id, $configdata);

        self::setAdminUser();
        $this->assertTrue($outcome->is_configuration_valid());

        // Delete certificate.
        $certificate->delete();
        $this->assertFalse($outcome->is_configuration_valid());
    }

    /**
     * Test user_can_add
     */
    public function test_user_can_add() {
        $rule0 = $this->get_generator()->create_rule();
        $certificate = $this->certgenerator->create_template((object)['name' => 'Test template']);
        $configdata = ['certificate' => $certificate->get_id()];
        certificate::create($rule0->id, $configdata);

        // Admin user.
        self::setAdminUser();
        $this->assertTrue(certificate::instance()->user_can_add());

        // Non-priveleged user.
        $user = self::getDataGenerator()->create_user();
        self::setUser($user);
        $this->assertFalse(certificate::instance()->user_can_add());

        // Grant priveleges to user.
        $roleid = create_role('Dummy role', 'dummyrole', 'dummy role description');
        $context = context_system::instance();
        assign_capability('tool/certificate:issue', CAP_ALLOW, $roleid, $context->id);
        role_assign($roleid, $user->id, $context->id);
        $this->assertTrue(certificate::instance()->user_can_add());
    }

    /**
     * Test user_can_edit
     */
    public function test_user_can_edit() {
        $rule0 = $this->get_generator()->create_rule();
        $certificate = $this->certgenerator->create_template((object)['name' => 'Test template']);
        $configdata = ['certificate' => $certificate->get_id()];
        certificate::create($rule0->id, $configdata);

        // Admin user.
        self::setAdminUser();
        $this->assertTrue(certificate::instance()->user_can_edit($configdata));

        // Non-priveleged user.
        $user = self::getDataGenerator()->create_user();
        self::setUser($user);
        $this->assertFalse(certificate::instance()->user_can_edit($configdata));

        // Grant priveleges to user.
        $roleid = create_role('Dummy role', 'dummyrole', 'dummy role description');
        assign_capability('tool/certificate:issue', CAP_ALLOW, $roleid, $certificate->get_context()->id);
        role_assign($roleid, $user->id, $certificate->get_context());
        $this->assertTrue(certificate::instance()->user_can_edit($configdata));
    }

    /**
     * Test test_user_can_edit by tenant.
     */
    public function test_user_can_edit_tenant() {
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_tenant');
        $tenant = $tenantgenerator->create_tenant();
        $tenantadmin = $this->getDataGenerator()->create_user();
        $tenantgenerator->allocate_user($tenantadmin->id, $tenant->id);
        $manager = new \tool_tenant\manager();
        $manager->assign_tenant_admin_role($tenant->id, [$tenantadmin->id]);

        $rule0 = $this->get_generator()->create_rule(['tenantid' => $tenant->id]);
        $certificate = $this->certgenerator->create_template((object)['name' => 'Test template']);
        $configdata = ['certificate' => $certificate->get_id()];
        certificate::create($rule0->id, $configdata);

        // Sanity check.
        self::setAdminUser();
        $this->assertTrue(certificate::instance()->user_can_edit($configdata));

        // Tenant admin can also access system context badge.
        self::setUser($tenantadmin);
        $this->assertTrue(certificate::instance()->user_can_edit($configdata));
    }
}
