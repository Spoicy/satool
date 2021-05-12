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
 * The SA-Tool submit project page.
 *
 * @package    local_satool
 * @copyright  2021 Jeremy Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/local/satool/classes/submitproj_form.php');
require_once($CFG->dirroot.'/local/satool/lib.php');
require_once($CFG->libdir.'/filelib.php');

// Check for capabilities and if user is logged in.
require_login();
!isguestuser($USER->id) || print_error('noguest');

// Get params.
$id = required_param('id', PARAM_INT);

// Get database objects.
$courses = $DB->get_records('local_satool_courses');
$course = array_pop($courses);
$project = $DB->get_record('local_satool_projects', ['id' => $id]);
if (!$project) {
    print_error('accessdenied', 'admin');
}
$projdef = json_decode($project->definition);
$student1 = $DB->get_record('local_satool_students', ['courseid' => $course->id, 'userid' => $projdef->student1]);
$student2 = $DB->get_record('local_satool_students', ['courseid' => $course->id, 'userid' => $projdef->student2]);
if (!$student1) {
    print_error('accessdenied', 'admin');
}


// Check if user is owner of the project.
if ($student1->userid != $USER->id && $student2->userid != $USER->id) {
    print_error('accessdenied', 'admin');
}

// Set Page variables.
$PAGE->set_url(new moodle_url('/local/satool/submitproj.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_satool'));
$PAGE->set_heading(get_string('title', 'local_satool'));
$PAGE->navbar->add('SA-Tool', new moodle_url('/local/satool'));
$PAGE->navbar->add($projdef->name, new moodle_url('/local/satool/viewproj.php', ['id' => $id]));
$PAGE->navbar->add(get_string('submit', 'local_satool'));

// Set additional values.
$returnurl = new moodle_url('/local/satool/viewproj.php', ['id' => $id]);
$manageroptions = array(
    'maxfiles' => 1,
    'accepted_types' => array('.zip')
);
$html = '';

// Check for existing submission.
if (isset($project->submission)) {
    $projsub = json_decode($project->submission);
    $projsub = file_prepare_standard_filemanager($projsub, 'projsubfiles', $manageroptions, $PAGE->context,
        'local_satool', 'document', $course->id * 1000000 + $project->id * 100 + 1);
}

$projsubform = new local_satool_submitproj_form(new moodle_url($PAGE->url), array('project' => $project));

// Deal with form submission.
if ($projsubform->is_cancelled()) {
    redirect($returnurl);
} else if ($projsubnew = $projsubform->get_data()) {
    unset($projsubnew->id);
    unset($projsubnew->submitbutton);
    $projectsave = file_save_draft_area_files($projsubnew->projsubfiles_filemanager, $PAGE->context->id,
        'local_satool', 'document', $course->id * 1000000 + $project->id * 100 + 1,
        array('maxbytes' => $CFG->maxbytes, 'maxfiles' => 1,
        'accepted_types' => array('.zip')
    ));
    local_satool_submit_projsub($projsubnew, $course->id, $project);
    redirect($returnurl);
}

// Output page.
echo $OUTPUT->header();
$projsubform->display();
echo $OUTPUT->footer();