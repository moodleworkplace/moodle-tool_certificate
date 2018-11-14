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
 * Contains class used to prepare a verification result for display.
 *
 * @package   tool_certificate
 * @copyright 2017 Mark Nelson <markn@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use tool_certificate\template;

/**
 * Class to prepare a verification result for display.
 *
 * @package   tool_certificate
 * @copyright 2017 Mark Nelson <markn@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class verify_certificate_result implements templatable, renderable {

    /**
     * @var string The URL to the user's profile.
     */
    public $userprofileurl;

    /**
     * @var string The user's fullname.
     */
    public $userfullname;

    /**
     * @var string The certificate's name.
     */
    public $certificatename;

    /**
     * @var string The time the issue was created.
     */
    public $timecreated;

    /**
     * @var string The timestamp the issue expires on.
     */
    public $expires;


    /**
     * @var string If issue expired based on current time.
     */
    public $expired;

    /**
     * Constructor.
     *
     * @param \stdClass $result
     */
    public function __construct($issue) {
        $this->viewurl = template::view_url($issue->code);
        $this->userprofileurl = new \moodle_url('/user/view.php', array('id' => $issue->userid));
        $this->userfullname = fullname($issue);
        $this->certificatename = $issue->certificatename;
        $this->timecreated = userdate($issue->timecreated);
        $this->expires = userdate($issue->expires);
        $this->expired = $issue->expires <= time();
    }

    /**
     * Function to export the renderer data in a format that is suitable for a mustache template.
     *
     * @param \renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return \stdClass|array
     */
    public function export_for_template(\renderer_base $output) {
        $result = new \stdClass();
        $result->viewurl = $this->viewurl;
        $result->userprofileurl = $this->userprofileurl;
        $result->userfullname = $this->userfullname;
        $result->certificatename = $this->certificatename;
        $result->timecreated = $this->timecreated;
        $result->expires = $this->expires;
        $result->expired = $this->expired;

        return $result;
    }
}
