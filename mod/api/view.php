<?php

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/api/lib.php');
require_once($CFG->dirroot.'/mod/api/locallib.php');
require_once($CFG->libdir.'/completionlib.php');

$id = optional_param('id', 0, PARAM_INT);  // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);   // ... api instance ID

if ($n) {
    if (!$api = $DB->get_record('api', array('id'=>$n))) {
        throw new \moodle_exception('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('api', $api->id, $api->course, false, MUST_EXIST);
} else {
    if (!$cm = get_coursemodule_from_id('api', $id)) {
        throw new \moodle_exception('invalidcoursemodule');
    }
    $api = $DB->get_record('api', array('id'=>$cm->instance), '*', MUST_EXIST);
    // Assurez-vous que toutes les propriétés sont définies.
if (!isset($api->content)) {
    $api->content = ''; // Valeur par défaut si non définie.
}
if (!isset($api->contentformat)) {
    $api->contentformat = 0; // Valeur par défaut si non définie.
}
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_login($course, true, $cm);

// Get the context
$context = context_module::instance($cm->id);

// Header
$PAGE->set_url('/mod/api/view.php', array('id' => $cm->id));
$activityheader = ['hidecompletion' => false];
$PAGE->activityheader->set_attrs($activityheader);
if (empty($options['printintro'])) {
    $activityheader['description'] = '';
}
$PAGE->activityheader->set_attrs($activityheader);
echo $OUTPUT->header();
$content = file_rewrite_pluginfile_urls($api->content, 'pluginfile.php', $context->id, 'mod_api', 'content', 0);
// $formatoptions = new stdClass;
// $formatoptions->noclean = true;
// $formatoptions->overflowdiv = true;
// $formatoptions->context = $context;
// $content = format_text($content, $api->contentformat, $formatoptions);
// debugging("Formatted Content: {$content}");
echo $OUTPUT->box($content, "generalbox center clearfix");

if (!isset($options['printlastmodified']) || !empty($options['printlastmodified'])) {
    $strlastmodified = get_string("lastmodified");
    echo html_writer::div("$strlastmodified: " . userdate($api->timemodified), 'modified');
}
// echo $OUTPUT->header();

// // Conditions to show the intro can change to look for own settings or whatever
// if ($api->intro) {
//     echo $OUTPUT->box(format_module_intro('api', $api, $cm->id), 'generalbox mod_introbox', 'apiintro');
// }

// // Check if content and contentformat properties exist
// if (!empty($api->content) && isset($api->contentformat)) {
//     // Fetch the content and format it using format_text
//     $content = file_rewrite_pluginfile_urls($api->content, 'pluginfile.php', $context->id, 'mod_api', 'content', $api->id);
//     $formatoptions = new stdClass;
//     $formatoptions->noclean = true;
//     $formatoptions->overflowdiv = true;
//     $formatoptions->context = $context;
//     $content = format_text($content, $api->contentformat, $formatoptions);

//     // Display the content
//     echo $OUTPUT->box($content, 'generalbox center clearfix');
// } else {
//     echo $OUTPUT->box(get_string('nocontent', 'mod_api'), 'generalbox');
// }

// if (!isset($options['printlastmodified']) || !empty($options['printlastmodified'])) {
//     $strlastmodified = get_string("lastmodified");
//     echo html_writer::div("$strlastmodified: " . userdate($api->timemodified), 'modified');
// }

// Finish the page
echo $OUTPUT->footer();
