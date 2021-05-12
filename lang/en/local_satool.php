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
 * English language file.
 *
 * @package    local_satool
 * @category   string
 * @copyright  2021 Jeremy Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Plugin required translations.
$string['pluginname'] = 'SA-Tool';
$string['title'] = 'SA-Tool';

// Schedule task translations.
$string['infomailtask'] = 'Send Info-Mail to SA course students';
$string['submitdatetask'] = 'Project definition warning email to SA course students';
$string['deadlinetask'] = 'Deadline warning email to SA course students';

// Course creation form translations.
$string['coursename'] = 'Course name';
$string['coursenameplaceholder'] = 'e.g. SA 2020/21';
$string['coursemaildate'] = 'Info-Mail date';
$string['coursesubmitdate'] = 'Submission date';
$string['coursedeadline'] = 'Deadline';
$string['coursemailtext'] = 'Info-Mail text';
$string['coursefiles'] = 'Course files';
$string['unassigned'] = 'Unassigned users';
$string['teacherassigned'] = 'Assigned teachers';
$string['studentassigned'] = 'Assigned students';
$string['teacherassignedcount'] = 'Assigned teachers ({$a})';
$string['studentassignedcount'] = 'Assigned students ({$a})';
$string['unassignedcount'] = 'Unassigned users ({$a})';
$string['search'] = 'Search';
$string['clear'] = 'Clear';
$string['add'] = 'Add';
$string['remove'] = 'Remove';
$string['createcoursetounlock'] = 'Create a new course to unlock adding students and teachers.';
$string['teacherassignedcountmatching'] = 'Assigned teachers matching \'{$a->search}\' ({$a->count})';
$string['studentassignedcountmatching'] = 'Assigned students matching \'{$a->search}\' ({$a->count})';
$string['unassignedcountmatching'] = 'Unassigned users matching \'{$a->search}\' ({$a->count})';

// Course mail translations. These will not be translated into english.
$string['warningsubmitdateincomplete'] = 'Ihrer Projektdefinition ist unvollständig und wurde bis jetzt von keine Lehrperson angenohmen. Bitte laden Sie die fehlende Daten bis Eingabetermin ({$a}) hoch.';
$string['warningsubmitdatemissing'] = 'Ihrer Projektdefinition wurde noch nicht hochgeladen. Bitte laden Sie die Projektdefinition bis Eingabetermin ({$a}) hoch.';
$string['warningdeadline'] = 'Die Abgabetermin ist in einer Woche ({$a}), bitte reichen Sie bis denn Ihre SA ein.';

// Project definition translations.
$string['projstudent1'] = 'Student 1';
$string['projstudent2'] = '(Opt.) Student 2';
$string['projstudent2_help'] = 'Leave as "None" if working alone.';
$string['projname'] = 'Project name';
$string['projemployer'] = 'Employer';
$string['projdescription'] = 'Idea description';
$string['projsketch'] = 'Sketch / Layout';
$string['projtools'] = 'Programming tools';
$string['projtools_help'] = 'Separate each tool with a comma.';
$string['projopsystems'] = 'Operating system(s)';
$string['projopsystems_help'] = 'Separate each operating system with a comma if listing multiple.';
$string['projlangs'] = 'Programming language(s)';
$string['projlangs_help'] = 'Separate each programming language with a comma if listing multiple.';
$string['projmusthaves'] = 'Must haves';
$string['projmusthaves_help'] = 'Separate each must have with a comma.';
$string['projnicetohaves'] = 'Nice to haves';
$string['projnicetohaves_help'] = 'Separate each nice to have with a comma if listing multiple.';
$string['projteacher'] = 'Supervisor';
$string['sketchalt'] = 'Project sketch / layout';

// Main page translations.
$string['projectdefinitions'] = 'Project definitions';
$string['projectdefinition'] = 'Project definition';
$string['supervisedprojects'] = 'Supervised projects';
$string['supervisedproject'] = 'Project';
$string['viewproject'] = 'View';
$string['superviseproject'] = 'Supervise';
$string['editproject'] = 'Bearbeiten';
$string['gradeproject'] = 'Grade';
$string['createnewcourse'] = 'Create a new course';
$string['editcurrentcourse'] = 'Edit current course';
$string['createnewdef'] = 'Create project definition';

// Confirm translations.
$string['confirmsupervise'] = 'Supervise the project \'{$a}\'?';
$string['confirmsupervisefull'] = 'Are you sure you want to supervise the project \'{$a}\'? This action is irreversible.';

// Project page translations.
$string['viewdefinition'] = 'View project definition';
$string['uploaddocuments'] = 'Upload documents';
$string['uploadlinks'] = 'Upload links';
$string['submitproject'] = 'Submit project';
$string['documents'] = 'Documents';
$string['nodocumentsfound'] = 'No documents have been uploaded yet.';
$string['statusgraded'] = 'Project status: Graded';
$string['statussubmitted'] = 'Project status: Submitted';
$string['statusincomplete'] = 'Project status: Incomplete';
$string['submission'] = 'Project submission';
$string['grade'] = 'Grade';
$string['gradetotals'] = '{$a->total}/{$a->totalall} points';
$string['gradevalue'] = 'Grade: ';

// Document uploading page translations.
$string['projfilestitle'] = 'File / Link title';
$string['projfilesnote'] = 'File / Link note';
$string['projfiles'] = 'File / Document';
$string['projfileslink'] = 'Link';

// Project submission page translations.
$string['projsubgithub'] = 'GitHub Link';
$string['projsubfiles'] = '.zip File';

// Project grade translations.
$string['submitgrade'] = 'Submit grade';

// General translations.
$string['goback'] = 'Go back';