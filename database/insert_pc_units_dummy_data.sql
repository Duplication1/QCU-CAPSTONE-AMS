-- Insert dummy PC Units data for testing
-- Make sure you have a room with id=5 (IK501) or adjust the room_id accordingly

-- Insert PC Units
INSERT INTO `pc_units` (`id`, `room_id`, `building_id`, `terminal_number`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 5, 1, 'PC-01', 'Active', 'Workstation 01 - Intel Core i7-10700, 16GB DDR4, 512GB SSD - Healthy status', '2025-11-23 14:30:00', '2025-11-23 14:30:00'),
(2, 5, 1, 'PC-02', 'Active', 'Workstation 02 - Intel Core i7-10700, 16GB DDR4, 512GB SSD - Warning: High CPU temperature', '2025-11-23 14:28:00', '2025-11-23 14:28:00'),
(3, 5, 1, 'PC-03', 'Active', 'Workstation 03 - Intel Core i5-9400, 8GB DDR4, 256GB SSD - Healthy status', '2025-11-23 14:32:00', '2025-11-23 14:32:00'),
(4, 5, 1, 'PC-04', 'Under Maintenance', 'Workstation 04 - Intel Core i7-10700, 16GB DDR4, 512GB SSD - Critical: Motherboard issue', '2025-11-22 10:15:00', '2025-11-23 14:00:00'),
(5, 5, 1, 'PC-05', 'Active', 'Workstation 05 - Intel Core i5-9400, 8GB DDR4, 256GB SSD - Healthy status', '2025-11-23 14:29:00', '2025-11-23 14:29:00');

-- Note: If you already have PC units in the table, you may need to adjust the IDs
-- or use UPDATE statements instead of INSERT.
-- To update existing records, uncomment and modify as needed:

-- UPDATE `pc_units` SET 
--     `terminal_number` = 'PC-01',
--     `status` = 'Active',
--     `notes` = 'Workstation 01 - Intel Core i7-10700, 16GB DDR4, 512GB SSD - Healthy status'
-- WHERE `id` = 1;
