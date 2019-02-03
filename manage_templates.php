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
 * Manage certificate templates.
 *
 * @package    tool_certificate
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$action = optional_param('action', '', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 10, PARAM_INT);

if ($action) {
    $tid = required_param('tid', PARAM_INT);
} else {
    $tid = optional_param('tid', 0, PARAM_INT);
}

$context = context_system::instance();

admin_externalpage_setup('tool_certificate/managetemplates');

$canissue = has_capability('tool/certificate:issue', $context);
$canmanage = has_any_capability(['tool/certificate:manage', 'tool/certificate:manageforalltenants'], $context);

if (!\tool_certificate\template::can_verify_loose()) {
    print_error('permissiondenied', 'tool_certificate');
}

// Set up the page.
$pageurl = new moodle_url('/admin/tool/certificate/manage_templates.php');

if ($tid) {

    $template = \tool_certificate\template::find_by_id($tid);
    $template->can_manage();

    if ($action && confirm_sesskey()) {
        $url = '/admin/tool/certificate/manage_templates.php';
        $nourl = new moodle_url($url);
        $yesurl = new moodle_url($url, ['tid' => $tid, 'action' => $action, 'confirm' => 1, 'sesskey' => sesskey()]);

        // Check if we are deleting a template.
        if ($action == 'delete') {
            if (!$confirm) {
                // Show a confirmation page.
                $heading = get_string('deleteconfirm', 'tool_certificate');
                $PAGE->navbar->add($heading);
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
            redirect($pageurl);

        } else if ($action == 'duplicate') {
            if (!$confirm) {
                if (has_capability('tool/certificate:manageforalltenants', $context)) {
                    $pageurl->param('tid', $tid);
                    $tenantform = new \tool_certificate\form\tenant_selector($pageurl->out());
                    if ($tenantform->is_cancelled()) {
                        redirect($pageurl);
                    }
                    if ($data = $tenantform->get_data()) {
                        $tenantid = $data->tenantid;
                        $yesurl->param('tenantid', $tenantid);
                    } else {
                        // Show a page to select tenant.
                        $heading = get_string('duplicateselecttenant', 'tool_certificate');
                        $PAGE->navbar->add($heading);
                        echo $OUTPUT->header();
                        echo $OUTPUT->heading($heading);
                        $tenantform->display();
                        echo $OUTPUT->footer();
                        exit();
                    }
                }
                // Show a confirmation page.
                $heading = get_string('duplicateconfirm', 'tool_certificate');
                $PAGE->navbar->add($heading);
                $message = get_string('duplicatetemplateconfirm', 'tool_certificate');
                echo $OUTPUT->header();
                echo $OUTPUT->heading($heading);
                echo $OUTPUT->confirm($message, $yesurl, $nourl);
                echo $OUTPUT->footer();
                exit();
            }

            // Copy the data to the new template.
            $tenantid = optional_param('tenantid', null, PARAM_INT);
            $template->duplicate($tenantid);

            // Redirect back to the manage templates page.
            redirect($pageurl);
        }
    }
}

$PAGE->set_title(get_string('managetemplates', 'tool_certificate'));
$PAGE->set_heading(get_string('managetemplates', 'tool_certificate'));

echo $OUTPUT->header();

$data = ['tabheading' => '', 'addbuttontitle'];
if (\tool_certificate\template::can_create()) {
    $data['addbuttontitle'] = get_string('createtemplate', 'tool_certificate');
    $data['addbuttonurl'] = \tool_certificate\template::new_template_url()->out(false);
}
$report = \tool_reportbuilder\system_report_factory::create(\tool_certificate\certificates_list::class);
$data['certificateslisttable'] = $report->output($OUTPUT);
echo $OUTPUT->render_from_template('tool_certificate/manage_certificates', $data);
echo $OUTPUT->footer();
