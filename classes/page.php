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
 * Class page
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

/**
 * Class page
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page {

    /** @var persistent\page */
    protected $persistent;

    /** @var \tool_certificate\element[] */
    protected $elements = null;

    /** @var template */
    protected $template = null;

    /**
     * page constructor.
     */
    final protected function __construct() {
    }

    /**
     * Helper method to initialise from persistent
     *
     * @param persistent\page $persistent
     * @return page
     */
    protected static function instance_from_persistent(\tool_certificate\persistent\page $persistent) {
        $a = new self();
        $a->persistent = $persistent;
        return $a;
    }

    /**
     * New instance of the page
     *
     * @param int $id
     * @param \stdClass $obj
     * @return page
     */
    public static function instance(int $id = 0, ?\stdClass $obj = null) {
        return self::instance_from_persistent(new \tool_certificate\persistent\page($id, $obj));
    }

    /**
     * Page elements
     *
     * @return \tool_certificate\element[]
     */
    public function get_elements() {
        if ($this->elements === null) {
            $this->elements = \tool_certificate\element::get_elements_in_page($this);
        }
        return $this->elements;
    }

    /**
     * Page id
     * @return int
     */
    public function get_id() {
        return $this->persistent->get('id');
    }

    /**
     * To record
     * @return \stdClass
     */
    public function to_record() : \stdClass {
        return $this->persistent->to_record();
    }

    /**
     * Load a list of records.
     *
     * @param template $template
     *
     * @return self[]
     */
    public static function get_pages_in_template(template $template) {
        /** @var \tool_certificate\persistent\page[] $instances */
        $instances = \tool_certificate\persistent\page::get_records(
            ['templateid' => $template->get_id()], 'sequence', 'ASC');
        $pages = [];
        foreach ($instances as $instance) {
            $page = self::instance_from_persistent($instance);
            $page->template = $template;
            $pages[$instance->get('id')] = $page;
        }
        return $pages;
    }

    /**
     * Get template
     * @return template
     */
    public function get_template() : template {
        if ($this->template === null) {
            $this->template = template::instance($this->persistent->get('templateid'));
        }
        return $this->template;
    }

    /**
     * Delete a page and all elements
     */
    public function delete() {
        global $DB;
        foreach ($this->get_elements() as $element) {
            $element->delete();
        }
        // Cleanup.
        $DB->delete_records(\tool_certificate\persistent\element::TABLE, ['pageid' => $this->get_id()]);

        $this->persistent->delete();
        // TODO trigger event.
    }

    /**
     * Save with new data
     *
     * @param \stdClass $data
     */
    public function save(\stdClass $data) {
        $properties = \tool_certificate\persistent\page::properties_definition();
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $properties) && $key !== 'id') {
                $this->persistent->set($key, $value);
            }
        }
        $this->persistent->save();
    }

    /**
     * Duplicate page with all elements (used inside "duplicate template" task)
     *
     * @param template $template target template
     * @return page
     */
    public function duplicate(template $template) : page {
        $record = $this->persistent->to_record();
        unset($record->id, $record->timemodified, $record->timecreated);
        $record->templateid = $template->get_id();
        $page = self::instance(0, $record);
        $page->template = $template;
        $page->persistent->save();

        foreach ($this->get_elements() as $el) {
            $el->duplicate($page);
        }

        return $page;
    }

    /**
     * Export
     *
     * @return output\page
     */
    public function get_exporter() : \tool_certificate\output\page {
        return new \tool_certificate\output\page($this->persistent, ['page' => $this]);
    }
}
