-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 03, 2025 at 06:56 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ams_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int(11) NOT NULL,
  `asset_tag` varchar(50) NOT NULL COMMENT 'Unique asset identification number',
  `asset_name` varchar(255) NOT NULL COMMENT 'Name/model of the asset',
  `asset_type` enum('Hardware','Software','Furniture','Equipment','Peripheral','Network Device','Other') NOT NULL DEFAULT 'Hardware',
  `category` varchar(100) DEFAULT NULL COMMENT 'Subcategory (e.g., Desktop, Laptop, Printer)',
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `specifications` text DEFAULT NULL COMMENT 'Technical specifications or description',
  `room_id` int(11) DEFAULT NULL COMMENT 'Foreign key to rooms table',
  `location` varchar(255) DEFAULT NULL COMMENT 'Specific location within room',
  `terminal_number` varchar(50) DEFAULT NULL COMMENT 'Terminal/workstation number',
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(10,2) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `status` enum('Active','In Use','Available','Under Maintenance','Retired','Disposed','Lost','Damaged') NOT NULL DEFAULT 'Available',
  `condition` enum('Excellent','Good','Fair','Poor','Non-Functional') NOT NULL DEFAULT 'Good',
  `is_borrowable` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = Can be borrowed, 0 = Cannot be borrowed',
  `assigned_to` int(11) DEFAULT NULL COMMENT 'User ID of person assigned to this asset',
  `assigned_date` datetime DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL COMMENT 'User ID of person who made the assignment',
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `maintenance_notes` text DEFAULT NULL,
  `notes` text DEFAULT NULL COMMENT 'General notes about the asset',
  `qr_code` varchar(255) DEFAULT NULL COMMENT 'Path to QR code image',
  `image` varchar(255) DEFAULT NULL COMMENT 'Path to asset image',
  `created_by` int(11) DEFAULT NULL COMMENT 'User ID who created the record',
  `updated_by` int(11) DEFAULT NULL COMMENT 'User ID who last updated the record',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Asset inventory management table';

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`id`, `asset_tag`, `asset_name`, `asset_type`, `category`, `brand`, `model`, `serial_number`, `specifications`, `room_id`, `location`, `terminal_number`, `purchase_date`, `purchase_cost`, `supplier`, `warranty_expiry`, `status`, `condition`, `is_borrowable`, `assigned_to`, `assigned_date`, `assigned_by`, `last_maintenance_date`, `next_maintenance_date`, `maintenance_notes`, `notes`, `qr_code`, `image`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'COMP-IK501-001', 'Desktop Computer', 'Hardware', 'Desktop', 'Dell', 'OptiPlex 7090', 'SN123456789', 'Intel i7, 16GB RAM, 512GB SSD', 1, NULL, '1', '2024-01-15', 45000.00, NULL, NULL, 'In Use', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-02 03:34:34', '2025-11-02 03:34:34'),
(2, 'COMP-IK501-002', 'Desktop Computer', 'Hardware', 'Desktop', 'Dell', 'OptiPlex 7090', 'SN123456790', 'Intel i7, 16GB RAM, 512GB SSD', 1, NULL, '2', '2024-01-15', 45000.00, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-02 03:34:34', '2025-11-02 03:34:34'),
(3, 'COMP-IK502-001', 'Desktop Computer', 'Hardware', 'Desktop', 'HP', 'ProDesk 600', 'SN987654321', 'Intel i5, 8GB RAM, 256GB SSD', 2, NULL, '1', '2023-08-20', 35000.00, NULL, NULL, 'In Use', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-02 03:34:34', '2025-11-02 03:34:34'),
(4, 'LAP-001', 'Laptop', 'Hardware', 'Laptop', 'Lenovo', 'ThinkPad E14', 'SN456789123', 'Intel i5, 8GB RAM, 256GB SSD, 14 inch', NULL, NULL, NULL, '2024-06-10', 42000.00, NULL, NULL, 'In Use', 'Excellent', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-02 03:34:34', '2025-11-02 04:54:56'),
(5, 'LAP-002', 'Laptop', 'Hardware', 'Laptop', 'ASUS', 'VivoBook 15', 'SN789123456', 'Intel i3, 4GB RAM, 256GB SSD, 15.6 inch', NULL, NULL, NULL, '2024-03-22', 28000.00, NULL, NULL, 'Available', 'Good', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-02 03:34:34', '2025-11-02 03:34:34'),
(6, 'PROJ-001', 'Projector', 'Equipment', 'Projector', 'Epson', 'EB-X05', 'SN321654987', '3300 lumens, XGA resolution', NULL, NULL, NULL, '2023-09-15', 25000.00, NULL, NULL, 'Available', 'Good', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-02 03:34:34', '2025-11-02 03:34:34'),
(7, 'PRINT-IK501-001', 'Network Printer', 'Peripheral', 'Printer', 'Canon', 'imageRUNNER 2525', 'SN147258369', 'Multifunction, Print/Scan/Copy', 1, NULL, NULL, '2023-05-10', 55000.00, NULL, NULL, 'Active', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-02 03:34:34', '2025-11-02 03:34:34'),
(8, 'SWITCH-IK501', 'Network Switch', 'Network Device', 'Switch', 'Cisco', 'Catalyst 2960', 'SN852963741', '24-Port Gigabit Ethernet', 1, NULL, NULL, '2023-11-05', 28000.00, NULL, NULL, 'Active', 'Excellent', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-02 03:34:34', '2025-11-02 03:34:34'),
(9, 'SOFT-MSOFFICE-001', 'Microsoft Office 365', 'Software', 'Productivity Suite', 'Microsoft', 'Office 365 Education', 'LICENSE123456', 'Word, Excel, PowerPoint, OneNote, Teams', NULL, NULL, NULL, '2024-01-01', 15000.00, NULL, NULL, 'Active', 'Excellent', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-02 03:34:34', '2025-11-02 03:34:34'),
(10, 'CHAIR-IK501-001', 'Office Chair', 'Furniture', 'Chair', 'Herman Miller', 'Aeron', NULL, 'Ergonomic office chair, adjustable', 1, NULL, NULL, '2023-07-20', 8500.00, NULL, NULL, 'In Use', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-02 03:34:34', '2025-11-02 03:34:34');

-- --------------------------------------------------------

--
-- Table structure for table `asset_borrowing`
--

CREATE TABLE `asset_borrowing` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Asset borrowing transactions and history';

--
-- Dumping data for table `asset_borrowing`
--

INSERT INTO `asset_borrowing` (`id`, `asset_id`, `borrower_id`, `borrower_name`, `borrowed_date`, `expected_return_date`, `actual_return_date`, `purpose`, `status`, `approved_by`, `approved_date`, `returned_condition`, `return_notes`, `created_at`, `updated_at`) VALUES
(1, 4, 5, 'Student One', '2025-11-04 00:00:00', '2025-11-05 00:00:00', NULL, 'dasdsa', 'Pending', 3, '2025-11-02 12:54:56', NULL, NULL, '2025-11-02 04:03:48', '2025-11-02 04:57:54');

-- --------------------------------------------------------

--
-- Table structure for table `asset_maintenance`
--

CREATE TABLE `asset_maintenance` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Asset maintenance history and schedule';

-- --------------------------------------------------------

--
-- Table structure for table `issues`
--

CREATE TABLE `issues` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `category` enum('hardware','software','network','laboratory','other') NOT NULL,
  `room` varchar(64) NOT NULL,
  `terminal` varchar(16) NOT NULL,
  `hardware_component` varchar(255) DEFAULT NULL,
  `hardware_component_other` varchar(255) DEFAULT NULL,
  `software_name` varchar(255) DEFAULT NULL,
  `network_issue_type` varchar(255) DEFAULT NULL,
  `network_issue_type_other` varchar(255) DEFAULT NULL,
  `laboratory_concern_type` varchar(255) DEFAULT NULL,
  `laboratory_concern_other` varchar(255) DEFAULT NULL,
  `other_concern_category` varchar(255) DEFAULT NULL,
  `other_concern_other` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('Low','Medium','High') DEFAULT 'Medium',
  `status` enum('Open','In Progress','Resolved','Closed') DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `assigned_technician` varchar(255) DEFAULT NULL,
  `submitted_by` varchar(255) DEFAULT NULL,
  `assigned_group` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `issues`
--

INSERT INTO `issues` (`id`, `user_id`, `category`, `room`, `terminal`, `hardware_component`, `hardware_component_other`, `software_name`, `network_issue_type`, `network_issue_type_other`, `laboratory_concern_type`, `laboratory_concern_other`, `other_concern_category`, `other_concern_other`, `title`, `description`, `priority`, `status`, `created_at`, `updated_at`, `assigned_technician`, `submitted_by`, `assigned_group`) VALUES
(1, 5, 'hardware', 'IK501', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Not working', 'May kagat yung keycaps!', '', 'Open', '2025-11-03 05:52:52', '2025-11-03 05:52:52', NULL, NULL, NULL),
(2, 5, 'hardware', 'IK502', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'May kagat yung keycaps', 'May kulangot yung letter M!', '', 'Open', '2025-11-03 05:53:19', '2025-11-03 05:53:19', NULL, NULL, NULL),
(3, 5, 'software', 'IK501', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'May google', 'Google tate', '', 'Open', '2025-11-03 05:54:36', '2025-11-03 05:55:13', NULL, NULL, 'John Technician');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `item_type` enum('hardware','software','network') NOT NULL,
  `asset_tag` varchar(100) DEFAULT NULL,
  `component` varchar(255) DEFAULT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `software_name` varchar(255) DEFAULT NULL,
  `version` varchar(100) DEFAULT NULL,
  `license_info` varchar(255) DEFAULT NULL,
  `network_item` varchar(255) DEFAULT NULL,
  `area` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `created_at`) VALUES
(1, 'IK501', '2025-10-31 13:02:25'),
(2, 'IK502', '2025-10-31 13:02:25'),
(3, 'IK503', '2025-10-31 13:02:25'),
(4, 'IK504', '2025-10-31 13:02:25'),
(5, 'IK505', '2025-10-31 13:02:25');

-- --------------------------------------------------------

--
-- Table structure for table `pc_units`
--

CREATE TABLE `pc_units` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL COMMENT 'Foreign key to rooms table',
  `terminal_number` varchar(50) NOT NULL COMMENT 'Terminal number (e.g., th-1, th-2)',
  `pc_name` varchar(100) DEFAULT NULL COMMENT 'Custom name for the PC',
  `asset_tag` varchar(50) DEFAULT NULL COMMENT 'Main PC asset tag reference',
  `status` enum('Active','Inactive','Under Maintenance','Disposed') NOT NULL DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Individual PC units in rooms';

--
-- Dumping data for table `pc_units`
--

INSERT INTO `pc_units` (`id`, `room_id`, `terminal_number`, `pc_name`, `asset_tag`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'th-1', 'IK501-PC01', 'COMP-IK501-001', 'Active', 'Main instructor station', '2025-11-11 08:00:00', '2025-11-11 08:00:00'),
(2, 1, 'th-2', 'IK501-PC02', 'COMP-IK501-002', 'Active', NULL, '2025-11-11 08:00:00', '2025-11-11 08:00:00'),
(3, 2, 'th-1', 'IK502-PC01', 'COMP-IK502-001', 'Active', NULL, '2025-11-11 08:00:00', '2025-11-11 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `pc_components`
--

CREATE TABLE `pc_components` (
  `id` int(11) NOT NULL,
  `pc_unit_id` int(11) NOT NULL COMMENT 'Foreign key to pc_units table',
  `component_type` enum('CPU','RAM','Motherboard','Storage','GPU','PSU','Case','Monitor','Keyboard','Mouse','Other') NOT NULL,
  `component_name` varchar(255) NOT NULL COMMENT 'Name/model of component',
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `specifications` text DEFAULT NULL COMMENT 'Detailed specs',
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(10,2) DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `status` enum('Working','Faulty','Replaced','Disposed') NOT NULL DEFAULT 'Working',
  `condition` enum('Excellent','Good','Fair','Poor') NOT NULL DEFAULT 'Good',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Components within each PC unit';

--
-- Dumping data for table `pc_components`
--

INSERT INTO `pc_components` (`id`, `pc_unit_id`, `component_type`, `component_name`, `brand`, `model`, `serial_number`, `specifications`, `purchase_date`, `purchase_cost`, `warranty_expiry`, `status`, `condition`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'CPU', 'Intel Core i7-11700', 'Intel', 'i7-11700', 'CPU-SN-001', '8 Cores, 16 Threads, 2.5GHz Base, 4.9GHz Boost', '2024-01-15', 18000.00, '2027-01-15', 'Working', 'Good', NULL, '2025-11-11 08:00:00', '2025-11-11 08:00:00'),
(2, 1, 'RAM', 'Corsair Vengeance 16GB', 'Corsair', 'CMK16GX4M2B3200C16', 'RAM-SN-001', 'DDR4 3200MHz, 2x8GB', '2024-01-15', 4500.00, '2026-01-15', 'Working', 'Good', NULL, '2025-11-11 08:00:00', '2025-11-11 08:00:00'),
(3, 1, 'Motherboard', 'Dell Motherboard', 'Dell', 'OptiPlex 7090 MB', 'MB-SN-001', 'Intel B560 Chipset, LGA1200 Socket', '2024-01-15', 8000.00, '2027-01-15', 'Working', 'Good', NULL, '2025-11-11 08:00:00', '2025-11-11 08:00:00'),
(4, 1, 'Storage', 'Samsung 970 EVO Plus 512GB', 'Samsung', '970 EVO Plus', 'SSD-SN-001', 'NVMe M.2 SSD, 3500MB/s Read, 3300MB/s Write', '2024-01-15', 5500.00, '2029-01-15', 'Working', 'Excellent', NULL, '2025-11-11 08:00:00', '2025-11-11 08:00:00'),
(5, 1, 'GPU', 'Intel UHD Graphics 750', 'Intel', 'UHD 750', NULL, 'Integrated Graphics', '2024-01-15', 0.00, NULL, 'Working', 'Good', 'Integrated with CPU', '2025-11-11 08:00:00', '2025-11-11 08:00:00'),
(6, 1, 'PSU', 'Dell 260W PSU', 'Dell', '260W', 'PSU-SN-001', '260W 80+ Bronze', '2024-01-15', 2500.00, '2027-01-15', 'Working', 'Good', NULL, '2025-11-11 08:00:00', '2025-11-11 08:00:00'),
(7, 1, 'Monitor', 'Dell P2422H', 'Dell', 'P2422H', 'MON-SN-001', '24 inch, 1920x1080, IPS, 60Hz', '2024-01-15', 8500.00, '2027-01-15', 'Working', 'Good', NULL, '2025-11-11 08:00:00', '2025-11-11 08:00:00'),
(8, 1, 'Keyboard', 'Dell KB216', 'Dell', 'KB216', 'KB-SN-001', 'USB Wired Keyboard', '2024-01-15', 800.00, NULL, 'Working', 'Good', NULL, '2025-11-11 08:00:00', '2025-11-11 08:00:00'),
(9, 1, 'Mouse', 'Dell MS116', 'Dell', 'MS116', 'MS-SN-001', 'USB Wired Optical Mouse', '2024-01-15', 500.00, NULL, 'Working', 'Good', NULL, '2025-11-11 08:00:00', '2025-11-11 08:00:00'),
(10, 2, 'CPU', 'Intel Core i7-11700', 'Intel', 'i7-11700', 'CPU-SN-002', '8 Cores, 16 Threads, 2.5GHz Base, 4.9GHz Boost', '2024-01-15', 18000.00, '2027-01-15', 'Working', 'Good', NULL, '2025-11-11 08:00:00', '2025-11-11 08:00:00'),
(11, 2, 'RAM', 'Corsair Vengeance 16GB', 'Corsair', 'CMK16GX4M2B3200C16', 'RAM-SN-002', 'DDR4 3200MHz, 2x8GB', '2024-01-15', 4500.00, '2026-01-15', 'Working', 'Good', NULL, '2025-11-11 08:00:00', '2025-11-11 08:00:00'),
(12, 2, 'Storage', 'Samsung 970 EVO Plus 512GB', 'Samsung', '970 EVO Plus', 'SSD-SN-002', 'NVMe M.2 SSD', '2024-01-15', 5500.00, '2029-01-15', 'Working', 'Good', NULL, '2025-11-11 08:00:00', '2025-11-11 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('Student','Faculty','Technician','Laboratory Staff','Administrator') NOT NULL,
  `status` enum('Active','Inactive','Suspended') DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `e_signature` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `id_number`, `password`, `full_name`, `email`, `role`, `status`, `created_at`, `updated_at`, `last_login`, `e_signature`) VALUES
(1, '22-0306', '$2y$10$NrA9Ob9vAY4MF436ROTd2ecE2iYcVMFWCtbEGcTdfD7zH.ErqYCV6', 'Admin User', 'admin@ams.edu', 'Administrator', 'Active', '2025-10-28 21:34:53', '2025-10-28 22:08:12', NULL, NULL),
(2, '22-0307', '$2y$10$on5Q98KdJ3bnnvysSRbsBePxalUzs62G8F76Yk7pZLl8sDdW5WVUu', 'John Technician', 'technician@ams.edu', 'Technician', 'Active', '2025-10-28 21:34:53', '2025-10-28 23:03:14', NULL, NULL),
(3, '22-0308', '$2y$10$bEBBQUTMdL1tBiviKwv0DubLn8QbWojiqmTVqUJzjxMp/xYH3SFFm', 'Maria Lab Staff', 'labstaff@ams.edu', 'Laboratory Staff', 'Active', '2025-10-28 21:34:53', '2025-11-02 12:49:35', NULL, 'signature_3_1762058975.jpg'),
(4, 'F2024-001', '12345', 'Dr. Jane Faculty', 'faculty@ams.edu', 'Faculty', 'Active', '2025-10-28 21:34:53', '2025-10-28 21:40:57', NULL, NULL),
(5, '22-0305', '$2y$10$clCXfgzls8VHen2k.aF6TuvTZ34Ntl.T3oWxfhzTn67A5mEEjI1QW', 'Student One', 'student1@ams.edu', 'Student', 'Active', '2025-10-28 21:34:53', '2025-11-02 11:23:02', NULL, 'signature_5_1762053782.jpg'),
(6, 'S2024-002', '12345', 'Student Two', 'student2@ams.edu', 'Student', 'Active', '2025-10-28 21:34:53', '2025-10-28 21:41:09', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_tag` (`asset_tag`),
  ADD KEY `idx_asset_type` (`asset_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_is_borrowable` (`is_borrowable`),
  ADD KEY `idx_room_id` (`room_id`),
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_serial_number` (`serial_number`),
  ADD KEY `fk_assets_assigned_by` (`assigned_by`),
  ADD KEY `fk_assets_updated_by` (`updated_by`);

--
-- Indexes for table `asset_borrowing`
--
ALTER TABLE `asset_borrowing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_asset_id` (`asset_id`),
  ADD KEY `idx_borrower_id` (`borrower_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_borrowed_date` (`borrowed_date`),
  ADD KEY `fk_borrowing_approved_by` (`approved_by`);

--
-- Indexes for table `asset_maintenance`
--
ALTER TABLE `asset_maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_asset_id` (`asset_id`),
  ADD KEY `idx_maintenance_date` (`maintenance_date`),
  ADD KEY `idx_performed_by` (`performed_by`);

--
-- Indexes for table `issues`
--
ALTER TABLE `issues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category` (`category`),
  ADD KEY `status` (`status`),
  ADD KEY `assigned_group` (`assigned_group`);

--
-- Indexes for table `pc_units`
--
ALTER TABLE `pc_units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_room_terminal` (`room_id`,`terminal_number`),
  ADD KEY `idx_room_id` (`room_id`),
  ADD KEY `idx_terminal_number` (`terminal_number`),
  ADD KEY `idx_asset_tag` (`asset_tag`);

--
-- Indexes for table `pc_components`
--
ALTER TABLE `pc_components`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pc_unit_id` (`pc_unit_id`),
  ADD KEY `idx_component_type` (`component_type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_number` (`id_number`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_id_number` (`id_number`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `asset_borrowing`
--
ALTER TABLE `asset_borrowing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `asset_maintenance`
--
ALTER TABLE `asset_maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `issues`
--
ALTER TABLE `issues`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pc_units`
--
ALTER TABLE `pc_units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pc_components`
--
ALTER TABLE `pc_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `fk_assets_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_assets_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_assets_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_assets_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_assets_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `asset_borrowing`
--
ALTER TABLE `asset_borrowing`
  ADD CONSTRAINT `fk_borrowing_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_borrowing_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_borrowing_borrower` FOREIGN KEY (`borrower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `asset_maintenance`
--
ALTER TABLE `asset_maintenance`
  ADD CONSTRAINT `fk_maintenance_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_maintenance_performed_by` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `pc_units`
--
ALTER TABLE `pc_units`
  ADD CONSTRAINT `fk_pc_units_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pc_components`
--
ALTER TABLE `pc_components`
  ADD CONSTRAINT `fk_pc_components_pc_unit` FOREIGN KEY (`pc_unit_id`) REFERENCES `pc_units` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
