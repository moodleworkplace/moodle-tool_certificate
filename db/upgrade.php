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
 * Customcert module upgrade code.
 *
 * @package    tool_certificate
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Customcert module upgrade code.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool always true
 */
function xmldb_tool_certificate_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2018051709) {

        // Define field expires to be added to tool_certificate_issues.
        $table = new xmldb_table('tool_certificate_issues');
        $field = new xmldb_field('expires', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timecreated');

        // Conditionally launch add field expires.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null, 'expires');

        // Conditionally launch add field data.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('component', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'data');

        // Conditionally launch add field component.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Certificate savepoint reached.
        upgrade_plugin_savepoint(true, 2018051709, 'tool', 'certificate');
    }

    if ($oldversion < 2018051710) {

        // Define field id to be added to tool_certificate_templates.
        $table = new xmldb_table('tool_certificate_templates');
        $field = new xmldb_field('tenantid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timemodified');

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $key = new xmldb_key('tenantid', XMLDB_KEY_FOREIGN, ['tenantid'], 'tool_tenant', ['id']);

        // Launch add key tenantid.
        $dbman->add_key($table, $key);

        // Certificate savepoint reached.
        upgrade_plugin_savepoint(true, 2018051710, 'tool', 'certificate');
    }

    if ($oldversion < 2019020300) {

        // Define key tenantid (foreign) to be dropped form tool_certificate_templates.
        $table = new xmldb_table('tool_certificate_templates');
        $key = new xmldb_key('tenantid', XMLDB_KEY_FOREIGN, ['tenantid'], 'tool_tenant', ['id']);

        // Launch drop key tenantid.
        $dbman->drop_key($table, $key);

        // Tenantid can not be null.
        $DB->execute('UPDATE {tool_certificate_templates} SET tenantid = 0 WHERE tenantid IS NULL');

        // Changing nullability of field tenantid on table tool_certificate_templates to not null.
        $table = new xmldb_table('tool_certificate_templates');
        $field = new xmldb_field('tenantid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'timemodified');

        // Launch change of nullability for field tenantid.
        $dbman->change_field_notnull($table, $field);

        // Certificate savepoint reached.
        upgrade_plugin_savepoint(true, 2019020300, 'tool', 'certificate');
    }

    return true;
}
