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
 * Tests for the privacy provider.
 *
 * @package   assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignfeedback_recitannotation;

use assignfeedback_recitannotation\privacy\provider;
use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\types\database_table;

/**
 * Tests for the Privacy API provider.
 *
 * @covers \assignfeedback_recitannotation\privacy\provider
 */
class privacy_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    // Metadata.

    public function test_get_metadata_returns_collection(): void {
        $collection = new collection('assignfeedback_recitannotation');
        $result     = provider::get_metadata($collection);
        $this->assertInstanceOf(collection::class, $result);
    }

    public function test_get_metadata_declares_annotation_table(): void {
        $collection = new collection('assignfeedback_recitannotation');
        $result     = provider::get_metadata($collection);

        $items = $result->get_collection();
        $this->assertNotEmpty($items);
        $this->assertContainsOnlyInstancesOf(database_table::class, $items);
    }

    public function test_get_metadata_includes_required_fields(): void {
        $collection = new collection('assignfeedback_recitannotation');
        $result     = provider::get_metadata($collection);

        // Moodle 4.x keys items by table name; older versions use numeric keys.
        $items  = $result->get_collection();
        /** @var database_table $table */
        $table  = reset($items);
        $fields = $table->get_privacy_fields();

        $this->assertArrayHasKey('submission', $fields);
        $this->assertArrayHasKey('ownerid', $fields);
        $this->assertArrayHasKey('annotation', $fields);
        $this->assertArrayHasKey('lastupdate', $fields);
    }

    // Data deletion at context level.

    public function test_delete_feedback_for_context_removes_annotations(): void {
        global $DB;

        [$course, $assign, $teacher, $student, $submissionid] = $this->create_submission_fixture();

        // Insert a raw annotation record directly to bypass the full React/AJAX flow.
        $DB->insert_record('assignfeedback_recitannotation', (object)[
            'submission' => $submissionid,
            'ownerid'    => $teacher->id,
            'annotation' => '<p>Test annotation</p>',
            'occurrences' => '{}',
            'lastupdate' => time(),
        ]);

        $this->assertSame(1, $DB->count_records('assignfeedback_recitannotation', ['submission' => $submissionid]));

        $requestdata = $this->make_request_data($assign, $student);
        provider::delete_feedback_for_context($requestdata);

        $this->assertSame(0, $DB->count_records('assignfeedback_recitannotation', ['submission' => $submissionid]));
    }

    public function test_delete_feedback_for_grade_removes_only_that_user(): void {
        global $DB;

        [$course, $assign, $teacher, $student1, $subid1] = $this->create_submission_fixture();

        // Create a second student with their own submission.
        $student2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, 'student');
        $subid2 = $this->create_raw_submission($assign->id, $student2->id);

        // Insert annotations for both students.
        foreach ([$subid1 => $student1, $subid2 => $student2] as $subid => $user) {
            $DB->insert_record('assignfeedback_recitannotation', (object)[
                'submission' => $subid,
                'ownerid'    => $teacher->id,
                'annotation' => '<p>Annotation</p>',
                'occurrences' => '{}',
                'lastupdate' => time(),
            ]);
        }

        $this->assertSame(2, $DB->count_records('assignfeedback_recitannotation'));

        $requestdata = $this->make_request_data($assign, $student1);
        provider::delete_feedback_for_grade($requestdata);

        // Only student1's annotation is gone; student2's remains.
        $this->assertSame(0, $DB->count_records('assignfeedback_recitannotation', ['submission' => $subid1]));
        $this->assertSame(1, $DB->count_records('assignfeedback_recitannotation', ['submission' => $subid2]));
    }

    // Helpers.

    /**
     * Creates a minimal course + assign + teacher + student + submission.
     * Returns [$course, $assigninstance, $teacher, $student, $submissionid].
     */
    private function create_submission_fixture(): array {
        $gen      = $this->getDataGenerator();
        $course   = $gen->create_course();
        $teacher  = $gen->create_user();
        $student  = $gen->create_user();

        $gen->enrol_user($teacher->id, $course->id, 'editingteacher');
        $gen->enrol_user($student->id, $course->id, 'student');

        $assigngen = $gen->get_plugin_generator('mod_assign');
        $assign    = $assigngen->create_instance(['course' => $course->id]);

        $subid = $this->create_raw_submission($assign->id, $student->id);

        return [$course, $assign, $teacher, $student, $subid];
    }

    /**
     * Inserts a minimal assign_submission row and returns its id.
     */
    private function create_raw_submission(int $assignid, int $userid): int {
        global $DB;
        return (int) $DB->insert_record('assign_submission', (object)[
            'assignment'    => $assignid,
            'userid'        => $userid,
            'timecreated'   => time(),
            'timemodified'  => time(),
            'status'        => 'submitted',
            'groupid'       => 0,
            'attemptnumber' => 0,
            'latest'        => 1,
        ]);
    }

    /**
     * Builds an assign_plugin_request_data mock for the given assign + user.
     */
    private function make_request_data(\stdClass $assigninstance, \stdClass $user): \mod_assign\privacy\assign_plugin_request_data {
        global $CFG;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $cm     = get_coursemodule_from_instance('assign', $assigninstance->id);
        $context = \context_module::instance($cm->id);
        $assign  = new \assign($context, $cm, null);

        return new \mod_assign\privacy\assign_plugin_request_data($context, $assign, null, [], $user);
    }
}
