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
 * Handles verifying the code for a certificate.
 *
 * @package   tool_certificate
 * @copyright 2017 Mark Nelson <markn@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

$code = optional_param('code', '', PARAM_ALPHANUM); // The code for the certificate we are verifying.

require_login();

$context = context_system::instance();

require_capability('tool/certificate:verifyallcertificates', $context);

$pageurl = new moodle_url('/admin/tool/certificate/index.php');

if ($code) {
    $pageurl->param('code', $code);
}

$heading = get_string('verifycertificates', 'tool_certificate');

$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_title(format_string($heading));
$PAGE->set_heading($SITE->fullname);

$PAGE->navbar->add(get_string('verifycertificates', 'tool_certificate'));

$form = new \tool_certificate\verify_certificate_form($pageurl);

if ($code) {
    $form->set_data(['code' => $code]);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
echo $form->display();
if ($form->get_data()) {
    $result = \tool_certificate\certificate::verify($code);
    $results = new \tool_certificate\output\verify_certificate_results($result);
    $renderer = $PAGE->get_renderer('tool_certificate');
    echo $renderer->render($results);
}
echo $OUTPUT->footer();
