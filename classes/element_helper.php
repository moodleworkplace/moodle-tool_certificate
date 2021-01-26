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
        $page = $element->get_page()->to_record();

        $align = 'L';
        if ($refpoint == self::CUSTOMCERT_REF_POINT_TOPRIGHT) {
            $align = 'R';
            $w = $w ?: ($x - $page->leftmargin);
            $x = $x - $w;
        } else if ($refpoint == self::CUSTOMCERT_REF_POINT_TOPCENTER) {
            $align = 'C';
            if (!$w) {
                $w = min($x - $page->leftmargin, $page->width - $page->rightmargin - $x) * 2;
            }
            $x = $x - $w / 2;
        } else {
            if (!$w) {
                $w = max(0, $page->width - $page->rightmargin - $x);
            }
        }
        if ($w) {
            $w += 0.0001;
        }
        $pdf->setCellPaddings(0, 0, 0, 0);
        $pdf->writeHTMLCell($w, 0, $x, $y, $content, 0, 0, false, true, $align);
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

        $style = $fontstyle . '; color: ' . $element->get_colour() . ';';
        if ($element->get_refpoint() == self::CUSTOMCERT_REF_POINT_TOPRIGHT) {
            $style .= ' text-align: right;';
        } else if ($element->get_refpoint() == self::CUSTOMCERT_REF_POINT_TOPCENTER) {
            $style .= ' text-align: center;';
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
        $mform->setDefault('font', 'freesans');
        $mform->addHelpButton('font', 'font', 'tool_certificate');
        $group = [];
        $group[] =& $mform->createElement('select', 'fontsize', get_string('fontsize', 'tool_certificate'), self::get_font_sizes());
        $group[] =& $mform->createElement('static', 'fontsizemetric', '', get_string('milimeter', 'tool_certificate'));
        $mform->addElement('group', 'fontsizegroup', get_string('fontsize', 'tool_certificate'), $group, ' ', false);
        $mform->setType('fontsize', PARAM_INT);
        $mform->setDefault('fontsize', 12);
        $mform->addHelpButton('fontsizegroup', 'fontsize', 'tool_certificate');
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

        $mform->addFormRule(function($data, $files) {
            return element_helper::validate_form_element_colour($data);
        });
    }

    /**
     * Helper function to render the position elements.
     *
     * @param \MoodleQuickForm $mform the edit_form instance.
     */
    public static function render_form_element_position($mform) {
        $group = [];
        $group[] =& $mform->createElement('text', 'posx', get_string('posx', 'tool_certificate'), ['size' => 10]);
        $group[] =& $mform->createElement('static', 'posxmetric', '', get_string('milimeter', 'tool_certificate'));
        $mform->addElement('group', 'posxgroup', get_string('posx', 'tool_certificate'), $group, ' ', false);
        $mform->setType('posx', PARAM_RAW_TRIMMED); // We need to track empty string.
        $mform->addHelpButton('posxgroup', 'posx', 'tool_certificate');
        $mform->setAdvanced('posxgroup');

        $group = [];
        $group[] =& $mform->createElement('text', 'posy', get_string('posy', 'tool_certificate'), ['size' => 10]);
        $group[] =& $mform->createElement('static', 'posymetric', '', get_string('milimeter', 'tool_certificate'));
        $mform->addElement('group', 'posygroup', get_string('posy', 'tool_certificate'), $group, ' ', false);
        $mform->setType('posy', PARAM_INT);
        $mform->addHelpButton('posygroup', 'posy', 'tool_certificate');
        $mform->setAdvanced('posygroup');

        $mform->addFormRule(function($data, $files) {
            return element_helper::validate_form_element_position($data);
        });
    }

    /**
     * If position was not entered in the form, suggest it automatically
     *
     * @param \stdClass $data
     * @param element $element
     */
    public static function suggest_position(\stdClass $data, element $element) {
        if (!property_exists($data, 'posx')) {
            // Position is not relevant for this element type.
            return;
        }
        if ($element->get_id() || strlen($data->posx)) {
            $data->posx = clean_param($data->posx, PARAM_INT);
            return;
        }

        // Only suggest posx if this element is being created, no posx is specified in the form.
        $refpoint = !empty($data->refpoint) ? $data->refpoint : self::CUSTOMCERT_REF_POINT_TOPLEFT;
        $pagerecord = $element->get_page()->to_record();
        if ($refpoint == self::CUSTOMCERT_REF_POINT_TOPRIGHT) {
            $data->posx = $pagerecord->width - $pagerecord->rightmargin;
        } else if ($refpoint == self::CUSTOMCERT_REF_POINT_TOPCENTER) {
            $data->posx = round(($pagerecord->width - $pagerecord->rightmargin + $pagerecord->leftmargin) / 2);
        } else {
            $data->posx = $pagerecord->leftmargin;
        }
    }

    /**
     * Helper function to render the width element (the width limiter for the text, advanced element)
     *
     * @param \MoodleQuickForm $mform the edit_form instance.
     */
    public static function render_form_element_text_width($mform) {
        $group = [];
        $group[] =& $mform->createElement('text', 'width', get_string('elementwidth', 'tool_certificate'), ['size' => 10]);
        $group[] =& $mform->createElement('static', 'widthmetric', '', get_string('milimeter', 'tool_certificate'));
        $mform->addElement('group', 'widthgroup', get_string('elementwidth', 'tool_certificate'), $group, ' ', false);
        $mform->setType('width', PARAM_INT);
        $mform->setDefault('width', 0);
        $mform->addHelpButton('widthgroup', 'elementwidth', 'tool_certificate');
        $mform->setAdvanced('widthgroup');

        $mform->addFormRule(function($data, $files) {
            $errors = [];
            // Check if width is less than 0.
            if (isset($data['width']) && $data['width'] < 0) {
                $errors['width'] = get_string('invalidelementwidth', 'tool_certificate');
            }
            return $errors;
        });
    }

    /**
     * Helper function to render the width of an element (an image)
     *
     * @param \MoodleQuickForm $mform the edit_form instance.
     * @param string $stringcomponent component for the strings:
     *     'width', 'width_help', 'invalidwidth'
     */
    public static function render_form_element_width($mform, $stringcomponent = 'certificateelement_image') {
        $group = [];
        $group[] =& $mform->createElement('text', 'width', get_string('width', $stringcomponent), ['size' => 10]);
        $group[] =& $mform->createElement('static', 'widthmetric', '', get_string('milimeter', 'tool_certificate'));
        $mform->addElement('group', 'widthgroup', get_string('width', $stringcomponent), $group, ' ', false);
        $mform->setType('width', PARAM_INT);
        $mform->setDefault('width', 0);
        $mform->addHelpButton('widthgroup', 'width', $stringcomponent);

        $mform->addFormRule(function($data, $files) use ($stringcomponent) {
            $errors = [];
            // Check if width is not set, or not numeric or less than 0.
            if (isset($data['width']) && (!is_numeric($data['width']) || $data['width'] < 0)) {
                $errors['width'] = get_string('invalidwidth', $stringcomponent);
            }
            return $errors;
        });
    }

    /**
     * Helper function to render the height of an element (an image)
     *
     * @param \MoodleQuickForm $mform the edit_form instance.
     * @param string $stringcomponent component for the strings:
     *     'height', 'height_help', 'invalidheight'
     */
    public static function render_form_element_height($mform, $stringcomponent = 'certificateelement_image') {
        $group = [];
        $group[] =& $mform->createElement('text', 'height', get_string('height', $stringcomponent), ['size' => 10]);
        $group[] =& $mform->createElement('static', 'heightmetric', '', get_string('milimeter', 'tool_certificate'));
        $mform->addElement('group', 'heightgroup', get_string('height', $stringcomponent), $group, ' ', false);
        $mform->setType('height', PARAM_INT);
        $mform->setDefault('height', 0);
        $mform->addHelpButton('heightgroup', 'height', $stringcomponent);

        $mform->addFormRule(function($data, $files) use ($stringcomponent) {
            $errors = [];
            // Check if height is not set, or not numeric or less than 0.
            if (isset($data['height']) && (!is_numeric($data['height']) || $data['height'] < 0)) {
                $errors['height'] = get_string('invalidheight', $stringcomponent);
            }
            return $errors;
        });
    }

    /**
     * Helper function to render reference point element that also serves as the text alignment
     *
     * @param \MoodleQuickForm $mform the edit_form instance.
     */
    public static function render_form_element_refpoint($mform) {
        $refpointoptions = array();
        $refpointoptions[self::CUSTOMCERT_REF_POINT_TOPLEFT] = get_string('alignleft', 'tool_certificate');
        $refpointoptions[self::CUSTOMCERT_REF_POINT_TOPCENTER] = get_string('aligncentre', 'tool_certificate');
        $refpointoptions[self::CUSTOMCERT_REF_POINT_TOPRIGHT] = get_string('alignright', 'tool_certificate');
        $mform->addElement('select', 'refpoint', get_string('alignment', 'tool_certificate'), $refpointoptions);
        $mform->setType('refpoint', PARAM_INT);
        $mform->setDefault('refpoint', self::CUSTOMCERT_REF_POINT_TOPCENTER);
        $mform->addHelpButton('refpoint', 'alignment', 'tool_certificate');
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
        if (!empty($data['posx']) && !is_numeric($data['posx'])) {
            $errors['posx'] = get_string('invalidposition', 'tool_certificate', 'X');
        }
        // Check if posy is not set, or not numeric or less than 0.
        if (!empty($data['posy']) && !is_numeric($data['posy'])) {
            $errors['posy'] = get_string('invalidposition', 'tool_certificate', 'Y');
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
            /** @var element $classname */
            $classname = '\\certificateelement_' . $plugin. '\\element';
            // Ensure the necessary class exists.
            if (class_exists($classname) && is_subclass_of($classname, element::class)) {
                // Additionally, check if the user is allowed to add the element at all.
                if ($classname::can_add()) {
                    $options[$plugin] = $classname::get_element_type_name();
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

    /**
     * Helps to render an image element and calculate width and height
     *
     * @param string $url
     * @param array $imageinfo array with 'width' and 'height' attributes for width and height of image in px
     * @param float $width display width in mm, 0 for automatic
     * @param float $height display height in mm, 0 for automatic
     * @param string $alt image alt text
     * @return string
     */
    public static function render_image_html(string $url, array $imageinfo, float $width, float $height, string $alt) {
        if (!$width && !$height) {
            // Width and height are not set, convert px to mm.
            // 1 px = 1/96 inch = 0.264583 mm .
            $width = (float)$imageinfo['width'] * 0.264583;
            $height = (float)$imageinfo['height'] * 0.264583;
        } else if (!$width || !$height) {
            $whratio = (float)$imageinfo['width'] / (float)$imageinfo['height'];
            $width = $width ?: $height * $whratio;
            $height = $height ?: $width / $whratio;
        }

        return \html_writer::img($url, $alt, ['data-width' => $width, 'data-height' => $height]);
    }
    /**
     * Calculates image size in mm
     *
     * @param \stored_file|string $file
     * @param array $fileinfo necessary for string files - must contain actual image width and height in pixels,
     *     ignored for stored_file instances (will be taken from the file itself)
     * @param int $width user specified width, in mm
     * @param int $height user specified height, in mm
     */
    public static function calculate_image_size($file, array $fileinfo, $width, $height) {
        $rv = ['width' => $width, 'height' => $height];
        if (!$file || ($width && $height)) {
            return $rv;
        }

        if ($file instanceof \stored_file) {
            $fileinfo = $file->get_imageinfo();
        }

        if (!$width && !$height) {
            // Width and height are not set, convert px to mm.
            // 1 px = 1/96 inch = 0.264583 mm .
            $rv['width'] = (float)$fileinfo['width'] * 0.264583;
            $rv['height'] = (float)$fileinfo['height'] * 0.264583;
        } else {
            $whratio = (float)$fileinfo['width'] / (float)$fileinfo['height'];
            $rv['width'] = $width ?: $height * $whratio;
            $rv['height'] = $height ?: $width / $whratio;
        }

        return $rv;
    }

    /**
     * Helps to render an image element in PDF
     *
     * @param \pdf $pdf
     * @param element $element
     * @param \stored_file|string $file
     * @param array $fileinfo necessary for string files - must contain actual image width and height in pixels,
     *     ignored for stored_file instances (will be taken from the file itself)
     * @param int $width user specified width, in mm
     * @param int $height user specified height, in mm
     */
    public static function render_image(\pdf $pdf, element $element, $file, array $fileinfo, $width, $height) {
        list($width, $height) = array_values(self::calculate_image_size($file, $fileinfo, $width, $height));

        if ($file instanceof \stored_file) {
            $location = make_request_directory() . '/target';
            $file->copy_content_to($location);

            $mimetype = $file->get_mimetype();
        } else {
            $location = $file;
            $mimetype = 'image/jpg';
        }

        if ($mimetype == 'image/svg+xml') {
            $pdf->ImageSVG($location, $element->get_posx(), $element->get_posy(), $width, $height);
        } else {
            $pdf->Image($location, $element->get_posx(), $element->get_posy(), $width, $height);
        }
    }

    /**
     * Return the list of possible shared images to use.
     *
     * @return array the list of images that can be used
     */
    public static function get_shared_images_list() {
        // Create file storage object.
        $fs = get_file_storage();

        // The array used to store the images.
        $arrfiles = array();
        if ($files = get_file_storage()->get_area_files(\context_system::instance()->id, 'tool_certificate',
            'image', false, 'filename', false)) {
            foreach ($files as $hash => $file) {
                $arrfiles[$file->get_id()] = $file->get_filename();
            }
        }

        if (count($arrfiles)) {
            \core_collator::asort($arrfiles);
            $arrfiles = array('0' => get_string('noimage', 'tool_certificate')) + $arrfiles;
        }

        return $arrfiles;
    }

    /**
     * Helper function to render the width element (the width limiter for the text, advanced element)
     *
     * @param \MoodleQuickForm $mform the edit_form instance.
     * @return bool whether the element was added (it would not be added if there are no shared images)
     */
    public static function render_shared_image_picker_element($mform) {
        $arrfiles = array();
        if ($files = get_file_storage()->get_area_files(\context_system::instance()->id, 'tool_certificate',
            'image', false, 'filename', false)) {
            foreach ($files as $hash => $file) {
                $arrfiles[$file->get_id()] = $file->get_filename();
            }
        }

        if (!$arrfiles) {
            return false;
        }

        \core_collator::asort($arrfiles);
        $arrfiles = array('0' => get_string('noimage', 'tool_certificate')) + $arrfiles;
        $mform->addElement('select', 'fileid', get_string('selectsharedimage', 'certificateelement_image'), $arrfiles);
        return true;
    }
}
