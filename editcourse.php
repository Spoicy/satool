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
 * The SA-Tool edit course page.
 *
 * @package    local_satool
 * @copyright  2021 Jeremy Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/local/satool/classes/editcourse_form.php');
require_once($CFG->dirroot.'/local/satool/lib.php');
require_once($CFG->libdir.'/filelib.php');

// Check for capabilities and if user is logged in.
require_login();
!isguestuser($USER->id) || print_error('noguest');
require_capability('local/satool:editcourse', context_system::instance());

// Get params.
$id = optional_param('id', -1, PARAM_INT);
$unassignedteacherids = optional_param_array('teacherunassignedselect', [], PARAM_INT);
$assignedteacherids = optional_param_array('teacherassignedselect', [], PARAM_INT);
$unassignedstudentids = optional_param_array('studentunassignedselect', [], PARAM_INT);
$assignedstudentids = optional_param_array('studentassignedselect', [], PARAM_INT);

// Set Page variables.
$PAGE->set_url(new moodle_url('/local/satool/editcourse.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_satool'));
$PAGE->set_heading(get_string('title', 'local_satool'));

// Set additional values.
$returnurl = new moodle_url('/local/satool');
$manageroptions = array(
    'maxfiles' => 2,
    'accepted_types' => array('.mp4', '.mov', '.jpg', '.png')
);

$html = '';

// Check for existing course.
if ($id == -1) {
    $course = new stdClass();
    $course->id = -1;
} else {
    $course = $DB->get_record('local_satool_courses', ['id' => $id]);
    if (!$course) {
        print_error('accessdenied', 'admin');
    }
}

// Add teacher to course.
if (optional_param('teacheradd', false, PARAM_BOOL) && count($unassignedteacherids) && confirm_sesskey()) {
    foreach ($unassignedteacherids as $teacherid) {
        $teacher = new stdClass();
        $teacher->courseid = $course->id;
        $teacher->userid = $teacherid;
        $DB->insert_record('local_satool_teachers', $teacher);
    }
}

// Remove teacher from course.
if (optional_param('teacherremove', false, PARAM_BOOL) && count($assignedteacherids) && confirm_sesskey()) {
    foreach ($assignedteacherids as $teacherid) {
        $DB->delete_records('local_satool_teachers', ['userid' => $teacherid]);
    }
}

// Add student to course.
if (optional_param('studentadd', false, PARAM_BOOL) && count($unassignedstudentids) && confirm_sesskey()) {
    foreach ($unassignedstudentids as $studentid) {
        $student = $DB->get_record('local_satool_students',
            ['courseid' => $course->id, 'userid' => $studentid]);
        if ($student) {
            $student->status = 1;
            $DB->update_record('local_satool_students', $student);
        } else {
            $student = new stdClass();
            $student->courseid = $course->id;
            $student->userid = $studentid;
            $student->status = 1;
            $DB->insert_record('local_satool_students', $student);
        }
    }
}

// Remove student from course.
if (optional_param('studentremove', false, PARAM_BOOL) && count($assignedstudentids) && confirm_sesskey()) {
    foreach ($assignedstudentids as $studentid) {
        $student = $DB->get_record('local_satool_students',
            ['courseid' => $course->id, 'userid' => $studentid]);
        $student->status = 0;
        $DB->update_record('local_satool_students', $student);
    }
}

// Prepare Filemanager if course exists.
if ($course->id !== -1) {
    $course = file_prepare_standard_filemanager($course, 'coursefiles', $manageroptions, $PAGE->context,
        'local_satool', 'document', $course->id * 10000);
}
$courseform = new local_satool_editcourse_form(new moodle_url($PAGE->url), array('course' => $course));

// Deal with form submission.
if ($courseform->is_cancelled()) {
    redirect($returnurl);
} else if ($coursenew = $courseform->get_data()) {
    $coursecreated = false;
    $coursenew->id = $id;
    if ($coursenew->id == -1) {
        unset($coursenew->id);
        $coursenewid = local_satool_create_course($coursenew);
        $coursenew->id = $coursenewid;
        $coursesave = file_save_draft_area_files($coursenew->coursefiles_filemanager, $PAGE->context->id,
            'local_satool', 'document', $coursenew->id * 10000,
            array('maxbytes' => $CFG->maxbytes, 'maxfiles' => 5));
        local_satool_update_course($coursenew);
    } else {
        $testsave = file_save_draft_area_files($coursenew->coursefiles_filemanager, $PAGE->context->id,
            'local_satool', 'document', $coursenew->id * 10000,
            array('maxbytes' => $CFG->maxbytes, 'maxfiles' => 5));
        local_satool_update_course($coursenew);
    }
    redirect($returnurl);
}

// Load both course forms if class exists in database.
if ($course->id != -1) {
    $teacherform = local_satool_load_courseteacherform($course);
    $studentform = local_satool_load_coursestudentform($course);
    $formtext = $teacherform . '<br>' . $studentform;
} else {
    $formtext = html_writer::tag('p', get_string('createcoursetounlock', 'local_satool'));
}

$html = '<br>' . html_writer::tag('form', $formtext,
    ['action' => new moodle_url($PAGE->url, ['sesskey' => sesskey()]), 'method' => 'post', 'class' => 'courseform']);

// Output the page.
echo $OUTPUT->header();
$courseform->display();
echo $html;
echo $OUTPUT->footer();