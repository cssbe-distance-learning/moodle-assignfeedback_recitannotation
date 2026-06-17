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
 * Printable list of comments for a given assignment.
 *
 * @package   assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license   {@link http://www.gnu.org/licenses/gpl-3.0.html} GNU GPL v3 or later
 */

namespace recitannotation;

require(dirname(__FILE__) . '/../../../../../config.php');
require_once(dirname(__FILE__) . '/PersistCtrl.php');

$assignment = required_param('assignment', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);

require_login();

// Get the course module.
$cm = get_coursemodule_from_id(null, $cmid, 0, false, MUST_EXIST);

// Get the course that corresponds to the course module.
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

// Set the page context.
$PAGE->set_cm($cm, $course);

$PAGE->set_context(\context_module::instance($cmid));

$theme = \theme_config::load($PAGE->theme->name);

$brandimage = "{$CFG->wwwroot}/mod/assign/feedback/recitannotation/pix/recit-logo.png";
$customerlogo = $theme->setting_file_url('logo', 'logo');
if (!empty($customerlogo)) {
    $brandimage = $customerlogo;
}

$persistctrl = PersistCtrl::get_instance($DB, $USER);

$isteacher = $persistctrl->has_teacher_access($assignment);

if (!$isteacher) {
    throw new \moodle_exception(get_string('access_denied', 'assignfeedback_recitannotation'));
}

$commentlist = $persistctrl->get_comment_list($assignment);

$pagetitle = sprintf(
    "%s: %s",
    get_string('pluginname', 'assignfeedback_recitannotation'),
    get_string('comment_list', 'assignfeedback_recitannotation')
);

$stylesheeturl = $CFG->wwwroot . "/theme/styles.php/{$CFG->theme}/{$CFG->themerev}_1/all";
$reportcssurl = $CFG->wwwroot . "/mod/recitcahiertraces/css/report.css";

echo '<!DOCTYPE html>';
echo '<html>';
echo '<head>';
echo '<title>' . s($pagetitle) . '</title>';
echo '<link rel="stylesheet" type="text/css" href="' . s($stylesheeturl) . '">';
echo '<link rel="stylesheet" type="text/css" href="' . s($reportcssurl) . '">';
echo '<link rel="icon" href="../pix/recit-logo.png" />';
echo '</head>';

echo "<body>";
echo "<div class='Portrait cahier-traces-print-notes'>";
echo "<header class='Header'>";
echo "<div style='flex-grow: 1'>";
echo "<div class='Title'>" . s(get_string('pluginname', 'assignfeedback_recitannotation')) . "</div>";
echo "<div class='Subtitle'>" . s(get_string('comment_list', 'assignfeedback_recitannotation')) . "</div>";
echo "</div>";
echo "<div class='Logo'><img src='" . s($brandimage) . "' alt='brand logo'/></div>";
echo "</header>";

if (empty($commentlist)) {
    echo "<h6>" . get_string('no_data', 'assignfeedback_recitannotation') . "</h6>";
} else {
    echo '<table class="table table-sm table-striped table-bordered">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>' . s(get_string('criterion', 'assignfeedback_recitannotation')) . '</th>';
    echo '<th>' . s(get_string('comment', 'assignfeedback_recitannotation')) . '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    foreach ($commentlist as $item) {
        echo '<tr>';
        echo '<td>' . s($item->description) . '</td>';
        echo '<td>' . s($item->comment) . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
}

echo '<footer class="text-left mt-5">';
echo s(sprintf("%s: %s", get_string('printed_on', 'assignfeedback_recitannotation'), date('Y-m-d H:i:s')));
echo '</footer>';
echo '</div>';
echo '</body>';
echo '</html>';
