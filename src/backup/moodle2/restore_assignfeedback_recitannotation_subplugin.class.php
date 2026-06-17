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
 * Restore subplugin class for assignfeedback_recitannotation.
 *
 * @package assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restore subplugin class.
 *
 * Provides the necessary information needed
 * to restore one assign_feedback subplugin.
 *
 * @package assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_assignfeedback_recitannotation_subplugin extends restore_subplugin {
    /**
     * Returns the paths to be handled by the subplugin at workshop level.
     * @return array
     */
    protected function define_grade_subplugin_structure() {
        $paths = [];
        // Checking userinfo is unnecessary: the assignment user data is checked before reaching this point.

        $elementname = $this->get_namefor('annotation');
        $elementpath = $this->get_pathfor('/feedback_recitannot_annot');
        $paths[] = new restore_path_element($elementname, $elementpath);

        $elementname = $this->get_namefor('criterion');
        $elementpath = $this->get_pathfor('/feedback_recitannot_crits/recitannot_crit');
        $paths[] = new restore_path_element($elementname, $elementpath);

        $elementname = $this->get_namefor('comment');
        $elementpath = $this->get_pathfor(
            '/feedback_recitannot_crits/recitannot_crit/feedback_recitannot_comments/recitannot_comment'
        );
        $paths[] = new restore_path_element($elementname, $elementpath);

        $elementname = $this->get_namefor('promptai');
        $elementpath = $this->get_pathfor('/feedback_recitannot_promptsai/recitannot_promptai');
        $paths[] = new restore_path_element($elementname, $elementpath);

        return $paths;
    }

    /**
     * Restores a single annotation record.
     *
     * @param mixed $data
     */
    public function process_assignfeedback_recitannotation_annotation($data) {
        global $DB;

        try {
            $data = (object)$data;
            unset($data->id);

            if ($data->ownerid > 0) {
                $data->ownerid = $this->get_mappingid('user', $data->ownerid);
            }

            if ($data->userid > 0) {
                $data->userid = $this->get_mappingid('user', $data->userid);
            }

            $assignment = $this->get_new_parentid('assign');

            $sql = "assignment = :assignment AND userid = :userid";
            $params = ['assignment' => $assignment, 'userid' => $data->userid];
            // Get the first (latest) to avoid error "Multiple records were found, whereas only one record was expected.".
            $submission = $DB->get_records_select('assign_submission', $sql, $params, 'attemptnumber DESC', '*', 0, 1);
            $submission = reset($submission);
            $data->submission = $submission->id;

            $exists = $DB->record_exists('assignfeedback_recitannotation', [
                'submission' => $data->submission,
            ]);
            if (!$exists) {
                $DB->insert_record('assignfeedback_recitannotation', $data);
            }
        } catch (Exception $ex) {
            debugging('Error on assignfeedback_recitannotation: ' . $ex->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Restores a single criterion record.
     *
     * @param mixed $data
     */
    public function process_assignfeedback_recitannotation_criterion($data) {
        global $DB;

        try {
            $data = (object)$data;
            $oldid = $data->id;
            unset($data->id);

            $data->assignment = $this->get_new_parentid('assign');

            $exists = $DB->record_exists('assignfeedback_recitannotation_crit', [
                'assignment' => $data->assignment,
                'name' => $data->name,
            ]);
            if (!$exists) {
                $newitemid = $DB->insert_record('assignfeedback_recitannotation_crit', $data);

                // Save mapping for child table.
                $this->set_mapping('criterion', $oldid, $newitemid);
            }
        } catch (Exception $ex) {
            debugging('Error on assignfeedback_recitannotation_crit: ' . $ex->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Restores a single comment record.
     *
     * @param mixed $data
     */
    public function process_assignfeedback_recitannotation_comment($data) {
        global $DB;

        try {
            $data = (object)$data;
            unset($data->id);

            // Map foreign key.
            $data->criterionid = $this->get_mappingid('criterion', $data->criterionid);

            if ($data->criterionid) {
                $exists = $DB->record_exists('assignfeedback_recitannotation_comment', [
                    'criterionid' => $data->criterionid,
                    'comment' => $data->comment,
                ]);
                if (!$exists) {
                    $DB->insert_record('assignfeedback_recitannotation_comment', $data);
                }
            } else {
                throw new Exception(get_string('err_criterionid_mapping', 'assignfeedback_recitannotation'));
            }
        } catch (Exception $ex) {
            debugging('Error on assignfeedback_recitannotation_comment: ' . $ex->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Restores a single AI prompt record.
     *
     * @param mixed $data
     */
    public function process_assignfeedback_recitannotation_promptai($data) {
        global $DB;

        try {
            $data = (object)$data;
            $oldid = $data->id;
            unset($data->id);

            $data->assignment = $this->get_new_parentid('assign');

            $exists = $DB->record_exists('assignfeedback_recitannotation_promptai', [
                'assignment' => $data->assignment,
            ]);
            if (!$exists) {
                $newitemid = $DB->insert_record('assignfeedback_recitannotation_promptai', $data);

                // Save mapping for child table.
                $this->set_mapping('promptai', $oldid, $newitemid);
            }
        } catch (Exception $ex) {
            debugging('Error on process_assignfeedback_recitannotation_promptai: ' . $ex->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
