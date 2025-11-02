-- Create assets table for Asset Management System
-- Run this SQL script to add the assets table to your database

CREATE TABLE `assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_tag` varchar(50) NOT NULL COMMENT 'Unique asset identification number',
  `asset_name` varchar(255) NOT NULL COMMENT 'Name/model of the asset',
  `asset_type` enum('Hardware','Software','Furniture','Equipment','Peripheral','Network Device','Other') NOT NULL DEFAULT 'Hardware',
  `category` varchar(100) DEFAULT NULL COMMENT 'Subcategory (e.g., Desktop, Laptop, Printer)',
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `specifications` text DEFAULT NULL COMMENT 'Technical specifications or description',
  
  -- Location Information
  `room_id` int(11) DEFAULT NULL COMMENT 'Foreign key to rooms table',
  `location` varchar(255) DEFAULT NULL COMMENT 'Specific location within room',
  `terminal_number` varchar(50) DEFAULT NULL COMMENT 'Terminal/workstation number',
  
  -- Financial Information
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(10,2) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  
  -- Status Information
  `status` enum('Active','In Use','Available','Under Maintenance','Retired','Disposed','Lost','Damaged') NOT NULL DEFAULT 'Available',
  `condition` enum('Excellent','Good','Fair','Poor','Non-Functional') NOT NULL DEFAULT 'Good',
  `is_borrowable` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = Can be borrowed, 0 = Cannot be borrowed',
  
  -- Assignment Information
  `assigned_to` int(11) DEFAULT NULL COMMENT 'User ID of person assigned to this asset',
  `assigned_date` datetime DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL COMMENT 'User ID of person who made the assignment',
  
  -- Maintenance Information
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `maintenance_notes` text DEFAULT NULL,
  
  -- Additional Information
  `notes` text DEFAULT NULL COMMENT 'General notes about the asset',
  `qr_code` varchar(255) DEFAULT NULL COMMENT 'Path to QR code image',
  `image` varchar(255) DEFAULT NULL COMMENT 'Path to asset image',
  
  -- Audit Fields
  `created_by` int(11) DEFAULT NULL COMMENT 'User ID who created the record',
  `updated_by` int(11) DEFAULT NULL COMMENT 'User ID who last updated the record',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_tag` (`asset_tag`),
  KEY `idx_asset_type` (`asset_type`),
  KEY `idx_status` (`status`),
  KEY `idx_is_borrowable` (`is_borrowable`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_serial_number` (`serial_number`),
  
  -- Foreign Key Constraints
  CONSTRAINT `fk_assets_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_assets_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_assets_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_assets_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_assets_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Asset inventory management table';

-- Create borrowing history/transactions table
CREATE TABLE `asset_borrowing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `borrower_id` int(11) NOT NULL COMMENT 'User ID of borrower',
  `borrower_name` varchar(150) NOT NULL,
  `borrowed_date` datetime NOT NULL,
  `expected_return_date` datetime DEFAULT NULL,
  `actual_return_date` datetime DEFAULT NULL,
  `purpose` text DEFAULT NULL COMMENT 'Purpose of borrowing',
  `status` enum('Pending','Approved','Borrowed','Returned','Overdue','Cancelled') NOT NULL DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL COMMENT 'User ID who approved the request',
  `approved_date` datetime DEFAULT NULL,
  `returned_condition` enum('Excellent','Good','Fair','Poor','Damaged') DEFAULT NULL,
  `return_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  PRIMARY KEY (`id`),
  KEY `idx_asset_id` (`asset_id`),
  KEY `idx_borrower_id` (`borrower_id`),
  KEY `idx_status` (`status`),
  KEY `idx_borrowed_date` (`borrowed_date`),
  
  CONSTRAINT `fk_borrowing_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_borrowing_borrower` FOREIGN KEY (`borrower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_borrowing_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Asset borrowing transactions and history';

-- Create asset maintenance log table
CREATE TABLE `asset_maintenance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `maintenance_type` enum('Preventive','Corrective','Emergency','Inspection','Upgrade') NOT NULL,
  `maintenance_date` date NOT NULL,
  `performed_by` int(11) DEFAULT NULL COMMENT 'Technician/User ID',
  `description` text NOT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `next_maintenance` date DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  PRIMARY KEY (`id`),
  KEY `idx_asset_id` (`asset_id`),
  KEY `idx_maintenance_date` (`maintenance_date`),
  KEY `idx_performed_by` (`performed_by`),
  
  CONSTRAINT `fk_maintenance_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_maintenance_performed_by` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Asset maintenance history and schedule';

-- Insert sample assets data
INSERT INTO `assets` (`asset_tag`, `asset_name`, `asset_type`, `category`, `brand`, `model`, `serial_number`, `specifications`, `room_id`, `terminal_number`, `status`, `condition`, `is_borrowable`, `purchase_date`, `purchase_cost`) VALUES
('COMP-IK501-001', 'Desktop Computer', 'Hardware', 'Desktop', 'Dell', 'OptiPlex 7090', 'SN123456789', 'Intel i7, 16GB RAM, 512GB SSD', 1, '1', 'In Use', 'Good', 0, '2024-01-15', 45000.00),
('COMP-IK501-002', 'Desktop Computer', 'Hardware', 'Desktop', 'Dell', 'OptiPlex 7090', 'SN123456790', 'Intel i7, 16GB RAM, 512GB SSD', 1, '2', 'Available', 'Good', 0, '2024-01-15', 45000.00),
('COMP-IK502-001', 'Desktop Computer', 'Hardware', 'Desktop', 'HP', 'ProDesk 600', 'SN987654321', 'Intel i5, 8GB RAM, 256GB SSD', 2, '1', 'In Use', 'Good', 0, '2023-08-20', 35000.00),
('LAP-001', 'Laptop', 'Hardware', 'Laptop', 'Lenovo', 'ThinkPad E14', 'SN456789123', 'Intel i5, 8GB RAM, 256GB SSD, 14 inch', NULL, NULL, 'Available', 'Excellent', 1, '2024-06-10', 42000.00),
('LAP-002', 'Laptop', 'Hardware', 'Laptop', 'ASUS', 'VivoBook 15', 'SN789123456', 'Intel i3, 4GB RAM, 256GB SSD, 15.6 inch', NULL, NULL, 'Available', 'Good', 1, '2024-03-22', 28000.00),
('PROJ-001', 'Projector', 'Equipment', 'Projector', 'Epson', 'EB-X05', 'SN321654987', '3300 lumens, XGA resolution', NULL, NULL, 'Available', 'Good', 1, '2023-09-15', 25000.00),
('PRINT-IK501-001', 'Network Printer', 'Peripheral', 'Printer', 'Canon', 'imageRUNNER 2525', 'SN147258369', 'Multifunction, Print/Scan/Copy', 1, NULL, 'Active', 'Good', 0, '2023-05-10', 55000.00),
('SWITCH-IK501', 'Network Switch', 'Network Device', 'Switch', 'Cisco', 'Catalyst 2960', 'SN852963741', '24-Port Gigabit Ethernet', 1, NULL, 'Active', 'Excellent', 0, '2023-11-05', 28000.00),
('SOFT-MSOFFICE-001', 'Microsoft Office 365', 'Software', 'Productivity Suite', 'Microsoft', 'Office 365 Education', 'LICENSE123456', 'Word, Excel, PowerPoint, OneNote, Teams', NULL, NULL, 'Active', 'Excellent', 0, '2024-01-01', 15000.00),
('CHAIR-IK501-001', 'Office Chair', 'Furniture', 'Chair', 'Herman Miller', 'Aeron', NULL, 'Ergonomic office chair, adjustable', 1, NULL, 'In Use', 'Good', 0, '2023-07-20', 8500.00);
