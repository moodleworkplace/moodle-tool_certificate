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

namespace tool_certificate;

use advanced_testcase;
use tool_certificate_generator;

/**
 * Tests for functions in observer.php
 *
 * @package     tool_certificate
 * @covers      \tool_certificate\element_helper
 * @copyright   2023 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class element_helper_test extends advanced_testcase {

    /**
     * Get certificate generator
     * @return tool_certificate_generator
     */
    protected function get_generator(): tool_certificate_generator {
        return $this->getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    public function test_allowed_filters(): void {
        $this->resetAfterTest();
        $this->assertEquals(['multilang'], element_helper::get_allowed_filters());
        set_config('allowfilters', "urltolink,emoticon", 'tool_certificate');
        $this->assertEquals(['urltolink', 'emoticon'], element_helper::get_allowed_filters());
    }

    public function test_format_text(): void {
        $this->resetAfterTest();
        // Setup fixture.
        filter_set_global_state('multilang', TEXTFILTER_ON);

        // Format text with multilang filter. It should apply by default.
        $text = '<span lang="en" class="multilang">En</span><span lang="fr" class="multilang">Fr</span>';
        $this->assertEquals('En', element_helper::format_text($text));

        // Do not allow multilang filter, allow something else.
        set_config('allowfilters', "urltolink,emoticon", 'tool_certificate');

        // Multilang filter no longer applies.
        $this->assertEquals($text, element_helper::format_text($text));

        // Do not allow any filters.
        set_config('allowfilters', "", 'tool_certificate');

        // Multilang filter no longer applies.
        $this->assertEquals($text, element_helper::format_text($text));
    }

    public function test_format_text_course(): void {
        $this->resetAfterTest();
        // Enable multilang filter in course context but not in system.
        filter_set_global_state('multilang', TEXTFILTER_OFF);
        $course = self::getDataGenerator()->create_course();
        filter_set_local_state('multilang', \context_course::instance($course->id)->id, TEXTFILTER_ON);

        // Format text with multilang filter. It should not apply in system but apply in the course.
        $text = '<span lang="en" class="multilang">En</span><span lang="fr" class="multilang">Fr</span>';
        $this->assertEquals($text, element_helper::format_text($text));
        $this->assertEquals('En', element_helper::format_text($text, $course->id));

        // Set 'multilang' filter as skipped.
        set_config('allowfilters', "", 'tool_certificate');

        // Multilang filter no longer applies in any context.
        $this->assertEquals($text, element_helper::format_text($text));
        $this->assertEquals($text, element_helper::format_text($text, $course->id));
    }
}
