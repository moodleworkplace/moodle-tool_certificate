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
 * Web service for tool certificate.
 *
 * @package    tool_certificate
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'tool_certificate_revoke_issue' => array(
        'classname'   => \tool_certificate\external\issues::class,
        'methodname'  => 'revoke_issue',
        'classpath'   => '',
        'description' => 'Revoke an issue for a certificate',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'tool_certificate_duplicate_template' => array(
        'classname'   => \tool_certificate\external\templates::class,
        'methodname'  => 'duplicate_template',
        'description' => 'Duplicates a template',
        'type'        => 'write',
        'ajax'        => true,
    ),
    'tool_certificate_delete_template' => array(
        'classname'   => \tool_certificate\external\templates::class,
        'methodname'  => 'delete_template',
        'description' => 'Deletes a template',
        'type'        => 'write',
        'ajax'        => true,
    ),
    'tool_certificate_delete_element' => array(
        'classname'   => \tool_certificate\external\elements::class,
        'methodname'  => 'delete_element',
        'description' => 'Deletes an element',
        'type'        => 'write',
        'ajax'        => true,
    ),
    'tool_certificate_update_element' => array(
        'classname'   => \tool_certificate\external\elements::class,
        'methodname'  => 'update_element',
        'description' => 'Updates an element',
        'type'        => 'write',
        'ajax'        => true,
    ),
    'tool_certificate_potential_certificate_selector' => [
        'classname'   => \tool_certificate\external\templates::class,
        'methodname'  => 'potential_certificate_selector',
        'description' => 'get list of certificates',
        'type'        => 'read',
        'ajax'        => true,
    ],
);
