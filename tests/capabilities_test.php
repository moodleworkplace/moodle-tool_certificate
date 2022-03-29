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
use tool_tenant_generator;
use context_coursecat;
use context_system;

/**
 * Unit tests for functions that deals with capabilities.
 *
 * @package    tool_certificate
 * @group      tool_certificate
 * @covers     \tool_certificate\permission
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capabilities_test extends advanced_testcase {

    /** @var tool_certificate_generator */
    protected $certgenerator;

    /**
     * Test set up.
     */
    public function setUp(): void {
        $this->resetAfterTest();
        $this->certgenerator = self::getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    /**
     * Test the can_manage
     */
    public function test_can_manage() {
        global $DB;
        $cat1 = self::getDataGenerator()->create_category();
        $cat2 = self::getDataGenerator()->create_category();

        $certificate1 = $this->certgenerator->create_template((object)['name' => 'Certificate 1', 'categoryid' => $cat1->id]);
        $certificate2 = $this->certgenerator->create_template((object)['name' => 'Certificate 2', 'categoryid' => $cat2->id]);
        $certificate3 = $this->certgenerator->create_template((object)['name' => 'Certificate 3']);

        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
        $manager = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($managerrole->id, $manager->id, context_coursecat::instance($cat1->id));

        $this->setUser($manager);

        // Managers can manage template in their category.
        $this->assertEquals(true, $certificate1->can_manage());

        // Managers can't manage template in different category.
        $this->assertEquals(false, $certificate2->can_manage());

        // Managers can't manage system template.
        $this->assertEquals(false, $certificate3->can_manage());

        // Assign the cap in system context.
        $this->getDataGenerator()->role_assign($managerrole->id, $manager->id, context_system::instance());

        // Now manager can manage templates everywhere.
        $this->assertTrue($certificate1->can_manage());
        $this->assertTrue($certificate2->can_manage());
        $this->assertTrue($certificate3->can_manage());
    }

    /**
     * Test can_verify. By default, users can verify certificates.
     */
    public function test_can_verify() {
        $manager = $this->getDataGenerator()->create_user();

        $this->setUser($manager);

        $this->assertTrue(\tool_certificate\permission::can_verify());
    }

    /**
     * Test can_view_admin_tree. For default, manager are able to view the admin tree, but guests are not.
     */
    public function test_can_view_admin_tree() {
        global $DB;

        $guest = $DB->get_record('user', array('username' => 'guest'));
        $this->setUser($guest);

        $this->assertFalse(\tool_certificate\permission::can_view_admin_tree());

        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
        $manager = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($managerrole->id, $manager->id);

        $this->setUser($manager);

        $this->assertTrue(\tool_certificate\permission::can_view_admin_tree());
    }

    /**
     * Test the can_issue with users within the same tenant
     */
    public function test_can_issue_same_tenant() {
        global $DB;

        // Skip tests if tool_tenant is not present.
        if (!class_exists('tool_tenant\tenancy')) {
            $this->markTestSkipped('Plugin tool_tenant not installed, skipping');
        }

        $cat1 = self::getDataGenerator()->create_category();
        $cat2 = self::getDataGenerator()->create_category();

        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
        $manager1 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($managerrole->id, $manager1->id, context_coursecat::instance($cat1->id));

        $this->setUser($manager1);

        $certificate1 = $this->certgenerator->create_template((object)['name' => 'Certificate 1', 'categoryid' => $cat1->id]);
        $certificate2 = $this->certgenerator->create_template((object)['name' => 'Certificate 2', 'categoryid' => $cat2->id]);
        $certificate3 = $this->certgenerator->create_template((object)['name' => 'Certificate 3']);

        // Managers can issue templates by default on same tenant and on shared templates, but not for other tenants.
        $this->assertEquals(true, $certificate1->can_issue($user1->id));
        $this->assertEquals(false, $certificate2->can_issue($user1->id));
        $this->assertEquals(false, $certificate3->can_issue($user1->id));

        $this->assertEquals(true, $certificate1->can_revoke($user1->id));
        $this->assertEquals(false, $certificate2->can_revoke($user1->id));
        $this->assertEquals(false, $certificate3->can_revoke($user1->id));

        // Assign the cap in system context.
        $this->getDataGenerator()->role_assign($managerrole->id, $manager1->id, context_system::instance());
        accesslib_clear_all_caches_for_unit_testing();

        // Now can issue in all tenants.
        $this->assertEquals(true, $certificate1->can_issue($user1->id));
        $this->assertEquals(true, $certificate2->can_issue($user1->id));
        $this->assertEquals(true, $certificate3->can_issue($user1->id));

        $this->assertEquals(true, $certificate1->can_revoke($user1->id));
        $this->assertEquals(true, $certificate2->can_revoke($user1->id));
        $this->assertEquals(true, $certificate3->can_revoke($user1->id));
    }

    /**
     * Test the can_issue with user allocated to non-default tenant
     */
    public function test_can_issue_other_tenant() {
        global $DB;

        // Skip tests if not using Postgres.
        if (!class_exists('tool_tenant\tenancy')) {
            $this->markTestSkipped('Plugin tool_tenant not installed, skipping');
        }

        $cat1 = self::getDataGenerator()->create_category();
        $cat2 = self::getDataGenerator()->create_category();
        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
        unassign_capability('moodle/site:viewparticipants', $managerrole->id, \context_system::instance()->id);
        $manager1 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($managerrole->id, $manager1->id);
        accesslib_clear_all_caches_for_unit_testing();

        /** @var tool_tenant_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_tenant');
        $tenant = $tenantgenerator->create_tenant();
        $tenantgenerator->allocate_user($manager1->id, $tenant->id);
        $tenantgenerator->allocate_user($user1->id, $tenant->id);
        $tenantgenerator->allocate_user($user2->id, $tenantgenerator->create_tenant()->id);

        $this->setUser($manager1);

        $certificate1 = $this->certgenerator->create_template((object)['name' => 'Certificate 1']);

        $this->assertEquals(true, $certificate1->can_issue($user1->id));
        $this->assertEquals(false, $certificate1->can_issue($user2->id));
        $this->assertEquals(true, $certificate1->can_revoke($user1->id));
        $this->assertEquals(false, $certificate1->can_revoke($user2->id));

        // Allow current user to access users from all tenants.
        assign_capability('moodle/site:viewparticipants', CAP_ALLOW, $managerrole->id, \context_system::instance()->id);

        // Now can issue in all tenants.
        $this->assertEquals(true, $certificate1->can_issue($user1->id));
        $this->assertEquals(true, $certificate1->can_issue($user2->id));
        $this->assertEquals(true, $certificate1->can_revoke($user1->id));
        $this->assertEquals(true, $certificate1->can_revoke($user2->id));
    }
}
