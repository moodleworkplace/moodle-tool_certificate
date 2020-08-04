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
 * This file contains the certificate element date's core interaction API.
 *
 * @package    certificateelement_date
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace certificateelement_date;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/grade/constants.php');

/**
 * The certificate element date's core interaction API.
 *
 * @package    certificateelement_date
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element extends \tool_certificate\element {

    /**
     * @var int Show creation date
     */
    const CUSTOMCERT_DATE_ISSUE = -1;

    /**
     * @var int Show expiry date.
     */
    const CUSTOMCERT_DATE_EXPIRY = -2;

    /**
     * This function renders the form elements when adding a certificate element.
     *
     * @param \MoodleQuickForm $mform the edit_form instance
     */
    public function render_form_elements($mform) {

        // Get the possible date options.
        $dateoptions = [];
        $dateoptions[self::CUSTOMCERT_DATE_ISSUE] = get_string('issueddate', 'certificateelement_date');
        $dateoptions[self::CUSTOMCERT_DATE_EXPIRY] = get_string('expirydate', 'certificateelement_date');

        $mform->addElement('select', 'dateitem', get_string('dateitem', 'certificateelement_date'), $dateoptions);
        $mform->addHelpButton('dateitem', 'dateitem', 'certificateelement_date');

        $mform->addElement('select', 'dateformat', get_string('dateformat', 'certificateelement_date'), self::get_date_formats());
        $mform->addHelpButton('dateformat', 'dateformat', 'certificateelement_date');

        parent::render_form_elements($mform);
    }

    /**
     * Handles saving the form elements created by this element.
     * Can be overridden if more functionality is needed.
     *
     * @param \stdClass $data the form data or partial data to be updated (i.e. name, posx, etc.)
     */
    public function save_form_data(\stdClass $data) {
        $data->data = json_encode(['dateitem' => $data->dateitem, 'dateformat' => $data->dateformat]);
        parent::save_form_data($data);
    }

    /**
     * Handles rendering the element on the pdf.
     *
     * @param \pdf $pdf the pdf object
     * @param bool $preview true if it is a preview, false otherwise
     * @param \stdClass $user the user we are rendering this for
     * @param \stdClass $issue the issue we are rendering
     */
    public function render($pdf, $preview, $user, $issue) {
        // Decode the information stored in the database.
        $dateinfo = @json_decode($this->get_data(), true) + ['dateitem' => '', 'dateformat' => ''];

        // If we are previewing this certificate then just show a demonstration date.
        if ($preview) {
            $date = time();
        } else if ($dateinfo['dateitem'] == self::CUSTOMCERT_DATE_EXPIRY) {
            $date = $issue->expires;
        } else {
            $date = $issue->timecreated;
        }

        // Ensure that a date has been set.
        if (!empty($date)) {
            \tool_certificate\element_helper::render_content($pdf, $this,
                $this->get_date_format_string($date, $dateinfo['dateformat']));
        }
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
        // Decode the information stored in the database.
        $dateinfo = @json_decode($this->get_data(), true) + ['dateformat' => ''];
        return \tool_certificate\element_helper::render_html_content($this,
            $this->get_date_format_string(time(), $dateinfo['dateformat']));
    }

    /**
     * Prepare data to pass to moodleform::set_data()
     *
     * @return \stdClass|array
     */
    public function prepare_data_for_form() {
        $record = parent::prepare_data_for_form();
        if (!empty($this->get_data())) {
            $dateinfo = json_decode($this->get_data());
            $record->dateitem = $dateinfo->dateitem;
            $record->dateformat = $dateinfo->dateformat;
        }
        return $record;
    }

    /**
     * Helper function to return all the date formats.
     *
     * @return array the list of date formats
     */
    public static function get_date_formats() {
        // Hard-code date so users can see the difference between short dates with and without the leading zero.
        // Eg. 06/07/18 vs 6/07/18.
        $date = 1530849658;

        $dateformats = [];

        $strdateformats = [
            'strftimedate',
            'strftimedatefullshort',
            'strftimedatefullshortwleadingzero',
            'strftimedateshort',
            'strftimedaydate',
            'strftimedayshort',
            'strftimemonthyear',
        ];

        foreach ($strdateformats as $strdateformat) {
            $dateformats[$strdateformat] = self::get_date_format_string($date, $strdateformat);
        }

        return $dateformats;
    }

    /**
     * Returns the date in a readable format.
     *
     * @param int $date
     * @param string $dateformat
     * @return string
     */
    protected static function get_date_format_string($date, $dateformat) {
        if ($dateformat == 'strftimedatefullshortwleadingzero') {
            $certificatedate = userdate($date, get_string('strftimedatefullshort', 'langconfig'), 99, false);
        } else if (get_string_manager()->string_exists($dateformat, 'langconfig')) {
            $certificatedate = userdate($date, get_string($dateformat, 'langconfig'));
        } else {
            $certificatedate = userdate($date, get_string('strftimedate', 'langconfig'));
        }
        return $certificatedate;
    }
}
