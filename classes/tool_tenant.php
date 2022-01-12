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
 * Implementation of multi-tenancy (used only by plugin tool_tenant and nothing else)
 *
 * @package     tool_certificate
 * @copyright   2020 Moodle Pty Ltd <support@moodle.com>
 * @author      2020 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

/**
 * Implementation of multi-tenancy (used only by plugin tool_tenant and nothing else)
 *
 * @package     tool_certificate
 * @copyright   2020 Moodle Pty Ltd <support@moodle.com>
 * @author      2020 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_tenant {

    /**
     * Callback used by tool_tenant to see which capabilities from this plugin are allowed for the "Tenant administrator" role
     *
     * @return array
     */
    public static function get_tenant_admin_capabilities() {
        return [
            'tool/certificate:issue' => CAP_ALLOW,
            'tool/certificate:viewallcertificates' => CAP_ALLOW,
        ];
    }

}
