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
 * Private page2 module utility functions
 *
 * @package mod_page2
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/page2/lib.php");


/**
 * File browsing support class
 */
class page2_content_file_info extends file_info_stored {
    public function get_parent() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->browser->get_file_info($this->context);
        }
        return parent::get_parent();
    }
    public function get_visible_name() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->topvisiblename;
        }
        return parent::get_visible_name();
    }
}


// Methode appelé dans view.php
function page2_get_editor_options($context) {
    global $CFG;
    return array('subdirs'=>1, 'maxbytes'=>$CFG->maxbytes, 'maxfiles'=>-1, 'changeformat'=>1, 'context'=>$context, 'noclean'=>1, 'trusttext'=>0);
}

/**
 * Summary of get_bonjour_message
 * @return bool|string
 */
function get_bonjour_message() {
    global $USER, $DB, $PAGE;


    $fullname = fullname($USER); // Récupère le nom complet de l'utilisateur connecté

     // Récupère l'adresse courriel et la ville de l'utilisateur connecté
     $email = $USER->email;
     $city = $USER->city;

     // Récupère le contexte actuel (cours ou module)
     $context = $PAGE->context;

     // Récupère les rôles de l'utilisateur dans le contexte actuel
    $roles = get_user_roles($context, $USER->id, true);

    // Construire une chaîne de rôles
    $rolestrings = array();
    foreach ($roles as $role) {
        $roledata = $DB->get_record('role', array('id' => $role->roleid));
        if ($roledata) {
            $rolestrings[] = role_get_name($roledata, $context);
        }
    }
    $rolelist = implode(', ', $rolestrings);
    // Crée le message
    $message = array(
        'message' => 'Bonjour ' . $fullname,
        'roles' => $rolelist,
        'email' => $email,
        'city' => $city
    );

    // Encode le message en JSON
    return json_encode($message, JSON_PRETTY_PRINT);
}

/**
 * Summary of get_etudiants_from_api
 * @return mixed
 */
function get_etudiants_from_api() {
    $api_url = 'https://127.0.0.1:8000/api/etudiants';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Désactive la vérification SSL pour le développement local
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }

    if (isset($data['hydra:member'])) {
        return $data['hydra:member'];
    } else {
        return null;
    }
}

// Affiche des functions spécifique en html provenant de l'API