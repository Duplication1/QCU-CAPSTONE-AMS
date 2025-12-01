-- Add created_by column to pc_units table
ALTER TABLE `pc_units` 
ADD COLUMN `created_by` INT(11) DEFAULT NULL AFTER `notes`,
ADD INDEX `idx_created_by` (`created_by`);

-- Optionally, you can set existing records to a default user ID
-- UPDATE `pc_units` SET `created_by` = 1 WHERE `created_by` IS NULL;
