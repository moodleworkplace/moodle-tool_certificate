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
 * Manage issued certificates for a given templateid.
 *
 * @package    tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$download = optional_param('download', null, PARAM_ALPHA);
$templateid = required_param('templateid', PARAM_INT);

$confirm = optional_param('confirm', 0, PARAM_INT);

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', \tool_certificate\certificate::ISSUES_PER_PAGE, PARAM_INT);

$pageurl = $url = new moodle_url('/admin/tool/certificate/certificates.php', ['templateid' => $templateid]);
$PAGE->set_url($pageurl);
$template = \tool_certificate\template::instance($templateid);
if ($coursecontext = $template->get_context()->get_course_context(false)) {
    require_login($coursecontext->instanceid);
} else {
    admin_externalpage_setup('tool_certificate/managetemplates', '', null, $pageurl, ['nosearch' => true]);
}

if (!$template->can_view_issues()) {
    throw new moodle_exception('issueormangenotallowed', 'tool_certificate');
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

$outputpage = new \tool_certificate\output\issues_page($template->get_id());

$data = $outputpage->export_for_template($PAGE->get_renderer('core'));
$data += ['heading' => get_string('issuedcertificates', 'tool_certificate')];
if ($template->can_issue_to_anybody()) {
    $data += ['addbutton' => true, 'addbuttontitle' => get_string('issuecertificates', 'tool_certificate'),
        'addbuttonurl' => null, 'addbuttonattrs' => ['name' => 'data-tid', 'value' => $template->get_id()],
        'addbuttonicon' => true, ];
}
$PAGE->requires->js_call_amd('tool_certificate/issues-list', 'init');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('tool_certificate/content_with_heading', $data);
echo $OUTPUT->footer();
