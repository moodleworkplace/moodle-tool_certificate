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

namespace tool_certificate\task;

use tool_certificate_generator;

/**
 *  Test for regenerate certificates Ad-hoc task
 *
 * @package   tool_certificate
 * @copyright 2025 Moodle Pty Ltd <support@moodle.com>
 * @author    2025 Tasio Bertomeu Gomez <tasio.bertomeu@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class regenerate_certificates_test extends \advanced_testcase {
    /** @var tool_certificate_generator */
    protected $certgenerator;

    /**
     * Test set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->certgenerator = self::getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    /**
     * Test execute() method for bulk certificate regeneration.
     * @covers \tool_certificate\task\regenerate_certificates::execute
     */
    public function test_execute(): void {
        global $DB;
        $this->resetAfterTest();

        // Set current user to admin for required capabilities.
        $this->setAdminUser();

        // Create the certificate.
        $template1 = $this->certgenerator->create_template((object)['name' => 'Certificate 1']);
        $template2 = $this->certgenerator->create_template((object)['name' => 'Certificate 2']);

        // Create some certificate issues for those templates.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $issue1 = $this->certgenerator->issue($template1, $user1);
        $issue2 = $this->certgenerator->issue($template1, $user2);
        $issue3 = $this->certgenerator->issue($template1, $user3);
        $issue4 = $this->certgenerator->issue($template2, $user4);

        // Run the adhoc task data for all users.
        $task = new regenerate_certificates();
        $task->set_custom_data((object)[
            'actiontype' => 'regenerateall',
            'templateid' => $template1->get_id(),
            'issueids' => [],
            'sendnotification' => true,
        ]);
        ob_start();
        $task->execute();
        $out = ob_get_clean();
        $this->assertEquals(
            'Regenerating users certificates. Number of certificates: 3' . PHP_EOL,
            $out
        );

        // Run the adhoc task data for a list of users.
        $task = new regenerate_certificates();
        $task->set_custom_data((object)[
            'actiontype' => 'regenerateselectedusers',
            'templateid' => $template1->get_id(),
            'issueids' => [$issue1->id, $issue2->id],
            'sendnotification' => true,
        ]);
        ob_start();
        $task->execute();
        $out = ob_get_clean();
        $this->assertEquals(
            'Regenerating users certificates. Number of certificates: 2' . PHP_EOL,
            $out
        );

        // Check user without permissions.
        $this->setGuestUser();

        // Run the adhoc task data for a list of users.
        $task = new regenerate_certificates();
        $task->set_custom_data((object)[
            'actiontype' => 'regenerateselectedusers',
            'templateid' => $template1->get_id(),
            'issueids' => [$issue1->id],
            'sendnotification' => true,
        ]);
        ob_start();
        $task->execute();
        $out = ob_get_clean();
        $this->assertEquals(
            'Regenerating users certificates. Number of certificates: 1' . PHP_EOL .
            "Skipping issue $issue1->id: cannot issue certificate." . PHP_EOL,
            $out
        );
    }
}
