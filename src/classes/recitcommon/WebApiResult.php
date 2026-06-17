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
 * Result wrapper returned by the WebApi service dispatcher.
 *
 * @package   assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace recitannotation;

/**
 * Result wrapper returned by the WebApi service dispatcher.
 */
class WebApiResult {
    /** @var bool */
    public $success = false;

    /** @var mixed */
    public $data = null;

    /** @var string */
    public $msg = "";

    /** @var string */
    public $contenttype = 'json';

    /**
     * Constructor.
     *
     * @param bool $success
     * @param mixed $data
     * @param string $msg
     * @param string $contenttype
     */
    public function __construct($success, $data = null, $msg = "", $contenttype = 'json') {
        $this->success = $success;
        $this->data = $data;
        $this->msg = $msg;
        $this->contenttype = $contenttype;
    }
}
