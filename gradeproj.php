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
$submit = optional_param('submitgrade', 0, PARAM_INT);

// Set Page variables.
$PAGE->set_url(new moodle_url('/local/satool/gradeproj.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_satool'));
$PAGE->set_heading(get_string('title', 'local_satool'));

// Get database objects.
$project = $DB->get_record('local_satool_projects', ['id' => $id]);
$projdef = json_decode($project->definition);
if ($projdef->teacher != $USER->id) {
    print_error('accessdenied', 'admin');
}
$teacher = $DB->get_record('local_satool_teachers', ['userid' => $projdef->teacher]);
$course = $DB->get_record('local_satool_courses', ['id' => $teacher->courseid]);
$rubric = json_decode($course->rubric);

// Update project with grade if rubric is submitted.
if ($submit) {
    $grade = [];
    foreach ($rubric as $key => $criteria) {
        $grade[$key] = required_param($key, PARAM_INT);
    }
    $project->grade = json_encode($grade);
    $DB->update_record('local_satool_projects', $project);
    redirect(new moodle_url('/local/satool/viewproj.php', ['id' => $id]));
}

// Setup html_table for the rubric.
$table = new html_table();
$table->attributes['class'] = 'generaltable projgradetable';
$i = 0;
// Go through each criteria in the rubric.
foreach ($rubric as $key => $criteria) {
    $row = array();
    $row[] = html_writer::tag('b', $criteria[0]);
    // Check if criteria is in set of 4 or 2. These are preset as custom rubrics have yet to be implemented.
    if ($criteria[1] == 4) {
        $row[] = html_writer::start_tag('input', ['type' => 'radio', 'id' => 'ung' . $i,
                'name' => $key, 'value' => 0, 'required' => '']) .
            html_writer::label('ungenügend', 'ung' . $i) . '<br>' .
            html_writer::span(0 * $criteria[2] . ' Punkte', 'font-italic');
        $row[] = html_writer::start_tag('input', ['type' => 'radio', 'id' => 'g' . $i, 'name' => $key, 'value' => 1]) .
            html_writer::label('genügend', 'g' . $i) . '<br>' .
            html_writer::span(1 * $criteria[2] . ' Punkte', 'font-italic');
        $row[] = html_writer::start_tag('input', ['type' => 'radio', 'id' => 'gut' . $i, 'name' => $key, 'value' => 2]) .
            html_writer::label('gut', 'gut' . $i) . '<br>' .
            html_writer::span(2 * $criteria[2] . ' Punkte', 'font-italic');
        $row[] = html_writer::start_tag('input', ['type' => 'radio', 'id' => 'sgut' . $i, 'name' => $key, 'value' => 3]) .
            html_writer::label('sehr gut', 'sgut' . $i) . '<br>' .
            html_writer::span(3 * $criteria[2] . ' Punkte', 'font-italic');
    } else if ($criteria[1] == 2) {
        $cell = new html_table_cell(html_writer::start_tag('input', ['type' => 'radio', 'id' => 'nein' . $i,
            'name' => $key, 'value' => 0, 'required' => '']) .
            html_writer::label('nein', 'nein' . $i) . '<br>' .
            html_writer::span(0 * $criteria[2] . ' Punkte', 'font-italic'));
        $cell->colspan = 2;
        $row[] = $cell;
        $cell = new html_table_cell(html_writer::start_tag('input', ['type' => 'radio', 'id' => 'ja' . $i,
            'name' => $key, 'value' => 1]) .
            html_writer::label('ja', 'ja' . $i) . '<br>' .
            html_writer::span(1 * $criteria[2] . ' Punkte', 'font-italic'));
        $cell->colspan = 2;
        $row[] = $cell;
    }
    // Add row to table.
    $table->data[] = $row;
    $i++;
}

// Prepare buttons.
$buttons = html_writer::tag('a', get_string('goback', 'local_satool'),
    ['href' => new moodle_url('/local/satool/viewproj.php', ['id' => $id]), 'class' => 'btn btn-secondary float-right ml-2']) .
    html_writer::tag('button', get_string('submitgrade', 'local_satool'),
        ['name' => 'submitgrade', 'id' => 'submitgrade', 'type' => 'submit',
        'class' => 'btn btn-primary float-right', 'value' => 1]);

// Create form with rubric table and buttons.
$form = html_writer::tag('form', html_writer::table($table) . '<br>' . $buttons,
    ['method' => 'post', 'action' => '#', 'name' => 'gradeForm']);

// Output page.
echo $OUTPUT->header();
echo $form;
echo $OUTPUT->footer();