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
 * Unit tests for block_inactiveuseralert.
 *
 * @package    block_inactiveuseralert
 * @copyright  2016 Marcus Fabriczy <marcus.fabriczy@blackboard.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for block_inactiveuseralert.
 *
 * @package    block_inactiveuseralert
 * @copyright  2016 Marcus Fabriczy <marcus.fabriczy@blackboard.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_inactiveuseralert_testcase extends advanced_testcase {
    /**
     * Setup.
     */
    public function setUp() {
        $this->resetAfterTest();
    }

    public function test_filter_by_students() {
        global $DB;

        $course1 = $this->getDataGenerator()->create_course();

        $users = array();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $expected = array();
        $expected[$user1->id] = $user1;

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));

        $alert = new stdClass();
        $alert->studentsonly = true;

        $context = context_course::instance($course1->id);

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);

        role_assign($studentrole->id, $user1->id, $context->id);
        role_assign($teacherrole->id, $user2->id, $context->id);
        role_unassign($studentrole->id, $user2->id, $context->id);

        $users[$user1->id] = $user1;
        $users[$user2->id] = $user2;

        accesslib_clear_all_caches_for_unit_testing();

        $actual = \block_inactiveuseralert\helper::filter_by_students($users, $alert, $context);

        $this->assertEquals($expected, $actual);
    }
}
