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
 * Plugin administration apis are defined here.
 *
 * @package     mod_api
 * @category    admin
 * @copyright   2024 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('mod_api_settings', new lang_string('pluginname', 'mod_api'));

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        require_once("$CFG->libdir/resourcelib.php");

        $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_OPEN, RESOURCELIB_DISPLAY_POPUP));
        $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_OPEN);

        //--- general settings -----------------------------------------------------------------------------------
        $settings->add(new admin_setting_configmultiselect('api/displayoptions',
            get_string('displayoptions', 'api'), get_string('configdisplayoptions', 'api'),
            $defaultdisplayoptions, $displayoptions));

        //--- modedit defaults -----------------------------------------------------------------------------------
        $settings->add(new admin_setting_heading('apimodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

        $settings->add(new admin_setting_configcheckbox('api/printintro',
            get_string('printintro', 'api'), get_string('printintroexplain', 'api'), 0));
        $settings->add(new admin_setting_configcheckbox('api/printlastmodified',
            get_string('printlastmodified', 'api'), get_string('printlastmodifiedexplain', 'api'), 1));
        $settings->add(new admin_setting_configselect('api/display',
            get_string('displayselect', 'api'), get_string('displayselectexplain', 'api'), RESOURCELIB_DISPLAY_OPEN, $displayoptions));
        $settings->add(new admin_setting_configtext('api/popupwidth',
            get_string('popupwidth', 'api'), get_string('popupwidthexplain', 'api'), 620, PARAM_INT, 7));
        $settings->add(new admin_setting_configtext('api/popupheight',
            get_string('popupheight', 'api'), get_string('popupheightexplain', 'api'), 450, PARAM_INT, 7));
    }
}
