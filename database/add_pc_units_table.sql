-- Create PC Units table
CREATE TABLE IF NOT EXISTS `pc_units` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) DEFAULT NULL,
  `building_id` int(11) DEFAULT NULL,
  `terminal_number` varchar(50) DEFAULT NULL,
  `status` enum('Active','Inactive','Under Maintenance','Retired') NOT NULL DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  KEY `building_id` (`building_id`),
  CONSTRAINT `pc_units_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pc_units_ibfk_2` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add pc_unit_id column to assets table
ALTER TABLE `assets` 
ADD COLUMN `pc_unit_id` int(11) DEFAULT NULL AFTER `room_id`,
ADD KEY `pc_unit_id` (`pc_unit_id`),
ADD CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`pc_unit_id`) REFERENCES `pc_units` (`id`) ON DELETE SET NULL;
