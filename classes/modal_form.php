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
 * Class modal_form
 *
 * @package     tool_certificate
 * @copyright   2018 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Class modal_form
 *
 * Extend this class to create a form that can be used in a modal dialogue.
 *
 * @package     tool_certificate
 * @copyright   2018 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class modal_form extends \moodleform {

    /**
     * Constructor for modal forms can not be overridden, however the same form can be used both in AJAX and normally
     *
     * @param mixed $action
     * @param mixed $customdata
     * @param string $method
     * @param string $target
     * @param array $attributes
     * @param bool $editable
     * @param array $ajaxformdata Forms submitted via ajax, must pass their data here, instead of relying on _GET and _POST.
     * @param bool $isajaxsubmission whether the form is called from WS and it needs to validate user access and set up context
     */
    final public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = [],
                                      $editable = true, ?array $ajaxformdata = null, bool $isajaxsubmission = false) {
        global $PAGE, $CFG;
        $this->_ajaxformdata = $ajaxformdata;
        if ($isajaxsubmission) {
            require_once($CFG->libdir . '/externallib.php');
            // This form was created from the WS that needs to validate user access to it and set page context.
            // It has to be done before calling parent constructor because elements definitions may need to use
            // format_string functions and other methods that expect the page to be set up.
            \external_api::validate_context($this->get_form_context());
            $PAGE->set_url($this->get_page_url_for_modal());
            $this->require_access();
        }
        $attributes += ['data-random-ids' => 1];
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    /**
     * Check if current user has access to this form, otherwise throw exception
     *
     * Sometimes permission check may depend on the action and/or id of the entity.
     * If necessary, form data is available in $this->_ajaxformdata or
     * by calling $this->optional_param()
     */
    abstract public function require_access();

    /**
     * Process the form submission
     *
     * This method can return scalar values or arrays that can be json-encoded, they will be passed to the caller JS.
     *
     * @param \stdClass $data
     * @return mixed
     */
    abstract public function process(\stdClass $data);

    /**
     * Load in existing data as form defaults
     *
     * Can be overridden to retrieve existing values from db by entity id and also
     * to preprocess editor and filemanager elements
     */
    public function set_data_for_modal() {
        $this->set_data($this->_ajaxformdata);
    }

    /**
     * Form context to use for validation
     * @return \context
     */
    public function get_form_context(): \context {
        return \context_system::instance();
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     * If the form has elements sensitive to the page url this method must be overridden
     *
     * Note: autosave function in Atto 'editor' elements is sensitive to page url
     *
     * @return \moodle_url
     */
    protected function get_page_url_for_modal(): \moodle_url {
        // TODO add debugging message here about the need to override this method.

        // In the most cases modal forms add/edit some elements and use parameter 'action' for the action (add/edit)
        // and 'id' for the element id in case of editing.

        // Some forms may also add 'parentid'/'categoryd' (or similar) to the 'add' actions.

        // This method must be overridden in each form.

        $action = $this->optional_param('action', null, PARAM_ALPHANUMEXT);
        $id = $this->optional_param('id', null, PARAM_INT);
        return new \moodle_url('/modalform.php',
            ['form' => get_class($this), 'action' => $action, 'id' => $id]);
    }

    /**
     * Returns an element of multi-dimensional array given the list of keys
     *
     * Example:
     * $array['a']['b']['c'] = 13;
     * $v = $this->get_array_value_by_keys($array, ['a', 'b', 'c']);
     *
     * Will result it $v==13
     *
     * @param array $array
     * @param array $keys
     * @return mixed returns null if keys not present
     */
    protected function get_array_value_by_keys(array $array, array $keys) {
        $value = $array;
        foreach ($keys as $key) {
            if (array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return null;
            }
        }
        return $value;
    }

    /**
     * Checks if a parameter was passed in the previous form submission
     *
     * @param string $name the name of the page parameter we want
     * @param mixed  $default the default value to return if nothing is found
     * @param string $type expected type of parameter
     * @return mixed
     */
    public function optional_param($name, $default, $type) {
        $nameparsed = [];
        // Convert element name into a sequence of keys, for example 'element[sub][13]' -> ['element', 'sub', '13'].
        parse_str($name . '=1', $nameparsed);
        $keys = [];
        while (is_array($nameparsed)) {
            $key = key($nameparsed);
            $keys[] = $key;
            $nameparsed = $nameparsed[$key];
        }

        // Search for the element first in $this->_ajaxformdata, then in $_POST and then in $_GET.
        if (($value = $this->get_array_value_by_keys($this->_ajaxformdata ?? [], $keys)) !== null ||
            ($value = $this->get_array_value_by_keys($_POST, $keys)) !== null ||
            ($value = $this->get_array_value_by_keys($_GET, $keys)) !== null) {
            return $type == PARAM_RAW ? $value : clean_param($value, $type);
        }

        return $default;
    }
}
