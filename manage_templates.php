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
 * Manage customcert templates.
 *
 * @package    tool_certificate
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

$contextid = optional_param('contextid', context_system::instance()->id, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 10, PARAM_INT);

if ($action) {
    $tid = required_param('tid', PARAM_INT);
} else {
    $tid = optional_param('tid', 0, PARAM_INT);
}

if ($tid) {
    $template = $DB->get_record('tool_certificate_templates', array('id' => $tid), '*', MUST_EXIST);
    $template = new \tool_certificate\template($template);
}

$context = context::instance_by_id($contextid);

require_login();
require_capability('tool/certificate:manage', $context);

$title = $SITE->fullname;
$heading = $title;

// Set up the page.
$pageurl = new moodle_url('/admin/tool/certificate/manage_templates.php');
\tool_certificate\page_helper::page_setup($pageurl, $context, $title);

// Additional page setup.
if ($tid && $action && confirm_sesskey()) {
    $PAGE->navbar->add(get_string('managetemplates', 'tool_certificate'),
        new moodle_url('/admin/tool/certificate/manage_templates.php'));
} else {
    $PAGE->navbar->add(get_string('managetemplates', 'tool_certificate'));
}

if ($tid) {
    if ($action && confirm_sesskey()) {
        $nourl = new moodle_url('/admin/tool/certificate/manage_templates.php');
        $yesurl = new moodle_url('/admin/tool/certificate/manage_templates.php',
            array(
                'tid' => $tid,
                'action' => $action,
                'confirm' => 1,
                'sesskey' => sesskey()
            )
        );

        // Check if we are deleting a template.
        if ($action == 'delete') {
            if (!$confirm) {
                // Show a confirmation page.
                $PAGE->navbar->add(get_string('deleteconfirm', 'tool_certificate'));
                $message = get_string('deletetemplateconfirm', 'tool_certificate');
                echo $OUTPUT->header();
                echo $OUTPUT->heading($heading);
                echo $OUTPUT->confirm($message, $yesurl, $nourl);
                echo $OUTPUT->footer();
                exit();
            }

            // Delete the template.
            $template->delete();

            // Redirect back to the manage templates page.
            redirect(new moodle_url('/admin/tool/certificate/manage_templates.php'));
        } else if ($action == 'duplicate') {
            if (!$confirm) {
                // Show a confirmation page.
                $PAGE->navbar->add(get_string('duplicateconfirm', 'tool_certificate'));
                $message = get_string('duplicatetemplateconfirm', 'tool_certificate');
                echo $OUTPUT->header();
                echo $OUTPUT->heading($heading);
                echo $OUTPUT->confirm($message, $yesurl, $nourl);
                echo $OUTPUT->footer();
                exit();
            }

            // Create another template to copy the data to.
            $newtemplate = new \stdClass();
            $newtemplate->name = $template->get_name() . ' (' . strtolower(get_string('duplicate', 'tool_certificate')) . ')';
            $newtemplate->contextid = $template->get_contextid();
            $newtemplate->timecreated = time();
            $newtemplate->timemodified = $newtemplate->timecreated;
            $newtemplateid = $DB->insert_record('tool_certificate_templates', $newtemplate);

            // Copy the data to the new template.
            $template->copy_to_template($newtemplateid);

            // Redirect back to the manage templates page.
            redirect(new moodle_url('/admin/tool/certificate/manage_templates.php'));
        }
    }
}

$table = new \tool_certificate\manage_templates_table($context);
$table->define_baseurl($pageurl);

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
$table->out($perpage, false);
$url = new moodle_url('/admin/tool/certificate/edit.php?contextid=' . $contextid);
echo $OUTPUT->single_button($url, get_string('createtemplate', 'tool_certificate'), 'get');
echo $OUTPUT->footer();
