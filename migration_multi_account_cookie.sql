-- Run once on an existing database.
ALTER TABLE `user` ADD COLUMN `x_cookie` TEXT NULL AFTER `user_id`;

-- After migration, open Admin and edit each account to paste its own X Cookie.
