<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * tabgame module
 *
 * @package mod_tabgame
 * @copyright  2003 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once($CFG->dirroot . '/mod/tabgame/locallib.php'); // Inclure la fonction send_data_to_api

$id = optional_param('id', 0, PARAM_INT);    // Course Module ID, or
$l = optional_param('l', 0, PARAM_INT);     // tabgame ID

if ($id) {
    $PAGE->set_url('/mod/tabgame/view.php', array('id' => $id));
    if (!$cm = get_coursemodule_from_id('tabgame', $id, 0, true)) {
        throw new \moodle_exception('invalidcoursemodule');
    }

    if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
        throw new \moodle_exception('coursemisconf');
    }

    if (!$tabgame = $DB->get_record("tabgame", array("id" => $cm->instance))) {
        throw new \moodle_exception('invalidcoursemodule');
    }

} else {
    $PAGE->set_url('/mod/tabgame/view.php', array('l' => $l));
    if (!$tabgame = $DB->get_record("tabgame", array("id" => $l))) {
        throw new \moodle_exception('invalidcoursemodule');
    }
    if (!$course = $DB->get_record("course", array("id" => $tabgame->course))) {
        throw new \moodle_exception('coursemisconf');
    }
    if (!$cm = get_coursemodule_from_instance("tabgame", $tabgame->id, $course->id, true)) {
        throw new \moodle_exception('invalidcoursemodule');
    }
}

require_login($course, true, $cm);

// Check if the label content is a URL
if (filter_var($tabgame->intro, FILTER_VALIDATE_URL)) {
    // If the content is a URL, display it in an iframe
    echo $OUTPUT->header();
    echo html_writer::tag('iframe', '', [
        'src' => $tabgame->intro,
        'width' => '100%',
        'height' => '600px',
        'frameborder' => '0',
        'style' => 'border:none;'
    ]);
    echo $OUTPUT->footer();
} else {
    // Otherwise, handle the content as usual
    $url = course_get_url($course, $cm->sectionnum, []);
    $url->set_anchor('module-' . $id);

redirect($url);

}

// $context = context_module::instance($cm->id);
// $PAGE->set_context($context);
// $PAGE->set_url('/mod/tabgame/view.php', array('id' => $cm->id));
// $PAGE->set_title(format_string($tabgame->name));
// $PAGE->set_heading(format_string($course->fullname));

// echo $OUTPUT->header();
// echo $OUTPUT->heading($tabgame->name);

// Récupérer et traiter la description du module pour extraire le JSON
// $intro = $tabgame->intro;
// $json_data = modify_content($intro);

// if ($json_data) {
//     // Afficher le JSON modifié pour débogage
//     echo $OUTPUT->box_start();
//     echo '<p>JSON Data to be sent:</p>';
//     echo '<pre>' . htmlspecialchars($json_data) . '</pre>';
//     echo $OUTPUT->box_end();

//     // Envoyer les données à l'API
//     $response = send_data_to_api($json_data);

//     // Afficher la réponse de l'API
//     echo $OUTPUT->box_start();
//     echo '<p>' . get_string('apiresponse', 'tabgame') . ':</p>';
//     echo '<pre>' . htmlspecialchars($response) . '</pre>';
//     echo $OUTPUT->box_end();
// } else {
//     echo $OUTPUT->notification('No valid JSON data found or JSON structure is incorrect.', 'error');
// }

// echo $OUTPUT->footer();

// /**
//  * Modifie le contenu JSON si nécessaire.
//  *
//  * @param string $intro
//  * @return string|false
//  */
// function modify_content($intro) {
//     // Regex pour extraire le JSON
//     $pattern = '/\{(?:[^{}]|(?R))*\}/';
//     if (preg_match($pattern, $intro, $matches)) {
//         $json_data = $matches[0];

//         // Décoder les données JSON pour modification
//         $data = json_decode($json_data, true);
//         if (json_last_error() === JSON_ERROR_NONE) {
//             // Modifier les données JSON si elles correspondent à la structure spécifiée
//             if ($data['code'] === 'TGV1' && $data['function'] === 'updateUser') {
//                 global $USER;
//                 $user_email = $USER->email;

//                 $data['params'] = [
//                     'email' => $user_email,
//                     'role' => $data['params']
//                 ];

//                 // Encoder les données JSON modifiées
//                 return json_encode($data, JSON_PRETTY_PRINT);
//             }
//         }
//     }
//     return false;
// }
