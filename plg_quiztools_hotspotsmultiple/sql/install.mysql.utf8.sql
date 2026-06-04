CREATE TABLE IF NOT EXISTS `#__quiztools_questions_hotspotsmultiple` (
    `id` int NOT NULL AUTO_INCREMENT,
    `question_id` int NOT NULL DEFAULT 0,
    `check_order` tinyint NOT NULL DEFAULT 0,
    `image` text NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_questionid` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `#__quiztools_questions_hotspotsmultiple_options` (
    `id` int NOT NULL AUTO_INCREMENT,
    `question_id` int NOT NULL DEFAULT 0,
    `coordinates` text NOT NULL,
    `color` text NOT NULL,
    `points` DOUBLE(10,2) NOT NULL DEFAULT 0,
    `ordering` int NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_questionid` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `#__quiztools_results_questions_hotspotsmultiple` (
    `id` int NOT NULL AUTO_INCREMENT,
    `results_question_id` int NOT NULL DEFAULT 0,
    `answer_coordinates` text NOT NULL,
    `answer_ordering` int NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_rqid` (`results_question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

