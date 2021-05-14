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
 * Form for creating and updating a project's milestones
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
 * Class local_satool_addmilestones_form.
 *
 * @copyright 2021 Jeremy Funke
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_satool_addmilestones_form extends moodleform {
    /**
     * Definition of the addmilestones moodleform.
     */
    public function definition() {
        global $USER, $CFG, $DB;

        $mform = $this->_form;

        if (!is_array($this->_customdata)) {
            throw new coding_exception('invalid custom data for submit_proj_form');
        }

        $year = date('Y');

        $dateoptions = array(
            'startyear' => $year - 2,
            'stopyear' => $year + 2,
            'step' => 5
        );

        // Get project and project milestones if exists.
        $project = $this->_customdata['project'];
        if (isset($project->milestones)) {
            $projmilestones = json_decode($project->milestones);
        } else {
            $projmilestones = null;
        }

        // Accessibility: "Required" is bad legend text.
        $strrequired = get_string('required');

        // Set up form.
        $mform->addElement('hidden', 'id', $project->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'topic1', get_string('projmiletopic1', 'local_satool'), 'size="45"');
        $mform->setType('topic1', PARAM_TEXT);
        $mform->addRule('topic1', $strrequired, 'required', null, 'client');

        $mform->addElement('date_selector', 'topic1date', get_string('projmiletopic1date', 'local_satool'), $dateoptions);
        $mform->addRule('topic1date', $strrequired, 'required', null, 'client');

        $mform->addElement('text', 'topic2', get_string('projmiletopic2', 'local_satool'), 'size="45"');
        $mform->setType('topic2', PARAM_TEXT);

        $mform->addElement('date_selector', 'topic2date', get_string('projmiletopic2date', 'local_satool'), $dateoptions);

        $mform->addElement('text', 'topic3', get_string('projmiletopic3', 'local_satool'), 'size="45"');
        $mform->setType('topic3', PARAM_TEXT);

        $mform->addElement('date_selector', 'topic3date', get_string('projmiletopic3date', 'local_satool'), $dateoptions);

        // Add buttons.
        $this->add_action_buttons();

        // Fill form with data if project milestones exist.
        $this->set_data($projmilestones);
    }
}