<?php

namespace mod_quiz\task;

use core\task\adhoc_task;

class quiz_calculate_best_attempts_for_quiz extends adhoc_task {
    
    /**
     * Execute the task.
     */
    public function execute() {
        $quizid = $this->get_custom_data()->id;
        \mod_quiz\grade_best_attempt::calculate_quiz($quizid);
    }
}
