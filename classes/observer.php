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
 * Class observer for tool_certificate.
 *
 * @package    tool_certificate
 * @author     2020 Mikel Martín <mikel@moodle.com>
 * @copyright  2020 Moodle Pty Ltd <support@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license    Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */

defined('MOODLE_INTERNAL') || die;

use tool_certification\constants;
use tool_certification\event\certification_completion_created;
use tool_certification\event\user_allocation_created;
use tool_certification\event\user_allocation_deleted;
use tool_program\event\program_completed;
use tool_certification\api;
use core\event\user_deleted;
use tool_program\persistent\program_user;

/**
 * Class tool_certificate_observer
 *
 * @package    tool_certificate
 * @author     2020 Mikel Martín <mikel@moodle.com>
 * @copyright  2020 Moodle Pty Ltd <support@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license    Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */
class tool_certificate_observer
{
    /**
     * Course deleted observer
     *
     * @param \core\event\course_deleted $event
     */
    public static function on_course_deleted(\core\event\course_deleted $event): void {
        global $DB;

        $DB->delete_records('tool_certificate_issues', ['courseid' => $event->courseid]);
    }
}