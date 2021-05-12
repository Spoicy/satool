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
 * Class local_satool_uploaddocs_form.
 *
 * @copyright 2021 Jeremy Funke
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_satool_uploaddocs_form extends moodleform {
    /**
     * Definition of the uploaddocs moodleform.
     */
    public function definition() {
        global $USER, $CFG;

        $mform = $this->_form;

        if (!is_array($this->_customdata)) {
            throw new coding_exception('invalid custom data for docs_upload_form');
        }

        // Fetch document if exists.
        $document = $this->_customdata['document'];

        // Setup accepted files types.
        $filetypes = array('.pdf', '.docx', '.xlsx', '.png', '.jpg', '.csv', '.svg', '.txt', '.zip', '.rar',
            '.7z', '.tar.gz', '.tar', '.xml', '.gif', '.json');

        // Accessibility: "Required" is bad legend text.
        $strrequired = get_string('required');

        // Set up form.
        $mform->addElement('hidden', 'type', $document->type);
        $mform->setType('type', PARAM_INT);
        $mform->addElement('hidden', 'projectid', $document->projectid);
        $mform->setType('projectid', PARAM_INT);
        $mform->addElement('hidden', 'fileid', $document->fileid);
        $mform->setType('fileid', PARAM_INT);

        $mform->addElement('text', 'title', get_string('projfilestitle', 'local_satool'), 'size="45"');
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', $strrequired, 'required', null, 'client');

        $mform->addElement('textarea', 'note', get_string('projfilesnote', 'local_satool'),
            'wrap="virtual" rows="5" cols="45"');
        $mform->setType('note', PARAM_TEXT);

        // Display filemanager or link, depending on the document type.
        if ($document->type) {
            $mform->addElement('filemanager', 'projfiles_filemanager', get_string('projfiles', 'local_satool'), null, [
                'maxfiles' => 1,
                'accepted_types' => $filetypes
            ]);
            $mform->setType('projfiles_filemanager', PARAM_FILE);
            $mform->addRule('projfiles_filemanager', $strrequired, 'required', null, 'client');
        } else {
            $mform->addElement('text', 'link', get_string('projfileslink', 'local_satool'), 'size="45"');
            $mform->setType('link', PARAM_TEXT);
            $mform->addRule('link', $strrequired, 'required', null, 'client');
        }

        // Add buttons.
        $this->add_action_buttons();

        // Fill form with data if document exists.
        $this->set_data($document);
    }
}