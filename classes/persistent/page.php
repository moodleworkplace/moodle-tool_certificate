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

namespace tool_certificate\persistent;

use core\persistent;

defined('MOODLE_INTERNAL') || die();

/**
 * Class page
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page extends persistent {

    /** @var string */
    const TABLE = 'tool_certificate_pages';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'templateid' => array(
                'type' => PARAM_INT
            ),
            'width' => array(
                'type' => PARAM_INT,
                'default' => 0,
            ),
            'height' => array(
                'type' => PARAM_INT,
                'default' => 0,
            ),
            'leftmargin' => array(
                'type' => PARAM_INT,
                'default' => 0,
            ),
            'rightmargin' => array(
                'type' => PARAM_INT,
                'default' => 0,
            ),
            'sequence' => array(
                'type' => PARAM_INT,
                'default' => 0
            ),
        );
    }

}
