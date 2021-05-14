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
 * The SA-Tool project page.
 *
 * @package    local_satool
 * @copyright  2021 Jeremy Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
!isguestuser($USER->id) || print_error('noguest');

// Get params.
$id = required_param('id', PARAM_INT);

// Set Page variables.
$PAGE->set_url(new moodle_url('/local/satool/viewproj.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_satool'));
$PAGE->set_heading(get_string('title', 'local_satool'));
$PAGE->navbar->add('SA-Tool', new moodle_url('/local/satool'));

// Get database objects.
$project = $DB->get_record('local_satool_projects', ['id' => $id]);
$student = $DB->get_record('local_satool_students', ['userid' => $USER->id, 'projectid' => $id]);
$teacher = $DB->get_record('local_satool_teachers', ['userid' => $USER->id]);
$projdef = json_decode($project->definition);
$courseidstuds = $DB->get_records('local_satool_students', ['projectid' => $id]);
$courseidstud = array_shift($courseidstuds);
$course = $DB->get_record('local_satool_courses', ['id' => $courseidstud->courseid]);
$documents = $DB->get_records_select('local_satool_documents', 'status = 1 AND projectid = ?', [$id]);

// Set additional navbar element.
$PAGE->navbar->add($projdef->name);

// If statement simplifier variables.
$userassigned = $projdef->teacher != $USER->id && $projdef->student1 != $USER->id && $projdef->student2 != $USER->id &&
    !has_capability('local/satool:viewallprojects', $PAGE->context);

// Check if user is allowed to view page.
if (!$project || $projdef->status != 1 || $userassigned) {
    print_error('accessdenied', 'admin');
}

// Setup html output variable.
$html = '';

// Prepare buttons.
$buttons = html_writer::tag('a', get_string('viewdefinition', 'local_satool'),
    ['href' => new moodle_url('/local/satool/viewdef.php', ['id' => $id]), 'class' => 'btn btn-secondary mr-2 mb-2']) .
    html_writer::tag('a', get_string('uploaddocuments', 'local_satool'),
    ['href' => new moodle_url('/local/satool/uploaddocs.php', ['id' => -1, 'projectid' => $id, 'type' => 1]),
    'class' => 'btn btn-secondary mr-2 mb-2']) .
    html_writer::tag('a', get_string('uploadlinks', 'local_satool'),
    ['href' => new moodle_url('/local/satool/uploaddocs.php', ['id' => -1, 'projectid' => $id, 'type' => 0]),
    'class' => 'btn btn-secondary mr-2 mb-2']);
if ($teacher) {
    $buttons .= html_writer::tag('a', get_string('setmilestones', 'local_satool'),
        ['href' => new moodle_url('/local/satool/addmilestones.php', ['id' => $id]),
            'class' => 'btn btn-secondary mr-2 mb-2']);
}
if ($teacher && $project->submission) {
    $buttons .= html_writer::tag('a', get_string('gradeproject', 'local_satool'),
        ['href' => new moodle_url('/local/satool/gradeproj.php', ['id' => $id]), 'class' => 'btn btn-secondary mr-2 mb-2']);
} else if ($student && !$project->grade) {
    $buttons .= html_writer::tag('a', get_string('submitproject', 'local_satool'),
        ['href' => new moodle_url('/local/satool/submitproj.php', ['id' => $id]), 'class' => 'btn btn-secondary mr-2 mb-2']);
}

// Display different elements based on project status.
$mischtml = '';
if ($project->grade) {
    // Set status as complete.
    $status = get_string('statusgraded', 'local_satool');

    // Display submission html.
    $projsub = json_decode($project->submission);
    $submitid = $course->id * 1000000 + $project->id * 100 + 1;
    $fs = get_file_storage();
    $files = $fs->get_area_files(1, 'local_satool', 'document', $submitid);
    $file = array_pop($files);
    $mischtml .= html_writer::tag('h3', get_string('submission', 'local_satool'), ['class' => 'mt-4']) .
        html_writer::tag('h5',
            html_writer::tag('a', get_string('projsubfiles', 'local_satool'),
                ['href' => '/pluginfile.php/1/local_satool/document/' . $submitid . '/' . $file->get_filename()])
            );
    if ($projsub->github) {
        $mischtml .= html_writer::tag('h5', html_writer::tag('a', get_string('projsubgithub', 'local_satool'),
            ['href' => $projsub->github, 'target' => '_blank']));
    }

    // Display grade html.
    $projgrade = json_decode($project->grade);
    $rubric = json_decode($course->rubric);
    $totalgrade = 0;
    $totalallgrade = 0;
    foreach ($projgrade as $key => $grade) {
        $totalgrade += $grade * $rubric->{$key}[2];
        $totalallgrade += ($rubric->{$key}[1] - 1) * $rubric->{$key}[2];
    }
    $finalgrade = round(($totalgrade * 1.0) / ($totalallgrade * 1.0) * 5 + 1, 1);
    $gradestringvals = new stdClass();
    $gradestringvals->total = $totalgrade;
    $gradestringvals->totalall = $totalallgrade;

    // Output all elements into misc html.
    $mischtml .= html_writer::tag('h3', get_string('grade', 'local_satool'), ['class' => 'mt-4 mb-3']) .
        html_writer::tag('p', get_string('gradetotals', 'local_satool', $gradestringvals), ['class' => 'mb-1']) .
        html_writer::tag('p', get_string('gradevalue', 'local_satool') .
            html_writer::tag('b', $finalgrade));
} else if ($project->submission) {
    // Set status as submitted.
    $status = get_string('statussubmitted', 'local_satool');

    // Display submission html.
    $projsub = json_decode($project->submission);
    $submitid = $course->id * 1000000 + $project->id * 100 + 1;
    $fs = get_file_storage();
    $files = $fs->get_area_files(1, 'local_satool', 'document', $submitid);
    $file = array_pop($files);

    // Output submission html and add github element if exists.
    $mischtml .= html_writer::tag('h3', get_string('submission', 'local_satool'), ['class' => 'mt-4']) .
        html_writer::tag('h5',
            html_writer::tag('a', get_string('projsubfiles', 'local_satool'),
                ['href' => '/pluginfile.php/1/local_satool/document/' . $submitid . '/' . $file->get_filename()])
            );
    if ($projsub->github) {
        $mischtml .= html_writer::tag('h5', html_writer::tag('a', get_string('projsubgithub', 'local_satool'),
            ['href' => $projsub->github, 'target' => '_blank']));
    }
} else {
    // Set status as incomplete.
    $status = get_string('statusincomplete', 'local_satool');
}

// Add elements to output html variable.
$html .= html_writer::tag('h2', $projdef->name) .
    html_writer::tag('h4', $status, ['class' => 'mb-4']) . $buttons .
    html_writer::tag('h3', get_string('documents', 'local_satool'), ['class' => 'mt-4']);

// Display documents if any exist.
if (count($documents)) {
    $dochtml = '';
    foreach ($documents as $document) {
        $doc = '';
        $doc .= html_writer::tag('a', $document->title, ['href' => $document->path,
            'class' => 'doc-title']);
        if ($document->note) {
            $doc .= html_writer::tag('p', $document->note);
        }
        $dochtml .= html_writer::div($doc, 'mb-2');
    }
    $html .= $dochtml;
} else {
    $html .= html_writer::tag('p', get_string('nodocumentsfound', 'local_satool'));
}

// Display milestones if any exist.
$milehtml = '';
if (isset($project->milestones)) {
    $dt = new DateTime();
    $projmilestones = json_decode($project->milestones);
    $milehtml .= html_writer::tag('h3', get_string('milestones', 'local_satool'), ['class' => 'mb-4']);
    $dt->setTimestamp($projmilestones->topic1date);
    $datetime = $dt->format('d.m.Y');
    $milehtml .= html_writer::tag('h6', $projmilestones->topic1, ['class' => 'font-weight-bold']) .
        html_writer::tag('p', $datetime, ['class' => 'mb-2']);
    if ($projmilestones->topic2) {
        $dt->setTimestamp($projmilestones->topic2date);
        $datetime = $dt->format('d.m.Y');
        $milehtml .= html_writer::tag('h6', $projmilestones->topic2, ['class' => 'font-weight-bold']) .
            html_writer::tag('p', $datetime, ['class' => 'mb-2']);
    }
    if ($projmilestones->topic3) {
        $dt->setTimestamp($projmilestones->topic3date);
        $datetime = $dt->format('d.m.Y');
        $milehtml .= html_writer::tag('h6', $projmilestones->topic3, ['class' => 'font-weight-bold']) .
            html_writer::tag('p', $datetime, ['class' => 'mb-2']);
    }
    $html .= $milehtml;
}

// Output page.
$html .= $mischtml;
echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();
