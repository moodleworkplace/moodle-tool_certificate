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
 * This file contains the classes for the admin settings of the certificate tool.
 *
 * @package   tool_certificate
 * @copyright 2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_certificate;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/adminlib.php');

/**
 * Manage element plugins
 *
 * @package   tool_certificate
 * @copyright 2018 Daniel Neis Araujo <daniel@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugin_manager {

    /**
     * Constructor
     */
    public function __construct() {
        $this->pageurl = new \moodle_url('/admin/tool/certificate/adminmanageplugins.php');
    }

    /**
     * This is the entry point for this controller class.
     *
     * @param string $action - The action to perform
     * @param string $plugin - Optional name of a plugin type to perform the action on
     * @return None
     */
    public function execute($action, $plugin) {
        global $OUTPUT;
        if ($action == null) {
            $action = 'view';
        }

        $this->check_permissions();

        // Process.
        if ($action == 'hide' && $plugin != null) {
            $action = $this->hide_plugin($plugin);
        } else if ($action == 'show' && $plugin != null) {
            $action = $this->show_plugin($plugin);
        } else if ($action == 'moveup' && $plugin != null) {
            $action = $this->move_plugin($plugin, 'up');
        } else if ($action == 'movedown' && $plugin != null) {
            $action = $this->move_plugin($plugin, 'down');
        } else if ($action == 'view') {

            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('manageelementplugins', 'tool_certificate'));

            $this->view_plugins_table();

            echo $OUTPUT->footer();
        }
    }

    /**
     * Write the HTML for the submission plugins table.
     *
     * @return None
     */
    private function view_plugins_table() {
        global $OUTPUT, $CFG;
        require_once($CFG->libdir . '/tablelib.php');

        // Set up the table.
        $table = new \flexible_table('pluginsadminttable');
        $table->define_baseurl($this->pageurl);
        $table->define_columns(array('pluginname', 'version', 'hideshow', 'order', 'settings', 'uninstall'));
        $table->define_headers(array(get_string('name'),
                get_string('version'), get_string('hideshow', 'tool_certificate'),
                get_string('order'), get_string('settings'), get_string('uninstallplugin', 'core_admin')));
        $table->set_attribute('id', 'plugins');
        $table->set_attribute('class', 'admintable generaltable');
        $table->setup();

        $plugins = $this->get_sorted_plugins_list();

        foreach ($plugins as $idx => $plugin) {
            $row = array();
            $class = '';

            $row[] = get_string('pluginname', 'certificateelement_' . $plugin);
            $row[] = get_config('certificateelement_' . $plugin, 'version');

            $visible = !get_config('certificateelement_' . $plugin, 'disabled');

            if ($visible) {
                $row[] = $this->format_icon_link('hide', $plugin, 'i/hide', get_string('disable'));
            } else {
                $row[] = $this->format_icon_link('show', $plugin, 'i/show', get_string('enable'));
                $class = 'dimmed_text';
            }

            $movelinks = '';
            if (!$idx == 0) {
                $movelinks .= $this->format_icon_link('moveup', $plugin, 't/up', get_string('up'));
            } else {
                $movelinks .= $OUTPUT->spacer(array('width' => 16));
            }
            if ($idx != count($plugins) - 1) {
                $movelinks .= $this->format_icon_link('movedown', $plugin, 't/down', get_string('down'));
            }
            $row[] = $movelinks;

            $exists = file_exists($CFG->dirroot . '/'. $CFG->admin . '/tool/certificate/element/' . $plugin . '/settings.php');
            if ($row[1] != '' && $exists) {
                $row[] = html_writer::link(new \moodle_url('/admin/settings.php',
                        array('section' => 'certificateelement_' . $plugin)), get_string('settings'));
            } else {
                $row[] = '&nbsp;';
            }

            $row[] = $this->format_icon_link('delete', $plugin, 'i/trash', get_string('uninstallplugin', 'core_admin'));

            $table->add_data($row, $class);
        }

        $table->finish_output();
    }

    /**
     * Check this user has permission to edit the list of installed plugins
     *
     * @return None
     */
    private function check_permissions() {
        // Check permissions.
        require_login();
        $systemcontext = \context_system::instance();
        require_capability('moodle/site:config', $systemcontext);
    }

    /**
     * Hide this plugin.
     *
     * @param string $plugin - The plugin to hide
     */
    public function hide_plugin($plugin) {
        set_config('disabled', 1, 'certificateelement_' . $plugin);
        \core_plugin_manager::reset_caches();
        redirect($this->pageurl);
    }

    /**
     * Show this plugin.
     *
     * @param string $plugin - The plugin to show
     */
    public function show_plugin($plugin) {
        set_config('disabled', 0, 'certificateelement_' . $plugin);
        \core_plugin_manager::reset_caches();
        redirect($this->pageurl);
    }

    /**
     * Return a list of plugins sorted by the order defined in the admin interface
     *
     * @return array The list of plugins
     */
    public function get_sorted_plugins_list() {
        $names = \core_component::get_plugin_list('certificateelement');

        $result = array();

        foreach ($names as $name => $path) {
            $idx = get_config('certificateelement_' . $name, 'sortorder');
            if (!$idx) {
                $idx = 0;
            }
            while (array_key_exists($idx, $result)) {
                $idx += 1;
            }
            $result[$idx] = $name;
        }
        ksort($result);

        return $result;
    }

    /**
     * Util function for writing an action icon link
     *
     * @param string $action URL parameter to include in the link
     * @param string $plugin URL parameter to include in the link
     * @param string $icon The key to the icon to use (e.g. 't/up')
     * @param string $alt The string description of the link used as the title and alt text
     * @return string The icon/link
     */
    private function format_icon_link($action, $plugin, $icon, $alt) {
        global $OUTPUT;

        $url = $this->pageurl;

        if ($action === 'delete') {
            $url = \core_plugin_manager::instance()->get_uninstall_url('certificateelement_'.$plugin, 'manage');
            if (!$url) {
                return '&nbsp;';
            }
            return \html_writer::link($url, get_string('uninstallplugin', 'core_admin'));
        }

        return $OUTPUT->action_icon(new \moodle_url($url,
                array('action' => $action, 'plugin' => $plugin, 'sesskey' => \sesskey())),
                new \pix_icon($icon, $alt, 'moodle', array('title' => $alt)),
                null, array('title' => $alt)) . ' ';
    }

    /**
     * Change the order of this plugin.
     *
     * @param string $plugintomove - The plugin to move
     * @param string $dir - up or down
     * @return string The next page to display
     */
    public function move_plugin($plugintomove, $dir) {
        // Get a list of the current plugins.
        $plugins = $this->get_sorted_plugins_list();

        $currentindex = 0;

        // Throw away the keys.
        $plugins = array_values($plugins);

        // Find this plugin in the list.
        foreach ($plugins as $key => $plugin) {
            if ($plugin == $plugintomove) {
                $currentindex = $key;
                break;
            }
        }

        // Make the switch.
        if ($dir == 'up') {
            if ($currentindex > 0) {
                $tempplugin = $plugins[$currentindex - 1];
                $plugins[$currentindex - 1] = $plugins[$currentindex];
                $plugins[$currentindex] = $tempplugin;
            }
        } else if ($dir == 'down') {
            if ($currentindex < (count($plugins) - 1)) {
                $tempplugin = $plugins[$currentindex + 1];
                $plugins[$currentindex + 1] = $plugins[$currentindex];
                $plugins[$currentindex] = $tempplugin;
            }
        }

        // Save the new normal order.
        foreach ($plugins as $key => $plugin) {
            set_config('sortorder', $key, 'certificateelement_' . $plugin);
        }
        redirect($this->pageurl);
    }
}
