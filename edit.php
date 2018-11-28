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

$templateid = optional_param('tid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
if ($action) {
    $actionid = required_param('aid', PARAM_INT);
}
$confirm = optional_param('confirm', 0, PARAM_INT);

if ($templateid) {
    // Edit an existing template.
    admin_externalpage_setup('tool_certificate/managetemplates');

    $template = \tool_certificate\template::find_by_id($templateid);
    $template->require_manage();

    $pageurl = new moodle_url('/admin/tool/certificate/edit.php', array('tid' => $templateid));
    $heading = get_string('editcertificate', 'tool_certificate');
    $PAGE->navbar->add($heading, new moodle_url('/admin/tool/certificate/edit.php', ['tid' => $templateid]));

} else {
    // Adding a new template.
    admin_externalpage_setup('tool_certificate/addcertificate');

    if (!\tool_certificate\template::can_create()) {
        print_error('createnotallowed', 'tool_certificate');
    }

    $pageurl = new moodle_url('/admin/tool/certificate/edit.php');
    $heading = get_string('addcertificate', 'tool_certificate');
}

$PAGE->set_title(format_string($heading));

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

if ($templateid) {
    $mform = new \tool_certificate\edit_form($pageurl, ['tid' => $templateid, 'tenantid' => $template->get_tenant_id()]);
    // Set the name for the form.
    $mform->set_data(['name' => $template->get_name()]);
} else {
    $mform = new \tool_certificate\edit_form($pageurl);
}

if ($data = $mform->get_data()) {
    // If there is no id, then we are creating a template.
    if (!$templateid) {
        $template = \tool_certificate\template::create($data);

        // Create a page for this template.
        $pageid = $template->add_page();

        // Associate all the data from the form to the newly created page.
        $width = 'pagewidth_' . $pageid;
        $height = 'pageheight_' . $pageid;
        $leftmargin = 'pageleftmargin_' . $pageid;
        $rightmargin = 'pagerightmargin_' . $pageid;
        $rightmargin = 'pagerightmargin_' . $pageid;

        $data->$width = $data->pagewidth_0;
        $data->$height = $data->pageheight_0;
        $data->$leftmargin = $data->pageleftmargin_0;
        $data->$rightmargin = $data->pagerightmargin_0;

        // We may also have clicked to add an element, so these need changing as well.
        if (isset($data->element_0) && isset($data->addelement_0)) {
            $element = 'element_' . $pageid;
            $addelement = 'addelement_' . $pageid;
            $data->$element = $data->element_0;
            $data->$addelement = $data->addelement_0;

            // Need to remove the temporary element and add element placeholders so we
            // don't try add an element to the wrong page.
            unset($data->element_0);
            unset($data->addelement_0);
        }
    }

    // Save any data for the template.
    $template->save($data);

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
    }

    // Redirect to the editing page to show form with recent updates.
    $url = new moodle_url('/admin/tool/certificate/edit.php', array('tid' => $template->get_id()));
    redirect($url);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
$mform->display();
echo $OUTPUT->footer();
