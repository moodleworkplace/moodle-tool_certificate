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
 * Class page
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate\output;

use core\external\persistent_exporter;

/**
 * Class page
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page extends persistent_exporter {
    /**
     * Defines the persistent class.
     *
     * @return string
     */
    protected static function define_class(): string {
        return \tool_certificate\persistent\page::class;
    }

    /**
     * Related objects definition.
     *
     * @return array
     */
    protected static function define_related(): array {
        return [
            'page' => \tool_certificate\page::class,
        ];
    }

    /**
     * Get page from related data
     *
     * @return \tool_certificate\page
     */
    protected function get_page() : \tool_certificate\page {
        return $this->related['page'];
    }

    /**
     * Other properties.
     *
     * @return array
     */
    protected static function define_other_properties(): array {
        return [
            'elements' => ['type' => element::class . '[]'],
            'haselements' => ['type' => PARAM_BOOL],
            'title' => ['type' => PARAM_NOTAGS],
            'moveupurl' => ['type' => PARAM_URL],
            'movedownurl' => ['type' => PARAM_URL],
            'deleteurl' => ['type' => PARAM_URL],
            'pagenumber' => ['type' => PARAM_INT],
            'rightmarginoffset' => ['type' => PARAM_INT],
            'pagecentreoffset' => ['type' => PARAM_INT],
            'widthbetweenmargins' => ['type' => PARAM_INT],
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param \renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(\renderer_base $output): array {
        $elements = $this->get_page()->get_elements();
        $exportedelements = [];
        foreach ($elements as $element) {
            $exportedelements[] = $element->get_exporter()->export($output);
        }
        $allpages = $this->get_page()->get_template()->get_pages();
        $sequence = array_search($this->get_page()->get_id(), array_keys($allpages));
        $rv = [
            'elements' => $exportedelements,
            'haselements' => !empty($exportedelements),
            'pagenumber' => $sequence + 1,
            'title' => get_string('page', 'tool_certificate', $sequence + 1),
            'deleteurl' => '',
            'moveupurl' => '',
            'movedownurl' => '',
            'rightmarginoffset' => $this->data->width - $this->data->rightmargin,
            'pagecentreoffset' => ($this->data->width - $this->data->rightmargin + $this->data->leftmargin) / 2,
            'widthbetweenmargins' => $this->data->width - $this->data->rightmargin - $this->data->leftmargin,
        ];
        $baseurl = new \moodle_url('/admin/tool/certificate/template.php',
            ['pageid' => $this->get_page()->get_id(), 'sesskey' => sesskey()]);
        if ($sequence) {
            $rv['moveupurl'] = (new \moodle_url($baseurl, ['action' => 'moveuppage']))->out(false);
        }
        if ($sequence < count($allpages) - 1) {
            $rv['movedownurl'] = (new \moodle_url($baseurl, ['action' => 'movedownpage']))->out(false);
        }
        if (count($allpages) > 1) {
            $rv['deleteurl'] = (new \moodle_url($baseurl, ['action' => 'deletepage']))->out(false);
        }
        return $rv;
    }

}
