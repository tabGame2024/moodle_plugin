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
 * Page module version information
 *
 * @package mod_page2
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/page2/lib.php');
require_once($CFG->dirroot.'/mod/page2/locallib.php');
require_once($CFG->libdir.'/completionlib.php');

$id      = optional_param('id', 0, PARAM_INT); // Course Module ID
$p       = optional_param('p', 0, PARAM_INT);  // Page instance ID
$inpopup = optional_param('inpopup', 0, PARAM_BOOL);
// $format = optional_param('format', '', PARAM_ALPHA);


if ($p) {
    if (!$page2 = $DB->get_record('page2', array('id'=>$p))) {
        throw new \moodle_exception('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('page2', $page2->id, $page2->course, false, MUST_EXIST);

} else {
    if (!$cm = get_coursemodule_from_id('page2', $id)) {
        throw new \moodle_exception('invalidcoursemodule');
    }
    $page2 = $DB->get_record('page2', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/page2:view', $context);

// Completion and trigger events.
page_view($page2, $course, $cm, $context);


$PAGE->set_url('/mod/page2/view.php', array('id' => $cm->id));

$options = empty($page2->displayoptions) ? [] : (array) unserialize_array($page2->displayoptions);

$activityheader = ['hidecompletion' => false];
if (empty($options['printintro'])) {
    $activityheader['description'] = '';
}

if ($inpopup and $page2->display == RESOURCELIB_DISPLAY_POPUP) {
    $PAGE->set_pagelayout('popup');
    $PAGE->set_title($course->shortname.': '.$page2->name);
    $PAGE->set_heading($course->fullname);
} else {
    $PAGE->add_body_class('limitedwidth');
    $PAGE->set_title($course->shortname.': '.$page2->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($page2);
    if (!$PAGE->activityheader->is_title_allowed()) {
        $activityheader['title'] = "";
    }
}
$PAGE->activityheader->set_attrs($activityheader);

// if ($format === 'json') {
//     if (strpos($page2->content, 'function') !== false) {
//         // Appeler la fonction pour obtenir le message JSON
//         header('Content-Type: application/json');
//         echo get_bonjour_message();
//         exit;
//     } else {
//         // Si la fonction n'est pas trouvée
//         header('Content-Type: application/json');
//         echo json_encode(array('error' => 'La fonction "bonjouruser" n\'a pas été trouvée dans le contenu.'));
//         exit;
//     }
// }

echo $OUTPUT->header();
$content = file_rewrite_pluginfile_urls($page2->content, 'pluginfile.php', $context->id, 'mod_page2', 'content', $page2->revision);
$formatoptions = new stdClass;
$formatoptions->noclean = true;
$formatoptions->overflowdiv = true;
$formatoptions->context = $context;
$content = format_text($content, $page2->contentformat, $formatoptions);

// Créer une méthode $content = tgModifContent($content, $USER, $Course...) ensuite les if à l'interieur ( l'api doit avoir accès au content et lui envoyer les informations à afficher en fonction de cela)
// Ajout : Vérifier et afficher "Bonjour tout le monde" si la balise spéciale est présente
if (strpos($content, 'function bonjour') !== false) {
    $content = str_replace('function bonjour', '', $content); // Supprimer la balise spéciale
    echo html_writer::div('Bonjour tout le monde', 'bonjour');
} else if(strpos($content, 'function user') !== false){
    $content =  str_replace('function user', '', $content);
    $message = get_bonjour_message();
    echo html_writer::div('<pre>' .$message . '</pre>', 'function user');
} else if(strpos($content, 'function api') !== false){
    // Supprimer "function api" du contenu pour éviter l'affichage dans la page
    $content = str_replace('function api', '', $content);

    // Récupérer les étudiants depuis l'API
    $etudiants = get_etudiants_from_api();
     // Afficher les informations des étudiants
     if (is_array($etudiants) && !empty($etudiants)) {
        $etudiants_html = '<h2>Liste des étudiants</h2><ul>';
        foreach ($etudiants as $etudiant) {
            if (isset($etudiant['nom']) && isset($etudiant['prenom'])) {
                $etudiants_html .= '<li>' . htmlspecialchars($etudiant['nom']) . ' ' . htmlspecialchars($etudiant['prenom']) . '</li>';
            } else {
                $etudiants_html .= '<li>Données d\'étudiant non valides.</li>';
            }
        }
        $etudiants_html .= '</ul>';
        // Ajouter le HTML généré pour les étudiants au contenu
        $content .= $etudiants_html;
    } else {
        $content .= '<p>Aucun étudiant trouvé ou erreur lors de la récupération des données.</p>';
    }
}

echo $OUTPUT->box($content, "generalbox center clearfix");


if (!isset($options['printlastmodified']) || !empty($options['printlastmodified'])) {
    $strlastmodified = get_string("lastmodified");
    echo html_writer::div("$strlastmodified: " . userdate($page2->timemodified), 'modified');
}

echo $OUTPUT->footer();
