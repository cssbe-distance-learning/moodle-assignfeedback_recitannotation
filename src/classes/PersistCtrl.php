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
 * @package   assignfeedback_recitannotation
 * @copyright 2025 RÉCIT 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace recitannotation;

require_once "$CFG->dirroot/user/externallib.php";
require_once __DIR__ . '/recitcommon/PersistCtrl.php';

use DateTime;
use Exception;
use stdClass;

/**
 * Singleton class
 */
class PersistCtrl extends MoodlePersistCtrl
{
    protected static $instance = null;
    
    /**
     * @param MySQL Resource
     * @return PersistCtrl
     */
    public static function getInstance($mysqlConn = null, $signedUser = null)
    {
        if(!isset(self::$instance)) {
            self::$instance = new self($mysqlConn, $signedUser);
        }
        return self::$instance;
    }
    
    protected function __construct($mysqlConn, $signedUser){
        parent::__construct($mysqlConn, $signedUser);
    }
    
    public function hasTeacherAccess($assignment){
        global $DB, $USER;

        $assign = $DB->get_record('assign', ['id' => $assignment], '*', MUST_EXIST);

        $context = \context_course::instance($assign->course);
        $roles = get_user_roles($context, $USER->id, true);

        foreach ($roles as $role) {
            if ($role->shortname == 'editingteacher' || $role->shortname == 'teacher') {
                return true;
            }
        }
        
        if (has_capability('moodle/course:update', $context) || has_capability('moodle/grade:viewall', $context)) {
            return true;
        } 
        
        return false;
    }

    public function getAnnotation($assignmentId, $userId, $attemptnumber = 0){
        $query = "select ". $this->sql_uniqueid() ." uniqueid, coalesce(t1.id, 0) id, 
        coalesce(t1.submission, t2.id) as submission, t1.ownerid, coalesce(t1.annotation, t3.onlinetext) as annotation,
        coalesce(t1.occurrences, '') as occurrences, t1.lastupdate
        from {assign_submission} t2
        inner join {assignsubmission_onlinetext} t3 on t2.id = t3.submission 
        left join {assignfeedback_recitannotation} t1 on t2.id = t1.submission
        where t2.assignment = ? and t2.userid = ? and t2.attemptnumber = ? ";

        $rst = $this->getRecordsSQL($query, [$assignmentId, $userId, $attemptnumber]);

        $rst = array_pop($rst);

        $result = RecitAnnotation::create($rst);

        // clean html tags if text has no annotation
        if($result->id == 0){
            $result->annotation = strip_tags($result->annotation, ['<br>', '<p>']);
        }
        
        return $result;
    }

    public function saveAnnotation($data, $assignment = 0){
        try{
            if($data->id != 0 && $assignment > 0){
                if(!$this->annotationBelongsToAssignment($data->id, $assignment)){
                    throw new \Exception(get_string('access_denied', 'assignfeedback_recitannotation'));
                }
            }

            $record = new stdClass();
            $record->submission = $data->submission;
            $record->ownerid = $this->signedUser->id;
            $record->annotation = $data->annotation;
            $record->occurrences = json_encode($data->occurrences);
            $record->lastupdate = time();

            $lastid = $data->id;
            if($data->id == 0){
                $lastid = $this->mysqlConn->insert_record("assignfeedback_recitannotation", $record);
            }
            else{
                $record->id = $data->id;
                $this->mysqlConn->update_record("assignfeedback_recitannotation", $record);
            }

            return $lastid;
        }
        catch(\Exception $ex){
            throw $ex;
        }
    }

    public function deleteAnnotation($id, $assignment = 0){
        try{
            if($assignment > 0 && !$this->annotationBelongsToAssignment($id, $assignment)){
                throw new \Exception(get_string('access_denied', 'assignfeedback_recitannotation'));
            }

            $this->mysqlConn->delete_records("assignfeedback_recitannotation", ['id' => $id]);
            return true;
        }
        catch(Exception $ex){
            throw $ex;
        }
    }

    protected function annotationBelongsToAssignment($annotationId, $assignment){
        $query = "SELECT t1.id FROM {assignfeedback_recitannotation} t1
                  INNER JOIN {assign_submission} t2 ON t1.submission = t2.id
                  WHERE t1.id = ? AND t2.assignment = ?";
        $records = $this->getRecordsSQL($query, [$annotationId, $assignment]);
        return !empty($records);
    }

    public function deletePluginData($assignment){
        global $DB;

        try{
            // delete comments
            $query = "select t1.id
                from {assignfeedback_recitannotation_comment} as t1
                inner join {assignfeedback_recitannotation_crit} as t2 on t1.criterionid = t2.id
                where t2.assignment = ?";
            $rst = $this->getRecordsSQL($query, [$assignment]);

            $ids = array();
            foreach($rst as $item){
                $ids[] = $item->id;
            }

            if(count($ids) > 0){
                list($in_sql, $params) = $DB->get_in_or_equal($ids);
                $DB->delete_records_select('assignfeedback_recitannotation_comment', "id $in_sql", $params);
            }

            // delete criterias
            $this->mysqlConn->delete_records("assignfeedback_recitannotation_crit", ['assignment' => $assignment]);

            // delete annotations
            $query = "select t1.id
                    from {assign_submission} t2
                    inner join {assignfeedback_recitannotation} t1 on t2.id = t1.submission
                    where t2.assignment = ?";
            $rst = $this->getRecordsSQL($query, [$assignment]);       

            $ids = array();
            foreach($rst as $item){
                $ids[] = $item->id;
            }

            if(count($ids) > 0){
                list($in_sql, $params) = $DB->get_in_or_equal($ids);
                $DB->delete_records_select('assignfeedback_recitannotation', "id $in_sql", $params);
            }
            
            return true;
        }
        catch(Exception $ex){
            throw $ex;
        }
    }

    public function saveCriterion($data){
        try{
            $record = new TableCriterion();
            $record->assignment = $data->assignment;
            $record->name = $data->name;
            $record->description = $data->description;
            $record->backgroundcolor = $data->backgroundcolor;
            $record->sortorder = $data->sortorder;
            $record->instruction_ai = $data->instruction_ai;

            if($data->id == 0){
                if (!$this->mysqlConn->record_exists('assignfeedback_recitannotation_crit', ['name' => $record->name, 'assignment' => $record->assignment])) {
                    // returns inserted ID
                    $record->id = $this->mysqlConn->insert_record("assignfeedback_recitannotation_crit", $record, true);
                }
            }
            else{
                // Verify the criterion belongs to the authorized assignment before updating.
                $existing = $this->mysqlConn->get_record('assignfeedback_recitannotation_crit', ['id' => $data->id], '*', MUST_EXIST);
                if((int)$existing->assignment !== (int)$data->assignment){
                    throw new \Exception(get_string('access_denied', 'assignfeedback_recitannotation'));
                }
                $record->id = $data->id;
                $this->mysqlConn->update_record("assignfeedback_recitannotation_crit", $record);
            }

            return $record;
        }
        catch(\Exception $ex){
            throw $ex;
        }
    }

    public function getLastSortOrder($assignment){
        $query = "select coalesce(max(sortorder),0) as sortorder from {assignfeedback_recitannotation_crit} 
                where assignment = ?";

        $result = $this->getRecordsSQL($query, array($assignment));

        return array_pop($result);
    }

    public function getCriteriaList($assignment){
        $query = "select id, assignment, name, description, backgroundcolor, sortorder, coalesce(instruction_ai, '') as instruction_ai from {assignfeedback_recitannotation_crit} 
                where assignment = ?
                order by sortorder asc";

        $result = $this->getRecordsSQL($query, array($assignment), true);

        return $result;
    }

    public function deleteCriterion($id, $assignment = 0){
        try{
            $current = $this->mysqlConn->get_record('assignfeedback_recitannotation_crit', ['id' => $id], '*', MUST_EXIST);

            if($assignment > 0 && (int)$current->assignment !== (int)$assignment){
                throw new \Exception(get_string('access_denied', 'assignfeedback_recitannotation'));
            }

            if($current){
                $this->mysqlConn->delete_records("assignfeedback_recitannotation_comment", ['criterionid' => $id]);
                $this->mysqlConn->delete_records("assignfeedback_recitannotation_crit", ['id' => $id]);
                $this->resequenceSortOrder($current->assignment);
            }

            return true;
        }
        catch(Exception $ex){
            throw $ex;
        }
    }

    public function deleteAllCriteria($assignment){
        try{
            $criteriaList = $this->getCriteriaList($assignment);

            $ids = array();
            foreach($criteriaList as $item){
                $ids[] = $item->id;
            }

            list($sql, $params) = $this->mysqlConn->get_in_or_equal($ids);
            $this->mysqlConn->delete_records_select('assignfeedback_recitannotation_comment', "criterionid $sql", $params);

            $this->mysqlConn->delete_records("assignfeedback_recitannotation_crit", ['assignment' => $assignment]);

            return true;
        }
        catch(Exception $ex){
            throw $ex;
        }
    }

    public function getCommentList($assignment){
        $query = "SELECT t1.id, t1.criterionid, t2.name,  t2.description, t1.comment 
                    FROM {assignfeedback_recitannotation_comment} as t1
                    inner join {assignfeedback_recitannotation_crit} as t2 on t1.criterionid = t2.id
                    where t2.assignment = ?
                    order by t2.sortorder, length(comment) asc, comment asc";

        $result = $this->getRecordsSQL($query, array($assignment));

        return $result;
    }

    public function deleteComment($id, $assignment = 0){
        try{
            if($assignment > 0 && !$this->commentBelongsToAssignment($id, $assignment)){
                throw new \Exception(get_string('access_denied', 'assignfeedback_recitannotation'));
            }

            $this->mysqlConn->delete_records("assignfeedback_recitannotation_comment", ['id' => $id]);
            return true;
        }
        catch(Exception $ex){
            throw $ex;
        }
    }

    public function saveComment($data, $assignment = 0){
        try{
            $record = new TableComment();
            $record->criterionid = $data->criterionid;
            $record->comment = $data->comment;

            if($data->id == 0){
                if (!$this->mysqlConn->record_exists('assignfeedback_recitannotation_comment', ['criterionid' => $record->criterionid, 'comment' => $record->comment])) {
                    $this->mysqlConn->insert_record("assignfeedback_recitannotation_comment", $record);
                }
            }
            else{
                if($assignment > 0 && !$this->commentBelongsToAssignment($data->id, $assignment)){
                    throw new \Exception(get_string('access_denied', 'assignfeedback_recitannotation'));
                }
                $record->id = $data->id;
                $this->mysqlConn->update_record("assignfeedback_recitannotation_comment", $record);
            }

            return true;
        }
        catch(\Exception $ex){
            throw $ex;
        }
    }

    protected function commentBelongsToAssignment($commentId, $assignment){
        $query = "SELECT t1.id FROM {assignfeedback_recitannotation_comment} t1
                  INNER JOIN {assignfeedback_recitannotation_crit} t2 ON t1.criterionid = t2.id
                  WHERE t1.id = ? AND t2.assignment = ?";
        $records = $this->getRecordsSQL($query, [$commentId, $assignment]);
        return !empty($records);
    }

    public function importCriteriaList($data){
        try{
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($data->fileContent);

            if ($xml === false) {
                $msg = "";
                foreach(libxml_get_errors() as $error) {
                    $msg .= "\t" . $error->message;
                }

                throw new Exception(get_string('err_xml_parse', 'assignfeedback_recitannotation', $msg));
            }

            $sortOrderObj = $this->getLastSortOrder($data->assignment);

            $prompt_ai = new TablePromptAi();
            $prompt_ai->assignment = (int) $data->assignment;
            $prompt_ai->prompt_ai = (string) $xml->prompt_ai->payload;
            $this->savePromptAi($prompt_ai);

            // Loop through each criterion
            foreach ($xml->criteria->criterion as $item) {
                $criterion = new TableCriterion();
                $criterion->assignment = (int) $data->assignment;
                $criterion->name = (string) $item->name;
                $criterion->description = (string) $item->description;
                $criterion->backgroundcolor = (string) $item->backgroundcolor;
                $criterion->sortorder = ++$sortOrderObj->sortorder;

                if(isset($item->instruction_ai)){
                    $criterion->instruction_ai = (string) $item->instruction_ai;
                }
                
                $criterion = $this->saveCriterion($criterion);

                // Handle comments
                foreach ($item->comments->comment as $item2) {
                    $comment = new TableComment();
                    $comment->criterionid = $criterion->id;
                    $comment->comment = (string) $item2->comment;
                    $this->saveComment($comment);
                }
            }
           
            return true;
        }
         catch(Exception $ex){
            throw $ex;
        }
    }

    public function changeCriterionSortOrder($id, $direction, $assignment = 0){
        // 1. Get current item
        $current = $this->mysqlConn->get_record('assignfeedback_recitannotation_crit', ['id' => $id], '*', MUST_EXIST);

        if($assignment > 0 && (int)$current->assignment !== (int)$assignment){
            throw new \Exception(get_string('access_denied', 'assignfeedback_recitannotation'));
        }

        // 2. Determine target sortorder
        $targetSort = ($direction === 'up') ? $current->sortorder - 1 : $current->sortorder + 1;

        // 3. Get adjacent item
        $adjacent = $this->mysqlConn->get_record('assignfeedback_recitannotation_crit', 
                [
                    'sortorder' => $targetSort,
                    'assignment' => $current->assignment
                ]
            );

        if ($adjacent) {
            // 4. Swap sortorders
            $this->mysqlConn->update_record('assignfeedback_recitannotation_crit', ['id' => $current->id, 'sortorder' => $adjacent->sortorder]);
            $this->mysqlConn->update_record('assignfeedback_recitannotation_crit', ['id' => $adjacent->id, 'sortorder' => $current->sortorder]);
            return true;
        } else {
            // Can't move (e.g. already at top or bottom)
            return false;
        }
    }

    public function resequenceSortOrder($assignment) {
        // Get items ordered by current sortorder
        $items = $this->mysqlConn->get_records('assignfeedback_recitannotation_crit', ['assignment' => $assignment], 'sortorder ASC');

        $i = 1;
        foreach ($items as $item) {
            if ($item->sortorder != $i) {
                $item->sortorder = $i;
                $this->mysqlConn->update_record('assignfeedback_recitannotation_crit', $item);
            }
            $i++;
        }
    }

    public function getPromptAi($assignment){
        $query = "SELECT * 
                    FROM {assignfeedback_recitannotation_promptai} 
                    where assignment = ?";

        $result = $this->getRecordsSQL($query, array($assignment), true);

        return (count($result) > 0 ? array_shift($result) : new TablePromptAi());
    }

    public function savePromptAi($data){
        try{	
            $record = $this->getPromptAi($data->assignment);

            if($record->id == 0){
                $record->assignment = $data->assignment;
                $record->prompt_ai = $data->prompt_ai;
                $this->mysqlConn->insert_record("assignfeedback_recitannotation_promptai", $record);
            }
            else{
                $record->prompt_ai = $data->prompt_ai;
                $this->mysqlConn->update_record("assignfeedback_recitannotation_promptai", $record);
            }

            return true;
        }
        catch(\Exception $ex){
            throw $ex;
        }
    }
}

class RecitAnnotation{
    public $id = 0;
    public $submission = 0;
    public $ownerid = 0;
    public $annotation = "";
    public $occurrences = "";
    public $lastupdate = 0;

    public static function create($dbData){
        $result = new RecitAnnotation();

        if($dbData == null){
            return $result;
        }
        
        $result->id = intval($dbData->id);
        $result->submission = intval($dbData->submission);
        $result->ownerid = intval($dbData->ownerid);
        $result->annotation = $dbData->annotation; 
        $result->occurrences = $dbData->occurrences; 
        $result->lastupdate = intval($dbData->lastupdate);
        return $result;
    }
}

class TableCriterion{
    public $id = 0;
    public $assignment = 0;
    public $name = "";
    public $description = "";
    public $backgroundcolor = "";
    public $sortorder = 0;
    public $instruction_ai = "";
}

class TableComment{
    public $id = 0;
    public $criterionid = 0;
    public $comment = "";
}

class TablePromptAi{
    public $id = 0;
    public $assignment = 0;
    public $prompt_ai = "";
}