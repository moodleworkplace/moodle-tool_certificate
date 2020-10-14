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
 * Class represents a certificate template.
 *
 * @package    tool_certificate
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

use core\message\message;
use core\output\inplace_editable;
use core_user;
use moodle_url;
use tool_certificate\customfield\issue_handler;

defined('MOODLE_INTERNAL') || die();

/**
 * Class represents a certificate template.
 *
 * @package    tool_certificate
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template {

    /** @var persistent\template */
    protected $persistent;

    /** @var page[] */
    protected $pages;

    /**
     * The constructor.
     */
    protected function __construct() {
    }

    /**
     * Instance of a template
     *
     * @param int $id
     * @param null|\stdClass $obj
     * @return template
     */
    public static function instance(int $id = 0, ?\stdClass $obj = null) : template {
        $data = new \stdClass();
        if ($obj !== null) {
            // Ignore fields that are not properties.
            $data = (object)array_intersect_key((array)$obj, \tool_certificate\persistent\template::properties_definition());
        }
        $t = new self();
        $t->persistent = new \tool_certificate\persistent\template($id, $data);
        return $t;
    }

    /**
     * Handles saving data.
     *
     * @param \stdClass $data the template data
     */
    public function save($data) {
        global $DB;
        $this->persistent->set('name', $data->name);
        if (isset($data->contextid)) {
            $this->persistent->set('contextid', $data->contextid);
        }
        if (isset($data->shared)) {
            $this->persistent->set('shared', $data->shared);
        }
        $this->persistent->save();
        \tool_certificate\event\template_updated::create_from_template($this)->trigger();
    }

    /**
     * Template pages
     *
     * @return \tool_certificate\page[]
     */
    public function get_pages() {
        if ($this->pages === null) {
            $this->pages = \tool_certificate\page::get_pages_in_template($this);
        }
        return $this->pages;
    }

    /**
     * New page (not saved)
     *
     * @return page
     */
    public function new_page() {
        $pages = $this->get_pages();

        if ($pages) {
            $lastpage = array_pop($pages);
            $data = $lastpage->to_record();
            unset($data->id, $data->timecreated, $data->timemodified);
            $data->sequence++;
        } else {
            $data = (object)['templateid' => $this->get_id()];
        }
        return page::instance(0, $data);
    }

    /**
     * Handles saving page data.
     *
     * @param \stdClass $data the template data
     */
    public function save_page($data) {
        global $DB;

        // TODO will be split into one form per page.

        // Set the time to a variable.
        $time = time();

        // Get the existing pages and save the page data.
        if ($pages = $DB->get_records('tool_certificate_pages', array('templateid' => $data->tid))) {
            // Loop through existing pages.
            foreach ($pages as $page) {
                // Get the name of the fields we want from the form.
                $width = 'pagewidth_' . $page->id;
                $height = 'pageheight_' . $page->id;
                $leftmargin = 'pageleftmargin_' . $page->id;
                $rightmargin = 'pagerightmargin_' . $page->id;
                // Create the page data to update the DB with.
                $p = new \stdClass();
                $p->id = $page->id;
                $p->width = $data->$width;
                $p->height = $data->$height;
                $p->leftmargin = $data->$leftmargin;
                $p->rightmargin = $data->$rightmargin;
                $p->timemodified = $time;
                // Update the page.
                $DB->update_record('tool_certificate_pages', $p);
            }
        }
        $this->pages = null;
    }

    /**
     * Handles deleting the template.
     */
    public function delete() {
        foreach ($this->get_pages() as $page) {
            $page->delete();
        }

        // Revoke certificate issues.
        $this->revoke_issues();

        $event = \tool_certificate\event\template_deleted::create_from_template($this);

        // Now, finally delete the actual template.
        $this->persistent->delete();

        $event->trigger();
    }

    /**
     * Handles deleting a page from the template.
     *
     * @param int $pageid the template page
     */
    public function delete_page($pageid) {
        global $DB;

        $pages = $this->get_pages();
        if (!array_key_exists($pageid, $pages)) {
            return;
        }
        $sequence = $pages[$pageid]->to_record()->sequence;
        $pages[$pageid]->delete();

        // Now we want to decrease the page number values of
        // the pages that are greater than the page we deleted.
        $sql = "UPDATE {tool_certificate_pages}
                   SET sequence = sequence - 1
                 WHERE templateid = :templateid
                   AND sequence > :sequence";
        $DB->execute($sql, array('templateid' => $this->get_id(), 'sequence' => $sequence));
        $this->pages = null;
    }

    /**
     * Handles deleting an element from the template.
     *
     * @param int $elementid the template page
     */
    public function delete_element($elementid) {
        global $DB;

        // Ensure element exists and delete it.
        $element = $DB->get_record('tool_certificate_elements', array('id' => $elementid), '*', MUST_EXIST);
        if (!array_key_exists($element->pageid, $this->get_pages())) {
            return;
        }

        // Get an instance of the element class.
        try {
            \tool_certificate\element::instance(0, $element)->delete();
        } catch (\moodle_exception $e) {
            // The plugin files are missing, so just remove the entry from the DB.
            $DB->delete_records('tool_certificate_elements', array('id' => $elementid));
        }

        // Now we want to decrease the sequence numbers of the elements
        // that are greater than the element we deleted.
        $sql = "UPDATE {tool_certificate_elements}
                   SET sequence = sequence - 1
                 WHERE pageid = :pageid
                   AND sequence > :sequence";
        $DB->execute($sql, array('pageid' => $element->pageid, 'sequence' => $element->sequence));
    }

    /**
     * Generate the PDF for the template.
     *
     * @param bool $preview True if it is a preview, false otherwise
     * @param \stdClass $issue The issued certificate we want to view
     * @param bool $return
     * @return string|null Return the PDF as string if $return specified
     */
    public function generate_pdf($preview = false, $issue = null, $return = false) {
        global $CFG, $USER;

        if (is_null($issue)) {
            $user = $USER;
        } else {
            $user = \core_user::get_user($issue->userid);
        }

        require_once($CFG->libdir . '/pdflib.php');

        // Get the pages for the template, there should always be at least one page for each template.
        if ($pages = $this->get_pages()) {
            // Create the pdf object.
            $pdf = new \pdf();

            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetTitle($this->get_formatted_name());
            $pdf->SetAutoPageBreak(true, 0);
            // Remove full-stop at the end, if it exists, to avoid "..pdf" being created and being filtered by clean_filename.
            $filename = rtrim($this->get_formatted_name(), '.');
            $filename = clean_filename($filename . '.pdf');
            // Loop through the pages and display their content.
            foreach ($pages as $page) {
                $pagerecord = $page->to_record();
                // Add the page to the PDF.
                if ($pagerecord->width > $pagerecord->height) {
                    $orientation = 'L';
                } else {
                    $orientation = 'P';
                }
                $pdf->AddPage($orientation, array($pagerecord->width, $pagerecord->height));
                $pdf->SetMargins($pagerecord->leftmargin, 0, $pagerecord->rightmargin);
                // Get the elements for the page.
                if ($elements = $page->get_elements()) {
                    // Loop through and display.
                    foreach ($elements as $element) {
                        $element->render($pdf, $preview, $user, $issue);
                    }
                }
            }
            if ($return) {
                return $pdf->Output('', 'S');
            }
            if (defined('PHPUNIT_TEST') and PHPUNIT_TEST) {
                // For some reason phpunit on travis-ci.com do not return 'cli' on php_sapi_name().
                echo $pdf->Output($filename, 'S');
            } else {
                $pdf->Output($filename);
            }
        }
    }

    /**
     * Duplicates the template into a new one
     *
     * @param \context $context
     * @return template
     */
    public function duplicate(?\context $context = null) {
        $data = new \stdClass();
        $data->name = get_string('certificatecopy', 'tool_certificate', $this->get_name());
        $data->shared = $this->get_shared();
        $data->contextid = $context ? $context->id : $this->get_context()->id;
        $newtemplate = self::create($data);

        // Copy the data to the new template.
        foreach ($this->get_pages() as $page) {
            $page->duplicate($newtemplate);
        }

        return $newtemplate;
    }

    /**
     * Move element files related to the template to a new context.
     * Note: Template issue files and shared image files are always stored in system_context, we don't need to move them.
     *
     * @param int $newcontextid
     */
    public function move_files_to_new_context(int $newcontextid) {
        $fs = get_file_storage();
        $oldcontextid = $this->get_context()->id;
        foreach ($this->get_pages() as $page) {
            foreach ($page->get_elements() as $element) {
                $fs->move_area_files_to_new_context($oldcontextid, $newcontextid, 'tool_certificate', 'element',
                    $element->get_id());
                $fs->move_area_files_to_new_context($oldcontextid, $newcontextid, 'tool_certificate', 'elementaux',
                    $element->get_id());
            }
        }
    }

    /**
     * Move page up or down one
     *
     * @param int $pageid
     * @param int $direction
     */
    public function move_page(int $pageid, int $direction) {
        $pages = $this->get_pages();
        $ids = array_keys($pages);
        if (($idx = array_search($pageid, $ids)) === false) {
            return;
        }
        if ($idx + $direction < 0 || $idx + $direction >= count($pages)) {
            return;
        }
        $t = $ids[$idx + $direction];
        $ids[$idx + $direction] = $ids[$idx];
        $ids[$idx] = $t;
        foreach ($ids as $sequence => $id) {
            $pages[$id]->save((object)['sequence' => $sequence]);
        }
        $this->pages = null;
    }

    /**
     * Update element sequence
     *
     * @param int $elementid
     * @param int $sequence
     * @return bool
     */
    public function update_element_sequence(int $elementid, int $sequence) {
        if ($sequence < 1) {
            return false;
        }
        foreach ($this->get_pages() as $page) {
            $elementids = array_keys($page->get_elements());
            if (!in_array($elementid, $elementids)) {
                continue;
            }
            if ($sequence > count($elementids)) {
                return false;
            }
            $elementids = array_diff($elementids, [$elementid]);
            array_splice($elementids, $sequence - 1, 0, [$elementid]);
            $idx = 1;
            foreach ($elementids as $id) {
                if ($page->get_elements()[$id]->get_sequence() != $idx) {
                    $page->get_elements()[$id]->save((object)['sequence' => $idx]);
                }
                $idx++;
            }
            return true;
        }
        return false;
    }

    /**
     * Returns the id of the template.
     *
     * @return int the id of the template
     */
    public function get_id() {
        return $this->persistent->get('id');
    }

    /**
     * Course category where this template is defined or 0 for system
     *
     * @return int
     */
    public function get_category_id() {
        $context = $this->get_context();
        if ($context instanceof \context_coursecat) {
            return $context->instanceid;
        }
        return 0;
    }

    /**
     * Returns the name of the template.
     *
     * @return string the name of the template
     */
    public function get_name() {
        return $this->persistent->get('name');
    }

    /**
     * Returns the shared setting of the template.
     *
     * @return string the shared setting of the template
     */
    public function get_shared() {
        return $this->persistent->get('shared');
    }

    /**
     * Returns the formatted name of the template.
     *
     * @return string the name of the template
     */
    public function get_formatted_name() {
        return format_string($this->get_name(), true, ['escape' => false]);
    }

    /**
     * Get editable name
     *
     * @return inplace_editable
     */
    public function get_editable_name() : inplace_editable {
        $editable = $this->can_manage();
        $displayname = $this->get_formatted_name();
        if ($editable) {
            $displayname = \html_writer::link($this->edit_url(), $displayname);
        }
        return new \core\output\inplace_editable('tool_certificate',
            'templatename', $this->get_id(), $editable,
            $displayname, $this->get_name(),
            get_string('edittemplatename', 'tool_certificate'),
            get_string('newvaluefor', 'form', $this->get_formatted_name()));
    }

    /**
     * Returns the context id.
     *
     * @return \context the context
     */
    public function get_context() {
        return \context::instance_by_id($this->persistent->get('contextid'));
    }

    /**
     * Convert to record
     *
     * @return object
     */
    public function to_record() {
        return $this->persistent->to_record();
    }

    /**
     * Ensures the user has the proper capabilities to manage this template.
     *
     * @throws \required_capability_exception if the user does not have the necessary capabilities (ie. Fred)
     */
    public function require_can_manage() {
        permission::require_can_manage($this->get_context());
    }

    /**
     * The URL to view an issued certificate
     *
     * @param string $code
     * @return \moodle_url
     */
    public static function view_url($code): \moodle_url {
        return new \moodle_url('/admin/tool/certificate/view.php', ['code' => $code]);
    }

    /**
     * The URL to edit certificate template
     *
     * @return \moodle_url
     */
    public function edit_url(): \moodle_url {
        return new \moodle_url('/admin/tool/certificate/template.php', ['id' => $this->get_id()]);
    }

    /**
     * The URL to verify an issued certificate from it's code
     *
     * @param string $code
     * @return \moodle_url
     */
    public static function verification_url($code): \moodle_url {
        return new \moodle_url('/admin/tool/certificate/index.php', ['code' => $code]);
    }

    /**
     * The URL to manage templates
     *
     * @return \moodle_url
     */
    public static function manage_url(): \moodle_url {
        return new \moodle_url('/admin/tool/certificate/manage_templates.php');
    }

    /**
     * Returns a template an element belongs to
     *
     * @param int $id
     * @return template
     */
    public static function find_by_element_id($id) : template {
        global $DB;
        $template = $DB->get_record_sql('SELECT t.* FROM {tool_certificate_templates} t
            JOIN {tool_certificate_pages} p ON p.templateid = t.id
            JOIN {tool_certificate_elements} e ON e.pageid = p.id
            WHERE e.id = :id', ['id' => $id], MUST_EXIST);
        return self::instance(0, $template);
    }

    /**
     * If a user can manage this template.
     *
     * @return bool
     */
    public function can_manage(): bool {
        return permission::can_manage($this->get_context());
    }

    /**
     * If a user can duplicate this template.
     *
     * @param \context $targetcontext
     * @return bool
     */
    public function can_duplicate(\context $targetcontext): bool {
        return permission::can_manage($this->get_context()) && permission::can_manage($targetcontext);
    }

    /**
     * If a user can duplicate this template.
     *
     * @param \context $targetcontext
     */
    public function require_can_duplicate(\context $targetcontext) {
        permission::require_can_manage($this->get_context());
        permission::require_can_manage($targetcontext);
    }

    /**
     * If a user can issue certificates from this template (to anybody)
     *
     * @param \context|null $context
     * @return bool
     */
    public function can_issue_to_anybody(\context $context = null): bool {
        return $this->get_id() && permission::can_issue_to_anybody($context ?? $this->get_context());
    }

    /**
     * If a user can issue certificate from this template to particular user
     *
     * @param int $issuetouserid When issuing to a specific user, validate user's tenant.
     * @param \context|null $context
     * @return bool
     */
    public function can_issue(int $issuetouserid, \context $context = null): bool {
        return $this->can_issue_to_anybody($context) && !permission::is_user_hidden_by_tenancy($issuetouserid);
    }

    /**
     * A user can revoke certificates from this template.
     *
     * @param int $userid
     * @param \context|null $context
     * @return bool
     */
    public function can_revoke(int $userid, \context $context = null): bool {
        return $this->can_issue($userid, $context);
    }

    /**
     * Can view issues for this template
     * @return bool
     */
    public function can_view_issues() {
        return permission::can_view_templates_in_context($this->get_context());
    }

    /**
     * Get issue record from database base on it's code.
     *
     * @param string $issuecode
     * @return \stdClass
     */
    public static function get_issue_from_code($issuecode): ?\stdClass {
        global $DB;
        $record = $DB->get_record('tool_certificate_issues', ['code' => $issuecode]);
        if ($record) {
            $record->customfields = issue_handler::create()->get_instance_data($record->id, true);
        }
        return $record ?: null;
    }

    /**
     * Creates a template.
     *
     * @param \stdClass $formdata Associative array with data to create template.
     * @return \tool_certificate\template the template object
     */
    public static function create($formdata) {
        $template = new \stdClass();
        $template->name = $formdata->name;
        $template->shared = $formdata->shared ?? 0;
        if (!isset($formdata->contextid)) {
            debugging('Context is missing', DEBUG_DEVELOPER);
            $template->contextid = \context_system::instance()->id;
        } else {
            $template->contextid = $formdata->contextid;
        }

        $t = new self();
        $t->persistent = new \tool_certificate\persistent\template(0, $template);
        $t->persistent->save();

        \tool_certificate\event\template_created::create_from_template($t)->trigger();

        return $t;
    }

    /**
     * Finds a certificate template by given name. Used on behat generator.
     *
     * @param string $name Name of the template
     * @return \tool_certificate\template|false
     */
    public static function find_by_name($name) {
        global $DB;
        if ($template = $DB->get_record('tool_certificate_templates', ['name' => $name])) {
            return self::instance(0, $template);
        }
        return false;
    }

    /**
     * Issues a certificate to a user.
     *
     * @param int $userid The ID of the user to issue the certificate to
     * @param int $expires The timestamp when the certificate will expiry. Null if do not expires.
     * @param array $data Additional data that will json_encode'd and stored with the issue.
     * @param string $component The component the certificate was issued by.
     * @param null $courseid
     * @return int The ID of the issue
     */
    public function issue_certificate($userid, $expires = null, array $data = [], $component = 'tool_certificate',
            $courseid = null) {
        global $DB;

        $issue = new \stdClass();
        $issue->userid = $userid;
        $issue->templateid = $this->get_id();
        $issue->code = \tool_certificate\certificate::generate_code($issue->userid);
        $issue->emailed = 0;
        $issue->timecreated = time();
        $issue->expires = $expires;
        $issue->component = $component;
        $issue->courseid = $courseid;

        // Store user fullname.
        $data['userfullname'] = fullname($DB->get_record('user', ['id' => $userid]));
        $issue->data = json_encode($data);

        // Insert the record into the database.
        $issue->id = $DB->insert_record('tool_certificate_issues', $issue);
        issue_handler::create()->save_additional_data($issue, $data);

        // Create the issue file and send notification.
        $issuefile = $this->create_issue_file($issue);
        self::send_issue_notification($issue, $issuefile);

        // Trigger event.
        \tool_certificate\event\certificate_issued::create_from_issue($issue)->trigger();

        return $issue->id;
    }
    /**
     * Creates stored file for an issue.
     *
     * @param \stdClass $issue
     * @param bool $regenerate
     * @return \stored_file
     */
    public function create_issue_file(\stdClass $issue, bool $regenerate = false): \stored_file {
        // Generate issue pdf contents.
        $filecontents = $this->generate_pdf(false, $issue, true);
        // Create a file instance.
        $file = (object) [
            'contextid' => \context_system::instance()->id,
            'component' => 'tool_certificate',
            'filearea'  => 'issues',
            'itemid'    => $issue->id,
            'filepath'  => '/',
            'filename'  => $issue->code . '.pdf'
        ];
        $fs = get_file_storage();

        // If file exists and $regenerate=true, delete current issue file.
        $storedfile = $fs->get_file($file->contextid, $file->component, $file->filearea, $file->itemid, $file->filepath,
            $file->filename);
        if ($storedfile && $regenerate) {
            $storedfile->delete();
        }

        return $fs->create_file_from_string($file, $filecontents);
    }

    /**
     * Gets the stored file for an issue. If issue file doesn't exist new file is created.
     *
     * @param \stdClass $issue
     * @return \stored_file
     */
    public function get_issue_file(\stdClass $issue): \stored_file {
        $fs = get_file_storage();
        $file = $fs->get_file(
            \context_system::instance()->id,
            'tool_certificate',
            'issues',
            $issue->id,
            '/',
            $issue->code . '.pdf'
        );
        if (!$file) {
            $file = $this->create_issue_file($issue);
        }
        return $file;
    }

    /**
     * Sends a moodle notification of the certificate issued.
     *
     * @param \stdClass $issue
     * @param \stored_file $file
     */
    private function send_issue_notification(\stdClass $issue, \stored_file $file): void {
        global $DB;

        $user = core_user::get_user($issue->userid);
        $userfullname = fullname($user, true);
        $mycertificatesurl = new moodle_url('/admin/tool/certificate/my.php');
        $subject = get_string('notificationsubjectcertificateissued', 'tool_certificate');
        $fullmessage = get_string(
            'notificationmsgcertificateissued',
            'tool_certificate',
            ['fullname' => $userfullname, 'url' => $mycertificatesurl->out(false)]
        );

        $message = new message();
        $message->courseid = $issue->courseid ?? SITEID;
        $message->component = 'tool_certificate';
        $message->name = 'certificateissued';
        $message->notification = 1;
        $message->userfrom = core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = $subject;
        $message->contexturl = $mycertificatesurl;
        $message->contexturlname = get_string('mycertificates', 'tool_certificate');
        $message->fullmessage = html_to_text($fullmessage);
        $message->fullmessagehtml = $fullmessage;
        $message->fullmessageformat = FORMAT_HTML;
        $message->smallmessage = '';
        $message->attachment = $file;
        $message->attachname = $file->get_filename();

        if (message_send($message)) {
            $DB->set_field('tool_certificate_issues', 'emailed', 1, ['id' => $issue->id]);
        }
    }

    /**
     * Deletes an issue of a certificate for a user.
     *
     * @param int $issueid
     */
    public function revoke_issue($issueid) {
        global $DB;
        if (!$issue = $DB->get_record('tool_certificate_issues', ['id' => $issueid, 'templateid' => $this->get_id()])) {
            return;
        }
        $DB->delete_records('tool_certificate_issues', ['id' => $issueid]);
        issue_handler::create()->delete_instance($issueid);
        $fs = get_file_storage();
        $fs->delete_area_files(\context_system::instance()->id, 'tool_certificate', 'issues', $issue->id);
        \tool_certificate\event\certificate_revoked::create_from_issue($issue)->trigger();
    }

    /**
     * Deletes issues of a templateid. Used when deleting a template.
     */
    protected function revoke_issues() {
        global $DB;
        $issues = $DB->get_records('tool_certificate_issues', ['templateid' => $this->get_id()]);
        foreach ($issues as $issue) {
            self::revoke_issue($issue->id);
        }
    }

    /**
     * Export
     *
     * @return output\template
     */
    public function get_exporter() : \tool_certificate\output\template {
        return new \tool_certificate\output\template($this->persistent, ['template' => $this]);
    }

    /**
     * Get a mapped list of templates ids and names, sorted by template name.
     *
     * @return array
     */
    public static function get_visible_templates_list(): array {
        global $DB;

        list($sql, $params) = self::get_visible_categories_contexts_sql();
        $sql = "SELECT tct.id, tct.name
                  FROM {tool_certificate_templates} tct
                  JOIN {context} ctx
                    ON ctx.id = tct.contextid AND " . $sql .
            " ORDER BY tct.name";

        $templates = $DB->get_records_sql($sql, $params);

        $list = [];
        foreach ($templates as $t) {
            $list[$t->id] = format_string($t->name);
        }
        return $list;
    }

    /**
     * Subquery for visible contexts for a category/system
     *
     * @return array
     */
    public static function get_visible_categories_contexts_sql() {
        global $DB;
        $contextids = \tool_certificate\permission::get_visible_categories_contexts(false);
        if ($contextids) {
            list($sql, $params) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, 'catparam2');
            return ['ctx.id '.$sql, $params];
        } else {
            return ['1=0', []];
        }
    }
}
