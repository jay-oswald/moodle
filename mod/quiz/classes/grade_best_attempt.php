<?php 

namespace mod_quiz;
use mod_quiz\task\quiz_calculate_best_attempts_for_quiz;
use core\task\manager;

class grade_best_attempt{
    public static function calculate_quiz($quizid){
        global $DB;

        $sql = "SELECT DISTINCT qa.userid
          FROM {quiz_attempts} qa
         WHERE qa.state = :state AND qa.quiz = :quiz";

        $params = ['state' => 'finished', 'quiz' => $quizid];

        $users = $DB->get_fieldset_sql($sql, $params);

        if(defined('CLI_SCRIPT') && CLI_SCRIPT){
            $totalusers = count($users);
            mtrace("Calculating grades for quiz $quizid for $totalusers users");
        }

        $count = 0;
        foreach($users as $user){
            if(++$count % 1000 === 0 && defined('CLI_SCRIPT') && CLI_SCRIPT){
                mtrace('calculating for user ' . $count);
            }
            self::calculate_quiz_user($quizid, $user);
        }
    }

    public static function calculate_quiz_user($quizid, $userid){
        global $DB;
        

        $sql = "UPDATE {quiz_attempts} qa
            SET
                gradehighest = (
                    CASE
                        WHEN qa.state = 'finished' AND NOT EXISTS (
                            SELECT 1
                            FROM {quiz_attempts} qa2
                            WHERE qa2.quiz   = qa.quiz
                            AND qa2.userid = qa.userid
                            AND qa2.state  = 'finished'
                            AND (
                                    COALESCE(qa2.sumgrades, 0) > COALESCE(qa.sumgrades, 0)
                                OR (
                                        COALESCE(qa2.sumgrades, 0) = COALESCE(qa.sumgrades, 0)
                                        AND qa2.attempt < qa.attempt
                                    )
                                )
                        )
                        THEN 1
                        ELSE 0
                    END
                ),
                attemptfirst = (
                    CASE
                        WHEN qa.state = 'finished' AND NOT EXISTS (
                            SELECT 1
                            FROM {quiz_attempts} qa2
                            WHERE qa2.quiz   = qa.quiz
                            AND qa2.userid = qa.userid
                            AND qa2.state  = 'finished'
                            AND qa2.attempt < qa.attempt
                        )
                        THEN 1
                        ELSE 0
                    END
                ),
                attemptlast = (
                    CASE
                        WHEN qa.state = 'finished' AND NOT EXISTS (
                            SELECT 1
                            FROM {quiz_attempts} qa2
                            WHERE qa2.quiz   = qa.quiz
                            AND qa2.userid = qa.userid
                            AND qa2.state  = 'finished'
                            AND qa2.attempt > qa.attempt
                        )
                        THEN 1
                        ELSE 0
                    END
                )
            WHERE qa.quiz   = :quiz
            AND qa.userid = :userid";

        $params = ['quiz' => $quizid, 'userid' => $userid];

        $DB->execute($sql, $params);
    }

    public static function calculate_all($useadhoc = true){
        global $DB;
        $sql = "SELECT DISTINCT qa.quiz
          FROM {quiz_attempts} qa
         WHERE qa.state = :state";

        $params = ['state' => 'finished'];

        $quizids = $DB->get_fieldset_sql($sql, $params);

        mtrace('Total Quizs that need calculating: ' . count($quizids));

        $task = new quiz_calculate_best_attempts_for_quiz();

        $count = 0;
        foreach($quizids as $quizid){
            if(++$count % 1000 === 0){
                mtrace('Processing quiz ' . $count);
            }
            $task->set_custom_data([
                'id' => $quizid,
            ]);
            if($useadhoc){
                manager::queue_adhoc_task($task);
            } else {
                self::calculate_quiz($quizid);
            }
        }
    }
}