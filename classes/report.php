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

class report {

    const PERPAGE = 50;
    protected $offset;
    public $context;
    public $page;
    public $users;

    public function __construct() {
        $this->page = optional_param('page', 0, PARAM_INT);
        $this->offset = $this->page * self::PERPAGE;
        $courseid = required_param('course', PARAM_INT);
        $this->context = \context_course::instance($courseid);
    }

    public function load_login_data() {
        global $DB;
        list($esql, $params) = get_enrolled_sql($this->context, '', 0, true);

        $namefields = get_all_user_name_fields(true, 'u');
        $namesql = $DB->sql_fullname('u.firstname', 'u.lastname');
        $select = "SELECT u.id, u.username, $namesql AS userfullname, $namefields, ul.timeaccess AS accessorstate, '' AS alerts ";
        $count = "SELECT count(1) ";
        $sql = "FROM {user} u
                JOIN ($esql) ue ON ue.id = u.id
                LEFT JOIN {user_lastaccess} ul ON ul.userid = u.id AND ul.courseid = :courseid ";
        $order = "ORDER BY u.username ASC";
        $params['courseid'] = $this->context->instanceid;

        $users = $DB->get_records_sql($select.$sql.$order, $params, $this->offset, self::PERPAGE);
        $count = $DB->count_records_sql($count.$sql, $params);

        $sql = "SELECT t.*
                FROM {block_inactiveuseralert_trac} t
                JOIN {block_inactiveuseralert} b ON b.course = :courseid AND b.alerttype = 'login' AND b.id = t.alertid
                ORDER BY t.userid ASC, t.alertnumber ASC";
        $params = ['courseid' => $this->context->instanceid];
        $alerts = $DB->get_records_sql($sql, $params);

        $this->users = $users;

        return array($users, $alerts, $count);
    }

    public function load_activity_data(alert $alert) {
        global $CFG, $DB;
        require_once("{$CFG->libdir}/completionlib.php");

        list($esql, $params) = get_enrolled_sql($this->context, '', 0, true);

        $namefields = get_all_user_name_fields(true, 'u');
        $namesql = $DB->sql_fullname('u.firstname', 'u.lastname');
        $select = "SELECT u.id, u.username, $namesql AS userfullname, $namefields, cmc.completionstate AS accessorstate, '' AS alerts ";
        $count = "SELECT count(1) ";
        $sql = "FROM {user} u
                JOIN ($esql) ue ON ue.id = u.id
                LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = :cmid AND cmc.userid = u.id ";
        $order = "ORDER BY u.username ASC";
        $params['cmid'] = $alert->cmid;
        $users = $DB->get_records_sql($select.$sql.$order, $params, $this->offset, self::PERPAGE);
        $count = $DB->count_records_sql($count.$sql, $params);

        $sql = "SELECT t.*
                FROM {block_inactiveuseralert_trac} t
                JOIN {block_inactiveuseralert} b ON b.id = :alertid AND t.alertid = b.id
                ORDER BY t.userid ASC, t.alertnumber ASC";
        $params = ['alertid' => $alert->id];
        $alerts = $DB->get_records_sql($sql, $params);

        $this->users = $users;

        return array($users, $alerts, $count);
    }


}
