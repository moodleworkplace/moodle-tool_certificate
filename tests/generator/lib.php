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
 * The class responsible for data generation during unit tests
 *
 * @package tool_certificate
 * @category test
 * @copyright 2017 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_certificate_generator extends component_generator_base {

    /**
     * Creates new certificate template
     *
     * @param array|stdClass $record
     * @return \tool_certificate\template
     */
    public function create_template($record = null): \tool_certificate\template {
        $record = (object)$record;
        if (isset($record->tenantid)) {
            debugging('Tenantid is no longer supported', DEBUG_DEVELOPER);
        }
        if (empty($record->contextid)) {
            if (!empty($record->categoryid)) {
                $record->contextid = context_coursecat::instance($record->categoryid)->id;
                unset($record->categoryid);
            } else {
                $record->contextid = context_system::instance()->id;
            }
        }
        return \tool_certificate\template::create($record);
    }

    /**
     * Looks up a template by name or id
     * @param string $nameorid
     * @return int
     */
    public function lookup_template(string $nameorid): int {
        global $DB;
        if (empty($nameorid)) {
            return 0;
        }
        if ($DB->record_exists(\tool_certificate\persistent\template::TABLE, ['id' => (int) $nameorid])) {
            return $nameorid;
        }
        return $DB->get_field_select(\tool_certificate\persistent\template::TABLE, 'id',
            'name = ?', [$nameorid, $nameorid], MUST_EXIST);
    }

    /**
     * Create a page
     *
     * @param \tool_certificate\template|int $template
     * @param array|stdClass $record
     * @return \tool_certificate\page
     */
    public function create_page($template, $record = null) : \tool_certificate\page {
        if (!$template instanceof \tool_certificate\template) {
            $template = \tool_certificate\template::instance($template);
        }
        $page = $template->new_page();
        $page->save((object)($record ?: []));
        return $page;
    }

    /**
     * New instance of an element class (not saved)
     *
     * @param int $pageid
     * @param string $elementtype
     * @param array $data
     * @return \tool_certificate\element
     */
    public function new_element(int $pageid, string $elementtype, $data = []) {
        $data = (array)$data;
        $data['element'] = $elementtype;
        $data['pageid'] = $pageid;
        return \tool_certificate\element::instance(0, (object)$data);
    }

    /**
     * Creates an element on a page
     *
     * @param int $pageid
     * @param string $elementtype
     * @param array $data
     * @return \tool_certificate\element
     */
    public function create_element(int $pageid, string $elementtype, $data = []) {
        $el = $this->new_element($pageid, $elementtype);
        if ($data) {
            $el->save_form_data((object)$data);
        } else {
            $el->save((object)[]);
        }
        return $el;
    }

    /**
     * Issue a certificate
     *
     * @param int|stdClass|\tool_certificate\template $certificate
     * @param stdClass|int $user
     * @param int $expires
     * @param array $data
     * @param string $component
     * @return stdClass
     */
    public function issue($certificate, $user, $expires = null, $data = [], $component = 'tool_certificate') {
        global $DB;
        if (is_int($certificate)) {
            $certificate = \tool_certificate\template::instance($certificate);
        } else if (!$certificate instanceof \tool_certificate\template) {
            $certificate = \tool_certificate\template::instance(0, $certificate);
        }
        $userid = is_object($user) ? $user->id : $user;
        $issueid = $certificate->issue_certificate($userid, $expires, $data, $component);
        return $DB->get_record('tool_certificate_issues', ['id' => $issueid], '*', MUST_EXIST);
    }

    /**
     * Generate pdf and returns as string
     *
     * @param int|stdClass|\tool_certificate\template $certificate
     * @param bool $preview
     * @param null $issue
     * @return string
     */
    public function generate_pdf($certificate, $preview = false, $issue = null) {
        $instance = null;
        $instanceid = 0;
        if ($certificate instanceof \tool_certificate\template) {
            $instance = $certificate->to_record();
        } else if (is_object($certificate)) {
            $instance = $certificate;
        } else {
            $instanceid = $certificate;
        }

        ob_start();
        \tool_certificate\template::instance($instanceid, $instance)->generate_pdf($preview, $issue);
        $filecontents = ob_get_contents();
        ob_end_clean();

        return $filecontents;
    }

    /**
     * Assigns manage capability.
     *
     * @param int $userid
     * @param int $roleid
     * @param context $context
     * @return void
     */
    public function assign_manage_capability(int $userid, int $roleid, context $context): void {
        assign_capability('tool/certificate:manage', CAP_ALLOW, $roleid, $context->id);
        role_assign($roleid, $userid, $context->id);
    }
}
