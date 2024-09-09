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
 * Add tabgame form
 *
 * @package mod_tabgame
 * @copyright  2006 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_tabgame_mod_form extends moodleform_mod
{

    function definition()
    {
        global $PAGE;

        $PAGE->force_settings_menu();

        $mform = $this->_form;

        $mform->addElement('header', 'generalhdr', get_string('general'));

        // Add element for name.
        $mform->addElement('text', 'name', get_string('tabgamename', 'tabgame'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addHelpButton('name', 'tabgamename', 'tabgame');

        $this->standard_intro_elements(get_string('tabgametext', 'tabgame'));


        // Ajout d'un champ pour l'URL de la page stylisée
// $mform->addElement('text', 'contenturl', get_string('contenturl', 'tabgame'), array('size' => '64'));
// $mform->setType('contenturl', PARAM_URL);
// $mform->addRule('contenturl', null, 'required', null, 'client');

// // Ajout d'un champ pour les paramètres supplémentaires
// $mform->addElement('text', 'additionalparams', get_string('additionalparams', 'tabgame'), array('size' => '64'));
// $mform->setType('additionalparams', PARAM_RAW);
// $mform->setDefault('additionalparams', ''); // Paramètres supplémentaires facultatifs
// $mform->addHelpButton('additionalparams', 'additionalparams', 'tabgame');


// // tabgame does not add "Show description" checkbox meaning that 'intro' is always shown on the course page.
        // $mform->addElement('hidden', 'showdescription', 1);
        // $mform->setType('showdescription', PARAM_INT);


        // $mform->addElement('text', 'contenturl', get_string('contenturl', 'tabgame'), array('size' => '64'));
        // $mform->setType('contenturl', PARAM_URL);
        // $mform->addRule('contenturl', null, 'required', null, 'client');

        $this->standard_coursemodule_elements();

        //-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons(true, false, null);
    }

    /**
     * Override validation in order to make name field non-required.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files)
    {
        $errors = parent::validation($data, $files);
        // Name field should not be required.
        if (array_key_exists('name', $errors)) {
            if ($errors['name'] === get_string('required')) {
                unset($errors['name']);
            }
        }
        return $errors;
    }

    // mod_form.php
    public function data_preprocessing(&$default_values) {
        global $DB;

        if (!empty($this->_instance)) {
            $tabgame = $DB->get_record('tabgame', array('id' => $this->_instance), 'id, intro');
            $default_values['intro'] = $tabgame->intro;
        }

        parent::data_preprocessing($default_values);
    }


}
