<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Library of interface functions and constants.
 *
 * @package     mod_api
 * @copyright   2024 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 defined('MOODLE_INTERNAL') || die();
 /**
 * Function to return the plugin icon.
 *
 * @return string
 */
function mod_monplugin_get_icon() {
    global $CFG;
    return $CFG->wwwroot . '/mod/api/pix/monlogo.svg';
}

/**
 * List of features supported in api module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function api_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        case FEATURE_MOD_PURPOSE:             return MOD_PURPOSE_CONTENT;

        default: return null;
    }
}




// /**
//  * Updates an instance of the mod_api in the database.
//  *
//  * Given an object containing all the necessary data (defined in mod_form.php),
//  * this function will update an existing instance with new data.
//  *
//  * @param object $moduleinstance An object from the form in mod_form.php.
//  * @param mod_api_mod_form $mform The form.
//  * @return bool True if successful, false otherwise.
//  */
// function api_update_instance($moduleinstance, $mform = null) {
//     global $DB;

//     $moduleinstance->timemodified = time();
//     $moduleinstance->id = $moduleinstance->instance;

//     return $DB->update_record('api', $moduleinstance);
// }


// /**
//  * Removes an instance of the mod_api from the database.
//  *
//  * @param int $id Id of the module instance.
//  * @return bool True if successful, false on failure.
//  */
// function api_delete_instance($id) {
//     global $DB;

//     $exists = $DB->get_record('api', array('id' => $id));
//     if (!$exists) {
//         return false;
//     }

//     $DB->delete_records('api', array('id' => $id));

//     return true;
// }
// function api_get_editor_options($context) {
//     return array(
//         'trusttext' => true,
//         'subdirs' => false,
//         'maxfiles' => 99,
//         'maxbytes' => 0,
//         'context' => $context
//     );
// }

function api_add_instance($data, $mform) {
    global $CFG, $DB;

    require_once("$CFG->libdir/resourcelib.php");

    $cmid = $data->coursemodule;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;

    // Process editor content
    if ($mform) {
        $content_editor = $data->content_editor;
        $data->content = $data->$content_editor['text'];
        $data->contentformat = $data->$content_editor['format'];
    }

    // Insert the new instance record into the database
    $data->id = $DB->insert_record('api', $data);

    $DB->set_field('course_modules', 'instance', $data->id, array('id' => $cmid));
    $context = context_module::instance($cmid);

    if ($mform && !empty($data->content_editor['itemid'])) {
        $draftitemid = $data->content_editor['itemid'];
        $data->content = file_save_draft_area_files($draftitemid, $context->id, 'mod_api', 'content', 0, api_get_editor_options($context), $data->content);
        $DB->update_record('api', $data);
    }

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($cmid, 'api', $data->id, $completiontimeexpected);

    return $data->id;
}

function api_update_instance($data, $mform) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    $cmid        = $data->coursemodule;
    $draftitemid = $data->content_editor['itemid'];

    $data->timemodified = time();
    $data->id = $data->instance;

    $data->content       = $data->content_editor['text'];
    $data->contentformat = $data->content_editor['format'];

    $DB->update_record('api', $data);
    $context = context_module::instance($cmid);
    if ($draftitemid) {
        $data->content = file_save_draft_area_files($draftitemid, $context->id, 'mod_api', 'content', 0, api_get_editor_options($context), $data->content);
        $DB->update_record('api', $data);
    }

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($cmid, 'page', $data->id, $completiontimeexpected);

    return true;
}

function api_delete_instance($id) {
    global $DB;

    if (!$api = $DB->get_record('api', array('id' => $id))) {
        return false;
    }

    // Delete the instance record from the database
    $DB->delete_records('api', array('id' => $api->id));

    return true;
}


// /**
//  * Check if the module has any update that affects the current user since a given time.
//  *
//  * @param  cm_info $cm course module data
//  * @param  int $from the time to check updates from
//  * @param  array $filter  if we need to check only specific updates
//  * @return stdClass an object with the different type of areas indicating if they were updated or not
//  * @since Moodle 3.2
//  */
// function api_check_updates_since(cm_info $cm, $from, $filter = array()) {
//     $updates = course_check_module_updates_since($cm, $from, array('content'), $filter);
//     return $updates;
// }


// function api_bonjour($userid) {
//     global $DB;
//     $user = $DB->get_record('user', array('id' => $userid), 'firstname, lastname');
//     if ($user) {
//         return "Bonjour, {$user->firstname} {$user->lastname}";
//     } else {
//         return "Bonjour, étudiant inconnu";
//     }
// }

// function write_hello_world($text) {
//     if ($text == 'fonction Bonjour') {
//         return 'Bonjour tout le monde';
//     }
//     return $text;
// }

// /**
//  * Return if the plugin supports $feature.
//  *
//  * @param string $feature Constant representing the feature.
//  * @return true | null True if the feature is supported, null otherwise.
//  */
// function api_supports($feature) {
//     switch ($feature) {
//         case FEATURE_MOD_INTRO:
//             return true;
//         default:
//             return null;
//     }
// }


// /**
//  * Saves a new instance of the mod_api into the database.
//  *
//  * Given an object containing all the necessary data, (defined by the form
//  * in mod_form.php) this function will create a new instance and return the id
//  * number of the instance.
//  *
//  * @param object $moduleinstance An object from the form.
//  * @param mod_api_mod_form $mform The form.
//  * @return int The id of the newly inserted record.
//  */
// function api_add_instance($moduleinstance, $mform = null) {
//     global $DB;

//     $moduleinstance->timecreated = time();

//     $id = $DB->insert_record('api', $moduleinstance);

//     return $id;
// }

// /**
//  * Updates an instance of the mod_api in the database.
//  *
//  * Given an object containing all the necessary data (defined in mod_form.php),
//  * this function will update an existing instance with new data.
//  *
//  * @param object $moduleinstance An object from the form in mod_form.php.
//  * @param mod_api_mod_form $mform The form.
//  * @return bool True if successful, false otherwise.
//  */
// function api_update_instance($moduleinstance, $mform = null) {
//     global $DB;

//     $moduleinstance->timemodified = time();
//     $moduleinstance->id = $moduleinstance->instance;

//     return $DB->update_record('api', $moduleinstance);
// }

// /**
//  * Removes an instance of the mod_api from the database.
//  *
//  * @param int $id Id of the module instance.
//  * @return bool True if successful, false on failure.
//  */
// function api_delete_instance($id) {
//     global $DB;

//     $exists = $DB->get_record('api', array('id' => $id));
//     if (!$exists) {
//         return false;
//     }

//     $DB->delete_records('api', array('id' => $id));

//     return true;
// }

// // function write_hello_world($text) {
// //     if ($text == 'fonction Bonjour') {
// //         return 'Bonjour tout le monde';
// //     }
// //     return $text;
// // }

// // function mod_api_add_instance($instancedata, $mform = null) {
// //     global $DB;

// //     $instancedata->content = write_hello_world($instancedata->content);
// //     $instancedata->timecreated = time();
// //     $instancedata->timemodified = $instancedata->timecreated;

// //     return $DB->insert_record('api', $instancedata);
// // }

// // function mod_api_update_instance($instancedata, $mform = null) {
// //     global $DB;

// //     $instancedata->content = write_hello_world($instancedata->content);
// //     $instancedata->timemodified = time();
// //     $instancedata->id = $instancedata->instance;

// //     return $DB->update_record('api', $instancedata);
// // }

// // function mod_api_delete_instance($id) {
// //     global $DB;

// //     if (!$api = $DB->get_record('api', array('id' => $id))) {
// //         return false;
// //     }

// //     $DB->delete_records('api', array('id' => $api->id));

// //     return true;
// // }
// function write_hello_world($text) {
//     debugging('write_hello_world called with: ' . $text);
//     if ($text == 'fonction Bonjour') {
//         return 'Bonjour tout le monde';
//     }
//     return $text;
// }

// function mod_api_add_instance($instancedata, $mform = null) {
//     global $DB;
//     debugging('mod_api_add_instance called');
//     $instancedata->content = write_hello_world($instancedata->content);
//     $instancedata->timecreated = time();
//     $instancedata->timemodified = $instancedata->timecreated;

//     return $DB->insert_record('api', $instancedata);
// }

// function mod_api_update_instance($instancedata, $mform = null) {
//     global $DB;
//     debugging('mod_api_update_instance called');
//     $instancedata->content = write_hello_world($instancedata->content);
//     $instancedata->timemodified = time();
//     $instancedata->id = $instancedata->instance;

//     return $DB->update_record('api', $instancedata);
// }

// function api_process_text($text) {
//     if ($text !== null && strpos($text, 'fonction bonjour') !== false) {
//         return str_replace('fonction bonjour', 'Bonjour tout le monde', $text);
//     }
//     return $text;
// }

/**
 * Retourne un message de salutation.
 *
 * @return string Le message "Bonjour tout le monde".
 */
function mod_api_bonjour() {
    return "Bonjour tout le monde";
}

function api_bonjour($userid) {
    global $DB;
    $user = $DB->get_record('user', array('id' => $userid), 'firstname, lastname');
    if ($user) {
        $fullname = $user->firstname . ' ' . $user->lastname;
        return json_encode(array('message' => "Bonjour, $fullname"));
    } else {
        return json_encode(array('message' => "Bonjour, étudiant inconnu"));
    }
}

function api_bonjour_tout_le_monde() {
    return json_encode(array('message' => "Bonjour tout le monde"));
}

function api_process_text($text, $userid) {
    if (trim($text) === 'fonction bonjour') {
        return api_bonjour($userid);
    } elseif (trim($text) === 'function bonjour') {
        return api_bonjour_tout_le_monde();
    }
    return json_encode(array('message' => $text));
}


