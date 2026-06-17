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
 * AJAX service dispatcher for the recitannotation assign feedback plugin.
 *
 * @package   assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace recitannotation;

require_once(dirname(__FILE__) . '/../../../../../../config.php');
require_login(null, false, null, false, true);
require_once(dirname(__FILE__) . '/recitcommon/MoodleApi.php');
require_once(dirname(__FILE__) . '/PersistCtrl.php');
require_once(dirname(__FILE__) . '/Options.php');

use Exception;
use stdClass;

/**
 * AJAX service dispatcher for the recitannotation assign feedback plugin.
 */
class WebApi extends MoodleApi {
    /** @var PersistCtrl */
    protected $ctrl = null;

    /**
     * Constructor.
     *
     * @param \moodle_database $db
     * @param \stdClass $course
     * @param \stdClass $user
     */
    public function __construct($db, $course, $user) {
        parent::__construct($db, $course, $user);
        $this->ctrl = PersistCtrl::get_instance($db, $user);
    }

    /**
     * Returns the list of service method names this API allows clients to call.
     *
     * @return array
     */
    protected function get_allowed_services(): array {
        return [
            'get_annotation_form_kit',
            'delete_annotation',
            'save_criterion',
            'delete_criterion',
            'delete_all_criteria',
            'save_annotation',
            'save_comment',
            'delete_comment',
            'export_criteria_list',
            'import_criteria_list',
            'change_criterion_sort_order',
            'call_azure_ai',
            'save_prompt_ai',
        ];
    }

    /**
     * Checks whether the current user is allowed to act at the given access level.
     *
     * $level: "a" = admin/teacher, "s" = student.
     *
     * @param string $level
     * @param int $assignment
     * @return bool
     */
    public function can_user_access($level, $assignment) {
        $isteacher = $this->ctrl->has_teacher_access($assignment);

        // If the level is admin then the user must have access to CAPABILITY.
        if (($level == 'a') && $isteacher) {
            return true;
        } else if (($level == 's')) {
            // If the user is student then it has access only if it is accessing its own stuff.
            return true;
        } else {
            throw new Exception(get_string('access_denied', 'assignfeedback_recitannotation'));
        }
    }

    /**
     * Returns the annotation, criteria, comments and AI prompt needed by the grading form.
     *
     * @param array $request
     * @return WebApiResult
     */
    public function get_annotation_form_kit($request) {
        try {
            $assignment = clean_param($request['assignment'], PARAM_INT);
            $attemptnumber = clean_param($request['attemptnumber'], PARAM_INT);
            $userid = clean_param($request['userid'], PARAM_INT);

            $this->can_user_access('a', $assignment);

            $result = new stdClass();
            $result->annotation = $this->ctrl->get_annotation($assignment, $userid, $attemptnumber);
            $result->criteriaList = $this->ctrl->get_criteria_list($assignment);
            $result->commentList = $this->ctrl->get_comment_list($assignment);
            $result->promptAi = $this->ctrl->get_prompt_ai($assignment);
            $this->prepare_json($result);
            return new WebApiResult(true, $result);
        } catch (Exception $ex) {
            return new WebApiResult(false, false, $ex->getMessage());
        }
    }

    /**
     * Deletes an annotation.
     *
     * @param array $request
     * @return WebApiResult
     */
    public function delete_annotation($request) {
        try {
            $assignment = clean_param($request['assignment'], PARAM_INT);
            $id = clean_param($request['id'], PARAM_INT);

            $this->can_user_access('a', $assignment);

            $this->ctrl->delete_annotation($id, $assignment);
            return new WebApiResult(true);
        } catch (Exception $ex) {
            return new WebApiResult(false, false, $ex->getMessage());
        }
    }

    /**
     * Saves (inserts or updates) a criterion.
     *
     * @param array $request
     * @return WebApiResult
     */
    public function save_criterion($request) {
        try {
            $data = json_decode(json_encode($request['data']), false);

            $data->id = clean_param($data->id, PARAM_INT);
            $data->assignment = clean_param($data->assignment, PARAM_INT);
            $data->name = clean_param($data->name, PARAM_TEXT);
            $data->description = clean_param($data->description, PARAM_TEXT);
            $data->backgroundcolor = clean_param($data->backgroundcolor, PARAM_TEXT);
            $data->sortorder = clean_param($data->sortorder, PARAM_INT);
            $data->instruction_ai = clean_param($data->instruction_ai, PARAM_RAW);

            $this->can_user_access('a', $data->assignment);

            $result = $this->ctrl->save_criterion($data);

            return new WebApiResult(true, $result);
        } catch (Exception $ex) {
            return new WebApiResult(false, false, $ex->getMessage());
        }
    }

    /**
     * Deletes a criterion.
     *
     * @param array $request
     * @return WebApiResult
     */
    public function delete_criterion($request) {
        try {
            $assignment = clean_param($request['assignment'], PARAM_INT);
            $id = clean_param($request['id'], PARAM_INT);

            $this->can_user_access('a', $assignment);

            $this->ctrl->delete_criterion($id, $assignment);
            return new WebApiResult(true);
        } catch (Exception $ex) {
            return new WebApiResult(false, false, $ex->getMessage());
        }
    }

    /**
     * Deletes all criteria for an assignment.
     *
     * @param array $request
     * @return WebApiResult
     */
    public function delete_all_criteria($request) {
        try {
            $assignment = clean_param($request['assignment'], PARAM_INT);

            $this->can_user_access('a', $assignment);

            $this->ctrl->delete_all_criteria($assignment);
            return new WebApiResult(true);
        } catch (Exception $ex) {
            return new WebApiResult(false, false, $ex->getMessage());
        }
    }

    /**
     * Saves (inserts or updates) an annotation.
     *
     * @param array $request
     * @return WebApiResult
     */
    public function save_annotation($request) {
        try {
            $data = json_decode(json_encode($request['data']), false);
            $assignment = clean_param($request['assignment'], PARAM_INT);

            $data->id = clean_param($data->id, PARAM_INT);
            $data->submission = clean_param($data->submission, PARAM_INT);
            // Intentional HTML: teacher-authored annotation markup.
            $data->annotation = clean_param($data->annotation, PARAM_RAW);
            if (is_object($data->occurrences)) {
                foreach ($data->occurrences as $key => $val) {
                    $data->occurrences->$key = (int)$val;
                }
            }

            $this->can_user_access('a', $assignment);

            $result = $this->ctrl->save_annotation($data, $assignment);

            return new WebApiResult(true, $result);
        } catch (Exception $ex) {
            return new WebApiResult(false, false, $ex->getMessage());
        }
    }

    /**
     * Saves (inserts or updates) a comment.
     *
     * @param array $request
     * @return WebApiResult
     */
    public function save_comment($request) {
        try {
            $data = json_decode(json_encode($request['data']), false);
            $assignment = clean_param($request['assignment'], PARAM_INT);

            $data->id = clean_param($data->id, PARAM_INT);
            $data->criterionid = clean_param($data->criterionid, PARAM_INT);
            $data->comment = clean_param($data->comment, PARAM_TEXT);

            $this->can_user_access('a', $assignment);

            $result = $this->ctrl->save_comment($data, $assignment);

            return new WebApiResult(true, $result);
        } catch (Exception $ex) {
            return new WebApiResult(false, false, $ex->getMessage());
        }
    }

    /**
     * Deletes a comment.
     *
     * @param array $request
     * @return WebApiResult
     */
    public function delete_comment($request) {
        try {
            $id = clean_param($request['id'], PARAM_INT);
            $assignment = clean_param($request['assignment'], PARAM_INT);

            $this->can_user_access('a', $assignment);

            $this->ctrl->delete_comment($id, $assignment);
            return new WebApiResult(true);
        } catch (Exception $ex) {
            return new WebApiResult(false, false, $ex->getMessage());
        }
    }

    /**
     * Exports the criteria list, comments and AI prompt as an XML file.
     *
     * @param array $request
     * @return WebApiResult
     */
    public function export_criteria_list($request) {
        try {
            $assignment = clean_param($request['assignment'], PARAM_INT);
            $this->can_user_access('a', $assignment);

            $criterialist = $this->ctrl->get_criteria_list($assignment);
            $commentlist = $this->ctrl->get_comment_list($assignment);
            $promptailist = $this->ctrl->get_prompt_ai($assignment);

            $doc = new \DOMDocument('1.0', 'UTF-8');
            $doc->formatOutput = true;

            $root = $doc->createElement('root');
            $doc->appendChild($root);

            $promptai = $doc->createElement('prompt_ai');
            $root->appendChild($promptai);
            $payload = $doc->createElement('payload');
            $payload->appendChild($doc->createCDATASection($promptailist->prompt_ai));
            $promptai->appendChild($payload);

            $criteria = $doc->createElement('criteria');
            $root->appendChild($criteria);

            foreach ($criterialist as $criteriondata) {
                $criterion = $doc->createElement('criterion');
                $criterion->appendChild($doc->createElement('name', $criteriondata->name));
                $criterion->appendChild($doc->createElement('description', $criteriondata->description));
                $criterion->appendChild($doc->createElement('backgroundcolor', $criteriondata->backgroundcolor));
                $criterion->appendChild($doc->createElement('sortorder', $criteriondata->sortorder));
                $criterion->appendChild($doc->createElement('instruction_ai', $criteriondata->instruction_ai));
                $criteria->appendChild($criterion);

                $comments = $doc->createElement('comments');
                $criterion->appendChild($comments);

                foreach ($commentlist as $commentdata) {
                    if ($commentdata->criterionid != $criteriondata->id) {
                        continue;
                    }
                    $comment = $doc->createElement('comment');
                    $comment->appendChild($doc->createElement('comment', $commentdata->comment));
                    $comments->appendChild($comment);
                }
            }

            $file = new stdClass();
            $file->filename = tempnam(sys_get_temp_dir(), 'recitannot_export_') . ".xml";
            $file->charset = 'UTF-8';
            $doc->save($file->filename);

            return new WebApiResult(true, $file, "", 'application/xml');
        } catch (Exception $ex) {
            return new WebApiResult(false, null, $ex->getMessage());
        }
    }

    /**
     * Imports a criteria list (and AI prompt) from an exported XML file.
     *
     * @param array $request
     * @return WebApiResult
     */
    public function import_criteria_list($request) {
        try {
            $data = json_decode(json_encode($request['data']), false);

            $data->assignment = clean_param($data->assignment, PARAM_INT);
            $data->fileContent = clean_param($data->fileContent, PARAM_RAW);

            $this->can_user_access('a', $data->assignment);

            $this->ctrl->import_criteria_list($data);
            return new WebApiResult(true);
        } catch (Exception $ex) {
            return new WebApiResult(false, null, $ex->getMessage());
        }
    }

    /**
     * Moves a criterion up or down in the sort order.
     *
     * @param array $request
     * @return WebApiResult
     */
    public function change_criterion_sort_order($request) {
        try {
            $id = clean_param($request['id'], PARAM_INT);
            $direction = clean_param($request['direction'], PARAM_TEXT);
            $assignment = clean_param($request['assignment'], PARAM_INT);

            $this->can_user_access('a', $assignment);

            $result = $this->ctrl->change_criterion_sort_order($id, $direction, $assignment);
            return new WebApiResult(true, $result);
        } catch (Exception $ex) {
            return new WebApiResult(false, false, $ex->getMessage());
        }
    }

    /**
     * Forwards the student text and prompt to the configured Azure AI endpoint.
     *
     * @param array $request
     * @return WebApiResult
     */
    public function call_azure_ai($request) {
        try {
            $assignment = clean_param($request['assignment'], PARAM_INT);
            $payload = json_decode(json_encode($request['payload']), false);

            $this->can_user_access('a', $assignment);

            // Replace these with your Azure details.
            $endpoint = Options::get_ai_api_endpoint();
            $apikey = Options::get_ai_api_key();

            // Setup headers.
            $headers = [
                "Content-Type: application/json",
                "api-key: $apikey",
            ];

            // Initialize cURL.
            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            // Execute request.
            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                unset($ch);
                throw new Exception($error);
            }

            unset($ch);

            return new WebApiResult(true, json_decode($response));
        } catch (Exception $ex) {
            return new WebApiResult(false, null, $ex->getMessage());
        }
    }

    /**
     * Saves (inserts or updates) the AI prompt for an assignment.
     *
     * @param array $request
     * @return WebApiResult
     */
    public function save_prompt_ai($request) {
        try {
            $data = json_decode(json_encode($request['data']), false);

            $data->id = clean_param($data->id, PARAM_INT);
            $data->assignment = clean_param($data->assignment, PARAM_INT);
            $data->prompt_ai = clean_param($data->prompt_ai, PARAM_RAW);

            $this->can_user_access('a', $data->assignment);

            $result = $this->ctrl->save_prompt_ai($data);

            return new WebApiResult(true, $result);
        } catch (Exception $ex) {
            return new WebApiResult(false, false, $ex->getMessage());
        }
    }
}

$PAGE->set_context(\context_system::instance());
$webapi = new WebApi($DB, $COURSE, $USER);
$webapi->get_request();
$webapi->process_request();
$webapi->reply_client();
