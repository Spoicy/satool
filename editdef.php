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
 * The SA-Tool edit project definition page.
 *
 * @package    local_satool
 * @copyright  2021 Jeremy Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/local/satool/classes/editdef_form.php');
require_once($CFG->dirroot.'/local/satool/lib.php');
require_once($CFG->libdir.'/filelib.php');

// Check for capabilities and if user is logged in.
require_login();
!isguestuser($USER->id) || print_error('noguest');

// Get params.
$id = optional_param('id', -1, PARAM_INT);

// Get database objects.
$course = array_reverse($DB->get_records('local_satool_courses'))[0];
// Check for existing project.
if ($id == -1) {
    $project = new stdClass();
    $project->id = -1;
    $project->status = 0;
    $student1 = $DB->get_record('local_satool_students', ['courseid' => $course->id, 'userid' => $USER->id]);
} else {
    $project = $DB->get_record('local_satool_projects', ['id' => $id]);
    $projdef = json_decode($project->definition);
    $student1 = $DB->get_record('local_satool_students', ['courseid' => $course->id, 'userid' => $projdef->student1]);
}

// Check if user is allowed to view page.
if (!$student1) {
    print_error('accessdenied', 'admin');
}


// Check if user is owner of the project.
if ($id != -1 && $student1->userid != $USER->id) {
    print_error('accessdenied', 'admin');
}

// Set Page variables.
$PAGE->set_url(new moodle_url('/local/satool/editdef.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_satool'));
$PAGE->set_heading(get_string('title', 'local_satool'));

// Set additional values.
$returnurl = new moodle_url('/local/satool');
$manageroptions = array(
    'maxfiles' => 1,
    'accepted_types' => array('.svg', '.jpg', '.png')
);
$html = '';

// Check for existing project.
if ($id == -1) {
    $project = new stdClass();
    $project->id = -1;
    $project->status = 0;
} else {
    $project = $DB->get_record('local_satool_projects', ['id' => $id]);
    $projdef = json_decode($project->definition);
}

// Prepare Filemanager if project exists.
if ($project->id !== -1) {
    $projdef = file_prepare_standard_filemanager($projdef, 'projsketch', $manageroptions, $PAGE->context,
        'local_satool', 'document', $course->id * 1000000 + $project->id * 100);
}
$projectform = new local_satool_editdef_form(new moodle_url($PAGE->url), array('project' => $project));

// Deal with form submission.
if ($projectform->is_cancelled()) {
    redirect($returnurl);
} else if ($projdefnew = $projectform->get_data()) {
    // Check if project is new and save accordingly if it is.
    if ($id == -1) {
        unset($projdefnew->id);
        unset($projdefnew->submitbutton);
        $projectnew = local_satool_create_projdef($projdefnew, $course->id);
        $projdefnew = json_decode($projectnew->definition);
        $projectsave = file_save_draft_area_files($projdefnew->projsketch_filemanager, $PAGE->context->id,
            'local_satool', 'document', $course->id * 1000000 + $projectnew->id * 100,
            array('maxbytes' => $CFG->maxbytes, 'maxfiles' => 1,
            'accepted_types' => array('.svg', '.jpg', '.png')
        ));
        local_satool_update_projdef($projdefnew, $course->id, $projectnew);
    } else {
        $projectnew = $DB->get_record('local_satool_projects', ['id' => $projdefnew->id]);
        unset($projdefnew->id);
        unset($projdefnew->submitbutton);
        $projectsave = file_save_draft_area_files($projdefnew->projsketch_filemanager, $PAGE->context->id,
            'local_satool', 'document', $course->id * 1000000 + $projectnew->id * 100,
            array('maxbytes' => $CFG->maxbytes, 'maxfiles' => 1,
            'accepted_types' => array('.svg', '.jpg', '.png')
        ));
        local_satool_update_projdef($projdefnew, $course->id, $projectnew);
    }
    redirect($returnurl);
}

// Output page.
echo $OUTPUT->header();
$projectform->display();
echo $OUTPUT->footer();