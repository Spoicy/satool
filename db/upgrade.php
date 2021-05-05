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
 * XMLDB upgrade instructions
 *
 * @package    local_satool
 * @copyright  2021 Jeremy Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Update SA-Tool DB tables
 *
 * @param int $oldversion
 */
function xmldb_local_satool_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2021050501) {

        // Define table local_satool_courses to be created.
        $table = new xmldb_table('local_satool_courses');

        // Adding fields to table local_satool_courses.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '45', null, null, null, null);
        $table->add_field('maildate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('submitdate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('deadline', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('mailtext', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('rubric', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table local_satool_courses.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_satool_courses.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_satool_teachers to be created.
        $table = new xmldb_table('local_satool_teachers');

        // Adding fields to table local_satool_teachers.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('submitrequire', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table local_satool_teachers.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'local_satool_courses', ['id']);

        // Conditionally launch create table for local_satool_teachers.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_satool_projects to be created.
        $table = new xmldb_table('local_satool_projects');

        // Adding fields to table local_satool_projects.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('definition', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('submission', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('grade', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('teacherid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table local_satool_projects.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('teacherid', XMLDB_KEY_FOREIGN, ['teacherid'], 'local_satool_teachers', ['id']);

        // Conditionally launch create table for local_satool_projects.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_satool_students to be created.
        $table = new xmldb_table('local_satool_students');

        // Adding fields to table local_satool_students.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('projectid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table local_satool_students.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'local_satool_courses', ['id']);
        $table->add_key('projectid', XMLDB_KEY_FOREIGN, ['projectid'], 'local_satool_projects', ['id']);

        // Conditionally launch create table for local_satool_students.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_satool_documents to be created.
        $table = new xmldb_table('local_satool_documents');

        // Adding fields to table local_satool_documents.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('managerid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('path', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('by', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('for', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('type', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('projectid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table local_satool_documents.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('projectid', XMLDB_KEY_FOREIGN, ['projectid'], 'local_satool_projects', ['id']);

        // Conditionally launch create table for local_satool_documents.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Satool savepoint reached.
        upgrade_plugin_savepoint(true, 2021050501, 'local', 'satool');
    }

    if ($oldversion < 2021050502) {

        // Define field coursefiles to be added to local_satool_courses.
        $table = new xmldb_table('local_satool_courses');
        $field = new xmldb_field('coursefiles', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'rubric');

        // Conditionally launch add field coursefiles.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Satool savepoint reached.
        upgrade_plugin_savepoint(true, 2021050502, 'local', 'satool');
    }

}