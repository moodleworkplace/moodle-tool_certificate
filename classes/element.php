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
 * The base class for the certificate elements.
 *
 * @package    tool_certificate
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

use core\output\inplace_editable;

defined('MOODLE_INTERNAL') || die();

/**
 * Class element
 *
 * All certificate element plugins are based on this class.
 *
 * @package    tool_certificate
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class element {

    /** @var persistent\element  */
    protected $persistent;

    /** @var bool $hasposition Element can be positioned (has x, y, refpoint) */
    protected $hasposition = true;

    /** @var bool $istext This is a text element, it has font, color and width limiter */
    protected $istext = true;

    /** @var page */
    protected $page = null;

    /**
     * Constructor.
     */
    protected function __construct() {
    }

    /**
     * Create instance of an element
     *
     * @param int $id
     * @param null|\stdClass $obj
     * @return element
     * @throws \moodle_exception
     */
    public static function instance(int $id = 0, ?\stdClass $obj = null) : element {
        $element = self::instance_from_persistent(new \tool_certificate\persistent\element($id, $obj));
        if (!$element) {
            throw new \moodle_exception('not found'); // TODO string.
        }
        return $element;
    }

    /**
     * Helper method to create an instance from persistent
     *
     * @param \tool_certificate\persistent\element $persistent
     * @return element
     */
    protected static function instance_from_persistent(\tool_certificate\persistent\element $persistent) :? element {
        // Get the class name.
        /** @var element $classname */
        $classname = '\\certificateelement_' . $persistent->get('element') . '\\element';

        // Ensure the necessary class exists.
        if (!class_exists($classname) || !is_subclass_of($classname, self::class)) {
            return null;
        }

        /** @var self $el */
        $el = new $classname($persistent);
        $el->persistent = $persistent;
        return $el;
    }

    /**
     * New instance (not saved)
     *
     * @param string $elementtype
     * @param int $pageid
     * @return element
     */
    public static function new_instance(string $elementtype, int $pageid) : self {
        return self::instance(0, (object)['element' => $elementtype, 'pageid' => $pageid]);
    }

    /**
     * Returns the id.
     *
     * @return int
     */
    public function get_id() {
        return $this->persistent->get('id');
    }

    /**
     * Returns the elmeent.
     *
     * @return int
     */
    public function get_element() {
        return $this->persistent->get('element');
    }

    /**
     * Returns the page id.
     *
     * @return int
     */
    public function get_pageid() {
        return $this->persistent->get('pageid');
    }

    /**
     * Returns the name.
     *
     * @return int
     */
    public function get_name() {
        return $this->persistent->get('name');
    }

    /**
     * Returns the data.
     *
     * @return mixed
     */
    public function get_data() {
        return $this->persistent->get('data');
    }

    /**
     * Returns the font name.
     *
     * @return string
     */
    public function get_font() {
        return $this->persistent->get('font');
    }

    /**
     * Returns the font size.
     *
     * @return int
     */
    public function get_fontsize() {
        return $this->persistent->get('fontsize');
    }

    /**
     * Returns the font colour.
     *
     * @return string
     */
    public function get_colour() {
        return $this->persistent->get('colour');
    }

    /**
     * Returns the position x.
     *
     * @return int
     */
    public function get_posx() {
        return $this->persistent->get('posx');
    }

    /**
     * Returns the position y.
     *
     * @return int
     */
    public function get_posy() {
        return $this->persistent->get('posy');
    }

    /**
     * Returns the width.
     *
     * @return int
     */
    public function get_width() {
        return $this->persistent->get('width');
    }

    /**
     * Returns the refpoint.
     *
     * @return int
     */
    public function get_refpoint() {
        return $this->persistent->get('refpoint');
    }

    /**
     * Get sequence
     *
     * @return int
     */
    public function get_sequence() : int {
        return $this->persistent->get('sequence');
    }

    /**
     * Converts to stdClass
     * @return \stdClass
     */
    public function to_record() : \stdClass {
        return $this->persistent->to_record();
    }

    /**
     * This function renders the form elements when adding a certificate element.
     * Can be overridden if more functionality is needed.
     *
     * @param \MoodleQuickForm $mform the edit_form instance.
     */
    public function render_form_elements($mform) {
        // Render the common elements.
        if ($this->istext) {
            element_helper::render_form_element_font($mform);
            element_helper::render_form_element_colour($mform);
        }
        if ($this->hasposition) {
            element_helper::render_form_element_position($mform);
        }
        if ($this->istext) {
            element_helper::render_form_element_text_width($mform);
        }
        if ($this->hasposition) {
            element_helper::render_form_element_refpoint($mform);
        }
    }

    /**
     * Sets the data on the form when editing an element.
     * Can be overridden if more functionality is needed.
     *
     * @param \MoodleQuickForm $mform the edit_form instance
     */
    public function definition_after_data($mform) {
        // Loop through the properties of the element and set the values
        // of the corresponding form element, if it exists.
        $record = $this->persistent->to_record();
        unset($record->timecreated, $record->timemodifed, $record->pageid);
        foreach ($record as $property => $value) {
            if (!is_null($value) && $mform->elementExists($property)) {
                $element = $mform->getElement($property);
                $element->setValue($value);
            }
        }
    }

    /**
     * Performs validation on the element values.
     * Can be overridden if more functionality is needed.
     *
     * @param array $data the submitted data
     * @param array $files the submitted files
     * @return array the validation errors
     */
    public function validate_form_elements($data, $files) {
        return [];
    }

    /**
     * Handles saving the form elements created by this element.
     * Can be overridden if more functionality is needed.
     *
     * @param \stdClass $data the form data or partial data to be updated (i.e. name, posx, etc.)
     */
    public function save(\stdClass $data) {
        unset($data->id);
        if (!empty($this->persistent->get('id'))) {
            unset($data->pageid, $data->element);
        }
        foreach (array_keys(\tool_certificate\persistent\element::properties_definition()) as $key) {
            if (property_exists($data, $key)) {
                $this->persistent->set($key, $data->$key);
            }
        }

        if (!$this->persistent->get('id')) {

            // TODO this should not be here.
            if (empty($data->name) && empty($this->persistent->get('name'))) {
                $this->persistent->set('name', get_string('pluginname',
                    'certificateelement_' . $this->persistent->get('element')));
            }
            $this->persistent->set('sequence',
                \tool_certificate\element_helper::get_element_sequence($this->persistent->get('pageid')));
        }

        $this->persistent->save();
    }

    /**
     * Duplicates element (used as part of "duplicate template" task)
     *
     * @param \int $pageid
     * @return element new element
     */
    public function duplicate(int $pageid) : element {
        $record = $this->persistent->to_record();
        unset($record->id, $record->timemodified, $record->timecreated);
        $record->pageid = $pageid;
        $el = self::instance(0, $record);
        $el->persistent->save();
        return $el;
    }

    /**
     * This defines if an element plugin can be added to a certificate.
     * Can be overridden if an element plugin wants to take over the control.
     *
     * @return bool returns true if the element can be added, false otherwise
     */
    public static function can_add() {
        return true;
    }

    /**
     * Handles rendering the element on the pdf.
     *
     * Must be overridden.
     *
     * @param \pdf $pdf the pdf object
     * @param bool $preview true if it is a preview, false otherwise
     * @param \stdClass $user the user we are rendering this for
     * @param \stdClass $issue the issue we are rendering
     */
    public abstract function render($pdf, $preview, $user, $issue);

    /**
     * Render the element in html.
     *
     * Must be overridden.
     *
     * This function is used to render the element when we are using the
     * drag and drop interface to position it.
     *
     * @return string the html
     */
    public abstract function render_html();

    /**
     * Handles deleting any data this element may have introduced.
     * Can be overridden if more functionality is needed.
     *
     * @return bool success return true if deletion success, false otherwise
     */
    public function delete() {
        return $this->persistent->delete();
    }

    /**
     * Load a list of records.
     *
     * @param page $page
     *
     * @return \tool_certificate\element[]
     */
    public static function get_elements_in_page(page $page) {
        /** @var \tool_certificate\persistent\element[] $instances */
        $instances = \tool_certificate\persistent\element::get_records(
            ['pageid' => $page->get_id()], 'sequence', 'ASC');
        $els = [];
        foreach ($instances as $instance) {
            if ($element = self::instance_from_persistent($instance)) {
                $element->page = $page;
                $els[$element->get_id()] = $element;
            }
        }
        return $els;
    }

    /**
     * Get page
     * @return page
     */
    public function get_page() : page {
        if ($this->page === null) {
            $this->page = page::instance($this->persistent->get('pageid'));
        }
        return $this->page;
    }

    /**
     * Get template
     * @return template
     */
    public function get_template() : template {
        return $this->get_page()->get_template();
    }

    /**
     * Export
     *
     * @return output\element
     */
    public function get_exporter() : \tool_certificate\output\element {
        return new \tool_certificate\output\element($this->persistent, ['element' => $this]);
    }

    /**
     * Inplace editable name
     * @return inplace_editable
     */
    public function get_inplace_editable() : inplace_editable {
        return new \core\output\inplace_editable('tool_certificate', 'elementname',
            $this->get_id(), true,
            format_string($this->get_name()), $this->get_name());
    }

    /**
     * Name of the type of the element
     * @return string
     */
    public static function get_element_type_name() {
        $parts = preg_split('/\\\\/', static::class);
        return get_string('pluginname', $parts[0]);
    }

    /**
     * Element type icon or spacer if there is no icon
     * @param bool $withtitle
     * @return \pix_icon
     */
    public static function get_element_type_image(bool $withtitle = false) : \pix_icon {
        global $PAGE;
        $parts = preg_split('/\\\\/', static::class);
        $pluginname = $parts[0];
        $title = $withtitle ? self::get_element_type_name() : '';
        if ($PAGE->theme->resolve_image_location('icon', $pluginname, false)) {
            return new \pix_icon('icon', $title, $pluginname, ['class' => 'icon pluginicon']);
        } else {
            return new \pix_icon('spacer', $title, 'moodle', ['class' => 'icon pluginicon noicon']);
        }
    }

    /**
     * Can element be dragged?
     * @return bool
     */
    public function is_draggable() : bool {
        return $this->hasposition;
    }
}
