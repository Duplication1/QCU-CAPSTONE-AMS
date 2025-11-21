-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 21, 2025 at 05:17 PM
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
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'User who performed the action',
  `action` varchar(50) NOT NULL COMMENT 'Action type: login, logout, create, update, delete, view, etc.',
  `entity_type` varchar(50) DEFAULT NULL COMMENT 'Type of entity affected: user, asset, ticket, borrowing, etc.',
  `entity_id` int(11) DEFAULT NULL COMMENT 'ID of the affected entity',
  `description` text DEFAULT NULL COMMENT 'Detailed description of the action',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of the user',
  `user_agent` text DEFAULT NULL COMMENT 'Browser/device information',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', 'system', NULL, 'User logged into the system', '127.0.0.1', NULL, '2025-11-21 05:26:29'),
(2, 1, 'create', 'user', 2, 'Created new user account', '127.0.0.1', NULL, '2025-11-21 05:26:29'),
(3, 1, 'update', 'asset', 1, 'Updated asset information', '127.0.0.1', NULL, '2025-11-21 05:26:29'),
(4, 1, 'export', 'report', NULL, 'Generated users report', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 05:47:09'),
(5, 1, 'view', 'report', NULL, 'Previewed borrowing report (2025-10-22 to 2025-11-21)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 05:51:17'),
(6, 1, 'view', 'report', NULL, 'Previewed borrowing report (2025-10-21 to 2025-11-21)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 05:51:21'),
(7, 1, 'view', 'report', NULL, 'Previewed borrowing report (2025-10-21 to 2025-11-22)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 05:51:23'),
(8, 1, 'view', 'report', NULL, 'Previewed borrowing report (2025-10-21 to 2025-11-26)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 05:51:25'),
(9, 1, 'view', 'report', NULL, 'Previewed borrowing report (2025-10-02 to 2025-11-26)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 05:51:28'),
(10, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-02 to 2025-11-26)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 05:51:31'),
(11, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-21 to 2025-11-21)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 13:51:02'),
(12, 1, 'export', 'report', NULL, 'Generated tickets report (2025-10-21 to 2025-11-21)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 13:51:09'),
(13, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-21 to 2025-11-22)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 13:51:32'),
(14, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-21 to 2025-11-27)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 13:51:34'),
(15, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-21 to 2025-11-27)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 13:51:43'),
(16, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-21 to 2025-11-21)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 13:52:02');

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
(1, 'COMP-IK501-001', 'Desktop Computer', 'Hardware', 'Desktop', 'Dell', 'OptiPlex 7090', 'SN123456789', 'Intel i7, 16GB RAM, 512GB SSD', 1, NULL, '1', '2024-01-15', 45000.00, NULL, NULL, 'In Use', 'Excellent', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-02 03:34:34', '2025-11-21 13:55:12'),
(2, 'COMP-IK501-002', 'Desktop Computer', 'Hardware', 'Desktop', 'Dell', 'OptiPlex 7090', 'SN123456790', 'Intel i7, 16GB RAM, 512GB SSD', 1, NULL, '2', '2024-01-15', 45000.00, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-02 03:34:34', '2025-11-02 03:34:34'),
(3, 'COMP-IK502-001', 'Desktop Computer', 'Hardware', 'Desktop', 'HP', 'ProDesk 600', 'SN987654321', 'Intel i5, 8GB RAM, 256GB SSD', 2, NULL, '1', '2023-08-20', 35000.00, NULL, NULL, 'In Use', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-02 03:34:34', '2025-11-02 03:34:34'),
(4, 'LAP-001', 'Laptop', 'Hardware', 'Laptop', 'Lenovo', 'ThinkPad E14', 'SN456789123', 'Intel i5, 8GB RAM, 256GB SSD, 14 inch', NULL, NULL, NULL, '2024-06-10', 42000.00, NULL, NULL, 'In Use', 'Excellent', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-02 03:34:34', '2025-11-02 04:54:56'),
(5, 'LAP-002', 'Laptop', 'Hardware', 'Laptop', 'ASUS', 'VivoBook 15', 'SN789123456', 'Intel i3, 4GB RAM, 256GB SSD, 15.6 inch', NULL, NULL, NULL, '2024-03-22', 28000.00, NULL, NULL, 'Available', 'Good', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-02 03:34:34', '2025-11-21 03:37:51'),
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
(1, 4, 5, 'Student One', '2025-11-04 00:00:00', '2025-11-05 00:00:00', NULL, 'dasdsa', 'Approved', 3, '2025-11-21 11:35:46', NULL, NULL, '2025-11-02 04:03:48', '2025-11-21 03:35:46'),
(5, 5, 5, 'Student One', '2025-11-21 00:00:00', '2025-11-29 00:00:00', '2025-11-21 11:37:51', 'csadasd', 'Returned', 3, '2025-11-21 11:27:47', 'Excellent', 'dsdsa', '2025-11-21 03:05:12', '2025-11-21 03:37:51');

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
  `assigned_group` varchar(100) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `issues`
--

INSERT INTO `issues` (`id`, `user_id`, `category`, `room`, `terminal`, `hardware_component`, `hardware_component_other`, `software_name`, `network_issue_type`, `network_issue_type_other`, `laboratory_concern_type`, `laboratory_concern_other`, `other_concern_category`, `other_concern_other`, `title`, `description`, `priority`, `status`, `created_at`, `updated_at`, `assigned_technician`, `submitted_by`, `assigned_group`, `is_archived`, `archived_at`) VALUES
(1, 5, 'hardware', 'IK501', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Not working', 'May kagat yung keycaps!', '', 'Open', '2025-11-03 05:52:52', '2025-11-03 05:52:52', NULL, NULL, NULL, 0, NULL),
(2, 5, 'hardware', 'IK502', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'May kagat yung keycaps', 'May kulangot yung letter M!', '', 'Resolved', '2025-11-03 05:53:19', '2025-11-21 01:38:27', NULL, NULL, 'John Technician', 0, NULL),
(3, 5, 'software', 'IK501', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'May google', 'Google tate', '', 'Resolved', '2025-11-03 05:54:36', '2025-11-21 11:26:35', 'John Technician', NULL, 'John Technician', 0, NULL),
(4, 5, 'hardware', 'IK501', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'dsa', 'dsadsa', '', 'Resolved', '2025-11-21 01:29:46', '2025-11-21 11:39:56', 'John Technician', NULL, NULL, 0, NULL),
(5, 5, 'hardware', 'IK502', '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'asdsa', 'ddsadas', '', 'Closed', '2025-11-21 02:29:42', '2025-11-21 11:40:01', 'John Technician', NULL, NULL, 0, NULL),
(6, 5, 'hardware', 'IK502', '5', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'dsadasdsad', 'sadasdsad', '', 'Closed', '2025-11-21 02:49:48', '2025-11-21 11:40:04', 'John Technician', NULL, NULL, 0, NULL),
(7, 5, 'hardware', 'IK502', '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'sadasd', 'dsadsa', '', 'In Progress', '2025-11-21 02:58:42', '2025-11-21 13:53:27', 'John Technician', NULL, NULL, 0, NULL),
(8, 5, 'hardware', 'IK502', '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'das', 'dsadsa', '', 'Resolved', '2025-11-21 03:04:13', '2025-11-21 11:18:05', 'John Technician', NULL, NULL, 0, NULL),
(9, 5, 'hardware', 'IK502', '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'sadsa', 'ddsasd', '', 'Resolved', '2025-11-21 03:04:26', '2025-11-21 11:44:39', 'John Technician', NULL, 'John Technician', 1, '2025-11-21 11:44:39'),
(10, 5, 'hardware', 'IK501', '3', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'dsa', 'sadasd', '', 'In Progress', '2025-11-21 13:43:47', '2025-11-21 13:58:47', 'John Technician', NULL, NULL, 0, NULL);

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
-- Table structure for table `login_history`
--

CREATE TABLE `login_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device_type` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_history`
--

INSERT INTO `login_history` (`id`, `user_id`, `login_time`, `ip_address`, `user_agent`, `device_type`) VALUES
(1, 5, '2025-11-21 02:25:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(2, 3, '2025-11-21 02:51:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(3, 2, '2025-11-21 03:29:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(4, 3, '2025-11-21 03:33:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(5, 5, '2025-11-21 03:40:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(6, 5, '2025-11-21 03:52:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(7, 1, '2025-11-21 04:03:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(8, 1, '2025-11-21 05:50:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(9, 2, '2025-11-21 06:00:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(10, 2, '2025-11-21 06:22:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(11, 3, '2025-11-21 06:22:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(12, 5, '2025-11-21 06:25:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(13, 3, '2025-11-21 06:28:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(14, 5, '2025-11-21 06:34:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(15, 3, '2025-11-21 06:40:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(16, 3, '2025-11-21 06:45:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(17, 2, '2025-11-21 06:51:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(18, 3, '2025-11-21 06:52:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(19, 1, '2025-11-21 07:25:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(20, 2, '2025-11-21 08:13:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(21, 1, '2025-11-21 09:57:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(22, 5, '2025-11-21 10:00:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(23, 2, '2025-11-21 10:02:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(24, 2, '2025-11-21 10:05:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(25, 3, '2025-11-21 10:09:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(26, 3, '2025-11-21 11:58:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(27, 5, '2025-11-21 13:41:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(28, 1, '2025-11-21 13:46:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(29, 2, '2025-11-21 13:52:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(30, 3, '2025-11-21 13:56:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(31, 2, '2025-11-21 13:58:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(32, 5, '2025-11-21 13:59:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(33, 5, '2025-11-21 16:11:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `related_type` enum('issue','borrowing','asset','system') DEFAULT 'system',
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `related_type`, `related_id`, `is_read`, `created_at`) VALUES
(1, 5, 'Ticket #9 - Status Updated', 'Your ticket is now being worked on by John Technician.', 'info', 'issue', 9, 1, '2025-11-21 03:29:33'),
(2, 5, 'Ticket #9 - Status Updated', 'Your ticket has been resolved by John Technician.', 'success', 'issue', 9, 0, '2025-11-21 03:33:08'),
(3, 5, 'Asset Returned - Request #5', 'Your borrowed asset has been returned and marked as \'Excellent\'. Thank you!', 'success', 'borrowing', 5, 0, '2025-11-21 03:37:51'),
(4, 5, 'Ticket #8 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 8, 0, '2025-11-21 03:57:49'),
(5, 5, 'Ticket #7 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 7, 1, '2025-11-21 06:24:55'),
(6, 2, 'New Ticket Assigned #7', 'You have been assigned to a hardware ticket: \"sadasd\". Please review and take action.', 'info', 'issue', 7, 0, '2025-11-21 06:24:55'),
(7, 5, 'Ticket #6 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 6, 1, '2025-11-21 06:28:46'),
(8, 2, 'New Ticket Assigned #6', 'You have been assigned to a hardware ticket: \"dsadasdsad\". Please review and take action.', 'info', 'issue', 6, 0, '2025-11-21 06:28:46'),
(9, 5, 'Ticket #5 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 5, 1, '2025-11-21 06:30:59'),
(10, 2, 'New Ticket Assigned #5', 'You have been assigned to a hardware ticket: \"asdsa\". Please review and take action.', 'info', 'issue', 5, 0, '2025-11-21 06:30:59'),
(11, 5, 'Ticket #9 - Status Updated', 'Your ticket is now being worked on by John Technician.', 'info', 'issue', 9, 1, '2025-11-21 10:24:11'),
(12, 5, 'Ticket #9 - Status Updated', 'Your ticket has been resolved by John Technician.', 'success', 'issue', 9, 1, '2025-11-21 10:24:15'),
(13, 5, 'Ticket #9 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 9, 1, '2025-11-21 10:27:49'),
(14, 2, 'New Ticket Assigned #9', 'You have been assigned to a hardware ticket: \"sadsa\". Please review and take action.', 'info', 'issue', 9, 0, '2025-11-21 10:27:49'),
(15, 5, 'Ticket #4 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 4, 1, '2025-11-21 10:28:15'),
(16, 2, 'New Ticket Assigned #4', 'You have been assigned to a hardware ticket: \"dsa\". Please review and take action.', 'info', 'issue', 4, 0, '2025-11-21 10:28:15'),
(17, 5, 'Ticket #8 - Status Updated', 'Your ticket has been resolved by John Technician.', 'success', 'issue', 8, 1, '2025-11-21 11:18:05'),
(18, 5, 'Ticket #3 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 3, 1, '2025-11-21 11:26:26'),
(19, 2, 'New Ticket Assigned #3', 'You have been assigned to a software ticket: \"May google\". Please review and take action.', 'info', 'issue', 3, 0, '2025-11-21 11:26:26'),
(20, 5, 'Ticket #3 - Status Updated', 'Your ticket has been resolved by John Technician.', 'success', 'issue', 3, 1, '2025-11-21 11:26:35'),
(21, 5, 'Ticket #5 - Status Updated', 'Your ticket is now being worked on by John Technician.', 'info', 'issue', 5, 1, '2025-11-21 11:39:24'),
(22, 5, 'Ticket #5 - Status Updated', 'Your ticket has been resolved by John Technician.', 'success', 'issue', 5, 1, '2025-11-21 11:39:27'),
(23, 5, 'Ticket #4 - Status Updated', 'Your ticket has been closed.', 'info', 'issue', 4, 1, '2025-11-21 11:39:53'),
(24, 5, 'Ticket #4 - Status Updated', 'Your ticket has been resolved by John Technician.', 'success', 'issue', 4, 1, '2025-11-21 11:39:56'),
(25, 5, 'Ticket #5 - Status Updated', 'Your ticket has been closed.', 'info', 'issue', 5, 1, '2025-11-21 11:40:01'),
(26, 5, 'Ticket #6 - Status Updated', 'Your ticket has been closed.', 'info', 'issue', 6, 1, '2025-11-21 11:40:04'),
(27, 5, 'Ticket #10 Submitted', 'Your ticket has been submitted successfully and is pending assignment.', 'success', 'issue', 10, 1, '2025-11-21 13:43:47'),
(28, 3, 'New Ticket Submitted', 'Student One submitted a new hardware ticket: dsa', 'info', 'issue', 10, 0, '2025-11-21 13:43:47'),
(29, 5, 'Ticket #7 - Status Updated', 'Your ticket is now being worked on by John Technician.', 'info', 'issue', 7, 1, '2025-11-21 13:53:27'),
(30, 5, 'Ticket #10 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 10, 1, '2025-11-21 13:58:26'),
(31, 2, 'New Ticket Assigned #10', 'You have been assigned to a hardware ticket: \"dsa\". Please review and take action.', 'info', 'issue', 10, 0, '2025-11-21 13:58:26'),
(32, 5, 'Ticket #10 - Status Updated', 'Your ticket is now being worked on by John Technician.', 'info', 'issue', 10, 1, '2025-11-21 13:58:47');

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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('Student','Faculty','Technician','Laboratory Staff','Administrator') NOT NULL,
  `status` enum('Active','Inactive','Suspended','Deactivated') DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `e_signature` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `id_number`, `password`, `full_name`, `email`, `role`, `status`, `created_at`, `updated_at`, `last_login`, `e_signature`) VALUES
(1, '22-0306', '$2y$10$NrA9Ob9vAY4MF436ROTd2ecE2iYcVMFWCtbEGcTdfD7zH.ErqYCV6', 'Admin User', 'admin@ams.edu', 'Administrator', 'Active', '2025-10-28 21:34:53', '2025-11-21 21:46:43', '2025-11-21 21:46:43', NULL),
(2, '22-0307', '$2y$10$on5Q98KdJ3bnnvysSRbsBePxalUzs62G8F76Yk7pZLl8sDdW5WVUu', 'John Technician', 'technician@ams.edu', 'Technician', 'Active', '2025-10-28 21:34:53', '2025-11-21 21:58:23', '2025-11-21 21:58:23', NULL),
(3, '22-0308', '$2y$10$bEBBQUTMdL1tBiviKwv0DubLn8QbWojiqmTVqUJzjxMp/xYH3SFFm', 'Maria Lab Staff', 'labstaff@ams.edu', 'Laboratory Staff', 'Active', '2025-10-28 21:34:53', '2025-11-21 21:56:17', '2025-11-21 21:56:17', 'signature_3_1762058975.jpg'),
(4, 'F2024-001', '12345', 'Dr. Jane Faculty', 'faculty@ams.edu', 'Faculty', 'Active', '2025-10-28 21:34:53', '2025-10-28 21:40:57', NULL, NULL),
(5, '22-0305', '$2y$10$clCXfgzls8VHen2k.aF6TuvTZ34Ntl.T3oWxfhzTn67A5mEEjI1QW', 'Student One', 'student1@ams.edu', 'Student', 'Active', '2025-10-28 21:34:53', '2025-11-22 00:11:14', '2025-11-22 00:11:14', 'signature_5_1763692234.jpg'),
(6, 'S2024-002', '12345', 'Student Two', 'student2@ams.edu', 'Student', 'Active', '2025-10-28 21:34:53', '2025-10-28 21:41:09', NULL, NULL),
(7, '22-0309', '$2y$10$OREa9SKZkYZjAt4zAzN/xO/wB19GIzwMYe7CNx4oeKMQ1B2k54r5a', 'Kim Gamot', 'gamot.kim.fernandez@gmail.com', 'Student', 'Active', '2025-11-21 09:31:17', NULL, NULL, NULL),
(8, '22-0310', '$2y$10$yT9vhx/qe/gM9QIx0jl8mO0Msyi8siyi6NpQyfP4IaACCs6/WHEqK', 'dsada dsdas', 'menard@gmail.com', 'Administrator', 'Active', '2025-11-21 09:34:51', '2025-11-21 09:40:09', NULL, NULL),
(10, '22-0312', '$2y$10$zf8qH/4c.t1dYdYw.BKriuIvaucJEdRQYsIFfeDRSgUa3tX9kgeEu', 'dsada Gamot', 'dsadas@gmail.com', 'Administrator', 'Active', '2025-11-21 09:41:11', NULL, NULL, NULL),
(11, '22-0315', '$2y$10$kdVvGXLNs5RcZq26dcbR3eeSLbUpB/4bOhQHLirIjk1l/rWG/ZVzi', 'Kim Gamot', 'sadsadasd@gmail.com', 'Technician', 'Active', '2025-11-21 09:41:39', '2025-11-21 13:53:28', NULL, NULL),
(12, '22-0350', '$2y$10$GeNNuBNLc340Evg..6nfNub8nGn7TRZ97r7J.Fp5QQnAvC8sC.646', 'Kim Gamot', 'asdsadsada@gmail.com', 'Administrator', 'Active', '2025-11-21 09:42:16', '2025-11-21 13:53:46', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_created_at` (`created_at`);

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
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_history`
--
ALTER TABLE `login_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `login_time` (`login_time`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `pc_components`
--
ALTER TABLE `pc_components`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pc_unit_id` (`pc_unit_id`),
  ADD KEY `idx_component_type` (`component_type`),
  ADD KEY `idx_status` (`status`);

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
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `asset_borrowing`
--
ALTER TABLE `asset_borrowing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `asset_maintenance`
--
ALTER TABLE `asset_maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `issues`
--
ALTER TABLE `issues`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_history`
--
ALTER TABLE `login_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `pc_components`
--
ALTER TABLE `pc_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `pc_units`
--
ALTER TABLE `pc_units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

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
-- Constraints for table `pc_components`
--
ALTER TABLE `pc_components`
  ADD CONSTRAINT `fk_pc_components_pc_unit` FOREIGN KEY (`pc_unit_id`) REFERENCES `pc_units` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pc_units`
--
ALTER TABLE `pc_units`
  ADD CONSTRAINT `fk_pc_units_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
