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
 * Backup subplugin class for assignfeedback_recitannotation.
 *
 * @package assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Provides the information to backup the recitannotation assign feedback subplugin.
 *
 * @package assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_assignfeedback_recitannotation_subplugin extends backup_subplugin {
    /**
     * Returns the subplugin information to attach to submission element.
     * @return backup_subplugin_element
     */
    protected function define_grade_subplugin_structure() {
        try {
            // This wrapper is required around your plugin's elements.
            $subplugin = $this->get_subplugin_element();
            $wrapper = new backup_nested_element($this->get_recommended_name());

            // Define your main and child elements.
            $annotation = new backup_nested_element('feedback_recitannot_annot', ['id'], [
                'userid', 'ownerid', 'annotation', 'occurrences', 'lastupdate',
            ]);

            $crits = new backup_nested_element('feedback_recitannot_crits');
            $crit = new backup_nested_element('recitannot_crit', ['id'], [
                'assignment', 'name', 'description', 'backgroundcolor', 'sortorder', 'instruction_ai',
            ]);

            $comments = new backup_nested_element('feedback_recitannot_comments');
            $comment = new backup_nested_element('recitannot_comment', ['id'], [
                'criterionid', 'comment',
            ]);

            $promptsai = new backup_nested_element('feedback_recitannot_promptsai');
            $promptai = new backup_nested_element('recitannot_promptai', ['id'], [
                'assignment', 'prompt_ai',
            ]);

            // Build the XML structure.
            $subplugin->add_child($wrapper);
            $wrapper->add_child($annotation);
            $wrapper->add_child($crits);
            $crits->add_child($crit);
            $crit->add_child($comments);
            $comments->add_child($comment);
            $wrapper->add_child($promptsai);
            $promptsai->add_child($promptai);

            // Set data sources.
            $annotationsql = 'SELECT t1.id, t1.ownerid, t1.annotation, t1.occurrences, t1.lastupdate, t2.userid' .
                ' FROM {assignfeedback_recitannotation} t1' .
                ' INNER JOIN {assign_submission} t2 ON t1.submission = t2.id' .
                ' WHERE t2.assignment = (SELECT assignment FROM {assign_grades} WHERE id = :gradeid AND userid = t2.userid)';
            $annotation->set_source_sql($annotationsql, ['gradeid' => backup::VAR_PARENTID]);

            $critsql = 'SELECT t1.id, t1.assignment, t1.name, t1.description, t1.backgroundcolor, t1.sortorder,' .
                ' t1.instruction_ai' .
                ' FROM {assignfeedback_recitannotation_crit} t1' .
                ' WHERE t1.assignment = (SELECT assignment FROM {assign_grades} WHERE id = :gradeid)';
            $crit->set_source_sql($critsql, ['gradeid' => backup::VAR_PARENTID]);

            $comment->set_source_table('assignfeedback_recitannotation_comment', [
                'criterionid' => backup::VAR_PARENTID,
            ]);

            $promptaisql = 'SELECT t1.id, t1.assignment, t1.prompt_ai' .
                ' FROM {assignfeedback_recitannotation_promptai} t1' .
                ' WHERE t1.assignment = (SELECT assignment FROM {assign_grades} WHERE id = :gradeid)';
            $promptai->set_source_sql($promptaisql, ['gradeid' => backup::VAR_PARENTID]);

            return $subplugin;
        } catch (Exception $ex) {
            debugging("Exception on define_grade_subplugin_structure: " . $ex->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
