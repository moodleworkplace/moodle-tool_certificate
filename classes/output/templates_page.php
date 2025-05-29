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

namespace tool_certificate\output;

use core_reportbuilder\system_report_factory;
use renderer_base;
use tool_certificate\reportbuilder\local\systemreports\templates;

/**
 * Class tool_certificate\output\templates_page
 *
 * @package   tool_certificate
 * @copyright 2022 Moodle Pty Ltd <support@moodle.com>
 * @author    2022 Odei Alba <odei.alba@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class templates_page implements \renderable, \templatable {
    /**
     * templates_page constructor.
     *
     * @param int $courseid
     */
    public function __construct(
        /** @var int course id */
        protected int $courseid = SITEID
    ) {
    }

    /**
     * Implementation of exporter from templatable interface
     *
     * @param renderer_base $output
     *
     * @return array|\stdClass
     * @throws \coding_exception
     */
    public function export_for_template(renderer_base $output) {
        $context = $this->courseid !== SITEID ? \context_course::instance($this->courseid) : \context_system::instance();
        $report = system_report_factory::create(templates::class, $context);

        return ['content' => $report->output()];
    }
}
