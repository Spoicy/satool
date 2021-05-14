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
 * The SA-Tool create milestones page.
 *
 * @package    local_satool
 * @copyright  2021 Jeremy Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/local/satool/classes/addmilestones_form.php');
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
$teacher = $DB->get_record('local_satool_teachers', ['courseid' => $course->id, 'userid' => $projdef->teacher]);
if (!$teacher || $teacher->userid != $USER->id) {
    print_error('accessdenied', 'admin');
}

// Set Page variables.
$PAGE->set_url(new moodle_url('/local/satool/addmilestones.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_satool'));
$PAGE->set_heading(get_string('title', 'local_satool'));
$PAGE->navbar->add('SA-Tool', new moodle_url('/local/satool'));
$PAGE->navbar->add($projdef->name, new moodle_url('/local/satool/viewproj.php', ['id' => $id]));
$PAGE->navbar->add(get_string('addmilestones', 'local_satool'));

// Set additional values.
$returnurl = new moodle_url('/local/satool/viewproj.php', ['id' => $id]);
$html = '';

$addmilestonesform = new local_satool_addmilestones_form(new moodle_url($PAGE->url), array('project' => $project));

// Deal with form submission.
if ($addmilestonesform->is_cancelled()) {
    redirect($returnurl);
} else if ($addmilestonesnew = $addmilestonesform->get_data()) {
    unset($addmilestonesnew->id);
    unset($addmilestonesnew->submitbutton);
    local_satool_add_milestones($addmilestonesnew, $course->id, $project);
    redirect($returnurl);
}

// Output page.
echo $OUTPUT->header();
$addmilestonesform->display();
echo $OUTPUT->footer();