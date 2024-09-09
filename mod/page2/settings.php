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
 * page2 module admin settings and defaults
 *
 * @package mod_page2
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_OPEN, RESOURCELIB_DISPLAY_POPUP));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_OPEN);

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configmultiselect('page2/displayoptions',
        get_string('displayoptions', 'page2'), get_string('configdisplayoptions', 'page2'),
        $defaultdisplayoptions, $displayoptions));

    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('page2modeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox('page2/printintro',
        get_string('printintro', 'page2'), get_string('printintroexplain', 'page2'), 0));
    $settings->add(new admin_setting_configcheckbox('page2/printlastmodified',
        get_string('printlastmodified', 'page2'), get_string('printlastmodifiedexplain', 'page2'), 1));
    $settings->add(new admin_setting_configselect('page2/display',
        get_string('displayselect', 'page2'), get_string('displayselectexplain', 'page2'), RESOURCELIB_DISPLAY_OPEN, $displayoptions));
    $settings->add(new admin_setting_configtext('page2/popupwidth',
        get_string('popupwidth', 'page2'), get_string('popupwidthexplain', 'page2'), 620, PARAM_INT, 7));
    $settings->add(new admin_setting_configtext('page2/popupheight',
        get_string('popupheight', 'page2'), get_string('popupheightexplain', 'page2'), 450, PARAM_INT, 7));
}
