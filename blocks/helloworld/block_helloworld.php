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
 * Block helloworld is defined here.
 *
 * @package     block_helloworld
 * @copyright   2024 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_helloworld extends block_base
{

    /**
     * Initializes class member variables.
     */
    public function init()
    {
        // Needed by Moodle to differentiate between blocks.
        $this->title = get_string('pluginname', 'block_helloworld');
    }

    /**
     * Returns the block contents.
     *
     * @return stdClass The block contents.
     */
    public function get_content()
    {

        if ($this->content !== null) {
            return $this->content;
        }

        //     if (empty($this->instance)) {
        //         $this->content = '';
        //         return $this->content;
        //     }

        //     $this->content = new stdClass();
        //     $this->content->items = array();
        //     $this->content->icons = array();
        //     $this->content->footer = '';

        //     if (!empty($this->config->text)) {
        //         $this->content->text = $this->config->text;
        //     } else {
        //         $text = 'Voici mon premier bloc';
        //         $this->content->text = $text;
        //     }

        //     return $this->content;
        // }
        $this->content = new stdClass();

        // Load user data
        global $USER;

        // Set the content text to greet the user by first name
        if (isset($USER->firstname)) {
            $this->content->text = 'Bonjour ' . $USER->firstname;
        } else {
            $this->content->text = 'Bonjour ' . $USER->id;
        }

        return $this->content;
    }

    /**
     * Defines configuration data.
     *
     * The function is called immediately after init().
     */
    public function specialization()
    {

        // Load user defined title and make sure it's never empty.
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_helloworld');
        } else {
            $this->title = $this->config->title;
        }
    }

    /**
     * Enables global configuration of the block in settings.php.
     *
     * @return bool True if the global configuration is enabled.
     */
    public function has_config()
    {
        return true;
    }

    /**
     * Sets the applicable formats for the block.
     *
     * @return string[] Array of pages and permissions.
     */
    public function applicable_formats()
    {
        return array(
            'all' => true
        );
    }
    function _self_test()
    {
        return true;
    }

    /**
     * Get user greeting
     */
    // public static function get_user_greeting()
    // {
    //     global $USER;
    //     if (isset($USER->firstname)) {
    //         return 'Bonjour ' . $USER->firstname;
    //     } else {
    //         return 'Bonjour ' . $USER->id;
    //     }
    // }
}
