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
$downloadcert = optional_param('downloadcert', false, PARAM_BOOL);
$revokecert = optional_param('revokecert', false, PARAM_BOOL);

if ($downloadcert || $revokecert) {
    $issueid = required_param('issueid', PARAM_INT);
    $issue = $DB->get_record('tool_certificate_issues', ['id' => $issueid], '*', MUST_EXIST);
    $templateid = $issue->templateid;
} else {
    $templateid = required_param('templateid', PARAM_INT);
}

$confirm = optional_param('confirm', 0, PARAM_INT);

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', \tool_certificate\certificate::CUSTOMCERT_PER_PAGE, PARAM_INT);

require_login();

$canissue = has_capability('tool/certificate:issue', context_system::instance());
$canmanage = has_capability('tool/certificate:manage', context_system::instance());
$canview = has_capability('tool/certificate:viewallcertificates', context_system::instance());

if (!$canmanage && !$canissue && !$canview) {
    print_error('cantvieworissue', 'tool_certificate');
}

$template = $DB->get_record('tool_certificate_templates', array('id' => $templateid), '*', MUST_EXIST);

// Check if we requested to download a certificate.
if ($downloadcert) {
    $template = new \tool_certificate\template($template);
    $template->generate_pdf(false, $issue);
    exit();
}

$context = context_system::instance();
$title = $SITE->fullname;

$pageurl = $url = new moodle_url('/admin/tool/certificate/certificates.php', array('templateid' => $templateid,
    'page' => $page, 'perpage' => $perpage));

// Set up the page.
\tool_certificate\page_helper::page_setup($pageurl, $context, $title);

admin_externalpage_setup('tool_certificate/managetemplates');

$table = new \tool_certificate\certificates_table($templateid, $download);
$table->define_baseurl($pageurl);

if ($table->is_downloading()) {
    $table->download();
    exit();
}

$PAGE->set_url($pageurl);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('certificates', 'tool_certificate'));
$PAGE->set_pagelayout('standard');

$heading = get_string('certificates', 'tool_certificate');

$PAGE->navbar->add($heading);

if ($revokecert && confirm_sesskey()) {
    $nourl = new moodle_url('/admin/tool/certificate/certificates.php', array('templateid' => $templateid));
    $yesurl = new moodle_url('/admin/tool/certificate/certificates.php',
        array('templateid' => $templateid, 'revokecert' => 1, 'issueid' => $issueid, 'confirm' => 1, 'sesskey' => sesskey()));

    if (!$confirm) {
        // Show a confirmation page.
        $PAGE->navbar->add(get_string('deleteconfirm', 'tool_certificate'));
        $message = get_string('revokecertificateconfirm', 'tool_certificate');
        echo $OUTPUT->header();
        echo $OUTPUT->heading($heading);
        echo $OUTPUT->confirm($message, $yesurl, $nourl);
        echo $OUTPUT->footer();
        exit();
    }
    // Delete the template.
    \tool_certificate\certificate::revoke_issue($issueid);

    // Redirect back to the manage templates page.
    redirect(new moodle_url('/admin/tool/certificate/certificates.php', ['templateid' => $templateid]));
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
echo html_writer::div(get_string('certificatesdescription', 'tool_certificate', $template));
if ($canmanage) {
    $newissueurl = new moodle_url('/admin/tool/certificate/issue.php', ['templateid' => $templateid]);
    $newissuestr = get_string('issuenewcertificates', 'tool_certificate');
    echo html_writer::link($newissueurl, $newissuestr, ['class' => 'btn btn-primary']);
}
$table->out($perpage, false);
echo $OUTPUT->footer();
