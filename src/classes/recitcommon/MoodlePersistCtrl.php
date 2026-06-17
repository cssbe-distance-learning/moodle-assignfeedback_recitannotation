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
 * Moodle-specific persistence controller helpers.
 *
 * @package   assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace recitannotation;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/PersistCtrl.php');

/**
 * Moodle-specific persistence controller helpers.
 */
abstract class MoodlePersistCtrl extends APersistCtrl {
    /**
     * Returns the name of a course module given its course module id.
     *
     * @param int $cmid
     * @param int $courseid
     * @param \course_modinfo|bool $moddata
     * @return string
     */
    public function get_cm_name_from_cm_id($cmid, $courseid, $moddata = false) {
        if ($courseid == 0) {
            return "not_found";
        }

        if (!$moddata) {
            $moddata = get_fast_modinfo($courseid);
        }

        foreach ($moddata->cms as $cm) {
            if ($cmid == $cm->id) {
                return $cm->name;
            }
        }
    }

    /**
     * Returns the course module given its course module id.
     *
     * @param int $cmid
     * @param int $courseid
     * @param \course_modinfo|bool $moddata
     * @return \cm_info|null
     */
    public function get_cm_from_cm_id($cmid, $courseid, $moddata = false) {
        if (!$moddata) {
            $moddata = get_fast_modinfo($courseid);
        }

        foreach ($moddata->cms as $cm) {
            if ($cmid == $cm->id) {
                return $cm;
            }
        }
    }
}
