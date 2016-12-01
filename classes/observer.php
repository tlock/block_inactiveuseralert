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

defined('MOODLE_INTERNAL') || die();

class block_inactiveuseralert_observer {

    /**
     * Delete the alerts and notifications sent
     *
     * @param \core\event\base $event
     */
    public static function delete(\core\event\base $event) {
        global $DB;

        if ($event->eventname == '\core\event\course_module_deleted') {
            if ($alertstodelete = $DB->get_records('block_inactiveuseralert', array('cmid' => $event->objectid))){
                foreach ($alertstodelete as $alert) {
                    // Delete sent notifications
                    $DB->delete_records('block_inactiveuseralert_trac', array('alertid' => $alert->id));

                    // Delete the alerts
                    $DB->delete_records('block_inactiveuseralert', array('cmid' => $event->objectid));
                }
            }
        }
    }
}
