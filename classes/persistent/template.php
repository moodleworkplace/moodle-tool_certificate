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
 * Class template
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate\persistent;

use core\persistent;
use tool_certificate\permission;

/**
 * Class template
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template extends persistent {

    /** @var string */
    const TABLE = 'tool_certificate_templates';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'name' => [
                'type' => PARAM_TEXT,
            ],
            'contextid' => [
                'type' => PARAM_INT,
            ],
            'shared' => [
                'type' => PARAM_BOOL,
            ],
        ];
    }

    /**
     * Returns the formatted name of the template.
     *
     * @return string the name of the template
     */
    public function get_formatted_name() {
        return format_string($this->get('name'), true, ['escape' => false, 'context' => $this->get_context()]);
    }

    /**
     * The URL to edit certificate template
     *
     * @return \moodle_url
     */
    public function edit_url(): \moodle_url {
        return new \moodle_url('/admin/tool/certificate/template.php', ['id' => $this->get('id')]);
    }

    /**
     * The URL to view certificate issues
     *
     * @return \moodle_url
     */
    public function view_issues_url(): \moodle_url {
        return new \moodle_url('/admin/tool/certificate/certificates.php', ['templateid' => $this->get('id')]);
    }

    /**
     * Returns the context id.
     *
     * @return \context the context
     */
    public function get_context(): \context {
        return \context::instance_by_id($this->get('contextid'));
    }

    /**
     * If a user can manage this template.
     *
     * @return bool
     */
    public function can_manage(): bool {
        return permission::can_manage($this->get_context());
    }

    /**
     * Can view issues for this template
     * @return bool
     */
    public function can_view_issues() {
        return permission::can_view_templates_in_context($this->get_context());
    }

    /**
     * If a user can issue certificates from this template (to anybody)
     *
     * @param \context|null $context
     * @return bool
     */
    public function can_issue_to_anybody(?\context $context = null): bool {
        return $this->get('id') && permission::can_issue_to_anybody($context ?? $this->get_context());
    }
}
