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

    /**
     * Method to add a repeating group of elements to a form.
     *
     * This method is mostly duplicated from the parent class, the only added argument is the $deletebuttonname
     *
     * @param array $elementobjs Array of elements or groups of elements that are to be repeated
     * @param int $repeats no of times to repeat elements initially
     * @param array $options a nested array. The first array key is the element name.
     *    the second array key is the type of option to set, and depend on that option,
     *    the value takes different forms.
     *         'default'    - default value to set. Can include '{no}' which is replaced by the repeat number.
     *         'type'       - PARAM_* type.
     *         'helpbutton' - array containing the helpbutton params.
     *         'disabledif' - array containing the disabledIf() arguments after the element name.
     *         'rule'       - array containing the addRule arguments after the element name.
     *         'expanded'   - whether this section of the form should be expanded by default. (Name be a header element.)
     *         'advanced'   - whether this element is hidden by 'Show more ...'.
     * @param string $repeathiddenname name for hidden element storing no of repeats in this form
     * @param string $addfieldsname name for button to add more fields
     * @param int $addfieldsno how many fields to add at a time
     * @param string $addstring name of button, {no} is replaced by no of blanks that will be added.
     * @param bool $addbuttoninside if true, don't call closeHeaderBefore($addfieldsname). Default false.
     * @param string $deletebuttonname if specified, treats the no-submit button with this name as a "delete element" button
     *         in each of the elements
     * @return int no of repeats of element in this page
     */
    public function repeat_elements($elementobjs, $repeats, $options, $repeathiddenname,
                                    $addfieldsname, $addfieldsno = 5, $addstring = null, $addbuttoninside = false,
                                    $deletebuttonname = '') {
        if ($addstring === null) {
            $addstring = get_string('addfields', 'form', $addfieldsno);
        } else {
            $addstring = str_ireplace('{no}', $addfieldsno, $addstring);
        }
        $repeats = $this->optional_param($repeathiddenname, $repeats, PARAM_INT);
        $addfields = $this->optional_param($addfieldsname, '', PARAM_TEXT);
        if (!empty($addfields)) {
            $repeats += $addfieldsno;
        }
        $mform =& $this->_form;
        $mform->registerNoSubmitButton($addfieldsname);
        $mform->addElement('hidden', $repeathiddenname, $repeats);
        $mform->setType($repeathiddenname, PARAM_INT);
        // Value not to be overridden by submitted value.
        $mform->setConstants(array($repeathiddenname => $repeats));
        $namecloned = array();
        $no = 1;
        for ($i = 0; $i < $repeats; $i++) {
            if ($deletebuttonname) {
                $mform->registerNoSubmitButton($deletebuttonname . "[$i]");
                $isdeleted = $this->optional_param($deletebuttonname . "[$i]", false, PARAM_RAW) ||
                    $this->optional_param($deletebuttonname . "-hidden[$i]", false, PARAM_RAW);
                if ($isdeleted) {
                    $mform->addElement('hidden', $deletebuttonname . "-hidden[$i]", 1);
                    $mform->setType($deletebuttonname . "-hidden[$i]", PARAM_INT);
                    continue;
                }
            }
            foreach ($elementobjs as $elementobj) {
                $elementclone = fullclone($elementobj);
                $this->repeat_elements_fix_clone($i, $elementclone, $namecloned);

                if ($elementclone instanceof \HTML_QuickForm_group && !$elementclone->_appendName) {
                    foreach ($elementclone->getElements() as $el) {
                        $this->repeat_elements_fix_clone($i, $el, $namecloned);
                    }
                    $elementclone->setLabel(str_replace('{no}', $no, $elementclone->getLabel()));
                } else if ($elementobj instanceof \HTML_QuickForm_submit && $elementobj->getName() == $deletebuttonname) {
                    // Mark the "Delete" button as no-submit.
                    $onclick = $elementclone->getAttribute('onclick');
                    $skip = 'skipClientValidation = true;';
                    $onclick = ($onclick !== null) ? $skip . ' ' . $onclick : $skip;
                    $elementclone->updateAttributes(['data-skip-validation' => 1, 'data-no-submit' => 1, 'onclick' => $onclick]);
                }

                $mform->addElement($elementclone);
                $no++;
            }
        }
        for ($i = 0; $i < $repeats; $i++) {
            foreach ($options as $elementname => $elementoptions) {
                $pos = strpos($elementname, '[');
                if ($pos !== false) {
                    $realelementname = substr($elementname, 0, $pos) . "[$i]";
                    $realelementname .= substr($elementname, $pos);
                } else {
                    $realelementname = $elementname . "[$i]";
                }
                foreach ($elementoptions as $option => $params) {

                    switch ($option) {
                        case 'default' :
                            $mform->setDefault($realelementname, str_replace('{no}', $i + 1, $params));
                            break;
                        case 'helpbutton' :
                            $params = array_merge(array($realelementname), $params);
                            call_user_func_array(array(&$mform, 'addHelpButton'), $params);
                            break;
                        case 'disabledif' :
                        case 'hideif' :
                            $pos = strpos($params[0], '[');
                            $ending = '';
                            if ($pos !== false) {
                                $ending = substr($params[0], $pos);
                                $params[0] = substr($params[0], 0, $pos);
                            }
                            foreach ($namecloned as $num => $name) {
                                if ($params[0] == $name) {
                                    $params[0] = $params[0] . "[$i]" . $ending;
                                    break;
                                }
                            }
                            $params = array_merge(array($realelementname), $params);
                            $function = ($option === 'disabledif') ? 'disabledIf' : 'hideIf';
                            call_user_func_array(array(&$mform, $function), $params);
                            break;
                        case 'rule' :
                            if (is_string($params)) {
                                $params = array(null, $params, null, 'client');
                            }
                            $params = array_merge(array($realelementname), $params);
                            call_user_func_array(array(&$mform, 'addRule'), $params);
                            break;

                        case 'type':
                            $mform->setType($realelementname, $params);
                            break;

                        case 'expanded':
                            $mform->setExpanded($realelementname, $params);
                            break;

                        case 'advanced' :
                            $mform->setAdvanced($realelementname, $params);
                            break;
                    }
                }
            }
        }
        $mform->addElement('submit', $addfieldsname, $addstring, [], false);

        if (!$addbuttoninside) {
            $mform->closeHeaderBefore($addfieldsname);
        }

        return $repeats;
    }
}
