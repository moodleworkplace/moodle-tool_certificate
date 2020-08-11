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
 * This is the external API for this tool.
 *
 * @package    tool_certificate
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_certificate\external;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use external_function_parameters;
use external_value;
use tool_certificate\certificate;

/**
 * This is the external API for this tool.
 *
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class templates extends \external_api {

    /**
     * Returns the duplicate_template() parameters.
     *
     * @return \external_function_parameters
     */
    public static function duplicate_template_parameters() {
        return new \external_function_parameters(
            array(
                'id' => new \external_value(PARAM_INT, 'Template id'),
                'categoryid' => new \external_value(PARAM_INT, 'Category id', VALUE_DEFAULT, 0)
            )
        );
    }

    /**
     * Handles duplicate template
     *
     * @param int $templateid
     * @param int $categoryid
     */
    public static function duplicate_template($templateid, $categoryid) {
        $params = self::validate_parameters(self::duplicate_template_parameters(),
            ['id' => $templateid, 'categoryid' => $categoryid]);
        self::validate_context(\context_system::instance());
        $template = \tool_certificate\template::instance($params['id']);
        $context = $params['categoryid'] ? \context_coursecat::instance($params['categoryid']) : $template->get_context();
        $template->require_can_duplicate($context);

        $template->duplicate($context);
    }

    /**
     * Returns the duplicate_template result value.
     *
     * @return \external_value
     */
    public static function duplicate_template_returns() {
        return null;
    }

    /**
     * Returns the delete_template() parameters.
     *
     * @return \external_function_parameters
     */
    public static function delete_template_parameters() {
        return new \external_function_parameters(
            array(
                'id' => new \external_value(PARAM_INT, 'Template id')
            )
        );
    }

    /**
     * Handles delete template
     *
     * @param int $templateid
     */
    public static function delete_template($templateid) {
        $params = self::validate_parameters(self::delete_template_parameters(),
            ['id' => $templateid]);
        self::validate_context(\context_system::instance());
        $template = \tool_certificate\template::instance($params['id']);
        $template->require_can_manage();

        $template->delete();
    }

    /**
     * Returns the delete_template result value.
     *
     * @return \external_value
     */
    public static function delete_template_returns() {
        return null;
    }
}
