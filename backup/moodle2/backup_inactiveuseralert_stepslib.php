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

class backup_inactiveuseralert_block_structure_step extends backup_block_structure_step {

    protected function define_structure() {
        global $DB;

        $inactiveuseralert = new backup_nested_element('inactiveuseralert', array('id'), array(
            'enabled', 'alerttype', 'alert1', 'alert2', 'alert3', 'sent1', 'sent2', 'sent3',
            'cmid', 'template', 'cc', 'timecreated', 'timemodified',
        ));

        $tracks = new backup_nested_element('tracks');
        $track = new backup_nested_element('track', array('id'), array(
            'alertid', 'userid', 'alertnumber', 'alerttime', 'timecreated',
        ));

        $inactiveuseralert->add_child($tracks);
        $tracks->add_child($track);

        $inactiveuseralert->set_source_table('block_inactiveuseralert', array('course' => backup::VAR_COURSEID));
        $track->set_source_table('block_inactiveuseralert_trac', array('alertid' => backup::VAR_PARENTID));

        $inactiveuseralert->annotate_ids('course_modules', 'cmid');
        $track->annotate_ids('user', 'userid');

        // Return the root element (inactiveuseralert), wrapped into standard block structure
        return $this->prepare_block_structure($inactiveuseralert);
    }
}
