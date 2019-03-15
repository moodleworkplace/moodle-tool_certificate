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
 * Edit the certificate settings.
 *
 * @package    tool_certificate
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$templateid = required_param('tid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
if ($action) {
    $actionid = required_param('aid', PARAM_INT);
}
$confirm = optional_param('confirm', 0, PARAM_INT);

admin_externalpage_setup('tool_certificate/managetemplates');

$template = \tool_certificate\template::find_by_id($templateid);
$template->require_manage();

$pageurl = new moodle_url('/admin/tool/certificate/edit.php', array('tid' => $templateid));
$heading = format_string($template->get_name());
$PAGE->navbar->add($heading, new moodle_url('/admin/tool/certificate/edit.php', ['tid' => $templateid]));

$PAGE->set_title($heading);
$PAGE->set_heading($heading);

// Flag to determine if we are deleting anything.
$deleting = false;

if ($templateid) {
    if ($action && confirm_sesskey()) {
        switch ($action) {
            case 'pmoveup' :
                $template->move_item('page', $actionid, 'up');
                break;
            case 'pmovedown' :
                $template->move_item('page', $actionid, 'down');
                break;
            case 'emoveup' :
                $template->move_item('element', $actionid, 'up');
                break;
            case 'emovedown' :
                $template->move_item('element', $actionid, 'down');
                break;
            case 'addpage' :
                $template->add_page();
                $url = new \moodle_url('/admin/tool/certificate/edit.php', array('tid' => $templateid));
                redirect($url);
                break;
            case 'deletepage' :
                if (!empty($confirm)) { // Check they have confirmed the deletion.
                    $template->delete_page($actionid);
                    $url = new \moodle_url('/admin/tool/certificate/edit.php', array('tid' => $templateid));
                    redirect($url);
                } else {
                    // Set deletion flag to true.
                    $deleting = true;
                    // Create the message.
                    $message = get_string('deletepageconfirm', 'tool_certificate');
                    // Create the link options.
                    $nourl = new moodle_url('/admin/tool/certificate/edit.php', array('tid' => $templateid));
                    $yesurl = new moodle_url('/admin/tool/certificate/edit.php',
                        array(
                            'tid' => $templateid,
                            'action' => 'deletepage',
                            'aid' => $actionid,
                            'confirm' => 1,
                            'sesskey' => sesskey()
                        )
                    );
                }
                break;
            case 'deleteelement' :
                if (!empty($confirm)) { // Check they have confirmed the deletion.
                    $template->delete_element($actionid);
                } else {
                    // Set deletion flag to true.
                    $deleting = true;
                    // Create the message.
                    $message = get_string('deleteelementconfirm', 'tool_certificate');
                    // Create the link options.
                    $nourl = new moodle_url('/admin/tool/certificate/edit.php', array('tid' => $templateid));
                    $yesurl = new moodle_url('/admin/tool/certificate/edit.php',
                        array(
                            'tid' => $templateid,
                            'action' => 'deleteelement',
                            'aid' => $actionid,
                            'confirm' => 1,
                            'sesskey' => sesskey()
                        )
                    );
                }
                break;
        }
    }
}

// Check if we are deleting either a page or an element.
if ($deleting) {
    // Show a confirmation page.
    $PAGE->navbar->add(get_string('deleteconfirm', 'tool_certificate'));
    echo $OUTPUT->header();
    echo $OUTPUT->heading($heading);
    echo $OUTPUT->confirm($message, $yesurl, $nourl);
    echo $OUTPUT->footer();
    exit();
}

$mform = new \tool_certificate\edit_form($pageurl, ['template' => $template]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/certificate/manage_templates.php'));
} else if ($data = $mform->get_data()) {

    // Save any page data.
    $template->save_page($data);

    // Loop through the data.
    foreach ($data as $key => $value) {
        // Check if they chose to add an element to a page.
        if (strpos($key, 'addelement_') !== false) {
            // Get the page id.
            $pageid = str_replace('addelement_', '', $key);
            // Get the element.
            $element = "element_" . $pageid;
            $element = $data->$element;
            // Create the URL to redirect to to add this element.
            $params = array();
            $params['tid'] = $template->get_id();
            $params['action'] = 'add';
            $params['element'] = $element;
            $params['pageid'] = $pageid;
            $url = new moodle_url('/admin/tool/certificate/edit_element.php', $params);
            redirect($url);
        }
    }

    // Check if we want to preview this custom certificate.
    if (!empty($data->previewbtn)) {
        redirect($template->preview_url());
    } else if (!$templateid) {
        // Redirect to the editing page to show form with recent updates.
        $url = new moodle_url('/admin/tool/certificate/edit.php', array('tid' => $template->get_id()));
        redirect($url);
    } else {
        redirect(new moodle_url('/admin/tool/certificate/manage_templates.php'));
    }

}

$edit = new \tool_wp\output\page_header_button(get_string('editdetails', 'tool_certificate'),
    ['data-action' => 'editdetails', 'data-id' => $template->get_id(), 'data-name' => $template->get_formatted_name()]);
$PAGE->set_button($edit->render($OUTPUT) . $PAGE->button);
$PAGE->requires->js_call_amd('tool_certificate/template-edit', 'init');

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
