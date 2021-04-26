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

$pageurl = $url = new moodle_url('/admin/tool/certificate/certificates.php', array('templateid' => $templateid));
$PAGE->set_url($pageurl);
$template = \tool_certificate\template::instance($templateid);
if ($coursecontext = $template->get_context()->get_course_context(false)) {
    require_login($coursecontext->instanceid);
} else {
    admin_externalpage_setup('tool_certificate/managetemplates', '', null, $pageurl);
}

if (!$template->can_view_issues()) {
    throw new moodle_exception('issueormanagenotallowed', 'tool_certificate');
}

$heading = get_string('certificates', 'tool_certificate');

$PAGE->set_title("$SITE->shortname: " . $heading);
$PAGE->navbar->add($heading);
$PAGE->set_heading($heading);

$table = new \tool_certificate\issues_list($template);
$table->define_baseurl($pageurl);

if ($table->is_downloading()) {
    $table->download();
    exit();
}

$renderer = $PAGE->get_renderer('tool_certificate');
$tablecontents = $renderer->render_table($table);
$data = ['content' => $tablecontents, 'heading' => format_string($template->get_name())];
if ($template->can_issue_to_anybody()) {
    $data += ['addbutton' => true, 'addbuttontitle' => get_string('issuecertificates', 'tool_certificate'),
        'addbuttonurl' => null, 'addbuttonattrs' => ['name' => 'data-tid', 'value' => $template->get_id()],
        'addbuttonicon' => true];
}
$PAGE->requires->js_call_amd('tool_certificate/issues-list', 'init');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('tool_certificate/content_with_heading', $data);
echo $OUTPUT->footer();
