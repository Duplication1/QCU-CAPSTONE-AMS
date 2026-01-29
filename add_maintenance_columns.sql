-- Add maintenance columns to rooms table
ALTER TABLE `rooms`
ADD COLUMN `next_maintenance_date` DATE DEFAULT NULL AFTER `name`,
ADD COLUMN `last_maintenance_date` DATE DEFAULT NULL AFTER `next_maintenance_date`,
ADD COLUMN `maintenance_frequency_days` INT DEFAULT 90 COMMENT 'Days between maintenance (default 90 days)' AFTER `last_maintenance_date`,
ADD COLUMN `maintenance_notes` TEXT DEFAULT NULL AFTER `maintenance_frequency_days`;

-- Optional: Update existing rooms with sample maintenance dates
-- Uncomment the lines below to add sample data

-- UPDATE `rooms` SET 
--     `next_maintenance_date` = DATE_ADD(CURDATE(), INTERVAL 5 DAY),
--     `last_maintenance_date` = DATE_SUB(CURDATE(), INTERVAL 85 DAY),
--     `maintenance_frequency_days` = 90
-- WHERE `id` = 6;

-- UPDATE `rooms` SET 
--     `next_maintenance_date` = DATE_SUB(CURDATE(), INTERVAL 3 DAY),
--     `last_maintenance_date` = DATE_SUB(CURDATE(), INTERVAL 93 DAY),
--     `maintenance_frequency_days` = 90
-- WHERE `id` = 7;

-- UPDATE `rooms` SET 
--     `next_maintenance_date` = DATE_ADD(CURDATE(), INTERVAL 15 DAY),
--     `last_maintenance_date` = DATE_SUB(CURDATE(), INTERVAL 75 DAY),
--     `maintenance_frequency_days` = 90
-- WHERE `id` IN (8, 10);
