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

/**
 * Class observer for tool_certificate.
 *
 * @package    tool_certificate
 * @author     2020 Mikel Martín <mikel@moodle.com>
 * @copyright  2020 Moodle Pty Ltd <support@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Class tool_certificate_observer
 *
 * @package    tool_certificate
 * @author     2020 Mikel Martín <mikel@moodle.com>
 * @copyright  2020 Moodle Pty Ltd <support@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_certificate_observer
{
    /**
     * Course deleted observer
     *
     * @param \core\event\course_content_deleted $event
     */
    public static function on_course_content_deleted(\core\event\course_content_deleted $event): void {
        global $DB;

        $fs = get_file_storage();
        $issues = $DB->get_records('tool_certificate_issues', ['courseid' => $event->courseid]);
        foreach ($issues as $issue) {
            $fs->delete_area_files(context_system::instance()->id, 'tool_certificate', 'issues', $issue->id);
        }

        $DB->delete_records('tool_certificate_issues', ['courseid' => $event->courseid]);

    }
}
