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

require_once(dirname(__FILE__).'/../../config.php');

$courseid = required_param('course', PARAM_INT);
$type = optional_param('alerttype', 'login', PARAM_ALPHA);
$alertid = optional_param('id', 0, PARAM_INT);
$delete = optional_param('delete', false, PARAM_BOOL);
$confirm = optional_param('confirm', false, PARAM_BOOL);

$context = context_course::instance($courseid);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course);
require_capability('block/inactiveuseralert:addinstance', $context);

$PAGE->set_context($context);
$pagename = get_string('addalert', 'block_inactiveuseralert');
$title = format_string($course->fullname) . ": $pagename";

$pageurl = new moodle_url('/blocks/inactiveuseralert/alert.php', array('course' => $courseid));
$PAGE->navbar->add(get_string('pluginname', 'block_inactiveuseralert'));
$PAGE->navbar->add($pagename, $pageurl);
$PAGE->set_pagelayout('report');
$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('course') . ': ' . $course->fullname);
$PAGE->set_heading($title);

$activities = [];
if ($type == 'activity') {
    $modinfo = get_fast_modinfo($course);
    $mods = $modinfo->get_cms();

    foreach ($mods as $mod) {
        if ($mod->completion) {
            $activities[$mod->id] = $mod->name;
        }
    }
    asort($activities);

    if (empty($activities)) {
        print_error('errornoactivitieswithcompletion', 'block_inactiveuseralert');
    }
}

$returnurl = new moodle_url('/course/view.php', ['id' => $courseid]);
if ($biid = $DB->get_field('block_instances', 'id', ['blockname' => 'inactiveuseralert', 'parentcontextid' => $context->id])) {
    $returnurl->param('bui_editid', $biid);
    $returnurl->param('sesskey', sesskey());
}

$alert = new \block_inactiveuseralert\alert();
$alert->course = $courseid;
$alert->alerttype = $type;
$alert->template = get_config('block_inactiveuseralert', 'defaulttemplate'.$type);
if (!empty($alertid)) {
    $alert = \block_inactiveuseralert\alert::instance($alertid);
} else if ($type == 'login') {
    $alert = \block_inactiveuseralert\alert::fetch_login_alert($courseid);
}

// Ensure no one is trying to edit an alert that belongs to another course.
if ($courseid != $alert->course) {
    print_error('errorcoursemismatch', 'block_inactiveuseralert');
}

if ($delete && !empty($alert->id)) {
    if ($confirm) {
        $alert->delete();
        redirect($returnurl);
    }
    echo $OUTPUT->header();
    $url = new moodle_url('/blocks/inactiveuseralert/alert.php', ['course' => $courseid, 'delete' => 1, 'id' => $alertid, 'confirm' => 1]);

    if (!$DB->record_exists('course_modules', array('course' => $alert->course, 'id' => $alert->cmid))) {
        echo $OUTPUT->confirm(get_string('deleteconfirminvalidalert', 'block_inactiveuseralert'), $url, $returnurl);
    } else {
        echo $OUTPUT->confirm(get_string('deleteconfirm', 'block_inactiveuseralert', $activities[$alert->cmid]), $url, $returnurl);
    }

    echo $OUTPUT->footer();
    exit;
}

$data = $alert->data_for_form();
$data->course = $courseid;

$actoptions = $activities;
if (empty($alertid) && $type == 'activity') {
    $actoptions = \block_inactiveuseralert\helper::get_cms_without_alert($modinfo, $courseid);
}

$mform = new \block_inactiveuseralert\form\alert(null,
    array('type' => $type, 'course' => $course, 'activities' => $actoptions, 'alertid' => $alertid, 'alert' => $alert));
$mform->set_data($data);

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    foreach ($data as $key => $value) {
        if (substr($key, 0, 5) == 'alert') {
            $alert->set_alert_time(substr($key, 5), $value);
            continue;
        }
        $alert->$key = $value;
    }
    $alert->save();
    redirect($returnurl);
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
