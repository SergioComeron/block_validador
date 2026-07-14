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

namespace block_validador;

use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/validador/block_validador.php');

/**
 * Unit tests for block_validador.
 *
 * @package    block_validador
 * @copyright  2024, Sergio Comerón <info@sergiocomeron.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\block_validador::class)]
final class block_validador_test extends \advanced_testcase {
    /** @var \block_validador Block instance used across tests. */
    private \block_validador $block;

    /**
     * Creates a block instance before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->block = new \block_validador();
    }

    /**
     * Calls a private method on the block via reflection.
     *
     * @param string $method Method name.
     * @param array $args Arguments to pass.
     * @return mixed Return value of the method.
     */
    private function callprivate(string $method, array $args = []): mixed {
        $ref = new \ReflectionMethod(\block_validador::class, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs($this->block, $args);
    }

    // -------------------------------------------------------------------------
    // timelimitvalidation
    // -------------------------------------------------------------------------

    /**
     * Timelimit passes for 90 minutes.
     */
    public function test_timelimit_passes_for_90_minutes(): void {
        $quiz = (object)['timelimit' => 5400];
        $result = $this->callprivate('timelimitvalidation', [$quiz]);
        $this->assertTrue($result[0]['passed']);
    }

    /**
     * Timelimit passes for 45 minutes.
     */
    public function test_timelimit_passes_for_45_minutes(): void {
        $quiz = (object)['timelimit' => 2700];
        $result = $this->callprivate('timelimitvalidation', [$quiz]);
        $this->assertTrue($result[0]['passed']);
    }

    /**
     * Timelimit fails for other duration.
     */
    public function test_timelimit_fails_for_other_duration(): void {
        $quiz = (object)['timelimit' => 3600];
        $result = $this->callprivate('timelimitvalidation', [$quiz]);
        $this->assertFalse($result[0]['passed']);
    }

    /**
     * Timelimit fails for zero.
     */
    public function test_timelimit_fails_for_zero(): void {
        $quiz = (object)['timelimit' => 0];
        $result = $this->callprivate('timelimitvalidation', [$quiz]);
        $this->assertFalse($result[0]['passed']);
    }

    // -------------------------------------------------------------------------
    // questionperpagevalidation
    // -------------------------------------------------------------------------

    /**
     * Questionsperpage passes when zero.
     */
    public function test_questionsperpage_passes_when_zero(): void {
        $quiz = (object)['questionsperpage' => 0];
        $result = $this->callprivate('questionperpagevalidation', [$quiz]);
        $this->assertTrue($result[0]['passed']);
    }

    /**
     * Questionsperpage fails when not zero.
     */
    public function test_questionsperpage_fails_when_not_zero(): void {
        $quiz = (object)['questionsperpage' => 1];
        $result = $this->callprivate('questionperpagevalidation', [$quiz]);
        $this->assertFalse($result[0]['passed']);
    }

    // -------------------------------------------------------------------------
    // validate_quiz_has_dates
    // -------------------------------------------------------------------------

    /**
     * Quiz has dates passes when both set.
     */
    public function test_quiz_has_dates_passes_when_both_set(): void {
        $quiz = (object)['timeopen' => time(), 'timeclose' => time() + 3600];
        $result = $this->callprivate('validate_quiz_has_dates', [$quiz]);
        $this->assertTrue($result[0]['passed']);
    }

    /**
     * Quiz has dates fails when timeopen missing.
     */
    public function test_quiz_has_dates_fails_when_timeopen_missing(): void {
        $quiz = (object)['timeopen' => 0, 'timeclose' => time() + 3600];
        $result = $this->callprivate('validate_quiz_has_dates', [$quiz]);
        $this->assertFalse($result[0]['passed']);
    }

    /**
     * Quiz has dates fails when timeclose missing.
     */
    public function test_quiz_has_dates_fails_when_timeclose_missing(): void {
        $quiz = (object)['timeopen' => time(), 'timeclose' => 0];
        $result = $this->callprivate('validate_quiz_has_dates', [$quiz]);
        $this->assertFalse($result[0]['passed']);
    }

    /**
     * Quiz has dates fails when both missing.
     */
    public function test_quiz_has_dates_fails_when_both_missing(): void {
        $quiz = (object)['timeopen' => 0, 'timeclose' => 0];
        $result = $this->callprivate('validate_quiz_has_dates', [$quiz]);
        $this->assertFalse($result[0]['passed']);
    }

    // -------------------------------------------------------------------------
    // validate_quiz_auto_submit
    // -------------------------------------------------------------------------

    /**
     * Autosubmit passes for autosubmit.
     */
    public function test_autosubmit_passes_for_autosubmit(): void {
        $quiz = (object)['overduehandling' => 'autosubmit'];
        $result = $this->callprivate('validate_quiz_auto_submit', [$quiz]);
        $this->assertTrue($result[0]['passed']);
    }

    /**
     * Autosubmit fails for graceperiod.
     */
    public function test_autosubmit_fails_for_graceperiod(): void {
        $quiz = (object)['overduehandling' => 'graceperiod'];
        $result = $this->callprivate('validate_quiz_auto_submit', [$quiz]);
        $this->assertFalse($result[0]['passed']);
    }

    /**
     * Autosubmit fails for open.
     */
    public function test_autosubmit_fails_for_open(): void {
        $quiz = (object)['overduehandling' => 'open'];
        $result = $this->callprivate('validate_quiz_auto_submit', [$quiz]);
        $this->assertFalse($result[0]['passed']);
    }

    // -------------------------------------------------------------------------
    // check_showc
    // -------------------------------------------------------------------------

    /**
     * Check showc returns true when not set.
     */
    public function test_check_showc_returns_true_when_not_set(): void {
        $availability = (object)[];
        $result = $this->callprivate('check_showc', [$availability]);
        $this->assertTrue($result);
    }

    /**
     * Check showc returns true when showc is false.
     */
    public function test_check_showc_returns_true_when_showc_is_false(): void {
        $availability = (object)['showc' => [false]];
        $result = $this->callprivate('check_showc', [$availability]);
        $this->assertTrue($result);
    }

    /**
     * Check showc returns false when showc is true.
     */
    public function test_check_showc_returns_false_when_showc_is_true(): void {
        $availability = (object)['showc' => [true]];
        $result = $this->callprivate('check_showc', [$availability]);
        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // has_group_restriction
    // -------------------------------------------------------------------------

    /**
     * Has group restriction direct match.
     */
    public function test_has_group_restriction_direct_match(): void {
        $availability = (object)['type' => 'group', 'id' => 42];
        $result = $this->callprivate('has_group_restriction', [$availability, 42]);
        $this->assertTrue($result);
    }

    /**
     * Has group restriction no match different id.
     */
    public function test_has_group_restriction_no_match_different_id(): void {
        $availability = (object)['type' => 'group', 'id' => 99];
        $result = $this->callprivate('has_group_restriction', [$availability, 42]);
        $this->assertFalse($result);
    }

    /**
     * Has group restriction nested match.
     */
    public function test_has_group_restriction_nested_match(): void {
        $availability = (object)[
            'op' => '&',
            'c' => [
                (object)['type' => 'date', 'd' => '>=', 't' => time()],
                (object)['type' => 'group', 'id' => 42],
            ],
        ];
        $result = $this->callprivate('has_group_restriction', [$availability, 42]);
        $this->assertTrue($result);
    }

    /**
     * Has group restriction nested no match.
     */
    public function test_has_group_restriction_nested_no_match(): void {
        $availability = (object)[
            'op' => '&',
            'c' => [
                (object)['type' => 'date', 'd' => '>=', 't' => time()],
                (object)['type' => 'group', 'id' => 99],
            ],
        ];
        $result = $this->callprivate('has_group_restriction', [$availability, 42]);
        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // check_completion_condition
    // -------------------------------------------------------------------------

    /**
     * Check completion condition returns false for non completion type.
     */
    public function test_check_completion_condition_returns_false_for_non_completion_type(): void {
        $availability = (object)['type' => 'date', 'd' => '>=', 't' => time()];
        $result = $this->callprivate('check_completion_condition', [$availability]);
        $this->assertFalse($result);
    }

    /**
     * Check completion condition returns false when no conditions.
     */
    public function test_check_completion_condition_returns_false_when_no_conditions(): void {
        $availability = (object)['op' => '&', 'c' => []];
        $result = $this->callprivate('check_completion_condition', [$availability]);
        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // gradetopass (needs DB)
    // -------------------------------------------------------------------------

    /**
     * Gradetopass passes when gradepass is 5.
     */
    public function test_gradetopass_passes_when_gradepass_is_5(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        $DB->set_field('grade_items', 'gradepass', 5, [
            'itemmodule' => 'quiz',
            'iteminstance' => $quiz->id,
        ]);

        $quizrecord = (object)['id' => $quiz->id];
        $result = $this->callprivate('gradetopass', [$quizrecord]);
        $this->assertTrue($result[0]['passed']);
    }

    /**
     * Gradetopass fails when gradepass is not 5.
     */
    public function test_gradetopass_fails_when_gradepass_is_not_5(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        $DB->set_field('grade_items', 'gradepass', 0, [
            'itemmodule' => 'quiz',
            'iteminstance' => $quiz->id,
        ]);

        $quizrecord = (object)['id' => $quiz->id];
        $result = $this->callprivate('gradetopass', [$quizrecord]);
        $this->assertFalse($result[0]['passed']);
    }

    // -------------------------------------------------------------------------
    // validate_quiz_auto_submit — return structure
    // -------------------------------------------------------------------------

    /**
     * Autosubmit returns expected id.
     */
    public function test_autosubmit_returns_expected_id(): void {
        $quiz = (object)['overduehandling' => 'autosubmit'];
        $result = $this->callprivate('validate_quiz_auto_submit', [$quiz]);
        $this->assertEquals('quizautosubmit', $result[0]['id']);
    }

    // -------------------------------------------------------------------------
    // return_pledge_validations
    // -------------------------------------------------------------------------

    /**
     * Return pledge validations structure.
     */
    public function test_return_pledge_validations_structure(): void {
        $result = $this->callprivate('return_pledge_validations', [true, false]);
        $this->assertCount(2, $result);
        $this->assertEquals('quizhaspledgeabove', $result[0]['id']);
        $this->assertEquals('pledgedates', $result[1]['id']);
        $this->assertTrue($result[0]['passed']);
        $this->assertFalse($result[1]['passed']);
    }

    /**
     * Return pledge validations both false.
     */
    public function test_return_pledge_validations_both_false(): void {
        $result = $this->callprivate('return_pledge_validations', [false, false]);
        $this->assertFalse($result[0]['passed']);
        $this->assertFalse($result[1]['passed']);
    }

    /**
     * Return pledge validations both true.
     */
    public function test_return_pledge_validations_both_true(): void {
        $result = $this->callprivate('return_pledge_validations', [true, true]);
        $this->assertTrue($result[0]['passed']);
        $this->assertTrue($result[1]['passed']);
    }
}
