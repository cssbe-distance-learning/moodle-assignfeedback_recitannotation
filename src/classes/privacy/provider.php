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
 * Privacy class for requesting user data.
 *
 * @package assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignfeedback_recitannotation\privacy;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;
use mod_assign\privacy\assign_plugin_request_data;
use mod_assign\privacy\useridlist;

/**
 * Privacy class for requesting user data.
 *
 * @package assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \mod_assign\privacy\assignfeedback_provider,
    \mod_assign\privacy\assignfeedback_user_provider {
    /**
     * Return meta data about this plugin.
     *
     * @param  collection $collection A list of information to add to.
     * @return collection Return the collection after adding to it.
     */
    public static function get_metadata(collection $collection): collection {
        // The annotation table stores annotated HTML feedback linked to a student's submission.
        // ownerid records the teacher who wrote the annotation.
        $collection->add_database_table(
            'assignfeedback_recitannotation',
            [
                'submission' => 'privacy:metadata:assignfeedback_recitannotation:submission',
                'ownerid'    => 'privacy:metadata:assignfeedback_recitannotation:ownerid',
                'annotation' => 'privacy:metadata:assignfeedback_recitannotation:annotation',
                'lastupdate' => 'privacy:metadata:assignfeedback_recitannotation:lastupdate',
            ],
            'privacy:metadata:assignfeedback_recitannotation'
        );

        return $collection;
    }

    /**
     * No need to fill in this method as all information can be acquired from the assign_grades table in the mod assign
     * provider.
     *
     * @param  int $userid The user ID.
     * @param  contextlist $contextlist The context list.
     */
    public static function get_context_for_userid_within_feedback(int $userid, contextlist $contextlist) {
        // This uses the assign_grade table.
    }

    /**
     * This also does not need to be filled in as this is already collected in the mod assign provider.
     *
     * @param  useridlist $useridlist A list of user IDs
     */
    public static function get_student_user_ids(useridlist $useridlist) {
        // Not required.
    }

    /**
     * If you have tables that contain userids and you can generate entries in your tables without creating an
     * entry in the assign_grades table then please fill in this method.
     *
     * @param  \core_privacy\local\request\userlist $userlist The userlist object
     */
    public static function get_userids_from_context(\core_privacy\local\request\userlist $userlist) {
        // Not required.
    }

    /**
     * Export all user data for this plugin.
     * Exports the annotation written about the user's submission.
     *
     * @param  assign_plugin_request_data $exportdata Data used to determine which context and user to export.
     */
    public static function export_feedback_user_data(assign_plugin_request_data $exportdata) {
        global $DB;

        $userid  = $exportdata->get_user()->id;
        $assign  = $exportdata->get_assign();
        $assignid = $assign->get_instance()->id;

        // Find annotations for all submissions of this user in this assignment.
        $sql = "SELECT t1.id, t1.annotation, t1.occurrences, t1.lastupdate, t1.ownerid
                  FROM {assignfeedback_recitannotation} t1
                  INNER JOIN {assign_submission} t2 ON t1.submission = t2.id
                  WHERE t2.assignment = :assignment AND t2.userid = :userid";

        $records = $DB->get_records_sql($sql, ['assignment' => $assignid, 'userid' => $userid]);

        if (empty($records)) {
            return;
        }

        $context = $exportdata->get_context();
        $subcontext = [get_string('pluginname', 'assignfeedback_recitannotation')];

        foreach ($records as $record) {
            $data = (object)[
                'annotation'  => $record->annotation,
                'occurrences' => $record->occurrences,
                'lastupdate'  => \core_privacy\local\request\transform::datetime($record->lastupdate),
            ];
            writer::with_context($context)->export_data($subcontext, $data);
        }
    }

    /**
     * Delete all feedback annotations for the given context (entire assignment).
     *
     * @param  assign_plugin_request_data $requestdata Data useful for deleting user data from this sub-plugin.
     */
    public static function delete_feedback_for_context(assign_plugin_request_data $requestdata) {
        global $DB;

        $assign   = $requestdata->get_assign();
        $assignid = $assign->get_instance()->id;

        $sql = "SELECT t1.id
                  FROM {assignfeedback_recitannotation} t1
                  INNER JOIN {assign_submission} t2 ON t1.submission = t2.id
                  WHERE t2.assignment = :assignment";

        $ids = $DB->get_fieldset_sql($sql, ['assignment' => $assignid]);

        if (!empty($ids)) {
            [$insql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('assignfeedback_recitannotation', "id $insql", $params);
        }
    }

    /**
     * Delete the annotation for a single grade (one student's submission attempt).
     *
     * @param  assign_plugin_request_data $requestdata Data useful for deleting user data.
     */
    public static function delete_feedback_for_grade(assign_plugin_request_data $requestdata) {
        global $DB;

        $userid   = $requestdata->get_user()->id;
        $assign   = $requestdata->get_assign();
        $assignid = $assign->get_instance()->id;

        $sql = "SELECT t1.id
                  FROM {assignfeedback_recitannotation} t1
                  INNER JOIN {assign_submission} t2 ON t1.submission = t2.id
                  WHERE t2.assignment = :assignment AND t2.userid = :userid";

        $ids = $DB->get_fieldset_sql($sql, ['assignment' => $assignid, 'userid' => $userid]);

        if (!empty($ids)) {
            [$insql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('assignfeedback_recitannotation', "id $insql", $params);
        }
    }

    /**
     * Delete annotations for multiple grade ids / userids in a context.
     *
     * @param  assign_plugin_request_data $deletedata A class that contains the relevant information required for deletion.
     */
    public static function delete_feedback_for_grades(assign_plugin_request_data $deletedata) {
        global $DB;

        $userids  = $deletedata->get_userids();
        $assign   = $deletedata->get_assign();
        $assignid = $assign->get_instance()->id;

        if (empty($userids)) {
            return;
        }

        [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');

        $sql = "SELECT t1.id
                  FROM {assignfeedback_recitannotation} t1
                  INNER JOIN {assign_submission} t2 ON t1.submission = t2.id
                  WHERE t2.assignment = :assignment AND t2.userid $usersql";

        $params = array_merge(['assignment' => $assignid], $userparams);
        $ids = $DB->get_fieldset_sql($sql, $params);

        if (!empty($ids)) {
            [$insql, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('assignfeedback_recitannotation', "id $insql", $inparams);
        }
    }
}
