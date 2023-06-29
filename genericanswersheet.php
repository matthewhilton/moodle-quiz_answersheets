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
 * This page prints a answer sheet of a particular quiz attempt.
 *
 * @package   quiz_answersheets
 * @copyright 2023 Matthew Hilton
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quiz_answersheets\report_display_options;
use quiz_answersheets\utils;

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__. '/../../attemptlib.php');
require_once(__DIR__.'/../../locallib.php');

$quizid = required_param('quizid', PARAM_INT);

// TODO require login
// require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
// require_capability('quiz/answersheets:view', context_module::instance($attemptobj->get_cmid()));
// require_capability('quiz/answersheets:viewrightanswers', context_module::instance($attemptobj->get_cmid()));


// Make a temporary generic attempt object.
global $DB;
$quiz = $DB->get_record('quiz', ['id' => $quizid]);
$cm = get_coursemodule_from_instance('quiz', $quizid);
$course = get_course($cm->course);

require_login($course, false, $cm);

$quizobj = new quiz($quiz, $cm, $course);

global $PAGE, $USER;
$renderer = $PAGE->get_renderer('quiz_answersheets');

// Start the preview attempt.
$quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
$quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

$timenow = time();

$attempt = null;

// Find any previous unfinished preview attempts.
$prevattempts = quiz_get_user_attempts($quizobj->get_quizid(), $USER->id, 'unfinished', true);

if (!empty($prevattempts)) {
    $attempt = array_pop($prevattempts);
} else {
    $attemptnumber = count(quiz_get_user_attempts($quizobj->get_quizid(), $USER->id, 'all', true)) + 1;
    $attempt = quiz_create_attempt($quizobj, $attemptnumber, false, $timenow, true, $USER->id);
    quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
    quiz_attempt_save_started($quizobj, $quba, $attempt);
}

$attemptobj = new quiz_attempt($attempt, $quiz, $cm, $course);

$reportoptions = new report_display_options('answersheets', $attemptobj->get_quiz(),
        $attemptobj->get_cm(), $attemptobj->get_course());

$pagetitle = get_string('answer_sheet_title', 'quiz_answersheets', ['courseshortname' => $course->shortname, 'quizname' => $quiz->name]);
$pagenav = get_string('answer_sheet_label', 'quiz_answersheets');
$sheettype = get_string('page_type_answer', 'quiz_answersheets');

utils::set_page_navigation($pagenav);

$PAGE->set_pagelayout('popup');
$PAGE->set_title($pagetitle);

// TODO this is hacky!
$PAGE->set_url('/mod/quiz/report/answersheets/genericanswersheet.php', ['rightanswer' => 1, 'attempt' => $attempt->id, 'quizid' => $quizid]);
echo $OUTPUT->header();

echo $renderer->render_attempt_navigation();
echo $renderer->render_attempt_sheet([], $attemptobj, $sheettype, $reportoptions);

// TODO stop preview attempt

echo $OUTPUT->footer();
