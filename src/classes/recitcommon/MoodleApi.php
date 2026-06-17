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
 * Moodle-specific WebApi dispatcher base class.
 *
 * @package   assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace recitannotation;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/WebApi.php');
require_once(__DIR__ . '/../PersistCtrl.php');

/**
 * Moodle-specific WebApi dispatcher base class.
 */
abstract class MoodleApi extends AWebApi {
    /** @var \stdClass the signed-in user */
    protected $signeduser = null;

    /** @var \stdClass the current course */
    protected $course = null;

    /** @var \moodle_database the Moodle DB connection */
    protected $dbconn = null;

    /**
     * Constructor.
     *
     * @param \moodle_database $db
     * @param \stdClass $course
     * @param \stdClass $user
     */
    public function __construct($db, $course, $user) {
        $this->signeduser = $user;
        $this->course = $course;
        $this->dbconn = $db;
        PersistCtrl::get_instance($db, $user);
    }
}
