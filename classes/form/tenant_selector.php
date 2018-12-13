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
 * Issue new certificate for users.
 *
 * @package    tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate\form;
defined('MOODLE_INTERNAL') || die();

use moodleform;

require_once($CFG->libdir . '/formslib.php');

/**
 * Select tenant when duplicating a template.
 *
 * @package    tool_certificate
 * @copyright  2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tenant_selector extends moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        $mform =& $this->_form;

        $tenants = \tool_tenant\tenancy::get_tenants();
        $options = [0 => get_string('shared', 'tool_certificate')];
        foreach ($tenants as $tenant) {
            $options[$tenant->id] = $tenant->name;
        }
        $mform->addElement('select', 'tenantid', get_string('selecttenant', 'tool_certificate'), $options);

        $group[] = $mform->addElement('hidden', 'action', 'duplicate');
        $mform->setType('action', PARAM_ALPHANUMEXT);

        $group = array();
        $group[] = $mform->createElement('submit', 'submitbtn', get_string('select'));
        $group[] = $mform->createElement('cancel', 'cancelbtn', get_string('cancel'));
        $mform->addElement('group', 'actiongroup', '', $group, '', false);
    }
}
