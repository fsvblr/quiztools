CREATE TABLE IF NOT EXISTS `#__quiztools_questions_truefalse` (
    `id` int NOT NULL AUTO_INCREMENT,
    `question_id` int NOT NULL DEFAULT 0,
    `correct_answer` tinyint NOT NULL DEFAULT 0 COMMENT '0=No, 1=Yes',
    PRIMARY KEY (`id`),
    KEY `idx_questionid` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `#__quiztools_results_questions_truefalse` (
    `id` int NOT NULL AUTO_INCREMENT,
    `results_question_id` int NOT NULL DEFAULT 0,
    `user_answer` tinyint NOT NULL DEFAULT 0 COMMENT '0=No, 1=Yes',
    PRIMARY KEY (`id`),
    KEY `idx_rqid` (`results_question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

