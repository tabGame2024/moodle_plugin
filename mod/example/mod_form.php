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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/example/locallib.php');
require_once($CFG->libdir.'/filelib.php');

/**
 * Form for adding and editing Example instances
 *
 * @package    mod_example
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_example_mod_form extends moodleform_mod {

    public function __construct($current, $section, $cm, $course) {
        parent::__construct($current, $section, $cm, $course);
    }
    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // General fieldset.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', empty($CFG->formatstringstriptags) ? PARAM_CLEANHTML : PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        if (!empty($this->_features->introeditor)) {
            // Description element that is usually added to the General fieldset.
            $this->standard_intro_elements();
        }

        // ------
        $mform->addElement('editor', 'content', get_string('content'), ['size' => '64']);
        $mform->setType('content', PARAM_RAW);


        // Other standard elements that are displayed in their own fieldsets.
        $this->standard_grading_coursemodule_elements();
        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }
    /**
     * Summary of data_preprocessing
     * @param mixed $defaults_values
     * @return void
     */
    public function data_preprocessing(&$defaults_values) {
        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('example');
             // Initialiser les valeurs si elles ne sont pas définies
        if (!isset($defaults_values['contentformat'])) {
            $defaults_values['contentformat'] = FORMAT_HTML; // Utiliser le format par défaut approprié
        }
        if (!isset($defaults_values['content'])) {
            $defaults_values['content'] = ''; // Texte par défaut vide
        }

        $defaults_values['example']['format'] = $defaults_values['contentformat'];
        $defaults_values['example']['text']   = file_prepare_draft_area($draftitemid, $this->context->id, 'mod_example',
                'content', 0, example_get_editor_options($this->context), $defaults_values['content']);
        $defaults_values['example']['itemid'] = $draftitemid;
        }
        if (!empty($defaults_values['displayoptions'])) {
            $displayoptions = (array) unserialize_array($defaults_values['displayoptions']);
            if (isset($displayoptions['printintro'])) {
                $defaults_values['printintro'] = $displayoptions['printintro'];
            }
            if (isset($displayoptions['printlastmodified'])) {
                $defaults_values['printlastmodified'] = $displayoptions['printlastmodified'];
            }
            if (!empty($displayoptions['popupwidth'])) {
                $defaults_values['popupwidth'] = $displayoptions['popupwidth'];
            }
            if (!empty($displayoptions['popupheight'])) {
                $defaults_values['popupheight'] = $displayoptions['popupheight'];
            }
        }
    }
}
