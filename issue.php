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
 * Issue a new certificate from a template to a user.
 *
 * @package    tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$templateid = required_param('templateid', PARAM_INT);

require_login();

$context = context_system::instance();

require_capability('tool/certificate:issue', $context);

$template = $DB->get_record('tool_certificate_templates', array('id' => $templateid), 'id', MUST_EXIST);

$url = new moodle_url('/admin/tool/certificate/issue.php', array('templateid' => $templateid));

$heading = get_string('issuenewcertificates', 'tool_certificate');

\tool_certificate\page_helper::page_setup($url, $context, $heading);

admin_externalpage_setup('tool_certificate/managetemplates');

$PAGE->navbar->add($heading, $url);

$form = new \tool_certificate\form\certificate_issues($url->out(false));
if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/certificate/certificates.php', array('templateid' => $templateid)));
} else if (($data = $form->get_data()) && !empty($data->users)) {
    $i = 0;
    foreach ($data->users as $userid) {
        $result = \tool_certificate\certificate::issue_certificate($template->id, $userid);
        if ($result) {
            $i++;
        }
    }
    if ($i == 0) {
        $notification = get_string('noissueswerecreated', 'tool_certificate');
    } else if ($i == 1) {
        $notification = get_string('oneissuewascreated', 'tool_certificate');
    } else {
        $notification = get_string('aissueswerecreated', 'tool_certificate', $i);
    }
    redirect($url, $notification);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
echo $form->display();
echo $OUTPUT->footer();
