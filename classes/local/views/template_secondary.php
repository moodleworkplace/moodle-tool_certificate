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

namespace tool_certificate\local\views;

use core\navigation\views\secondary as core_secondary;
use tool_certificate\template;

/**
 * Class tool_certificate\local\views\template_secondary
 *
 * @package   tool_certificate
 * @copyright 2022 Moodle Pty Ltd <support@moodle.com>
 * @author    2022 Ruslan Kabalin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template_secondary extends core_secondary {

    /** @var template */
    protected $template;

    /**
     * Navigation constructor.
     *
     * @param \moodle_page $page
     * @param template $template
     */
    public function __construct(\moodle_page $page, template $template) {
        $this->template = $template;
        parent::__construct($page);
    }

    /**
     * Initialise the view based navigation based on the current context.
     */
    public function initialise(): void {
        if ($this->template->can_manage()) {
            $this->add(get_string('template', 'tool_certificate'),
                new \moodle_url('/admin/tool/certificate/template.php', ['id' => $this->template->get_id()]),
                null, null, 'template');
            $this->add(get_string('details'),
                new \moodle_url('/admin/tool/certificate/template_details.php', ['id' => $this->template->get_id()]),
                null, null, 'details');
        }
        if ($this->template->can_view_issues()) {
            $this->add(get_string('issuedcertificates', 'tool_certificate'),
                new \moodle_url('/admin/tool/certificate/certificates.php', ['templateid' => $this->template->get_id()]),
                null, null, 'issuedcertificates');
        }
    }
}
