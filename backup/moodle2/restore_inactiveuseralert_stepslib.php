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

class restore_inactiveuseralert_block_structure_step extends restore_structure_step {

    protected $donotrestore;

    protected function define_structure() {

        $paths = array();

        $paths[] = new restore_path_element('inactiveuseralert', '/block/inactiveuseralert');

        $userinfo = $this->get_setting_value('users');
        if ($userinfo) {
            $paths[] = new restore_path_element('track', '/block/inactiveuseralert/tracks/track');
        }

        return $paths;
    }

    public function process_inactiveuseralert($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;

        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        if ($data->alerttype == 'login') {
            if ($DB->record_exists('block_inactiveuseralert', array('alerttype' => 'login', 'course' => $data->course))) {
                return;
            }
        }

        if ($DB->record_exists('block_inactiveuseralert', array('course' => $data->course, 'alerttype' => 'activity',
            'cmid' => $data->cmid))) {
            $this->donotrestore = true;
            return;
        }

        $newid = $DB->insert_record('block_inactiveuseralert', $data);
        $this->set_mapping('block_inactiveuseralert', $oldid, $newid);
    }

    public function process_track($data) {
        global $DB;
        $data = (object)$data;

        $data->alertid = $this->get_mappingid('block_inactiveuseralert', $data->alertid);

        if (empty($data->alertid)) {
            return;
        }

        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        $DB->insert_record('block_inactiveuseralert_trac', $data);
    }

    public function after_restore() {
        global $DB;

        if ($this->donotrestore) {
            return;
        }

        $courseid = $this->get_courseid();
        $alerts = $DB->get_records('block_inactiveuseralert', ['course' => $courseid]);

        foreach ($alerts as $alert) {
            $oldid = $alert->cmid;

            if ($alert->cmid = $this->get_mappingid('course_module', $alert->cmid)) {
                $DB->update_record('block_inactiveuseralert', $alert);
            } else {
                $DB->delete_records('block_inactiveuseralert', array('id' => $alert->id, 'cmid' => 0,
                    'alerttype' => 'activity'));
                $DB->delete_records('block_inactiveuseralert_trac', array('alertid' => $alert->id));
            }

            if (!$DB->record_exists('course_modules', array('id' => $oldid, 'course' => $courseid))) {
                $DB->delete_records('block_inactiveuseralert_trac', array('alertid' => $alert->id));
                $DB->delete_records('block_inactiveuseralert', array('cmid' => $oldid, 'course' => $courseid,
                    'alerttype' => 'activity'));
            }
        }
    }

}
