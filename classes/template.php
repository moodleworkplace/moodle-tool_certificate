<?php
// This file is part of Moodle - http://moodle.org/
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

use core\output\inplace_editable;
use tool_tenant\tenancy;

defined('MOODLE_INTERNAL') || die();

/**
 * Class represents a certificate template.
 *
 * @package    tool_certificate
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template {

    /**
     * @var int $id The id of the template.
     */
    protected $id;

    /**
     * @var int $tenantid The tenantid of the template.
     */
    protected $tenantid;

    /**
     * @var string $name The name of this template
     */
    protected $name;

    /**
     * @var int $contextid The context id of this template
     */
    protected $contextid;

    /**
     * @var \context $context The context of this template
     */
    protected $context;

    /**
     * @var int $timecreated The creation time of this template
     */
    protected $timecreated;

    /** @var int $timemodified */
    protected $timemodified;

    /** @var array */
    protected $pages;

    /**
     * The constructor.
     *
     * @param \stdClass $template
     */
    public function __construct($template) {
        $this->id = $template->id;
        $this->tenantid = $template->tenantid;
        $this->name = $template->name;
        if (isset($template->contextid)) {
            $this->contextid = $template->contextid;
            $this->context = \context::instance_by_id($this->contextid);
        } else {
            $this->context = \context_system::instance();
            $this->contextid = $this->context->id;
        }
        if (isset($template->timecreated)) {
            $this->timecreated = $template->timecreated;
        } else {
            $this->timecreated = time();
        }
    }

    /**
     * Handles saving data.
     *
     * @param \stdClass $data the template data
     */
    public function save($data) {
        global $DB;

        $savedata = new \stdClass();
        $savedata->id = $this->id;
        $savedata->name = clean_param($data->name, PARAM_TEXT);
        $savedata->timemodified = time();

        $DB->update_record('tool_certificate_templates', $savedata);
        $this->name = $savedata->name;

        \tool_certificate\event\template_updated::create_from_template($this)->trigger();
    }

    /**
     * Template pages.
     * @return \stdClass[]
     */
    public function get_pages() {
        global $DB;
        if ($this->pages === null) {
            $this->pages = $DB->get_records('tool_certificate_pages',
                ['templateid' => $this->id], 'sequence ASC');
        }
        return $this->pages;
    }

    /**
     * Handles adding another page to the template.
     *
     * @return int the id of the page
     */
    public function add_page() {
        global $DB;

        // Set the page number to 1 to begin with.
        $sequence = 1;
        // Get the max page number.
        $sql = "SELECT MAX(sequence) as maxpage
                  FROM {tool_certificate_pages} cp
                 WHERE cp.templateid = :templateid";
        if ($maxpage = $DB->get_record_sql($sql, array('templateid' => $this->id))) {
            $sequence = $maxpage->maxpage + 1;
        }

        // New page creation.
        $page = new \stdClass();
        $page->templateid = $this->id;
        $page->width = '210';
        $page->height = '297';
        $page->sequence = $sequence;
        $page->timecreated = time();
        $page->timemodified = $page->timecreated;

        // Insert the page.
        $this->pages = null;
        return $DB->insert_record('tool_certificate_pages', $page);
    }

    /**
     * Handles saving page data.
     *
     * @param \stdClass $data the template data
     */
    public function save_page($data) {
        global $DB;

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
        global $DB;

        // Delete the elements.
        $sql = "SELECT e.*
                  FROM {tool_certificate_elements} e
            INNER JOIN {tool_certificate_pages} p
                    ON e.pageid = p.id
                 WHERE p.templateid = :templateid";
        if ($elements = $DB->get_records_sql($sql, array('templateid' => $this->id))) {
            foreach ($elements as $element) {
                // Get an instance of the element class.
                if ($e = \tool_certificate\element_factory::get_element_instance($element)) {
                    $e->delete();
                } else {
                    // The plugin files are missing, so just remove the entry from the DB.
                    $DB->delete_records('tool_certificate_elements', array('id' => $element->id));
                }
            }
        }

        // Delete the pages.
        $DB->delete_records('tool_certificate_pages', array('templateid' => $this->id));

        // Revoke certificate issues.
        $this->revoke_issues();

        // Now, finally delete the actual template.
        $DB->delete_records('tool_certificate_templates', array('id' => $this->id));

        \tool_certificate\event\template_deleted::create_from_template($this)->trigger();
    }

    /**
     * Handles deleting a page from the template.
     *
     * @param int $pageid the template page
     */
    public function delete_page($pageid) {
        global $DB;

        // Get the page.
        $page = $DB->get_record('tool_certificate_pages', array('id' => $pageid), '*', MUST_EXIST);

        // Delete this page.
        $DB->delete_records('tool_certificate_pages', array('id' => $page->id));

        // The element may have some extra tasks it needs to complete to completely delete itself.
        if ($elements = $DB->get_records('tool_certificate_elements', array('pageid' => $page->id))) {
            foreach ($elements as $element) {
                // Get an instance of the element class.
                if ($e = \tool_certificate\element_factory::get_element_instance($element)) {
                    $e->delete();
                } else {
                    // The plugin files are missing, so just remove the entry from the DB.
                    $DB->delete_records('tool_certificate_elements', array('id' => $element->id));
                }
            }
        }

        // Now we want to decrease the page number values of
        // the pages that are greater than the page we deleted.
        $sql = "UPDATE {tool_certificate_pages}
                   SET sequence = sequence - 1
                 WHERE templateid = :templateid
                   AND sequence > :sequence";
        $DB->execute($sql, array('templateid' => $this->id, 'sequence' => $page->sequence));
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

        // Get an instance of the element class.
        if ($e = \tool_certificate\element_factory::get_element_instance($element)) {
            $e->delete();
        } else {
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
     * @param int $issue The issued certificate we want to view
     */
    public function generate_pdf($preview = false, $issue = null) {
        global $CFG, $DB, $USER;

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
            $pdf->SetTitle($this->name);
            $pdf->SetAutoPageBreak(true, 0);
            // Remove full-stop at the end, if it exists, to avoid "..pdf" being created and being filtered by clean_filename.
            $filename = rtrim($this->name, '.');
            $filename = clean_filename($filename . '.pdf');
            // Loop through the pages and display their content.
            foreach ($pages as $page) {
                // Add the page to the PDF.
                if ($page->width > $page->height) {
                    $orientation = 'L';
                } else {
                    $orientation = 'P';
                }
                $pdf->AddPage($orientation, array($page->width, $page->height));
                $pdf->SetMargins($page->leftmargin, 0, $page->rightmargin);
                // Get the elements for the page.
                if ($elements = $DB->get_records('tool_certificate_elements', array('pageid' => $page->id), 'sequence ASC')) {
                    // Loop through and display.
                    foreach ($elements as $element) {
                        // Get an instance of the element class.
                        if ($e = \tool_certificate\element_factory::get_element_instance($element)) {
                            $e->render($pdf, $preview, $user, $issue);
                        }
                    }
                }
            }
            $pdf->Output($filename);
        }
    }

    /**
     * Handles copying this template into another.
     *
     * @param int $copytotemplateid The template id to copy to
     */
    public function copy_to_template($copytotemplateid) {
        global $DB;

        // Get the pages for the template, there should always be at least one page for each template.
        if ($templatepages = $this->get_pages()) {
            // Loop through the pages.
            foreach ($templatepages as $templatepage) {
                $page = clone($templatepage);
                $page->templateid = $copytotemplateid;
                $page->timecreated = time();
                $page->timemodified = $page->timecreated;
                // Insert into the database.
                $page->id = $DB->insert_record('tool_certificate_pages', $page);
                // Now go through the elements we want to load.
                if ($templateelements = $DB->get_records('tool_certificate_elements', array('pageid' => $templatepage->id))) {
                    foreach ($templateelements as $templateelement) {
                        $element = clone($templateelement);
                        $element->pageid = $page->id;
                        $element->timecreated = time();
                        $element->timemodified = $element->timecreated;
                        // Ok, now we want to insert this into the database.
                        $element->id = $DB->insert_record('tool_certificate_elements', $element);
                        // Load any other information the element may need to for the template.
                        if ($e = \tool_certificate\element_factory::get_element_instance($element)) {
                            if (!$e->copy_element($templateelement)) {
                                // Failed to copy - delete the element.
                                $e->delete();
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Duplicates the template into a new one
     *
     * @param int $tenantid
     * @return template
     */
    public function duplicate($tenantid = null) {
        $data = new \stdClass();
        $data->name = $this->get_name() . ' (' . strtolower(get_string('duplicate', 'tool_certificate')) . ')';
        if (isset($tenantid)
                && has_capability('tool/certificate:manageforalltenants', $this->get_context())
                && ($tenantid == 0 || array_key_exists($tenantid, tenancy::get_tenants()))) {
            $data->tenantid = $tenantid;
        } else {
            $data->tenantid = tenancy::get_tenant_id();
        }
        $newtemplate = self::create($data);

        // Copy the data to the new template.
        $this->copy_to_template($newtemplate->get_id());

        return $newtemplate;
    }

    /**
     * Handles moving an item on a template.
     *
     * @param string $itemname the item we are moving
     * @param int $itemid the id of the item
     * @param string $direction the direction
     */
    public function move_item($itemname, $itemid, $direction) {
        global $DB;

        $table = 'tool_certificate_';
        if ($itemname == 'page') {
            $table .= 'pages';
        } else { // Must be an element.
            $table .= 'elements';
        }

        if ($moveitem = $DB->get_record($table, array('id' => $itemid))) {
            // Check which direction we are going.
            if ($direction == 'up') {
                $sequence = $moveitem->sequence - 1;
            } else { // Must be down.
                $sequence = $moveitem->sequence + 1;
            }

            // Get the item we will be swapping with. Make sure it is related to the same template (if it's
            // a page) or the same page (if it's an element).
            if ($itemname == 'page') {
                $params = array('templateid' => $moveitem->templateid);
            } else { // Must be an element.
                $params = array('pageid' => $moveitem->pageid);
            }
            $swapitem = $DB->get_record($table, $params + array('sequence' => $sequence));
        }

        // Check that there is an item to move, and an item to swap it with.
        if ($moveitem && !empty($swapitem)) {
            $DB->set_field($table, 'sequence', $swapitem->sequence, array('id' => $moveitem->id));
            $DB->set_field($table, 'sequence', $moveitem->sequence, array('id' => $swapitem->id));
        }
    }

    /**
     * Returns the id of the template.
     *
     * @return int the id of the template
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Returns the tenantid of the template.
     *
     * @return int the id of the template
     */
    public function get_tenant_id() {
        return $this->tenantid;
    }

    /**
     * Returns the name of the template.
     *
     * @return string the name of the template
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Returns the formatted name of the template.
     *
     * @return string the name of the template
     */
    public function get_formatted_name() {
        return format_string($this->name, true, ['escape' => false]);
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
        return $this->context;
    }

    /**
     * Convert to record
     *
     * @return object
     */
    public function to_record() {
        return (object)[
            'id' => $this->id,
            'name' => $this->name,
            'contextid' => $this->contextid,
            'timecreated' => $this->timecreated,
            'timemodified' => $this->timemodified,
            'tenantid' => $this->tenantid,
        ];
    }

    /**
     * Ensures the user has the proper capabilities to manage this template.
     *
     * @throws \required_capability_exception if the user does not have the necessary capabilities (ie. Fred)
     */
    public function require_manage() {
        if (!$this->can_manage()) {
            throw new \required_capability_exception($this->get_context(), 'tool/certificate:manage', 'nopermission', 'error');
        }
    }

    /**
     * The URL to preview a template
     *
     * @return \moodle_url
     */
    public function preview_url(): \moodle_url {
        return new \moodle_url('/admin/tool/certificate/view.php',
            ['preview' => 1, 'templateid' => $this->get_id(), 'code' => 'previewing']);
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
     * The URL to issue a new certificate from this template
     *
     * @return \moodle_url
     */
    public function new_issue_url(): \moodle_url {
        return new \moodle_url('/admin/tool/certificate/issue.php', ['templateid' => $this->id]);
    }

    /**
     * The URL to edit certificate template
     *
     * @return \moodle_url
     */
    public function edit_url(): \moodle_url {
        return new \moodle_url('/admin/tool/certificate/edit.php', ['tid' => $this->id]);
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
     * The URL to create a new certificate template
     *
     * @return \moodle_url
     */
    public static function new_template_url(): \moodle_url {
        return new \moodle_url('/admin/tool/certificate/edit.php');
    }

    /**
     * Returns a new template from it's id
     *
     * @param int $id
     * @return \tool_certificate\template
     */
    public static function find_by_id($id): template {
        global $DB;
        $template = $DB->get_record('tool_certificate_templates', ['id' => $id], '*', MUST_EXIST);
        return new \tool_certificate\template($template);
    }

    /**
     * Returns an element if it belongs to this template, false otherwise.
     *
     * @param int $id
     * @return bool|\stdClass
     */
    public function find_element_by_id($id) {
        global $DB;
        $element = $DB->get_record('tool_certificate_elements', ['id' => $id], '*', MUST_EXIST);
        $page = $DB->get_record('tool_certificate_pages', ['id' => $element->pageid], '*', MUST_EXIST);
        if ($page->templateid != $this->id) {
            return false;
        }
        return $element;
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
        return new self($template);
    }

    /**
     * Returns a template a page belongs to
     *
     * @param int $id
     * @return template
     */
    public static function find_by_page_id($id) : template {
        global $DB;
        $template = $DB->get_record_sql('SELECT t.* FROM {tool_certificate_templates} t
            JOIN {tool_certificate_pages} p ON p.templateid = t.id
            WHERE p.id = :id', ['id' => $id], MUST_EXIST);
        return new self($template);
    }

    /**
     * Returns a new element if pageid belongs to this template, false otherwise.
     *
     * @param int $pageid
     * @param string $elementtype
     * @return bool|\stdClass
     */
    public function new_element_for_page_id($pageid, $elementtype) {
        global $DB;
        $pagetemplate = $DB->get_field('tool_certificate_pages', 'templateid', ['id' => $pageid], MUST_EXIST);
        if ($pagetemplate != $this->id) {
            return false;
        }
        $element = new \stdClass();
        $element->element = $elementtype;
        $element->pageid = $pageid;
        return $element;
    }

    /**
     * If a user can manage this template.
     *
     * @return bool
     */
    public function can_manage(): bool {
        return has_capability('tool/certificate:manageforalltenants', $this->get_context()) ||
               (has_capability('tool/certificate:manage', $this->get_context()) && $this->tenantid == tenancy::get_tenant_id());
    }

    /**
     * If a user can duplicate this template.
     *
     * @return bool
     */
    public function can_duplicate(): bool {
        return has_capability('tool/certificate:manageforalltenants', $this->get_context()) ||
               (has_capability('tool/certificate:manage', $this->get_context()) &&
                 ($this->tenantid == 0) || ($this->tenantid == tenancy::get_tenant_id()));
    }

    /**
     * If a user can issue certificate from this template.
     *
     * @param int $issuetouserid When issuing to a specific user, validate user's tenant.
     * @return bool
     */
    public function can_issue(int $issuetouserid = 0): bool {
        if (has_capability('tool/certificate:issueforalltenants', $this->get_context())) {
            return true;
        }
        $generalcap = (has_capability('tool/certificate:issue', $this->get_context()) &&
                   (($this->tenantid == 0) || ($this->tenantid == tenancy::get_tenant_id())));
        if ($issuetouserid == 0) {
            return $generalcap;
        }
        return $generalcap && (($this->tenantid == 0) || ($this->tenantid == tenancy::get_tenant_id($issuetouserid)));
    }

    /**
     * A user can revoke certificates from this template.
     *
     * @return bool
     */
    public function can_revoke(): bool {
        // TODO this needs arguments (possibly $userid?) to know which issue are we revoking
        // (it is possible that it is an issue on a shared template from another tenant).
        return $this->can_issue();
    }

    /**
     * Can view issues for this template
     * @return bool
     */
    public function can_view_issues() {
        $context = \context_system::instance();
        if (has_any_capability(['tool/certificate:issueforalltenants', 'tool/certificate:manageforalltenants'],
            $context)) {
            return true;
        }
        if ($this->get_tenant_id() && $this->get_tenant_id() != tenancy::get_tenant_id()) {
            return false;
        }
        return has_any_capability(['tool/certificate:issue', 'tool/certificate:manage',
            'tool/certificate:viewallcertificates'], $context);
    }

    /**
     * If current user can verify certificates
     *
     * @return bool
     */
    public static function can_verify_loose(): bool {
        return has_any_capability(['tool/certificate:issue', 'tool/certificate:issueforalltenants',
                                   'tool/certificate:verify', 'tool/certificate:verifyforalltenants',
                                   'tool/certificate:manage', 'tool/certificate:manageforalltenants',
                                   'tool/certificate:viewallcertificates'], \context_system::instance());

    }

    /**
     * If current user can view the section on admin tree
     *
     * @return bool
     */
    public static function can_view_admin_tree(): bool {
        return has_any_capability(['tool/certificate:issue', 'tool/certificate:issueforalltenants',
                                   'tool/certificate:manage', 'tool/certificate:manageforalltenants',
                                   'tool/certificate:viewallcertificates'], \context_system::instance());
    }

    /**
     * Get issue record from database base on it's code.
     *
     * @param string $issuecode
     * @return \stdClass
     */
    public static function get_issue_from_code($issuecode): \stdClass {
        global $DB;
        return $DB->get_record('tool_certificate_issues', ['code' => $issuecode], '*', MUST_EXIST);
    }

    /**
     * If current user can view an issued certificate
     *
     * @param \stdClass $issue
     * @return bool
     */
    public function can_view_issue($issue): bool {
        global $USER;
        return ($issue->userid == $USER->id) || $this->can_verify();
    }

    /**
     * If current user can view list of certificates
     * @param int $userid The id of user which certificates were issued for.
     */
    public static function can_view_list($userid) {
        global $USER;
        if ($userid == $USER->id) {
            return true;
        }
        $context = \context_system::instance();
        if (has_capability('tool/certificate:issueforalltenants', $context)) {
            return true;
        }
        return (has_any_capability(['tool/certificate:viewallcertificates', 'tool/certificate:issue'],
                                   $context) &&
                (tenancy::get_tenant_id() == tenancy::get_tenant_id($userid)));
    }

    /**
     * If current user can create a certificate template
     */
    public static function can_create() {
        return has_any_capability(['tool/certificate:manage', 'tool/certificate:manageforalltenants'], \context_system::instance());
    }

    /**
     * If current user can issue or manage certificate templates in all tenants.
     */
    public static function can_issue_or_manage_all_tenants() {
        return has_any_capability(['tool/certificate:issueforalltenants', 'tool/certificate:manageforalltenants'],
            \context_system::instance());
    }

    /**
     * If current user can verify issued certificates from this template
     *
     * @return bool
     */
    public function can_verify() {
        if (self::can_verify_for_all_tenants()) {
            return true;
        }
        return has_any_capability(['tool/certificate:verify', 'tool/certificate:issue', 'tool/certificate:viewallcertificates',
                                   'tool/certificate:manage'] , \context_system::instance()) &&
                   (($this->tenantid == 0) || ($this->tenantid == tenancy::get_tenant_id()));
    }

    /**
     * If current user can verify issued certificates on all tenants
     *
     * @return bool
     */
    public static function can_verify_for_all_tenants() {
        if (has_any_capability(['tool/certificate:verifyforalltenants', 'tool/certificate:issueforalltenants',
                                'tool/certificate:manageforalltenants'], \context_system::instance())) {
            return true;
        }
        return false;
    }

    /**
     * Can manage shared images
     * @return bool
     */
    public static function can_manage_images() {
        return has_capability('tool/certificate:imageforalltenants', \context_system::instance());
    }

    /**
     * Creates a template.
     *
     * @param \stdClass $formdata Associative array with data to create template.
     * @return \tool_certificate\template the template object
     */
    public static function create($formdata) {
        global $DB;

        $template = new \stdClass();
        $template->name = $formdata->name;
        $template->contextid = \context_system::instance()->id;
        $template->timecreated = time();
        $template->timemodified = $template->timecreated;
        if (isset($formdata->tenantid)) {
            $template->tenantid = $formdata->tenantid;
        } else {
            $template->tenantid = tenancy::get_default_tenant_id();
        }
        $template->id = $DB->insert_record('tool_certificate_templates', $template);
        $template = new \tool_certificate\template($template);

        \tool_certificate\event\template_created::create_from_template($template)->trigger();

        return $template;
    }

    /**
     * Finds a certificate template by given name. Used on behat generator.
     *
     * @param string $name Name of the template
     * @return \tool_certificate\template
     */
    public static function find_by_name($name) {
        global $DB;
        if ($template = $DB->get_record('tool_certificate_templates', ['name' => $name])) {
            return new \tool_certificate\template($template);
        }
        return false;
    }

    /**
     * Return an array of certificate templates for the given tenantid.
     *
     * @param int $tenantid
     * @return array
     */
    public static function get_all_by_tenantid(int $tenantid): array {
        global $DB;

        $certificates = [];
        if ($templates = $DB->get_records('tool_certificate_templates', ['tenantid' => $tenantid])) {
            foreach ($templates as $t) {
                $certificates[] = new \tool_certificate\template($t);
            }
        }
        return $certificates;
    }

    /**
     * Return an array of certificate templates that are shared or belong to current user's tenant.
     *
     * @return array
     */
    public static function get_all(): array {
        global $DB;

        $certificates = [];

        $sql = "SELECT *
                  FROM {tool_certificate_templates}
                 WHERE tenantid = 0
                    OR tenantid = :tenantid";
        if ($templates = $DB->get_records_sql($sql, ['tenantid' => \tool_tenant\tenancy::get_tenant_id()])) {
            foreach ($templates as $t) {
                $certificates[] = new \tool_certificate\template($t);
            }
        }
        return $certificates;
    }

    /**
     * Issues a certificate to a user.
     *
     * @param int $userid The ID of the user to issue the certificate to
     * @param int $expires The timestamp when the certificate will expiry. Null if do not expires.
     * @param array $data Additional data that will json_encode'd and stored with the issue.
     * @param string $component The component the certificate was issued by.
     * @return int The ID of the issue
     */
    public function issue_certificate($userid, $expires = null, $data = [], $component = 'tool_certificate') {
        global $DB;

        $issue = new \stdClass();
        $issue->userid = $userid;
        $issue->templateid = $this->get_id();
        $issue->code = \tool_certificate\certificate::generate_code();
        $issue->emailed = 0;
        $issue->timecreated = time();
        $issue->expires = $expires;
        $issue->data = json_encode($data);
        $issue->component = $component;

        // Insert the record into the database.
        if ($issue->id = $DB->insert_record('tool_certificate_issues', $issue)) {
            \tool_certificate\event\certificate_issued::create_from_issue($issue)->trigger();
        }

        return $issue->id;
    }

    /**
     * Deletes an issue of a certificate for a user.
     *
     * @param int $issueid
     */
    public function revoke_issue($issueid) {
        global $DB;
        $issue = $DB->get_record('tool_certificate_issues', ['id' => $issueid, 'templateid' => $this->get_id()]);
        $DB->delete_records('tool_certificate_issues', ['id' => $issueid]);
        \tool_certificate\event\certificate_revoked::create_from_issue($issue)->trigger();
    }

    /**
     * Deletes issues of a templateid. Used when deleting a template.
     */
    protected function revoke_issues() {
        global $DB;
        $issues = $DB->get_records('tool_certificate_issues', ['templateid' => $this->get_id()]);
        $DB->delete_records('tool_certificate_issues', ['templateid' => $this->get_id()]);
        foreach ($issues as $issue) {
            \tool_certificate\event\certificate_revoked::create_from_issue($issue)->trigger();
        }
    }
}
