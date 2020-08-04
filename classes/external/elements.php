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
 * Class elements
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate\external;

use tool_certificate\template;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

/**
 * Class elements
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class elements extends \external_api {

    /**
     * Returns the delete_element() parameters.
     *
     * @return \external_function_parameters
     */
    public static function delete_element_parameters() {
        return new \external_function_parameters(
            array(
                'id' => new \external_value(PARAM_INT, 'Element id')
            )
        );
    }

    /**
     * Handles delete element
     *
     * @param int $elementid
     */
    public static function delete_element($elementid) {
        $params = self::validate_parameters(self::delete_element_parameters(), ['id' => $elementid]);
        self::validate_context(\context_system::instance());
        $template = template::find_by_element_id($params['id']);
        $template->require_can_manage();
        $template->delete_element($elementid);
    }

    /**
     * Returns the delete_element result value.
     *
     * @return \external_value
     */
    public static function delete_element_returns() {
        return null;
    }

    /**
     * Returns the update_element() parameters.
     *
     * @return \external_function_parameters
     */
    public static function update_element_parameters() {
        return new \external_function_parameters(
            array(
                'id' => new \external_value(PARAM_INT, 'Element id'),
                'sequence' => new \external_value(PARAM_INT, 'Sequence', VALUE_DEFAULT, null),
                'posx' => new \external_value(PARAM_INT, 'X position', VALUE_DEFAULT, null),
                'posy' => new \external_value(PARAM_INT, 'Y position', VALUE_DEFAULT, null),
            )
        );
    }

    /**
     * Handles update element
     *
     * @param int $elementid
     * @param int $sequence
     * @param int $posx
     * @param int $posy
     */
    public static function update_element($elementid, $sequence, $posx, $posy) {
        $params = self::validate_parameters(self::update_element_parameters(),
            ['id' => $elementid, 'sequence' => $sequence, 'posx' => $posx, 'posy' => $posy]);
        self::validate_context(\context_system::instance());
        $template = template::find_by_element_id($params['id']);
        $template->require_can_manage();
        if (isset($params['sequence'])) {
            return $template->update_element_sequence($params['id'], $params['sequence']);
        }
        if (isset($params['posx']) && isset($params['posy'])) {
            foreach ($template->get_pages() as $page) {
                foreach ($page->get_elements() as $element) {
                    if ($element->get_id() == $params['id']) {
                        $element->save((object)['posx' => $params['posx'], 'posy' => $params['posy']]);
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Returns the update_element result value.
     */
    public static function update_element_returns() {
        return new \external_value(PARAM_BOOL, 'success');
    }
}
