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

namespace tool_certificate\task;

use core\task\adhoc_task;
use tool_certificate\template;

/**
 *  Ad-hoc task to regenerate certificates
 *
 * @package   tool_certificate
 * @copyright 2025 Moodle Pty Ltd <support@moodle.com>
 * @author    2025 Tasio Bertomeu Gomez <tasio.bertomeu@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class regenerate_certificates extends adhoc_task {
    /**
     * Execute the task
     *
     * @return void
     */
    public function execute(): void {
        global $DB;

        $actiontype = $this->get_custom_data()->actiontype;
        $templateid = $this->get_custom_data()->templateid;
        $issueids = $this->get_custom_data()->issueids;
        $sendnotification = $this->get_custom_data()->sendnotification;

        if ($actiontype === 'regenerateall') {
            $sqlcount = "SELECT COUNT(*) FROM {tool_certificate_issues} WHERE templateid = :templateid AND archived = 0";
            $params = ['templateid' => $templateid];
            $sql = "SELECT * FROM {tool_certificate_issues} WHERE templateid = :templateid AND archived = 0 ORDER BY id ASC";
        } else {
            [$insql, $params] = $DB->get_in_or_equal($issueids, SQL_PARAMS_NAMED);
            $sqlcount = "SELECT COUNT(*) FROM {tool_certificate_issues} WHERE id $insql AND archived = 0";
            $sql = "SELECT * FROM {tool_certificate_issues} WHERE id $insql AND archived = 0 ORDER BY id ASC";
        }
        $numofissues = $DB->count_records_sql($sqlcount, $params);
        $issues = $DB->get_recordset_sql($sql, $params);

        mtrace("Regenerating users certificates. Number of certificates: $numofissues");
        foreach ($issues as $issue) {
            $template = template::instance((int)$issue->templateid);
            if (!$template->can_issue((int)$issue->id)) {
                mtrace("Skipping issue $issue->id: cannot issue certificate.");
                continue;
            }
            $template->create_issue_file($issue, true, (bool) $sendnotification);
        }
        $issues->close();
    }

    /**
     * Queue the task for the next run.
     *
     * @param string $actiontype
     * @param int $templateid
     * @param array $issueids
     * @param bool $sendnotification
     */
    public static function queue(string $actiontype, int $templateid, array $issueids, bool $sendnotification): void {
        $task = new self();
        $task->set_custom_data((object)[
            'actiontype' => $actiontype,
            'templateid' => $templateid,
            'issueids' => $issueids,
            'sendnotification' => $sendnotification,
        ]);
        $task->set_component('tool_certificate');
        \core\task\manager::queue_adhoc_task($task, true);
    }
}
