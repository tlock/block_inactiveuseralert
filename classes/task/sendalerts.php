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

namespace block_inactiveuseralert\task;

use block_inactiveuseralert\helper;

class sendalerts extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('sendalertstask', 'block_inactiveuseralert');
    }

    public function execute() {
        global $CFG, $DB;
        require_once("{$CFG->libdir}/completionlib.php");
        $pending = helper::get_pending_alert_info();

        // Preload course info.
        $courseids = array_map(function($alert) {
            return $alert->course;
        }, $pending);

        $courses = $DB->get_records_list('course', 'id', $courseids, '', 'id, shortname, fullname, visible');
        $subject = get_string('subject', 'block_inactiveuseralert');

        $ccusers = [];
        foreach ($courses as $course) {
            $ccusers[$course->id] = get_enrolled_users(\context_course::instance($course->id), 'moodle/course:update');
        }

        foreach ($pending as $alert) {
            $cc = isset($ccusers[$alert->course]) ? $ccusers[$alert->course] : [];
            if ($alert->alerttype == 'login') {
                $this->process_login_alert($alert, $courses, $subject, $cc);
                continue;
            }
            $this->process_activity_alert($alert, $courses, $subject, $cc);
        }
    }

    protected function process_activity_alert($alert, $courses, $subject, $ccusers) {
        global $DB, $SITE;
        mtrace("Processing inactiveuseralert for cmid: {$alert->cmid}");

        if (!$alert->cmid || !$DB->record_exists('course_modules', array('id' => $alert->cmid, 'course' => $alert->course))
            && $alert->alerttype == 'activity') {
           mtrace("- Error: could not load an invalid cmid for alert id: {$alert->id}");
           return false;
        }

        $context = \context_module::instance($alert->cmid);
        $cm = get_coursemodule_from_id(null, $alert->cmid, $alert->course);
        if (!$cm) {
            mtrace('- Error: could not load cm record');
            return false;
        }
        $activity = (object)[
            'url' => new \moodle_url("/mod/$cm->modname/view.php", ['id' => $alert->cmid]),
            'name' => $cm->name,
        ];

        // Get users who haven't completed the activity.
        list($esql, $params) = get_enrolled_sql($context, '', 0, true);
        $sql = "SELECT u.*
                FROM {user} u
                JOIN ($esql) eu ON eu.id = u.id
                WHERE u.id NOT IN (
                    SELECT userid
                    FROM {course_modules_completion}
                    WHERE coursemoduleid = :cmid AND completionstate = 1
                )";
        $params['cmid'] = $alert->cmid;
        $users = $DB->get_records_sql($sql, $params);

        $users = helper::filter_by_students($users, $alert, $context);

        foreach ($users as $user) {
            // Course vis check.
            if (!$courses[$alert->course]->visible && !has_capability('moodle/course:viewhiddencourses', $context, $user)) {
                continue;
            }
            // CM vis check.
            if (empty($cm->visible) && !has_capability('moodle/course:viewhiddenactivities', $context, $user)) {
                continue;
            }

            list($text, $html) = helper::parse_template($alert->template, $courses[$alert->course], $user, $activity);
            email_to_user($user, $SITE->shortname, $subject, $text, $html);
            if ($alert->cc) {
                foreach ($ccusers as $ccuser) {
                    email_to_user($ccuser, $SITE->shortname, $subject, $text, $html);
                }
            }
            $alert->track($user->id);
        }

        $alert->set_sent_and_save();
        return true;
    }

    protected function process_login_alert($alert, $courses, $subject, $ccusers) {
        global $DB, $SITE;
        mtrace("Processing inactiveuseralert for courseid: {$alert->course}");

        // Get users who haven't viewed the course.
        $context = \context_course::instance($alert->course);
        list($esql, $params) = get_enrolled_sql($context, '', 0, true);
        $params['courseid'] = $alert->course;
        $sql = "SELECT u.*
                FROM {user} u
                JOIN ($esql) eu ON eu.id = u.id
                WHERE u.id NOT IN (
                    SELECT userid
                    FROM {user_lastaccess}
                    WHERE courseid = :courseid
                )";
        $users = $DB->get_records_sql($sql, $params);

        $users = helper::filter_by_students($users, $alert, $context);

        // Assuming they can view the course at all, email them an alert.
        foreach ($users as $user) {
            if (!$courses[$alert->course]->visible && !has_capability('moodle/course:viewhiddencourses', $context, $user)) {
                continue;
            }

            list($text, $html) = helper::parse_template($alert->template, $courses[$alert->course], $user);
            email_to_user($user, $SITE->shortname, $subject, $text, $html);
            if ($alert->cc) {
                foreach ($ccusers as $ccuser) {
                    email_to_user($ccuser, $SITE->shortname, $subject, $text, $html);
                }
            }
            $alert->track($user->id);
        }

        $alert->set_sent_and_save();
        return true;
    }

}
