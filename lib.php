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
 * SA-Tool Library
 *
 * @package    local_satool
 * @copyright  2021 Jeremy Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Insert a new course into the database
 *
 * @param stdClass $course
 */
function local_satool_create_course($course) {
    global $DB, $CFG;

    $context = context_system::instance();
    $manageroptions = array(
        'maxfiles' => 5
    );

    if (!is_object($course)) {
        $course = (object) $course;
    }

    $course->name = trim($course->name);
    $courseid = $DB->insert_record('local_satool_courses', $course);
    $course->id = $courseid;
    $course = file_postupdate_standard_filemanager($course, 'coursefiles', $manageroptions, $context,
        'local_satool', 'document', $course->id * 10000);
    $DB->update_record('local_satool_courses', $course);
    return $courseid;
}

/**
 * Update an existing course
 *
 * @param stdClass $course
 */
function local_satool_update_course($course) {
    global $DB, $CFG;

    $context = context_system::instance();

    $manageroptions = array(
        'maxfiles' => 5
    );

    if (!is_object($course)) {
        $course = (object) $course;
    }

    $course->name = trim($course->name);
    $course = file_postupdate_standard_filemanager($course, 'coursefiles', $manageroptions, $context,
        'local_satool', 'document', $course->id * 10000);
    $DB->update_record('local_satool_courses', $course);
}

/**
 * Retrieve an uploaded SA-Tool file
 * Based on other _pluginfile functions.
 *
 * @category  files
 * @param stdClass $course course object
 * @param stdClass $cm block instance record
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool
 */
function local_satool_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $DB, $CFG, $USER;

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        send_file_not_found();
    }
    require_login();

    if ($filearea !== 'document') {
        send_file_not_found();
    }

    $fs = get_file_storage();

    $filename = array_pop($args);
    $filepath = $args ? '/'.implode('/', $args).'/' : '/';

    if (!$file = $fs->get_file($context->id, 'local_satool', 'document', $args[0], '/', $filename) or $file->is_directory()) {
        send_file_not_found();
    }

    \core\session\manager::write_close();
    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 * Load the SA-Tool teacher form
 *
 * @param stdClass $course satool course object
 */
function local_satool_load_courseteacherform($course) {
    global $DB, $OUTPUT;

    $html = '';

    // Fetch loading animation.
    $loading = $OUTPUT->image_url("i/loading", "core");

    // Get teachers from database.
    $inselect = 'SELECT userid FROM {local_satool_teachers} WHERE courseid = ' . $course->id;
    $assigned = $DB->get_records_sql('SELECT * FROM {user} u WHERE u.id IN (' . $inselect . ')');
    $unassigned = $DB->get_records_sql('SELECT * FROM {user} u WHERE u.id NOT IN (' . $inselect . ')');

    // Prepare select options and optgroups with teacher data.
    $assignedselect = $unassignedselect = '';
    foreach ($assigned as $teacher) {
        $assignedselect .= html_writer::tag('option', fullname($teacher) . " (" . $teacher->username . ")",
            ['value' => $teacher->id]);
    }
    foreach ($unassigned as $teacher) {
        $unassignedselect .= html_writer::tag('option', fullname($teacher) . " (" . $teacher->username . ")",
            ['value' => $teacher->id]);
    }
    $assignedoptgroup = html_writer::tag('optgroup', $assignedselect,
        ['label' => get_string('teacherassignedcount', 'local_satool', count($assigned))]);
    $unassignedoptgroup = html_writer::tag('optgroup', $unassignedselect,
        ['label' => get_string('unassignedcount', 'local_satool', count($unassigned))]);

    // Create table cells for the selects and buttons.
    $assignedtd = html_writer::tag('td',
        html_writer::tag('p',
            html_writer::label(get_string('teacherassigned', 'local_satool'), 'teacherassignedselect', true,
                ['class' => 'font-weight-bold'])
        ) .
        html_writer::div(
            html_writer::tag('select', $assignedoptgroup, [
                'multiple' => 'multiple', 'class' => 'form-control',
                'name' => 'teacherassignedselect[]', 'id' => 'teacherassignedselect', 'size' => '20',
                'onchange' => 'enableTeacherAssigned()'])
        ) .
        html_writer::div(
            html_writer::label(get_string('search', 'local_satool'), 'teacherassignedselect_searchtext',
                true, ['class' => 'mr-1']) .
            html_writer::tag('input', '', ['type' => 'text', 'size' => '15', 'class' => 'form-control',
                'id' => 'teacherassignedselect_searchtext',
                'name' => 'teacherassignedselect_searchtext', 'oninput' => 'searchTeacherAssigned()']) .
            html_writer::tag('input', '', ['type' => 'button', 'value' => get_string('clear', 'local_satool'),
                'id' => 'teacherassignedselect_cleartext', 'name' => 'teacherassignedselect_cleartext',
                'class' => 'btn btn-secondary mx-1', 'onclick' => 'clearTeacherAssigned()']),
        'form-inline classsearch my-1'), ['id' => 'assignedcell']
    );
    $buttonstd = html_writer::tag('td',
        html_writer::div(
            html_writer::tag('input', '', [
                'id' => 'teacheradd', 'name' => 'teacheradd', 'type' => 'submit',
                'value' => get_string('add', 'local_satool'), 'class' => 'btn btn-secondary', 'disabled' => ''
                ]), '', ['id' => 'addcontrols']
        ) .
        html_writer::div(
            html_writer::tag('input', '', [
                'id' => 'teacherremove', 'name' => 'teacherremove', 'type' => 'submit',
                'value' => get_string('remove', 'local_satool'), 'class' => 'btn btn-secondary', 'disabled' => ''
                ]), '', ['id' => 'removecontrols']
        ), ['id' => 'buttonscell']
    );
    $notassignedtd = html_writer::tag('td',
        html_writer::tag('p',
            html_writer::label(get_string('unassigned', 'local_satool'), 'teacherunassignedselect', true,
                ['class' => 'font-weight-bold'])
        ) .
        html_writer::div(
            html_writer::tag('select', $unassignedoptgroup, [
                'multiple' => 'multiple', 'class' => 'form-control',
                'name' => 'teacherunassignedselect[]', 'id' => 'teacherunassignedselect', 'size' => '20',
                'onChange' => 'enableTeacherUnassigned()'])
        ) .
        html_writer::div(
            html_writer::label(get_string('search', 'local_satool'), 'teacherunassignedselect_searchtext',
                true, ['class' => 'mr-1']) .
            html_writer::tag('input', '', ['type' => 'text', 'size' => '15', 'class' => 'form-control',
                'id' => 'teacherunassignedselect_searchtext', 'name' => 'teacherunassignedselect_searchtext',
                'oninput' => 'searchTeacherUnassigned()']) .
            html_writer::tag('input', '', ['type' => 'button', 'value' => get_string('clear', 'local_satool'),
                'id' => 'teacherunassignedselect_cleartext', 'name' => 'teacherunassignedselect_cleartext',
                'class' => 'btn btn-secondary mx-1', 'onclick' => 'clearTeacherUnassigned()']),
        'form-inline classsearch my-1'), ['id' => 'unassignedcell']
    );

    // Prepare HTML for output with table and search scripts.
    $html = html_writer::tag('table',
        html_writer::tag('tbody', html_writer::tag('tr', $assignedtd . $buttonstd . $notassignedtd)),
            ['class' => 'teachertable w-100']) .
        html_writer::script('
            var timer;
            function enableTeacherAssigned() {
                $("#teacherremove").prop("disabled", false);
            }
            function enableTeacherUnassigned() {
                $("#teacheradd").prop("disabled", false);
            }
            function searchTeacherAssigned() {
                $("#teacherassignedselect").css("background", "url(' . $loading . ') center center no-repeat");
                var variables = {
                    "pagemode": 0,
                    "search": $("#teacherassignedselect_searchtext").val(),
                    "courseid": "' . $course->id . '",
                    "mode": 0
                }
                var data = JSON.stringify(variables);
                clearTimeout(timer);
                timer = setTimeout(function() {
                    $.ajax({
                        url: "ajax/search.php",
                        type: "POST",
                        data: {
                            "data": data
                        },
                        success: function(data) {
                            $("select#teacherassignedselect").html(data);
                            $("#teacherassignedselect").css("background", "");
                            $("#remove").prop("disabled", true);
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            $("#teacherassignedselect").css("background", "");
                            $("#remove").prop("disabled", true);
                        }
                    })
                }, 500);
            }
            function searchTeacherUnassigned() {
                $("#teacherunassignedselect").css("background", "url(' . $loading . ') center center no-repeat");
                var variables = {
                    "pagemode": 0,
                    "search": $("#teacherunassignedselect_searchtext").val(),
                    "courseid": "' . $course->id . '",
                    "mode": 1
                }
                var data = JSON.stringify(variables);
                clearTimeout(timer);
                timer = setTimeout(function() {
                    $.ajax({
                        url: "ajax/search.php",
                        type: "POST",
                        data: {
                            "data": data
                        },
                        success: function(data) {
                            $("select#teacherunassignedselect").html(data);
                            $("#teacherunassignedselect").css("background", "");
                            $("#add").prop("disabled", true);
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            $("#teacherunassignedselect").css("background", "");
                            $("#add").prop("disabled", true);
                        }
                    })
                }, 500);
            }
            function clearTeacherAssigned() {
                if ($("#teacherassignedselect_searchtext").val()) {
                    $("#teacherassignedselect_searchtext").val("");
                    searchTeacherAssigned();
                }
            }
            function clearTeacherUnassigned() {
                if ($("#teacherunassignedselect_searchtext").val()) {
                    $("#teacherunassignedselect_searchtext").val("");
                    searchTeacherUnassigned();
                }
            }
        ');

    return $html;
}

/**
 * Load the SA-Tool student form
 *
 * @param stdClass $course satool course object
 */
function local_satool_load_coursestudentform($course) {
    global $DB, $OUTPUT;

    $html = '';

    // Fetch loading animation.
    $loading = $OUTPUT->image_url("i/loading", "core");

    // Get students from database.
    $inselect = 'SELECT lss.userid FROM {local_satool_students} lss WHERE lss.courseid = ' . $course->id . ' AND lss.status = 1';
    $assigned = $DB->get_records_sql('SELECT * FROM {user} u WHERE u.id IN (' . $inselect . ')');
    $unassigned = $DB->get_records_sql('SELECT * FROM {user} u WHERE u.id NOT IN (' . $inselect . ')');

    // Prepare select options and optgroups with student data.
    $assignedselect = $unassignedselect = '';
    foreach ($assigned as $student) {
        $assignedselect .= html_writer::tag('option', fullname($student) . " (" . $student->username . ")",
            ['value' => $student->id]);
    }
    foreach ($unassigned as $student) {
        $unassignedselect .= html_writer::tag('option', fullname($student) . " (" . $student->username . ")",
            ['value' => $student->id]);
    }
    $assignedoptgroup = html_writer::tag('optgroup', $assignedselect,
        ['label' => get_string('studentassignedcount', 'local_satool', count($assigned))]);
    $unassignedoptgroup = html_writer::tag('optgroup', $unassignedselect,
        ['label' => get_string('unassignedcount', 'local_satool', count($unassigned))]);

    // Create table cells for the selects and buttons.
    $assignedtd = html_writer::tag('td',
        html_writer::tag('p',
            html_writer::label(get_string('studentassigned', 'local_satool'), 'studentassignedselect', true,
                ['class' => 'font-weight-bold'])
        ) .
        html_writer::div(
            html_writer::tag('select', $assignedoptgroup, [
                'multiple' => 'multiple', 'class' => 'form-control',
                'name' => 'studentassignedselect[]', 'id' => 'studentassignedselect', 'size' => '20',
                'onchange' => 'enableStudentAssigned()'])
        ) .
        html_writer::div(
            html_writer::label(get_string('search', 'local_satool'), 'studentassignedselect_searchtext',
                true, ['class' => 'mr-1']) .
            html_writer::tag('input', '', ['type' => 'text', 'size' => '15', 'class' => 'form-control',
                'id' => 'studentassignedselect_searchtext',
                'name' => 'studentassignedselect_searchtext', 'oninput' => 'searchStudentAssigned()']) .
            html_writer::tag('input', '', ['type' => 'button', 'value' => get_string('clear', 'local_satool'),
                'id' => 'studentassignedselect_cleartext', 'name' => 'studentassignedselect_cleartext',
                'class' => 'btn btn-secondary mx-1', 'onclick' => 'clearStudentAssigned()']),
        'form-inline classsearch my-1'), ['id' => 'assignedcell']
    );
    $buttonstd = html_writer::tag('td',
        html_writer::div(
            html_writer::tag('input', '', [
                'id' => 'studentadd', 'name' => 'studentadd', 'type' => 'submit',
                'value' => get_string('add', 'local_satool'), 'class' => 'btn btn-secondary', 'disabled' => ''
                ]), '', ['id' => 'addcontrols']
        ) .
        html_writer::div(
            html_writer::tag('input', '', [
                'id' => 'studentremove', 'name' => 'studentremove', 'type' => 'submit',
                'value' => get_string('remove', 'local_satool'), 'class' => 'btn btn-secondary', 'disabled' => ''
                ]), '', ['id' => 'removecontrols']
        ), ['id' => 'buttonscell']
    );
    $notassignedtd = html_writer::tag('td',
        html_writer::tag('p',
            html_writer::label(get_string('unassigned', 'local_satool'), 'studentunassignedselect', true,
                ['class' => 'font-weight-bold'])
        ) .
        html_writer::div(
            html_writer::tag('select', $unassignedoptgroup, [
                'multiple' => 'multiple', 'class' => 'form-control',
                'name' => 'studentunassignedselect[]', 'id' => 'studentunassignedselect', 'size' => '20',
                'onChange' => 'enableStudentUnassigned()'])
        ) .
        html_writer::div(
            html_writer::label(get_string('search', 'local_satool'), 'studentunassignedselect_searchtext',
                true, ['class' => 'mr-1']) .
            html_writer::tag('input', '', ['type' => 'text', 'size' => '15', 'class' => 'form-control',
                'id' => 'studentunassignedselect_searchtext', 'name' => 'studentunassignedselect_searchtext',
                'oninput' => 'searchStudentUnassigned()']) .
            html_writer::tag('input', '', ['type' => 'button', 'value' => get_string('clear', 'local_satool'),
                'id' => 'studentunassignedselect_cleartext', 'name' => 'studentunassignedselect_cleartext',
                'class' => 'btn btn-secondary mx-1', 'onclick' => 'clearStudentUnassigned()']),
        'form-inline classsearch my-1'), ['id' => 'unassignedcell']
    );

    // Prepare HTML for output with table and search scripts.
    $html = html_writer::tag('table',
        html_writer::tag('tbody', html_writer::tag('tr', $assignedtd . $buttonstd . $notassignedtd)),
            ['class' => 'studenttable w-100']) .
        html_writer::script('
            var timer;
            function enableStudentAssigned() {
                $("#studentremove").prop("disabled", false);
            }
            function enableStudentUnassigned() {
                $("#studentadd").prop("disabled", false);
            }
            function searchStudentAssigned() {
                $("#studentassignedselect").css("background", "url(' . $loading . ') center center no-repeat");
                var variables = {
                    "pagemode": 1,
                    "search": $("#studentassignedselect_searchtext").val(),
                    "courseid": "' . $course->id . '",
                    "mode": 0
                }
                var data = JSON.stringify(variables);
                clearTimeout(timer);
                timer = setTimeout(function() {
                    $.ajax({
                        url: "ajax/search.php",
                        type: "POST",
                        data: {
                            "data": data
                        },
                        success: function(data) {
                            $("select#studentassignedselect").html(data);
                            $("#studentassignedselect").css("background", "");
                            $("#remove").prop("disabled", true);
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            $("#studentassignedselect").css("background", "");
                            $("#remove").prop("disabled", true);
                        }
                    })
                }, 500);
            }
            function searchStudentUnassigned() {
                $("#studentunassignedselect").css("background", "url(' . $loading . ') center center no-repeat");
                var variables = {
                    "pagemode": 1,
                    "search": $("#studentunassignedselect_searchtext").val(),
                    "courseid": "' . $course->id . '",
                    "mode": 1
                }
                var data = JSON.stringify(variables);
                clearTimeout(timer);
                timer = setTimeout(function() {
                    $.ajax({
                        url: "ajax/search.php",
                        type: "POST",
                        data: {
                            "data": data
                        },
                        success: function(data) {
                            $("select#studentunassignedselect").html(data);
                            $("#studentunassignedselect").css("background", "");
                            $("#add").prop("disabled", true);
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            $("#studentunassignedselect").css("background", "");
                            $("#add").prop("disabled", true);
                        }
                    })
                }, 500);
            }
            function clearStudentAssigned() {
                if ($("#studentassignedselect_searchtext").val()) {
                    $("#studentassignedselect_searchtext").val("");
                    searchStudentAssigned();
                }
            }
            function clearStudentUnassigned() {
                if ($("#studentunassignedselect_searchtext").val()) {
                    $("#studentunassignedselect_searchtext").val("");
                    searchStudentUnassigned();
                }
            }
        ');

    return $html;
}