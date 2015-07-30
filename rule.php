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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');

/*
 * @package   quizaccess_reattemptchecker
 * @copyright 2015 Amir Shurrab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * A rule requiring the students have not achieved a pass grade
 */
class quizaccess_reattemptchecker extends quiz_access_rule_base {


    public static function make(quiz $quizobj, $timenow, $canignoretimelimits) {

        if (empty($quizobj->get_quiz()->reattemptchecker)) {
            return null;
        }

        return new self($quizobj, $timenow);
    }

    public static function add_settings_form_fields(
            mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {

        $mform->addElement('text', 'reattemptchecker', get_string("preventpassed", "quizaccess_reattemptchecker"), 'maxlength="3" size="3"');
        $mform->setType('reattemptchecker', PARAM_INT);
        $mform->addHelpButton('reattemptchecker',
                'preventpassed', 'quizaccess_reattemptchecker');
    }

    public static function save_settings($quiz) {
        global $DB;
        if (empty($quiz->reattemptchecker)) {
            $DB->delete_records('quizaccess_reattemptchecker', array('quizid' => $quiz->id));
        } else {
            if ($record = $DB->get_record('quizaccess_reattemptchecker', array('quizid' => $quiz->id))) {
                $record->reattemptchecker = $quiz->reattemptchecker;
                $DB->update_record('quizaccess_reattemptchecker', $record);
            } else {
                $record = new stdClass();
                $record->quizid = $quiz->id;
                $record->reattemptchecker = $quiz->reattemptchecker;
                $DB->insert_record('quizaccess_reattemptchecker', $record);
            }
        }
    }

    public static function get_settings_sql($quizid) {
        return array(
            'reattemptchecker',
            'LEFT JOIN {quizaccess_reattemptchecker} reattemptchecker ON reattemptchecker.quizid = quiz.id',
            array());
    }

    public function prevent_new_attempt($numattempts, $lastattempt) {
        global $DB;

        if ($numattempts == 0) {
            return false;
        }

        // Check if preventonpass is set, and whether the student has passed the minimum passing grade.
        $previousattempts = $DB->get_records_select('quiz_attempts',
                "quiz = :quizid AND userid = :userid AND timefinish > 0 and preview != 1",
                array('quizid' => $this->quiz->id, 'userid' => $lastattempt->userid));

        if (quiz_rescale_grade(quiz_calculate_best_grade($this->quiz, $previousattempts), $this->quiz, false)
                >= $this->quiz->reattemptchecker) {
            return get_string('accessprevented', 'quizaccess_reattemptchecker');
        }

        return false;
    }

    public static function delete_settings($quiz) {
        global $DB;
        $DB->delete_records('quizaccess_reattemptchecker', array('quizid' => $quiz->id));
    }
}
