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
 * Library of functions and constants for module label
 *
 * @package mod_label
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/** LABEL_MAX_NAME_LENGTH = 50 */
define("LABEL_MAX_NAME_LENGTH", 50);

/**
 * @uses LABEL_MAX_NAME_LENGTH
 * @param object $label
 * @return string
 */
function get_label_name($label) {
    // Return label name if not empty.
    if ($label->name) {
        return $label->name;
    }

    $context = context_module::instance($label->coursemodule);
    $intro = format_text($label->intro, $label->introformat, ['filter' => false, 'context' => $context]);
    $name = html_to_text(format_string($intro, true, ['context' => $context]));
    $name = preg_replace('/@@PLUGINFILE@@\/[[:^space:]]+/i', '', $name);
    // Remove double space and also nbsp; characters.
    $name = preg_replace('/\s+/u', ' ', $name);
    $name = trim($name);
    if (core_text::strlen($name) > LABEL_MAX_NAME_LENGTH) {
        $name = core_text::substr($name, 0, LABEL_MAX_NAME_LENGTH) . "...";
    }

    if (empty($name)) {
        // arbitrary name
        $name = get_string('modulename','label');
    }

    return $name;
}

/**
 * Summary of validate_json_input
 * @param mixed $intro
 * @return string|null
 */
function validate_json_input($intro) {

    // Afficher le contenu brut pour le débogage
    error_log("Debug JSON Input: " . var_export($intro, true));

     // Nettoyage du JSON pour enlever les caractères indésirables
     $intro = strip_tags($intro); // Supprime les balises HTML
     $intro = trim($intro); // Supprime les espaces en début et fin

     // Afficher le contenu après nettoyage
    error_log("Debug JSON Input After Cleaning: " . var_export($intro, true));

    $data = json_decode($intro, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return 'Invalid JSON: ' . json_last_error_msg();
    }

    $code = $data['code'] ?? null;
    $function = $data['function'] ?? null;
    $params = $data['params'] ?? null;

    if ($code !== 'TGV1' || $function !== 'updateUser') {
        return 'Invalid code or function';
    }

    if ($params !== 'student' && $params !== 'editor') {
        return 'Invalid params value. It must be either "student" or "editor".';
    }

    return null;
}

/**
 * Summary of call_update_user_api
 * @param mixed $jsonContent
 * @throws \moodle_exception
 * @return void
 */
function call_update_user_api($jsonContent) {
    $ch = curl_init('https://127.0.0.1:8000/api/utilisateurs/update');

    //  // Vérifiez l'URL et les données JSON avant d'exécuter cURL
    //  if (filter_var($url, FILTER_VALIDATE_URL) === false) {
    //     throw new \moodle_exception('Invalid API URL');
    // }

    // if (empty($jsonContent)) {
    //     throw new \moodle_exception('Empty JSON content');
    // }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonContent);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

    //  ignorer les problèmes de certificat SSL auto-signé
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    curl_close($ch);

    // Vérifie si cURL a rencontré une erreur
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        throw new \moodle_exception('cURL Error: ' . $error_msg);
    }

    curl_close($ch);

    if ($response === false) {
        throw new \moodle_exception('No response from API');
    }

    $apiResponse = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \moodle_exception('Invalid JSON in API response: ' . json_last_error_msg());
    }

    if (isset($apiResponse['error'])) {
        throw new \moodle_exception('API Error: ' . $apiResponse['error']);
    }
    // Si l'utilisateur a été mis à jour ou ajouté, retournez les détails
    if (isset($apiResponse['utilisateur'])) {
        $userDetails = $apiResponse['utilisateur'];
        return "Vous êtes inscrit au cours. Dernière connexion : " . $userDetails['lastAccess'];
    }

     // Retourne toujours ce message si tout s'est bien passé
     return 'Vous êtes inscrit au cours';
}

/**
 * Summary of call_upload_api
 * @param mixed $jsonContent
 * @return mixed
 */
function call_upload_api($jsonContent) {
    $url = 'http://127.0.0.1:8000/api/spreadsheet/upload';  // Votre URL API

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonContent);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

    $response = curl_exec($ch);

    // Vérifiez si cURL a rencontré une erreur
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return 'cURL Error: ' . $error_msg;
    }

    curl_close($ch);

    if ($response === false) {
        return 'No response from API';
    }

    $apiResponse = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return 'Invalid JSON in API response: ' . json_last_error_msg();
    }

    if (isset($apiResponse['error'])) {
        return 'API Error: ' . $apiResponse['error'];
    }

    return $apiResponse['message'] ?? 'Unknown error';
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @param object $label
 * @return bool|int
 */
function label_add_instance($label) {
    global $DB;

    // Si la validation passe, continuez avec l'insertion
    $label->name = get_label_name($label);
    $label->timemodified = time();


    $id = $DB->insert_record("label", $label);

    $completiontimeexpected = !empty($label->completionexpected) ? $label->completionexpected : null;
    \core_completion\api::update_completion_date_event($label->coursemodule, 'label', $id, $completiontimeexpected);


    return $id;
}

/**
 * Sets the special label display on course page.
 *
 * @param cm_info $cm Course-module object
 */
function label_cm_info_view(cm_info $cm) {
    $cm->set_custom_cmlist_item(true);
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $label
 * @return bool
 */
function label_update_instance($label) {
    global $DB;



    $label->name = get_label_name($label);
    $label->timemodified = time();
    $label->id = $label->instance;

    $completiontimeexpected = !empty($label->completionexpected) ? $label->completionexpected : null;
    \core_completion\api::update_completion_date_event($label->coursemodule, 'label', $label->id, $completiontimeexpected);



    return $DB->update_record("label", $label);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id
 * @return bool
 */
function label_delete_instance($id) {
    global $DB;

    if (! $label = $DB->get_record("label", array("id"=>$id))) {
        return false;
    }

    $result = true;

    $cm = get_coursemodule_from_instance('label', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'label', $label->id, null);

    if (! $DB->delete_records("label", array("id"=>$label->id))) {
        $result = false;
    }

    return $result;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 *
 * @global object
 * @param object $coursemodule
 * @return cached_cm_info|null
 */
// function label_get_coursemodule_info($coursemodule) {
//     global $DB;

//     if ($label = $DB->get_record('label', array('id'=>$coursemodule->instance), 'id, name, intro, introformat')) {
//         if (empty($label->name)) {
//             // label name missing, fix it
//             $label->name = "label{$label->id}";
//             $DB->set_field('label', 'name', $label->name, array('id'=>$label->id));
//         }
//         $info = new cached_cm_info();
//         // no filtering hre because this info is cached and filtered later
//         $info->content = format_module_intro('label', $label, $coursemodule->id, false);
//         $info->name  = $label->name;
//         return $info;
//     } else {
//         return null;
//     }
// }
 /** Fonctionne à exporter */
// function label_get_coursemodule_info($coursemodule) {
//     global $DB, $USER;

//     if ($label = $DB->get_record('label', array('id'=>$coursemodule->instance), 'id, name, intro, introformat')) {
//         if (empty($label->name)) {
//             // label name missing, fix it
//             $label->name = "label{$label->id}";
//             $DB->set_field('label', 'name', $label->name, array('id'=>$label->id));
//         }

//         // Récupération du nom d'utilisateur et du nom du cours
//         $username = urlencode($USER->username);
//         $courseid = $coursemodule->course;
//         $coursename = urlencode(get_course($courseid)->shortname); // ou fullname selon vos besoins

//         // Construction de l'URL avec les paramètres GET
//         $url = new moodle_url('https://www.upjv.info/st2024/djeneba/functs/tgaccueil.php', array(
//             'user' => $username,
//             'classe' => $coursename,
//             'userid' => $USER->id,
//             'ts' => time() // Ajout d'un timestamp pour forcer le rafraîchissement

//         ));

//         // Construction du contenu du label avec l'iframe
//         $info = new cached_cm_info();
//         $iframe = '<iframe src="' . $url->out() . '" width="100%" height="600px"></iframe>';
//         $info->content = $iframe;
//         $info->name  = $label->name;


//         return $info;
//     } else {
//         return null;
//     }
// }
/** Fin Fonctionne à exporter */

/** Aujourd'hui : */
// function label_get_coursemodule_info($coursemodule) {
//     global $DB, $USER;

//     if ($label = $DB->get_record('label', array('id' => $coursemodule->instance), 'id, name, intro, introformat')) {
//         if (empty($label->name)) {
//             // label name missing, fix it
//             $label->name = "label{$label->id}";
//             $DB->set_field('label', 'name', $label->name, array('id' => $label->id));
//         }

//         // Valider le contenu JSON dans le champ `intro`
//         $error = validate_json_input($label->intro);
//         $message = '';
//         if ($error) {
//             $message = "<div style='color:red;'><strong>Error: $error</strong></div>";
//         } else {
//             // Appel à l'API pour obtenir le message de confirmation
//             $message = "<div style='color:green;'><strong>" . call_update_user_api($label->intro) . "</strong></div>";
//         }
//         // Récupération du nom d'utilisateur et du nom du cours
//         $username = urlencode($USER->firstname);
//         $courseid = $coursemodule->course;
//         $coursename = urlencode(get_course($courseid)->shortname);

//         // Construction de l'URL avec les paramètres GET, incluant userid et username
//         $url = new moodle_url('https://www.upjv.info/st2024/djeneba/functs/tgaccueil.php', array(
//             'user' => $username,
//             'classe' => $coursename,
//             'userid' => $USER->id
//         ));

//         // Construction du contenu du label avec l'iframe et des données spécifiques à l'utilisateur
//         $info = new cached_cm_info();
//         $iframe = '<iframe src="' . $url->out() . '" width="100%" height="600px"></iframe>';

//         // Ajout de contenu dynamique au label
//         $custom_content = "<div>Bienvenue, {$USER->firstname} {$USER->lastname}!</div>";
//         $info->content = $custom_content . $iframe;
//         $info->name = $label->name;

//         return $info;
//     } else {
//         return null;
//     }
// }

/** Fin Aujourd'hui */

// TODO: decommente this
// function label_get_coursemodule_info($coursemodule) {
//     global $DB, $USER;

//     if ($label = $DB->get_record('label', array('id' => $coursemodule->instance), 'id, name, intro, introformat')) {
//         if (empty($label->name)) {
//             // Le nom du label est manquant, on le corrige
//             $label->name = "label{$label->id}";
//             $DB->set_field('label', 'name', $label->name, array('id' => $label->id));
//         }

//         // Valider le contenu JSON dans le champ `intro`
//         $error = validate_json_input($label->intro);
//         $message = '';
//         if ($error) {
//             $message = "<div style='color:red;'><strong>Error: $error</strong></div>";
//         } else {
//             // Appel à l'API pour obtenir le message de confirmation
//             $message = "<div style='color:green;'><strong>" . call_update_user_api($label->intro) . "</strong></div>";
//         }

//         // Récupération du nom d'utilisateur et du nom du cours
//         $username = urlencode($USER->firstname);
//         $courseid = $coursemodule->course;
//         $coursename = urlencode(get_course($courseid)->shortname); // ou fullname selon vos besoins

//         // Construction de l'URL avec les paramètres GET, incluant userid et username
//         $url = new moodle_url('https://www.upjv.info/st2024/djeneba/functs/tgaccueil.php', array(
//             'user' => $username,
//             'classe' => $coursename,
//             'userid' => $USER->id
//         ));

//         // Construction du contenu du label avec l'iframe et des données spécifiques à l'utilisateur
//         $info = new cached_cm_info();
//         $iframe = '<iframe src="' . $url->out() . '" width="100%" height="600px"></iframe>';

//         // Ajout du message API et contenu dynamique au label
//         $custom_content = "<div>Bienvenue, {$USER->firstname} {$USER->lastname}!</div>" . $message;
//         $info->content = $custom_content . $iframe;
//         $info->name = $label->name;

//         return $info;
//     } else {
//         return null;
//     }
// }
// End decommented

//Fonctionne

function label_get_coursemodule_info($coursemodule) {
    global $DB, $USER;

    if ($label = $DB->get_record('label', array('id' => $coursemodule->instance), 'id, name, intro, introformat')) {
        if (empty($label->name)) {
            // Le nom du label est manquant, on le corrige
            $label->name = "label{$label->id}";
            $DB->set_field('label', 'name', $label->name, array('id' => $label->id));
        }

        // Validation et transformation du JSON dans le champ `intro`
        $transformed_json = validate_and_transform_json($label->intro, $USER->email);
        $message = '';

        // Gestion des erreurs de validation
        if (strpos($transformed_json, 'Invalid') === 0) {
            $message = "<div style='color:red;'><strong>Error: $transformed_json</strong></div>";
        } else {
            // Appel à l'API pour obtenir le message de confirmation
            $api_response = call_update_user_api($transformed_json);

            if ($api_response) {
                $message = "<div style='color:green;'><strong>$api_response</strong></div>";
            } else {
                $message = "<div style='color:red;'><strong>Error: No response from API.</strong></div>";
            }
        }

        // Récupération du nom d'utilisateur et du nom du cours
        $username = urlencode($USER->firstname);
        $courseid = $coursemodule->course;
        $coursename = urlencode(get_course($courseid)->shortname); // ou fullname selon vos besoins

        // Construction de l'URL avec les paramètres GET, incluant userid et username
        $url = new moodle_url('https://www.upjv.info/st2024/djeneba/functs/tgaccueil.php', array(
            'user' => $username,
            'classe' => $coursename,
            'userid' => $USER->id
        ));

        // Construction du contenu du label avec l'iframe et des données spécifiques à l'utilisateur
        $info = new cached_cm_info();
        $iframe = '<iframe src="' . $url->out() . '" width="100%" height="600px"></iframe>';

        // Ajout du message API et contenu dynamique au label
        $custom_content = "<div>Bienvenue, {$USER->firstname} {$USER->lastname}!</div>" . $message;
        $info->content = $custom_content . $iframe;
        $info->name = $label->name;

        return $info;
    } else {
        return null;
    }
}

// Fin fonctionne


function validate_and_transform_json($intro) {
    global $USER;
    // Afficher le contenu brut pour le débogage
    error_log("Debug JSON Input: " . var_export($intro, true));

    // Nettoyage du JSON pour enlever les caractères indésirables
    $intro = strip_tags($intro); // Supprime les balises HTML
    $intro = trim($intro); // Supprime les espaces en début et fin

    // Afficher le contenu après nettoyage
    error_log("Debug JSON Input After Cleaning: " . var_export($intro, true));

    // Décodage du JSON
    $data = json_decode($intro, true);

    // Vérifier les erreurs de décodage
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON Decode Error: " . json_last_error_msg()); // Ajouter un log d'erreur spécifique
        return 'Invalid JSON: ' . json_last_error_msg();
    }

    // Vérification des champs 'code', 'function', et 'params'
    $code = $data['code'] ?? null;
    $function = $data['function'] ?? null;
    $params = $data['params'] ?? null;

    if ($code !== 'TGV1' || $function !== 'updateUser') {
        return 'Invalid code or function';
    }

    // Si `params` est une simple chaîne de caractères, transformez-le en objet
    if (is_string($params)) {
        $role = strtolower($params);
        if ($role !== 'editor' && $role !== 'student') {
            return 'Invalid role value. It must be either "editor" or "student".';
        }

        // Transformation du JSON pour inclure l'email et le rôle
        $data['params'] = [
            'email' => $USER->email, // Utilisez l'email de l'utilisateur Moodle connecté
            'role' => $role
        ];
    } else {
        return 'Invalid params structure. It must be a simple string like "editor" or "student".';
    }

    // Reconvertir en JSON le tableau transformé
    $transformed_json = json_encode($data);

    // Afficher le JSON transformé pour le débogage
    error_log("Transformed JSON: " . var_export($transformed_json, true));

    // Retourner le JSON transformé
    return $transformed_json;
}


/** Redirection de la page */
// function label_get_coursemodule_info($coursemodule) {
//     global $DB, $USER;

//     if ($label = $DB->get_record('label', array('id'=>$coursemodule->instance), 'id, name, intro, introformat')) {
//         if (empty($label->name)) {
//             // label name missing, fix it
//             $label->name = "label{$label->id}";
//             $DB->set_field('label', 'name', $label->name, array('id'=>$label->id));
//         }

//         // Récupération du nom d'utilisateur et du nom du cours
//         $username = urlencode($USER->username);
//         $courseid = $coursemodule->course;
//         $coursename = urlencode(get_course($courseid)->shortname); // ou fullname selon vos besoins

//         // Construction de l'URL avec les paramètres GET
//         $url = new moodle_url('https://www.upjv.info/st2024/djeneba/functs/tgaccueil.php', array(
//             'user' => $username,
//             'classe' => $coursename
//         ));

//         // Redirection immédiate vers l'URL
//         redirect($url);
//     } else {
//         return null;
//     }
// }
/** Fin Redirection de la page */

/** Avec du Javascript */
// function label_get_coursemodule_info($coursemodule) {
//     global $DB, $USER, $SESSION;

//     if ($label = $DB->get_record('label', array('id'=>$coursemodule->instance), 'id, name, intro, introformat')) {
//         if (empty($label->name)) {
//             // Nom du label manquant, le corriger
//             $label->name = "label{$label->id}";
//             $DB->set_field('label', 'name', $label->name, array('id'=>$label->id));
//         }

//         // Récupération des informations utilisateur et cours
//         $username = urlencode($USER->username);
//         $courseid = $coursemodule->course;
//         $coursename = urlencode(get_course($courseid)->shortname);

//         // Vérifier si l'utilisateur a changé
//         if (!isset($SESSION->last_username) || $SESSION->last_username !== $USER->username) {
//             // Mettre à jour la session avec le nouvel utilisateur
//             $SESSION->last_username = $USER->username;
//             $SESSION->last_coursename = $coursename;

//             // Marquer la session comme nécessitant une régénération
//             $SESSION->force_regenerate = true;
//         } else {
//             // Pas de changement d'utilisateur
//             $SESSION->force_regenerate = false;
//         }

//         // Construction de l'URL avec des paramètres GET uniques
//         $url = new moodle_url('https://www.upjv.info/st2024/djeneba/functs/tgaccueil.php', array(
//             'user' => $username,
//             'classe' => $coursename,
//             'time' => time() // Paramètre unique pour éviter le cache
//         ));

//         // Génération de l'iframe avec l'URL
//         $info = new cached_cm_info();

//         // Si l'utilisateur a changé ou si nous forçons la régénération
//         if ($SESSION->force_regenerate) {
//             $iframe = '<iframe src="' . $url->out() . '" width="100%" height="600px" style="border:none;"></iframe>';
//         } else {
//             // Sinon, utiliser le cache (si pertinent)
//             $iframe = '<iframe src="' . $url->out() . '" width="100%" height="600px" style="border:none;"></iframe>';
//         }

//         $info->content = $iframe;
//         $info->name  = $label->name;

//         return $info;
//     } else {
//         return null;
//     }
// }

/** Fin Avec du Javascript */
/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function label_reset_userdata($data) {

    // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
    // See MDL-9367.

    return array();
}

/**
 * @uses FEATURE_IDNUMBER
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function label_supports($feature) {
    switch($feature) {
        case FEATURE_IDNUMBER:                return true;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_NO_VIEW_LINK:            return true;
        case FEATURE_MOD_PURPOSE:             return MOD_PURPOSE_CONTENT;

        default: return null;
    }
}

/**
 * Register the ability to handle drag and drop file uploads
 * @return array containing details of the files / types the mod can handle
 */
function label_dndupload_register() {
    $strdnd = get_string('dnduploadlabel', 'mod_label');
    if (get_config('label', 'dndmedia')) {
        $mediaextensions = file_get_typegroup('extension', ['web_image', 'web_video', 'web_audio']);
        $files = array();
        foreach ($mediaextensions as $extn) {
            $extn = trim($extn, '.');
            $files[] = array('extension' => $extn, 'message' => $strdnd);
        }
        $ret = array('files' => $files);
    } else {
        $ret = array();
    }

    $strdndtext = get_string('dnduploadlabeltext', 'mod_label');
    return array_merge($ret, array('types' => array(
        array('identifier' => 'text/html', 'message' => $strdndtext, 'noname' => true),
        array('identifier' => 'text', 'message' => $strdndtext, 'noname' => true)
    )));
}

/**
 * Handle a file that has been uploaded
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 */
function label_dndupload_handle($uploadinfo) {
    global $USER;

    // Gather the required info.
    $data = new stdClass();
    $data->course = $uploadinfo->course->id;
    $data->name = $uploadinfo->displayname;
    $data->intro = '';
    $data->introformat = FORMAT_HTML;
    $data->coursemodule = $uploadinfo->coursemodule;

    // Extract the first (and only) file from the file area and add it to the label as an img tag.
    if (!empty($uploadinfo->draftitemid)) {
        $fs = get_file_storage();
        $draftcontext = context_user::instance($USER->id);
        $context = context_module::instance($uploadinfo->coursemodule);
        $files = $fs->get_area_files($draftcontext->id, 'user', 'draft', $uploadinfo->draftitemid, '', false);
        if ($file = reset($files)) {
            if (file_mimetype_in_typegroup($file->get_mimetype(), 'web_image')) {
                // It is an image - resize it, if too big, then insert the img tag.
                $config = get_config('label');
                $data->intro = label_generate_resized_image($file, $config->dndresizewidth, $config->dndresizeheight);
            } else {
                // We aren't supposed to be supporting non-image types here, but fallback to adding a link, just in case.
                $url = moodle_url::make_draftfile_url($file->get_itemid(), $file->get_filepath(), $file->get_filename());
                $data->intro = html_writer::link($url, $file->get_filename());
            }
            $data->intro = file_save_draft_area_files($uploadinfo->draftitemid, $context->id, 'mod_label', 'intro', 0,
                                                      null, $data->intro);
        }
    } else if (!empty($uploadinfo->content)) {
        $data->intro = $uploadinfo->content;
        if ($uploadinfo->type != 'text/html') {
            $data->introformat = FORMAT_PLAIN;
        }
    }

    return label_add_instance($data, null);
}

/**
 * Resize the image, if required, then generate an img tag and, if required, a link to the full-size image
 * @param stored_file $file the image file to process
 * @param int $maxwidth the maximum width allowed for the image
 * @param int $maxheight the maximum height allowed for the image
 * @return string HTML fragment to add to the label
 */
function label_generate_resized_image(stored_file $file, $maxwidth, $maxheight) {
    global $CFG;

    $fullurl = moodle_url::make_draftfile_url($file->get_itemid(), $file->get_filepath(), $file->get_filename());
    $link = null;
    $attrib = array('alt' => $file->get_filename(), 'src' => $fullurl);

    if ($imginfo = $file->get_imageinfo()) {
        // Work out the new width / height, bounded by maxwidth / maxheight
        $width = $imginfo['width'];
        $height = $imginfo['height'];
        if (!empty($maxwidth) && $width > $maxwidth) {
            $height *= (float)$maxwidth / $width;
            $width = $maxwidth;
        }
        if (!empty($maxheight) && $height > $maxheight) {
            $width *= (float)$maxheight / $height;
            $height = $maxheight;
        }

        $attrib['width'] = $width;
        $attrib['height'] = $height;

        // If the size has changed and the image is of a suitable mime type, generate a smaller version
        if ($width != $imginfo['width']) {
            $mimetype = $file->get_mimetype();
            if ($mimetype === 'image/gif' or $mimetype === 'image/jpeg' or $mimetype === 'image/png') {
                require_once($CFG->libdir.'/gdlib.php');
                $data = $file->generate_image_thumbnail($width, $height);

                if (!empty($data)) {
                    $fs = get_file_storage();
                    $record = array(
                        'contextid' => $file->get_contextid(),
                        'component' => $file->get_component(),
                        'filearea'  => $file->get_filearea(),
                        'itemid'    => $file->get_itemid(),
                        'filepath'  => '/',
                        'filename'  => 's_'.$file->get_filename(),
                    );
                    $smallfile = $fs->create_file_from_string($record, $data);

                    // Replace the image 'src' with the resized file and link to the original
                    $attrib['src'] = moodle_url::make_draftfile_url($smallfile->get_itemid(), $smallfile->get_filepath(),
                                                                    $smallfile->get_filename());
                    $link = $fullurl;
                }
            }
        }

    } else {
        // Assume this is an image type that get_imageinfo cannot handle (e.g. SVG)
        $attrib['width'] = $maxwidth;
    }

    $attrib['class'] = "img-fluid";
    $img = html_writer::empty_tag('img', $attrib);
    if ($link) {
        return html_writer::link($link, $img);
    } else {
        return $img;
    }
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function label_check_updates_since(cm_info $cm, $from, $filter = array()) {
    $updates = course_check_module_updates_since($cm, $from, array(), $filter);
    return $updates;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_label_core_calendar_provide_event_action(calendar_event $event,
                                                      \core_calendar\action_factory $factory,
                                                      int $userid = 0) {
    $cm = get_fast_modinfo($event->courseid, $userid)->instances['label'][$event->instance];

    if (!$cm->uservisible) {
        // The module is not visible to the user for any reason.
        return null;
    }

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/label/view.php', ['id' => $cm->id]),
        1,
        true
    );
}
