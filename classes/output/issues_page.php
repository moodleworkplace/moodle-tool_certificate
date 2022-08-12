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
use tool_certificate\reportbuilder\local\systemreports\issues;
use tool_certificate\template;

/**
 * Class tool_certificate\output\issues_page
 *
 * @package   tool_certificate
 * @copyright 2022 Moodle Pty Ltd <support@moodle.com>
 * @author    2022 Odei Alba <odei.alba@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issues_page implements \templatable, \renderable {
    /** @var int */
    protected $templateid;

    /**
     * templates_page constructor.
     *
     * @param int $templateid
     */
    public function __construct(int $templateid) {
        $this->templateid = $templateid;
    }

    /**
     * Implementation of exporter from templatable interface
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $context = template::instance($this->templateid)->get_context();
        $report = system_report_factory::create(issues::class, $context,
            '', '', 0, ['templateid' => $this->templateid]);

        return ['content' => $report->output()];
    }
}
