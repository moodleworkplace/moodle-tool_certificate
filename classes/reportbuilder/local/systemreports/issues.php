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

declare(strict_types=1);

namespace tool_certificate\reportbuilder\local\systemreports;

use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\report\action;
use core_reportbuilder\system_report;
use lang_string;
use moodle_url;
use pix_icon;
use stdClass;
use tool_certificate\certificate;
use tool_certificate\reportbuilder\local\entities\issue;
use tool_certificate\template;
use html_writer;

/**
 * Certificate issues system report implementation
 *
 * @package   tool_certificate
 * @copyright 2022 Moodle Pty Ltd <support@moodle.com>
 * @author    2022 Odei Alba <odei.alba@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issues extends system_report {

    /** @var template */
    protected $template;
    /** @var int */
    protected $userid;

    /**
     * Current certificate
     *
     * @return template
     */
    protected function get_template(): template {
        if (!$this->template) {
            $this->template = template::instance($this->get_parameter('templateid', 0, PARAM_INT));
        }
        return $this->template;
    }

    /**
     * Initialise report, we need to set the main table, load our entities and set columns/filters
     */
    protected function initialise(): void {
        // Our main entity, it contains all of the column definitions that we need.
        $entitymain = new issue();
        $entitymainalias = $entitymain->get_table_alias('tool_certificate_issues');

        $this->set_main_table('tool_certificate_issues', $entitymainalias);
        $this->add_entity($entitymain);

        // Restrict to given template.
        if ($templateid = $this->get_parameter('templateid', 0, PARAM_INT)) {
            $this->add_base_condition_simple("{$entitymainalias}.templateid", $templateid);
        }

        // Add user entity.
        $userentity = new user();
        $useralias = $userentity->get_table_alias('user');
        $this->add_entity($userentity);

        // Add user join.
        $this->add_join("JOIN {user} {$useralias} ON {$useralias}.id = {$entitymainalias}.userid");

        // Any columns required by actions should be defined here to ensure they're always available.
        $requiredcolumns = ['code', 'id', 'userid', 'templateid'];
        $this->add_base_fields("{$entitymainalias}." . implode(", {$entitymainalias}.", $requiredcolumns));

        // Add callback for tenant feature.
        $this->add_base_condition_sql(certificate::get_users_subquery($useralias));

        // If this report is used in mod_coursecertificate, add course and group conditions.
        if ($courseid = $this->get_parameter('courseid', 0, PARAM_INT)) {
            $this->add_base_condition_simple("{$entitymainalias}.courseid", $courseid);

            $groupid = $this->get_parameter('groupid', 0, PARAM_INT);
            $groupmode = $this->get_parameter('groupmode', 0, PARAM_INT);
            if (($groupmode != NOGROUPS) && $groupid) {
                $groupjoin = groups_get_members_join([$groupid], "{$useralias}.id");
                $this->add_join($groupjoin->joins, $groupjoin->params, false);
                $this->add_base_condition_sql($groupjoin->wheres);
            }
        }

        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        $this->set_downloadable(true);
        $this->set_initial_sort_column('user:fullnamewithpicturelink', SORT_ASC);
    }

    /**
     * Validates access to view this report
     *
     * @return bool
     */
    protected function can_view(): bool {
        return $this->get_template()->can_view_issues($this->get_context());
    }

    /**
     * Adds the columns we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    public function add_columns(): void {
        // User fullname.
        $certificateissuealias = $this->get_main_table_alias();
        $this->add_column_from_entity('user:fullnamewithpicturelink')
            ->add_field("{$certificateissuealias}.archived")
            ->add_callback([$this, 'apply_archived_label']);

        $columns = [
            'user:email',
            'issue:status',
            'issue:expires',
            'issue:timecreated',
        ];
        $this->add_columns_from_entities($columns);

        // Code with a link.
        $this->add_column_from_entity('issue:codewithlink')
            ->set_title(new lang_string('code', 'tool_certificate'));
    }

    /**
     * Adds the filters we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_filters(): void {
        $filters = [
            'user:fullname',
            'user:email',
            'issue:status',
            'issue:expires',
            'issue:timecreated',
            'issue:archived',
        ];

        $this->add_filters_from_entities($filters);
    }

    /**
     * Add the system report actions. An extra column will be appended to each row, containing all actions added here
     *
     * Note the use of ":id" placeholder which will be substituted according to actual values in the row
     */
    protected function add_actions(): void {
        // View.
        $this->add_action((new action(
            new moodle_url('/admin/tool/certificate/view.php', ['code' => ':code']),
            new pix_icon('i/search', get_string('view'), 'core'),
            [
                'target' => '_blank'
            ],
            false,
            new lang_string('view')
        )));

        // Regenerate file.
        $this->add_action((new action(
            new moodle_url('#'),
            new pix_icon('a/refresh', get_string('regenerateissuefile', 'tool_certificate'), 'core'),
            [
                'data-action' => 'regenerate',
                'data-id' => ':id',
            ],
            false,
            new lang_string('regenerateissuefile', 'tool_certificate')
        ))->add_callback(function() {
            return $this->get_template()->can_issue($this->userid, $this->get_context());
        }));

        // Revoke.
        $this->add_action((new action(
            new moodle_url('#'),
            new pix_icon('i/trash', get_string('revoke', 'tool_certificate'), 'core'),
            [
                'data-action' => 'revoke',
                'data-id' => ':id',
            ],
            false,
            new lang_string('revoke', 'tool_certificate')
        ))->add_callback(function() {
            return $this->get_template()->can_issue($this->userid, $this->get_context());
        }));
    }

    /**
     * Remembers the current user id
     *
     * @param stdClass $row
     */
    public function row_callback(stdClass $row): void {
        $this->userid = (int) $row->userid;
    }

    /**
     * Callback for the fullname to display badge for archived issues.
     *
     * @param string $userfullname
     * @param stdClass $row
     * @return string
     */
    public function apply_archived_label($userfullname, stdClass $row) {
        if ($row->archived) {
            $userfullname .= html_writer::span(get_string('archived', 'tool_certificate'), 'ml-1 badge badge-pill badge-secondary');
        }
        return $userfullname;
    }
}
