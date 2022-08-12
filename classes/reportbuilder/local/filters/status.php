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

declare(strict_types=1);

namespace tool_certificate\reportbuilder\local\filters;

use lang_string;
use MoodleQuickForm;
use core_reportbuilder\local\filters\boolean_select;

/**
 * Certificate status report filter
 *
 * Overrides the operator language strings.
 *
 * @package   tool_certificate
 * @copyright 2022 Moodle Pty Ltd <support@moodle.com>
 * @author    2022 Ruslan Kabalin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class status extends boolean_select {

    /**
     * Return an array of operators available for this filter
     *
     * @return lang_string[]
     */
    private function get_operators(): array {
        return [
            self::ANY_VALUE => new lang_string('filterisanyvalue', 'core_reportbuilder'),
            self::NOT_CHECKED => new lang_string('valid', 'tool_certificate'),
            self::CHECKED => new lang_string('expired', 'tool_certificate'),
        ];
    }

    /**
     * Setup form
     *
     * @param MoodleQuickForm $mform
     */
    public function setup_form(MoodleQuickForm $mform): void {
        $operatorlabel = get_string('filterfieldoperator', 'core_reportbuilder', $this->get_header());
        $mform->addElement('select', "{$this->name}_operator", $operatorlabel, $this->get_operators())
            ->setHiddenLabel(true);

        $mform->setType("{$this->name}_operator", PARAM_INT);
        $mform->setDefault("{$this->name}_operator", self::ANY_VALUE);
    }
}
