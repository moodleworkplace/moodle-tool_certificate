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
 * Manage issued certificates for a given templateid.
 *
 * @package    tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$download = optional_param('download', null, PARAM_ALPHA);
$revokecert = optional_param('revokecert', false, PARAM_BOOL);

if ($revokecert) {
    $issueid = required_param('issueid', PARAM_INT);
    $issue = $DB->get_record('tool_certificate_issues', ['id' => $issueid], '*', MUST_EXIST);
    $templateid = $issue->templateid;
} else {
    $templateid = required_param('templateid', PARAM_INT);
}

$confirm = optional_param('confirm', 0, PARAM_INT);

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', \tool_certificate\certificate::CUSTOMCERT_PER_PAGE, PARAM_INT);

admin_externalpage_setup('tool_certificate/managetemplates');

$template = \tool_certificate\template::find_by_id($templateid);

if (!$template->can_view_issues()) {
    print_error('issueormanagenotallowed', 'tool_certificate');
}

$pageurl = $url = new moodle_url('/admin/tool/certificate/certificates.php', array('templateid' => $templateid));

$heading = get_string('certificates', 'tool_certificate');

$PAGE->navbar->add($heading);
$PAGE->set_heading($heading);

if ($revokecert && confirm_sesskey()) {

    if (!$template->can_revoke()) {
        print_error('revokenotallowed', 'toolcertificate');
    }

    $nourl = new moodle_url('/admin/tool/certificate/certificates.php', array('templateid' => $templateid));
    $yesurl = new moodle_url('/admin/tool/certificate/certificates.php',
        array('templateid' => $templateid, 'revokecert' => 1, 'issueid' => $issueid, 'confirm' => 1, 'sesskey' => sesskey()));

    if (!$confirm) {
        $PAGE->navbar->add(get_string('deleteconfirm', 'tool_certificate'));
        $message = get_string('revokecertificateconfirm', 'tool_certificate');
        echo $OUTPUT->header();
        echo $OUTPUT->heading($heading);
        echo $OUTPUT->confirm($message, $yesurl, $nourl);
        echo $OUTPUT->footer();
        exit();
    }

    $template->revoke_issue($issueid);

    redirect(new moodle_url('/admin/tool/certificate/certificates.php', ['templateid' => $templateid]));
}

$report = \tool_reportbuilder\system_report_factory::create(\tool_certificate\issues_list::class,
    ['templateid' => $template->get_id()]);
$r = new \tool_wp\output\content_with_heading($report->output(), format_string($template->get_name()));
if ($template->can_issue()) {
    $r->add_button(get_string('issuenewcertificates', 'tool_certificate'),
        $template->new_issue_url());
}
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('tool_wp/content_with_heading', $r->export_for_template($OUTPUT));

echo $OUTPUT->footer();
