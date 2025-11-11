CREATE TABLE IF NOT EXISTS `#__quiztools_lpaths` (
    `id` int NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `alias` varchar(255) NOT NULL,
    `catid` int NOT NULL DEFAULT 0,
    `description` text NOT NULL,
    `type_access` tinyint NOT NULL DEFAULT 0,
    `show_progressbar` tinyint NOT NULL DEFAULT 0,
    `lpath_items` text NOT NULL,
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


CREATE TABLE IF NOT EXISTS `#__quiztools_lpaths_users` (
    `id` int NOT NULL AUTO_INCREMENT,
    `user_id` int NOT NULL DEFAULT 0,
    `lpath_id` int NOT NULL DEFAULT 0,
    `type` varchar(4) NOT NULL,
    `type_id` int NOT NULL DEFAULT 0,
    `unique_id` varchar(255) NOT NULL,
    `passed` tinyint NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `user_lpath` (`user_id`,`lpath_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

