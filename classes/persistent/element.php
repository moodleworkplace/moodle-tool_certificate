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

namespace tool_certificate\persistent;

use core\persistent;

/**
 * Class element
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element extends persistent {

    /** @var string */
    const TABLE = 'tool_certificate_elements';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'pageid' => array(
                'type' => PARAM_INT
            ),
            'name' => array(
                'type' => PARAM_TEXT,
                'default' => ''
            ),
            'element' => array(
                'type' => PARAM_ALPHANUMEXT
            ),
            'data' => array(
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null
            ),
            'font' => array(
                'type' => PARAM_NOTAGS,
                'null' => NULL_ALLOWED,
                'default' => 'freesans',
            ),
            'fontsize' => array(
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null
            ),
            'colour' => array(
                'type' => PARAM_NOTAGS,
                'null' => NULL_ALLOWED,
                'default' => null
            ),
            'posx' => array(
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null
            ),
            'posy' => array(
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null
            ),
            'width' => array(
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null
            ),
            'refpoint' => array(
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null
            ),
            'sequence' => array(
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null
            ),
        );
    }

    /**
     * Magic setter for element
     *
     * @param  string $value
     * @return element
     */
    protected function set_element($value) {
        if ($this->get('id') && $value !== $this->get('element')) {
            throw new \coding_exception('Type of existing element can not be changed');
        }
        return $this->raw_set('element', $value);
    }

    /**
     * Magic setter for pageid
     *
     * @param int $value
     * @return element
     * @throws \coding_exception
     */
    protected function set_pageid($value) {
        if ($this->get('id') && $this->get('pageid') && (int)$value != $this->get('pageid')) {
            throw new \coding_exception('Page of existing element can not be changed');
        }
        return $this->raw_set('pageid', $value);
    }
}
