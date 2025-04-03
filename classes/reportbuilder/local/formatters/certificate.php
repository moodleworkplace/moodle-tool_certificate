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

namespace tool_certificate\reportbuilder\local\formatters;

use core_reportbuilder\local\helpers\format;
use core_course_category;
use html_writer;
use stdClass;
use moodle_url;
use context_system;

/**
 * Class certificate_format
 *
 * @package   tool_certificate
 * @copyright 2019 Moodle Pty Ltd <support@moodle.com>
 * @author    2022 Carlos Castillo <carlos.castillo@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate {

    /**
     * Formats a string
     *
     * @param string $value
     * @param stdClass $row
     * @return string
     */
    public static function format_string(string $value, stdClass $row): string {
        $context = $row->contextid ?? context_system::instance();
        return format_string($value, true, ['context' => $context, 'escape' => false]);
    }

    /**
     * Formats a code to add link
     *
     * @param string|null $value
     * @param stdClass $row
     * @return string
     */
    public static function code_with_link(?string $value, stdClass $row): string {
        $url = new moodle_url('/admin/tool/certificate/index.php', ['code' => $row->code]);
        return html_writer::link($url, $row->code, ['title' => get_string('verify', 'tool_certificate')]);
    }

    /**
     * Formats a course category name
     *
     * @param string|null $name
     * @param stdClass $category
     * @return string
     */
    public static function course_category_name(?string $name, stdClass $category): string {
        if ($name === null || empty(trim($category->id))) {
            return '';
        }
        return core_course_category::get($category->id, MUST_EXIST, true)->get_formatted_name();
    }

    /**
     * Formats a course category path
     *
     * @param string|null $name
     * @param stdClass $category
     * @return string
     */
    public static function course_category_path(?string $name, stdClass $category): string {
        if ($name === null || empty(trim($category->id))) {
            return '';
        }
        return core_course_category::get($category->id, MUST_EXIST, true)->get_nested_name(false);
    }

    /**
     * Format the status column.
     *
     * @param string|null $value
     * @param stdClass $row
     * @return string
     */
    public static function certificate_issued_status(?string $value, stdClass $row): string {
        $status = $row->expires && $row->expires <= time() ? 'expired' : 'valid';
        return get_string($status, 'tool_certificate');
    }

    /**
     * Format the expires column.
     *
     * @param int|null $value
     * @param stdClass $row
     * @return string
     */
    public static function certificate_issued_expires(?int $value, stdClass $row): string {
        if ($value > 0) {
            return format::userdate($value, $row);
        }
        return get_string('never', 'tool_certificate');
    }

    /**
     * Appends shared badge if needed
     *
     * Note that this method needs 'fullname' and 'shared' to be passed in order to work.
     *
     * @param string $fullname
     * @param stdClass $row
     * @return string
     */
    public static function append_shared_badge(string $fullname, stdClass $row): string {
        $badge = '';

        if ($row->shared) {
            $badge = html_writer::tag('span', get_string('shared', 'tool_certificate'),
                ['class' => 'badge bg-secondary text-dark ms-1']);
        }

        return $fullname . ' ' . $badge;
    }
}
