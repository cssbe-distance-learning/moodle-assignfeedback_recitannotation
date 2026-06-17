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
 * DTO representing one row of the assignfeedback_recitannotation table.
 *
 * @package   assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace recitannotation;

/**
 * DTO representing one row of the assignfeedback_recitannotation table.
 */
class RecitAnnotation {
    /** @var int */
    public $id = 0;

    /** @var int */
    public $submission = 0;

    /** @var int */
    public $ownerid = 0;

    /** @var string */
    public $annotation = "";

    /** @var string */
    public $occurrences = "";

    /** @var int */
    public $lastupdate = 0;

    /**
     * Builds a RecitAnnotation from a raw DB row.
     *
     * @param \stdClass|null $dbdata
     * @return RecitAnnotation
     */
    public static function create($dbdata) {
        $result = new RecitAnnotation();

        if ($dbdata == null) {
            return $result;
        }

        $result->id = intval($dbdata->id);
        $result->submission = intval($dbdata->submission);
        $result->ownerid = intval($dbdata->ownerid);
        $result->annotation = $dbdata->annotation;
        $result->occurrences = $dbdata->occurrences;
        $result->lastupdate = intval($dbdata->lastupdate);
        return $result;
    }
}
