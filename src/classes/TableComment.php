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
 * DTO representing one row of the assignfeedback_recitannotation_comment table.
 *
 * @package   assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace recitannotation;

defined('MOODLE_INTERNAL') || die();

/**
 * DTO representing one row of the assignfeedback_recitannotation_comment table.
 */
class TableComment {
    /** @var int */
    public $id = 0;

    /** @var int */
    public $criterionid = 0;

    /** @var string */
    public $comment = "";
}
