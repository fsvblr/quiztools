CREATE TABLE IF NOT EXISTS `#__quiztools_quizzes` (
    `id` int NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `alias` varchar(255) NOT NULL,
    `catid` int NOT NULL DEFAULT 0,
    `description` text NOT NULL,
    `type_access` tinyint NOT NULL DEFAULT 0,
    `certificate_id` int NOT NULL DEFAULT 0,
    `quiz_autostart` tinyint NOT NULL DEFAULT 0,
    `allow_continue` tinyint NOT NULL DEFAULT 1,
    `timer_show` tinyint NOT NULL DEFAULT 0,
    `timer_style` tinyint NOT NULL DEFAULT 0,
    `limit_time` int NOT NULL DEFAULT 0,
    `limit_attempts` int NOT NULL DEFAULT 0,
    `attempts_reset_period` int NOT NULL DEFAULT 0,
    `attempts_reset_next_day` tinyint NOT NULL DEFAULT 0,
    `total_score` DOUBLE(10,2) NOT NULL DEFAULT 0,
    `passing_score` DOUBLE(5,2) NOT NULL DEFAULT 0,
    `questions_on_page` tinyint NOT NULL DEFAULT 0,
    `shuffle_questions` tinyint NOT NULL DEFAULT 0,
    `skip_questions` tinyint NOT NULL DEFAULT 0,
    `enable_prev_button` tinyint NOT NULL DEFAULT 0,
    `question_number` tinyint NOT NULL DEFAULT 0,
    `question_points` tinyint NOT NULL DEFAULT 0,
    `question_pool` varchar(64) NOT NULL,
    `question_pool_randon_qty` int NOT NULL DEFAULT 1,
    `question_pool_categories` text NOT NULL,
    `redirect_after_finish` tinyint NOT NULL DEFAULT 0,
    `redirect_after_finish_link` varchar(255) NOT NULL,
    `redirect_after_finish_delay` int NOT NULL DEFAULT 0,
    `results_by_categories` tinyint NOT NULL DEFAULT 0,
    `results_with_questions` tinyint NOT NULL DEFAULT 0,
    `results_pdf` tinyint NOT NULL DEFAULT 0,
    `results_certificate` tinyint NOT NULL DEFAULT 0,
    `feedback_question` tinyint NOT NULL DEFAULT 0,
    `feedback_question_pdf` tinyint NOT NULL DEFAULT 0,
    `feedback_question_final` tinyint NOT NULL DEFAULT 0,
    `feedback_msg_right` text NOT NULL,
    `feedback_msg_wrong` text NOT NULL,
    `feedback_final_msg_options` varchar(64) NOT NULL DEFAULT 'hide',
    `feedback_final_msg` text NOT NULL,
    `feedback_final_msg_default_passed`  text NOT NULL,
    `feedback_final_msg_default_unpassed`  text NOT NULL,
    `state` tinyint NOT NULL DEFAULT 1,
    `asset_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'FK to the #__assets table.',
    `ordering` int NOT NULL DEFAULT 0,
    `language` varchar(255) NOT NULL DEFAULT '*',
    `created` datetime NOT NULL,
    `created_by` int unsigned NOT NULL DEFAULT 0,
    `modified` datetime NOT NULL,
    `modified_by` int unsigned NOT NULL DEFAULT 0,
    `checked_out` int unsigned DEFAULT NULL,
    `checked_out_time` datetime DEFAULT NULL,
    `access` int unsigned NOT NULL DEFAULT 0,
    `metatitle` text NOT NULL,
    `metakey` text NOT NULL,
    `metadesc` text NOT NULL,
    `params` text NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_access` (`access`),
    KEY `idx_checkout` (`checked_out`),
    KEY `idx_state` (`state`),
    KEY `idx_catid` (`catid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `#__quiztools_questions` (
    `id` int NOT NULL AUTO_INCREMENT,
    `quiz_id` int NOT NULL DEFAULT 0,
    `catid` int NOT NULL DEFAULT 0,
    `type` varchar(64) NOT NULL,
    `text` text NOT NULL,
    `attempts` int NOT NULL DEFAULT 0,
    `points` DOUBLE(10,2) NOT NULL DEFAULT 0,
    `penalty` int NOT NULL DEFAULT 0,
    `feedback` tinyint NOT NULL DEFAULT 0,
    `feedback_msg_right` text NOT NULL,
    `feedback_msg_wrong` text NOT NULL,
    `state` tinyint NOT NULL DEFAULT 1,
    `ordering` int NOT NULL DEFAULT 0,
    `created` datetime NOT NULL,
    `created_by` int unsigned NOT NULL DEFAULT 0,
    `modified` datetime NOT NULL,
    `modified_by` int unsigned NOT NULL DEFAULT 0,
    `checked_out` int unsigned DEFAULT NULL,
    `checked_out_time` datetime DEFAULT NULL,
    `params` text NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_state` (`state`),
    KEY `idx_catid` (`catid`),
    KEY `idx_quizid` (`quiz_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `#__quiztools_certificates` (
    `id` int NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `file` varchar(255) NOT NULL,
    `fields` text NOT NULL,
    `state` tinyint NOT NULL DEFAULT 1,
    `created` datetime NOT NULL,
    `created_by` int unsigned NOT NULL DEFAULT 0,
    `modified` datetime NOT NULL,
    `modified_by` int unsigned NOT NULL DEFAULT 0,
    `checked_out` int unsigned DEFAULT NULL,
    `checked_out_time` datetime DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `#__quiztools_results_quizzes` (
    `id` int NOT NULL AUTO_INCREMENT,
    `quiz_id` int NOT NULL DEFAULT 0,
    `user_id` int NOT NULL DEFAULT 0,
    `total_score` DOUBLE(10,2) NOT NULL DEFAULT 0,
    `passing_score` DOUBLE(10,2) NOT NULL DEFAULT 0,
    `sum_points_received` DOUBLE(10,2) NOT NULL DEFAULT 0,
    `passed` tinyint NOT NULL DEFAULT 0,
    `finished` tinyint NOT NULL DEFAULT 0,
    `start_datetime` datetime DEFAULT NULL,
    `sum_time_spent` int NOT NULL DEFAULT 0,
    `unique_id` varchar(255) NOT NULL,
    `params` text NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_quizid` (`quiz_id`),
    KEY `idx_userid` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `#__quiztools_results_users` (
    `id` int NOT NULL AUTO_INCREMENT,
    `result_quiz_id` int NOT NULL DEFAULT 0,
    `user_id` int NOT NULL DEFAULT 0,
    `user_name` varchar(255) NOT NULL,
    `user_surname` varchar(255) NOT NULL,
    `user_email` varchar(255) NOT NULL,
    `user_data` text NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_rqid` (`result_quiz_id`),
    KEY `idx_userid` (`user_id`),
    KEY `idx_useremail` (`user_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `#__quiztools_results_questions` (
    `id` int NOT NULL AUTO_INCREMENT,
    `result_quiz_id` int NOT NULL DEFAULT 0,
    `question_id` int NOT NULL DEFAULT 0,
    `total_points` DOUBLE(10,2) NOT NULL DEFAULT 0,
    `points_received` DOUBLE(10,2) NOT NULL DEFAULT 0,
    `attempts` int NOT NULL DEFAULT 0,
    `is_correct` tinyint NOT NULL DEFAULT 0,
    `response_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_resultquizid` (`result_quiz_id`),
    KEY `idx_questionid` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `#__quiztools_results_chains` (
    `quiz_id` int NOT NULL DEFAULT 0,
    `user_id` int NOT NULL DEFAULT 0,
    `chain` text NOT NULL,
    `unique_id` varchar(255) NOT NULL,
    `result_quiz_id` int NOT NULL DEFAULT 0,
    KEY `idx_uniqueid` (`unique_id`),
    KEY `idx_resultquizid` (`result_quiz_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;














