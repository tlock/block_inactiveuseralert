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

class helper {

    public static function load_alerts_with_track($alertid) {
        global $DB;

        $alertdata = [];
        $alert = alert::instance($alertid);

        $alertdata = [
            [$alert->alert1, 0],
            [$alert->alert2, 0],
            [$alert->alert3, 0],
        ];
        // Get track counts.
        $sql = "SELECT alertnumber, count(userid)
                FROM {block_inactiveuseralert_trac}
                WHERE alertid = ?
                GROUP BY alertnumber";
        $tracks = $DB->get_records_sql($sql, [$alert->id]);
        foreach ($tracks as $track) {
            $alertdata[$track->alertnumber - 1][1] = $track->count;
        }

        return $alertdata;
    }

    public static function get_alerts($courseid, $enabledonly = false) {
        global $DB;

        $params = ['course' => $courseid];
        if ($enabledonly) {
            $params['enabled'] = 1;
        }
        $records = $DB->get_records('block_inactiveuseralert', $params);
        $alerts = [];
        foreach ($records as $record) {
            $alerts[$record->id] = new alert($record);
        }
        return $alerts;
    }

    public static function get_pending_alert_info() {
        global $DB;

        $time = time();
        $sql = "SELECT *
                FROM {block_inactiveuseralert}
                WHERE enabled = 1 AND (
                    (alert1 < ? AND alert1 > 0 AND sent1 = 0) OR
                    (alert2 < ? AND alert2 > 0 AND sent2 = 0) OR
                    (alert3 < ? AND alert3 > 0 AND sent3 = 0)
                )";
        $params = [$time, $time, $time];

        $records = $DB->get_records_sql($sql, $params);
        $alerts = [];
        foreach ($records as $record) {
            $alerts[$record->id] = new alert($record);
        }
        return $alerts;
    }

    /**
     * Parse template
     *
     * @param string $template The template to parse.
     * @param object $course Has the id, shortname and fullname of the course.
     * @param object $user A user record.
     * @param object $activity Has the url and name of the activity.
     * @return array The parsed template.
     */
    public static function parse_template($template, $course, $user, $activity = null) {
        $activityurl = isset($activity->url) ? $activity->url : '';
        $activityname = isset($activity->name) ? $activity->name : '';
        $courseurl = new \moodle_url('/course/view.php', array('id' => $course->id));

        $html = str_replace('{clink}', \html_writer::link($courseurl, $course->fullname), $template);
        $html = str_replace('{alink}', \html_writer::link($activityurl, $activityname), $html);
        $html = str_replace('{cfull}', format_string($course->fullname), $html);
        $html = str_replace('{cshort}', format_string($course->shortname), $html);
        $html = str_replace('{userfullname}', fullname($user), $html);
        $html = nl2br($html);

        $text = html_to_text($html);

        return array($text, $html);
    }

    public static function get_cms_without_alert($modinfo, $courseid) {
        global $DB;
        $existing = $DB->get_records('block_inactiveuseralert', ['course' => $courseid, 'alerttype' => 'activity'], '', 'cmid');

        $options = [];
        foreach ($modinfo->cms as $cmid => $data) {
            if ($data->completion && !isset($existing[$cmid])) {
                $options[$cmid] = $data->name;
            }
        }
        return $options;
    }

    /**
     * Filters the users in the array who have the role of student.
     *
     * @param array $users Users which may have different roles
     * @param object $alert An instance of the alert itself
     * @param object $context The course context
     * @return array $users The users array with just students only
     */
    public static function filter_by_students($users, $alert, $context) {
        if ($alert->studentsonly and !empty($users)) {
            $userids = get_users_by_capability($context, 'moodle/course:viewhiddencourses', 'u.id');

            $users = array_udiff($users, $userids,
                function ($a, $b) {
                    return $a->id - $b->id;
                }
            );
        }
        return $users;
    }
}
