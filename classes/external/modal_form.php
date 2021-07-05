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
 * This is the external API for this tool.
 *
 * @package    tool_certificate
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_certificate\external;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use external_function_parameters;
use external_single_structure;
use external_value;

/**
 * This is the external API for this tool.
 *
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class modal_form extends \external_api {
    /**
     * Parameters for modal_form
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'form' => new external_value(PARAM_RAW_TRIMMED, 'Form class', VALUE_REQUIRED),
            'formdata' => new external_value(PARAM_RAW, 'url-encoded form data', VALUE_REQUIRED),
        ]);
    }

    /**
     * Submit a form from a modal dialogue.
     *
     * @param string $formclass
     * @param string $formdatastr
     * @return array
     */
    public static function execute(string $formclass, string $formdatastr): array {
        global $PAGE, $OUTPUT;
        $params = self::validate_parameters(self::execute_parameters(), [
            'form' => $formclass,
            'formdata' => $formdatastr,
        ]);
        $formclass = $params['form'];
        parse_str($params['formdata'], $formdata);

        if (!class_exists($formclass) || !is_subclass_of($formclass, \tool_certificate\modal_form::class)) {
            // For security reason we don't throw exception "class does not exist" but rather an access exception.
            throw new \moodle_exception('nopermissionform', 'tool_certificate');
        }

        /** @var \tool_certificate\modal_form $form */
        $form = new $formclass(null, null, 'post', '', [], true, $formdata, true);
        $form->set_data_for_modal();
        if (!$form->is_cancelled() && $form->is_submitted() && $form->is_validated()) {
            // Form was properly submitted, process and return results of processing.
            // Note, when form is submitted we do not return the form html because it will not be correct,
            // for example, if the element was created as a result of form submission the "id" in the form will be still zero.
            // If the caller needs to re-render the form after submission it has to send a new request.
            return ['submitted' => true, 'data' => json_encode($form->process($form->get_data()))];
        }

        // Render actual form.
        // Hack alert: Forcing bootstrap_renderer to initiate moodle page.
        $OUTPUT->header();
        $PAGE->start_collecting_javascript_requirements();
        $data = $form->render();
        $jsfooter = $PAGE->requires->get_end_code();
        $output = ['submitted' => false, 'html' => $data, 'javascript' => $jsfooter];
        return $output;
    }

    /**
     * Return for modal_form
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure(
            array(
                'submitted' => new external_value(PARAM_BOOL, 'If form was submitted and validated'),
                'data' => new external_value(PARAM_RAW, 'JSON-encoded return data from form processing method', VALUE_OPTIONAL),
                'html' => new external_value(PARAM_RAW, 'HTML fragment of the form', VALUE_OPTIONAL),
                'javascript' => new external_value(PARAM_RAW, 'JavaScript fragment of the form', VALUE_OPTIONAL)
            )
        );
    }
}
