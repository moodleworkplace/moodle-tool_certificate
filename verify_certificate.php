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

// This file does not need require_login because capability to verify can be granted to guests, skip codechecker here.
// @codingStandardsIgnoreLine
require_once('../../config.php');

$contextid = optional_param('contextid', context_system::instance()->id, PARAM_INT);
$code = optional_param('code', '', PARAM_ALPHANUM); // The code for the certificate we are verifying.

$context = context::instance_by_id($contextid);

// Set up the page.
$pageurl = new moodle_url('/admin/tool/certificate/verify_certificate.php', array('contextid' => $contextid));

if ($code) {
    $pageurl->param('code', $code);
}

// Ok, a certificate was specified.
if ($context->contextlevel != CONTEXT_SYSTEM) {
    $cm = get_coursemodule_from_id('tool_certificate', $context->instanceid, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $customcert = $DB->get_record('tool_certificate', array('id' => $cm->instance), '*', MUST_EXIST);

    // Check if we are allowing anyone to verify, if so, no need to check login, or permissions.
    if (!$customcert->verifyany) {
        // Need to be logged in.
        require_login($course, false, $cm);
        // Ok, now check the user has the ability to verify certificates.
        require_capability('tool/certificate:verifycertificate', $context);
    } else {
        $PAGE->set_cm($cm, $course);
    }

    $title = $customcert->name;
    $heading = format_string($title);
    $checkallofsite = false;
} else {
    $title = $SITE->fullname;
    $heading = $title;
    $checkallofsite = true;
}

\tool_certificate\page_helper::page_setup($pageurl, $context, $title);

// Additional page setup.
if ($context->contextlevel == CONTEXT_SYSTEM) {
    $PAGE->navbar->add(get_string('verifycertificate', 'tool_certificate'));
}

if ($checkallofsite) {
    // If the 'verifyallcertificates' is not set and the user does not have the capability 'tool/certificate:verifyallcertificates'
    // then show them a message letting them know they can not proceed.
    $verifyallcertificates = get_config('tool_certificate', 'verifyallcertificates');
    $canverifyallcertificates = has_capability('tool/certificate:verifyallcertificates', $context);
    if (!$verifyallcertificates && !$canverifyallcertificates) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading($heading);
        echo $OUTPUT->notification(get_string('cannotverifyallcertificates', 'tool_certificate'));
        echo $OUTPUT->footer();
        exit();
    }
}

// The form we are using to verify these codes.
$form = new \tool_certificate\verify_certificate_form($pageurl);

if ($form->get_data()) {
    $result = new stdClass();
    $result->issues = array();

    // Ok, now check if the code is valid.
    $userfields = get_all_user_name_fields(true, 'u');
    $sql = "SELECT ci.id, ci.customcertid, ci.code, ci.emailed, ci.timecreated,
                   u.id as userid, $userfields, co.id as courseid,
                   co.fullname as coursefullname, c.id as certificateid,
                   c.name as certificatename, c.verifyany
              FROM {customcert} c
              JOIN {tool_certificate_issues} ci
                ON c.id = ci.customcertid
              JOIN {course} co
                ON c.course = co.id
              JOIN {user} u
                ON ci.userid = u.id
             WHERE ci.code = :code";

    if ($checkallofsite) {
        // Only people with the capability to verify all the certificates can verify any.
        if (!$canverifyallcertificates) {
            $sql .= " AND c.verifyany = 1";
        }
        $params = ['code' => $code];
    } else {
        $sql .= " AND c.id = :customcertid";
        $params = ['code' => $code, 'customcertid' => $customcert->id];
    }

    $sql .= " AND u.deleted = 0";

    // It is possible (though unlikely) that there is the same code for issued certificates.
    if ($issues = $DB->get_records_sql($sql, $params)) {
        $result->success = true;
        $result->issues = $issues;
    } else {
        // Can't find it, let's say it's not verified.
        $result->success = false;
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
echo $form->display();
if (isset($result)) {
    $renderer = $PAGE->get_renderer('tool_certificate');
    if ($result->success) {
        foreach ($result->issues as $issue) {
            \tool_certificate\event\certificate_verified::create_from_issue($issue)->trigger();
        }
    }
    $result = new \tool_certificate\output\verify_certificate_results($result);
    echo $renderer->render($result);
}
echo $OUTPUT->footer();
