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
 * Handles position elements on the PDF via drag and drop.
 *
 * @package    tool_certificate
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// The page of the certificate we are editing.
$pid = required_param('pid', PARAM_INT);

$PAGE->set_context(null);

require_login();

$PAGE->set_pagelayout('admin');
$PAGE->set_url(new moodle_url('/admin/tool/certificate/rearrange.php', ['pid' => $pid]));

$page = $DB->get_record('tool_certificate_pages', ['id' => $pid], '*', MUST_EXIST);

$template = \tool_certificate\template::find_by_id($page->templateid);

$template->require_manage();

$elements = $DB->get_records('tool_certificate_elements', ['pageid' => $pid], 'sequence');

$editstr = get_string('editcertificate', 'tool_certificate');
$managestr = get_string('managetemplates', 'tool_certificate');

$PAGE->navbar->add($managestr, \tool_certificate\template::manage_url());
$PAGE->navbar->add($template->get_formatted_name(), $template->edit_url());

$PAGE->navbar->add(get_string('rearrangeelements', 'tool_certificate'));

// Include the JS we need.
$PAGE->requires->yui_module('moodle-tool_certificate-rearrange', 'Y.M.tool_certificate.rearrange.init',
    array($template->get_id(),
          $page,
          $elements));

// Create the buttons to save the position of the elements.
$html = html_writer::start_tag('div', array('class' => 'buttons'));
$html .= $OUTPUT->single_button(new moodle_url('/admin/tool/certificate/edit.php', array('tid' => $template->get_id())),
        get_string('saveandclose', 'tool_certificate'), 'get', array('class' => 'savepositionsbtn'));
$html .= $OUTPUT->single_button(new moodle_url('/admin/tool/certificate/rearrange.php', array('pid' => $pid)),
        get_string('saveandcontinue', 'tool_certificate'), 'get', array('class' => 'applypositionsbtn'));
$html .= $OUTPUT->single_button(new moodle_url('/admin/tool/certificate/edit.php', array('tid' => $template->get_id())),
        get_string('cancel'), 'get', array('class' => 'cancelbtn'));
$html .= html_writer::end_tag('div');

// Create the div that represents the PDF.
$style = 'height: ' . $page->height . 'mm; line-height: normal; width: ' . $page->width . 'mm;';
$marginstyle = 'height: ' . $page->height . 'mm; width:1px; float:left; position:relative;';
$html .= html_writer::start_tag('div', array(
    'data-templateid' => $template->get_id(),
    'data-contextid' => $template->get_context()->id,
    'id' => 'pdf',
    'style' => $style)
);
if ($page->leftmargin) {
    $position = 'left:' . $page->leftmargin . 'mm;';
    $html .= "<div id='leftmargin' style='$position $marginstyle'></div>";
}
if ($elements) {
    foreach ($elements as $element) {
        // Get an instance of the element class.
        if ($e = \tool_certificate\element_factory::get_element_instance($element)) {
            switch ($element->refpoint) {
                case \tool_certificate\element_helper::CUSTOMCERT_REF_POINT_TOPRIGHT:
                    $class = 'element refpoint-right';
                    break;
                case \tool_certificate\element_helper::CUSTOMCERT_REF_POINT_TOPCENTER:
                    $class = 'element refpoint-center';
                    break;
                case \tool_certificate\element_helper::CUSTOMCERT_REF_POINT_TOPLEFT:
                default:
                    $class = 'element refpoint-left';
            }
            $html .= html_writer::tag('div', $e->render_html(), array('class' => $class,
                'data-refpoint' => $element->refpoint, 'id' => 'element-' . $element->id));
        }
    }
}
if ($page->rightmargin) {
    $position = 'left:' . ($page->width - $page->rightmargin) . 'mm;';
    $html .= "<div id='rightmargin' style='$position $marginstyle'></div>";
}
$html .= html_writer::end_tag('div');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('rearrangeelementsheading', 'tool_certificate'), 4);
echo $html;
$PAGE->requires->js_call_amd('tool_certificate/rearrange-area', 'init', array('#pdf'));
echo $OUTPUT->footer();
