<?php
// This file is part of the tool_certificate plugin for Moodle - http://moodle.org/
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

use tool_certificate\permission;

/**
 * Serves certificate issues and other files.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool|null false if file not found, does not return anything if found - just send the file
 */
function tool_certificate_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $CFG, $DB;

    require_once($CFG->libdir . '/filelib.php');

    // We are positioning the elements.
    if ($filearea === 'image') {
        if (!permission::can_manage_anywhere()) {
            // Shared images are only displayed to the users during editing of a template.
            return false;
        }

        $relativepath = implode('/', $args);
        $fullpath = '/' . $context->id . '/tool_certificate/image/' . $relativepath;

        $fs = get_file_storage();
        if (!($file = $fs->get_file_by_hash(sha1($fullpath))) || $file->is_directory()) {
            return false;
        }

        send_stored_file($file, 0, 0, $forcedownload);
    }

    // Elements can use several fileareas defined in tool_certificate.
    if ($filearea === 'element' || $filearea === 'elementaux') {
        $elementid = array_shift($args);
        $template = \tool_certificate\template::find_by_element_id($elementid);
        $template->require_can_manage();

        $filename = array_pop($args);
        if (!$args) {
            $filepath = '/';
        } else {
            $filepath = '/' . implode('/', $args) . '/';
        }
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'tool_certificate', $filearea, $elementid, $filepath, $filename);
        if (!$file) {
            return false;
        }
        send_stored_file($file, null, 0, $forcedownload, $options);
    }

    // Issues filearea.
    if ($filearea === 'issues') {
        $filename = array_pop($args); // File name is actually the certificate code.
        $code = pathinfo($filename, PATHINFO_FILENAME);

        $issue = $DB->get_record('tool_certificate_issues', ['code' => $code], '*', MUST_EXIST);
        $template = \tool_certificate\template::instance($issue->templateid);
        if (!permission::can_view_issue($template, $issue) && !permission::can_verify()) {
            return false;
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'tool_certificate', $filearea, $issue->id, '', false);
        if (!$file = reset($files)) {
            return false;
        }
        send_stored_file($file, null, 0, $forcedownload, $options);
    }

    return false;
}

/**
 * Add nodes to myprofile page.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 * @return void
 */
function tool_certificate_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $USER;
    if (permission::can_view_list($user->id, \context_user::instance($user->id))) {
        if ($USER->id == $user->id) {
            $link = get_string('mycertificates', 'tool_certificate');
        } else {
            $link = get_string('certificates', 'tool_certificate');
        }
        $url = new moodle_url('/admin/tool/certificate/my.php', $iscurrentuser ? [] : ['userid' => $user->id]);
        $node = new core_user\output\myprofile\node('miscellaneous', 'toolcertificatemy', $link, null, $url);
        $tree->add_node($node);
    }
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
    global $CFG;
    require_once($CFG->libdir . '/externallib.php');

    $newvalue = clean_param($newvalue, PARAM_TEXT);
    external_api::validate_context(context_system::instance());

    if ($itemtype === 'elementname') {
        // Validate access.
        $element = \tool_certificate\element::instance($itemid);
        $element->get_template()->require_can_manage();
        $element->save((object)['name' => $newvalue]);
        return $element->get_inplace_editable();
    }

    if ($itemtype === 'templatename') {
        // Validate access.
        $template = \tool_certificate\template::instance($itemid);
        $template->require_can_manage();
        $template->save((object)['name' => $newvalue]);
        return $template->get_editable_name();
    }
}

/**
 * Get icon mapping for font-awesome.
 */
function tool_certificate_get_fontawesome_icon_map() {
    return [
        'tool_certificate:download' => 'fa-download',
        'tool_certificate:linkedin' => 'fa-linkedin-square',
    ];
}

/**
 * Display the Certificate link in the course administration menu.
 *
 * @param settings_navigation $navigation The settings navigation object
 * @param stdClass $course The course
 * @param context $context Course context
 */
function tool_certificate_extend_navigation_course($navigation, $course, $context) {
    if (permission::can_view_templates_in_context($context)) {
        $certificatenode = $navigation->add(get_string('certificates', 'tool_certificate'),
            null, navigation_node::TYPE_CONTAINER, null, 'tool_certificate');
        $url = new moodle_url('/admin/tool/certificate/manage_templates.php', ['courseid' => $course->id]);
        $certificatenode->add(get_string('managetemplates', 'tool_certificate'), $url, navigation_node::TYPE_SETTING,
            null, 'tool_certificate');
    }
}

/**
 * Hook called to check if template delete is permitted when deleting category.
 *
 * @param \core_course_category $category The category record.
 * @return bool
 */
function tool_certificate_can_course_category_delete(\core_course_category $category): bool {
    // Deletion requires certificates to be present and permission to manage them.
    $certificatescount = \tool_certificate\certificate::count_templates_in_category($category);
    return !$certificatescount || permission::can_manage($category->get_context());
}

/**
 * Hook called to check if template move is permitted when deleting category.
 *
 * @param \core_course_category $category The category record.
 * @param \core_course_category $newcategory The new category record.
 * @return bool
 */
function tool_certificate_can_course_category_delete_move(\core_course_category $category,
        \core_course_category $newcategory): bool {
    // Deletion with move requires certificates to move to be present and
    // permission to manage them at destination category.
    $certificatescount = \tool_certificate\certificate::count_templates_in_category($category);
    return !$certificatescount || (permission::can_manage($category->get_context())
        && permission::can_manage($newcategory->get_context()));
}

/**
 * Hook called to add information that is displayed on category deletion form.
 *
 * @param \core_course_category $category The category record.
 * @return string
 */
function tool_certificate_get_course_category_contents(\core_course_category $category): string {
    if (\tool_certificate\certificate::count_templates_in_category($category)) {
        return get_string('certificatetemplates', 'tool_certificate');
    }
    return '';
}

/**
 * Hook called before we delete a category.
 * Deletes all the templates in the category.
 *
 * @param \stdClass $category The category record.
 */
function tool_certificate_pre_course_category_delete(\stdClass $category): void {
    $context = context_coursecat::instance($category->id);
    $templates = \tool_certificate\persistent\template::get_records(['contextid' => $context->id]);
    foreach ($templates as $template) {
        \tool_certificate\template::instance(0, $template->to_record())
            ->delete();
    }
}

/**
 * Hook called before we delete a category.
 * Moves all the templates in the deleted category to the new category.
 *
 * @param \core_course_category $category The category record.
 * @param \core_course_category $newcategory The new category record.
 */
function tool_certificate_pre_course_category_delete_move(\core_course_category $category,
          \core_course_category $newcategory): void {
    $context = $category->get_context();
    $newcontext = $newcategory->get_context();
    $templates = \tool_certificate\persistent\template::get_records(['contextid' => $context->id]);
    foreach ($templates as $template) {
        \tool_certificate\template::instance(0, $template->to_record())
            ->move_files_to_new_context($newcontext->id);

        $template->set('contextid', $newcontext->id)->update();
    }
}

/**
 * Callback for theme_workplace, return list of workplace menu items to be added to the launcher.
 *
 * @return array[] The array containing the workplace menu items where each item is an array with keys:
 *                 url => moodle_url where item will redirect
 *                 name => string name shown in the launcher
 *                 imageurl => string url for the icon shown in the launcher
 *                 isglobal (optional) => bool to indicate if item is displayed in the global section.
 */
function tool_certificate_theme_workplace_menu_items(): array {
    global $OUTPUT;

    $menuitems = [];
    if (permission::can_view_admin_tree()) {
        $menuitems[] = [
            'url' => new moodle_url("/admin/tool/certificate/manage_templates.php"),
            'name' => get_string('certificates', 'tool_certificate'),
            'imageurl' => $OUTPUT->image_url('icon', 'tool_certificate')->out(false),
            'isglobal' => component_class_callback('\tool_tenant\permission', 'can_switch_tenant', [], false),
        ];
    }
    return $menuitems;
}
