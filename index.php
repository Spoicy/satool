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

// Prepare html variables
$html = '';
$supprojectshtml = '';

// Prepare bools to show project definitions or supervised projects depending on if they exist.
$showdefs = $showproj = 0;

// Get required database objects.
$course = array_reverse($DB->get_records('local_satool_courses'))[0];
$teacher = $DB->get_record('local_satool_teachers', ['courseid' => $course->id, 'userid' => $USER->id]);
$student = $DB->get_record('local_satool_students', ['courseid' => $course->id, 'userid' => $USER->id]);

// Display different layouts depending on permissions and roles.
if ($teacher) {
    $cards = '';
    $supcards = '';
    $projdefstudents = $DB->get_records_select('local_satool_students', 'courseid = ? AND projectid IS NOT NULL',
        [$course->id]);
    $projectids = array();
    foreach ($projdefstudents as $projdefstudent) {
        if (!in_array($projdefstudent->projectid, $projectids)) {
            $projectids[] = $projdefstudent->projectid;
        }
    }
    foreach ($projectids as $id) {
        $project = $DB->get_record('local_satool_projects', ['id' => $id]);
        $projdef = json_decode($project->definition);
        if ($projdef->status != 1) {
            $showdefs = 1;
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
        } else if ($projdef->status == 1 && $projdef->teacher == $teacher->userid) {
            $showproj = 1;
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
                        ['href' => new moodle_url('/local/satool/viewproj.php', ['id' => $project->id,]),
                            'class' => 'btn btn-primary mr-2']) .
                    html_writer::tag('a', get_string('gradeproject', 'local_satool'),
                        ['href' => new moodle_url('/local/satool/gradeproj.php', ['id' => $project->id]),
                        'class' => 'btn btn-secondary']),
                    'card-body'),
                'card');
            $supcards .= $card;
        }
    }
    $projdefshtml = html_writer::div($cards, 'card-columns');
    $supprojhtml = html_writer::div($supcards, 'card-columns');
} else if (has_capability('local/satool:viewallprojects', $PAGE->context)) {
    $cards = '';
    $supcards = '';
    $projdefstudents = $DB->get_records_select('local_satool_students', 'courseid = ? AND projectid IS NOT NULL',
        [$course->id]);
    $projectids = array();
    $html .= html_writer::div(
        html_writer::tag('a', get_string('createnewcourse', 'local_satool'),
            ['href' => new moodle_url('/local/satool/editcourse.php', ['id' => -1]),
            'class' => 'btn btn-primary mb-3 mr-2 btn-lg']) .
        html_writer::tag('a', get_string('editcurrentcourse', 'local_satool'),
            ['href' => new moodle_url('/local/satool/editcourse.php', ['id' => $course->id]),
            'class' => 'btn btn-primary mb-3 btn-lg']),
        'd-flex justify-content-center');
    foreach ($projdefstudents as $projdefstudent) {
        if (!in_array($projdefstudent->projectid, $projectids)) {
            $projectids[] = $projdefstudent->projectid;
        }
    }
    foreach ($projectids as $id) {
        $project = $DB->get_record('local_satool_projects', ['id' => $id]);
        $projdef = json_decode($project->definition);
        if ($projdef->status != 1) {
            $showdefs = 1;
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
        } else if ($projdef->status == 1) {
            $showproj = 1;
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
                        ['href' => new moodle_url('/local/satool/viewproj.php', ['id' => $project->id]),
                        'class' => 'btn btn-primary mr-2']),
                    'card-body'),
                'card');
            $supcards .= $card;
        }
    }
    $projdefshtml = html_writer::div($cards, 'card-columns');
    $supprojhtml = html_writer::div($supcards, 'card-columns');
} else if ($student) {
    $cards = '';
    $supcards = '';
    $project = $DB->get_record('local_satool_projects', ['id' => $student->projectid]);
    if (!$project) {
        $html .= html_writer::div(
            html_writer::tag('a', get_string('createnewdef', 'local_satool'),
                ['href' => new moodle_url('/local/satool/editdef.php', ['id' => -1]),
                'class' => 'btn btn-primary mb-3 mr-2 btn-lg']),
            'd-flex justify-content-center');
    }
    if ($project) {
        $projdef = json_decode($project->definition);
    } else {
        $projdef = null;
    }
    if ($project && $projdef->status != 1) {
        $showdefs = 1;
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
    } else if ($project && $projdef->status == 1) {
        $showproj = 1;
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
                    ['href' => new moodle_url('/local/satool/viewproj.php', ['id' => $project->id]),
                    'class' => 'btn btn-primary mr-2']),
                'card-body'),
            'card');
        $supcards .= $card;
    }
    $projdefshtml = html_writer::div($cards, 'card-columns');
    $supprojhtml = html_writer::div($supcards, 'card-columns');
} else {
    print_error('accessdenied', 'admin');
}

// Setup html to output.
if ($student) {
    if ($showdefs) {
        $html .= html_writer::tag('h2', get_string('projectdefinition', 'local_satool'), ['class' => 'mb-4']) .
            $projdefshtml;
    }
} else {
    $html .= html_writer::tag('h2', get_string('projectdefinitions', 'local_satool'), ['class' => 'mb-4']) .
        $projdefshtml;
}
if ($student) {
    if ($showproj) {
        $html .= html_writer::tag('h2', get_string('supervisedproject', 'local_satool'), ['class' => 'mb-4']) .
            $supprojhtml;
    }
} else {
    $html .= html_writer::tag('h2', get_string('supervisedprojects', 'local_satool'), ['class' => 'mb-4']) .
        $supprojhtml;
}

// Output the page.
echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();