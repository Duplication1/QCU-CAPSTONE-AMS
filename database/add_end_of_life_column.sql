-- Add end_of_life column to assets table
ALTER TABLE `assets` 
ADD COLUMN `end_of_life` DATE DEFAULT NULL COMMENT 'Expected end of life date for the asset' 
AFTER `warranty_expiry`;
