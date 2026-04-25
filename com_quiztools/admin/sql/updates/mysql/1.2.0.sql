CREATE TABLE IF NOT EXISTS `#__quiztools_subscriptions` (
    `id` int NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `payment_method` varchar(255) NOT NULL DEFAULT 'manual',
    `product_id` int unsigned NOT NULL DEFAULT 0,
    `users_max` int unsigned NOT NULL DEFAULT 1,
    `type` varchar(32) NOT NULL DEFAULT 'quiz',
    `quiz_id` int unsigned NOT NULL DEFAULT 0,
    `lpath_id` int unsigned NOT NULL DEFAULT 0,
    `access_type` varchar(32) NOT NULL DEFAULT 'days',
    `access_days` int unsigned NOT NULL DEFAULT 0,
    `access_from` datetime NOT NULL DEFAULT (CURRENT_TIMESTAMP()),
    `access_to` datetime NOT NULL DEFAULT (CURRENT_TIMESTAMP()),
    `attempts` int NOT NULL DEFAULT 0,
    `state` tinyint NOT NULL DEFAULT 1,
    `ordering` int NOT NULL DEFAULT 0,
    `asset_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'FK to the #__assets table.',
    `created` datetime NOT NULL,
    `created_by` int unsigned NOT NULL DEFAULT 0,
    `modified` datetime NOT NULL,
    `modified_by` int unsigned NOT NULL DEFAULT 0,
    `checked_out` int unsigned DEFAULT NULL,
    `checked_out_time` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_payment_product` (`payment_method`,`product_id`),
    KEY `idx_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `#__quiztools_orders` (
    `id` int NOT NULL AUTO_INCREMENT,
    `status` varchar(32) NOT NULL DEFAULT 'P',
    `user_id` int NOT NULL DEFAULT 0,
    `subscription_id` int NOT NULL DEFAULT 0,
    `users_used` int NOT NULL DEFAULT 1,
    `start_datetime` datetime DEFAULT NULL,
    `end_datetime` datetime DEFAULT NULL,
    `attempts_max` int NOT NULL DEFAULT 0,
    `store_type` varchar(32) NOT NULL DEFAULT 'manual',
    `store_order_id` int NOT NULL DEFAULT 0,
    `store_product_id` int NOT NULL DEFAULT 0,
    `reActivated` datetime DEFAULT NULL,
    `asset_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'FK to the #__assets table.',
    `created` datetime NOT NULL,
    `created_by` int unsigned NOT NULL DEFAULT 0,
    `modified` datetime NOT NULL,
    `modified_by` int unsigned NOT NULL DEFAULT 0,
    `checked_out` int unsigned DEFAULT NULL,
    `checked_out_time` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_subscription` (`subscription_id`),
    KEY `idx_store_order` (`store_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `#__quiztools_order_users` (
    `id` int NOT NULL AUTO_INCREMENT,
    `order_id` int NOT NULL DEFAULT 0 COMMENT 'FK to the #__quiztools_orders table.',
    `parent_user_id` int NOT NULL DEFAULT 0,
    `user_id` int NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_order` (`order_id`),
    KEY `idx_parentuser` (`parent_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `#__quiztools_results_quizzes` ADD COLUMN `order_id` INT NOT NULL DEFAULT '0' AFTER `finished`;
ALTER TABLE `#__quiztools_results_quizzes` ADD KEY `idx_user_order` (`user_id`,`order_id`);

ALTER TABLE `#__quiztools_lpaths_users` ADD COLUMN `result_quiz_id` INT NOT NULL DEFAULT '0' AFTER `type_id`;
ALTER TABLE `#__quiztools_lpaths_users` ADD KEY `idx_resultQuizId` (`result_quiz_id`);

ALTER TABLE `#__quiztools_lpaths_users` ADD COLUMN `order_id` INT NOT NULL DEFAULT '0' AFTER `result_quiz_id`;
ALTER TABLE `#__quiztools_lpaths_users` ADD KEY `idx_user_order` (`user_id`,`order_id`);

