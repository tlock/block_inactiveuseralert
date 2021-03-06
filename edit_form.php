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

use block_inactiveuseralert\alert;
use block_inactiveuseralert\helper;

class block_inactiveuseralert_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        global $OUTPUT;
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $url = new moodle_url('/blocks/inactiveuseralert/alert.php', ['course' => $this->page->course->id]);
        $pix = new pix_icon('t/edit', get_string('edit'));
        $link = html_writer::link($url, $OUTPUT->render($pix));
        $mform->addElement('static', 'config_login', get_string('typelogin', 'block_inactiveuseralert'), $link);


        $modinfo = get_fast_modinfo($this->page->course);

        $link = get_string('nomoreactivities', 'block_inactiveuseralert');
        if (!empty(helper::get_cms_without_alert($modinfo, $this->page->course->id))) {
            $url = new moodle_url('/blocks/inactiveuseralert/alert.php', ['course' => $this->page->course->id, 'alerttype' => 'activity']);
            $link = html_writer::link($url, get_string('addalert', 'block_inactiveuseralert'));
        }
        $mform->addElement('static', 'config_activity', get_string('typeactivity', 'block_inactiveuseralert'), $link);

        $enabledalerts = helper::get_alerts($this->page->course->id);
        $url = new moodle_url('/blocks/inactiveuseralert/alert.php', ['course' => $this->page->course->id, 'alerttype' => 'activity']);
        foreach ($enabledalerts as $alert) {
            if ($alert->alerttype != 'activity') {
                 continue;
            }

            if ((!$alert->cmid || !isset($modinfo->cms[$alert->cmid])) && $alert->alerttype == 'activity') {
                $name = get_string('invalidalert', 'block_inactiveuseralert');
            } else {
                $name = $modinfo->cms[$alert->cmid]->name;
            }

            $url->param('id', $alert->id);
            $pix = new pix_icon('t/edit', get_string('edit'));
            $link = html_writer::link($url, $OUTPUT->render($pix));
            $url->param('delete', '1');
            $pix = new pix_icon('t/delete', get_string('delete'));
            $link .= '&nbsp;&nbsp;'.html_writer::link($url, $OUTPUT->render($pix));
            $mform->addElement('static', 'config_activity', $name, $link);
            $url->remove_params('delete');
        }
    }

}
