-- Add e_signature column to users table
-- Run this SQL script to update your database

ALTER TABLE `users` 
ADD COLUMN `e_signature` VARCHAR(255) NULL DEFAULT NULL AFTER `last_login`;

-- Update the table comment
ALTER TABLE `users` COMMENT = 'User accounts with e-signature support';
