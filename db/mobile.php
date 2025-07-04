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
 * Tool certificate declaration for mobile app.
 *
 * @package     tool_certificate
 * @copyright   2025 Dani Palou <dani@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    'tool_certificate' => [
        'handlers' => [
            'mycertificates' => [
                'displaydata' => [
                    'title' => 'mycertificates',
                    'icon' => 'fas-medal',
                    'class' => '',
                ],
                'delegate' => 'CoreUserDelegate',
                'method' => 'mobile_my_certificates_view',
                'init' => 'mobile_my_certificates_init',
                'restricttocurrentuser' => true,
                'restricttoenrolledcourses' => false,
                'styles' => [
                    'url' => $CFG->wwwroot . '/admin/tool/certificate/mobileapp/styles/mycertificates.css',
                    'version' => '1',
                ],
            ],
        ],
        'lang' => [
            ['mycertificates', 'tool_certificate'],
            ['mycertificatesdescription', 'tool_certificate'],
            ['nothingtodisplay', 'moodle'],
            ['name', 'moodle'],
            ['issueddate', 'tool_certificate'],
            ['expirydate', 'tool_certificate'],
            ['never', 'moodle'],
            ['expired', 'tool_certificate'],
            ['code', 'tool_certificate'],
            ['verify', 'tool_certificate'],
            ['file', 'moodle'],
            ['shareonlinkedin', 'tool_certificate'],
            ['view', 'moodle'],
            ['viewmore', 'tool_certificate'],
        ],
    ],
];
