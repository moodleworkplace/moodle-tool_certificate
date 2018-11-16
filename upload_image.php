<?php
// This file is part of the tool_certificate for Moodle - http://moodle.org/
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
 * Handles uploading files
 *
 * @package    tool_certificate
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('tool_certificate/managetemplates');

require_capability('tool/certificate:imageforalltenants');

$struploadimage = get_string('uploadimage', 'tool_certificate');

// Additional page setup.
$PAGE->navbar->add($struploadimage);

$uploadform = new \tool_certificate\upload_image_form();

if ($uploadform->is_cancelled()) {
    redirect(new moodle_url('/admin/settings.php?section=toolcertificatemanagetemplates'));
} else if ($data = $uploadform->get_data()) {
    // Handle file uploads.
    \tool_certificate\certificate::upload_files($data->certificateimage);

    redirect(new moodle_url('/admin/tool/certificate/upload_image.php'), get_string('changessaved'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('uploadimage', 'tool_certificate'));
$uploadform->display();
echo $OUTPUT->footer();
