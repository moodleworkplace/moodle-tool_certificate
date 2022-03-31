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
 * Contains the class responsible for step definitions related to tool_certificate.
 *
 * @package   tool_certificate
 * @category  test
 * @copyright 2017 Mark Nelson <markn@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode;
use tool_certificate\my_certificates_table;

/**
 * The class responsible for step definitions related to tool_certificate.
 *
 * @package tool_certificate
 * @category test
 * @copyright 2017 Mark Nelson <markn@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_certificate extends behat_base {

    /**
     * Adds an element to the specified page of a template.
     *
     * phpcs:ignore
     * @Given /^I add the element "(?P<element_name>(?:[^"]|\\")*)" to page "(?P<page_number>\d+)" of the "(?P<template_name>(?:[^"]|\\")*)" site certificate template$/
     *
     * @param string $elementname
     * @param int $pagenum
     * @param string $templatename
     */
    public function i_add_the_element_to_the_site_certificate_template_page($elementname, $pagenum, $templatename) {
        if (!$this->running_javascript()) {
            throw new coding_exception('You can only add element using the selenium driver.');
        }

        // Click on "Add field" button.
        $this->execute('behat_general::i_click_on_in_the',
            array(get_string('addelement', 'tool_certificate'), "button",
                "//*[@data-region='page'][{$pagenum}]", "xpath_element"));

        // Wait until the respective element type selector has class .show .
        $xpath = "//*[@data-region='page'][{$pagenum}]".
            "//*[@data-region='elementtypeslist' and contains(concat(' ', normalize-space(@class), ' '), ' show ')]";
        $this->execute("behat_general::wait_until_exists", array($this->escape($xpath), "xpath_element"));
        // Wait for CSS transition to finish.
        $this->getSession()->wait(200);

        // Click on the link in the element type selector.
        $this->execute('behat_general::i_click_on_in_the',
            array($elementname, "link", $xpath, "xpath_element"));
    }

    /**
     * Verifies the certificate code for a user.
     *
     * @Given /^I verify the "(?P<certificate_name>(?:[^"]|\\")*)" site certificate for the user "(?P<user_name>(?:[^"]|\\")*)"$/
     * @param string $templatename
     * @param string $username
     */
    public function i_verify_the_site_certificate_for_user($templatename, $username) {
        global $DB;

        $template = $DB->get_record('tool_certificate_templates', array('name' => $templatename), '*', MUST_EXIST);
        $user = $DB->get_record('user', array('username' => $username), '*', MUST_EXIST);
        $issue = $DB->get_record('tool_certificate_issues', array('userid' => $user->id, 'templateid' => $template->id),
            '*', MUST_EXIST);

        $this->execute('behat_forms::i_set_the_field_to', array(get_string('code', 'tool_certificate'), $issue->code));
        $this->execute('behat_forms::press_button', get_string('verify', 'tool_certificate'));
        $this->execute('behat_general::assert_page_contains_text', get_string('valid', 'tool_certificate'));
        $this->execute('behat_general::assert_page_not_contains_text', get_string('expired', 'tool_certificate'));
    }

    /**
     * Verifies the certificate with a code.
     *
     * @Given /^I verify the site certificate with code "(?P<code>(?:[^"]|\\")*)"$/
     * @param string $code
     */
    public function i_verify_the_site_certificate_with_code($code) {
        global $DB;

        $issue = $DB->get_record('tool_certificate_issues', ['code' => $code], '*', MUST_EXIST);

        $this->execute('behat_forms::i_set_the_field_to', [get_string('code', 'tool_certificate'), $issue->code]);
        $this->execute('behat_forms::press_button', get_string('verify', 'tool_certificate'));
        $this->execute('behat_general::assert_page_contains_text', get_string('valid', 'tool_certificate'));
        $this->execute('behat_general::assert_page_not_contains_text', get_string('expired', 'tool_certificate'));
    }

    /**
     * Verifies the certificate code for a user.
     *
     * phpcs:ignore
     * @Given /^I can not verify the "(?P<certificate_name>(?:[^"]|\\")*)" site certificate for the user "(?P<user_name>(?:[^"]|\\")*)"$/
     *
     * @param string $templatename
     * @param string $username
     */
    public function i_can_not_verify_the_site_certificate_for_user($templatename, $username) {
        global $DB;

        $template = $DB->get_record('tool_certificate_templates', array('name' => $templatename), '*', MUST_EXIST);
        $user = $DB->get_record('user', array('username' => $username), '*', MUST_EXIST);
        $issue = $DB->get_record('tool_certificate_issues', array('userid' => $user->id, 'templateid' => $template->id),
            '*', MUST_EXIST);

        $this->execute('behat_forms::i_set_the_field_to', array(get_string('code', 'tool_certificate'), $issue->code));
        $this->execute('behat_forms::press_button', get_string('verify', 'tool_certificate'));
        $this->execute('behat_general::assert_page_contains_text', get_string('notverified', 'tool_certificate'));
        $this->execute('behat_general::assert_page_not_contains_text', get_string('verified', 'tool_certificate'));
    }

    /**
     * Directs the user to the URL for verifying all certificates on the site.
     *
     * @Given /^I visit the sites certificates verification url/
     */
    public function i_visit_the_sites_certificates_verification_url() {
        $url = new moodle_url('/admin/tool/certificate/index.php');
        $this->getSession()->visit($this->locate_path($url->out_as_local_url()));
    }

    /**
     * Looks up category id
     *
     * @param array $elementdata
     */
    protected function lookup_category(array &$elementdata) {
        global $DB;
        if (array_key_exists('category', $elementdata)) {
            if (!empty($elementdata['category'])) {
                // Lookup category id by category name.
                $categoryid = $DB->get_field('course_categories', 'id',
                    ['name' => $elementdata['category']], MUST_EXIST);
                $elementdata['contextid'] = context_coursecat::instance($categoryid)->id;
            }
            unset($elementdata['category']);
        }
    }

    /**
     * Generates a template with a given name
     *
     * @Given /^the following certificate templates exist:$/
     *
     * Supported table fields:
     *
     * - Name: Template name (required).
     *
     * @param TableNode $data
     */
    public function the_following_certificate_templates_exist(TableNode $data) {
        foreach ($data->getHash() as $elementdata) {
            $this->lookup_category($elementdata);
            $elementdata['contextid'] = $elementdata['contextid'] ?? \context_system::instance()->id;
            $template = \tool_certificate\template::create((object)$elementdata);
            if (isset($elementdata['numberofpages']) && $elementdata['numberofpages'] > 0) {
                for ($p = 0; $p < $elementdata['numberofpages']; $p++) {
                    $template->new_page()->save((object)[]);
                }
            }
        }
    }

    /**
     * Issues certificate from a given template name and user shortname
     *
     * @Given /^the following certificate issues exist:$/
     *
     * Supported table fields:
     *
     * - Name: Template name (required).
     *
     * @param TableNode $data
     */
    public function the_following_certificate_issues_exist(TableNode $data) {
        global $DB;
        foreach ($data->getHash() as $elementdata) {
            if (!isset($elementdata['template']) || !isset($elementdata['user'])) {
                continue;
            }
            if ($template = \tool_certificate\template::find_by_name($elementdata['template'])) {
                if ($userid = $DB->get_field('user', 'id', ['username' => $elementdata['user']])) {
                    if (isset($elementdata['course'])) {
                        $courseid = $DB->get_field('course', 'id', ['shortname' => $elementdata['course']]);
                        $issueid = $template->issue_certificate($userid, null, [], $elementdata['component'] ?? '', $courseid);
                    } else {
                        $issueid = $template->issue_certificate($userid);
                    }
                    if (isset($elementdata['code'])) {
                        $DB->update_record('tool_certificate_issues', (object) ['id' => $issueid, 'code' => $elementdata['code']]);
                    }
                }
            }
        }
    }

    /**
     * Checks that a share on LinkedIn link exists on the page
     *
     * @Then /^I should see a share on LinkedIn link for "([^"]*)"$/
     *
     * @param string $certificatename
     */
    public function i_should_see_a_share_on_linkedin_link_for(string $certificatename) {
        $certificatename = str_replace(' ', '%20', $certificatename);
        $year = (new DateTime())->format('Y');
        $month = (new DateTime())->format('m');

        $url = my_certificates_table::LINKEDIN_ADD_TO_PROFILE_URL . "?name=$certificatename&issueYear=$year&issueMonth=$month";

        $this->find(
            'xpath',
            "//a[contains(@href, '$url')]"
        );
    }

    /**
     * Checks that a share on LinkedIn link does not exist on the page
     *
     * @Then /^I should not see a share on LinkedIn link for "([^"]*)"$/
     *
     * @param string $certificatename
     */
    public function i_should_not_see_a_share_on_linkedin_link_for(string $certificatename) {
        $certificatename = str_replace(' ', '%20', $certificatename);
        $year = (new DateTime())->format('Y');
        $month = (new DateTime())->format('m');

        $url = my_certificates_table::LINKEDIN_ADD_TO_PROFILE_URL . "?name=$certificatename&issueYear=$year&issueMonth=$month";

        $exception = null;
        try {
            $this->find(
                'xpath',
                "//a[contains(@href, '$url')]"
            );
        } catch (\Behat\Mink\Exception\ElementNotFoundException $e) {
            $exception = $e;
        }

        if ($exception === null) {
            throw new \Behat\Mink\Exception\ExpectationException('Share on LinkedIn link was found', $this->getSession());
        }
    }
}
