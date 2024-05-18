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
 * Table class that contains the list of user their activity reports.
 *
 * @package    report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_lmsace_reports\widgets;

use report_lmsace_reports\output\widgets_info;

/**
 * Clas user My activities.
 */
class usermyactivitieswidget extends widgets_info {

    /**
     * @var string $context
     */
    public $context = "user";

    /**
     * @var array $records
     */
    private $records;

    /**
     * @var object $user.
     */
    public $user;

    /**
     * Implemented the constructor.
     * @param int $userid
     */
    public function __construct($userid) {
        global $DB;
        parent::__construct();
        $this->user = $DB->get_record('user', ['id' => $userid]);
        $this->param_sql();
    }

    /**
     * Get chart type.
     */
    public function get_charttype() {
        return null;
    }

    /**
     * Report is chart or not.
     * @return bool
     */
    public function is_chart() {
        return false;
    }

    /**
     * Get the cache key.
     * @return string
     */
    public function get_cache_key() {
        return "u_" . $this->user->id . "_coursemyactivities";
    }

    /**
     * Load the data.
     */
    public function param_sql() {
        global $DB;
        if ($this->cache == null) {
            $this->load_cache();
        }
        if (!$this->cache->get($this->get_cache_key())) {
            $sql = "SELECT count(cm.id) AS Activities, count(cp.id) AS Completions,
                count(case when cm.completionexpected != 0 AND cm.completionexpected < :currenttime AND NOT EXISTS
                (SELECT * FROM {course_modules_completion} cpp WHERE cpp.coursemoduleid = cm.id
                AND cpp.userid = ue.userid AND cp.completionstate = 1 ) then 1 else NULL end) As dues
                FROM {enrol} e
                JOIN {user_enrolments} ue ON e.id = ue.enrolid
                JOIN {course} c ON e.courseid = c.id
                JOIN {course_modules} cm ON cm.course = c.id
                JOIN {modules} m ON m.id = cm.module
                LEFT JOIN {course_modules_completion} cp ON cp.coursemoduleid = cm.id
                    AND cp.completionstate = 1 AND cp.userid = ue.userid
                WHERE ue.userid = :userid AND cm.deletioninprogress = 0";
            $params = [
                'userid' => $this->user->id,
                'currenttime' => time(),
            ];
            $records = $DB->get_record_sql($sql, $params);
            $this->cache->set($this->get_cache_key(), $records);
        }
        $this->records = $this->cache->get($this->get_cache_key());
    }

    /**
     * Get chart data.
     * @return string
     */
    public function get_data() {
        global $OUTPUT, $PAGE;
        return $OUTPUT->render_from_template('report_lmsace_reports/widgets/usermyactivities', $this->records);
    }
}
