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

    return true;
}
