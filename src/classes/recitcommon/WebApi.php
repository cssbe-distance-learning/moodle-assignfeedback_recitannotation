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
 * Common base WebApi dispatcher shared across RECIT plugins.
 *
 * @package   assignfeedback_recitannotation
 * @copyright 2019 RECIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace recitannotation;

use stdClass;
use DateTime;
use Exception;
use DateTimeZone;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . "/WebApiResult.php");
require_once(dirname(__FILE__) . "/PersistCtrl.php");

/**
 * Base WebApi request dispatcher.
 */
abstract class AWebApi {
    /** @var array|null the current request payload */
    protected $request = null;

    /** @var WebApiResult|null the result of the last dispatched service */
    protected $lastresult = null;

    /** @var array|null the last PHP error captured by the shutdown handler */
    public static $lasterror = null;

    /** @var string the allowed CORS origin */
    public static $httporigin = "";

    /**
     * Shutdown handler that replies with a JSON error when a fatal PHP error occurred.
     */
    public static function on_php_error() {
        if (self::$lasterror == null) {
            self::$lasterror = error_get_last();
        }

        if (self::$lasterror != null) {
            $headers = self::get_default_headers();
            $headers[] = 'Status: 500 Internal Server Error';
            $headers[] = "Content-type: application/json; charset=utf-8";
            foreach ($headers as $header) {
                header($header);
            }

            if (ob_get_length() > 0) {
                ob_clean();
            }

            flush();
            echo json_encode(new WebApiResult(false, null, self::$lasterror['message']));
        }
    }

    /**
     * Returns the default CORS response headers.
     *
     * @return array
     */
    public static function get_default_headers() {
        $result = [];
        $result[] = "Access-Control-Allow-Origin: " . self::$httporigin;
        $result[] = 'Access-Control-Allow-Credentials: true';
        $result[] = 'Access-Control-Max-Age: 86400'; // Cache for 1 day.
        $result[] = "Access-Control-Allow-Methods: GET, POST, OPTIONS";
        $result[] = "Access-Control-Allow-Headers: Origin, Accept, Content-Type";
        return $result;
    }

    /**
     * Reads the incoming request into $this->request.
     */
    public function get_request() {
        if (empty($_REQUEST)) {
            $this->request = json_decode(file_get_contents('php://input'), true);
            if ($this->request == null) {
                $this->request = [];
            }
        } else {
            $this->request = $_REQUEST;
        }
    }

    /**
     * Validates the session key and the presence of a requested service.
     *
     * @return bool
     */
    public function pre_process_request() {
        $sesskey = (isset($this->request['sesskey']) ? clean_param($this->request['sesskey'], PARAM_TEXT) : 'nosesskey');

        if (!confirm_sesskey($sesskey)) {
            $this->lastresult = new WebApiResult(false, null, 'invalidsesskey');
            return false;
        }

        if (!isset($this->request['service'])) {
            $msg = 'servicenotfound';
            $success = false;

            if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == "OPTIONS") {
                $msg = "Replying OPTIONS request";
                $success = true;
            }

            $this->lastresult = new WebApiResult($success, null, $msg);
            return false;
        }

        return true;
    }

    /**
     * Returns the list of service method names this API allows clients to call.
     *
     * @return array
     */
    protected function get_allowed_services(): array {
        return [];
    }

    /**
     * Validates and dispatches the requested service.
     */
    public function process_request() {
        if (!$this->pre_process_request()) {
            return;
        }

        $servicewanted = clean_param($this->request['service'], PARAM_TEXT);

        $allowed = $this->get_allowed_services();
        if (!in_array($servicewanted, $allowed, true)) {
            $this->lastresult = new WebApiResult(false, null, 'servicenotfound');
            return;
        }

        $result = $this->$servicewanted($this->request);

        $this->lastresult = $result;
    }

    /**
     * Sends the last result back to the client.
     */
    public function reply_client() {
        self::$lasterror = error_get_last();
        if (self::$lasterror != null) {
            return;
        }

        $webapiresult = $this->lastresult;
        $headers = self::get_default_headers();
        $result = json_encode($webapiresult);

        switch ($webapiresult->contenttype) {
            case 'json':
                $headers[] = "Content-type: application/json; charset=utf-8";
                break;
            case 'html':
                $headers[] = "Content-type: text/html; charset=utf-8";
                break;
            case 'octet-stream':
            case 'application/csv':
            case 'application/xml':
                $headers[] = sprintf("Content-type: %s", $webapiresult->contenttype);
                $headers[] = "Content-Description: File Transfer";
                $headers[] = sprintf('Content-Disposition: attachment; filename="%s"', basename($webapiresult->data->filename));
                $headers[] = 'Content-Transfer-Encoding: binary';
                $headers[] = 'Expires: 0';
                $headers[] = 'Cache-Control: must-revalidate';
                $headers[] = 'Pragma: public';
                $headers[] = sprintf('Content-Length: %s', filesize($webapiresult->data->filename));
                $result = file_get_contents($webapiresult->data->filename);
                @unlink($webapiresult->data->filename);
                break;
            default:
                $headers[] = "Content-type: text; charset=utf-8";
        }

        foreach ($headers as $header) {
            header($header);
        }

        if (ob_get_length() > 0) {
            ob_clean();
        }

        flush();
        echo $result;
    }

    /**
     * Recursively converts DateTime properties to client-friendly strings.
     *
     * @param mixed $obj
     */
    protected function prepare_json($obj) {
        if (is_object($obj)) {
            $tmp = get_object_vars($obj);
            foreach ($tmp as $attr => $value) {
                if ($value instanceof DateTime) {
                    $obj->$attr = $this->php_dt_to_js_dt($value);
                } else if (is_array($value)) {
                    foreach ($value as $item) {
                        $this->prepare_json($item);
                    }
                } else if (is_object($value)) {
                    $this->prepare_json($value);
                }
            }
        }
    }

    /**
     * Convert the PHP DateTime Object to be sent to the client (JavaScript date time string).
     *
     * @param DateTime|null $value
     * @return string
     */
    protected function php_dt_to_js_dt($value) {
        // Force the conversion to UTC date DateTime::ATOM.
        return ($value == null ? "" : $value->format("Y-m-d H:i:s"));
    }

    /**
     * Convert the JavaScript date string to PHP DateTime Object.
     *
     * @param string|null $value
     * @return DateTime|null
     */
    protected function js_dt_to_php_dt($value) {
        // Force the conversion to UTC date.
        return (empty($value) ? null : new DateTime($value, new DateTimeZone("UTC")));
    }

    /**
     * Splits a comma-separated request field into an array.
     *
     * @param array $request
     * @param string $field
     * @return array
     */
    protected function js_array_to_php_array($request, $field) {
        if (isset($request[$field])) {
            if (strlen($request[$field]) > 0) {
                return explode(",", $request[$field]);
            }
        }

        return [];
    }

    /**
     * Writes rows to a CSV file.
     *
     * @param string $filename
     * @param array $content
     * @param string $charset
     * @param string $delimiter
     * @return stdClass
     */
    protected function create_csv_file($filename, $content, $charset = "ISO-8859-1", $delimiter = ";") {
        try {
            $fp = fopen($filename, 'w');
            if (!$fp) {
                throw new Exception("FAILED: It was not possible to create the temporary file.");
            }

            foreach ($content as $row) {
                $nbcols = count($row);
                for ($icol = 0; $icol < $nbcols; $icol++) {
                    $row[$icol] = mb_convert_encoding($row[$icol], 'ISO-8859-1', 'UTF-8');
                }
                fputcsv($fp, $row, $delimiter);
            }

            fclose($fp);

            $data = new stdClass();
            $data->filename = $filename;
            $data->charset = $charset;
            return $data;
        } catch (Exception $ex) {
            throw $ex;
        }
    }
}

register_shutdown_function(function () {
    return AWebApi::on_php_error();
});
