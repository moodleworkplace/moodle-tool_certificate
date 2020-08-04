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
 * Manage course custom fields
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('tool_certificate_customfield');

$output = $PAGE->get_renderer('core_customfield');
$handler = \tool_certificate\customfield\issue_handler::create();
$handler->create_custom_fields_if_not_exist();
$outputpage = new \core_customfield\output\management($handler);

echo $output->header(),
$output->heading(new lang_string('certificate_customfield', 'tool_certificate')),
$output->render($outputpage),
$output->footer();
