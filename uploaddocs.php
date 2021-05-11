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
 * The SA-Tool upload project documents page.
 *
 * @package    local_satool
 * @copyright  2021 Jeremy Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/local/satool/classes/uploaddocs_form.php');
require_once($CFG->dirroot.'/local/satool/lib.php');
require_once($CFG->libdir.'/filelib.php');

// Check for capabilities and if user is logged in.
require_login();
!isguestuser($USER->id) || print_error('noguest');

// Get params.
$id = optional_param('id', -1, PARAM_INT);
$projectid = required_param('projectid', PARAM_INT);
$type = optional_param('type', 0, PARAM_INT);

// Set Page variables.
$PAGE->set_url(new moodle_url('/local/satool/uploaddocs.php', ['id' => $id, 'projectid' => $projectid, 'type' => $type]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_satool'));
$PAGE->set_heading(get_string('title', 'local_satool'));

// Set additional values.
$returnurl = new moodle_url('/local/satool/viewproj.php', ['id' => $projectid]);
$filetypes = array('.pdf', '.docx', '.xlsx', '.png', '.jpg', '.csv', '.svg', '.txt', '.zip', '.rar',
    '.7z', '.tar.gz', '.tar', '.xml', '.gif', '.json');
$manageroptions = array(
    'maxfiles' => 1,
    'accepted_types' => $filetypes
);

// Get database objects.
$project = $DB->get_record('local_satool_projects', ['id' => $projectid]);
$projdef = json_decode($project->definition);
$students = $DB->get_records('local_satool_students', ['projectid' => $projectid]);
$student = array_shift($students);
$curstudent = $DB->get_record('local_satool_students', ['userid' => $USER->id]);
$curteacher = $DB->get_record('local_satool_teachers', ['userid' => $USER->id]);

// Check for existing document.
if ($id == -1) {
    $document = new stdClass();
    $document->id = -1;
    $document->type = $type;
    $document->projectid = $projectid;
    $allprojdocss = $DB->get_records_select('local_satool_documents', 'fileid != 0 AND projectid = ?', [$projectid]);
    $document->fileid = count($allprojdocss) + 1;
} else {
    $document = $DB->get_record('local_satool_documents', ['id' => $id]);
    if (!$document) {
        print_error('accessdenied', 'admin');
    }
}

// Prepare Filemanager if document exists and is a file.
if ($document->id !== -1) {
    $document = file_prepare_standard_filemanager($document, 'projfiles', $manageroptions, $PAGE->context,
        'local_satool', 'document', $student->courseid * 1000000 + $projectid * 100 + $document->fileid + 10);
}
$uploadform = new local_satool_uploaddocs_form(new moodle_url($PAGE->url), array('document' => $document));

// Deal with form submission.
if ($uploadform->is_cancelled()) {
    redirect($returnurl);
} else if ($uploadnew = $uploadform->get_data()) {
    $uploadcreated = false;
    $uploadnew->id = $id;
    if ($uploadnew->id == -1) {
        $uploadnewid = local_satool_upload_doc($uploadnew, $student->courseid, $project->id);
        $uploadnew->id = $uploadnewid->id;
        if (isset($uploadnew->projfiles_filemanager)) {
            $uploadsave = file_save_draft_area_files($uploadnew->projfiles_filemanager, $PAGE->context->id,
                'local_satool', 'document', $student->courseid * 1000000 + $projectid * 100 + $uploadnew->fileid + 10,
                $manageroptions);
        }
        local_satool_update_doc($uploadnew, $student->courseid, $project->id);
    } else {
        if ($curstudent) {
            if ($projdef->student2) {
                $by = json_encode([$projdef->student1, $projdef->student2]);
            } else {
                $by = json_encode([$curstudent->userid]);
            }
            $for = json_encode($projdef->teacher);
        } else {
            $by = json_encode($curteacher->userid);
            if ($projdef->student2) {
                $from = json_encode([$projdef->student1, $projdef->student2]);
            } else {
                $from = json_encode([$curstudent->userid]);
            }
        }
        if (isset($uploadnew->projfiles_filemanager)) {
            $uploadsave = file_save_draft_area_files($uploadnew->projfiles_filemanager, $PAGE->context->id,
                'local_satool', 'document', $student->courseid * 1000000 + $projectid * 100 + $uploadnew->fileid + 10,
                $manageroptions);
        }
        local_satool_update_doc($uploadnew, $student->courseid, $project->id, $by, $for);
    }
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $uploadform->display();
echo $OUTPUT->footer();