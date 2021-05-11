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

// Get database objects.
$project = $DB->get_record('local_satool_projects', ['id' => $id]);
$student = $DB->get_record('local_satool_students', ['userid' => $USER->id, 'projectid' => $id]);
$teacher = $DB->get_record('local_satool_teachers', ['userid' => $USER->id]);
$projdef = json_decode($project->definition);
$courseidstud = $DB->get_record('local_satool_students', ['userid' => $projdef->student1]);
$course = $DB->get_record('local_satool_courses', ['id' => $courseidstud->courseid]);
$documents = $DB->get_records_select('local_satool_documents', 'status = 1 AND projectid = ?', [$id]);

// If statement simplifier variables.
$userassigned = !$teacher && !$student && !has_capability('local/satool:viewallprojects', $PAGE->context);

if (!$project || $projdef->status != 1 || $userassigned) {
    print_error('accessdenied', 'admin');
}

$html = '';
$buttons = html_writer::tag('a', get_string('viewdefinition', 'local_satool'),
    ['href' => new moodle_url('/local/satool/viewdef.php', ['id' => $id]), 'class' => 'btn btn-secondary mr-2']) .
    html_writer::tag('a', get_string('uploaddocuments', 'local_satool'),
    ['href' => new moodle_url('/local/satool/uploaddocs.php', ['id' => -1, 'projectid' => $id, 'type' => 1]),
    'class' => 'btn btn-secondary mr-2']) .
    html_writer::tag('a', get_string('uploadlinks', 'local_satool'),
    ['href' => new moodle_url('/local/satool/uploaddocs.php', ['id' => -1, 'projectid' => $id, 'type' => 0]),
    'class' => 'btn btn-secondary mr-2']);
if ($teacher) {
    $buttons .= html_writer::tag('a', get_string('gradeproject', 'local_satool'),
        ['href' => new moodle_url('/local/satool/gradeproj.php', ['id' => $id]), 'class' => 'btn btn-secondary mr-2']);
} else if ($student && !$project->grade) {
    $buttons .= html_writer::tag('a', get_string('submitproject', 'local_satool'),
        ['href' => new moodle_url('/local/satool/submitproj.php', ['id' => $id]), 'class' => 'btn btn-secondary mr-2']);
}

$mischtml = '';
if ($project->grade) {
    $status = get_string('statusgraded', 'local_satool');

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

    $projgrade = json_decode($project->grade);
    $rubric = json_decode($course->rubric);
    $totalgrade = 0;
    $totalallgrade = 0;
    foreach ($projgrade as $key => $grade) {
        $totalgrade += $grade * $rubric->$key[2];
        $totalallgrade += ($rubric->$key[1] - 1) * $rubric->$key[2];
    }
    $finalgrade = round(($totalgrade * 1.0) / ($totalallgrade * 1.0) * 5 + 1, 1);
    $gradestringvals = new stdClass();
    $gradestringvals->total = $totalgrade;
    $gradestringvals->totalall = $totalallgrade;
    $mischtml .= html_writer::tag('h3', get_string('grade', 'local_satool'), ['class' => 'mt-4 mb-3']) .
        html_writer::tag('p', get_string('gradetotals', 'local_satool', $gradestringvals), ['class' => 'mb-1']) .
        html_writer::tag('p', get_string('gradevalue', 'local_satool') .
            html_writer::tag('b', $finalgrade));
} else if ($project->submission) {
    $status = get_string('statussubmitted', 'local_satool');

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
} else {
    $status = get_string('statusincomplete', 'local_satool');
}

$html .= html_writer::tag('h2', $projdef->name) .
    html_writer::tag('h4', $status, ['class' => 'mb-4']) . $buttons .
    html_writer::tag('h3', get_string('documents', 'local_satool'), ['class' => 'mt-5']);

if (count($documents)) {
    $dochtml = '';
    foreach($documents as $document) {
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

$html .= $mischtml;

echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();
