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
 * Tests for PersistCtrl criteria and comments CRUD.
 *
 * @package   assignfeedback_recitannotation
 * @copyright 2025 RECIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignfeedback_recitannotation;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/feedback/recitannotation/locallib.php');

/**
 * Tests for PersistCtrl criteria and comments CRUD.
 *
 * @covers \recitannotation\PersistCtrl
 */
final class persistctrl_test extends \advanced_testcase {
    /** @var \stdClass */
    private $assign;

    /** @var \recitannotation\PersistCtrl */
    private $ctrl;

    protected function setUp(): void {
        global $DB, $USER;
        parent::setUp();
        $this->resetAfterTest(true);

        // PersistCtrl is a singleton; reset it so each test gets a fresh instance
        // bound to the current test's $DB and $USER.
        $ref = new \ReflectionProperty(\recitannotation\PersistCtrl::class, 'instance');
        $ref->setAccessible(true);
        $ref->setValue(null, null);

        $generator = $this->getDataGenerator();
        $course   = $generator->create_course();
        $teacher  = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->setUser($teacher);

        $assigngen    = $generator->get_plugin_generator('mod_assign');
        $this->assign = $assigngen->create_instance(['course' => $course->id]);

        $this->ctrl = \recitannotation\PersistCtrl::get_instance($DB, $USER);
    }

    // Criteria.

    public function test_save_criterion_returns_positive_id(): void {
        $saved = $this->ctrl->save_criterion($this->make_criterion('clarity', 1));
        $this->assertGreaterThan(0, $saved->id);
    }

    public function test_get_criteria_list_returns_saved_items(): void {
        $this->ctrl->save_criterion($this->make_criterion('clarity', 1));
        $this->ctrl->save_criterion($this->make_criterion('grammar', 2));

        $list = $this->ctrl->get_criteria_list($this->assign->id);
        $this->assertCount(2, $list);
    }

    public function test_criteria_returned_in_sort_order(): void {
        $this->ctrl->save_criterion($this->make_criterion('beta', 2));
        $this->ctrl->save_criterion($this->make_criterion('alpha', 1));

        $list = array_values($this->ctrl->get_criteria_list($this->assign->id));
        $this->assertEquals('alpha', $list[0]->name);
        $this->assertEquals('beta', $list[1]->name);
    }

    public function test_duplicate_criterion_name_is_ignored(): void {
        $this->ctrl->save_criterion($this->make_criterion('clarity', 1));
        $this->ctrl->save_criterion($this->make_criterion('clarity', 1));

        $list = $this->ctrl->get_criteria_list($this->assign->id);
        $this->assertCount(1, $list);
    }

    public function test_update_criterion(): void {
        $saved = $this->ctrl->save_criterion($this->make_criterion('clarity', 1));

        $update              = $this->make_criterion('clarity', 1);
        $update->id          = $saved->id;
        $update->description = 'Updated description';
        $this->ctrl->save_criterion($update);

        $list = $this->ctrl->get_criteria_list($this->assign->id);
        $this->assertEquals('Updated description', reset($list)->description);
    }

    public function test_delete_criterion_removes_it(): void {
        $saved = $this->ctrl->save_criterion($this->make_criterion('grammar', 1));
        $this->ctrl->delete_criterion($saved->id, $this->assign->id);

        $list = $this->ctrl->get_criteria_list($this->assign->id);
        $this->assertCount(0, $list);
    }

    public function test_delete_all_criteria(): void {
        $this->ctrl->save_criterion($this->make_criterion('alpha', 1));
        $this->ctrl->save_criterion($this->make_criterion('beta', 2));
        $this->ctrl->delete_all_criteria($this->assign->id);

        $list = $this->ctrl->get_criteria_list($this->assign->id);
        $this->assertCount(0, $list);
    }

    // Sort order.

    public function test_change_sort_order_up_swaps_items(): void {
        $a = $this->ctrl->save_criterion($this->make_criterion('alpha', 1));
        $b = $this->ctrl->save_criterion($this->make_criterion('beta', 2));

        $result = $this->ctrl->change_criterion_sort_order($b->id, 'up', $this->assign->id);
        $this->assertTrue($result);

        $list = array_values($this->ctrl->get_criteria_list($this->assign->id));
        $this->assertEquals('beta', $list[0]->name);
        $this->assertEquals('alpha', $list[1]->name);
    }

    public function test_change_sort_order_down_swaps_items(): void {
        $a = $this->ctrl->save_criterion($this->make_criterion('alpha', 1));
        $b = $this->ctrl->save_criterion($this->make_criterion('beta', 2));

        $result = $this->ctrl->change_criterion_sort_order($a->id, 'down', $this->assign->id);
        $this->assertTrue($result);

        $list = array_values($this->ctrl->get_criteria_list($this->assign->id));
        $this->assertEquals('beta', $list[0]->name);
        $this->assertEquals('alpha', $list[1]->name);
    }

    public function test_change_sort_order_at_boundary_returns_false(): void {
        $a = $this->ctrl->save_criterion($this->make_criterion('only', 1));

        $result = $this->ctrl->change_criterion_sort_order($a->id, 'up', $this->assign->id);
        $this->assertFalse($result);
    }

    // Comments.

    public function test_save_and_get_comment(): void {
        $crit = $this->ctrl->save_criterion($this->make_criterion('vocab', 1));
        $this->ctrl->save_comment($this->make_comment($crit->id, 'Well done!'), $this->assign->id);

        $list = $this->ctrl->get_comment_list($this->assign->id);
        $this->assertCount(1, $list);
        $this->assertEquals('Well done!', reset($list)->comment);
    }

    public function test_duplicate_comment_is_ignored(): void {
        $crit = $this->ctrl->save_criterion($this->make_criterion('vocab', 1));
        $this->ctrl->save_comment($this->make_comment($crit->id, 'Good job!'), $this->assign->id);
        $this->ctrl->save_comment($this->make_comment($crit->id, 'Good job!'), $this->assign->id);

        $list = $this->ctrl->get_comment_list($this->assign->id);
        $this->assertCount(1, $list);
    }

    public function test_delete_comment(): void {
        $crit = $this->ctrl->save_criterion($this->make_criterion('vocab', 1));
        $this->ctrl->save_comment($this->make_comment($crit->id, 'Excellent!'), $this->assign->id);

        $list       = $this->ctrl->get_comment_list($this->assign->id);
        $commentid  = (int) reset($list)->id;
        $this->ctrl->delete_comment($commentid, $this->assign->id);

        $this->assertCount(0, $this->ctrl->get_comment_list($this->assign->id));
    }

    public function test_delete_criterion_also_removes_its_comments(): void {
        $crit = $this->ctrl->save_criterion($this->make_criterion('vocab', 1));
        $this->ctrl->save_comment($this->make_comment($crit->id, 'Nice!'), $this->assign->id);

        $this->ctrl->delete_criterion($crit->id, $this->assign->id);

        $this->assertCount(0, $this->ctrl->get_comment_list($this->assign->id));
    }

    // Plugin data cleanup.

    public function test_delete_plugin_data_removes_criteria_and_comments(): void {
        $crit = $this->ctrl->save_criterion($this->make_criterion('structure', 1));
        $this->ctrl->save_comment($this->make_comment($crit->id, 'Good structure!'), $this->assign->id);

        $this->ctrl->delete_plugin_data($this->assign->id);

        $this->assertCount(0, $this->ctrl->get_criteria_list($this->assign->id));
        $this->assertCount(0, $this->ctrl->get_comment_list($this->assign->id));
    }

    // Helpers.

    /**
     * Builds a criterion stdClass fixture.
     */
    private function make_criterion(string $name, int $sortorder): \stdClass {
        $d                  = new \stdClass();
        $d->id              = 0;
        $d->assignment      = $this->assign->id;
        $d->name            = $name;
        $d->description     = ucfirst($name) . ' criterion';
        $d->backgroundcolor = '#cccccc';
        $d->sortorder       = $sortorder;
        $d->instruction_ai  = '';
        return $d;
    }

    /**
     * Builds a comment stdClass fixture.
     */
    private function make_comment(int $criterionid, string $text): \stdClass {
        $c              = new \stdClass();
        $c->id          = 0;
        $c->criterionid = $criterionid;
        $c->comment     = $text;
        return $c;
    }
}
