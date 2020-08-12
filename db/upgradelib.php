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
 * Upgrade functions
 *
 * @package    tool_certificate
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Set contextid instead of tenantid for the templates
 *
 * @param string $tablename for unittests we might need a different table because main table may already not have all fields
 */
function tool_certificate_upgrade_remove_tenant_field($tablename = 'tool_certificate_templates') {
    global $DB;
    $dbman = $DB->get_manager();

    $params = ['coursecat' => CONTEXT_COURSECAT, 'syscontext' => context_system::instance()->id];
    if ($dbman->table_exists(new xmldb_table('tool_tenant'))) {
        $contextids = $DB->get_records_sql_menu('SELECT DISTINCT ct.tenantid, ctx.id AS contextid
            FROM {' . $tablename . '} ct
            LEFT JOIN {tool_tenant} t ON ct.tenantid = t.id
            LEFT JOIN {context} ctx ON t.categoryid = ctx.instanceid AND ctx.contextlevel = :coursecat',
            $params);

        foreach ($contextids as $tenantid => $contextid) {
            $DB->execute('UPDATE {' . $tablename . '} SET contextid = ? WHERE tenantid = ?',
                [$contextid ?: $params['syscontext'], $tenantid]);
        }
    } else {
        $sql = 'UPDATE {' . $tablename . '} SET contextid = :syscontext';
        $DB->execute($sql, $params);
    }
}

/**
 * Move the data from 'data' column into the custom fields
 *
 * @param string $tablename for unittests we might need a different table because main table may already not have all fields
 */
function tool_certificate_upgrade_move_data_to_customfields($tablename = 'tool_certificate_issues') {
    global $DB;

    $records = $DB->get_records($tablename, ['component' => 'tool_dynamicrule'], 'id', 'id,data');
    if (!$records) {
        return;
    }

    $handler = \tool_certificate\customfield\issue_handler::create();
    $handler->create_custom_fields_if_not_exist();
    $allfields = $handler->get_all_fields_shortnames();

    foreach ($records as $record) {
        $data = @json_decode($record->data, true);
        $issuedata = [];
        if (isset($data['certificationname']) && is_string($data['certificationname'])
            && in_array('certificationname', $allfields)) {
            $issuedata['certificationname'] = $data['certificationname'];
            unset($data['certificationname']);
        }
        if (isset($data['programname']) && is_string($data['programname'])
            && in_array('programname', $allfields)) {
            $issuedata['programname'] = $data['programname'];
            unset($data['programname']);
        }
        if (!empty($data['completiondate']) && is_numeric($data['completiondate'])
                && in_array('programcompletiondate', $allfields)) {
            $issuedata['programcompletiondate'] = userdate($data['completiondate'], get_string('strftimedatefullshort'));
            unset($data['completiondate']);
        } else if (isset($data['completiondate']) && empty($data['completiondate'])) {
            unset($data['completiondate']);
        }
        if (!empty($data['completedcourses']) && is_array($data['completedcourses'])
                && in_array('programcompletedcourses', $allfields)) {
            $issuedata['programcompletedcourses'] = '<ul><li>' . join('</li><li>', $data['completedcourses']) . '</li></ul>';
            unset($data['completedcourses']);
        } else if (isset($data['completedcourses']) && empty($data['completedcourses'])) {
            unset($data['completedcourses']);
        }
        if ($issuedata) {
            $handler->save_additional_data($record, $issuedata);
            $DB->update_record($tablename, ['id' => $record->id, 'data' => json_encode($data)]);
        }
    }
}

/**
 * Store user fullname data in tool_certificate_issues 'data' column if it does not exist
 *
 * @param string $tablename for unittests we might need a different table because main table may already not have all fields
 */
function tool_certificate_upgrade_store_fullname_in_data($tablename = 'tool_certificate_issues') {
    global $DB;

    $records = $DB->get_records($tablename);
    if (!$records) {
        return;
    }
    foreach ($records as $record) {
        $data = @json_decode($record->data, true);
        if (!isset($data['userfullname'])) {
            $user = $DB->get_record('user', ['id' => $record->userid]);
            $data = json_encode(['userfullname' => fullname($user)]);
            $DB->update_record($tablename, ['id' => $record->id, 'data' => $data]);
        }
    }
}

/**
 * Finds all templates that use non-existing context and delete them.
 *
 * This is basically replicates what happens on $template->delete() without using API.
 */
function tool_certificate_delete_certificates_with_missing_context() {
    global $DB;

    // Find all templates that use non-existing context.
    $sql = 'SELECT ct.id, ct.contextid FROM {tool_certificate_templates} ct
                    LEFT JOIN {context} ctx ON ct.contextid = ctx.id
                        WHERE ctx.id IS NULL';
    $templates = $DB->get_records_sql($sql);
    foreach ($templates as $template) {
        // Delete page elements.
        $pages = $DB->get_records('tool_certificate_pages', ['templateid' => $template->id]);
        foreach ($pages as $page) {
            // Delete elements in page.
            // File cleanup is not required, it has been done on context deletion.
            $DB->delete_records('tool_certificate_elements', ['pageid' => $page->id]);
        }

        // Delete pages.
        $DB->delete_records('tool_certificate_pages', ['templateid' => $template->id]);

        // Delete issues.
        $issues = $DB->get_records('tool_certificate_issues', ['templateid' => $template->id]);
        $handler = \tool_certificate\customfield\issue_handler::create();
        $fs = get_file_storage();
        foreach ($issues as $issue) {
            $handler->delete_instance($issue->id);
            // Delete issue files.
            $fs->delete_area_files(context_system::instance()->id, 'tool_certificate', 'issues', $issue->id);
        }
        $DB->delete_records('tool_certificate_issues', ['templateid' => $template->id]);

        // Delete template.
        $DB->delete_records('tool_certificate_templates', ['id' => $template->id]);
    }
}
