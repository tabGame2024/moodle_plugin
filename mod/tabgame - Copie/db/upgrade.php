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
 * tabgame module upgrade
 *
 * @package mod_tabgame
 * @copyright  2006 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This file keeps track of upgrades to
// the tabgame module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

function xmldb_tabgame_upgrade($oldversion) {
    global $CFG, $DB;

    // Automatically generated Moodle v4.1.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2022112801) {
        $prevlang = force_current_language($CFG->lang);

        $select = $DB->sql_like('name', ':tofind');
        $params = ['tofind' => '%@@PLUGINFILE@@%'];
        $total = $DB->count_records_select('tabgame', $select, $params);
        if ($total > 0) {
            $tabgames = $DB->get_recordset_select('tabgame', $select, $params, '', 'id, name, intro');

            // Show a progress bar.
            $pbar = new progress_bar('upgrademodtabgamepluginfile', 500, true);
            $current = 0;

            $defaultname = get_string('modulename', 'tabgame');
            foreach ($tabgames as $tabgame) {
                $originalname = $tabgame->name;
                // Make sure that all tabgames have now the same name according to the new convention.
                // Note this is the same (and duplicated) code as in get_tabgame_name as we cannot call any API function
                // during upgrade.
                $name = html_to_text(format_string($tabgame->intro, true));
                $name = preg_replace('/@@PLUGINFILE@@\/[[:^space:]]+/i', '', $name);
                // Remove double space and also nbsp; characters.
                $name = preg_replace('/\s+/u', ' ', $name);
                $name = trim($name);
                if (core_text::strlen($name) > tabgame_MAX_NAME_LENGTH) {
                    $name = core_text::substr($name, 0, tabgame_MAX_NAME_LENGTH) . "...";
                }
                if (empty($name)) {
                    $name = $defaultname;
                }
                $tabgame->name = $name;
                if ($originalname !== $name) {
                    $DB->update_record('tabgame', $tabgame);
                }
                $current++;
                $pbar->update($current, $total, "Upgrading tabgame activity names - $current/$total.");
            }
            $tabgames->close();
        }
        force_current_language($prevlang);
        upgrade_mod_savepoint(true, 2022112801, 'tabgame');
    }

    // Automatically generated Moodle v4.2.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v4.3.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v4.4.0 release upgrade line.
    // Put any upgrade step following this.

    return true;
}
