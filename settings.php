<?php
// This file is part of the Certificate module for Moodle - http://moodle.org/
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
/*
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/certificate/adminlib.php');

$url = $CFG->wwwroot . '/' . $CFG->admin . '/tool/certificate/verify_certificate.php';

$ADMIN->add('modsettings', new admin_category('tool_certificate', get_string('pluginname', 'tool_certificate')));
$settings = new admin_settingpage('modsettingcustomcert', new lang_string('customcertsettings', 'tool_certificate'));

$settings->add(new admin_setting_configcheckbox('customcert/verifyallcertificates',
    get_string('verifyallcertificates', 'tool_certificate'),
    get_string('verifyallcertificates_desc', 'tool_certificate', $url),
    0));

$settings->add(new admin_setting_configcheckbox('customcert/showposxy',
    get_string('showposxy', 'tool_certificate'),
    get_string('showposxy_desc', 'tool_certificate'),
    0));

$settings->add(new \tool_certificate\admin_setting_link('customcert/verifycertificate',
    get_string('verifycertificate', 'tool_certificate'), get_string('verifycertificatedesc', 'tool_certificate'),
    get_string('verifycertificate', 'tool_certificate'), new moodle_url('/admin/tool/certificate/verify_certificate.php'), ''));

$settings->add(new \tool_certificate\admin_setting_link('customcert/managetemplates',
    get_string('managetemplates', 'tool_certificate'), get_string('managetemplatesdesc', 'tool_certificate'),
    get_string('managetemplates', 'tool_certificate'), new moodle_url('/admin/tool/certificate/manage_templates.php'), ''));

$settings->add(new \tool_certificate\admin_setting_link('customcert/uploadimage',
    get_string('uploadimage', 'tool_certificate'), get_string('uploadimagedesc', 'tool_certificate'),
    get_string('uploadimage', 'tool_certificate'), new moodle_url('/mod/customcert/upload_image.php'), ''));

$settings->add(new admin_setting_heading('defaults',
    get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

$yesnooptions = [
    0 => get_string('no'),
    1 => get_string('yes'),
];

$settings->add(new admin_setting_configselect('customcert/emailstudents',
    get_string('emailstudents', 'tool_certificate'), get_string('emailstudents_help', 'tool_certificate'), 0, $yesnooptions));
$settings->add(new admin_setting_configselect('customcert/emailteachers',
    get_string('emailteachers', 'tool_certificate'), get_string('emailteachers_help', 'tool_certificate'), 0, $yesnooptions));
$settings->add(new admin_setting_configtext('customcert/emailothers',
    get_string('emailothers', 'tool_certificate'), get_string('emailothers_help', 'tool_certificate'), '', PARAM_TEXT));
$settings->add(new admin_setting_configselect('customcert/verifyany',
    get_string('verifycertificateanyone', 'tool_certificate'), get_string('verifycertificateanyone_help', 'tool_certificate'),
    0, $yesnooptions));
$settings->add(new admin_setting_configtext('customcert/requiredtime',
    get_string('coursetimereq', 'tool_certificate'), get_string('coursetimereq_help', 'tool_certificate'), 0, PARAM_INT));
$settings->add(new admin_setting_configcheckbox('customcert/protection_print',
    get_string('preventprint', 'tool_certificate'),
    get_string('preventprint_desc', 'tool_certificate'),
    0));
$settings->add(new admin_setting_configcheckbox('customcert/protection_modify',
    get_string('preventmodify', 'tool_certificate'),
    get_string('preventmodify_desc', 'tool_certificate'),
    0));
$settings->add(new admin_setting_configcheckbox('customcert/protection_copy',
    get_string('preventcopy', 'tool_certificate'),
    get_string('preventcopy_desc', 'tool_certificate'),
    0));

$ADMIN->add('tool_certificate', $settings);

$ADMIN->add('tool_certificate', new tool_certificate_admin_page_manage_element_plugins());

// Element plugin settings.
$ADMIN->add('tool_certificate', new admin_category('certificateelements', get_string('elementplugins', 'tool_certificate')));
$plugins = \core_plugin_manager::instance()->get_plugins_of_type('certificateelement');
foreach ($plugins as $plugin) {
    $plugin->load_settings($ADMIN, 'certificateelements', $hassiteconfig);
}
*/
// Tell core we already added the settings structure.
$settings = null;
