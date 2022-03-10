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
 * Creates a link to the upload form on the settings page.
 *
 * @package    tool_certificate
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/certificate/adminlib.php');

if ($hassiteconfig || \tool_certificate\permission::can_view_admin_tree()) {

    $ADMIN->add('root', new admin_category('certificates', new lang_string('certificates', 'tool_certificate')),
        'location');

    $ADMIN->add('certificates', new \tool_certificate\admin_externalpage('tool_certificate/managetemplates',
                get_string('managetemplates', 'tool_certificate'),
                new moodle_url('/admin/tool/certificate/manage_templates.php'), function() {
                    return \tool_certificate\permission::can_view_admin_tree();
                }
        ));

    $ADMIN->add('certificates', new \tool_certificate\admin_externalpage('tool_certificate/verify',
                get_string('verifycertificates', 'tool_certificate'),
                new moodle_url('/admin/tool/certificate/index.php'), function() {
                    return \tool_certificate\permission::can_verify();
                }
        ));

    $ADMIN->add('certificates', new \tool_certificate\admin_externalpage('tool_certificate/images',
                get_string('certificateimages', 'tool_certificate'),
                new moodle_url('/admin/tool/certificate/upload_image.php'), function() {
                    return \tool_certificate\permission::can_manage_images();
                }
            ));
}

if ($hassiteconfig) {
    $ADMIN->add('tools', new tool_certificate_admin_page_manage_element_plugins());
    $ADMIN->add('certificates',
        new admin_externalpage('tool_certificate_customfield',
            new lang_string('certificate_customfield', 'tool_certificate'),
            new moodle_url('/admin/tool/certificate/customfield.php'),
            'moodle/site:config',
            true // This item is hidden.
        )
    );

    // Certificates settings.
    $settings = new admin_settingpage('tool_certificate', get_string('certificatesettings', 'tool_certificate'));

    $settings->add(new admin_setting_configcheckbox('tool_certificate/issuelang',
        new lang_string('issuelang', 'tool_certificate'),
        new lang_string('issuelangdesc', 'tool_certificate'),
        false
    ));
    $settings->add(new admin_setting_configcheckbox('tool_certificate/show_shareonlinkedin',
        new lang_string('show_shareonlinkedin', 'tool_certificate'),
        new lang_string('show_shareonlinkedin_desc', 'tool_certificate'),
        false
    ));
    $settings->add(new admin_setting_configtext('tool_certificate/linkedinorganizationid',
        new lang_string('linkedinorganizationid', 'tool_certificate'),
        new lang_string('linkedinorganizationid_desc', 'tool_certificate'),
        ''
    ));
    $settings->hide_if('tool_certificate/linkedinorganizationid', 'tool_certificate/show_shareonlinkedin');

    $ADMIN->add('certificates', $settings);
}
