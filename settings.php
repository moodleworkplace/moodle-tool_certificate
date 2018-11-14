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

$managecaps = ['tool/certificate:manage', 'tool/certificate:manageforalltenants'];
$viewcaps = ['tool/certificate:viewallcertificates', 'tool/certificate:verifyallcertificates'];
$imagecaps = ['tool/certificate:imageforalltenants'];
$issuecaps = ['tool/certificate:issue'];
$anycaps = array_merge($managecaps, $viewcaps, $imagecaps, $issuecaps);

if ($hassiteconfig || has_any_capability($anycaps, context_system::instance())) {

    $ADMIN->add('root', new admin_category('certificates', new lang_string('certificates', 'tool_certificate')));

    $ADMIN->add('certificates', new admin_externalpage('tool_certificate/managetemplates',
                get_string('managetemplates', 'tool_certificate'),
                new moodle_url('/admin/tool/certificate/manage_templates.php'), $anycaps));

    $ADMIN->add('certificates', new admin_externalpage('tool_certificate/verify',
                get_string('verifycertificate', 'tool_certificate'),
                new moodle_url('/admin/tool/certificate/index.php'), array_merge($managecaps, $issuecaps)));

    $ADMIN->add('certificates', new admin_externalpage('tool_certificate/addcertificate',
                get_string('addcertificate', 'tool_certificate'),
                new moodle_url('/admin/tool/certificate/edit.php'), $managecaps));

    $ADMIN->add('certificates', new admin_externalpage('tool_certificate/images',
                get_string('certificateimages', 'tool_certificate'),
                new moodle_url('/admin/tool/certificate/upload_image.php'), array_merge($managecaps, $imagecaps)));

    if ($hassiteconfig) {
        $ADMIN->add('tools', new admin_category('tool_certificate', get_string('pluginname', 'tool_certificate')));

        $ADMIN->add('tool_certificate', new tool_certificate_admin_page_manage_element_plugins());

        // Element plugin settings.
        $elementpluginsstr = get_string('elementplugins', 'tool_certificate');
        $ADMIN->add('tool_certificate', new admin_category('certificateelements', $elementpluginsstr));
        $plugins = \core_plugin_manager::instance()->get_plugins_of_type('certificateelement');
        foreach ($plugins as $plugin) {
            $plugin->load_settings($ADMIN, 'certificateelements', $hassiteconfig);
        }
    }
}
