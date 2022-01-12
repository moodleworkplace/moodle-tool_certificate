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
 * Class issue_handler
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate\customfield;

use core_customfield\field_controller;
use core_customfield\handler;
use tool_certification\certification;

/**
 * Class issue_handler
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issue_handler extends handler {

    /**
     * @var issue_handler
     */
    static protected $singleton;

    /**
     * Returns a singleton
     *
     * @param int $itemid
     * @return issue_handler
     */
    public static function create(int $itemid = 0) : \core_customfield\handler {
        if (static::$singleton === null) {
            self::$singleton = new static(0);
        }
        return self::$singleton;
    }

    /**
     * Context that should be used for new categories created by this handler
     *
     * @return \context
     */
    public function get_configuration_context() : \context {
        return \context_system::instance();
    }

    /**
     * URL for configuration of the fields on this handler.
     *
     * @return \moodle_url
     */
    public function get_configuration_url() : \moodle_url {
        return new \moodle_url('/admin/tool/certificate/customfield.php');
    }

    /**
     * Context that should be used for data stored for the given record
     *
     * @param int $instanceid id of the instance or 0 if the instance is being created
     * @return \context
     */
    public function get_instance_context(int $instanceid = 0) : \context {
        global $DB;
        if ($instanceid > 0) {
            // If issue has courseid then return course context.
            if ($courseid = $DB->get_field('tool_certificate_issues', 'courseid', ['id' => $instanceid], IGNORE_MISSING)) {
                return \context_course::instance($courseid);
            }
            // Return the issue template context.
            $sql = 'SELECT ct.contextid
                    FROM {tool_certificate_templates} ct
                    JOIN {tool_certificate_issues} ci
                    ON ct.id = ci.templateid
                    WHERE ci.id = :instanceid';
            if ($templatecontext = $DB->get_field_sql($sql, ['instanceid' => $instanceid], IGNORE_MISSING)) {
                return \context::instance_by_id($templatecontext);
            }
        }
        return \context_system::instance();
    }

    /**
     * The current user can configure custom fields on this component.
     *
     * @return bool
     */
    public function can_configure() : bool {
        return has_capability('moodle/site:config', \context_system::instance());
    }

    /**
     * The current user can edit given custom fields on the given instance
     *
     * Called to filter list of fields displayed on the instance edit form
     *
     * Capability to edit/create instance is checked separately
     *
     * @param field_controller $field
     * @param int $instanceid id of the instance or 0 if the instance is being created
     * @return bool
     */
    public function can_edit(field_controller $field, int $instanceid = 0) : bool {
        // Always return true, to make sure we can call instance_form_save() from any user.
        return true;
    }

    /**
     * The current user can view the value of the custom field for a given custom field and instance
     *
     * Called to filter list of fields returned by methods get_instance_data(), get_instances_data(),
     * export_instance_data(), export_instance_data_object()
     *
     * Access to the instance itself is checked by handler before calling these methods
     *
     * @param field_controller $field
     * @param int $instanceid
     * @return bool
     */
    public function can_view(field_controller $field, int $instanceid) : bool {
        return (bool)$field->get_configdata_property('visible');
    }

    /**
     * Allows to add custom controls to the field configuration form that will be saved in configdata
     *
     * @param \MoodleQuickForm $mform
     */
    public function config_form_definition(\MoodleQuickForm $mform) {
        $mform->addElement('header', 'issue_handler_header', get_string('customfieldsettings', 'tool_certificate'));
        $mform->setExpanded('issue_handler_header', true);

        // If field is visible.
        $mform->addElement('selectyesno', 'configdata[visible]', get_string('customfield_visible', 'tool_certificate'));
        $mform->addHelpButton('configdata[visible]', 'customfield_visible', 'tool_certificate');

        // Preview value.
        $mform->addElement('text', 'configdata[previewvalue]',
            get_string('customfield_previewvalue', 'tool_certificate'), ['size' => 50]);
        $mform->setType('configdata[previewvalue]', PARAM_TEXT);
        $mform->addHelpButton('configdata[previewvalue]', 'customfield_previewvalue', 'tool_certificate');
    }

    /**
     * Set up page customfield/edit.php
     *
     * @param field_controller $field
     * @return string page heading
     */
    public function setup_edit_page(field_controller $field) : string {
        global $CFG, $PAGE;
        require_once($CFG->libdir.'/adminlib.php');

        $title = parent::setup_edit_page($field);
        admin_externalpage_setup('tool_certificate_customfield');
        $PAGE->navbar->add($title);
        return $title;
    }

    /**
     * Finds a field by its shortname
     *
     * @param string $shortname
     * @return field_controller|null
     */
    public function find_field_by_shortname(string $shortname) : ?field_controller {
        $categories = self::create()->get_categories_with_fields();
        foreach ($categories as $category) {
            foreach ($category->get_fields() as $field) {
                if ($field->get('shortname') === $shortname) {
                    return $field;
                }
            }
        }
        return null;
    }

    /**
     * Create a field if it does not exist
     *
     * @param string $shortname
     * @param string $type currently only supported 'text' and 'textarea'
     * @param null|string $displayname
     * @param bool $visible
     * @param null|string $previewvalue
     * @param array $config additional field configuration, for example, for date - includetime
     * @return field_controller|null
     */
    public function ensure_field_exists(string $shortname, string $type = 'text', string $displayname = '',
            bool $visible = false, ?string $previewvalue = null, array $config = []) : ?field_controller {
        if ($field = $this->find_field_by_shortname($shortname)) {
            return $field;
        }

        $categories = $this->get_categories_with_fields();
        if (empty($categories)) {
            $categoryid = $this->create_category();
            $category = \core_customfield\category_controller::create($categoryid);
        } else {
            $category = reset($categories);
        }

        if ($type !== 'textarea') {
            $type = 'text';
        }

        try {
            $config = ['visible' => $visible, 'previewvalue' => $previewvalue] + $config;
            $record = (object)['type' => $type, 'shortname' => $shortname, 'name' => $displayname ?: $shortname,
                'descriptionformat' => FORMAT_HTML, 'configdata' => json_encode($config)];
            $field = \core_customfield\field_controller::create(0, $record, $category);
        } catch (\moodle_exception $e) {
            return null;
        }

        $this->save_field_configuration($field, $record);

        return $this->find_field_by_shortname($shortname);
    }

    /**
     * Saves additional data to the object
     *
     * @param \stdClass $issue
     * @param array $data
     */
    public function save_additional_data($issue, array $data) {
        if (empty($data)) {
            return;
        }
        $this->create_custom_fields_if_not_exist();
        $issue = (object)['id' => $issue->id];
        foreach ($data as $key => $value) {
            if ($field = $this->find_field_by_shortname($key)) {
                if (get_class($field) === 'customfield_textarea\field_controller') {
                    $issue->{'customfield_' . $key . '_editor'} = ['text' => '' . $value, 'format' => FORMAT_HTML];
                } else {
                    $issue->{'customfield_' . $key} = '' . $value;
                }
            }
        }

        // Instead of failing hard if we can't save additional data, emit some debugging.
        try {
            $this->instance_form_save($issue, true);
        } catch (\moodle_exception $ex) {
            debugging($ex->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Initialises the custom fields for the course data.
     */
    public function create_custom_fields_if_not_exist() {
        // Allow all plugins to register their fields.
        $callbacks = get_plugins_with_function('tool_certificate_fields');
        foreach ($callbacks as $plugintype => $plugins) {
            foreach ($plugins as $plugin => $callback) {
                try {
                    $callback();
                } catch (\moodle_exception $e) {
                    continue;
                }
            }
        }
    }

    /**
     * Helper function to get a list of fields shortnames
     *
     * @return array
     */
    public function get_all_fields_shortnames() {
        $this->create_custom_fields_if_not_exist();
        $fieldkeys = [];
        foreach ($this->get_fields() as $field) {
            $fieldkeys[] = $field->get('shortname');
        }
        return $fieldkeys;
    }

    /**
     * For use in unittests
     */
    public static function reset_caches() {
        self::$singleton = null;
    }
}
