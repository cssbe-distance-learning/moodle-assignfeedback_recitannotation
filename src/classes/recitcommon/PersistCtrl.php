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
 * Common base persistence controller shared across RECIT plugins.
 *
 * @package   assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace recitannotation;

defined('MOODLE_INTERNAL') || die();

/**
 * Base persistence controller providing cross-database SQL helpers.
 */
abstract class APersistCtrl {
    /**
     * @var \mysqli_native_moodle_database
     */
    protected $mysqlconn;

    /** @var \stdClass the signed-in user */
    protected $signeduser;

    /** @var string the DB table prefix */
    protected $prefix = "";

    /**
     * Constructor.
     *
     * @param \moodle_database $mysqlconn
     * @param \stdClass $signeduser
     */
    protected function __construct($mysqlconn, $signeduser) {
        global $CFG;

        $this->mysqlconn = $mysqlconn;
        $this->signeduser = $signeduser;
        $this->prefix = $CFG->prefix;
    }

    /**
     * Whether the current request has a valid signed-in user.
     *
     * @return bool
     */
    public function check_session() {
        return (isset($this->signeduser) && $this->signeduser->id > 0);
    }

    /**
     * Executes a raw SQL statement.
     *
     * @param string $sql
     * @param array $params
     * @return bool
     */
    public function exec_sql($sql, $params = []) {
        $result = $this->mysqlconn->execute($sql, $params);
        return $result;
    }

    /**
     * Runs a SQL query and converts snake_case columns to camelCase properties.
     *
     * @param string $sql
     * @param array $params
     * @param bool $ignoreunderscore
     * @return array
     */
    public function get_records_sql($sql, $params = [], $ignoreunderscore = false) {
        $result = $this->mysqlconn->get_records_sql($sql, $params);

        foreach ($result as $item) {
            foreach ((array)$item as $k => $v) {
                if (!$ignoreunderscore) {
                    if (strpos($k, '_') != false) {
                        $key = preg_replace_callback("/_[a-z]?/", function ($matches) {
                            return strtoupper(ltrim($matches[0], "_"));
                        }, $k);
                        $item->$key = $v;
                        unset($item->$k);
                    }
                }
            }
        }
        return array_values($result);
    }

    /**
     * Return SQL for performing group concatenation on given field/expression
     *
     * @param string $field
     * @param string $separator
     * @param string $sort
     * @return string
     */
    public function sql_group_concat(string $field, string $separator = ',', string $sort = ''): string {
        global $CFG;
        if ($CFG->dbtype == 'pgsql') {
            $fieldsort = $sort ? "ORDER BY {$sort}" : '';
            return "STRING_AGG(CAST({$field} AS VARCHAR), '{$separator}' {$fieldsort})";
        } else {
            $fieldsort = $sort ? "ORDER BY {$sort}" : '';
            return "GROUP_CONCAT({$field} {$fieldsort} SEPARATOR '{$separator}')";
        }
    }

    /**
     * Returns SQL testing whether a value is present in a comma-separated set.
     *
     * @param string $tofind
     * @param string $field
     * @return string
     */
    public function sql_find_in_set(string $tofind, string $field): string {
        global $CFG;
        // The "tofind" value is embedded literally; callers must pass only safe, already-validated values.
        $escaped = str_replace("'", "''", $tofind);
        if ($CFG->dbtype == 'pgsql') {
            return "'{$escaped}' = ANY (string_to_array($field,','))";
        } else {
            return "FIND_IN_SET('{$escaped}', $field)";
        }
    }

    /**
     * Returns SQL generating a unique identifier.
     *
     * @return string
     */
    public function sql_uniqueid(): string {
        global $CFG;
        if ($CFG->dbtype == 'pgsql') {
            return "gen_random_uuid()";
        } else {
            return "uuid()";
        }
    }

    /**
     * Returns SQL converting a unix timestamp field to a datetime string.
     *
     * @param string $field
     * @return string
     */
    public function sql_from_unixtime($field): string {
        global $CFG;
        if ($CFG->dbtype == 'pgsql') {
            return "to_char(to_timestamp($field), 'yyyy-mm-dd HH24:MI:SS')";
        } else {
            return "FROM_UNIXTIME($field)";
        }
    }

    /**
     * Returns SQL converting a unix timestamp field to a database time value.
     *
     * @param string $field
     * @return string
     */
    public function sql_to_time($field): string {
        global $CFG;
        if ($CFG->dbtype == 'pgsql') {
            return "to_timestamp($field)";
        } else {
            return "FROM_UNIXTIME($field)";
        }
    }

    /**
     * Returns SQL converting a time field to seconds.
     *
     * @param string $field
     * @return string
     */
    public function sql_time_to_secs($field): string {
        global $CFG;
        if ($CFG->dbtype == 'pgsql') {
            return "EXTRACT(EPOCH FROM $field)";
        } else {
            return "TIME_TO_SEC($field)";
        }
    }

    /**
     * Returns SQL computing the day difference between two date fields.
     *
     * @param string $field
     * @param string $field2
     * @return string
     */
    public function sql_datediff($field, $field2): string {
        global $CFG;
        if ($CFG->dbtype == 'pgsql') {
            return "EXTRACT(DAY FROM $field - $field2)";
        } else {
            return "DATEDIFF($field, $field2)";
        }
    }

    /**
     * Returns SQL casting a field to a string/text type.
     *
     * @param string $field
     * @return string
     */
    public function sql_caststring($field): string {
        global $CFG;
        if ($CFG->dbtype == 'pgsql') {
            return "CAST($field AS TEXT)";
        } else {
            return "$field";
        }
    }

    /**
     * Returns SQL casting a field to UTF-8.
     *
     * @param string $field
     * @return string
     */
    public function sql_castutf8($field): string {
        global $CFG;
        if ($CFG->dbtype == 'pgsql') {
            return "CAST($field AS TEXT)";
        } else {
            return "CONVERT($field USING utf8)";
        }
    }

    /**
     * Returns the database function name used to build a JSON object.
     *
     * @return string
     */
    public function sql_tojson(): string {
        global $CFG;
        if ($CFG->dbtype == 'pgsql') {
            return "jsonb_build_object";
        } else {
            return "JSON_OBJECT";
        }
    }

    /**
     * Returns SQL converting a number of seconds to a time value.
     *
     * @param string $field
     * @return string
     */
    public function sql_sectotime($field): string {
        global $CFG;
        if ($CFG->dbtype == 'pgsql') {
            return "to_char( ($field ||' seconds')::interval, 'HH24:MM:SS' )";
        } else {
            return "SEC_TO_TIME($field)";
        }
    }
}
