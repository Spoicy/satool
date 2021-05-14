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
    // Setup filemanager options.
    $manageroptions = array(
        'maxfiles' => 5
    );
    $dt = new DateTime();

    if (!is_object($course)) {
        $course = (object) $course;
    }

    // Set values for scheduled tasks.
    $dates = ['\local_satool\task\infomail_task' => $course->maildate,
        '\local_satool\task\submitdate_task' => $course->submitdate - 604800,
        '\local_satool\task\deadline_task' => $course->deadline - 604800];
    foreach ($dates as $key => $date) {
        $dt->setTimestamp($date);
        $datetime = explode(' ', $dt->format('j n Y G i'));
        $task = \core\task\manager::get_scheduled_task($key);
        $task->set_minute($datetime[4]);
        $task->set_hour($datetime[3]);
        $task->set_month($datetime[1]);
        $task->set_day($datetime[0]);
        $task->set_disabled(0);
        \core\task\manager::configure_scheduled_task($task);
    }

    $course->name = trim($course->name);
    // Rubric is static and cannot be changed currently. For compatibility, it is set to the database object.
    $course->rubric = '{"allg":["Allgemeiner Eindruck",4,1],"doc":["Dokumentation",4,2],' .
        '"analyse":["Analyse",4,1],"des":["Design",4,1],"real":["Realisation",4,3],"test":["Tests",4,1],' .
        '"praes":["Pr\u00e4sentation",4,1],"git":["GitHub",2,2]}';
    $courseid = $DB->insert_record('local_satool_courses', $course);
    $course->id = $courseid;
    // Save files and course.
    $course = file_postupdate_standard_filemanager($course, 'coursefiles', $manageroptions, $context,
        'local_satool', 'document', $course->id * 1000000);
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
    // Setup filemanager options.
    $manageroptions = array(
        'maxfiles' => 5
    );
    $dt = new DateTime();

    if (!is_object($course)) {
        $course = (object) $course;
    }

    // Set values for scheduled tasks.
    $dates = ['\local_satool\task\infomail_task' => $course->maildate,
        '\local_satool\task\submitdate_task' => $course->submitdate - 604800,
        '\local_satool\task\deadline_task' => $course->deadline - 604800];
    foreach ($dates as $key => $date) {
        $dt->setTimestamp($date);
        $datetime = explode(' ', $dt->format('j n Y G i'));
        $task = \core\task\manager::get_scheduled_task($key);
        $task->set_minute($datetime[4]);
        $task->set_hour($datetime[3]);
        $task->set_month($datetime[1]);
        $task->set_day($datetime[0]);
        \core\task\manager::configure_scheduled_task($task);
    }

    // Save course files and course.
    $course->name = trim($course->name);
    $course = file_postupdate_standard_filemanager($course, 'coursefiles', $manageroptions, $context,
        'local_satool', 'document', $course->id * 1000000);
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

    // Check if in correct context.
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        send_file_not_found();
    }
    require_login();

    // Check if filearea is valid.
    if ($filearea !== 'document') {
        send_file_not_found();
    }

    // Get file storage and name.
    $fs = get_file_storage();
    $filename = array_pop($args);
    $filepath = $args ? '/'.implode('/', $args).'/' : '/';

    // Get project id and check if user is allowed to view/download the file.
    $projectid = (($args[0] % 1000000) - ($args[0] % 100)) / 100;
    $project = $DB->get_record('local_satool_projects', ['id' => $projectid]);
    $projdef = json_decode($project->definition);
    $usernotinproject = $projdef->teacher != $USER->id && $projdef->status != 0 && $projdef->student1 != $USER->id &&
        $projdef->student2 != $USER->id;
    if (!has_capability('local/satool:viewallprojects', context_system::instance()) && $usernotinproject &&
            $args[0] != $args[0] - ($args[0] % 1000000)) {
        send_file_not_found();
    }

    // Check if file exists.
    if (!$file = $fs->get_file($context->id, 'local_satool', 'document', $args[0], '/', $filename) or $file->is_directory()) {
        send_file_not_found();
    }

    // Send file.
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
    // Prepare the assigned column with select and search bar.
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
    // Prepare the buttons column.
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
    // Prepare the not assigned column with select and search bar.
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
    // Prepare the assigned column with select and search bar.
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
    // Prepare the buttons column.
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
    // Prepare the not assigned column with select and search bar.
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

/**
 * Insert a new project definition into the database.
 *
 * @param stdClass $projdef
 * @param int $courseid
 */
function local_satool_create_projdef($projdef, $courseid) {
    global $DB, $CFG;

    $context = context_system::instance();
    // Setup filemanager options.
    $manageroptions = array(
        'maxfiles' => 1,
        'accepted_types' => array('.svg', '.jpg', '.png')
    );

    if (!is_object($projdef)) {
        $projdef = (object) $projdef;
    }

    // Trim all text field values.
    $trimmable = ['name', 'employer', 'description', 'tools', 'opsystems', 'langs', 'musthaves', 'nicetohaves'];
    foreach ($trimmable as $trim) {
        $projdef->$trim = trim($projdef->$trim);
    }

    // Create new project object and save to get project id.
    $project = new stdClass();
    $project->definition = null;
    $projectid = $DB->insert_record('local_satool_projects', $project);
    $project->id = $projectid;
    // Save file to server and save definition to project object.
    $projdef = file_postupdate_standard_filemanager($projdef, 'projsketch', $manageroptions, $context,
        'local_satool', 'document', $courseid * 1000000 + $project->id * 100);
    $project->definition = json_encode($projdef);
    $DB->update_record('local_satool_projects', $project);
    // Set student1's projectid.
    $stud1 = $DB->get_record('local_satool_students', ['userid' => $projdef->student1, 'courseid' => $courseid]);
    if ($stud1) {
        $stud1->projectid = $project->id;
        $DB->update_record('local_satool_students', $stud1);
    }
    return $project;
}

/**
 * Update an existing project definition
 *
 * @param stdClass $projdef
 * @param int $courseid
 * @param stdClass $project
 */
function local_satool_update_projdef($projdef, $courseid, $project) {
    global $DB, $CFG;

    $context = context_system::instance();
    // Setup filemanager options.
    $manageroptions = array(
        'maxfiles' => 1,
        'accepted_types' => array('.svg', '.jpg', '.png')
    );

    if (!is_object($projdef)) {
        $projdef = (object) $projdef;
    }

    // Trim all text field values.
    $trimmable = ['name', 'employer', 'description', 'tools', 'opsystems', 'langs', 'musthaves', 'nicetohaves'];
    foreach ($trimmable as $trim) {
        $projdef->$trim = trim($projdef->$trim);
    }

    // Save file to server and save definition to project object.
    $projdef = file_postupdate_standard_filemanager($projdef, 'projsketch', $manageroptions, $context,
        'local_satool', 'document', $courseid * 1000000 + $project->id * 100);
    $project->definition = json_encode($projdef);
    $DB->update_record('local_satool_projects', $project);

    // Set student2's projectid if exists.
    $oldstud2 = $DB->get_record_select('local_satool_students', 'projectid = ? AND userid != ?',
        [$project->id, $projdef->student1]);
    if ($oldstud2) {
        $oldstud2->projectid = null;
        $DB->update_record('local_satool_students', $oldstud2);
    }
    $stud2 = $DB->get_record('local_satool_students', ['userid' => $projdef->student2, 'courseid' => $courseid]);
    if ($stud2) {
        $stud2->projectid = $project->id;
        $DB->update_record('local_satool_students', $stud2);
    }
}

/**
 * Insert a new project document into the database
 *
 * @param stdClass $document
 * @param int $courseid
 * @param int $projectid
 */
function local_satool_upload_doc($document, $courseid, $projectid) {
    global $DB, $CFG;

    $context = context_system::instance();

    // Setup options for the filemanager.
    $filetypes = array('.pdf', '.docx', '.xlsx', '.png', '.jpg', '.csv', '.svg', '.txt', '.zip', '.rar',
        '.7z', '.tar.gz', '.tar', '.xml', '.gif', '.json');
    $manageroptions = array(
        'maxfiles' => 1,
        'accepted_types' => $filetypes
    );

    if (!is_object($document)) {
        $document = (object) $document;
    }

    // Set dynamic fileid if document is file.
    if ($document->type) {
        $projdocuments = $DB->get_records_select('local_satool_documents', 'fileid != 0 AND projectid = ?', [$projectid]);
        $document->fileid = count($projdocuments) + 1;
    } else {
        $document->fileid = 0;
    }
    // Set the other document values.
    $document->status = 1;
    $document->title = trim($document->title);
    $document->note = trim($document->note);
    $documentid = $DB->insert_record('local_satool_documents', $document);
    $document->id = $documentid;

    // Save file to server and set path in document database object if document is file.
    $docfullid = $courseid * 1000000 + $projectid * 100 + $document->fileid + 10;
    if ($document->type) {
        $document = file_postupdate_standard_filemanager($document, 'projfiles', $manageroptions, $context,
            'local_satool', 'document', $docfullid);
        $fs = get_file_storage();
        $files = $fs->get_area_files(1, 'local_satool', 'document', $docfullid);
        $file = array_pop($files);
        $document->path = '/pluginfile.php/1/local_satool/document/' . $docfullid . '/' . $file->get_filename();
    } else {
        $document->path = $document->link;
    }
    $DB->update_record('local_satool_documents', $document);
    return $document;
}

/**
 * Update an existing document
 *
 * @param stdClass $document
 * @param int $courseid
 * @param int $projectid
 */
function local_satool_update_doc($document, $courseid, $projectid) {
    global $DB, $CFG;

    $context = context_system::instance();
    // Setup options for the filemanager.
    $filetypes = array('.pdf', '.docx', '.xlsx', '.png', '.jpg', '.csv', '.svg', '.txt', '.zip', '.rar',
        '.7z', '.tar.gz', '.tar', '.xml', '.gif', '.json');
    $manageroptions = array(
        'maxfiles' => 1,
        'accepted_types' => $filetypes
    );

    if (!is_object($document)) {
        $document = (object) $document;
    }

    // Set other necessary document values.
    $document->title = trim($document->title);
    $document->note = trim($document->note);
    // Save file to server and set path in document object if document is file.
    $docfullid = $courseid * 1000000 + $projectid * 100 + $document->fileid + 10;
    if ($document->type) {
        $document = file_postupdate_standard_filemanager($document, 'projfiles', $manageroptions, $context,
            'local_satool', 'document', $docfullid);
        $fs = get_file_storage();
        $files = $fs->get_area_files(1, 'local_satool', 'document', $docfullid);
        $file = array_pop($files);
        $document->path = '/pluginfile.php/1/local_satool/document/' . $docfullid . '/' . $file->get_filename();
    } else {
        $document->path = $document->link;
    }
    $DB->update_record('local_satool_documents', $document);
}

/**
 * Insert a new project submission into the database
 *
 * @param stdClass $projsub
 * @param int $courseid
 * @param stdClass $project
 */
function local_satool_submit_projsub($projsub, $courseid, $project) {
    global $DB, $CFG, $SITE;

    $context = context_system::instance();
    // Setup filemanager options.
    $manageroptions = array(
        'maxfiles' => 1,
        'accepted_types' => array('.zip')
    );

    if (!is_object($projsub)) {
        $projsub = (object) $projsub;
    }

    // Prepare array with users to send notification mail to.
    $tosend = [];
    $projdef = json_decode($project->definition);
    $student1 = $DB->get_record('user', ['id' => $projdef->student1]);
    $tosend[] = $student1;
    if ($projdef->student2) {
        $student2 = $DB->get_record('user', ['id' => $projdef->student2]);
        $tosend[] = $student2;
    }
    $teacher = $DB->get_record('user', ['id' => $projdef->teacher]);
    $tosend[] = $teacher;

    // Set necessary values and save .zip file to server.
    $projsub->github = trim($projsub->github);
    $projsub = file_postupdate_standard_filemanager($projsub, 'projsubfiles', $manageroptions, $context,
        'local_satool', 'document', $courseid * 1000000 + $project->id * 100 + 1);
    // Save submission to project object.
    $project->submission = json_encode($projsub);
    $DB->update_record('local_satool_projects', $project);
    // Send notification mail to teacher and student(s).
    foreach ($tosend as $user) {
        email_to_user($user, $SITE->shortname, "Bestätigung SA-Abgabe",
            get_string('notifysubmissionmail', 'local_satool', $projdef->name));
    }
}

/**
 * Insert new project milestones into the database
 *
 * @param stdClass $projmilestones
 * @param int $courseid
 * @param stdClass $project
 */
function local_satool_add_milestones($projmilestones, $courseid, $project) {
    global $DB, $CFG, $SITE;

    $context = context_system::instance();

    if (!is_object($projmilestones)) {
        $projmilestones = (object) $projmilestones;
    }

    // Trim topic if exists.
    $projmilestones->topic1 = trim($projmilestones->topic1);
    if ($projmilestones->topic2) {
        $projmilestones->topic2 = trim($projmilestones->topic2);
    }
    if ($projmilestones->topic3) {
        $projmilestones->topic3 = trim($projmilestones->topic3);
    }
    // Save submission to project object.
    $project->milestones = json_encode($projmilestones);
    $DB->update_record('local_satool_projects', $project);
}