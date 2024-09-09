<?php

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/api/locallib.php');
require_once($CFG->libdir.'/filelib.php');

class mod_api_mod_form extends moodleform_mod {

    function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'modulename', 'mod_api');

        // Adding the standard "intro" and "introformat" fields
        $this->standard_intro_elements();

        // Adding the "content" field as an editor
        $mform->addElement('header', 'contentsection', get_string('contentheader', 'api'));
        $mform->addElement('editor', 'content_editor', get_string('content', 'api'), null, api_get_editor_options($this->context));
        $mform->setType('content_editor', PARAM_RAW);
        $mform->addRule('content_editor', get_string('required'), 'required', null, 'client');

        // Add standard elements
        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    /**
     * Enforce defaults here.
     *
     * @param array $defaultvalues Form defaults
     * @return void
     **/
    function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);

        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('content_editor');

            if (isset($defaultvalues['content_editor']['text'])) {
                $defaultvalues['content_editor']['itemid'] = $draftitemid;
                $defaultvalues['content_editor']['format'] = $defaultvalues['content_editor']['format'];
            } else {
                $defaultvalues['content_editor'] = array(
                    'text' => '',
                    'format' => FORMAT_HTML,
                    'itemid' => $draftitemid,
                );
            }
        }
    }
}
