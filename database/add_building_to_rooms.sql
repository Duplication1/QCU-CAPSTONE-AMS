-- Add building_id foreign key to rooms table
ALTER TABLE `rooms` 
ADD COLUMN `building_id` INT(11) DEFAULT NULL COMMENT 'Foreign key to buildings table' AFTER `id`,
ADD KEY `fk_rooms_building` (`building_id`),
ADD CONSTRAINT `fk_rooms_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
