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