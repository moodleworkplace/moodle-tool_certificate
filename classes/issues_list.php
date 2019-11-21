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
 * Class issues_list
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

use tool_reportbuilder\local\entities\user as user_entity;
use tool_reportbuilder\report_action;
use tool_reportbuilder\report_column;
use tool_reportbuilder\system_report;

defined('MOODLE_INTERNAL') || die();

/**
 * Class issues_list
 *
 * @package     tool_certificate
 * @copyright   2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issues_list extends system_report {

    /** @var \tool_certificate\template */
    protected $template;

    /**
     * Initialise the report
     */
    protected function initialise() {
        if ($templateid = $this->get_parameter('templateid', 0, PARAM_INT)) {
            $this->template = \tool_certificate\template::instance($templateid);
        }
        $this->set_columns();
        $this->set_main_table('tool_certificate_issues', 'i');
        $this->add_base_condition_simple('i.templateid', $templateid);
        $this->add_base_join('INNER JOIN {user} u ON u.id = i.userid');
        $this->add_base_condition_sql(certificate::get_users_subquery());
        $this->add_base_fields('i.id, i.expires, i.code, i.userid'); // Necessary for row class and actions.
        $this->set_actions();
    }

    /**
     * Validates access to view this report with the given parameters
     *
     * @return bool
     */
    protected function can_view(): bool {
        return $this->template && $this->template->can_view_issues();
    }

    /**
     * Columns definitions
     */
    protected function set_columns() {
        $this->annotate_entity('tool_certificate_issues', new \lang_string('entitycertificateissues', 'tool_certificate'));
        $this->annotate_entity('user', new \lang_string('entityuser', 'tool_reportbuilder'));

        // Column "fullname".
        $newcolumn = (new report_column(
            'fullname',
            new \lang_string('fullname'),
            'user'
        ))
            ->add_fields(user_entity::get_all_user_name_fields(true, 'u'))
            ->add_field('u.id')
            ->set_is_default(true, 1)
            ->set_is_sortable(true, true);
        $newcolumn->add_callback([\tool_reportbuilder\local\helpers\format::class, 'fullname']);
        $this->add_column($newcolumn);

        // Column "awarded".
        $newcolumn = (new report_column(
            'timecreated',
            new \lang_string('receiveddate', 'tool_certificate'),
            'tool_certificate_issues'
        ))
            ->add_fields('i.timecreated')
            ->set_is_default(true, 2)
            ->set_is_sortable(true);
        $newcolumn->add_callback([\tool_reportbuilder\local\helpers\format::class, 'userdate']);
        $this->add_column($newcolumn);

        // Column "expires".
        $newcolumn = (new report_column(
            'expires',
            new \lang_string('expires', 'tool_certificate'),
            'tool_certificate_issues'
        ))
            ->add_field('i.expires')
            ->set_is_default(true, 3)
            ->set_is_sortable(true);
        $newcolumn->add_callback([$this, 'col_expires']);
        $this->add_column($newcolumn);

        // Column "code".
        $newcolumn = (new report_column(
            'code',
            new \lang_string('code', 'tool_certificate'),
            'tool_certificate_issues'
        ))
            ->add_field('i.code')
            ->set_is_default(true, 4)
            ->set_is_sortable(true);
        $newcolumn->add_callback([$this, 'col_code']);
        $newcolumn->set_is_available(permission::can_verify());
        $this->add_column($newcolumn);
    }

    /**
     * Issue actions
     */
    protected function set_actions() {
        // File.
        $icon = new \pix_icon('i/search', get_string('view'), 'core');
        $link = template::view_url(':code');
        $this->add_action((new report_action($link, $icon, [])));

        // Revoke.
        $template = $this->template;
        $icon = new \pix_icon('i/trash', get_string('revoke', 'tool_certificate'), 'core');
        $this->add_action(
            (new report_action(new \moodle_url('#'), $icon, ['data-action' => 'revoke', 'data-id' => ':id']))
                ->add_callback(function($row) use ($template) {
                    return $template && $template->can_revoke($row->userid);
                }));
    }

    /**
     * Report name
     * @return string
     */
    public static function get_name() {
        return get_string('certificates', 'tool_certificate');
    }

    /**
     * Generate the certificate expires column.
     *
     * @param int $expires
     * @return string
     */
    public function col_expires($expires) {
        if (!$expires) {
            return get_string('never');
        }
        $column = userdate($expires);
        if ($expires && $expires <= time()) {
            $column .= \html_writer::tag('span', get_string('expired', 'tool_certificate'),
                ['class' => 'badge badge-secondary']);
        }
        return $column;
    }

    /**
     * Generate the code column.
     *
     * @param string $code
     * @return string
     */
    public function col_code($code) {
        return \html_writer::link(new \moodle_url('/admin/tool/certificate/index.php', ['code' => $code]),
            $code, ['title' => get_string('verify', 'tool_certificate')]);
    }

    /**
     * CSS class for the row
     *
     * @param \stdClass $row
     * @return string
     */
    public function get_row_class(\stdClass $row): string {
        return ($row->expires && $row->expires < time()) ? 'dimmed_text' : '';
    }
}
