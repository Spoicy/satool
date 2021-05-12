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
 * The SA-Tool project definition page.
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
$supervise = optional_param('supervise', 0, PARAM_INT);
$superviseconfirm = optional_param('superviseconfirm', '', PARAM_ALPHANUM);

// Set Page variables.
$PAGE->set_url(new moodle_url('/local/satool/viewdef.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_satool'));
$PAGE->set_heading(get_string('title', 'local_satool'));

// Get various database objects.
$teacher = $DB->get_record('local_satool_teachers', ['userid' => $USER->id]);
$student = $DB->get_record('local_satool_students', ['projectid' => $id, 'userid' => $USER->id]);
$project = $DB->get_record('local_satool_projects', ['id' => $id]);
$projdef = json_decode($project->definition);
$student1 = $DB->get_record('user', ['id' => $projdef->student1]);
$student2 = $DB->get_record('user', ['id' => $projdef->student2]);
$projdefteacher = $DB->get_record('user', ['id' => $projdef->teacher]);
$PAGE->navbar->add('SA-Tool', new moodle_url('/local/satool'));
$PAGE->navbar->add($projdef->name);

// Check if user is allowed to view page.
if (($student1->id != $USER->id && ($student2 && $student2->id != $USER->id) &&
        ($projdefteacher && $projdefteacher->id != $USER->id) &&
        !has_capability('local/satool:viewallprojects', $PAGE->context)) && !($teacher && $projdef->status != 1)) {
    print_error('accessdenied', 'admin');
}

// Ask for confirmation if teacher is sure they want to supervise the project.
if (($supervise || $superviseconfirm) && $projdef->teacher == 0) {
    if ($teacher) {
        if ($superviseconfirm != md5($supervise)) {
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('confirmsupervise', 'local_satool', $projdef->name));
            $optionsyes = array('supervise' => $supervise, 'superviseconfirm' => md5($supervise), 'sesskey' => sesskey());
            $returnurl = new moodle_url('/local/satool/viewdef.php?id=' . $id);
            $confirmurl = new moodle_url($returnurl, $optionsyes);
            $confirmbutton = new single_button($confirmurl, get_string('superviseproject', 'local_satool'), 'post');
            echo $OUTPUT->confirm(get_string('confirmsupervisefull', 'local_satool', $projdef->name), $confirmbutton, $returnurl);
            echo $OUTPUT->footer();
            die;
        } else {
            if ($projdef->status != 1) {
                $projdef->teacher = $teacher->userid;
                $projdef->status = 1;
                $project->definition = json_encode($projdef);
                $DB->update_record('local_satool_projects', $project);
            }
            redirect(new moodle_url('/local/satool'));
        }
    }
}

// Check permissions and display different things based on user.
if ($teacher && ($projdef->teacher == $teacher->userid || $projdef->status == 0)) {
    // Get course and calculate the item id for the sketch image.
    $course = $DB->get_record('local_satool_courses', ['id' => $teacher->courseid]);
    $sketchid = $course->id * 1000000 + $project->id * 100;

    // Get files from storage.
    $fs = get_file_storage();
    $files = $fs->get_area_files($PAGE->context->id, 'local_satool', 'document', $sketchid);

    // Find the image in the retrieved files and create the html img.
    $filesmask = ['.png', '.jpg', '.svg'];
    $sketchfile = '';
    foreach ($files as $file) {
        if (in_array(substr($file->get_filename(), -4), $filesmask)) {
            $sketchfile = $file->get_filename();
        }
    }
    $sketch = html_writer::img('/pluginfile.php/1/local_satool/document/' . $sketchid . '/' . $sketchfile,
        get_string('sketchalt', 'local_satool'), ['class' => 'w-100']);

    // Set troublesome values in advance.
    if ($student2) {
        $student2text = fullname($student2);
    } else {
        $student2text = '';
    }
    if ($projdefteacher) {
        $teachertext = fullname($projdefteacher);
    } else {
        $teachertext = '';
    }

    // Explode array to simplify trimming of definition variables.
    $explodearray = [
        'tools' => 'projtools',
        'opsystems' => 'projopsystems',
        'langs' => 'projlangs',
        'musthaves' => 'projmusthaves',
        'nicetohaves' => 'projnicetohaves'
    ];
    // Trim definition variables.
    foreach ($explodearray as $key => $explode) {
        $items = explode(',', $projdef->$key);
        $$explode = '';
        $i = 0;
        foreach ($items as $item) {
            if ($i == 0) {
                $$explode .= trim($item);
            } else {
                $$explode .= '<br>' . trim($item);
            }
            $i++;
        }
    }

    // Set up infos column.
    $infosarray = [
        'projname' => $projdef->name,
        'projstudent1' => fullname($student1),
        'projstudent2' => $student2text,
        'projemployer' => $projdef->employer,
        'projteacher' => $teachertext,
        'projtools' => $projtools,
        'projopsystems' => $projopsystems,
        'projlangs' => $projlangs,
        'projmusthaves' => $projmusthaves,
        'projnicetohaves' => $projnicetohaves
    ];
    $infos = '';
    foreach ($infosarray as $key => $info) {
        $infos .= html_writer::div(
            html_writer::div(html_writer::span(get_string($key, 'local_satool') . ':',
                'font-weight-bold text-break'), 'col-md-4') .
            html_writer::div(html_writer::span($info, 'text-break'), 'col-md-8'),
            'row mb-2');
    }

    // Combine sketch and infos columns and output.
    $html = html_writer::div(
        html_writer::div($infos, 'col-md-7') .
        html_writer::div($sketch, 'col-md-5 mb-2'), 'row') .
        html_writer::tag('a', get_string('goback', 'local_satool'),
            ['href' => new moodle_url('/local/satool'), 'class' => 'btn btn-secondary float-right']);
} else if (has_capability('local/satool:viewallprojects', $PAGE->context) || $student) {
    // Get course and calculate the item id for the sketch image.
    $stud1 = $DB->get_record('local_satool_students', ['userid' => $projdef->student1, 'projectid' => $project->id]);
    $course = $DB->get_record('local_satool_courses', ['id' => $stud1->courseid]);
    $sketchid = $course->id * 1000000 + $project->id * 100;

    // Get files from storage.
    $fs = get_file_storage();
    $files = $fs->get_area_files($PAGE->context->id, 'local_satool', 'document', $sketchid);

    // Find the image in the retrieved files and create the html img.
    $filesmask = ['.png', '.jpg', '.svg'];
    $sketchfile = '';
    foreach ($files as $file) {
        if (in_array(substr($file->get_filename(), -4), $filesmask)) {
            $sketchfile = $file->get_filename();
        }
    }
    $sketch = html_writer::img('/pluginfile.php/1/local_satool/document/' . $sketchid . '/' . $sketchfile,
        get_string('sketchalt', 'local_satool'), ['class' => 'w-100']);

    // Set troublesome values in advance.
    if ($student2) {
        $student2text = fullname($student2);
    } else {
        $student2text = '';
    }
    if ($projdefteacher) {
        $teachertext = fullname($projdefteacher);
    } else {
        $teachertext = '';
    }

    // Explode array to simplify trimming of definition variables.
    $explodearray = [
        'tools' => 'projtools',
        'opsystems' => 'projopsystems',
        'langs' => 'projlangs',
        'musthaves' => 'projmusthaves',
        'nicetohaves' => 'projnicetohaves'
    ];
    // Trim definition variables.
    foreach ($explodearray as $key => $explode) {
        $items = explode(', ', $projdef->$key);
        $$explode = '';
        $i = 0;
        foreach ($items as $item) {
            if ($i == 0) {
                $$explode .= $item;
            } else {
                $$explode .= '<br>' . $item;
            }
            $i++;
        }
    }

    // Set up infos column.
    $infosarray = [
        'projname' => $projdef->name,
        'projstudent1' => fullname($student1),
        'projstudent2' => $student2text,
        'projemployer' => $projdef->employer,
        'projteacher' => $teachertext,
        'projdescription' => trim($projdef->description),
        'projtools' => $projtools,
        'projopsystems' => $projopsystems,
        'projlangs' => $projlangs,
        'projmusthaves' => $projmusthaves,
        'projnicetohaves' => $projnicetohaves
    ];
    $infos = '';
    foreach ($infosarray as $key => $info) {
        $infos .= html_writer::div(
            html_writer::div(html_writer::span(get_string($key, 'local_satool') . ':', 'font-weight-bold'), 'col-md-4') .
            html_writer::div(html_writer::span($info), 'col-md-8'),
            'row mb-2');
    }

    // Combine sketch and infos columns and output.
    $html = html_writer::div(
        html_writer::div($infos, 'col-md-7') .
        html_writer::div($sketch, 'col-md-5 mb-2'), 'row') .
        html_writer::tag('a', get_string('goback', 'local_satool'),
            ['href' => new moodle_url('/local/satool'), 'class' => 'btn btn-secondary float-right']);
} else {
    print_error('accessdenied', 'admin');
}

// Output page.
echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();