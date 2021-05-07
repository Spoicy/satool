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
 * The SA-Tool mainpage.
 *
 * @package    local_satool
 * @copyright  2021 Jeremy Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
!isguestuser($USER->id) || print_error('noguest');

// Set Page variables.
$PAGE->set_url(new moodle_url('/local/satool/'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_satool'));
$PAGE->set_heading(get_string('title', 'local_satool'));

$html = '';
$supprojectshtml = '';

$course = array_reverse($DB->get_records('local_satool_courses'))[0];
$teacher = $DB->get_record('local_satool_teachers', ['courseid' => $course->id, 'userid' => $USER->id]);
$student = $DB->get_record('local_satool_students', ['courseid' => $course->id, 'userid' => $USER->id]);
if ($teacher) {
    $cards = '';
    $projdefstudents = $DB->get_records_select('local_satool_students', 'courseid = ? AND projectid IS NOT NULL',
        [$course->id]);
    $projectids = array();
    foreach ($projdefstudents as $student) {
        if (!in_array($student->projectid, $projectids)) {
            $projectids[] = $student->projectid;
        }
    }
    foreach ($projectids as $id) {
        $project = $DB->get_record('local_satool_projects', ['id' => $id]);
        $projdef = json_decode($project->definition);
        if ($projdef->status != -1) {
            $student1 = $DB->get_record('user', ['id' => $projdef->student1]);
            if ($projdef->student2 != 0) {
                $student2 = $DB->get_record('user', ['id' => $projdef->student2]);
                $names = fullname($student1) . ', ' . fullname($student2);
            } else {
                $names = fullname($student1);
            }
            $card = html_writer::div(
                html_writer::tag('h5', $names, ['class' => 'card-header']) .
                html_writer::div(
                    html_writer::tag('h4', $projdef->name) .
                    html_writer::tag('a', get_string('viewproject', 'local_satool'),
                        ['href' => new moodle_url('/local/satool/viewdef.php', ['id' => $project->id,]),
                            'class' => 'btn btn-primary mr-2']) .
                    html_writer::tag('a', get_string('superviseproject', 'local_satool'),
                        ['href' => new moodle_url('/local/satool/viewdef.php', ['id' => $project->id, 'supervise' => 1]),
                        'class' => 'btn btn-secondary']),
                    'card-body'),
                'card');
            $cards .= $card;
        }
    }
    $projdefshtml = html_writer::div($cards, 'card-columns');
} else if (has_capability('local/satool:viewallprojects', $PAGE->context)) {
    $cards = '';
    $projdefstudents = $DB->get_records_select('local_satool_students', 'courseid = ? AND projectid IS NOT NULL',
        [$course->id]);
    $projectids = array();
    foreach ($projdefstudents as $student) {
        if (!in_array($student->projectid, $projectids)) {
            $projectids[] = $student->projectid;
        }
    }
    foreach ($projectids as $id) {
        $project = $DB->get_record('local_satool_projects', ['id' => $id]);
        $projdef = json_decode($project->definition);
        if ($projdef->status != 1) {
            $student1 = $DB->get_record('user', ['id' => $projdef->student1]);
            if ($projdef->student2 != 0) {
                $student2 = $DB->get_record('user', ['id' => $projdef->student2]);
                $names = fullname($student1) . ', ' . fullname($student2);
            } else {
                $names = fullname($student1);
            }
            $card = html_writer::div(
                html_writer::tag('h5', $names, ['class' => 'card-header']) .
                html_writer::div(
                    html_writer::tag('h4', $projdef->name) .
                    html_writer::tag('a', get_string('viewproject', 'local_satool'),
                        ['href' => new moodle_url('/local/satool/viewdef.php', ['id' => $project->id]),
                        'class' => 'btn btn-primary mr-2']),
                    'card-body'),
                'card');
            $cards .= $card;
        }
    }
    $projdefshtml = html_writer::div($cards, 'card-columns');
} else if ($student) {
    $cards = '';
    $project = $DB->get_record('local_satool_projects', ['id' => $student->projectid]);
    $projdef = json_decode($project->definition);
        if ($projdef->status != 1) {
            $student1 = $DB->get_record('user', ['id' => $projdef->student1]);
            if ($projdef->student2 != 0) {
                $student2 = $DB->get_record('user', ['id' => $projdef->student2]);
                $names = fullname($student1) . ', ' . fullname($student2);
            } else {
                $names = fullname($student1);
            }
            $card = html_writer::div(
                html_writer::tag('h5', $names, ['class' => 'card-header']) .
                html_writer::div(
                    html_writer::tag('h4', $projdef->name) .
                    html_writer::tag('a', get_string('viewproject', 'local_satool'),
                        ['href' => new moodle_url('/local/satool/viewdef.php', ['id' => $project->id]),
                        'class' => 'btn btn-primary mr-2']) .
                    html_writer::tag('a', get_string('editproject', 'local_satool'),
                        ['href' => new moodle_url('/local/satool/editdef.php', ['id' => $project->id]),
                        'class' => 'btn btn-secondary']),
                    'card-body'),
                'card');
            $cards .= $card;
        }
    $projdefshtml = html_writer::div($cards, 'card-columns');
} else {
    print_error('accessdenied', 'admin');
}

$html .= html_writer::tag('h2', get_string('projectdefinitions', 'local_satool'), ['class' => 'mb-4']);
$html .= $projdefshtml;
$html .= html_writer::tag('h2', get_string('supervisedprojects', 'local_satool'));

// Output the page.
echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();