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
 * View issued certificate as pdf.
 *
 * @package    tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

$issuecode = required_param('code', PARAM_TEXT);
$preview = optional_param('preview', false, PARAM_BOOL);
if ($preview) {

    $templateid = required_param('templateid', PARAM_INT);
    require_login();
    require_capability('tool/certificate:manage', context_system::instance());
    $template = $DB->get_record('tool_certificate_templates', ['id' => $templateid]);
    $template = new \tool_certificate\template($template);
    $template->generate_pdf(true);

} else if ($issue = $DB->get_record('tool_certificate_issues', ['code' => $issuecode], '*')) {
    if (isloggedin() && $issue->userid != $USER->id) {
        // Ok, now check the user has the ability to verify certificates.
        require_capability('tool/certificate:viewallcertificates', context_system::instance());
    }

    $template = $DB->get_record('tool_certificate_templates', ['id' => $issue->templateid]);
    $template = new \tool_certificate\template($template);
    $template->generate_pdf(false, $issue);
} else {
    printerror('notfound');
}
