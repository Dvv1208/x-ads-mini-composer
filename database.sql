CREATE TABLE IF NOT EXISTS `admin_user` (
    `entity_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(64) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'editor', 'user') NOT NULL DEFAULT 'user',
    `is_active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `failed_attempts` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until` DATETIME NULL,
    `last_login_at` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`entity_id`),
    UNIQUE KEY `uniq_admin_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `admin_user`
    MODIFY `role` ENUM('admin', 'editor', 'user') NOT NULL DEFAULT 'user';

UPDATE `admin_user`
SET `username` = 'dvv1208',
    `password_hash` = '$2y$10$VySYBtHciuyBEM9XRP3ik.IpSR9vhA6.FYlKAPqmVXYK8wXzQVPie',
    `role` = 'admin',
    `is_active` = 1,
    `failed_attempts` = 0,
    `locked_until` = NULL
WHERE `username` = 'admin';

INSERT INTO `admin_user` (`username`, `password_hash`, `role`, `is_active`)
VALUES ('dvv1208', '$2y$10$VySYBtHciuyBEM9XRP3ik.IpSR9vhA6.FYlKAPqmVXYK8wXzQVPie', 'admin', 1)
ON DUPLICATE KEY UPDATE
    `password_hash` = VALUES(`password_hash`),
    `role` = VALUES(`role`),
    `is_active` = VALUES(`is_active`),
    `failed_attempts` = 0,
    `locked_until` = NULL;

INSERT INTO `admin_user` (`username`, `password_hash`, `role`, `is_active`)
VALUES ('ken', '$2y$10$fNpZPr8PLTBdXGEVh372iOf3MO6UTlT3rRJtdRe7VgJQzopPrCp.S', 'editor', 1)
ON DUPLICATE KEY UPDATE
    `password_hash` = VALUES(`password_hash`),
    `role` = VALUES(`role`),
    `is_active` = VALUES(`is_active`),
    `failed_attempts` = 0,
    `locked_until` = NULL;

CREATE TABLE IF NOT EXISTS `user` (
    `entity_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_id` VARCHAR(32) NOT NULL,
    `user_id` VARCHAR(32) NOT NULL,
    `x_cookie` TEXT NULL,
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
