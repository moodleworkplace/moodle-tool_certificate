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

namespace tool_certificate\hook;

/**
 * Certification element classes discovery hook.
 *
 * @package     tool_certificate
 * @copyright   2024 Petr Skoda
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class element_classes implements \core\hook\described_hook {
    /**
     * @var array<string, class-string<\tool_certificate\element>>
     */
    protected $classes = [];

    /**
     * Add known enabled element class.
     *
     * @param string $shortname name of certificateelement sub-plugin or other unique name
     * @param string $classname \tool_certificate\element class
     */
    public function add_class(string $shortname, string $classname): void {
        if (!class_exists($classname) || !is_subclass_of($classname, \tool_certificate\element::class)) {
            debugging('Invalid certificate element class: ' . $classname, DEBUG_DEVELOPER);
            return;
        }
        if (isset($this->classes[$shortname])) {
            debugging('Duplicate certificate element short name detected: ' . $shortname, DEBUG_DEVELOPER);
            // Override previous in case admins forgot to uninstall element add-on.
        }
        $this->classes[$shortname] = $classname;
    }

    /**
     * Returns known enabled element classes indexed with their short names.
     *
     * @return array<string, class-string<\tool_certificate\element>>
     */
    public function get_classes(): array {
        return $this->classes;
    }

    /**
     * Hook description.
     *
     * @return string
     */
    public static function get_hook_description(): string {
        return 'Certificate element class discovery';
    }

    /**
     * Hook tags.
     *
     * @return array
     */
    public static function get_hook_tags(): array {
        return [];
    }
}
