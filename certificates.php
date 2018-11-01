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
 * Manage all issued certificates on site.
 *
 * @package    tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

$templateid = required_param('templateid', PARAM_INT);
$download = optional_param('download', null, PARAM_ALPHA);
$downloadcert = optional_param('downloadcert', '', PARAM_BOOL);
if ($downloadcert) {
    $userid = required_param('userid', PARAM_INT);
}

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', \tool_certificate\certificate::CUSTOMCERT_PER_PAGE, PARAM_INT);

require_login();

require_capability('tool/certificate:viewallcertificates', context_system::instance());

$template = $DB->get_record('tool_certificate_templates', array('id' => $templateid), '*', MUST_EXIST);

// Check if we requested to download a certificate.
if ($downloadcert) {
    $template = new \tool_certificate\template($template);
    $template->generate_pdf(false, $userid);
    exit();
}

$pageurl = $url = new moodle_url('/admin/tool/certificate/certificates.php', array('templateid' => $templateid,
    'page' => $page, 'perpage' => $perpage));

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

$PAGE->navbar->add(get_string('mycertificates', 'tool_certificate'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('certificates', 'tool_certificate'));
echo html_writer::div(get_string('certificatesdescription', 'tool_certificate', $template));
$table->out($perpage, false);
echo $OUTPUT->footer();
