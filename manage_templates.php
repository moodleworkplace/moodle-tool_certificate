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

$courseid = optional_param('courseid', null, PARAM_INT);

$title = get_string('managetemplates', 'tool_certificate');
if ($courseid) {
    require_login($courseid);
    $context = context_course::instance($courseid);
    if (!\tool_certificate\permission::can_view_templates_in_context($context)) {
        // TODO WP-1196 Support certificates in course context.
        require_capability('tool/certificate:manage', $context);
    }
    $PAGE->set_url(new moodle_url('/admin/tool/certificate/manage_templates.php', array('courseid' => $courseid)));
    $title .= ': ' . format_string($PAGE->course->fullname);
} else {
    admin_externalpage_setup('tool_certificate/managetemplates');
    $context = context_system::instance();
}

$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();

$report = \tool_reportbuilder\system_report_factory::create(\tool_certificate\certificates_list::class,
    ['contextid' => $context->id]);
$r = new \tool_wp\output\content_with_heading($report->output());
if (\tool_certificate\permission::can_create()) {
    $r->add_button(get_string('createtemplate', 'tool_certificate'), null,
        ['data-contextid' => $context->id]);
}
$PAGE->requires->js_call_amd('tool_certificate/templates-list', 'init');
echo $OUTPUT->render_from_template('tool_wp/content_with_heading', $r->export_for_template($OUTPUT));
echo $OUTPUT->footer();
