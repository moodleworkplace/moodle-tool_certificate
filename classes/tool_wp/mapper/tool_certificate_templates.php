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
 * Class to allow mapping of certificate templates during export/import
 *
 * @package     tool_certificate
 * @copyright   2020 Moodle Pty Ltd <support@moodle.com>
 * @author      2020 Paul Holden <paulh@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license     Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */

namespace tool_certificate\tool_wp\mapper;

use context;
use tool_certificate\persistent\template;
use tool_wp\export_import_mapper_base;

defined('MOODLE_INTERNAL') || die();

/**
 * Certificate template class
 *
 * @package     tool_certificate
 * @copyright   2020 Moodle Pty Ltd <support@moodle.com>
 * @author      2020 Paul Holden <paulh@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license     Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */
class tool_certificate_templates extends export_import_mapper_base {

    /**
     * Initialises mapper and registers all potential notices
     */
    protected function initialise(): void {
        $this->register_potential_error(self::NOTFOUND, [
            self::ERROR_CONFLICTHEADER => get_string('mappingerrorcertificateheader', 'tool_certificate'),
            self::ERROR_LOG => function(array $identifier): string {
                return get_string('mappingerrorcertificatelog', 'tool_certificate',
                    $this->get_identifier_for_display($identifier));
            },
            self::ERROR_IDENTIFIER => static function(array $identifier) {
                return static::display_identifier_idnumber_name($identifier);
            },
        ]);
    }

    /**
     * Returns an array of certificate properties that can be used to find it during import
     *
     * This function is used when the entity itself is not included in the export
     *
     * @param int $id
     * @return array|null
     */
    public function get_mapping_data_for_workplace_export(int $id): ?array {
        global $DB;

        $certificate = $DB->get_record(template::TABLE, ['id' => $id], 'id, name', IGNORE_MISSING);

        return $certificate ? (array) $certificate : null;
    }

    /**
     * Locate an existing entity, that is available to the current user, by default identifier
     *
     * @param string $identifier the default identifier used by the entity (normally shortname/idnumber)
     * @param int $tenantid strictly inside the given tenant (for entities that can be inside tenants)
     * @return int|null the id of the entity or null if not found or not available
     */
    public function locate_mapping_default(string $identifier, ?int $tenantid = null): ?int {
        if ($certificateid = $this->locate_mapping(['name' => $identifier])) {
            return $certificateid;
        }

        return null;
    }

    /**
     * Locate an existing entity that is available to the current user
     *
     * @param array $identifier array or known entity's attributes, for example:
     *     ['idnumber' => 'OLDNAME', 'shortname' => 'OLDID']
     * @return int|null
     */
    public function locate_mapping(array $identifier): ?int {
        if (!empty($identifier['name']) &&
                $certificates = template::get_records(['name' => $identifier['name']])) {

            // Loop over the matching certificates, find one the user has access to.
            foreach ($certificates as $certificate) {
                $context = context::instance_by_id($certificate->get('contextid'));
                if (has_any_capability([
                        'tool/certificate:issue', 'tool/certificate:manage', 'tool/certificate:viewallcertificates',
                    ], $context)) {

                    return $certificate->get('id');
                }
            }
        }

        return null;
    }
}