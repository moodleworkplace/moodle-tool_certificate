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
 * Class element
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate\output;

use core\external\persistent_exporter;
use core\output\inplace_editable;

/**
 * Class element
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element extends persistent_exporter {
    /**
     * Defines the persistent class.
     *
     * @return string
     */
    protected static function define_class(): string {
        return \tool_certificate\persistent\element::class;
    }

    /**
     * Related objects definition.
     *
     * @return array
     */
    protected static function define_related(): array {
        return [
            'element' => \tool_certificate\element::class,
        ];
    }

    /**
     * Get related element
     * @return \tool_certificate\element
     */
    protected function get_element() : \tool_certificate\element {
        return $this->related['element'];
    }

    /**
     * Other properties.
     *
     * @return array
     */
    protected static function define_other_properties(): array {
        return [
            'displayname' => ['type' => PARAM_NOTAGS],
            'editablename' => ['type' => inplace_editable::class],
            'elementtype' => ['type' => PARAM_NOTAGS],
            'movetitle' => ['type' => PARAM_NOTAGS],
            'icon' => ['type' => PARAM_RAW],
            'html' => ['type' => PARAM_RAW],
            'draggable' => ['type' => PARAM_BOOL],
            'showrefpoint' => ['type' => PARAM_BOOL],
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param \renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(\renderer_base $output): array {
        $element = $this->get_element();
        $pluginname = 'certificateelement_' . $this->persistent->get('element');
        return [
            'displayname' => $element->get_display_name(),
            'editablename' => $element->get_inplace_editable()->export_for_template($output),
            'elementtype' => get_string('pluginname', $pluginname),
            'movetitle' => get_string('changeelementsequence', 'tool_certificate'),
            'icon' => $output->render($this->get_element()->get_element_type_image(true)),
            'html' => $this->get_element()->render_html(),
            'draggable' => $this->get_element()->is_draggable(),
            'showrefpoint' => $this->persistent->get('refpoint') !== null
        ];
    }

    /**
     * Get the formatting parameters for the name.
     *
     * @return array
     */
    protected function get_format_parameters_for_name() {
        return [
            'context' => $this->get_element()->get_template()->get_context(),
            'escape' => false
        ];
    }
}
