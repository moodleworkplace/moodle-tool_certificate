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
 * Class heading_button
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate\output;

use renderer_base;

/**
 * Class heading_button
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_header_button implements \templatable {

    /** @var string */
    protected $title;
    /** @var array */
    protected $attributes;

    /**
     * heading_button constructor.
     *
     * @param string $title
     * @param array $attributes
     */
    public function __construct(string $title, array $attributes = []) {
        $this->title = $title;
        $this->attributes = $attributes;
    }

    /**
     * Export for template
     *
     * @param renderer_base $output
     * @return array|\stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = ['title' => $this->title, 'attributes' => []];
        foreach ($this->attributes as $key => $value) {
            if ($key === 'class') {
                $data['class'] = $value;
            } else {
                $data['attributes'][] = ['name' => $key, 'value' => $value];
            }
        }
        return $data;
    }

    /**
     * Renders the button
     *
     * @param renderer_base $output
     * @return string
     */
    public function render(renderer_base $output) : string {
        return $output->render_from_template('tool_certificate/page_header_button', $this->export_for_template($output));
    }
}
