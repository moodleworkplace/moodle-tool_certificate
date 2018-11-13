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
 * This file contains the certificate element code's core interaction API.
 *
 * @package    certificateelement_code
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace certificateelement_code;

defined('MOODLE_INTERNAL') || die();

/**
 * The certificate element code's core interaction API.
 *
 * @package    certificateelement_code
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element extends \tool_certificate\element {

    /**
     * Handles rendering the element on the pdf.
     *
     * @param \pdf $pdf the pdf object
     * @param bool $preview true if it is a preview, false otherwise
     * @param \stdClass $user the user we are rendering this for
     */
    public function render($pdf, $preview, $user, $issue) {
        global $DB;

        if ($preview) {
            $code = \tool_certificate\certificate::generate_code();
        } else {
            // Get the page.
            $page = $DB->get_record('tool_certificate_pages', array('id' => $this->get_pageid()), '*', MUST_EXIST);
            // Get the certificate this page belongs to.
            $certificate = $DB->get_record('tool_certificate_templates', array('id' => $page->templateid), '*', MUST_EXIST);
            // Now we can get the issue for this user.
            $issue = $DB->get_record('tool_certificate_issues', array('userid' => $user->id, 'templateid' => $certificate->id),
                '*', MUST_EXIST);
            $code = $issue->code;
        }

        \tool_certificate\element_helper::render_content($pdf, $this, $code);
    }

    /**
     * Render the element in html.
     *
     * This function is used to render the element when we are using the
     * drag and drop interface to position it.
     *
     * @return string the html
     */
    public function render_html() {
        $code = \tool_certificate\certificate::generate_code();

        return \tool_certificate\element_helper::render_html_content($this, $code);
    }
}
