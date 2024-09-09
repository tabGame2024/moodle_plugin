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
 * Provides support for the conversion of moodle1 backup to the moodle2 format
 *
 * @package mod_api
 * @copyright  2011 Andrew Davis <andrew@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * api conversion handler. This resource handler is called by moodle1_mod_resource_handler
 */
class moodle1_mod_api_handler extends moodle1_resource_successor_handler {

    /** @var moodle1_file_manager instance */
    protected $fileman = null;

    /**
     * Converts /MOODLE_BACKUP/COURSE/MODULES/MOD/RESOURCE data
     * Called by moodle1_mod_resource_handler::process_resource()
     */
    public function process_legacy_resource(array $data, array $raw = null) {

        // get the course module id and context id
        $instanceid = $data['id'];
        $cminfo     = $this->get_cminfo($instanceid, 'resource');
        $moduleid   = $cminfo['id'];
        $contextid  = $this->converter->get_contextid(CONTEXT_MODULE, $moduleid);

        // convert the legacy data onto the new api record
        $api                       = array();
        $api['id']                 = $data['id'];
        $api['name']               = $data['name'];
        $api['intro']              = $data['intro'];
        $api['introformat']        = $data['introformat'];
        $api['content']            = $data['alltext'];

        if ($data['type'] === 'html') {
            // legacy Resource of the type Web api
            $api['contentformat'] = FORMAT_HTML;

        } else {
            // legacy Resource of the type Plain text api
            $api['contentformat'] = (int)$data['reference'];

            if ($api['contentformat'] < 0 or $api['contentformat'] > 4) {
                $api['contentformat'] = FORMAT_MOODLE;
            }
        }

        $api['legacyfiles']        = RESOURCELIB_LEGACYFILES_ACTIVE;
        $api['legacyfileslast']    = null;
        $api['revision']           = 1;
        $api['timemodified']       = $data['timemodified'];

        // populate display and displayoptions fields
        $options = array('printintro' => 0);
        if ($data['popup']) {
            $api['display'] = RESOURCELIB_DISPLAY_POPUP;
            $rawoptions = explode(',', $data['popup']);
            foreach ($rawoptions as $rawoption) {
                list($name, $value) = explode('=', trim($rawoption), 2);
                if ($value > 0 and ($name == 'width' or $name == 'height')) {
                    $options['popup'.$name] = $value;
                    continue;
                }
            }
        } else {
            $api['display'] = RESOURCELIB_DISPLAY_OPEN;
        }
        $api['displayoptions'] = serialize($options);

        // get a fresh new file manager for this instance
        $this->fileman = $this->converter->get_file_manager($contextid, 'mod_api');

        // convert course files embedded into the intro
        $this->fileman->filearea = 'intro';
        $this->fileman->itemid   = 0;
        $api['intro'] = moodle1_converter::migrate_referenced_files($api['intro'], $this->fileman);

        // convert course files embedded into the content
        $this->fileman->filearea = 'content';
        $this->fileman->itemid   = 0;
        $api['content'] = moodle1_converter::migrate_referenced_files($api['content'], $this->fileman);

        // write api.xml
        $this->open_xml_writer("activities/api_{$moduleid}/api.xml");
        $this->xmlwriter->begin_tag('activity', array('id' => $instanceid, 'moduleid' => $moduleid,
            'modulename' => 'api', 'contextid' => $contextid));
        $this->write_xml('api', $api, array('/api/id'));
        $this->xmlwriter->end_tag('activity');
        $this->close_xml_writer();

        // write inforef.xml for migrated resource file.
        $this->open_xml_writer("activities/api_{$moduleid}/inforef.xml");
        $this->xmlwriter->begin_tag('inforef');
        $this->xmlwriter->begin_tag('fileref');
        foreach ($this->fileman->get_fileids() as $fileid) {
            $this->write_xml('file', array('id' => $fileid));
        }
        $this->xmlwriter->end_tag('fileref');
        $this->xmlwriter->end_tag('inforef');
        $this->close_xml_writer();
    }

    // function moodle1_save_instance($instance) {
    //     global $DB;
        
    //     // Appel à votre fonction personnalisée
    //     $instance->text = write_hello_world($instance->text);
        
    //     // Enregistrement de l'instance
    //     if (empty($instance->id)) {
    //         $instance->id = $DB->insert_record('moodle1', $instance);
    //     } else {
    //         $DB->update_record('moodle1', $instance);
    //     }
        
    //     return $instance->id;
    // }
    
}
