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
 * Class template
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate\output;

use core\external\exporter;
use core\external\persistent_exporter;
use core\output\inplace_editable;
use tool_certificate\element_helper;

/**
 * Class template
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template extends persistent_exporter {
    /**
     * Defines the persistent class.
     *
     * @return string
     */
    protected static function define_class(): string {
        return \tool_certificate\persistent\template::class;
    }

    /**
     * Related objects definition.
     *
     * @return array
     */
    protected static function define_related(): array {
        return [
            'template' => \tool_certificate\template::class,
        ];
    }

    /**
     * Related information - template
     *
     * @return \tool_certificate\template
     */
    protected function get_template() : \tool_certificate\template {
        return $this->related['template'];
    }

    /**
     * Other properties.
     *
     * @return array
     */
    protected static function define_other_properties(): array {
        return [
            'pages' => ['type' => page::class . '[]'],
            'addbutton' => ['type' => 'bool'],
            'addbuttontitle' => ['type' => 'string'],
            'addbuttonicon' => ['type' => 'bool'],
            'elementtypes' => ['type' => 'array'],
        ];
    }

    /**
     * List of add-able types
     *
     * @return array
     */
    protected function get_element_types() {
        $types = element_helper::get_available_element_types();
        $rv = [];
        foreach ($types as $type => $name) {
            $rv[] = ['type' => $type, 'name' => $name];
        }
        return $rv;
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param \renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(\renderer_base $output): array {
        $pages = $this->get_template()->get_pages();
        $exportedpages = [];
        foreach ($pages as $page) {
            $exportedpages[] = $page->get_exporter()->export($output);
        }
        return [
            'pages' => $exportedpages,
            'addbutton' => true,
            'addbuttontitle' => get_string('addcertpage', 'tool_certificate'),
            'addbuttonicon' => true,
            'elementtypes' => $this->get_element_types(),
        ];
    }

    /**
     * Get the formatting parameters for the name.
     *
     * @return array
     */
    protected function get_format_parameters_for_name() {
        return [
            'context' => $this->get_template()->get_context(),
            'escape' => false
        ];
    }
}
