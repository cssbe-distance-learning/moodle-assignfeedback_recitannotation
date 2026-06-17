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
 * Library of functions for the recitannotation assign feedback plugin.
 *
 * @package assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once(dirname(__FILE__) . '/classes/Options.php');
require_once(dirname(__FILE__) . '/classes/PersistCtrl.php');

/**
 * Text annotation assign feedback plugin.
 *
 * @package assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_feedback_recitannotation extends assign_feedback_plugin {
    /**
     * Get the name of the file feedback plugin.
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname2', 'assignfeedback_recitannotation');
    }

    /**
     * Get form elements for the grading page
     *
     * @param stdClass|null $grade
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool true if elements were added to the form
     */
    public function get_form_elements_for_user($grade, MoodleQuickForm $mform, stdClass $data, $userid) {
        global $PAGE, $DB, $USER, $CFG;

        $persistctrl = \recitannotation\PersistCtrl::get_instance($DB, $USER);

        $data = $persistctrl->get_annotation($grade->assignment, $userid, $grade->attemptnumber);

        $containerhtml = "<div id='assignfeedback_recitannotation' style='position: sticky; top: 0;' class='bg-white rounded'></div>";
        $group[] = $mform->createElement('static', '', '', $containerhtml);

        $html = html_writer::script('', "{$CFG->wwwroot}/mod/assign/feedback/recitannotation/react/build/index.js");
        $html .= "<link href='{$CFG->wwwroot}/mod/assign/feedback/recitannotation/react/build/index.css' rel='stylesheet'></link>";

        $strings = [
            'pluginname' => '', 'msg_action_completed' => '', 'msg_confirm_deletion' => '', 'student_production' => '',
            'undo' => '', 'redo' => '', 'clean_student_production' => '', 'annotate' => '', 'ask_ai' => '',
            'occurrences' => '', 'criterion' => '', 'count' => '', 'msg_confirm_clean_html_code' => '', 'select_item' => '',
            'comment' => '', 'search_comment' => '', 'add_edit_comment' => '', 'delete' => '', 'cancel' => '',
            'save' => '', 'msg_required_field' => '', 'msg_error_highlighting' => '', 'ask_question' => '', 'ask' => '',
            'back_annotation_view' => '', 'criteria_list' => '', 'comment_list' => '', 'add_new_item' => '',
            'import_criteria' => '', 'export_criteria' => '', 'name' => '', 'description' => '', 'color' => '', 'edit' => '',
            'move_up' => '', 'move_down' => '', 'only_lowercase' => '', 'add_edit_criterion' => '', 'ok' => '',
            'delete_criterion' => '', 'quick_annotation_method' => '', 'add_edit_annotation' => '',
            'print_comment_list' => '', 'prompt' => '', 'input' => '', 'output' => '', 'result' => '', 'apply' => '',
            'prompt_ai' => '', 'add_edit_prompt_ai' => '', 'prompt_ai_help' => '', 'instruction_ai' => '',
            'select_criteria' => '', 'review_prompt' => '', 'generate_prompt' => '', 'msg_ai_student_text_prefix' => '',
            'documentation_download' => '', 'delete_all' => '', 'reset_annotation' => '', 'click_to_filter' => '',
            'msg_confirm_ai_correction' => '', 'msg_confirm_reset_annotation' => '',
            'err_no_ai_response' => '', 'err_ai_refused' => '', 'err_ai_response_too_long' => '',
            'err_ai_json_format' => '', 'err_criterion_not_found' => '',
            'student_work_placeholder' => '', 'default_prompt_ai' => '',
            'analysis_in_progress' => '', 'time_elapsed' => '', 'cancel_request' => '',
            'schema_annotated_text_desc' => '', 'schema_general_feedback_desc' => '',
            'schema_explanation_desc' => '', 'schema_strategy_desc' => '', 'schema_criterion_desc' => '',
        ];

        foreach ($strings as $key => $value) {
            $strings[$key] = get_string($key, 'assignfeedback_recitannotation');
        }

        $jsondata = [
            'assignment' => $grade->assignment,
            'submission' => $data->submission,
            'attemptnumber' => $grade->attemptnumber,
            'userid' => $userid,
            'aiApi' => \recitannotation\Options::is_ai_api_active(),
            'aiModel' => \recitannotation\Options::get_ai_model(),
            'documentationUrl' => \recitannotation\Options::get_url_documentation(),
        ];

        $script = 'if (window.loadRecitAnnotationReactApp) {' .
            'window.loadRecitAnnotationReactApp(' . json_encode($jsondata) . ', ' . json_encode($strings) . ');' .
            '}';

        // If it is in debug mode, then avoid require() to load app React in dev mode.
        if ($CFG->debug < DEBUG_DEVELOPER) {
            $script = "require(['recitannotation'], function () { $script });";
        }

        $html .= html_writer::script($script);

        $group[] = $mform->createElement('static', '', '', $html);

        $mform->addGroup(
            $group,
            'assignfeedbackrecitannotation_group',
            $this->get_name(),
            '',
            false,
            ['class' => 'has-popout invisible']
        );

        return true;
    }

    /**
     * Display the comment in the feedback table.
     *
     * @param stdClass $grade
     * @param bool $showviewlink Set to true to show a link to view the full feedback
     * @return string
     */
    public function view_summary(stdClass $grade, &$showviewlink) {
        global $DB, $USER;

        $showviewlink = false;

        $persistctrl = \recitannotation\PersistCtrl::get_instance($DB, $USER);
        $data = $persistctrl->get_annotation($grade->assignment, $grade->userid, $grade->attemptnumber);
        $criterialist = $persistctrl->get_criteria_list($grade->assignment);

        $html = "<div id='assignfeedback_recitannotation' class='bg-white p-2'>";

        $html .= "<div class='mb-3 p-2'>$data->annotation</div>";

        $data->occurrences = json_decode($data->occurrences);
        $html .= "<div class='d-flex flex-wrap'>";

        foreach ($criterialist as $criterion) {
            $attr = $criterion->name;
            if (!isset($data->occurrences->$attr)) {
                continue;
            }

            $bgcolor = s($criterion->backgroundcolor);
            $html .= "<span class='badge-criterion' style='background-color: {$bgcolor}; border-color: {$bgcolor};'>";
            $html .= "<span class='badge-criterion-name'>" . s($criterion->description) . "</span>";
            $html .= "<span class='badge-criterion-counter'>" . s((string)$data->occurrences->$attr) . "</span>";
            $html .= "</span>";
        }

        $html .= "</div>";

        $html .= "</div>";

        return $html;
    }

    /**
     * Display the comment in the feedback table.
     *
     * @param stdClass $grade
     * @return string
     */
    public function view(stdClass $grade) {
        return "";
    }

    /**
     * Saving the comment content into database.
     *
     * @param stdClass $grade
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $grade, stdClass $data) {
        return true;
    }

    /**
     * Has the comment feedback been modified?
     *
     * @param stdClass $grade The grade object.
     * @param stdClass $data Data from the form submission.
     * @return boolean True if the comment feedback has been modified, else false.
     */
    public function is_feedback_modified(stdClass $grade, stdClass $data) {
        return true;
    }

    /**
     * Returns true if there are no feedback comments for the given grade.
     *
     * @param stdClass $grade
     * @return bool
     */
    public function is_empty(stdClass $grade) {
        global $DB, $USER;

        $persistctrl = \recitannotation\PersistCtrl::get_instance($DB, $USER);
        $data = $persistctrl->get_annotation($grade->assignment, $grade->userid, $grade->attemptnumber);

        return ($data->id == 0);
    }

    /**
     * The assignment has been deleted - cleanup.
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB, $USER;

        $persistctrl = \recitannotation\PersistCtrl::get_instance($DB, $USER);
        return $persistctrl->delete_plugin_data($this->assignment->get_instance()->id);
    }

    /**
     * Called by the assignment module when someone chooses something from the
     * grading navigation or batch operations list.
     *
     * @param string $action - The page to view
     * @return string - The html response
     */
    public function view_page($action) {
        return 'view_page';
    }

    /**
     * Return a list of the grading actions performed by this plugin.
     * This plugin supports upload zip.
     *
     * @return array The list of grading actions
     */
    public function get_grading_actions() {
        return ['my_grading_action' => 'my_grading_action'];
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of settings
     * @since Moodle 3.2
     */
    public function get_config_for_external() {
        return (array) $this->get_config();
    }
}
