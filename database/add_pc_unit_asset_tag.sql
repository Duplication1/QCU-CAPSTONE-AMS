-- Add asset_tag column to pc_units table
ALTER TABLE `pc_units` 
ADD COLUMN `asset_tag` VARCHAR(100) DEFAULT NULL UNIQUE AFTER `terminal_number`,
ADD INDEX `idx_asset_tag` (`asset_tag`);

-- Update existing pc_units with asset tags (format: DATE-ROOM-TH-01)
-- Example: 12-01-2025-IK501-TH-01
UPDATE `pc_units` pc
LEFT JOIN rooms r ON pc.room_id = r.id
SET pc.`asset_tag` = CONCAT(
    DATE_FORMAT(COALESCE(pc.created_at, NOW()), '%m-%d-%Y'),
    '-',
    r.name,
    '-TH-',
    LPAD(pc.id, 2, '0')
)
WHERE pc.`asset_tag` IS NULL;
