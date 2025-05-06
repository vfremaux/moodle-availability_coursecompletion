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
 * Course completion condition.
 *
 * @package availability_coursecompletion
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_coursecompletion;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/completionlib.php');

/**
 * course completion condition.
 *
 * @package availability_coursecompletion
 * @copyright 2016 Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
    /** @var int ID of module that this depends on */
    protected $courseid;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {

        // Get course.
        if (isset($structure->c) && is_number($structure->c)) {
            $this->courseid = (int)$structure->c;
        } else {
            throw new \coding_exception('Missing or invalid ->courseid for completion condition');
        }
    }

    public function save() {
        return (object)array('type' => 'coursecompletion',
                'c' => $this->courseid);
    }

    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        global $DB, $USER;

        $course = $DB->get_record('course', array('id' => $this->courseid));
        if (!$course) {
            // If course was deleted consider the condition is blocking.
            return false;
        }

        $completion = new \completion_info($course);

        $allow = $completion->is_course_complete($userid);

        if ($not) {
            $allow = !$allow;
        }

        return $allow;
    }

    public function get_description($full, $not, \core_availability\info $info) {
        global $DB;

        // check course still exists.
        if (!$course = $DB->get_record('course', array('id' => $this->courseid))) {
            return get_string('missing', 'availability_coursecompletion', $this->courseid);
        }

        return get_string('requirescompletion', 'availability_coursecompletion', '['.$course->shortname.'] '.format_string($course->fullname));
    }

    protected function get_debug_string() {
        return ' Completion in course '.$this->courseid;
    }

    public function update_after_restore($restoreid, $courseid, \base_logger $logger, $name) {
        return true;
    }
}
