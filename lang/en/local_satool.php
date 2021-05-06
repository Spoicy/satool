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