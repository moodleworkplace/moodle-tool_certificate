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
 * Edit a certificate element.
 *
 * @package    tool_certificate
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$templateid = required_param('tid', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);

admin_externalpage_setup('tool_certificate/managetemplates');

$template = \tool_certificate\template::find_by_id($templateid);

$template->require_manage();

if ($action == 'edit') {
    // The id of the element must be supplied if we are currently editing one.
    $id = required_param('id', PARAM_INT);
    if (!$element = $template->find_element_by_id($id)) {
        print_error('invalidelementfortemplate', 'tool_certificate');
    }
    $pageurl = new moodle_url('/admin/tool/certificate/edit_element.php', ['id' => $id, 'tid' => $templateid, 'action' => $action]);
} else { // Must be adding an element.
    // We need to supply what element we want added to what page.
    $pageid = required_param('pageid', PARAM_INT);
    if (!$element = $template->new_element_for_page_id($pageid)) {
        print_error('invalidpagefortemplate', 'tool_certificate');
    }
    $pageurl = new moodle_url('/admin/tool/certificate/edit_element.php', ['tid' => $templateid, 'element' => $element->element,
        'pageid' => $pageid, 'action' => $action]);
}

$PAGE->navbar->add(get_string('editcertificate', 'tool_certificate'), new moodle_url('/admin/tool/certificate/edit.php',
    array('tid' => $templateid)));

$heading = get_string('editelement', 'tool_certificate');
$PAGE->navbar->add($heading);

$mform = new \tool_certificate\edit_element_form($pageurl, array('element' => $element));

// Check if they cancelled.
if ($mform->is_cancelled()) {
    $url = new moodle_url('/admin/tool/certificate/edit.php', array('tid' => $templateid));
    redirect($url);
}

if ($data = $mform->get_data()) {
    // Set the id, or page id depending on if we are editing an element, or adding a new one.
    if ($action == 'edit') {
        $data->id = $id;
    } else {
        $data->pageid = $pageid;
    }
    // Set the element variable.
    $data->element = $element->element;
    // Get an instance of the element class.
    if ($e = \tool_certificate\element_factory::get_element_instance($data)) {
        $e->save_form_elements($data);
    }

    $url = new moodle_url('/admin/tool/certificate/edit.php', array('tid' => $templateid));
    redirect($url);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
$mform->display();
echo $OUTPUT->footer();
