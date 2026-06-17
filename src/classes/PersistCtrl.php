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
 * Persistence controller for the recitannotation assign feedback plugin.
 *
 * @package   assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace recitannotation;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/externallib.php');
require_once(__DIR__ . '/recitcommon/MoodlePersistCtrl.php');
require_once(__DIR__ . '/RecitAnnotation.php');
require_once(__DIR__ . '/TableCriterion.php');
require_once(__DIR__ . '/TableComment.php');
require_once(__DIR__ . '/TablePromptAi.php');

use DateTime;
use Exception;
use stdClass;

/**
 * Singleton persistence controller.
 */
class PersistCtrl extends MoodlePersistCtrl {
    /** @var PersistCtrl|null */
    protected static $instance = null;

    /**
     * Returns the singleton instance.
     *
     * @param \moodle_database|null $mysqlconn
     * @param \stdClass|null $signeduser
     * @return PersistCtrl
     */
    public static function get_instance($mysqlconn = null, $signeduser = null) {
        if (!isset(self::$instance)) {
            self::$instance = new self($mysqlconn, $signeduser);
        }
        return self::$instance;
    }

    /**
     * Whether the current user has teacher-level access to the assignment.
     *
     * @param int $assignment
     * @return bool
     */
    public function has_teacher_access($assignment) {
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

    /**
     * Returns the annotation for a given submission attempt.
     *
     * @param int $assignmentid
     * @param int $userid
     * @param int $attemptnumber
     * @return RecitAnnotation
     */
    public function get_annotation($assignmentid, $userid, $attemptnumber = 0) {
        $query = 'SELECT ' . $this->sql_uniqueid() . ' uniqueid, COALESCE(t1.id, 0) id,' .
            ' COALESCE(t1.submission, t2.id) AS submission, t1.ownerid,' .
            ' COALESCE(t1.annotation, t3.onlinetext) AS annotation,' .
            ' COALESCE(t1.occurrences, \'\') AS occurrences, t1.lastupdate' .
            ' FROM {assign_submission} t2' .
            ' INNER JOIN {assignsubmission_onlinetext} t3 ON t2.id = t3.submission' .
            ' LEFT JOIN {assignfeedback_recitannotation} t1 ON t2.id = t1.submission' .
            ' WHERE t2.assignment = ? AND t2.userid = ? AND t2.attemptnumber = ?';

        $rst = $this->get_records_sql($query, [$assignmentid, $userid, $attemptnumber]);

        $rst = array_pop($rst);

        $result = RecitAnnotation::create($rst);

        // Clean html tags if text has no annotation.
        if ($result->id == 0) {
            $result->annotation = strip_tags($result->annotation, ['<br>', '<p>']);
        }

        return $result;
    }

    /**
     * Saves (inserts or updates) an annotation.
     *
     * @param \stdClass $data
     * @param int $assignment
     * @return int the annotation id
     */
    public function save_annotation($data, $assignment = 0) {
        try {
            if ($data->id != 0 && $assignment > 0) {
                if (!$this->annotation_belongs_to_assignment($data->id, $assignment)) {
                    throw new \Exception(get_string('access_denied', 'assignfeedback_recitannotation'));
                }
            }

            $record = new stdClass();
            $record->submission = $data->submission;
            $record->ownerid = $this->signeduser->id;
            $record->annotation = $data->annotation;
            $record->occurrences = json_encode($data->occurrences);
            $record->lastupdate = time();

            $lastid = $data->id;
            if ($data->id == 0) {
                $lastid = $this->mysqlconn->insert_record("assignfeedback_recitannotation", $record);
            } else {
                $record->id = $data->id;
                $this->mysqlconn->update_record("assignfeedback_recitannotation", $record);
            }

            return $lastid;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Deletes an annotation.
     *
     * @param int $id
     * @param int $assignment
     * @return bool
     */
    public function delete_annotation($id, $assignment = 0) {
        try {
            if ($assignment > 0 && !$this->annotation_belongs_to_assignment($id, $assignment)) {
                throw new \Exception(get_string('access_denied', 'assignfeedback_recitannotation'));
            }

            $this->mysqlconn->delete_records("assignfeedback_recitannotation", ['id' => $id]);
            return true;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Whether the annotation belongs to the given assignment.
     *
     * @param int $annotationid
     * @param int $assignment
     * @return bool
     */
    protected function annotation_belongs_to_assignment($annotationid, $assignment) {
        $query = 'SELECT t1.id FROM {assignfeedback_recitannotation} t1' .
            ' INNER JOIN {assign_submission} t2 ON t1.submission = t2.id' .
            ' WHERE t1.id = ? AND t2.assignment = ?';
        $records = $this->get_records_sql($query, [$annotationid, $assignment]);
        return !empty($records);
    }

    /**
     * Deletes all plugin data (annotations, criteria, comments) for an assignment.
     *
     * @param int $assignment
     * @return bool
     */
    public function delete_plugin_data($assignment) {
        global $DB;

        try {
            // Delete comments.
            $query = 'SELECT t1.id' .
                ' FROM {assignfeedback_recitannotation_comment} t1' .
                ' INNER JOIN {assignfeedback_recitannotation_crit} t2 ON t1.criterionid = t2.id' .
                ' WHERE t2.assignment = ?';
            $rst = $this->get_records_sql($query, [$assignment]);

            $ids = [];
            foreach ($rst as $item) {
                $ids[] = $item->id;
            }

            if (count($ids) > 0) {
                [$insql, $params] = $DB->get_in_or_equal($ids);
                $DB->delete_records_select('assignfeedback_recitannotation_comment', "id $insql", $params);
            }

            // Delete criterias.
            $this->mysqlconn->delete_records("assignfeedback_recitannotation_crit", ['assignment' => $assignment]);

            // Delete annotations.
            $query = 'SELECT t1.id' .
                ' FROM {assign_submission} t2' .
                ' INNER JOIN {assignfeedback_recitannotation} t1 ON t2.id = t1.submission' .
                ' WHERE t2.assignment = ?';
            $rst = $this->get_records_sql($query, [$assignment]);

            $ids = [];
            foreach ($rst as $item) {
                $ids[] = $item->id;
            }

            if (count($ids) > 0) {
                [$insql, $params] = $DB->get_in_or_equal($ids);
                $DB->delete_records_select('assignfeedback_recitannotation', "id $insql", $params);
            }

            return true;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Saves (inserts or updates) a criterion.
     *
     * @param \stdClass $data
     * @return TableCriterion
     */
    public function save_criterion($data) {
        try {
            $record = new TableCriterion();
            $record->assignment = $data->assignment;
            $record->name = $data->name;
            $record->description = $data->description;
            $record->backgroundcolor = $data->backgroundcolor;
            $record->sortorder = $data->sortorder;
            $record->instruction_ai = $data->instruction_ai;

            if ($data->id == 0) {
                $exists = $this->mysqlconn->record_exists('assignfeedback_recitannotation_crit', [
                    'name' => $record->name,
                    'assignment' => $record->assignment,
                ]);
                if (!$exists) {
                    // Returns inserted ID.
                    $record->id = $this->mysqlconn->insert_record("assignfeedback_recitannotation_crit", $record, true);
                }
            } else {
                // Verify the criterion belongs to the authorized assignment before updating.
                $existing = $this->mysqlconn->get_record(
                    'assignfeedback_recitannotation_crit',
                    ['id' => $data->id],
                    '*',
                    MUST_EXIST
                );
                if ((int)$existing->assignment !== (int)$data->assignment) {
                    throw new \Exception(get_string('access_denied', 'assignfeedback_recitannotation'));
                }
                $record->id = $data->id;
                $this->mysqlconn->update_record("assignfeedback_recitannotation_crit", $record);
            }

            return $record;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Returns the highest sortorder currently in use for an assignment's criteria.
     *
     * @param int $assignment
     * @return \stdClass
     */
    public function get_last_sort_order($assignment) {
        $query = 'SELECT COALESCE(MAX(sortorder), 0) AS sortorder FROM {assignfeedback_recitannotation_crit}' .
            ' WHERE assignment = ?';

        $result = $this->get_records_sql($query, [$assignment]);

        return array_pop($result);
    }

    /**
     * Returns the list of criteria for an assignment, ordered by sortorder.
     *
     * @param int $assignment
     * @return array
     */
    public function get_criteria_list($assignment) {
        $query = 'SELECT id, assignment, name, description, backgroundcolor, sortorder,' .
            ' COALESCE(instruction_ai, \'\') AS instruction_ai' .
            ' FROM {assignfeedback_recitannotation_crit}' .
            ' WHERE assignment = ?' .
            ' ORDER BY sortorder ASC';

        $result = $this->get_records_sql($query, [$assignment], true);

        return $result;
    }

    /**
     * Deletes a criterion and resequences the remaining sort orders.
     *
     * @param int $id
     * @param int $assignment
     * @return bool
     */
    public function delete_criterion($id, $assignment = 0) {
        try {
            $current = $this->mysqlconn->get_record('assignfeedback_recitannotation_crit', ['id' => $id], '*', MUST_EXIST);

            if ($assignment > 0 && (int)$current->assignment !== (int)$assignment) {
                throw new \Exception(get_string('access_denied', 'assignfeedback_recitannotation'));
            }

            if ($current) {
                $this->mysqlconn->delete_records("assignfeedback_recitannotation_comment", ['criterionid' => $id]);
                $this->mysqlconn->delete_records("assignfeedback_recitannotation_crit", ['id' => $id]);
                $this->resequence_sort_order($current->assignment);
            }

            return true;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Deletes all criteria (and their comments) for an assignment.
     *
     * @param int $assignment
     * @return bool
     */
    public function delete_all_criteria($assignment) {
        try {
            $criterialist = $this->get_criteria_list($assignment);

            $ids = [];
            foreach ($criterialist as $item) {
                $ids[] = $item->id;
            }

            [$sql, $params] = $this->mysqlconn->get_in_or_equal($ids);
            $this->mysqlconn->delete_records_select('assignfeedback_recitannotation_comment', "criterionid $sql", $params);

            $this->mysqlconn->delete_records("assignfeedback_recitannotation_crit", ['assignment' => $assignment]);

            return true;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Returns the list of comments for an assignment's criteria.
     *
     * @param int $assignment
     * @return array
     */
    public function get_comment_list($assignment) {
        $query = 'SELECT t1.id, t1.criterionid, t2.name, t2.description, t1.comment' .
            ' FROM {assignfeedback_recitannotation_comment} t1' .
            ' INNER JOIN {assignfeedback_recitannotation_crit} t2 ON t1.criterionid = t2.id' .
            ' WHERE t2.assignment = ?' .
            ' ORDER BY t2.sortorder, LENGTH(comment) ASC, comment ASC';

        $result = $this->get_records_sql($query, [$assignment]);

        return $result;
    }

    /**
     * Deletes a comment.
     *
     * @param int $id
     * @param int $assignment
     * @return bool
     */
    public function delete_comment($id, $assignment = 0) {
        try {
            if ($assignment > 0 && !$this->comment_belongs_to_assignment($id, $assignment)) {
                throw new \Exception(get_string('access_denied', 'assignfeedback_recitannotation'));
            }

            $this->mysqlconn->delete_records("assignfeedback_recitannotation_comment", ['id' => $id]);
            return true;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Saves (inserts or updates) a comment.
     *
     * @param \stdClass $data
     * @param int $assignment
     * @return bool
     */
    public function save_comment($data, $assignment = 0) {
        try {
            $record = new TableComment();
            $record->criterionid = $data->criterionid;
            $record->comment = $data->comment;

            if ($data->id == 0) {
                $exists = $this->mysqlconn->record_exists('assignfeedback_recitannotation_comment', [
                    'criterionid' => $record->criterionid,
                    'comment' => $record->comment,
                ]);
                if (!$exists) {
                    $this->mysqlconn->insert_record("assignfeedback_recitannotation_comment", $record);
                }
            } else {
                if ($assignment > 0 && !$this->comment_belongs_to_assignment($data->id, $assignment)) {
                    throw new \Exception(get_string('access_denied', 'assignfeedback_recitannotation'));
                }
                $record->id = $data->id;
                $this->mysqlconn->update_record("assignfeedback_recitannotation_comment", $record);
            }

            return true;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Whether the comment belongs to the given assignment.
     *
     * @param int $commentid
     * @param int $assignment
     * @return bool
     */
    protected function comment_belongs_to_assignment($commentid, $assignment) {
        $query = 'SELECT t1.id FROM {assignfeedback_recitannotation_comment} t1' .
            ' INNER JOIN {assignfeedback_recitannotation_crit} t2 ON t1.criterionid = t2.id' .
            ' WHERE t1.id = ? AND t2.assignment = ?';
        $records = $this->get_records_sql($query, [$commentid, $assignment]);
        return !empty($records);
    }

    /**
     * Imports a criteria list (and AI prompt) from an exported XML file.
     *
     * @param \stdClass $data
     * @return bool
     */
    public function import_criteria_list($data) {
        try {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($data->fileContent);

            if ($xml === false) {
                $msg = "";
                foreach (libxml_get_errors() as $error) {
                    $msg .= "\t" . $error->message;
                }

                throw new Exception(get_string('err_xml_parse', 'assignfeedback_recitannotation', $msg));
            }

            $sortorderobj = $this->get_last_sort_order($data->assignment);

            $promptai = new TablePromptAi();
            $promptai->assignment = (int) $data->assignment;
            $promptai->prompt_ai = (string) $xml->prompt_ai->payload;
            $this->save_prompt_ai($promptai);

            // Loop through each criterion.
            foreach ($xml->criteria->criterion as $item) {
                $criterion = new TableCriterion();
                $criterion->assignment = (int) $data->assignment;
                $criterion->name = (string) $item->name;
                $criterion->description = (string) $item->description;
                $criterion->backgroundcolor = (string) $item->backgroundcolor;
                $criterion->sortorder = ++$sortorderobj->sortorder;

                if (isset($item->instruction_ai)) {
                    $criterion->instruction_ai = (string) $item->instruction_ai;
                }

                $criterion = $this->save_criterion($criterion);

                // Handle comments.
                foreach ($item->comments->comment as $item2) {
                    $comment = new TableComment();
                    $comment->criterionid = $criterion->id;
                    $comment->comment = (string) $item2->comment;
                    $this->save_comment($comment);
                }
            }

            return true;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Moves a criterion up or down in the sort order, swapping with its neighbour.
     *
     * @param int $id
     * @param string $direction "up" or "down"
     * @param int $assignment
     * @return bool
     */
    public function change_criterion_sort_order($id, $direction, $assignment = 0) {
        // 1. Get current item.
        $current = $this->mysqlconn->get_record('assignfeedback_recitannotation_crit', ['id' => $id], '*', MUST_EXIST);

        if ($assignment > 0 && (int)$current->assignment !== (int)$assignment) {
            throw new \Exception(get_string('access_denied', 'assignfeedback_recitannotation'));
        }

        // 2. Determine target sortorder.
        $targetsort = ($direction === 'up') ? $current->sortorder - 1 : $current->sortorder + 1;

        // 3. Get adjacent item.
        $adjacent = $this->mysqlconn->get_record('assignfeedback_recitannotation_crit', [
            'sortorder' => $targetsort,
            'assignment' => $current->assignment,
        ]);

        if ($adjacent) {
            // 4. Swap sortorders.
            $this->mysqlconn->update_record(
                'assignfeedback_recitannotation_crit',
                ['id' => $current->id, 'sortorder' => $adjacent->sortorder]
            );
            $this->mysqlconn->update_record(
                'assignfeedback_recitannotation_crit',
                ['id' => $adjacent->id, 'sortorder' => $current->sortorder]
            );
            return true;
        } else {
            // Can't move (e.g. already at top or bottom).
            return false;
        }
    }

    /**
     * Resequences the sortorder of an assignment's criteria to be contiguous from 1.
     *
     * @param int $assignment
     */
    public function resequence_sort_order($assignment) {
        // Get items ordered by current sortorder.
        $items = $this->mysqlconn->get_records(
            'assignfeedback_recitannotation_crit',
            ['assignment' => $assignment],
            'sortorder ASC'
        );

        $i = 1;
        foreach ($items as $item) {
            if ($item->sortorder != $i) {
                $item->sortorder = $i;
                $this->mysqlconn->update_record('assignfeedback_recitannotation_crit', $item);
            }
            $i++;
        }
    }

    /**
     * Returns the AI prompt configured for an assignment.
     *
     * @param int $assignment
     * @return TablePromptAi
     */
    public function get_prompt_ai($assignment) {
        $query = 'SELECT * FROM {assignfeedback_recitannotation_promptai} WHERE assignment = ?';

        $result = $this->get_records_sql($query, [$assignment], true);

        return (count($result) > 0 ? array_shift($result) : new TablePromptAi());
    }

    /**
     * Saves (inserts or updates) the AI prompt for an assignment.
     *
     * @param \stdClass $data
     * @return bool
     */
    public function save_prompt_ai($data) {
        try {
            $record = $this->get_prompt_ai($data->assignment);

            if ($record->id == 0) {
                $record->assignment = $data->assignment;
                $record->prompt_ai = $data->prompt_ai;
                $this->mysqlconn->insert_record("assignfeedback_recitannotation_promptai", $record);
            } else {
                $record->prompt_ai = $data->prompt_ai;
                $this->mysqlconn->update_record("assignfeedback_recitannotation_promptai", $record);
            }

            return true;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }
}
