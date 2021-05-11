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
 * Form for creating and editing a project definition
 *
 * @copyright 2021 Jeremy Funke
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_satool
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/user/lib.php');

/**
 * Class local_satool_editdef_form.
 *
 * @copyright 2021 Jeremy Funke
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_satool_editdef_form extends moodleform {
    /**
     * Definition of the editdef moodleform.
     */
    public function definition() {
        global $USER, $CFG, $DB;

        $mform = $this->_form;

        if (!is_array($this->_customdata)) {
            throw new coding_exception('invalid custom data for def_edit_form');
        }

        $year = date('Y');

        $project = $this->_customdata['project'];
        if (isset($project->definition)) {
            $projdef = json_decode($project->definition);
        } else {
            $projdef = null;
        }

        // Accessibility: "Required" is bad legend text.
        $strgeneral  = get_string('general');
        $strrequired = get_string('required');

        // Set option for student 1.
        if (isset($projdef->student1)) {
            $curuserid = $projdef->student1;
        } else {
            $curuserid = $USER->id;
        }
        $curuser = $DB->get_record('user', ['id' => $curuserid]);

        // Get students for second student dropdown.
        $course = array_reverse($DB->get_records('local_satool_courses'))[0];
        $students = $DB->get_records_select('local_satool_students', 'courseid = ? AND projectid IS NULL AND userid != ?',
            [$course->id, $curuserid]);
        $studentoptions = array();
        $studentoptions[0] = 'None';

        // Set up select options for student 2.
        foreach ($students as $student) {
            $user = $DB->get_record('user', ['id' => $student->userid]);
            $studentoptions[$student->userid] = fullname($user);
        }

        // Set hidden element values.
        if (isset($projdef->teacher)) {
            $teacher = $projdef->teacher;
        } else {
            $teacher = null;
        }
        if (isset($projdef->status)) {
            $status = $projdef->status;
        } else {
            $status = 0;
        }

        // Set up form.
        $mform->addElement('select', 'student1', get_string('projstudent1', 'local_satool'),
            [$curuserid => fullname($curuser)]);
        $mform->setDefault('student1', $curuserid);

        $mform->addElement('select', 'student2', get_string('projstudent2', 'local_satool'), $studentoptions);
        $mform->addHelpButton('student2', 'projstudent2', 'local_satool');
        if (isset($projdef->student2) && $projdef->student2 != 0) {
            $mform->setDefault('student2', $projdef->student2);
        } else {
            $mform->setDefault('student2', 0);
        }

        $mform->addElement('text', 'name', get_string('projname', 'local_satool'), 'size="45"');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('text', 'employer', get_string('projemployer', 'local_satool'));
        $mform->setType('employer', PARAM_TEXT);

        $mform->addElement('textarea', 'description', get_string('projdescription', 'local_satool'),
            'wrap="virtual" rows="10" cols="65"');

        $mform->addElement('filemanager', 'projsketch_filemanager', get_string('projsketch', 'local_satool'), null, [
            'maxfiles' => 1,
            'accepted_types' => array('.svg', '.jpg', '.png')
        ]);
        $mform->setType('projsketch_filemanager', PARAM_FILE);

        $mform->addElement('text', 'tools', get_string('projtools', 'local_satool'), 'size="45"');
        $mform->setType('tools', PARAM_TEXT);
        $mform->addHelpButton('tools', 'projtools', 'local_satool');

        $mform->addElement('text', 'opsystems', get_string('projopsystems', 'local_satool'), 'size="45"');
        $mform->setType('opsystems', PARAM_TEXT);
        $mform->addHelpButton('opsystems', 'projopsystems', 'local_satool');

        $mform->addElement('text', 'langs', get_string('projlangs', 'local_satool'), 'size="45"');
        $mform->setType('langs', PARAM_TEXT);
        $mform->addHelpButton('langs', 'projlangs', 'local_satool');

        $mform->addElement('text', 'musthaves', get_string('projmusthaves', 'local_satool'), 'size="45"');
        $mform->setType('musthaves', PARAM_TEXT);
        $mform->addHelpButton('musthaves', 'projmusthaves', 'local_satool');

        $mform->addElement('text', 'nicetohaves', get_string('projnicetohaves', 'local_satool'), 'size="45"');
        $mform->setType('nicetohaves', PARAM_TEXT);
        $mform->addHelpButton('nicetohaves', 'projnicetohaves', 'local_satool');

        $mform->addElement('hidden', 'status', $status);
        $mform->setType('status', PARAM_INT);
        $mform->addElement('hidden', 'teacher', $teacher);
        $mform->setType('teacher', PARAM_INT);
        $mform->addElement('hidden', 'id' , $project->id);
        $mform->setType('id', PARAM_INT);

        // Add buttons.
        $this->add_action_buttons();

        // Fill form with data if course exists.
        $this->set_data($projdef);
    }
}