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
 * @package assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['access_denied'] = 'Access Denied: You don't currently have permission to use this feature.';
$string['add_edit_annotation'] = 'Add/Modify an annotation';
$string['add_edit_comment'] = 'Add/Modify a Comment';
$string['add_edit_criterion'] = 'Add/Edit Criteria';
$string['add_edit_prompt_ai'] = 'Add/Edit AI Prompt';
$string['add_new_item'] = 'Add new item';
$string['ai_api_endpoint'] = 'AI API Endpoint';
$string['ai_api_endpoint_desc'] = 'Full URL of the AI API to which Moodle should send requests.';
$string['ai_api_key'] = 'AI API Key';
$string['ai_api_key_desc'] = 'Secret authentication key provided by the AI service. It allows Moodle to securely access the API.';
$string['ai_model'] = 'AI Model';
$string['ai_model_desc'] = 'The AI engine used to analyze the text and generate corrections.';
$string['analysis_in_progress'] = 'Analysis in progress';
$string['annotate'] = 'Annotate';
$string['apply'] = "Apply";
$string['ask'] = 'Ask';
$string['ask_ai'] = 'Ask AI';
$string['ask_question'] = 'Ask a question';
$string['back_annotation_view'] = 'Back to the annotation view';
$string['cancel'] = 'Cancel';
$string['cancel_request'] = 'Cancel the request';
$string['clean_student_production'] = 'Clean Student Text';
$string['click_to_filter'] = 'Click to filter';
$string['color'] = 'Color';
$string['comment'] = 'Comment';
$string['comment_list'] = 'Comment list';
$string['count'] = 'Number';
$string['criteria_list'] = 'Criteria list';
$string['criterion'] = 'Criterion';
$string['default'] = 'Enabled by default';
$string['default_help'] = 'If set, this feedback method will be enabled by default for all new assignments.';
$string['default_prompt_ai'] = "You are a strict JSON extractor. Analyse the text and generate a JSON respecting EXACTLY these keys:\n" .
    "1. \"annotatedText\": text with tags [[e1:word]].\n" .
    "2. \"generalFeedback\": encouraging message.\n" .
    "3. \"corrections\": array of objects with EXACTLY these keys:\n" .
    "   - \"id\": (e.g. \"e1\")\n" .
    "   - \"suggestion\": (the corrected word)\n" .
    "   - \"explanation\": (why it is an error)\n" .
    "   - \"strategy\": (tip for the student)\n" .
    "   - \"criterion\": (the criterion ID)\n" .
    "4. First identify errors in the text and mark them with [[eX:word]].\n" .
    "5. Each [[eX:word]] tag in the \"annotatedText\" field must have a corresponding UNIQUE entry in the \"corrections\" array.\n" .
    "6. The \"eX\" ID in the array must exactly match the ID visible in the text.\n\n" .
    "NOTE: Never use the ID (e1, e2) as a key name. Use \"id\": \"e1\".\n\n" .
    "INSTRUCTION: Return ONLY the raw JSON object. No text before, no text after, no ```json tags.\n\n" .
    "TONE: The 'generalFeedback' field must be benevolent and encouraging.\n\n" .
    "CRITERIA TO USE (ID):\n" .
    "<<<\n" .
    "PLACEHOLDER_CRITERIA_LIST\n" .
    ">>>";
$string['delete'] = 'Delete';
$string['delete_all'] = 'Delete all';
$string['delete_criterion'] = 'If there are comments linked to this criteria/criterion, they'll be deleted too.';
$string['description'] = 'Description';
$string['documentation_download'] = 'Documentation and criteria download';
$string['edit'] = 'Edit';
$string['enabled'] = 'File feedback';
$string['enabled_help'] = 'If enabled, the teacher will be able to annotate on students assignment submissions.';
$string['err_ai_json_format'] = 'Formatting error: the AI did not return valid JSON.';
$string['err_ai_refused'] = 'The AI refused to process this request for safety or policy reasons.';
$string['err_ai_response_too_long'] = 'The response is too long and was cut off. Try reducing the text to correct.';
$string['err_criterion_not_found'] = 'The criterion "%s" was not found.';
$string['err_criterionid_mapping'] = 'Could not find mapping for criterion during restore.';
$string['err_no_ai_response'] = 'No response from AI.';
$string['err_xml_parse'] = 'Error reading XML file: {$a}';
$string['export_criteria'] = 'Export criteria';
$string['foreign_key'] = 'A foreign key constraint prevents deletion to ensure data integrity. Please delete or modify the associated items first.';
$string['generate_prompt'] = 'Generate prompt';
$string['import_criteria'] = 'Import criteria';
$string['input'] = "Input";
$string['instruction_ai'] = 'AI Instructions';
$string['move_down'] = 'Move down';
$string['move_up'] = 'Move up';
$string['msg_action_completed'] = "The action was successfully completed.";
$string['msg_ai_student_text_prefix'] = "Analyse this student text and generate the JSON according to the schema:\nText:\n";
$string['msg_confirm_ai_correction'] = '<p>This action will ask the AI to correct the text, and all your current annotations will be lost.</p><p><strong>Do you wish to continue?</strong></p>';
$string['msg_confirm_clean_html_code'] = "Are you sure you want to clean the HTML code?<br/><br/>This action will also remove any annotations you've added.";
$string['msg_confirm_deletion'] = 'Do you confirm the deletion? This operation cannot be undone.';
$string['msg_confirm_reset_annotation'] = '<p><strong>Do you really want to reset the annotation?</strong></p><p>This action will remove all annotations you have added.</p>';
$string['msg_error_highlighting'] = 'Error applying highlighting: There are partially selected nodes.';
$string['msg_required_field'] = "Please fill '%s' field before continuing.";
$string['name'] = 'Name';
$string['no_data'] = 'This page currently has no data to display.';
$string['occurrences'] = 'Occurrences';
$string['ok'] = 'OK';
$string['only_lowercase'] = 'Please enter only lowercase letters without spaces.';
$string['output'] = "Output";
$string['pluginname'] = 'RÉCIT Annotation feedback';
$string['pluginname2'] = 'Text Annotation';
$string['print_comment_list'] = 'Print the list of comments';
$string['printed_on'] = "Printed on";
$string['privacy:metadata:assignfeedback_recitannotation'] = 'Annotated feedback written by a teacher on a student\'s submission.';
$string['privacy:metadata:assignfeedback_recitannotation:annotation'] = 'The annotated HTML content of the student\'s submission.';
$string['privacy:metadata:assignfeedback_recitannotation:lastupdate'] = 'The timestamp of the last modification to the annotation.';
$string['privacy:metadata:assignfeedback_recitannotation:ownerid'] = 'The ID of the teacher who wrote the annotation.';
$string['privacy:metadata:assignfeedback_recitannotation:submission'] = 'The ID of the submission this annotation belongs to.';
$string['prompt'] = "Prompt";
$string['prompt_ai'] = 'AI Prompt';
$string['prompt_ai_help'] = 'Use the following variables for automatic substitution when building the prompt: PLACEHOLDER_CRITERIA_LIST';
$string['quick_annotation_method'] = 'Quick Method';
$string['redo'] = 'Redo';
$string['reset_annotation'] = 'Reset annotation';
$string['result'] = "Result";
$string['review_prompt'] = 'Review prompt';
$string['save'] = 'Save';
$string['schema_annotated_text_desc'] = "The full text where each error is surrounded as: [[id:misspelled_word]]. It is crucial to leave the student's mistake between the brackets. Example: 'He [[e1:eated]]' (not 'ate'). It is also crucial to return the student's text with the original HTML tags.";
$string['schema_criterion_desc'] = 'The criterion identifier. The list of criteria will be passed in the prompt. Each criterion will have in its description (ID=) which will be the identifier to add in this field.';
$string['schema_explanation_desc'] = 'Short explanation';
$string['schema_general_feedback_desc'] = 'An overall encouraging piece of advice';
$string['schema_strategy_desc'] = 'Tip to remember';
$string['search_comment'] = 'Search for a comment';
$string['select_criteria'] = 'Select your criteria';
$string['select_item'] = 'Select an item';
$string['student_production'] = 'Student Production';
$string['student_work_placeholder'] = 'The student\'s submitted work will be displayed here.';
$string['time_elapsed'] = 'Time elapsed';
$string['undo'] = 'Undo';
$string['url_documentation'] = 'Documentation URL';
$string['url_documentation_desc'] = 'This link gives users easy access to additional information or instructions.';
