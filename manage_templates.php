<?php
// This file is part of the tool_certificate for Moodle - http://moodle.org/
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
 * Manage certificate templates.
 *
 * @package    tool_certificate
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('tool_certificate/managetemplates');

$context = context_system::instance();
$canissue = has_capability('tool/certificate:issue', $context);
$canmanage = has_any_capability(['tool/certificate:manage', 'tool/certificate:manageforalltenants'], $context);

$PAGE->set_title(get_string('managetemplates', 'tool_certificate'));
$PAGE->set_heading(get_string('managetemplates', 'tool_certificate'));

echo $OUTPUT->header();

$report = \tool_reportbuilder\system_report_factory::create(\tool_certificate\certificates_list::class);
$r = new \tool_wp\output\content_with_heading($report->output());
if (\tool_certificate\template::can_create()) {
    $r->add_button(get_string('createtemplate', 'tool_certificate'));
}
$PAGE->requires->js_call_amd('tool_certificate/templates-list', 'init');
echo $OUTPUT->render_from_template('tool_wp/content_with_heading', $r->export_for_template($OUTPUT));
echo $OUTPUT->footer();
