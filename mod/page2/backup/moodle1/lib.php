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
 * @package mod_page2
 * @copyright  2011 Andrew Davis <andrew@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Page conversion handler. This resource handler is called by moodle1_mod_resource_handler
 */
class moodle1_mod_page2_handler extends moodle1_resource_successor_handler {

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

        // convert the legacy data onto the new page2 record
        $page2                       = array();
        $page2['id']                 = $data['id'];
        $page2['name']               = $data['name'];
        $page2['intro']              = $data['intro'];
        $page2['introformat']        = $data['introformat'];
        $page2['content']            = $data['alltext'];

        if ($data['type'] === 'html') {
            // legacy Resource of the type Web page
            $page2['contentformat'] = FORMAT_HTML;

        } else {
            // legacy Resource of the type Plain text page
            $page2['contentformat'] = (int)$data['reference'];

            if ($page2['contentformat'] < 0 or $page2['contentformat'] > 4) {
                $page2['contentformat'] = FORMAT_MOODLE;
            }
        }

        $page2['legacyfiles']        = RESOURCELIB_LEGACYFILES_ACTIVE;
        $page2['legacyfileslast']    = null;
        $page2['revision']           = 1;
        $page2['timemodified']       = $data['timemodified'];

        // populate display and displayoptions fields
        $options = array('printintro' => 0);
        if ($data['popup']) {
            $page2['display'] = RESOURCELIB_DISPLAY_POPUP;
            $rawoptions = explode(',', $data['popup']);
            foreach ($rawoptions as $rawoption) {
                list($name, $value) = explode('=', trim($rawoption), 2);
                if ($value > 0 and ($name == 'width' or $name == 'height')) {
                    $options['popup'.$name] = $value;
                    continue;
                }
            }
        } else {
            $page2['display'] = RESOURCELIB_DISPLAY_OPEN;
        }
        $page2['displayoptions'] = serialize($options);

        // get a fresh new file manager for this instance
        $this->fileman = $this->converter->get_file_manager($contextid, 'mod_page2');

        // convert course files embedded into the intro
        $this->fileman->filearea = 'intro';
        $this->fileman->itemid   = 0;
        $page2['intro'] = moodle1_converter::migrate_referenced_files($page2['intro'], $this->fileman);

        // convert course files embedded into the content
        $this->fileman->filearea = 'content';
        $this->fileman->itemid   = 0;
        $page2['content'] = moodle1_converter::migrate_referenced_files($page2['content'], $this->fileman);

        // write page.xml
        $this->open_xml_writer("activities/page2_{$moduleid}/page2.xml");
        $this->xmlwriter->begin_tag('activity', array('id' => $instanceid, 'moduleid' => $moduleid,
            'modulename' => 'page2', 'contextid' => $contextid));
        $this->write_xml('page2', $page2, array('/page2/id'));
        $this->xmlwriter->end_tag('activity');
        $this->close_xml_writer();

        // write inforef.xml for migrated resource file.
        $this->open_xml_writer("activities/page2_{$moduleid}/inforef.xml");
        $this->xmlwriter->begin_tag('inforef');
        $this->xmlwriter->begin_tag('fileref');
        foreach ($this->fileman->get_fileids() as $fileid) {
            $this->write_xml('file', array('id' => $fileid));
        }
        $this->xmlwriter->end_tag('fileref');
        $this->xmlwriter->end_tag('inforef');
        $this->close_xml_writer();
    }
}
