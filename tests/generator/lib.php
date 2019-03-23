<?php
// This file is part of the tool_certificate for Moodle - http://moodle.org/
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
 * Contains the class responsible for data generation during unit tests
 *
 * @package tool_certificate
 * @category test
 * @copyright 2017 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

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
     * @return stdClass
     */
    public function create_template($record = null): \tool_certificate\template {
        return \tool_certificate\template::create($record);
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
}
