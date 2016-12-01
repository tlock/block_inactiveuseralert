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
 * Form for editing the block instances.
 *
 * @package   block_inactiveuseralert
 * @copyright 2015 Blackboard
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_inactiveuseralert\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class alert extends \moodleform {
    protected function definition() {
        $mform = $this->_form;
        $type = 'login';
        if (!empty($this->_customdata['type'])) {
            $type = $this->_customdata['type'];
        }
        $course = $this->_customdata['course'];
        $startdate = !empty($course->startdate) ? $course->startdate : time();

        $mform->addElement('header', 'addalert', get_string('addalert', 'block_inactiveuseralert'));

        $mform->addElement('static', 'typetext', get_string('type', 'block_inactiveuseralert'),
            get_string("type$type", 'block_inactiveuseralert'));

        if (!$course->enablecompletion && $type == 'activity') {
            $mform->addElement('static', 'completionwarning', '', get_string('errorcoursecompletiondisabled', 'block_inactiveuseralert'));
        }
        $mform->addElement('selectyesno', 'enabled', get_string('enabled', 'block_inactiveuseralert'));
        $mform->setDefault('enabled', 0);

        $mform->addElement('selectyesno', 'studentsonly', get_string('studentsonly', 'block_inactiveuseralert'));
        $mform->setDefault('studentsonly', 0);
        $mform->addHelpButton('studentsonly', 'studentsonly', 'block_inactiveuseralert');

        if ($type == 'activity') {
            $mform->addElement('select', 'cmid', get_string('activity', 'block_inactiveuseralert'), $this->_customdata['activities']);
            $mform->addRule('cmid', get_string('required'), 'required');
            if (!empty($this->_customdata['alertid'])) {
                $mform->hardFreeze('cmid');
            }
        }

        $offset = 5;
        for ($i = 1; $i <= 3; $i++) {
            $default = $startdate + $offset * DAYSECS;
            $optional = $i > 1;
            $mform->addElement('date_selector', "alert{$i}",
                get_string('alertdate', 'block_inactiveuseralert', $i), array('optional' => $optional));
            $mform->setDefault("alert{$i}", $default);
            $offset += 2;
        }

        $mform->addElement('textarea', 'template', get_string('alerttemplate', 'block_inactiveuseralert'), array('cols' => 40, 'rows' => 5));
        $mform->addHelpButton('template', $type.'alerttemplate', 'block_inactiveuseralert');
        $mform->addRule('template', get_string('required'), 'required');

        $mform->addElement('selectyesno', 'cc', get_string('cc', 'block_inactiveuseralert'));
        $mform->addHelpButton('cc', 'cc', 'block_inactiveuseralert');
        $mform->setDefault('cc', 0);

        $mform->addElement('hidden', 'course');
        $mform->setType('course', PARAM_INT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'alerttype');
        $mform->setType('alerttype', PARAM_ALPHA);
        $mform->setDefault('alerttype', $type);

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = [];

        // Any new alerts setup need to be in the future. So only todays date or higher is accepted.
        $existing = $this->_customdata['alert'];
        for ($i = 1; $i <=3; $i++) {
            if ($existing->{"alert$i"} != $data["alert$i"] && $data["alert$i"] < usergetmidnight(time())) {
                $errors["alert$i"] = get_string('errorinthepast', 'block_inactiveuseralert');
            }
        }

        for ($i = 2; $i <=3; $i++) {
            $field = $data["alert$i"];
            $prev = $i - 1;
            $prevfield = $data["alert$prev"];

            if ($field > 0 && $field < $prevfield) {
                $errors["alert$i"] = get_string('errorearlierthanprevious', 'block_inactiveuseralert');
            }
        }

        return $errors;
    }

}
