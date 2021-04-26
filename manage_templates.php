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
require_once($CFG->libdir.'/adminlib.php');

$courseid = optional_param('courseid', null, PARAM_INT);
$download = optional_param('download', null, PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', \tool_certificate\certificate::TEMPLATES_PER_PAGE, PARAM_INT);
$pageurl = new moodle_url('/admin/tool/certificate/manage_templates.php', ['page' => $page, 'perpage' => $perpage]);

$title = get_string('managetemplates', 'tool_certificate');
admin_externalpage_setup('tool_certificate/managetemplates');
$context = context_system::instance();

if (!\tool_certificate\permission::can_view_admin_tree()) {
    throw new moodle_exception('managenotallowed', 'tool_certificate');
}

$PAGE->set_title($title);
$PAGE->set_heading($title);

$table = new \tool_certificate\certificates_list();
$table->define_baseurl($pageurl);

if ($table->is_downloading()) {
    $table->download();
    exit();
}

$renderer = $PAGE->get_renderer('tool_certificate');
$tablecontents = $renderer->render_table($table);

$data = ['content' => $tablecontents];
if (\tool_certificate\permission::can_create()) {
    $data += ['addbutton' => true, 'addbuttontitle' => get_string('createtemplate', 'tool_certificate'),
        'addbuttonurl' => null, 'addbuttonattrs' => ['name' => 'data-contextid', 'value' => $context->id],
        'addbuttonicon' => true];
}
$PAGE->requires->js_call_amd('tool_certificate/templates-list', 'init');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('tool_certificate/content_with_heading', $data);
echo $OUTPUT->footer();
