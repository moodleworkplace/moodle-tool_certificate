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
     * @codingStandardsIgnoreLine
     * @Given /^I add the element "(?P<element_name>(?:[^"]|\\")*)" to page "(?P<page_number>\d+)" of the "(?P<template_name>(?:[^"]|\\")*)" certificate template$/
     * @param string $elementname
     * @param int $pagenum
     * @param string $templatename
     */
    public function i_add_the_element_to_the_certificate_template_page($elementname, $pagenum, $templatename) {
        global $DB;

        $template = $DB->get_record('tool_certificate_templates', array('name' => $templatename), '*', MUST_EXIST);
        $page = $DB->get_record('tool_certificate_pages', array('templateid' => $template->id, 'sequence' => $pagenum),
            '*', MUST_EXIST);

        $this->execute('behat_forms::i_set_the_field_to', array($this->escape('element_' . $page->id),
            $this->escape($elementname)));
        $this->execute('behat_forms::press_button', get_string('addelement', 'tool_certificate'));
    }

    /**
     * Deletes an element from a specified page of a template.
     *
     * @Given /^I delete page "(?P<page_number>\d+)" of the "(?P<template_name>(?:[^"]|\\")*)" certificate template$/
     * @param int $pagenum
     * @param string $templatename
     */
    public function i_delete_the_certificate_page($pagenum, $templatename) {
        global $DB;

        $template = $DB->get_record('tool_certificate_templates', array('name' => $templatename), '*', MUST_EXIST);
        $page = $DB->get_record('tool_certificate_pages', array('templateid' => $template->id, 'sequence' => $pagenum),
            '*', MUST_EXIST);

        $this->execute('behat_general::i_click_on_in_the', array('Delete page', 'link',
            $this->escape('#id_page_' . $page->id), 'css_element'));
        $this->execute('behat_forms::press_button', get_string('continue'));
    }

    /**
     * Verifies the certificate code for a user.
     *
     * @Given /^I verify the "(?P<certificate_name>(?:[^"]|\\")*)" certificate for the user "(?P<user_name>(?:[^"]|\\")*)"$/
     * @param string $certificatename
     * @param string $username
     */
    public function i_verify_the_certificate_for_user($templatename, $username) {
        global $DB;

        $template = $DB->get_record('tool_certificate_templates', array('name' => $templatename), '*', MUST_EXIST);
        $user = $DB->get_record('user', array('username' => $username), '*', MUST_EXIST);
        $issue = $DB->get_record('tool_certificate_issues', array('userid' => $user->id, 'templateid' => $template->id),
            '*', MUST_EXIST);

        $this->execute('behat_forms::i_set_the_field_to', array(get_string('code', 'tool_certificate'), $issue->code));
        $this->execute('behat_forms::press_button', get_string('verify', 'tool_certificate'));
        $this->execute('behat_general::assert_page_contains_text', get_string('verified', 'tool_certificate'));
        $this->execute('behat_general::assert_page_not_contains_text', get_string('notverified', 'tool_certificate'));
    }

    /**
     * Verifies the certificate code for a user.
     *
     * @Given /^I can not verify the "(?P<certificate_name>(?:[^"]|\\")*)" certificate for the user "(?P<user_name>(?:[^"]|\\")*)"$/
     * @param string $certificatename
     * @param string $username
     */
    public function i_can_not_verify_the_certificate_for_user($templatename, $username) {
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
     * Directs the user to the URL for verifying a certificate.
     *
     * This has been created as we allow non-users to verify certificates and they can not navigate to
     * the page like a conventional user.
     *
     * @Given /^I visit the verification url for the "(?P<certificate_name>(?:[^"]|\\")*)" certificate$/
     * @param string $certificatename
     */
    public function i_visit_the_verification_url_for_certificate($templatename) {
        global $DB;

        $template = $DB->get_record('tool_certificate_templates', array('name' => $templatename), '*', MUST_EXIST);

        $url = new moodle_url('/admin/tool/certificate/index.php');
        $this->getSession()->visit($this->locate_path($url->out_as_local_url()));
    }

    /**
     * Directs the user to the URL for verifying all certificates on the site.
     *
     * @Given /^I visit the verification url for the site$/
     */
    public function i_visit_the_verification_url_for_the_site() {
        $url = new moodle_url('/admin/tool/certificate/index.php');
        $this->getSession()->visit($this->locate_path($url->out_as_local_url()));
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
            $template = \tool_certificate\template::create((object)$elementdata);
            if (isset($elementdata['numberofpages']) && $elementdata['numberofpages'] > 0) {
                for ($p = 0; $p < $elementdata['numberofpages']; $p++) {
                    $template->add_page();
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
        $contextid = \context_system::instance()->id;
        foreach ($data->getHash() as $elementdata) {
            if (!isset($elementdata['template']) || !isset($elementdata['user'])) {
                continue;
            }
            if ($template = \tool_certificate\template::find_by_name($elementdata['template'], $contextid)) {
                if ($userid = $DB->get_field('user', 'id', ['username' => $elementdata['user']])) {
                    \tool_certificate\certificate::issue_certificate($template->get_id(), $userid);
                }
            }
        }
    }
}
