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
 * Customcert module core interaction API
 *
 * @package    tool_certificate
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

/**
 * Serves certificate issues and other files.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool|null false if file not found, does not return anything if found - just send the file
 */
function tool_certificate_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG;

    require_once($CFG->libdir . '/filelib.php');

    // We are positioning the elements.
    if ($filearea === 'image') {
        if (!\tool_certificate\template::can_verify_loose()) {
            return false;
        }

        $relativepath = implode('/', $args);
        $fullpath = '/' . $context->id . '/tool_certificate/image/' . $relativepath;

        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }

        send_stored_file($file, 0, 0, $forcedownload);
    }
}

/**
 * Serve the edit element as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function tool_certificate_output_fragment_editelement($args) {
    global $DB;

    // Get the element.
    $element = $DB->get_record('tool_certificate_elements', array('id' => $args['elementid']), '*', MUST_EXIST);

    $pageurl = new moodle_url('/admin/tool/certificate/rearrange.php', array('pid' => $element->pageid));
    $form = new \tool_certificate\edit_element_form($pageurl, array('element' => $element));

    return $form->render();
}

/**
 * Add nodes to myprofile page.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 * @return bool
 */
function tool_certificate_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    $url = new moodle_url('/admin/tool/certificate/my_certificates.php', array('userid' => $user->id));
    $node = new core_user\output\myprofile\node('miscellaneous', 'toolcertificatemy',
        get_string('mycertificates', 'tool_certificate'), null, $url);
    $tree->add_node($node);
}

/**
 * Handles editing the 'name' of the element in a list.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param string $newvalue
 * @return \core\output\inplace_editable
 */
function tool_certificate_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $PAGE;

    if ($itemtype === 'elementname') {
        $element = $DB->get_record('tool_certificate_elements', array('id' => $itemid), '*', MUST_EXIST);
        $page = $DB->get_record('tool_certificate_pages', array('id' => $element->pageid), '*', MUST_EXIST);
        $template = $DB->get_record('tool_certificate_templates', array('id' => $page->templateid), '*', MUST_EXIST);

        // Set the template object.
        $template = new \tool_certificate\template($template);
        // Perform checks.
        if ($cm = $template->get_cm()) {
            require_login($cm->course, false, $cm);
        } else {
            $PAGE->set_context(context_system::instance());
            require_login();
        }
        // Make sure the user has the required capabilities.
        $template->require_manage();

        // Clean input and update the record.
        $updateelement = new stdClass();
        $updateelement->id = $element->id;
        $updateelement->name = clean_param($newvalue, PARAM_TEXT);
        $DB->update_record('tool_certificate_elements', $updateelement);

        return new \core\output\inplace_editable('tool_certificate', 'elementname', $element->id, true,
            $updateelement->name, $updateelement->name);
    }
}

/**
 * Get icon mapping for font-awesome.
 */
function tool_certificate_get_fontawesome_icon_map() {
    return [
        'tool_certificate:download' => 'fa-download'
    ];
}

/**
 * Callback to filter form-potential-users-selector
 * @param string $area
 * @param int $itemid
 * @return array
 */
function tool_certificate_potential_users_selector($area, $itemid) {
    if ($area !== 'issue') {
        return null;
    }

    $template = \tool_certificate\template::find_by_id($itemid);

    if ($template->get_tenant_id() == 0 && \tool_certificate\template::can_issue_or_manage_all_tenants()) {
        $join = '';
        $params = [];
        $where = ' ci.id IS NULL OR (ci.expires > 0 AND ci.expires < :now)';
    } else if ($template->can_issue()) {
        list($join, $where, $params) = \tool_tenant\tenancy::get_users_sql('u', $template->get_tenant_id());
        $where .= ' AND (ci.id IS NULL OR (ci.expires > 0 AND ci.expires < :now))';
    } else {
        throw new required_capability_exception(context_system::instance(), 'tool/certificate:issue', 'nopermissions');
    }

    $join .= ' LEFT JOIN {tool_certificate_issues} ci ON u.id = ci.userid AND ci.templateid = :templateid';

    $params['templateid'] = $itemid;
    $params['now'] = time();

    return [$join, $where, $params];
}
