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
 * Page external API
 *
 * @package    mod_page2
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

use core_course\external\helper_for_get_mods_by_courses;
use core_external\external_api;
use core_external\external_files;
use core_external\external_format_value;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_warnings;
use core_external\util;

/**
 * Page external functions
 *
 * @package    mod_page2
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
class mod_page2_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function view_page2_parameters() {
        return new external_function_parameters(
            array(
                'page2id' => new external_value(PARAM_INT, 'page2 instance id')
            )
        );
    }

    /**
     * Simulate the page2/view.php web interface page2: trigger events, completion, etc...
     *
     * @param int $page2id the page instance id
     * @return array of warnings and status result
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function view_page2($page2id) {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/mod/page2/lib.php");

        $params = self::validate_parameters(self::view_page2_parameters(),
                                            array(
                                                'page2id' => $page2id
                                            ));
        $warnings = array();

        // Request and permission validation.
        $page2 = $DB->get_record('page2', array('id' => $params['page2id']), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($page2, 'page2');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/page2:view', $context);

        // Call the page2/lib API.
        page2_view($page2, $course, $cm, $context);

        $result = array();
        $result['status'] = true;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return \core_external\external_description
     * @since Moodle 3.0
     */
    public static function view_page2_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Describes the parameters for get_pages_by_courses.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_pages_by_courses_parameters() {
        return new external_function_parameters (
            array(
                'courseids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Course id'), 'Array of course ids', VALUE_DEFAULT, array()
                ),
            )
        );
    }

    /**
     * Returns a list of pages in a provided list of courses.
     * If no list is provided all pages that the user can view will be returned.
     *
     * @param array $courseids course ids
     * @return array of warnings and pages
     * @since Moodle 3.3
     */
    public static function get_pages_by_courses($courseids = array()) {

        $warnings = array();
        $returnedpages = array();

        $params = array(
            'courseids' => $courseids,
        );
        $params = self::validate_parameters(self::get_pages_by_courses_parameters(), $params);

        $mycourses = array();
        if (empty($params['courseids'])) {
            $mycourses = enrol_get_my_courses();
            $params['courseids'] = array_keys($mycourses);
        }

        // Ensure there are courseids to loop through.
        if (!empty($params['courseids'])) {

            list($courses, $warnings) = util::validate_courses($params['courseids'], $mycourses);

            // Get the pages in this course, this function checks users visibility permissions.
            // We can avoid then additional validate_context calls.
            $pages = get_all_instances_in_courses("page2", $courses);
            foreach ($pages as $page2) {
                helper_for_get_mods_by_courses::format_name_and_intro($page2, 'mod_page2');

                $context = context_module::instance($page2->coursemodule);
                list($page2->content, $page2->contentformat) = \core_external\util::format_text(
                        $page2->content, $page2->contentformat,
                        $context, 'mod_page', 'content', $page2->revision, ['noclean' => true]);
                $page2->contentfiles = util::get_area_files($context->id, 'mod_page2', 'content');

                $returnedpages[] = $page2;
            }
        }

        $result = array(
            'pages' => $returnedpages,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the get_pages_by_courses return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_pages_by_courses_returns() {
        return new external_single_structure(
            array(
                'pages' => new external_multiple_structure(
                    new external_single_structure(array_merge(
                        helper_for_get_mods_by_courses::standard_coursemodule_elements_returns(),
                        [
                            'content' => new external_value(PARAM_RAW, 'Page content'),
                            'contentformat' => new external_format_value('content'),
                            'contentfiles' => new external_files('Files in the content'),
                            'legacyfiles' => new external_value(PARAM_INT, 'Legacy files flag'),
                            'legacyfileslast' => new external_value(PARAM_INT, 'Legacy files last control flag'),
                            'display' => new external_value(PARAM_INT, 'How to display the page'),
                            'displayoptions' => new external_value(PARAM_RAW, 'Display options (width, height)'),
                            'revision' => new external_value(PARAM_INT, 'Incremented when after each file changes, to avoid cache'),
                            'timemodified' => new external_value(PARAM_INT, 'Last time the page was modified'),
                        ]
                    ))
                ),
                'warnings' => new external_warnings(),
            )
        );
    }
    /**
     * Summary of get_user_info_parameters
     * @return external_function_parameters
     */
    public static function get_user_info_parameters(){
        return new external_function_parameters(
            array()
        );
    }
    /**
     * Summary of get_user_info
     * @return array
     */
    public static function get_user_info(){
        global $USER, $DB;

        $context = context_user::instance($USER->id);
        self::validate_context($context);

        // Récupère les rôles de l'utilisateur dans le contexte actuel
        $roles = get_user_roles($context, $USER->id, true);
        $rolestrings = array();
        foreach ($roles as $role) {
            $roledata = $DB->get_record('role', array('id' => $role->roleid));
            if ($roledata) {
                $rolestrings[] = role_get_name($roledata, $context);
            }
        }
        $rolelist = implode(', ', $rolestrings);

        return array(
            'fullname' => fullname($USER),
            'email' => $USER->email,
            'city' => $USER->city,
            'roles' => $rolelist
        );
    }

    public static function get_user_info_returns(){
        return new external_single_structure(
            array(
                'fullname' => new external_value(PARAM_TEXT, 'Full name of the user'),
                'email' => new external_value(PARAM_TEXT, 'Email of the user'),
                'city' => new external_value(PARAM_TEXT, 'City of the user'),
                'roles' => new external_value(PARAM_TEXT, 'Roles of the user')
            )
            );
    }
    public static function get_students_parameters() {
        return new external_function_parameters([]);
    }

    public static function get_students() {
        global $CFG;

        $url = 'https://127.0.0.1:8000/api/etudiants';
        $token = 'f602d473c44af2a55c3df2308fbdb92f'; // Si votre API nécessite une authentification

        // $options = array(
        //     'http' => array(
        //         'header' => "Authorization: Bearer $token\r\n",
        //         'method' => 'GET'
        //     )
        // );
        // $context = stream_context_create($options);
        // $result = file_get_contents($url, false, $context);

        // if ($result === FALSE) {
        //     throw new moodle_exception('Cannot fetch students from API');
        // }

        // $students = json_decode($result, true);

        // if (json_last_error() !== JSON_ERROR_NONE) {
        //     throw new moodle_exception('Invalid JSON response from API');
        // }

        // return [
        //     'students' => $students
        // ];
        $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Bearer $token"
    ));

    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new moodle_exception('Cannot fetch students from API', 'mod_page2', '', curl_error($ch));
    }

    curl_close($ch);

    $students = json_decode($result, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new moodle_exception('Invalid JSON response from API', 'mod_page2', '', json_last_error_msg());
    }

    return [
        'students' => $students
    ];
    }

    public static function get_students_returns() {
        return new external_single_structure(
            array(
                'students' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'ID of the student'),
                            'nom' => new external_value(PARAM_TEXT, 'Name of the student'),
                            'email' => new external_value(PARAM_TEXT, 'Email of the student')
                        )
                    )
                )
            )
        );
    }
}
