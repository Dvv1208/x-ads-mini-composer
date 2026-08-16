CREATE TABLE IF NOT EXISTS `user` (
    `entity_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_id` VARCHAR(32) NOT NULL,
    `user_id` VARCHAR(32) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`entity_id`),
    UNIQUE KEY `uniq_account_user` (`account_id`, `user_id`),
    KEY `idx_account_id` (`account_id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `user` (`account_id`, `user_id`)
VALUES ('18ce55nu7l7', '1855582736')
ON DUPLICATE KEY UPDATE
    `account_id` = VALUES(`account_id`),
    `user_id` = VALUES(`user_id`);
