-- Migration: Change e_signature column to LONGTEXT for Base64 storage
-- Run this in phpMyAdmin or MySQL command line

-- Change the column type to LONGTEXT (can store up to 4GB of text)
ALTER TABLE `users` 
MODIFY COLUMN `e_signature` LONGTEXT NULL;

-- Optional: Clear old file-based signatures (they won't work anymore)
-- Uncomment the next line if you want to force everyone to re-upload
-- UPDATE `users` SET `e_signature` = NULL WHERE `e_signature` NOT LIKE 'data:image/%';

-- Verify the change
DESCRIBE `users`;
