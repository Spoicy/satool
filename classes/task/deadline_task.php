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
 * The Deadline scheduled task.
 *
 * @package    local_satool
 * @copyright  2021 Jeremy Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_satool\task;

use DateTime;

/**
 * Class deadline_task.
 *
 * @copyright 2021 Jeremy Funke
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class deadline_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('deadlinetask', 'local_satool');
    }

    /**
     * Run task.
     */
    public function execute() {
        global $CFG, $DB, $SITE;

        // Get database objects.
        $course = array_reverse($DB->get_records('local_satool_courses'))[0];
        $students = $DB->get_records('local_satool_students', ['courseid' => $course->id]);
        // Cycle through each student.
        foreach ($students as $student) {
            $project = $DB->get_record('local_satool_projects', ['id' => $student->projectid]);
            // Check if project submission exists and send an email if it doesn't.
            if ($project && !$project->submission) {
                $user = $DB->get_record('user', ['id' => $student->userid]);
                $dt = new DateTime();
                $dt->setTimestamp($course->submitdate);
                $datetime = $dt->format('d.m.Y H:i');
                email_to_user($user, $SITE->shortname, "Warn-Email Abgabetermin $course->name",
                    get_string('warningdeadline', 'local_satool', $datetime));
            }
        }
    }

}