-- Asset History Table
-- This table tracks all changes made to assets including status changes, location moves, condition updates, etc.

CREATE TABLE IF NOT EXISTS `asset_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL COMMENT 'Foreign key to assets table',
  `action_type` enum('Created','Updated','Status Changed','Location Changed','Assigned','Unassigned','Borrowed','Returned','Maintenance','Condition Changed','Disposed','Restored','Archived','QR Generated') NOT NULL COMMENT 'Type of action performed',
  `field_changed` varchar(100) DEFAULT NULL COMMENT 'Specific field that was changed',
  `old_value` text DEFAULT NULL COMMENT 'Previous value before change',
  `new_value` text DEFAULT NULL COMMENT 'New value after change',
  `description` text DEFAULT NULL COMMENT 'Detailed description of the change',
  `performed_by` int(11) DEFAULT NULL COMMENT 'User ID who performed the action',
  `performed_by_name` varchar(255) DEFAULT NULL COMMENT 'Name of user who performed the action',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of the user',
  `user_agent` text DEFAULT NULL COMMENT 'Browser/device information',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'When the action occurred',
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `action_type` (`action_type`),
  KEY `performed_by` (`performed_by`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `asset_history_asset_fk` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks all historical changes to assets';

-- Insert initial history records for existing assets (optional)
INSERT INTO `asset_history` (`asset_id`, `action_type`, `description`, `performed_by`, `created_at`)
SELECT 
    `id`,
    'Created',
    CONCAT('Asset ', `asset_tag`, ' - ', `asset_name`, ' created'),
    `created_by`,
    `created_at`
FROM `assets`
WHERE NOT EXISTS (
    SELECT 1 FROM `asset_history` WHERE `asset_history`.`asset_id` = `assets`.`id`
);
