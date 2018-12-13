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
 * Provides useful functions related to elements.
 *
 * @package    tool_certificate
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

defined('MOODLE_INTERNAL') || die();

/**
 * Class helper.
 *
 * Provides useful functions related to elements.
 *
 * @package    tool_certificate
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element_helper {

    /**
     * @var int the top-left of element
     */
    const CUSTOMCERT_REF_POINT_TOPLEFT = 0;

    /**
     * @var int the top-center of element
     */
    const CUSTOMCERT_REF_POINT_TOPCENTER = 1;

    /**
     * @var int the top-left of element
     */
    const CUSTOMCERT_REF_POINT_TOPRIGHT = 2;

    /**
     * Common behaviour for rendering specified content on the pdf.
     *
     * @param \pdf $pdf the pdf object
     * @param \tool_certificate\element $element the certificate element
     * @param string $content the content to render
     */
    public static function render_content($pdf, $element, $content) {
        list($font, $attr) = self::get_font($element);
        $pdf->setFont($font, $attr, $element->get_fontsize());
        $fontcolour = \TCPDF_COLORS::convertHTMLColorToDec($element->get_colour(), $fontcolour);
        $pdf->SetTextColor($fontcolour['R'], $fontcolour['G'], $fontcolour['B']);

        $x = $element->get_posx();
        $y = $element->get_posy();
        $w = $element->get_width();
        $refpoint = $element->get_refpoint();
        $actualwidth = $pdf->GetStringWidth($content);

        if ($w and $w < $actualwidth) {
            $actualwidth = $w;
        }

        switch ($refpoint) {
            case self::CUSTOMCERT_REF_POINT_TOPRIGHT:
                $x = $element->get_posx() - $actualwidth;
                if ($x < 0) {
                    $x = 0;
                    $w = $element->get_posx();
                } else {
                    $w = $actualwidth;
                }
                break;
            case self::CUSTOMCERT_REF_POINT_TOPCENTER:
                $x = $element->get_posx() - $actualwidth / 2;
                if ($x < 0) {
                    $x = 0;
                    $w = $element->get_posx() * 2;
                } else {
                    $w = $actualwidth;
                }
                break;
        }

        if ($w) {
            $w += 0.0001;
        }
        $pdf->setCellPaddings(0, 0, 0, 0);
        $pdf->writeHTMLCell($w, 0, $x, $y, $content, 0, 0, false, true);
    }

    /**
     * Common behaviour for rendering specified content on the drag and drop page.
     *
     * @param \tool_certificate\element $element the certificate element
     * @param string $content the content to render
     * @return string the html
     */
    public static function render_html_content($element, $content) {
        list($font, $attr) = self::get_font($element);
        $fontstyle = 'font-family: ' . $font;
        if (strpos($attr, 'B') !== false) {
            $fontstyle .= '; font-weight: bold';
        }
        if (strpos($attr, 'I') !== false) {
            $fontstyle .= '; font-style: italic';
        }

        $style = $fontstyle . '; color: ' . $element->get_colour() . '; font-size: ' . $element->get_fontsize() . 'pt;';
        if ($element->get_width()) {
            $style .= ' width: ' . $element->get_width() . 'mm';
        }
        return \html_writer::div($content, '', array('style' => $style));
    }

    /**
     * Helper function to render the font elements.
     *
     * @param \MoodleQuickForm $mform the edit_form instance.
     */
    public static function render_form_element_font($mform) {
        $mform->addElement('select', 'font', get_string('font', 'tool_certificate'), self::get_fonts());
        $mform->setType('font', PARAM_TEXT);
        $mform->setDefault('font', 'times');
        $mform->addHelpButton('font', 'font', 'tool_certificate');
        $mform->addElement('select', 'fontsize', get_string('fontsize', 'tool_certificate'), self::get_font_sizes());
        $mform->setType('fontsize', PARAM_INT);
        $mform->setDefault('fontsize', 12);
        $mform->addHelpButton('fontsize', 'fontsize', 'tool_certificate');
    }

    /**
     * Helper function to render the colour elements.
     *
     * @param \MoodleQuickForm $mform the edit_form instance.
     */
    public static function render_form_element_colour($mform) {
        $mform->addElement('certificate_colourpicker', 'colour', get_string('fontcolour', 'tool_certificate'));
        $mform->setType('colour', PARAM_RAW); // Need to validate that this is a valid colour.
        $mform->setDefault('colour', '#000000');
        $mform->addHelpButton('colour', 'fontcolour', 'tool_certificate');
    }

    /**
     * Helper function to render the position elements.
     *
     * @param \MoodleQuickForm $mform the edit_form instance.
     */
    public static function render_form_element_position($mform) {
        $mform->addElement('text', 'posx', get_string('posx', 'tool_certificate'), array('size' => 10));
        $mform->setType('posx', PARAM_INT);
        $mform->setDefault('posx', 0);
        $mform->addHelpButton('posx', 'posx', 'tool_certificate');
        $mform->addElement('text', 'posy', get_string('posy', 'tool_certificate'), array('size' => 10));
        $mform->setType('posy', PARAM_INT);
        $mform->setDefault('posy', 0);
        $mform->addHelpButton('posy', 'posy', 'tool_certificate');
    }

    /**
     * Helper function to render the width element.
     *
     * @param \MoodleQuickForm $mform the edit_form instance.
     */
    public static function render_form_element_width($mform) {
        $mform->addElement('text', 'width', get_string('elementwidth', 'tool_certificate'), array('size' => 10));
        $mform->setType('width', PARAM_INT);
        $mform->setDefault('width', 0);
        $mform->addHelpButton('width', 'elementwidth', 'tool_certificate');
        $refpointoptions = array();
        $refpointoptions[self::CUSTOMCERT_REF_POINT_TOPLEFT] = get_string('topleft', 'tool_certificate');
        $refpointoptions[self::CUSTOMCERT_REF_POINT_TOPCENTER] = get_string('topcenter', 'tool_certificate');
        $refpointoptions[self::CUSTOMCERT_REF_POINT_TOPRIGHT] = get_string('topright', 'tool_certificate');
        $mform->addElement('select', 'refpoint', get_string('refpoint', 'tool_certificate'), $refpointoptions);
        $mform->setType('refpoint', PARAM_INT);
        $mform->setDefault('refpoint', self::CUSTOMCERT_REF_POINT_TOPCENTER);
        $mform->addHelpButton('refpoint', 'refpoint', 'tool_certificate');
    }

    /**
     * Helper function to performs validation on the colour element.
     *
     * @param array $data the submitted data
     * @return array the validation errors
     */
    public static function validate_form_element_colour($data) {
        $errors = array();
        // Validate the colour.
        if (!self::validate_colour($data['colour'])) {
            $errors['colour'] = get_string('invalidcolour', 'tool_certificate');
        }
        return $errors;
    }

    /**
     * Helper function to performs validation on the position elements.
     *
     * @param array $data the submitted data
     * @return array the validation errors
     */
    public static function validate_form_element_position($data) {
        $errors = array();

        // Check if posx is not set, or not numeric or less than 0.
        if ((!isset($data['posx'])) || (!is_numeric($data['posx'])) || ($data['posx'] < 0)) {
            $errors['posx'] = get_string('invalidposition', 'tool_certificate', 'X');
        }
        // Check if posy is not set, or not numeric or less than 0.
        if ((!isset($data['posy'])) || (!is_numeric($data['posy'])) || ($data['posy'] < 0)) {
            $errors['posy'] = get_string('invalidposition', 'tool_certificate', 'Y');
        }

        return $errors;
    }

    /**
     * Helper function to perform validation on the width element.
     *
     * @param array $data the submitted data
     * @return array the validation errors
     */
    public static function validate_form_element_width($data) {
        $errors = array();

        // Check if width is less than 0.
        if (isset($data['width']) && $data['width'] < 0) {
            $errors['width'] = get_string('invalidelementwidth', 'tool_certificate');
        }

        return $errors;
    }

    /**
     * Returns the font used for this element.
     *
     * @param \tool_certificate\element $element the certificate element
     * @return array the font and font attributes
     */
    public static function get_font($element) {
        // Variable for the font.
        $font = $element->get_font();
        // Get the last two characters of the font name.
        $fontlength = strlen($font);
        $lastchar = $font[$fontlength - 1];
        $secondlastchar = $font[$fontlength - 2];
        // The attributes of the font.
        $attr = '';
        // Check if the last character is 'i'.
        if ($lastchar == 'i') {
            // Remove the 'i' from the font name.
            $font = substr($font, 0, -1);
            // Check if the second last char is b.
            if ($secondlastchar == 'b') {
                // Remove the 'b' from the font name.
                $font = substr($font, 0, -1);
                $attr .= 'B';
            }
            $attr .= 'I';
        } else if ($lastchar == 'b') {
            // Remove the 'b' from the font name.
            $font = substr($font, 0, -1);
            $attr .= 'B';
        }
        return array($font, $attr);
    }

    /**
     * Validates the colour selected.
     *
     * @param string $colour
     * @return bool returns true if the colour is valid, false otherwise
     */
    public static function validate_colour($colour) {
        // List of valid HTML colour names.
        $colournames = array(
            'aliceblue', 'antiquewhite', 'aqua', 'aquamarine', 'azure',
            'beige', 'bisque', 'black', 'blanchedalmond', 'blue',
            'blueviolet', 'brown', 'burlywood', 'cadetblue', 'chartreuse',
            'chocolate', 'coral', 'cornflowerblue', 'cornsilk', 'crimson',
            'cyan', 'darkblue', 'darkcyan', 'darkgoldenrod', 'darkgray',
            'darkgrey', 'darkgreen', 'darkkhaki', 'darkmagenta',
            'darkolivegreen', 'darkorange', 'darkorchid', 'darkred',
            'darksalmon', 'darkseagreen', 'darkslateblue', 'darkslategray',
            'darkslategrey', 'darkturquoise', 'darkviolet', 'deeppink',
            'deepskyblue', 'dimgray', 'dimgrey', 'dodgerblue', 'firebrick',
            'floralwhite', 'forestgreen', 'fuchsia', 'gainsboro',
            'ghostwhite', 'gold', 'goldenrod', 'gray', 'grey', 'green',
            'greenyellow', 'honeydew', 'hotpink', 'indianred', 'indigo',
            'ivory', 'khaki', 'lavender', 'lavenderblush', 'lawngreen',
            'lemonchiffon', 'lightblue', 'lightcoral', 'lightcyan',
            'lightgoldenrodyellow', 'lightgray', 'lightgrey', 'lightgreen',
            'lightpink', 'lightsalmon', 'lightseagreen', 'lightskyblue',
            'lightslategray', 'lightslategrey', 'lightsteelblue', 'lightyellow',
            'lime', 'limegreen', 'linen', 'magenta', 'maroon',
            'mediumaquamarine', 'mediumblue', 'mediumorchid', 'mediumpurple',
            'mediumseagreen', 'mediumslateblue', 'mediumspringgreen',
            'mediumturquoise', 'mediumvioletred', 'midnightblue', 'mintcream',
            'mistyrose', 'moccasin', 'navajowhite', 'navy', 'oldlace', 'olive',
            'olivedrab', 'orange', 'orangered', 'orchid', 'palegoldenrod',
            'palegreen', 'paleturquoise', 'palevioletred', 'papayawhip',
            'peachpuff', 'peru', 'pink', 'plum', 'powderblue', 'purple', 'red',
            'rosybrown', 'royalblue', 'saddlebrown', 'salmon', 'sandybrown',
            'seagreen', 'seashell', 'sienna', 'silver', 'skyblue', 'slateblue',
            'slategray', 'slategrey', 'snow', 'springgreen', 'steelblue', 'tan',
            'teal', 'thistle', 'tomato', 'turquoise', 'violet', 'wheat', 'white',
            'whitesmoke', 'yellow', 'yellowgreen'
        );

        if (preg_match('/^#?([[:xdigit:]]{3}){1,2}$/', $colour)) {
            return true;
        } else if (in_array(strtolower($colour), $colournames)) {
            return true;
        }

        return false;
    }

    /**
     * Helper function that returns the sequence on a specified certificate page for a
     * newly created element.
     *
     * @param int $pageid the id of the page we are adding this element to
     * @return int the element number
     */
    public static function get_element_sequence($pageid) {
        global $DB;

        // Set the sequence of the element we are creating.
        $sequence = 1;
        // Check if there already elements that exist, if so, overwrite value.
        $sql = "SELECT MAX(sequence) as maxsequence
                  FROM {tool_certificate_elements}
                 WHERE pageid = :id";
        // Get the current max sequence on this page and add 1 to get the new sequence.
        if ($maxseq = $DB->get_record_sql($sql, array('id' => $pageid))) {
            $sequence = $maxseq->maxsequence + 1;
        }

        return $sequence;
    }

    /**
     * Return the list of possible elements to add.
     *
     * @return array the list of element types that can be used.
     */
    public static function get_available_element_types() {
        global $CFG;

        // Array to store the element types.
        $options = array();

        $plugins = self::get_enabled_plugins();

        // Loop through the enabled plugins.
        foreach ($plugins as $plugin) {
            $classname = '\\certificateelement_' . $plugin. '\\element';
            // Ensure the necessary class exists.
            if (class_exists($classname)) {
                // Additionally, check if the user is allowed to add the element at all.
                if ($classname::can_add()) {
                    $component = "certificateelement_{$plugin}";
                    $options[$plugin] = get_string('pluginname', $component);
                }
            }
        }

        return $options;
    }

    /**
     * Get the list of element plugins enabled on site level.
     * @return array  The list of enabled plugins
     */
    public static function get_enabled_plugins() {
        global $DB;

        // Get all available plugins.
        $manager = new plugin_manager();
        $plugins = $manager->get_sorted_plugins_list();
        if (!$plugins) {
            return array();
        }

        // Check they are enabled using get_config (which is cached and hopefully fast).
        $enabled = array();
        foreach ($plugins as $plugin) {
            $disabled = get_config('certificateelement_' . $plugin, 'disabled');
            if (empty($disabled)) {
                $enabled[$plugin] = $plugin;
            }
        }
        return $enabled;
    }

    /**
     * Return the list of possible fonts to use.
     */
    private static function get_fonts() {
        global $CFG;

        require_once($CFG->libdir . '/pdflib.php');

        $arrfonts = [];
        $pdf = new \pdf();
        $fontfamilies = $pdf->get_font_families();
        foreach ($fontfamilies as $fontfamily => $fontstyles) {
            foreach ($fontstyles as $fontstyle) {
                $fontstyle = strtolower($fontstyle);
                if ($fontstyle == 'r') {
                    $filenamewoextension = $fontfamily;
                } else {
                    $filenamewoextension = $fontfamily . $fontstyle;
                }
                $fullpath = \TCPDF_FONTS::_getfontpath() . $filenamewoextension;
                // Set the name of the font to null, the include next should then set this
                // value, if it is not set then the file does not include the necessary data.
                $name = null;
                // Some files include a display name, the include next should then set this
                // value if it is present, if not then $name is used to create the display name.
                $displayname = null;
                // Some of the TCPDF files include files that are not present, so we have to
                // suppress warnings, this is the TCPDF libraries fault, grrr.
                @include($fullpath . '.php');
                // If no $name variable in file, skip it.
                if (is_null($name)) {
                    continue;
                }
                // Check if there is no display name to use.
                if (is_null($displayname)) {
                    // Format the font name, so "FontName-Style" becomes "Font Name - Style".
                    $displayname = preg_replace("/([a-z])([A-Z])/", "$1 $2", $name);
                    $displayname = preg_replace("/([a-zA-Z])-([a-zA-Z])/", "$1 - $2", $displayname);
                }

                $arrfonts[$filenamewoextension] = $displayname;
            }
        }
        ksort($arrfonts);

        return $arrfonts;
    }

    /**
     * Return the list of possible font sizes to use.
     */
    private static function get_font_sizes() {
        // Array to store the sizes.
        $sizes = array();

        for ($i = 1; $i <= 200; $i++) {
            $sizes[$i] = $i;
        }

        return $sizes;
    }
}
