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
 * Teacher and student search in edit course view
 *
 * @copyright 2021 Jeremy Funke
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_satool
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/satool/ajax/search.php');

echo $OUTPUT->header();

// Check access.
require_login();
require_capability('local/satool:editcourse', context_system::instance());

// Get the search parameter.
$data = json_decode(required_param('data', PARAM_RAW));

if ($data->pagemode == 0) {
    $inselect = 'SELECT userid FROM {local_satool_teachers} WHERE courseid = ' . $data->courseid;
    $assignedsql = 'SELECT * FROM {user} u WHERE u.id IN (' . $inselect . ')';
    $unassignedsql = 'SELECT * FROM {user} u WHERE u.id NOT IN (' . $inselect . ')';
    // Display the select differently depending on if search is empty or not.
    if ($data->search != "") {
        // Differentiate between both selects.
        if ($data->mode == 0) {
            $assigned = $DB->get_records_sql($assignedsql . ' AND (CONCAT(u.firstname, " ", u.lastname)
                 LIKE ? OR u.username LIKE ?) ORDER BY u.firstname', ["%$data->search%", "%$data->search%"]);
            $assignedselect = '';
            $assignedstringvars = new stdClass();
            $assignedstringvars->search = $data->search;
            $assignedstringvars->count = count($assigned);
            foreach ($assigned as $teacher) {
                $assignedselect .= html_writer::tag('option', $teacher->firstname . " " .
                    $teacher->lastname . " (" . $teacher->username . ")", ['value' => $teacher->id]);
                
            }
            $assignedoptgroup = html_writer::tag('optgroup', $assignedselect,
                ['label' => get_string('teacherassignedcountmatching', 'local_satool', $assignedstringvars)]);
            $output = $assignedoptgroup;
        } else {
            $unassigned = $DB->get_records_sql($unassignedsql . ' AND (CONCAT(u.firstname, " ", u.lastname)
                LIKE ? OR u.username LIKE ?) ORDER BY u.firstname', ["%$data->search%", "%$data->search%"]);
            $unassignedselect = '';
            $unassignedstringvars = new stdClass();
            $unassignedstringvars->search = $data->search;
            $unassignedstringvars->count = count($unassigned);
            foreach ($unassigned as $teacher) {
                $unassignedselect .= html_writer::tag('option', $teacher->firstname . " " .
                    $teacher->lastname . " (" . $teacher->username . ")", ['value' => $teacher->id]);
            }
            $unassignedoptgroup = html_writer::tag('optgroup', $unassignedselect,
                ['label' => get_string('unassignedcountmatching', 'local_satool', $unassignedstringvars)]);
            $output = $unassignedoptgroup;
        }
    } else {
        // Differentiate between both selects.
        if ($data->mode == 0) {
            $assigned = $DB->get_records_sql($assignedsql . ' ORDER BY u.firstname');
            $assignedselect = '';
            foreach ($assigned as $teacher) {
                $assignedselect .= html_writer::tag('option', $teacher->firstname . " " .
                    $teacher->lastname . " (" . $teacher->username . ")", ['value' => $teacher->id]);
            }
            $assignedoptgroup = html_writer::tag('optgroup', $assignedselect,
                ['label' => get_string('teacherassignedcount', 'local_satool', count($assigned))]);
            $output = $assignedoptgroup;
        } else {
            $unassigned = $DB->get_records_sql($unassignedsql . ' ORDER BY u.firstname');
            $unassignedselect = '';
            foreach ($unassigned as $teacher) {
                $unassignedselect .= html_writer::tag('option', $teacher->firstname . " " .
                    $teacher->lastname . " (" . $teacher->username . ")", ['value' => $teacher->id]);
            }
            $unassignedoptgroup = html_writer::tag('optgroup', $unassignedselect,
                ['label' => get_string('unassignedcount', 'local_satool', count($unassigned))]);
            $output = $unassignedoptgroup;
        }
    }
    echo json_encode($output);
} else {
    $inselect = 'SELECT lss.userid FROM {local_satool_students} lss WHERE lss.courseid = ' . $data->courseid . ' AND lss.status = 1';
    $assignedsql = 'SELECT * FROM {user} u WHERE u.id IN (' . $inselect . ')';
    $unassignedsql = 'SELECT * FROM {user} u WHERE u.id NOT IN (' . $inselect . ')';
    // Display the select differently depending on if search is empty or not.
    if ($data->search != "") {
        // Differentiate between both selects.
        if ($data->mode == 0) {
            $assigned = $DB->get_records_sql($assignedsql . ' AND (CONCAT(u.firstname, " ", u.lastname)
                 LIKE ? OR u.username LIKE ?) ORDER BY u.firstname', ["%$data->search%", "%$data->search%"]);
            $assignedselect = '';
            $assignedstringvars = new stdClass();
            $assignedstringvars->search = $data->search;
            $assignedstringvars->count = count($assigned);
            foreach ($assigned as $student) {
                $assignedselect .= html_writer::tag('option', $student->firstname . " " .
                    $student->lastname . " (" . $student->username . ")", ['value' => $student->id]);
                
            }
            $assignedoptgroup = html_writer::tag('optgroup', $assignedselect,
                ['label' => get_string('studentassignedcountmatching', 'local_satool', $assignedstringvars)]);
            $output = $assignedoptgroup;
        } else {
            $unassigned = $DB->get_records_sql($unassignedsql . ' AND (CONCAT(u.firstname, " ", u.lastname)
                LIKE ? OR u.username LIKE ?) ORDER BY u.firstname', ["%$data->search%", "%$data->search%"]);
            $unassignedselect = '';
            $unassignedstringvars = new stdClass();
            $unassignedstringvars->search = $data->search;
            $unassignedstringvars->count = count($unassigned);
            foreach ($unassigned as $student) {
                $unassignedselect .= html_writer::tag('option', $student->firstname . " " .
                    $student->lastname . " (" . $student->username . ")", ['value' => $student->id]);
            }
            $unassignedoptgroup = html_writer::tag('optgroup', $unassignedselect,
                ['label' => get_string('unassignedcountmatching', 'local_satool', $unassignedstringvars)]);
            $output = $unassignedoptgroup;
        }
    } else {
        // Differentiate between both selects.
        if ($data->mode == 0) {
            $assigned = $DB->get_records_sql($assignedsql . ' ORDER BY u.firstname');
            $assignedselect = '';
            foreach ($assigned as $student) {
                $assignedselect .= html_writer::tag('option', $student->firstname . " " .
                    $student->lastname . " (" . $student->username . ")", ['value' => $student->id]);
            }
            $assignedoptgroup = html_writer::tag('optgroup', $assignedselect,
                ['label' => get_string('studentassignedcount', 'local_satool', count($assigned))]);
            $output = $assignedoptgroup;
        } else {
            $unassigned = $DB->get_records_sql($unassignedsql . ' ORDER BY u.firstname');
            $unassignedselect = '';
            foreach ($unassigned as $student) {
                $unassignedselect .= html_writer::tag('option', $student->firstname . " " .
                    $student->lastname . " (" . $student->username . ")", ['value' => $student->id]);
            }
            $unassignedoptgroup = html_writer::tag('optgroup', $unassignedselect,
                ['label' => get_string('unassignedcount', 'local_satool', count($unassigned))]);
            $output = $unassignedoptgroup;
        }
    }
    echo json_encode($output);
}