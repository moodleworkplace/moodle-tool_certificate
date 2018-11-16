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
     * @var int $timecreated The creation time of this template
     */
    protected $timecreated;

    /**
     * The constructor.
     *
     * @param \stdClass $template
     */
    public function __construct($template) {
        $this->id = $template->id;
        $this->tenantid = $template->tenantid;
        $this->name = $template->name;
        $this->contextid = $template->contextid;
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
        $savedata->tenantid = $this->tenantid;
        $savedata->name = $data->name;
        $savedata->timemodified = time();
        $savedata->timecreated = $this->timecreated;
        $savedata->contextid = $this->contextid;

        \tool_certificate\event\template_updated::create_from_template($savedata)->trigger();

        $DB->update_record('tool_certificate_templates', $savedata);
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
    }

    /**
     * Handles deleting the template.
     *
     * @return bool return true if the deletion was successful, false otherwise
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
        if (!$DB->delete_records('tool_certificate_pages', array('templateid' => $this->id))) {
            return false;
        }

        // Revoke certificate issues
        \tool_certificate\certificate::revoke_issues_by_templateid($this->id);

        $deletedtemplate = $DB->get_record('tool_certificate_templates', ['id' => $this->id]);
        \tool_certificate\event\template_deleted::create_from_template($deletedtemplate)->trigger();

        // Now, finally delete the actual template.
        if (!$DB->delete_records('tool_certificate_templates', array('id' => $this->id))) {
            return false;
        }

        return true;
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
     * @param bool $preview true if it is a preview, false otherwise
     * @param int $issue the issued certificate we want to view
     * @param bool $return Do we want to return the contents of the PDF?
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
        if ($pages = $DB->get_records('tool_certificate_pages', array('templateid' => $this->id), 'sequence ASC')) {
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
        if ($templatepages = $DB->get_records('tool_certificate_pages', array('templateid' => $this->id))) {
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
     * @return template
     */
    public function duplicate($tenantid = null) {
        $data = new \stdClass();
        $data->name = $this->get_name() . ' (' . strtolower(get_string('duplicate', 'tool_certificate')) . ')';
        if ($tenantid) {
            $data->tenantid = $tenantid;
        } else {
            $data->tenantid = $this->get_tenant_id();
        }
        $contextid = $this->get_contextid();
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
     * Returns the context id.
     *
     * @return int the context id
     */
    public function get_contextid() {
        return $this->contextid;
    }

    /**
     * Returns the context id.
     *
     * @return \context the context
     */
    public function get_context() {
        return \context::instance_by_id($this->contextid);
    }

    /**
     * Returns the context id.
     *
     * @return \context_module|null the context module, null if there is none
     */
    public function get_cm() {
        $context = $this->get_context();
        if ($context->contextlevel === CONTEXT_MODULE) {
            return get_coursemodule_from_id('tool_certificate', $context->instanceid, 0, false, MUST_EXIST);
        }

        return null;
    }

    /**
     * Ensures the user has the proper capabilities to manage this template.
     *
     * @throws \required_capability_exception if the user does not have the necessary capabilities (ie. Fred)
     */
    public function require_manage() {
        // TODO either get rid of this function or use it always and modify it to check correct permission and the tenant.
        require_capability('tool/certificate:manage', $this->get_context());
    }

    public function preview_url() {
        return new \moodle_url('/admin/tool/certificate/view.php',
            ['preview' => 1, 'templateid' => $this->get_id(), 'code' => 'previewing']);
    }

    public static function view_url($code) {
        return new \moodle_url('/admin/tool/certificate/view.php', ['code' => $code]);
    }

    public static function verification_url($code) {
        return new \moodle_url('/admin/tool/certificate/index.php', ['code' => $code]);
    }

    public static function find_by_id($id) {
        global $DB;
        $template = $DB->get_record('tool_certificate_templates', ['id' => $id]);
        return new \tool_certificate\template($template);
    }

    /**
     * A user can manage all templates in all tenants or just templates on own tenant.
     *
     * @return bool
     */
    public function can_manage() {
        // TODO you have can_manage and require_manage - make one of them call another.
        $context = \context_system::instance();
        return has_capability('tool/certificate:manageforalltenants', $context) ||
               (has_capability('tool/certificate:manage', $context) && $this->tenantid == tenancy::get_tenant_id());
    }

    /**
     * A user can issue certificate for templates.
     *
     * @return bool
     */
    public function can_issue() {
        // TODO check the tenant.
        return has_capability('tool/certificate:issue', \context_system::instance());
    }

    /**
     * Creates a template.
     *
     * @param stdClass $formdata Associative array with data to create template.
     * @return \tool_certificate\template the template object
     */
    public static function create($formdata) {
        global $DB;

        $template = new \stdClass();
        $template->name = $formdata->name;
        $template->contextid = \context_system::instance()->id;
        $template->timecreated = time();
        $template->timemodified = $template->timecreated;
        if (isset($formdata->tenantid) && $formdata->tenantid > 0) {
            $template->tenantid = $formdata->tenantid;
        } else {
            $template->tenantid = tenancy::get_default_tenant_id();
        }
        $template->id = $DB->insert_record('tool_certificate_templates', $template);

        \tool_certificate\event\template_created::create_from_template($template)->trigger();

        return new \tool_certificate\template($template);
    }

    public static function find_by_name($name) {
        global $DB;
        if ($template = $DB->get_record('tool_certificate_templates', ['name' => $name])) {
            return new \tool_certificate\template($template);
        }
        return false;
    }
}
