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
    global $DB, $CFG;
    require_once($CFG->dirroot.'/admin/tool/certificate/db/upgradelib.php');

    $dbman = $DB->get_manager();

    if ($oldversion < 2019030706) {

        // Changing type of field element on table tool_certificate_elements to char.
        $table = new xmldb_table('tool_certificate_elements');
        $field = new xmldb_field('element', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, 'name');

        // Launch change of type for field element.
        $dbman->change_field_type($table, $field);

        // Certificate savepoint reached.
        upgrade_plugin_savepoint(true, 2019030706, 'tool', 'certificate');
    }

    if ($oldversion < 2019030707) {
        // Change instances of bgimage to image.
        $elements = $DB->get_records('tool_certificate_elements', ['element' => 'bgimage']);
        foreach ($elements as $element) {
            $data = @json_decode($element->data, true);
            $data['isbackground'] = 1;
            $DB->update_record('tool_certificate_elements',
                ['id' => $element->id, 'element' => 'image', 'data' => json_encode($data)]);
        }

        upgrade_plugin_savepoint(true, 2019030707, 'tool', 'certificate');
    }

    if ($oldversion < 2019030708) {
        // Change instances of studentname to userfield.
        $DB->execute("UPDATE {tool_certificate_elements} SET element = ?, data = ? WHERE element = ?",
            ['userfield', 'fullname', 'studentname']);

        upgrade_plugin_savepoint(true, 2019030708, 'tool', 'certificate');
    }

    if ($oldversion < 2019030710) {
        // Change refpoint of all images.
        $DB->execute("UPDATE {tool_certificate_elements} SET refpoint = null WHERE element IN (?, ?, ?)",
            ['image', 'userpicture', 'digitalsignature']);

        upgrade_plugin_savepoint(true, 2019030710, 'tool', 'certificate');
    }

    if ($oldversion < 2019030711) {
        // Change refpoint of all images.
        $DB->execute("DELETE FROM {config_plugins} WHERE name = ? AND plugin IN (?, ?)",
            ['version', 'certificateelement_bgimage', 'certificateelement_studentname']);

        upgrade_plugin_savepoint(true, 2019030711, 'tool', 'certificate');
    }

    if ($oldversion < 2019111501) {

        // Define field tenantid to be dropped from tool_certificate_templates.
        $table = new xmldb_table('tool_certificate_templates');
        $field = new xmldb_field('tenantid');

        // Conditionally launch drop field tenantid.
        if ($dbman->field_exists($table, $field)) {
            // For templates that belonged to the tenants use the course category context instead.
            tool_certificate_upgrade_remove_tenant_field();

            $dbman->drop_field($table, $field);
        }

        // Certificate savepoint reached.
        upgrade_plugin_savepoint(true, 2019111501, 'tool', 'certificate');
    }

    if ($oldversion < 2019111502) {

        tool_certificate_upgrade_move_data_to_customfields();

        // Certificate savepoint reached.
        upgrade_plugin_savepoint(true, 2019111502, 'tool', 'certificate');
    }

    if ($oldversion < 2020070700) {

        tool_certificate_upgrade_store_fullname_in_data();

        // Certificate savepoint reached.
        upgrade_plugin_savepoint(true, 2020070700, 'tool', 'certificate');
    }

    if ($oldversion < 2020071600) {

        // Define field courseid to be added to tool_certificate_issues.
        $table = new xmldb_table('tool_certificate_issues');
        $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'component');
        $key = new xmldb_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

        // Conditionally launch add field courseid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Launch add key courseid.
        $dbman->add_key($table, $key);

        // Define field shared to be added to tool_certificate_templates.
        $table = new xmldb_table('tool_certificate_templates');
        $field = new xmldb_field('shared', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'contextid');

        // Conditionally launch add field visible.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Appointment savepoint reached.
        upgrade_plugin_savepoint(true, 2020071600, 'tool', 'certificate');
    }

    if ($oldversion < 2020081200) {
        tool_certificate_delete_certificates_with_missing_context();
        upgrade_plugin_savepoint(true, 2020081200, 'tool', 'certificate');
    }

    if ($oldversion < 2020081301) {
        tool_certificate_delete_orphaned_issue_files();
        upgrade_plugin_savepoint(true, 2020081301, 'tool', 'certificate');
    }

    if ($oldversion < 2020100900) {
        tool_certificate_fix_orphaned_template_element_files();
        upgrade_plugin_savepoint(true, 2020100900, 'tool', 'certificate');
    }

    return true;
}
