<?php
// This file is part of Moodle - https://moodle.org/
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
 * Creates a link to the upload form on the settings page.
 *
 * @package    tool_certificate
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/certificate/adminlib.php');

if ($hassiteconfig) {
    $ADMIN->add('courses', new admin_externalpage('tool_certificate/validate',
                new lang_string('verifycertificate', 'tool_certificate'),
                new moodle_url('/admin/tool/certificate/verify_certificate.php')));

    $ADMIN->add('courses', new admin_externalpage('tool_certificate/managetemplates',
                new lang_string('managetemplates', 'tool_certificate'),
                new moodle_url('/admin/tool/certificate/manage_templates.php')));

    $ADMIN->add('tools', new admin_category('tool_certificate', get_string('pluginname', 'tool_certificate')));

    $settings = new admin_settingpage('toolcertificatemanagetemplates', new lang_string('settings', 'tool_certificate'));
    $settings->add(new \tool_certificate\admin_setting_link('tool_certificate/uploadimage',
        get_string('uploadimage', 'tool_certificate'), get_string('uploadimagedesc', 'tool_certificate'),
        get_string('uploadimage', 'tool_certificate'), new moodle_url('/tool/certificate/upload_image.php'), ''));

    $settings->add(new admin_setting_configcheckbox('tool_certificate/verifyallcertificates', get_string('verifyallcertificates',
        'tool_certificate'), '', '0'));

    $ADMIN->add('tool_certificate', $settings);

    $ADMIN->add('tool_certificate', new tool_certificate_admin_page_manage_element_plugins());

    // Element plugin settings.
    $ADMIN->add('tool_certificate', new admin_category('certificateelements', get_string('elementplugins', 'tool_certificate')));
    $plugins = \core_plugin_manager::instance()->get_plugins_of_type('certificateelement');
    foreach ($plugins as $plugin) {
        $plugin->load_settings($ADMIN, 'certificateelements', $hassiteconfig);
    }
}
