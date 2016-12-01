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

namespace block_inactiveuseralert\output\report;

defined('MOODLE_INTERNAL') || die;

/**
 * Renderable class for the report page.
 *
 * @package    block_inactiveuseralert
 * @copyright  2015 Blackboard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderable implements \renderable {

    public $columns;

    public $count;

    public $page;

    public $perpage;

    public $url;

    public $userdata;

    /**
     * Name of the alert.
     */
    public $name;

    /**
     * List of alerts, keyed on alertid.
     */
    public $alertlist;

    /**
     * The alertid we're displaying data for.
     */
    public $selected;

    /**
     * The courses modinfo from get_fast_modinfo.
     */
    public $modinfo;

    public $type;

    public $sentcount;

    public function __construct($name, $userdata, $count, $page, $url, $trackdata, $alertlist, $selected, $modinfo, $sentcount) {
        $this->name = $name;
        $this->modinfo = $modinfo;
        $this->count = $count;
        $this->perpage = \block_inactiveuseralert\report::PERPAGE;
        $this->page = $page;
        $this->url = $url;
        $this->selected = $selected;
        $this->alertlist = $alertlist;
        $this->userdata = $userdata;
        $this->type = isset($alertlist[$selected]) ? $alertlist[$selected]->alerttype : 'login';
        $accessorstate = $this->type == 'login' ?
            get_string('lastcourseaccess') : get_string('activitiescompleted', 'completion');

        $this->columns = [
            'username' => get_string('username'),
            'userfullname' => get_string('fullnameuser'),
            'accessorstate' => $accessorstate,
            'alerts' => get_string('alertssent', 'block_inactiveuseralert'),
        ];

        $this->sentcount = $sentcount;

        foreach ($trackdata as $data) {
            if (!isset($this->userdata[$data->userid])) {
                continue;
            }
            if (empty($this->userdata[$data->userid]->alerts)) {
                $this->userdata[$data->userid]->alerts = array();
            }
            $this->userdata[$data->userid]->alerts[] = $data;
        }
    }
}
