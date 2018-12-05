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

admin_externalpage_setup('tool_certificate/managetemplates');

$template = \tool_certificate\template::find_by_id($templateid);

if (!$template->can_issue()) {
    print_error('issuenotallowed', 'tool_certificate');
}

$customdata = ['excludeduserids' => $template->get_issued_user_ids(), 'tenantid' => $template->get_tenant_id()];
$form = new \tool_certificate\form\certificate_issues($template->new_issue_url()->out(), $customdata);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/certificate/certificates.php', ['templateid' => $templateid]));
} else if (($data = $form->get_data()) && !empty($data->users)) {
    $i = 0;
    foreach ($data->users as $userid) {
        if ($template->can_issue($userid)) {
            $result = $template->issue_certificate($userid, $data->expires);
            if ($result) {
                $i++;
            }
        }
    }
    if ($i == 0) {
        $notification = get_string('noissueswerecreated', 'tool_certificate');
    } else if ($i == 1) {
        $notification = get_string('oneissuewascreated', 'tool_certificate');
    } else {
        $notification = get_string('aissueswerecreated', 'tool_certificate', $i);
    }
    redirect(new moodle_url('/admin/tool/certificate/certificates.php', ['templateid' => $templateid]), $notification);
}

$url = new moodle_url('/admin/tool/certificate/issue.php', ['templateid' => $templateid]);
$heading = get_string('issuenewcertificates', 'tool_certificate');

$PAGE->navbar->add($heading, $url);

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
echo $form->display();
echo $OUTPUT->footer();
