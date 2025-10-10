CREATE TABLE IF NOT EXISTS `#__quiztools_questions_mchoice` (
    `id` int NOT NULL AUTO_INCREMENT,
    `question_id` int NOT NULL DEFAULT 0,
    `shuffle_answers` tinyint NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_questionid` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `#__quiztools_questions_mchoice_options` (
    `id` int NOT NULL AUTO_INCREMENT,
    `question_id` int NOT NULL DEFAULT 0,
    `option` text NOT NULL,
    `is_correct` tinyint NOT NULL DEFAULT 0,
    `points` DOUBLE(10,2) NOT NULL DEFAULT 0,
    `ordering` int NOT NULL DEFAULT 0,
    `feedback_msg` text NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_questionid` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `#__quiztools_results_questions_mchoice` (
    `id` int NOT NULL AUTO_INCREMENT,
    `results_question_id` int NOT NULL DEFAULT 0,
    `option_id` int NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_rqid` (`results_question_id`),
    KEY `idx_optionid` (`option_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

