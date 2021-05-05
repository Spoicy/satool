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
 * Form for creating and editing an SA course
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
 * Class local_satool_editcourse_form.
 *
 * @copyright 2021 Jeremy Funke
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_satool_editcourse_form extends moodleform {
    /**
     * Definition of the editcourse moodleform.
     */
    public function definition() {
        global $USER, $CFG;

        $mform = $this->_form;

        if (!is_array($this->_customdata)) {
            throw new coding_exception('invalid custom data for course_edit_form');
        }

        $year = date('Y');

        $datetimeoptions = array(
            'startyear' => $year - 2,
            'stopyear' => $year + 2,
            'step' => 5
        );

        $course = $this->_customdata['course'];

        // Accessibility: "Required" is bad legend text.
        $strgeneral  = get_string('general');
        $strrequired = get_string('required');

        // Set up form.
        $mform->addElement('text', 'name', get_string('coursename', 'local_satool'),
            'size="45" placeholder="' . get_string('coursenameplaceholder', 'local_satool') . '"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', $strrequired, 'required', null, 'client');

        $mform->addElement('date_time_selector', 'maildate', get_string('coursemaildate', 'local_satool'), $datetimeoptions);
        $mform->addRule('maildate', $strrequired, 'required', null, 'client');

        $mform->addElement('date_time_selector', 'submitdate', get_string('coursesubmitdate', 'local_satool'), $datetimeoptions);
        $mform->addRule('submitdate', $strrequired, 'required', null, 'client');

        $mform->addElement('date_time_selector', 'deadline', get_string('coursedeadline', 'local_satool'), $datetimeoptions);
        $mform->addRule('deadline', $strrequired, 'required', null, 'client');

        $mform->addElement('textarea', 'mailtext', get_string('coursemailtext', 'local_satool'),
            'wrap="virtual" rows="14" cols="65"');
        $mform->addRule('mailtext', $strrequired, 'required', null, 'client');

        $mform->addElement('filemanager', 'coursefiles_filemanager', get_string('coursefiles', 'local_satool'), null, [
            'maxfiles' => 5
        ]);
        $mform->setType('coursefiles_filemanager', PARAM_FILE);

        // Add buttons.
        $this->add_action_buttons();

        // Fill form with data if course exists.
        $this->set_data($course);
    }
}