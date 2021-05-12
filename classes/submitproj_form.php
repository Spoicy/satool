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
 * Class local_satool_submitproj_form.
 *
 * @copyright 2021 Jeremy Funke
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_satool_submitproj_form extends moodleform {
    /**
     * Definition of the submitproj moodleform.
     */
    public function definition() {
        global $USER, $CFG, $DB;

        $mform = $this->_form;

        if (!is_array($this->_customdata)) {
            throw new coding_exception('invalid custom data for submit_proj_form');
        }

        // Get project and project submission if exists.
        $project = $this->_customdata['project'];
        if (isset($project->submission)) {
            $projsub = json_decode($project->submission);
        } else {
            $projsub = null;
        }

        // Accessibility: "Required" is bad legend text.
        $strrequired = get_string('required');

        // Set up form.
        $mform->addElement('hidden', 'id', $project->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'github', get_string('projsubgithub', 'local_satool'), 'size="45"');
        $mform->setType('github', PARAM_TEXT);

        $mform->addElement('filemanager', 'projsubfiles_filemanager', get_string('projsubfiles', 'local_satool'), null, [
            'maxfiles' => 1,
            'accepted_types' => array('.zip')
        ]);
        $mform->setType('projsubfiles_filemanager', PARAM_FILE);
        $mform->addRule('projsubfiles_filemanager', $strrequired, 'required', null, 'client');

        // Add buttons.
        $this->add_action_buttons();

        // Fill form with data if project submission exists.
        $this->set_data($projsub);
    }
}