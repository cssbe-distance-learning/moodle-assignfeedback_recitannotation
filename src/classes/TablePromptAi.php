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
 * DTO representing one row of the assignfeedback_recitannotation_promptai table.
 *
 * @package   assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace recitannotation;

/**
 * DTO representing one row of the assignfeedback_recitannotation_promptai table.
 *
 * Property names must match the DB column names exactly: instances of this
 * class are passed directly to insert_record()/update_record().
 */
class TablePromptAi {
    /** @var int */
    public $id = 0;

    /** @var int */
    public $assignment = 0;

    /** @var string must match the "prompt_ai" DB column name. */
    // phpcs:ignore moodle.NamingConventions.ValidVariableName.MemberNameUnderscore
    public $prompt_ai = "";
}
