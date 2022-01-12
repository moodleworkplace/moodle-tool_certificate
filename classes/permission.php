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
 * Certificates-related permissions
 *
 * @package    tool_certificate
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

/**
 * Certificates-related permissions
 *
 * @package    tool_certificate
 * @copyright  2019 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class permission {


    /**
     * If a user can manage this template.
     *
     * @param \context $context
     * @return bool
     */
    public static function can_manage(\context $context): bool {
        return has_capability('tool/certificate:manage', $context);
    }

    /**
     * Ensures the user has the proper capabilities to manage this template.
     *
     * @param \context $context
     * @throws \required_capability_exception if the user does not have the necessary capabilities (ie. Fred)
     */
    public static function require_can_manage(\context $context) {
        if (!self::can_manage($context)) {
            throw new \required_capability_exception($context, 'tool/certificate:manage', 'nopermission', 'error');
        }
    }

    /**
     * If a user can manage this template.
     *
     * @param \context $context
     * @return bool
     */
    public static function can_issue_to_anybody(\context $context): bool {
        return has_capability('tool/certificate:issue', $context);
    }

    /**
     * Checks if current user is hidden by tenancy (belongs to another tenant)
     *
     * @uses \tool_tenant\tenancy::is_user_hidden_by_tenancy()
     *
     * @param int|\stdClass $user
     * @return mixed
     */
    public static function is_user_hidden_by_tenancy($user) {
        return component_class_callback('tool_tenant\\tenancy', 'is_user_hidden_by_tenancy', [$user]);
    }

    /**
     * If current user can verify certificates
     *
     * @return bool
     */
    public static function can_verify(): bool {
        global $CFG;
        // When user is not logged in as guest, we still want to check the capability for the "Guest" role.
        // It should be possible to make this capability available to the guests even when forcelogin is on
        // and "login as guest" is not available.
        if (isloggedin()) {
            return has_capability('tool/certificate:verify', \context_system::instance());
        } else {
            return has_capability('tool/certificate:verify', \context_system::instance(), $CFG->siteguest);
        }
    }

    /**
     * If current user can view the section on admin tree
     *
     * @return bool
     */
    public static function can_view_admin_tree(): bool {
        return self::can_manage_anywhere() || self::get_visible_categories_contexts();
    }

    /**
     * User can manage certificate template on system level or in any course category
     *
     * @return bool
     */
    public static function can_manage_anywhere(): bool {
        return has_capability('tool/certificate:manage', \context_system::instance()) ||
            \core_course_category::make_categories_list('tool/certificate:manage');
    }

    /**
     * User can create certificate template in system context or some course categories
     *
     * @return bool
     */
    public static function can_create(): bool {
        return self::can_manage_anywhere();
    }

    /**
     * User can create certificate template in system context or some course categories
     */
    public static function require_can_create() {
        if (!self::can_create()) {
            throw new \required_capability_exception(\context_system::instance(),
                'tool/certificate:manage', 'nopermission', 'error');
        }
    }

    /**
     * Get categories/system contexts where templates are present and visible to user
     *
     * "Visible" means that user can either manage them or view or issue certificates on them
     *
     * @param bool $usecache
     * @return array array of context ids (only CONTEXT_COURSECAT or CONTEXT_SYSTEM)
     */
    public static function get_visible_categories_contexts(bool $usecache = true): array {
        global $DB;

        $coursecatcache = \cache::make('core', 'coursecat');
        $key = 'tool_certificate_visiblecat';
        if ($usecache && ($value = $coursecatcache->get($key)) !== false) {
            return json_decode($value, true);
        }
        $ctxsql = \context_helper::get_preload_record_columns_sql('ctx');
        $ctxparams = ['coursecat' => CONTEXT_COURSECAT, 'contextsys' => CONTEXT_SYSTEM];
        $records = $DB->get_records_sql("SELECT DISTINCT ctx.id, $ctxsql FROM {tool_certificate_templates} ct
            JOIN {context} ctx ON (ctx.contextlevel = :coursecat OR ctx.contextlevel = :contextsys) AND ctx.id = ct.contextid",
            $ctxparams);
        $ids = [];
        foreach ($records as $record) {
            \context_helper::preload_from_record($record);
            $context = \context::instance_by_id($record->id);
            if (self::can_view_templates_in_context($context)) {
                $ids[] = $record->id;
            }
        }

        $coursecatcache->set($key, json_encode($ids));
        return $ids;
    }

    /**
     * Get templates visible to user
     *
     * "Visible" means that user can either manage, view or issue on template context
     * or every parent context with "shared" property.
     *
     * @param \context $currentcontext
     * @return array
     */
    public static function get_visible_templates(\context $currentcontext): array {
        global $DB;

        if (!self::can_issue_to_anybody($currentcontext)) {
            return [];
        }
        // Get visible category contexts.
        $visiblecatctxs = self::get_visible_categories_contexts(false);
        [$baseinsql, $baseinparams] = $DB->get_in_or_equal($visiblecatctxs, SQL_PARAMS_NAMED, '', true, 0);
        // Get shared parent contexts.
        $parentctxs = $currentcontext->get_parent_context_ids();
        [$parentinsql, $parentinparams] = $DB->get_in_or_equal($parentctxs, SQL_PARAMS_NAMED, '', true, 0);
        // Return templates.
        return $DB->get_records_sql("
            SELECT * FROM {tool_certificate_templates} ct
            WHERE ct.contextid $baseinsql
            OR ct.contextid $parentinsql AND ct.shared = 1
            ", $baseinparams + $parentinparams);
    }

    /**
     * If current user can view the section on admin tree
     *
     * Note, for course context this function does not check access to the course (enrolled/can view)!
     *
     * @param \context $context
     * @return bool
     */
    public static function can_view_templates_in_context(\context $context): bool {
        if ($context instanceof \context_coursecat && !\core_course_category::get($context->instanceid, IGNORE_MISSING)) {
            // Category is not visible.
            return false;
        }
        if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT
                && $context->contextlevel != CONTEXT_COURSE) {
            return false;
        }
        return has_any_capability(['tool/certificate:issue',
            'tool/certificate:manage',
            'tool/certificate:viewallcertificates'], $context);
    }

    /**
     * If current user can view list of certificates
     *
     * If $context is course context, we assume that we already called require_login()
     * and user actually has access to the course.
     *
     * @param int $userid The id of user which certificates were issued for.
     * @param \context|null $context
     * @return bool
     */
    public static function can_view_list(int $userid, ?\context $context = null): bool {
        global $USER;
        if ($userid == $USER->id) {
            return true;
        }
        if (!$context) {
            $context = \context_system::instance();
        }
        if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT
                && $context->contextlevel != CONTEXT_COURSE) {
            return false;
        }
        if (class_exists('\\tool_organisation\\organisation')) {
            $user = \tool_organisation\organisation::get_user_with_jobs();
            if ($user && $user->is_manager_over_user($userid)) {
                return true;
            }
        }
        return (has_any_capability(['tool/certificate:viewallcertificates', 'tool/certificate:issue'], $context) &&
                !self::is_user_hidden_by_tenancy($userid));
    }

    /**
     * Can manage shared images
     * @return bool
     */
    public static function can_manage_images() {
        return has_capability('tool/certificate:image', \context_system::instance());
    }

    /**
     * Can view all certificates
     *
     * If $context is course context, we assume that we already called require_login()
     * and user actually has access to the course.
     *
     * @param \context|null $context
     * @return bool
     */
    public static function can_view_all_certificates(?\context $context = null) {
        if (!$context) {
            $context = \context_system::instance();
        }
        if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSE
                && $context->contextlevel != CONTEXT_COURSECAT) {
            return false;
        }
        return has_capability('tool/certificate:viewallcertificates', $context);
    }

    /**
     * If current user can view an issued certificate
     *
     * @param template $template
     * @param \stdClass $issue
     * @param \context|null $context
     * @return bool
     */
    public static function can_view_issue(template $template, \stdClass $issue, ?\context $context = null): bool {
        global $USER;
        if (!$template->get_id()) {
            return false;
        }
        if ($issue->userid == $USER->id) {
            return true;
        }
        if (!$context) {
            $context = $template->get_context();
        }
        if (class_exists('\\tool_organisation\\organisation')) {
            $user = \tool_organisation\organisation::get_user_with_jobs();
            if ($user && $user->is_manager_over_user($issue->userid)) {
                return true;
            }
        }

        return has_any_capability(['tool/certificate:issue', 'tool/certificate:viewallcertificates',
                'tool/certificate:manage'] , $context) &&
            !self::is_user_hidden_by_tenancy($issue->userid);
    }

}
