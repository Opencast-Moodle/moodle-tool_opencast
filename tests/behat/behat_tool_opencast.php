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
 * Behat steps definitions for tool opencast.
 *
 * @package    tool_opencast
 * @category   test
 * @copyright  2024 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

use tool_opencast\seriesmapping;

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

/**
 * Steps definitions related with the opencast tool API.
 *
 * @package    tool_opencast
 * @category   test
 * @copyright  2024 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_opencast extends behat_base {

    /**
     * Setup of block block by creating series mapping.
     *
     * @Given /^I setup block plugin$/
     */
    public function i_setup_block_plugin() {
        $courses = \core_course_category::search_courses(['search' => 'Course 1']);

        $mapping = new seriesmapping();
        $mapping->set('courseid', reset($courses)->id);
        $mapping->set('series', '1234-1234-1234-1234-1234');
        $mapping->set('isdefault', '1');
        $mapping->set('ocinstanceid', 1);
        $mapping->create();
    }

    /**
     * adds a breakpoints in tool
     * stops the execution until you hit enter in the console
     *
     * @Then /^breakpoint in tool/
     */
    public function breakpoint_in_tool() {
        fwrite(STDOUT, "\033[s    \033[93m[Breakpoint] Press \033[1;93m[RETURN]\033[0;93m to continue...\033[0m");
        while (fgets(STDIN, 1024) == '') {
            continue;
        }
        fwrite(STDOUT, "\033[u");
        return;
    }

    /**
     * Adds a step to make sure the block drawer keeps opened.
     *
     * @Given /^I make sure the block drawer keeps opened/
     */
    public function i_make_sure_the_block_drawer_keeps_opened() {
        set_user_preference('behat_keep_drawer_closed', 0);
    }
}
