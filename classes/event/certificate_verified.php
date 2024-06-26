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
 * The tool_certificate certificate issued event.
 *
 * @package    tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate\event;

use tool_certificate\template;

/**
 * The tool_certificate certificate issued event class.
 *
 * @package    tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate_verified extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'tool_certificate_issues';
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' verified the certificate issue with id '$this->objectid'".
                " issued to user with id '$this->relateduserid'.";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcertificateverified', 'tool_certificate');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        return template::verification_url($this->other['code']);
    }

    /**
     * Create instance of event.
     *
     * @param \stdClass $issue
     * @return certificate_issued
     */
    public static function create_from_issue(\stdClass $issue) {
        global $DB;

        $context = \context_system::instance();
        if ($DB->record_exists('course', ['id' => $issue->courseid])) {
            $context = \context_course::instance($issue->courseid);
        }
        $data = [
            'context' => $context,
            'objectid' => $issue->id,
            'relateduserid' => $issue->userid,
            'other' => [
                'code' => $issue->code,
            ],
        ];
        $event = self::create($data);
        $event->add_record_snapshot('tool_certificate_issues', $issue);
        return $event;
    }
}
