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
 * Unit tests for the completion condition.
 *
 * @package availability_completion
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use availability_coursecompletion\condition;

global $CFG;
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/completion/cron.php');

/**
 * Unit tests for the completion condition.
 *
 * @package availability_coursecompletion
 * @copyright 2016 Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class availability_coursecompletion_condition_testcase extends advanced_testcase {
    /**
     * Load required classes.
     */
    public function setUp() {
        // Load the mock info class so that it can be used.
        global $CFG;
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');
    }

    /**
     * Tests constructing and using condition as part of tree.
     */
    public function test_in_tree() {
        global $USER, $CFG;
        $this->resetAfterTest();

        $this->setAdminUser();

        $CFG->enablecompletion = true;
        $CFG->enableavailability = true;

        $generator = $this->getDataGenerator();

        // Create course with completion turned on and a Page.
        $course1 = $generator->create_course(array('enablecompletion' => 1));
        $course2 = $generator->create_course(array('enablecompletion' => 1));

        $page1 = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course1->id, 'completion' => COMPLETION_TRACKING_MANUAL));

        $page2 = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course2->id, 'completion' => COMPLETION_TRACKING_MANUAL));

        $modinfo = get_fast_modinfo($course1);
        $cm1 = $modinfo->get_cm($page1->cmid);

        $modinfo = get_fast_modinfo($course2);
        $cm2 = $modinfo->get_cm($page2->cmid);

        $info1 = new \core_availability\mock_info($course1, $USER->id);
        $info2 = new \core_availability\mock_info($course2, $USER->id);

        // Page in course1 is to be available on course2 completion.
        $structure = (object)array('op' => '|', 'show' => true, 'c' => array(
                (object)array('type' => 'coursecompletion', 'course' => (int)$course2->id,
            )));
        $tree = new \core_availability\tree($structure);

        // Initial check (user has not completed activity).
        $result = $tree->check_available(false, $info1, true, $USER->id);
        $this->assertFalse($result->is_available());

        // Mark activity complete.
        $completion = new completion_info($course2);
        $completion->update_state($cm2, COMPLETION_COMPLETE);
        // Ensure everything has been propagated.
        completion_cron();
        completion_cron();
        completion_cron();

        // Now it's true!
        $result = $tree->check_available(false, $info1, true, $USER->id);
        $this->assertTrue($result->is_available());
    }

    /**
     * Tests the constructor including error conditions. Also tests the
     * string conversion feature (intended for debugging only).
     */
    public function test_constructor() {
        // No parameters.
        $structure = new stdClass();
        try {
            $cond = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Missing or invalid ->course', $e->getMessage());
        }

        // Invalid $cm.
        $structure->course = 'hello';
        try {
            $cond = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Missing or invalid ->course', $e->getMessage());
        }

    }

    /**
     * Tests the save() function.
     */
    public function test_save() {
        $structure = (object)array('course' => 2);
        $cond = new condition($structure);
        $structure->type = 'coursecompletion';
        $this->assertEquals($structure, $cond->save());
    }

    /**
     * Tests the is_available and get_description functions.
     */
    public function test_usage() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $this->resetAfterTest();

        // Create course with completion turned on.
        $CFG->enablecompletion = true;
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();

        $course1 = $generator->create_course(array('enablecompletion' => 1));
        $course2 = $generator->create_course(array('enablecompletion' => 1));

        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course1->id);
        $generator->enrol_user($user->id, $course2->id);

        $this->setUser($user);

        // Create a Page with manual completion for basic checks.
        $page1 = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course1->id, 'name' => 'Availability Target Page!',
                'completion' => COMPLETION_TRACKING_MANUAL));

        // Create a Page with manual completion for basic checks.
        $page2 = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course2->id, 'name' => 'Course completion trigger Page!',
                'completion' => COMPLETION_TRACKING_MANUAL));

        // Get basic details.
        $modinfo = get_fast_modinfo($course1);
        $page1cm = $modinfo->get_cm($page1->cmid);

        $modinfo = get_fast_modinfo($course2);
        $page2cm = $modinfo->get_cm($page2->cmid);

        // Availability info on test target course
        $info1 = new \core_availability\mock_info($course2, $user->id);

        // COMPLETE with course 2 completion
        $cond = new condition((object)array(
                'course' => (int)$course2->id));
        $this->assertFalse($cond->is_available(false, $info1, true, $user->id));

        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course2);

        $this->assertRegExp('~Page!.*is marked complete~', $information);
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));

        // Mark trigger page complete.
        $completion = new completion_info($course2);
        $completion->update_state($page2cm, COMPLETION_COMPLETE);
        // ensure everything has been propagated
        completion_cron();
        completion_cron();
        completion_cron();

        // Assert course 2 has been completed.
        $cond = new condition((object)array(
                'course' => (int)$course2->id));
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $information = \core_availability\info::format_info($information, $course1);
        $this->assertRegExp('~Page!.*is incomplete~', $information);


        // Simulate deletion of an activity by using an invalid courseid. These
        // conditions always fail, regardless of NOT flag or INCOMPLETE.
        $cond = new condition((object)array(
                'course' => 100000));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course1);
        $this->assertRegExp('~(Missing course).*is marked complete~', $information);
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));
        $cond = new condition((object)array(
                'course' => 100000));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
    }
}
