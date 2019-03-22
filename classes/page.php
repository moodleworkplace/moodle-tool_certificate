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
 * Class page
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

defined('MOODLE_INTERNAL') || die();

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
    protected final function __construct() {
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
            $this->elements = \tool_certificate\element::get_records(
                ['pageid' => $this->persistent->get('id')], 'sequence', 'ASC');
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
     * @param array $filters Filters to apply.
     * @param string $sort Field to sort by.
     * @param string $order Sort order.
     * @param int $skip Limitstart.
     * @param int $limit Number of rows to return.
     *
     * @return self[]
     */
    public static function get_records($filters = array(), $sort = '', $order = 'ASC', $skip = 0, $limit = 0) {
        $instances = \tool_certificate\persistent\page::get_records($filters, $sort, $order, $skip, $limit);
        $pages = [];
        foreach ($instances as $instance) {
            $pages[$instance->get('id')] = self::instance_from_persistent($instance);
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
     * Duplicate page
     *
     * @param int $templateid target template id
     * @param bool $withelements
     * @return page
     */
    public function duplicate(int $templateid, bool $withelements = true) : page {
        $record = $this->persistent->to_record();
        unset($record->id, $record->timemodified, $record->timecreated);
        if ($templateid == $record->templateid) {
            $record->sequence++;
        }
        $record->templateid = $templateid;
        $page = self::instance(0, $record);
        $page->persistent->save();

        if ($withelements) {
            foreach ($this->get_elements() as $el) {
                $el->duplicate($page->get_id());
            }
        }

        return $page;
    }
}
