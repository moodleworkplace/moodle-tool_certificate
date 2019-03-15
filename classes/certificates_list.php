<?php
// This file is part of Moodle - http://moodle.org/
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
 * Class certificates_list
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

use tool_reportbuilder\report_action;
use tool_reportbuilder\report_column;
use tool_reportbuilder\system_report;
use tool_wp\db;

defined('MOODLE_INTERNAL') || die();

/**
 * Class certificates_list
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificates_list extends system_report {

    /**
     * Initialise
     */
    protected function initialise() {
        $this->set_columns();
        // Set main table. For certificates we want a custom tenant filter, so disable automatic one.
        $this->set_main_table('tool_certificate_templates', 'c', false);
        $this->add_base_fields('c.id, c.name, c.tenantid');
        $this->set_downloadable(false);
        $this->add_actions();
    }

    /**
     * Validates access to view this report with the given parameters
     *
     * @return bool
     */
    protected function can_view(): bool {
        return template::can_view_admin_tree();
    }

    /**
     * Set columns
     */
    protected function set_columns() {
        $this->annotate_entity('tool_certificate', new \lang_string('entitycertificate', 'tool_certificate'));

        $newcolumn = (new report_column(
            'name',
            new \lang_string('name', 'tool_certificate'),
            'tool_certificate'
        ))
            ->add_fields('c.name, c.id, c.tenantid')
            ->set_is_default(true, 1)
            ->set_is_sortable(true, true);
        $newcolumn->add_callback(function($v, $row) {
            global $OUTPUT;
            $t = new template($row);
            return $t->get_editable_name()->render($OUTPUT);
        });
        $this->add_column($newcolumn);

        // Add 'Tenant' column visible only for users who can manage all tenants.
        $newcolumn = (new report_column(
            'tenantname',
            new \lang_string('tenant', 'tool_certificate'),
            'tool_certificate'
        ))
            ->add_join('LEFT JOIN {tool_tenant} t ON t.id = c.tenantid')
            ->add_field('t.name', 'tenantname')
            ->add_field('c.tenantid')
            ->set_is_default(true, 2)
            ->set_is_sortable(true)
            ->set_is_available(\tool_certificate\template::can_issue_or_manage_all_tenants())
            ->add_callback([$this, 'col_tenant_name']);
        $this->add_column($newcolumn);

        if (!\tool_certificate\template::can_issue_or_manage_all_tenants()) {
            // User can not manage all tenants' templates. Display templates from own tenant
            // and shared templates, do not display tenant column.
            $tenantid = db::generate_param_name();
            $this->add_base_join('LEFT JOIN {tool_tenant} t ON t.id = c.tenantid');
            $this->add_base_condition_sql("(c.tenantid = :{$tenantid} OR c.tenantid = 0)",
                [$tenantid => \tool_tenant\tenancy::get_tenant_id()]);
        }
    }

    /**
     * Name of the report
     *
     * @return string
     */
    public static function get_name() {
        return get_string('managetemplates', 'tool_certificate');
    }

    /**
     * Formatter for the tenant name
     *
     * @param mixed $value
     * @param \stdClass $template
     * @return string
     */
    public function col_tenant_name($value, \stdClass $template) {
        if ($template->tenantid) {
            return format_string($value);
        } else {
            return get_string('shared', 'tool_certificate');
        }
    }

    /**
     * Actions
     */
    protected function add_actions() {

        // Edit content.
        $editlink = new \moodle_url('/admin/tool/certificate/edit.php', array('tid' => ':id'));
        $icon = new \pix_icon('a/wp-arrow-right', get_string('editcontent', 'tool_certificate'), 'theme');
        $this->add_action((new report_action($editlink, $icon, []))
            ->add_callback(function($row) {
                return (new template($row))->can_manage();
            })
        );

        // Edit details.
        $editlink = new \moodle_url('#');
        $icon = new \pix_icon('a/wp-cog', get_string('editdetails', 'tool_certificate'), 'theme');
        $this->add_action(
            (new report_action($editlink, $icon, ['data-action' => 'editdetails', 'data-id' => ':id', 'data-name' => ':name']))
                ->add_callback(function($row) {
                    $t = new template($row);
                    $row->name = $t->get_formatted_name();
                    return $t->can_manage();
                })
        );

        // Preview.
        $previewlink = new \moodle_url('/admin/tool/certificate/view.php',
            ['preview' => 1, 'templateid' => ':id', 'code' => 'previewing']);
        $icon = new \pix_icon('a/wp-search', get_string('preview'), 'theme');
        $this->add_action((new report_action($previewlink, $icon, []))
            ->add_callback(function($row) {
                return (new template($row))->can_manage();
            })
        );

        // View issue.
        $issueslink = new \moodle_url('/admin/tool/certificate/certificates.php', array('templateid' => ':id'));
        $issuesstr  = get_string('certificatesissued', 'tool_certificate');
        $icon = new \pix_icon('a/wp-list', $issuesstr, 'theme');
        $this->add_action((new report_action($issueslink, $icon, []))
            ->add_callback(function($row) {
                return (new template($row))->can_view_issues();
            })
        );

        // Issue.
        $newissuelink = new \moodle_url('#');
        $newissuestr  = get_string('issuenewcertificate', 'tool_certificate');
        $icon = new \pix_icon('a/wp-plus', $newissuestr, 'theme');
        $this->add_action((new report_action($newissuelink, $icon, ['data-action' => 'issue', 'data-tid' => ':id']))
            ->add_callback(function($row) {
                return (new template($row))->can_issue();
            })
        );

        // Duplicate.
        $duplicatelink = new \moodle_url('/admin/tool/certificate/manage_templates.php',
            array('tid' => ':id', 'action' => 'duplicate', 'sesskey' => sesskey()));
        $icon = new \pix_icon('a/wp-duplicate', get_string('duplicate'), 'theme');
        $this->add_action((new report_action($duplicatelink, $icon, []))
            ->add_callback(function($row) {
                return (new template($row))->can_duplicate();
            })
        );

        // Delete.
        $deletelink = new \moodle_url('/admin/tool/certificate/manage_templates.php',
            array('tid' => ':id', 'action' => 'delete', 'sesskey' => sesskey()));
        $icon = new \pix_icon('a/wp-trash', get_string('delete'), 'theme');
        $this->add_action((new report_action($deletelink, $icon, []))
            ->add_callback(function($row) {
                return (new template($row))->can_manage();
            })
        );

    }
}
