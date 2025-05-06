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
 * Front-end class.
 *
 * @package availability_coursecompletion
 * @copyright 2016 Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_coursecompletion;

defined('MOODLE_INTERNAL') || die();

/**
 * Front-end class.
 *
 * @package availability_coursecompletion
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {
    /**
     * @var array Cached init parameters
     */
    protected $cacheparams;

    protected function get_javascript_strings() {
        return array('label_course');
    }

    protected function get_javascript_init_params($course, \cm_info $cm = null, \section_info $section = null) {
        global $COURSE, $USER, $DB;

        // Use cached result if available. The cache is just because we call it
        // twice (once from allow_add) so it's nice to avoid doing all the
        // print_string calls twice.

        if (!isset($this->cacheparams)) {

            // Get list of courses which have completion enabled,
            // to fill the dropdown.
            // Courses must be in courses where i have edition capabilities
            if ($authored = get_user_capability_course('moodle/course:manageactivities', $USER->id, true, '', 'sortorder')) {

                $authoredcourses = array();
                foreach ($authored as $a) {
                    if ($a->id == $COURSE->id) {
                        // Avoid locking on your self
                        continue;
                    }

                    // TODO : better check circular impossibilities in which :
                    // - you try locking an activity on a completion course that depends on the local course completion AND the current activity 
                    // is involved in the local course completion

                    $fullcourse = $DB->get_record('course', array('id' => $a->id));
                    if ($fullcourse->enablecompletion) {
                        // Keep only completion enabled courses
                        $authoredcourses[$a->id] = '['.$fullcourse->shortname.'] '.format_string($fullcourse->fullname);
                    }
                }

                $converted = self::convert_associative_array_for_js($authoredcourses, 'field', 'display');
                $this->cacheparams = array($converted);
            } else {
                // Not used case, should be trapped before by the add_allow() method.
            }
        }
        return $this->cacheparams;
    }

    protected function allow_add($course, \cm_info $cm = null, \section_info $section = null) {
        global $CFG;

        // Check if there's at least one other course with completion enabled in my authoring courses.
        $params = $this->get_javascript_init_params($course, $cm, $section);
        return ((array)$params[0]) != false;
    }
}
