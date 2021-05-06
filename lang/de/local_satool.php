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
 * German language file.
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
$string['infomailtask'] = 'Info-Mail an SA-Kursschüler senden';
$string['submitdatetask'] = 'Warn-Email an SA-Kursschüler für Projektdefinitionsabgabe senden';
$string['deadlinetask'] = 'Warn-Email an SA-Kursschüler für SA-Abgabe senden';

// Course creation form translations.
$string['coursename'] = 'Kursname';
$string['coursenameplaceholder'] = 'z.B. SA 2020/21';
$string['coursemaildate'] = 'Info-Mail-Termin';
$string['coursesubmitdate'] = 'Eingabetermin';
$string['coursedeadline'] = 'Abgabetermin';
$string['coursemailtext'] = 'Info-Mail Text';
$string['coursefiles'] = 'Kursdateien';
$string['teacherassigned'] = 'Eingeschriebene Lehrpersonen';
$string['studentassigned'] = 'Eingeschriebene Schüler/innen';
$string['unassigned'] = 'Nicht eingeschriebene Benutzer';
$string['teacherassignedcount'] = 'Eingeschriebene Lehrpersonen ({$a})';
$string['studentassignedcount'] = 'Eingeschriebene Schüler/innen ({$a})';
$string['unassignedcount'] = 'Nicht eingeschriebene Benutzer ({$a})';
$string['search'] = 'Suchen';
$string['clear'] = 'Löschen';
$string['add'] = 'Einschreiben';
$string['remove'] = 'Entfernen';
$string['createcoursetounlock'] = 'Erstellen Sie einen neuen Kurs um Schüler und Lehrpersonen einschreiben zu können.';
$string['teacherassignedcountmatching'] = 'Eingeschriebene Lehrpersonen mit \'{$a->search}\' ({$a->count})';
$string['studentassignedcountmatching'] = 'Eingeschriebene Schüler/innen mit \'{$a->search}\' ({$a->count})';
$string['unassignedcountmatching'] = 'Nicht eingeschriebene Benutzer mit \'{$a->search}\' ({$a->count})';

// Course mail translations. These will not be translated into english.
$string['warningsubmitdateincomplete'] = 'Ihrer Projektdefinition ist unvollständig und wurde bis jetzt von keine Lehrperson angenohmen. Bitte laden Sie die fehlende Daten bis Eingabetermin ({$a}) hoch.';
$string['warningsubmitdatemissing'] = 'Ihrer Projektdefinition wurde noch nicht hochgeladen. Bitte laden Sie die Projektdefinition bis Eingabetermin ({$a}) hoch.';
$string['warningdeadline'] = 'Die Abgabetermin ist in einer Woche ({$a}), bitte reichen Sie bis denn Ihre SA ein.';