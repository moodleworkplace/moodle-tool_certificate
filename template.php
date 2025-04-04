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

$pageurl = new moodle_url('/admin/tool/certificate/template.php', ['id' => $template->get_id()]);
if ($template->get_context()->contextlevel == CONTEXT_COURSE) {
    $courseid = $template->get_context()->instanceid;
    require_login($courseid);
    $manageurl = new moodle_url('/admin/tool/certificate/manage_templates.php', ['courseid' => $courseid]);
    $PAGE->navbar->add(get_string('managetemplates', 'tool_certificate'), $manageurl);
    $PAGE->set_url($pageurl);
} else {
    admin_externalpage_setup('tool_certificate/managetemplates', '', null, $pageurl, ['nosearch' => true]);
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
if ($template->get_shared()) {
    $heading .= html_writer::tag('div', get_string('shared', 'tool_certificate'),
        ['class' => 'badge rounded-pill bg-secondary text-dark font-small ms-2 align-middle']);
}
$PAGE->navbar->add($title, $pageurl);

$PAGE->set_title($title);
$PAGE->set_heading($heading, false);

// Secondary navigation.
$secondarynav = new \tool_certificate\local\views\template_secondary($PAGE, $template);
$secondarynav->initialise();
$PAGE->set_secondarynav($secondarynav);

$data = $template->get_exporter()->export($PAGE->get_renderer('core'));
$data->heading = get_string('template', 'tool_certificate');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('tool_certificate/edit_layout', $data);
echo $OUTPUT->footer();
