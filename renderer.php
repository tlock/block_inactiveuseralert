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
 * Block rendrer
 *
 * @package    block_inactiveuseralert
 * @copyright  2015 Blackboard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class block_inactiveuseralert_renderer extends plugin_renderer_base {
    /**
     * Render the main block display.
     *
     * @param \block_inactiveuseralert\output\overview\renderable $overview
     * @return string
     */
    public function alerts(array $alerts) {
        $o = '';
        foreach ($alerts as $alert) {
            if (!empty($o)) {
                $o .= html_writer::empty_tag('br');
            }
            $o .= $this->render_alert($alert);
        }

        if (empty($alerts)) {
            $o .= html_writer::tag('p', get_string('noalerts', 'block_inactiveuseralert'));
        }

        $url = new moodle_url('/blocks/inactiveuseralert/index.php', array('course' => $this->page->course->id));
        $o .= html_writer::link($url, get_string('viewreport', 'block_inactiveuseralert'));

        return $o;
    }

    protected function alert_list($list, $selected, $modinfo) {
        $o = html_writer::start_tag('ul');
        foreach ($list as $alert) {
            $url = $this->page->url;
            $url->param('alertid', $alert->id);
            $name = get_string('typelogin', 'block_inactiveuseralert');
            if ($alert->alerttype == 'activity') {
                $name = $modinfo->cms[$alert->cmid]->name;
            }
            $link = html_writer::link($url, $name);
            $o .= html_writer::tag('li', $link);
        }
        $o .= html_writer::end_tag('ul');
        return $o;
    }

    public function render_report(\block_inactiveuseralert\output\report\renderable $report) {
        $o = $this->output->heading(get_string('reportname', 'block_inactiveuseralert').': '.$report->name);

        $o .= $this->alert_list($report->alertlist, $report->selected, $report->modinfo);

        $table = new html_table();
        $table->head = $report->columns;

        foreach ($report->userdata as $row) {
            $data = array_intersect_key((array)$row, $report->columns);
            if (!empty($data['accessorstate'])) {
                if ($report->type == 'login') {
                    $data['accessorstate'] = userdate($data['accessorstate']);
                } else {
                    $data['accessorstate'] = $data['accessorstate'] != COMPLETION_INCOMPLETE ? get_string('yes') : '';
                }
            }
            if (!empty($data['alerts'])) {
                $alertstr = [];
                foreach ($data['alerts'] as $alert) {
                    $alertstr[] = get_string('alert', 'block_inactiveuseralert').' '.$alert->alertnumber.': '.userdate($alert->timecreated);
                }
                $data['alerts'] = implode(html_writer::empty_tag('br'), $alertstr);
            }
            $table->data[] = $data;
        }

        $o .= $this->output->heading(get_string('alertssent', 'block_inactiveuseralert'), 4);
        for ($i = 1; $i <= 3; $i++) {
            if (!isset($report->alertlist[$report->selected]) || $report->alertlist[$report->selected]->{"alert$i"} == 0) {
                continue;
            }
            $str = get_string('alert', 'block_inactiveuseralert')." {$i}: ";
            $str .= $report->sentcount[$i - 1][1];
            $o .= html_writer::span($str);
            $o .= html_writer::empty_tag('br');
        }

        $pagingbar = new \paging_bar($report->count, $report->page, $report->perpage, $report->url);
        $o .= $this->render($pagingbar);
        $o .= $this->output->box_start('boxaligncenter');
        $o .= html_writer::table($table);
        $o .= $this->output->box_end();

        return $o;
    }

    public function render_alert(\block_inactiveuseralert\output\alert\renderable $overview) {
        $state = $overview->enabled ? 'yes' : 'no';
        $enabledstr = html_writer::span(get_string($state), "enabled-$state");
        $enabled = html_writer::span(get_string('enabled', 'block_inactiveuseralert').": $enabledstr", 'alerthead');

        $name = html_writer::span($overview->name, 'bold');
        $brtag = html_writer::empty_tag('br');
        $o = html_writer::span($name.$brtag.$enabled);
        $o .= $brtag;

        $alertstr = get_string('alert', 'block_inactiveuseralert');
        $format = get_string('strftimedate', 'langconfig');
        $alerts = array();
        $num = 1;
        foreach ($overview->alerts as $alert) {
            $alerttime = userdate($alert[0], $format);
            $alertstat = '';
            if ($alert[0] <= time()) {
                $alertstat = '('.get_string('numbersent', 'block_inactiveuseralert', $alert[1]).')';
            }
            $head = html_writer::span("{$alertstr} {$num}:", 'alerthead');
            if ($alert[0] > 0) {
                $alerts[] = "$head $alerttime $alertstat";
            }
            $num++;
        }
        $o .= html_writer::tag('span', implode($brtag, $alerts));
        $o .= $brtag;
        return $o;
    }

}
