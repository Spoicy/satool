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
 * The Submit Date scheduled task.
 *
 * @package    local_satool
 * @copyright  2021 Jeremy Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_satool\task;

use DateTime;

class submitdate_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('submitdatetask', 'local_satool');
    }

    /**
     * Run task.
     */
    public function execute() {
        global $CFG, $DB, $SITE;
        // Array with values to check if project definitions are incomplete.
        $requireddefvals = ['employer', 'description', 'tools', 'opsystems', 'langs', 'musthaves', 'nicetohaves'];

        $course = array_reverse($DB->get_records('local_satool_courses'))[0];
        $students = $DB->get_records('local_satool_students', ['courseid' => $course->id]);
        foreach ($students as $student) {
            $project = $DB->get_record('local_satool_projects', ['id' => $student->projectid]);
            $projdef = json_decode($project->definition);
            $user = $DB->get_record('user', ['id' => $student->userid]);
            $dt = new DateTime();
            $dt->setTimestamp($course->submitdate);
            $datetime = $dt->format('d.m.Y H:i');
            if (!$projdef) {
                email_to_user($user, $SITE->shortname, "Warn-Email Eingabetermin $course->name",
                    get_string('warningsubmitdatemissing', 'local_satool', $datetime));
            } else {
                $warning = 0;
                foreach ($requireddefvals as $val) {
                    if ($projdef->$val == '') {
                        $warning = 1;
                    }
                }
                if ($warning) {
                    email_to_user($user, $SITE->shortname, "Warn-Email Eingabetermin $course->name",
                        get_string('warningsubmitdateincomplete', 'local_satool', $datetime));
                }
            }
            
        }
    }

}