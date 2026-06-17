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
 * Plugin configuration accessors.
 *
 * @package   assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace recitannotation;

/**
 * Reads the plugin's admin settings.
 */
class Options {
    /**
     * Returns the configured AI API endpoint.
     *
     * @return string
     */
    public static function get_ai_api_endpoint() {
        return get_config('assignfeedback_recitannotation', 'ai_api_endpoint');
    }

    /**
     * Returns the configured AI model.
     *
     * @return string
     */
    public static function get_ai_model() {
        return get_config('assignfeedback_recitannotation', 'ai_model');
    }

    /**
     * Returns the configured AI API key.
     *
     * @return string
     */
    public static function get_ai_api_key() {
        return get_config('assignfeedback_recitannotation', 'ai_api_key');
    }

    /**
     * Returns the configured documentation URL.
     *
     * @return string
     */
    public static function get_url_documentation() {
        return get_config('assignfeedback_recitannotation', 'url_documentation');
    }

    /**
     * Whether the AI API has been configured (endpoint and key both set).
     *
     * @return bool
     */
    public static function is_ai_api_active() {
        $endpoint = self::get_ai_api_endpoint();
        $apikey = self::get_ai_api_key();

        if ((strlen($endpoint) > 0) && (strlen($apikey) > 0)) {
            return true;
        } else {
            return false;
        }
    }
}
