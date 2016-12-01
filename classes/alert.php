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

namespace block_inactiveuseralert;

class alert {

    private $record;
    private $altered = false;

    public function __construct($record = null) {
        $this->record = (object) [
            'alert1' => 0,
            'alert2' => 0,
            'alert3' => 0,
            'cmid' => 0,
            'template' => '',
            'enabled' => 0,
            'alerttype' => '',
            'sent1' => 0,
            'sent2' => 0,
            'sent3' => 0,
            'cc' => 0,
            'course' => 0,
        ];
        if (!is_null($record)) {
            foreach ($record as $key => $value) {
                $this->record->$key = $value;
            }
        }
    }

    public function __get($name) {
        if (isset($this->record->$name)) {
            return $this->record->$name;
        }
        return null;
    }

    public function __isset($name) {
        return isset($this->record->$name);
    }

    public function __set($name, $value) {
        if (!isset($this->record->$name)) {
            return;
        }
        if ($this->record->$name != $value) {
            $this->altered = true;
        }
        $this->record->$name = $value;
    }

    public function data_for_form() {
        return clone $this->record;
    }

    public function delete() {
        global $DB;
        $DB->delete_records('block_inactiveuseralert_trac', ['alertid' => $this->record->id]);
        $DB->delete_records('block_inactiveuseralert', ['id' => $this->record->id]);
    }

    public static function instance($id) {
        global $DB;
        $record = $DB->get_record('block_inactiveuseralert', array('id' => $id), '*', MUST_EXIST);
        return new self($record);
    }

    public function set_alert_time($number, $time) {
        $key = "alert{$number}";
        $sentkey = "sent{$number}";

        if ($this->record->$key != $time) {
            $this->altered = true;
        }
        $this->record->$key = $time;
        // If they're setting a date in the past, flag it as sent.
        $this->record->$sentkey = $time <= strtotime('today') && $time > 0;
    }

    public static function fetch_login_alert($courseid) {
        global $DB;

        if (!$record = $DB->get_record('block_inactiveuseralert', array('course' => $courseid, 'alerttype' => 'login'))) {
            $record = new \stdClass();
            $record->alerttype = 'login';
            $record->course = $courseid;
            $record->template = get_config('block_inactiveuseralert', 'defaulttemplatelogin');
            $record->enabled = 0;
        }

        return new self($record);
    }

    public function save() {
        global $DB;
        if (!$this->valid()) {
            return false;
        }
        $this->record->timemodified =  time();
        if (empty($this->record->id)) {
            $this->record->timecreated = $this->record->timemodified;
            $this->record->id = $DB->insert_record('block_inactiveuseralert', $this->record);
        }

        if (!$this->altered) {
            return true;
        }
        if ($update = $DB->update_record('block_inactiveuseralert', $this->record)) {
            $this->altered = false;
        }
        return $update;
    }

    public function set_sent_and_save() {
        for ($i = 1; $i <=3; $i++) {
            $key = "alert{$i}";
            $sent = "sent{$i}";
            if ($this->record->$key < time()) {
                $this->record->$sent = 1;
                $this->altered = true;
            }
        }
        return $this->save();
    }

    public function track($userid) {
        global $DB;

        // Reverse search so we get the newest valid one.
        for ($i = 3; $i >= 1; $i--) {
            $key = "alert{$i}";
            $sent = "sent{$i}";
            if ($this->record->$key > 0 && $this->record->$key < time() && $this->record->$sent == 0) {
                $alertnumber = $i;
                $alerttime = $this->record->$key;
                break;
            }
        }
        if (!isset($alertnumber)) {
            return;
        }

        $params = [
            'alertid' => $this->record->id,
            'userid' => $userid,
            'alertnumber' => $alertnumber,
        ];

        if (!$DB->record_exists('block_inactiveuseralert_trac', $params)) {
            $track = (object)$params;
            $track->alerttime = $alerttime;
            $track->timecreated = time();
            $DB->insert_record('block_inactiveuseralert_trac', $track);
        }
    }

    public function valid() {
        if (empty($this->record->course) || empty($this->record->alerttype)) {
            return false;
        }
        if ($this->record->alerttype == 'login' && !empty($this->record->cmid)) {
            return false;
        }
        return true;
    }
}
