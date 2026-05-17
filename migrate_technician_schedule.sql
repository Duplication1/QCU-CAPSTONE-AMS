-- Add allowed_login_days column to users table for Technician schedule restriction
-- This column stores a comma-separated list of allowed days: Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday

ALTER TABLE `users` 
ADD COLUMN `allowed_login_days` VARCHAR(255) DEFAULT NULL COMMENT 'Comma-separated list of allowed login days for Technicians (e.g., Monday,Wednesday,Friday)';

-- Update existing technicians to allow all days by default
UPDATE `users` 
SET `allowed_login_days` = 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday' 
WHERE `role` = 'Technician';
