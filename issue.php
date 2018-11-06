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

$templateid = required_param('templateid', PARAM_INT);

require_login();

require_capability('tool/certificate:manage', context_system::instance());

$template = $DB->get_record('tool_certificate_templates', array('id' => $templateid), '*', MUST_EXIST);

$pageurl = $url = new moodle_url('/admin/tool/certificate/certificates.php', array('templateid' => $templateid,
    'page' => $page, 'perpage' => $perpage));

$heading = get_string('issuenewcertificates', 'tool_certificate');

$PAGE->navbar->add($heading);

if ($userids && confirm_sesskey()) {

    // Delete the template.
    \tool_certificate\certificate::issue($userids);

    // Redirect back to the manage templates page.
    redirect(new moodle_url('/admin/tool/certificate/issue.php', ['templateid' => $templateid]));
}
