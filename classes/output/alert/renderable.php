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

namespace block_inactiveuseralert\output\alert;

defined('MOODLE_INTERNAL') || die;

/**
 * Renderable class for alerts.
 *
 * @package    block_inactiveuseralert
 * @copyright  2015 Blackboard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderable implements \renderable {

    /**
     * Alerts enabled.
     *
     * @var bool
     * @access public
     */
    public $enabled;

    /**
     * Times for the alerts to be sent.
     *
     * @var array List of upto 3 alerts with stats.
     *
     * First number is the datetime the alert is set for.
     * Second number is how many were sent.
     *
     * Example:
     * $alerts = array(
     *  array(1234567890, 5),
     *  array(1234588888, null),
     *  array(1234599999, null),
     * );
     *
     * @access public
     */
    public $alerts;

    public $courseid;

    public function __construct($enabled, array $alerts, $courseid, $name) {
        $this->enabled = $enabled;
        $this->alerts = $alerts;
        $this->courseid = $courseid;
        $this->name = $name;
    }
}
