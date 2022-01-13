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
 * Privacy Subsystem implementation for tool_certificate.
 *
 * @package    tool_certificate
 * @copyright  2018 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_certificate\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use tool_certificate\customfield\issue_handler;

/**
 * Privacy Subsystem implementation for tool_certificate.
 *
 * @copyright  2018 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider,
                          \core_privacy\local\request\subsystem\plugin_provider,
                          \core_privacy\local\request\core_user_data_provider,
                          \core_privacy\local\request\core_userlist_provider {

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $items) : collection {
        $items->add_database_table(
            'tool_certificate_issues',
            [
                'userid' => 'privacy:metadata:tool_certificate_issues:userid',
                'templateid' => 'privacy:metadata:tool_certificate_issues:templateid',
                'code' => 'privacy:metadata:tool_certificate_issues:code',
                'expires' => 'privacy:metadata:tool_certificate_issues:expires',
                'timecreated' => 'privacy:metadata:tool_certificate_issues:timecreated',
            ],
            'privacy:metadata:tool_certificate:issues'
        );

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        global $DB;
        $contextlist = new contextlist();
        if ($DB->record_exists('tool_certificate_issues', ['userid' => $userid])) {
            $contextlist->add_system_context();
        }
        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_system) {
            return;
        }
        $sql = "SELECT userid FROM {tool_certificate_issues}";
        $userlist->add_from_sql('userid', $sql, []);
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();

        $sql = 'SELECT i.*, t.name as certificatename
                  FROM {tool_certificate_issues} i
                  JOIN {tool_certificate_templates} t
                    ON (t.id = i.templateid)
                 WHERE i.userid = :userid
              ORDER BY i.timecreated, i.id ASC';

        $context = \context_user::instance($user->id);
        $contextpath = [get_string('certificates', 'tool_certificate')];

        $recordset = $DB->get_recordset_sql($sql, ['userid' => $user->id]);
        foreach ($recordset as $record) {
            $data = (object) [
                'name' => format_string($record->certificatename),
                'code' => $record->code,
                'data' => self::export_issue_data($record->id),
                'timecreated' => transform::datetime($record->timecreated),
                'expires' => $record->expires ? transform::datetime($record->expires) : null,
            ];

            writer::with_context($context)->export_data(array_merge($contextpath, [
                clean_param($record->certificatename, PARAM_FILE)
            ]), $data);
        }

        $recordset->close();
    }

    /**
     * Export json-encoded issue data
     *
     * @param int $id
     * @return mixed|null
     */
    protected static function export_issue_data($id) {
        $handler = issue_handler::create();
        $data = [];
        foreach ($handler->export_instance_data_object($id, true) as $key => $value) {
            if (strlen('' . $value)) {
                $data[$key] = $value;
            }
        }
        return $data ?: null;
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_system) {
            return;
        }

        // Delete issue files.
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'tool_certificate', 'issues');

        // Delete issue records.
        $DB->delete_records('tool_certificate_issues');
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_system) {
                continue;
            }

            // Delete issue files.
            $fs = get_file_storage();
            $issues = $DB->get_records('tool_certificate_issues', ['userid' => $userid]);
            foreach ($issues as $issue) {
                $fs->delete_area_files($context->id, 'tool_certificate', 'issues', $issue->id);
            }

            // Delete issue records.
            $DB->delete_records('tool_certificate_issues', ['userid' => $userid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if (!$context instanceof \context_system) {
            return;
        }
        list($userinsql, $userinparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);

        // Delete issue files.
        $fs = get_file_storage();
        $issues = $DB->get_records_select('tool_certificate_issues', ' userid ' . $userinsql, $userinparams);
        foreach ($issues as $issue) {
            $fs->delete_area_files($context->id, 'tool_certificate', 'issues', $issue->id);
        }
        // Delete issue records.
        $DB->delete_records_select('tool_certificate_issues', ' userid ' . $userinsql, $userinparams);
    }
}
