-- Add login security columns to users table
-- Run this migration to enable failed login attempt tracking

ALTER TABLE `users` 
ADD COLUMN `failed_login_attempts` INT DEFAULT 0 AFTER `last_login`,
ADD COLUMN `account_locked_until` DATETIME DEFAULT NULL AFTER `failed_login_attempts`;

-- Index for faster lookups
ALTER TABLE `users` ADD INDEX `idx_account_locked` (`account_locked_until`);
