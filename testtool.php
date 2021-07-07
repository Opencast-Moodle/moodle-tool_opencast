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
 * 
 * @package    tool_opencast
 * @copyright  2021 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

require_admin();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$pagetitle = get_string('testtoolheader', 'tool_opencast');
$url = new \moodle_url("/admin/settings.php?section=tool_opencast");
// Use the same page url to return to admin page.
$returnurl = $url;

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_title("$SITE->shortname: " . $pagetitle);
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

echo html_writer::start_tag('div', array('class' => 'p-2'));

echo tool_opencast_test_url_connection(false);
echo tool_opencast_test_connection_with_credentials(false);

echo html_writer::end_tag('div');

echo $OUTPUT->render(new single_button($returnurl, get_string('back')));

echo $OUTPUT->footer();
