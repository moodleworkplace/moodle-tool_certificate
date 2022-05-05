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
 * Edit certificate template
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$pageid = optional_param('pageid', 0, PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHANUMEXT);
if ($pageid && $action) {
    $page = \tool_certificate\page::instance($pageid);
    $template = $page->get_template();
} else {
    $templateid = required_param('id', PARAM_INT);
    $template = \tool_certificate\template::instance($templateid);
}

$pageurl = new moodle_url('/admin/tool/certificate/template.php', array('id' => $template->get_id()));
if ($template->get_context()->contextlevel == CONTEXT_COURSE) {
    $courseid = $template->get_context()->instanceid;
    require_login($courseid);
    $manageurl = new moodle_url('/admin/tool/certificate/manage_templates.php', array('courseid' => $courseid));
    $PAGE->navbar->add(get_string('managetemplates', 'tool_certificate'), $manageurl);
    $PAGE->set_url($pageurl);
} else {
    admin_externalpage_setup('tool_certificate/managetemplates', '', null, $pageurl);
}

$template->require_can_manage();

if ($action && $pageid) {
    require_sesskey();
    if ($action === 'moveuppage') {
        $template->move_page($pageid, -1);
    } else if ($action === 'movedownpage') {
        $template->move_page($pageid, 1);
    } else if ($action === 'deletepage') {
        $template->delete_page($pageid);
    }
    redirect($pageurl);
}

$heading = $title = $template->get_formatted_name();
// If 'formatstringstriptags' config is enabled, we can't show a styled badge, so we avoid showing 'shared' string.
if ($template->get_shared()) {
    $heading .= html_writer::tag('div', get_string('shared', 'tool_certificate'),
        ['class' => 'badge badge-secondary ml-2', 'style' => 'font-size: 40%; vertical-align: middle;']);
}
$PAGE->navbar->add($title, $pageurl);

$PAGE->set_title($title);
$PAGE->set_heading($heading, false);

$output = $PAGE->get_renderer('tool_certificate');
$edit = new \tool_certificate\output\page_header_button(get_string('editdetails', 'tool_certificate'),
    ['data-action' => 'editdetails', 'data-id' => $template->get_id(), 'data-name' => $template->get_formatted_name()]);
$PAGE->set_button($edit->render($output) . $PAGE->button);

echo $OUTPUT->header();

$data = $template->get_exporter()->export($OUTPUT);
echo $OUTPUT->render_from_template('tool_certificate/edit_layout', $data);
echo $OUTPUT->footer();
