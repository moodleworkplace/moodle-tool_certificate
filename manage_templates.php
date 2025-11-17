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
 * Manage certificate templates.
 *
 * @package    tool_certificate
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$courseid = optional_param('courseid', SITEID, PARAM_INT);
$download = optional_param('download', null, PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', \tool_certificate\certificate::TEMPLATES_PER_PAGE, PARAM_INT);
$pageurl = new moodle_url('/admin/tool/certificate/manage_templates.php', ['page' => $page, 'perpage' => $perpage]);
$iscoursepage = !empty($courseid) && $courseid !== SITEID;

if ($iscoursepage) {
    $course = get_course($courseid);
    $pageurl->param('courseid', $courseid);
    $context = \context_course::instance($course->id);
    $PAGE->set_course($course);
    $PAGE->add_body_class('limitedwidth');
} else {
    $context = \context_system::instance();
    $PAGE->set_secondary_navigation(false);
    admin_externalpage_setup('tool_certificate/managetemplates', '', null, '', ['nosearch' => true]);
}

if (!\tool_certificate\permission::can_view_admin_tree()) {
    throw new moodle_exception('issueormangenotallowed', 'tool_certificate');
}

$title = get_string('managetemplates', 'tool_certificate');

$PAGE->set_heading($iscoursepage ? $course->fullname : $title);
$PAGE->set_title($title);
$PAGE->set_url($pageurl);

$outputpage = new \tool_certificate\output\templates_page($courseid);

$data = $outputpage->export_for_template($PAGE->get_renderer('core'));
if (\tool_certificate\permission::can_create()) {
    $data += ['addbutton' => true, 'addbuttontitle' => get_string('createtemplate', 'tool_certificate'),
        'addbuttonurl' => null, 'addbuttonattrs' => ['name' => 'data-contextid', 'value' => $context->id],
        'addbuttonicon' => true, ];
}
$PAGE->requires->js_call_amd('tool_certificate/templates-list', 'init');

echo $OUTPUT->header();
if ($iscoursepage) {
    echo $OUTPUT->heading($title);
}
echo $OUTPUT->render_from_template('tool_certificate/content_with_heading', $data);
echo $OUTPUT->footer();
