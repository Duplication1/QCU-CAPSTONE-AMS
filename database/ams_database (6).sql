-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 27, 2025 at 02:26 PM
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
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `action` varchar(50) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(10) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-27 to 2025-11-26)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 13:33:21');

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
  `pc_unit_id` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL COMMENT 'Specific location within room',
  `terminal_number` varchar(50) DEFAULT NULL COMMENT 'Terminal/workstation number',
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(10,2) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `status` enum('Available','In Use','Available','Under Maintenance','Retired','Disposed','Lost','Broken','Archive') NOT NULL DEFAULT 'Available',
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

INSERT INTO `assets` (`id`, `asset_tag`, `asset_name`, `asset_type`, `category`, `brand`, `model`, `serial_number`, `specifications`, `room_id`, `pc_unit_id`, `location`, `terminal_number`, `purchase_date`, `purchase_cost`, `supplier`, `warranty_expiry`, `status`, `condition`, `is_borrowable`, `assigned_to`, `assigned_date`, `assigned_by`, `last_maintenance_date`, `next_maintenance_date`, `maintenance_notes`, `notes`, `qr_code`, `image`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(51, '11-23-2025-RAM-IK501-019', 'RAM #19', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-23-2025-RAM-IK501-019%22%2C%22asset_name%22%3A%22RAM+%2319%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-23 13:55:21', '2025-11-27 12:06:28'),
(52, '11-23-2025-RAM-IK501-020', 'RAM #20', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, 21, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-23-2025-RAM-IK501-020%22%2C%22asset_name%22%3A%22RAM+%2320%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-23 13:55:21', '2025-11-27 12:06:26'),
(63, '11-24-2025-RAM-IK501-001', 'RAM #1', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, 1, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-RAM-IK501-001%22%2C%22asset_name%22%3A%22RAM+%231%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A%', NULL, 3, 3, '2025-11-24 13:39:04', '2025-11-27 12:30:17'),
(64, '11-24-2025-RAM-IK501-002', 'RAM #2', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Archive', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-RAM-IK501-002%22%2C%22asset_name%22%3A%22RAM+%232%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A%', NULL, 3, 3, '2025-11-24 13:39:04', '2025-11-27 09:57:39'),
(65, '11-24-2025-RAM-IK501-003', 'RAM #3', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Archive', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-RAM-IK501-003%22%2C%22asset_name%22%3A%22RAM+%233%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A%', NULL, 3, 3, '2025-11-24 13:39:04', '2025-11-27 09:57:39'),
(66, '11-24-2025-RAM-IK501-004', 'RAM #4', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Archive', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-RAM-IK501-004%22%2C%22asset_name%22%3A%22RAM+%234%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A%', NULL, 3, 3, '2025-11-24 13:39:04', '2025-11-27 09:57:39'),
(67, '11-24-2025-RAM-IK501-005', 'RAM #5', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Archive', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-RAM-IK501-005%22%2C%22asset_name%22%3A%22RAM+%235%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A%', NULL, 3, 3, '2025-11-24 13:39:04', '2025-11-27 09:57:39'),
(68, '11-24-2025-RAM-IK501-006', 'RAM #6', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Archive', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-RAM-IK501-006%22%2C%22asset_name%22%3A%22RAM+%236%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A%', NULL, 3, 3, '2025-11-24 13:39:04', '2025-11-27 09:57:39'),
(69, '11-24-2025-RAM-IK501-007', 'RAM #7', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Archive', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-RAM-IK501-007%22%2C%22asset_name%22%3A%22RAM+%237%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A%', NULL, 3, 3, '2025-11-24 13:39:04', '2025-11-27 12:09:52'),
(70, '11-24-2025-RAM-IK501-008', 'RAM #8', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Archive', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-RAM-IK501-008%22%2C%22asset_name%22%3A%22RAM+%238%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A%', NULL, 3, 3, '2025-11-24 13:39:04', '2025-11-27 12:09:52'),
(71, '11-24-2025-RAM-IK501-009', 'RAM #9', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Archive', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-RAM-IK501-009%22%2C%22asset_name%22%3A%22RAM+%239%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A%', NULL, 3, 3, '2025-11-24 13:39:04', '2025-11-27 12:09:52'),
(72, '11-24-2025-RAM-IK501-010', 'RAM #10', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-RAM-IK501-010%22%2C%22asset_name%22%3A%22RAM+%2310%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, 3, '2025-11-24 13:39:04', '2025-11-27 12:13:55'),
(73, '11-24-2025-RAM-IK501-011', 'RAM #11', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-RAM-IK501-011%22%2C%22asset_name%22%3A%22RAM+%2311%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, 3, '2025-11-24 13:39:04', '2025-11-27 12:13:55'),
(74, '11-24-2025-RAM-IK501-012', 'RAM #12', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-RAM-IK501-012%22%2C%22asset_name%22%3A%22RAM+%2312%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, 3, '2025-11-24 13:39:04', '2025-11-27 12:13:55'),
(75, '11-24-2025-RAM-IK501-013', 'RAM #13', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-RAM-IK501-013%22%2C%22asset_name%22%3A%22RAM+%2313%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, 3, '2025-11-24 13:39:04', '2025-11-27 12:13:55'),
(76, '11-24-2025-RAM-IK501-014', 'RAM #14', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-RAM-IK501-014%22%2C%22asset_name%22%3A%22RAM+%2314%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-24 13:39:04', '2025-11-24 13:39:04'),
(77, '11-24-2025-RAM-IK501-015', 'RAM #15', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-RAM-IK501-015%22%2C%22asset_name%22%3A%22RAM+%2315%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-24 13:39:04', '2025-11-24 13:39:04'),
(78, '11-24-2025-RAM-IK501-016', 'RAM #16', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-RAM-IK501-016%22%2C%22asset_name%22%3A%22RAM+%2316%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-24 13:39:04', '2025-11-24 13:39:04'),
(79, '11-24-2025-RAM-IK501-017', 'RAM #17', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-RAM-IK501-017%22%2C%22asset_name%22%3A%22RAM+%2317%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-24 13:39:04', '2025-11-24 13:39:04'),
(80, '11-24-2025-RAM-IK501-018', 'RAM #18', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-RAM-IK501-018%22%2C%22asset_name%22%3A%22RAM+%2318%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-24 13:39:04', '2025-11-24 13:39:04'),
(81, '11-24-2025-RAM-IK501-019', 'RAM #19', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-RAM-IK501-019%22%2C%22asset_name%22%3A%22RAM+%2319%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-24 13:39:04', '2025-11-24 13:39:04'),
(82, '11-24-2025-RAM-IK501-020', 'RAM #20', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-RAM-IK501-020%22%2C%22asset_name%22%3A%22RAM+%2320%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-24 13:39:04', '2025-11-24 13:39:04'),
(83, '11-24-2025-chair-IK501-001', 'chair #1', 'Furniture', NULL, 'Dell', 'dasdsa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-chair-IK501-001%22%2C%22asset_name%22%3A%22chair+%231%22%2C%22asset_type%22%3A%22Furniture%22%2C%22room_id%22%3Anull%2C%22brand%22%3A%22Dell%22%2C%22model%22%', NULL, 3, NULL, '2025-11-24 13:41:53', '2025-11-24 13:41:53'),
(84, '11-24-2025-chair-IK200-002', 'chair #2', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Archive', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-chair-IK200-002%22%2C%22asset_name%22%3A%22chair+%232%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%2C%22brand%22%3A%22Dell%22%2C%22model%22%3', NULL, 3, 3, '2025-11-24 13:42:49', '2025-11-27 13:15:41'),
(85, '11-24-2025-chair-IK200-003', 'chair #3', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-chair-IK200-003%22%2C%22asset_name%22%3A%22chair+%233%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%2C%22brand%22%3A%22Dell%22%2C%22model%22%3', NULL, 3, NULL, '2025-11-24 13:42:49', '2025-11-24 13:42:49'),
(86, '11-24-2025-chair-IK200-004', 'chair #4', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-chair-IK200-004%22%2C%22asset_name%22%3A%22chair+%234%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%2C%22brand%22%3A%22Dell%22%2C%22model%22%3', NULL, 3, NULL, '2025-11-24 13:42:49', '2025-11-24 13:42:49'),
(87, '11-24-2025-chair-IK200-005', 'chair #5', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-chair-IK200-005%22%2C%22asset_name%22%3A%22chair+%235%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%2C%22brand%22%3A%22Dell%22%2C%22model%22%3', NULL, 3, NULL, '2025-11-24 13:42:49', '2025-11-24 13:42:49'),
(88, '11-24-2025-chair-IK200-006', 'chair #6', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-chair-IK200-006%22%2C%22asset_name%22%3A%22chair+%236%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%2C%22brand%22%3A%22Dell%22%2C%22model%22%3', NULL, 3, NULL, '2025-11-24 13:42:49', '2025-11-24 13:42:49'),
(89, '11-24-2025-chair-IK200-007', 'chair #7', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-chair-IK200-007%22%2C%22asset_name%22%3A%22chair+%237%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%2C%22brand%22%3A%22Dell%22%2C%22model%22%3', NULL, 3, NULL, '2025-11-24 13:42:49', '2025-11-24 13:42:49'),
(90, '11-24-2025-chair-IK200-008', 'chair #8', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-chair-IK200-008%22%2C%22asset_name%22%3A%22chair+%238%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%2C%22brand%22%3A%22Dell%22%2C%22model%22%3', NULL, 3, NULL, '2025-11-24 13:42:49', '2025-11-24 13:42:49'),
(91, '11-24-2025-chair-IK200-009', 'chair #9', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-chair-IK200-009%22%2C%22asset_name%22%3A%22chair+%239%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%2C%22brand%22%3A%22Dell%22%2C%22model%22%3', NULL, 3, NULL, '2025-11-24 13:42:49', '2025-11-24 13:42:49'),
(92, '11-24-2025-chair-IK200-010', 'chair #10', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-chair-IK200-010%22%2C%22asset_name%22%3A%22chair+%2310%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%2C%22brand%22%3A%22Dell%22%2C%22model%22%', NULL, 3, NULL, '2025-11-24 13:42:49', '2025-11-24 13:42:49'),
(93, '11-24-2025-chair-IK200-011', 'chair #11', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-chair-IK200-011%22%2C%22asset_name%22%3A%22chair+%2311%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%2C%22brand%22%3A%22Dell%22%2C%22model%22%', NULL, 3, NULL, '2025-11-24 13:42:49', '2025-11-24 13:42:49'),
(94, '11-24-2025-chair-IK200-012', 'chair #12', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-chair-IK200-012%22%2C%22asset_name%22%3A%22chair+%2312%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%2C%22brand%22%3A%22Dell%22%2C%22model%22%', NULL, 3, NULL, '2025-11-24 13:42:49', '2025-11-24 13:42:49'),
(95, '11-24-2025-chair-IK200-013', 'chair #13', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-chair-IK200-013%22%2C%22asset_name%22%3A%22chair+%2313%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%2C%22brand%22%3A%22Dell%22%2C%22model%22%', NULL, 3, NULL, '2025-11-24 13:42:49', '2025-11-24 13:42:49'),
(96, '11-24-2025-chair-IK200-014', 'chair #14', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-chair-IK200-014%22%2C%22asset_name%22%3A%22chair+%2314%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%2C%22brand%22%3A%22Dell%22%2C%22model%22%', NULL, 3, NULL, '2025-11-24 13:42:49', '2025-11-24 13:42:49'),
(97, '11-24-2025-chair-IK200-015', 'chair #15', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-chair-IK200-015%22%2C%22asset_name%22%3A%22chair+%2315%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%2C%22brand%22%3A%22Dell%22%2C%22model%22%', NULL, 3, NULL, '2025-11-24 13:42:49', '2025-11-24 13:42:49'),
(98, '11-24-2025-chair-IK200-016', 'chair #16', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-chair-IK200-016%22%2C%22asset_name%22%3A%22chair+%2316%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%2C%22brand%22%3A%22Dell%22%2C%22model%22%', NULL, 3, NULL, '2025-11-24 13:42:49', '2025-11-24 13:42:49'),
(99, '11-24-2025-chair-IK200-017', 'chair #17', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-chair-IK200-017%22%2C%22asset_name%22%3A%22chair+%2317%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%2C%22brand%22%3A%22Dell%22%2C%22model%22%', NULL, 3, NULL, '2025-11-24 13:42:49', '2025-11-24 13:42:49'),
(100, '11-24-2025-chair-IK200-018', 'chair #18', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-chair-IK200-018%22%2C%22asset_name%22%3A%22chair+%2318%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%2C%22brand%22%3A%22Dell%22%2C%22model%22%', NULL, 3, NULL, '2025-11-24 13:42:49', '2025-11-24 13:42:49'),
(101, '11-24-2025-chair-IK200-019', 'chair #19', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-chair-IK200-019%22%2C%22asset_name%22%3A%22chair+%2319%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%2C%22brand%22%3A%22Dell%22%2C%22model%22%', NULL, 3, NULL, '2025-11-24 13:42:49', '2025-11-24 13:42:49'),
(102, '11-24-2025-chair-IK200-020', 'chair #20', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-chair-IK200-020%22%2C%22asset_name%22%3A%22chair+%2320%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%2C%22brand%22%3A%22Dell%22%2C%22model%22%', NULL, 3, 3, '2025-11-24 13:42:49', '2025-11-24 13:43:04'),
(103, '11-24-2025-chair-IK200-021', 'chair #21', 'Hardware', NULL, 'Dell', 'dasdsa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-24-2025-chair-IK200-021%22%2C%22asset_name%22%3A%22chair+%2321%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%2C%22brand%22%3A%22Dell%22%2C%22model%22%', NULL, 3, NULL, '2025-11-24 13:42:49', '2025-11-24 13:42:49'),
(104, '11-27-2025-LAPTOP-IK501-001', 'LAPTOP #1', 'Hardware', NULL, '', '', NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-27-2025-LAPTOP-IK501-001%22%2C%22asset_name%22%3A%22LAPTOP+%231%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A5%2C%22room_name%22%3A%22IK501%22%2C%22brand%', NULL, 3, NULL, '2025-11-27 12:26:54', '2025-11-27 12:26:54');

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
(1, 4, 5, 'Student One', '2025-11-04 00:00:00', '2025-11-05 00:00:00', '2025-11-19 14:47:20', 'dasdsa', 'Returned', 3, '2025-11-19 14:47:02', 'Excellent', '', '2025-11-02 04:03:48', '2025-11-19 06:47:20');

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
-- Table structure for table `buildings`
--

CREATE TABLE `buildings` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buildings`
--

INSERT INTO `buildings` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'IK', '2025-11-22 13:15:33', '2025-11-22 13:34:39'),
(2, 'IL', '2025-11-22 13:15:33', '2025-11-22 13:34:42'),
(4, 'KORPHIL', '2025-11-22 13:22:42', '2025-11-22 14:31:18'),
(8, 'Main Building', '2025-11-23 11:27:47', '2025-11-23 11:27:47'),
(9, 'Science Building', '2025-11-23 11:27:47', '2025-11-23 11:27:47'),
(10, 'IT Building', '2025-11-23 11:27:47', '2025-11-23 11:27:47'),
(11, 'Ikot ng Katipunan Building', '2025-11-23 11:27:54', '2025-11-23 11:27:54');

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
  `archived_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `issues`
--

INSERT INTO `issues` (`id`, `user_id`, `category`, `room`, `terminal`, `hardware_component`, `hardware_component_other`, `software_name`, `network_issue_type`, `network_issue_type_other`, `laboratory_concern_type`, `laboratory_concern_other`, `other_concern_category`, `other_concern_other`, `title`, `description`, `priority`, `status`, `created_at`, `updated_at`, `assigned_technician`, `submitted_by`, `assigned_group`, `is_archived`, `archived_at`) VALUES
(1, 5, 'hardware', 'IK501', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Not working', 'May kagat yung keycaps!', '', 'Open', '2025-11-03 05:52:52', '2025-11-07 06:17:45', NULL, NULL, 'John Technician', 0, NULL),
(2, 5, 'hardware', 'IK502', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'May kagat yung keycaps', 'May kulangot yung letter M!', '', 'Open', '2025-11-03 05:53:19', '2025-11-19 04:21:15', NULL, NULL, 'John Technician', 0, NULL),
(3, 5, 'software', 'IK501', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'May google', 'Google tate', '', 'Resolved', '2025-11-03 05:54:36', '2025-11-19 03:55:26', NULL, NULL, 'John Technician', 1, NULL),
(4, 5, 'software', 'IK502', '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'q', 'q', '', 'Open', '2025-11-19 03:48:51', '2025-11-19 06:57:41', NULL, NULL, 'John Technician', 0, NULL),
(5, 5, 'hardware', 'IK501', '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'w', 'w', '', 'Open', '2025-11-19 03:49:02', '2025-11-19 06:57:36', NULL, NULL, 'John Technician', 0, NULL),
(6, 5, 'hardware', 'IK502', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ISSE', 'qweqwe', '', 'Resolved', '2025-11-19 04:44:55', '2025-11-19 04:57:19', NULL, NULL, 'John Technician', 1, '2025-11-19 12:57:19'),
(7, 5, 'hardware', 'IK503', '5', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'q', 'q', '', 'Open', '2025-11-19 04:58:29', '2025-11-19 06:57:33', NULL, NULL, 'John Technician', 0, NULL),
(8, 5, 'hardware', 'IK501', '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'q', 'q', '', 'Open', '2025-11-19 05:02:00', '2025-11-19 06:57:29', NULL, NULL, 'John Technician', 0, NULL),
(9, 5, 'hardware', 'IK501', '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'q', 'q', '', 'Open', '2025-11-19 05:11:39', '2025-11-19 06:57:26', NULL, NULL, 'John Technician', 0, NULL),
(10, 5, 'hardware', 'IK502', '3', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'q', 'q', '', 'Open', '2025-11-19 05:15:45', '2025-11-19 06:57:23', NULL, NULL, 'John Technician', 0, NULL),
(11, 5, 'hardware', 'IK501', '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'qwe', 'qr', '', 'Open', '2025-11-19 05:57:11', '2025-11-19 06:57:21', NULL, NULL, 'John Technician', 0, NULL),
(12, 5, 'hardware', 'IK502', '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'qzxzxc', 'qwewe', '', 'Open', '2025-11-19 05:58:06', '2025-11-19 06:57:19', NULL, NULL, 'John Technician', 0, NULL),
(13, 5, 'hardware', 'IK502', '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'qweqwezxc', 'qwezxc', '', 'Open', '2025-11-19 05:58:51', '2025-11-19 06:57:16', NULL, NULL, 'John Technician', 0, NULL),
(14, 5, 'hardware', 'IK503', '3', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'qbsfdghsf', 'qdhgsfgh', '', 'Open', '2025-11-19 05:59:43', '2025-11-19 06:11:17', NULL, NULL, 'John Technician', 0, NULL),
(15, 5, 'hardware', 'IK502', '3', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'qweqwewq', 'eqweqwewqeqwe', '', 'Open', '2025-11-19 06:00:06', '2025-11-19 06:02:12', NULL, NULL, 'John Technician', 0, NULL),
(16, 5, 'hardware', 'IK502', '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'qw', 'qwe', '', 'Open', '2025-11-19 06:58:47', '2025-11-19 07:02:40', NULL, NULL, 'John Technician', 0, NULL),
(17, 5, 'hardware', 'IK501', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'qwr', 'qwr', '', 'Open', '2025-11-19 07:05:36', '2025-11-19 07:06:00', NULL, NULL, 'John Technician', 0, NULL),
(18, 5, 'hardware', 'IK503', '3', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'q', 'weqwe', '', 'Open', '2025-11-19 07:06:10', '2025-11-24 13:29:18', 'John Technician', NULL, NULL, 0, NULL),
(19, 5, 'software', 'IK503', '4', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'qwe', 'qwe', '', 'In Progress', '2025-11-19 07:06:16', '2025-11-24 13:08:45', 'John Technician', NULL, NULL, 0, NULL);

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
(1, 1, '2025-11-21 09:31:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(2, 3, '2025-11-22 12:49:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(3, 3, '2025-11-24 12:59:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(4, 2, '2025-11-24 13:08:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(5, 2, '2025-11-24 13:15:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(6, 3, '2025-11-24 13:15:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(7, 3, '2025-11-26 12:40:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(8, 1, '2025-11-26 12:58:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(9, 1, '2025-11-26 12:58:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(10, 2, '2025-11-26 12:58:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(11, 3, '2025-11-26 13:11:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(12, 2, '2025-11-26 13:14:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(13, 1, '2025-11-26 13:28:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(14, 3, '2025-11-26 15:01:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(15, 5, '2025-11-27 03:50:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(16, 3, '2025-11-27 03:51:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop');

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
(1, 5, 'Ticket #19 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 19, 0, '2025-11-22 14:40:17'),
(2, 2, 'New Ticket Assigned #19', 'You have been assigned to a software ticket: \"qwe\". Please review and take action.', 'info', 'issue', 19, 0, '2025-11-22 14:40:17'),
(3, 5, 'Ticket #19 - Status Updated', 'Your ticket is now being worked on by John Technician.', 'info', 'issue', 19, 0, '2025-11-24 13:08:45'),
(4, 5, 'Ticket #18 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 18, 0, '2025-11-24 13:29:18'),
(5, 2, 'New Ticket Assigned #18', 'You have been assigned to a hardware ticket: \"q\". Please review and take action.', 'info', 'issue', 18, 0, '2025-11-24 13:29:18');

-- --------------------------------------------------------

--
-- Table structure for table `pc_units`
--

CREATE TABLE `pc_units` (
  `id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `building_id` int(11) DEFAULT NULL,
  `terminal_number` varchar(50) DEFAULT NULL,
  `status` enum('Active','Inactive','Under Maintenance','Retired','Archive') NOT NULL DEFAULT 'Active',
  `condition` enum('Excellent','Good','Fair','Poor','Non-Functional') NOT NULL DEFAULT 'Good',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pc_units`
--

INSERT INTO `pc_units` (`id`, `room_id`, `building_id`, `terminal_number`, `status`, `condition`, `notes`, `created_at`, `updated_at`) VALUES
(1, 5, 1, 'TH-01', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:56:26'),
(7, 5, 1, 'TH-02', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:56:26'),
(8, 5, 1, 'TH-03', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:56:26'),
(9, 5, 1, 'TH-04', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:56:26'),
(10, 5, 1, 'TH-05', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:56:26'),
(11, 5, 1, 'TH-06', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:56:26'),
(12, 5, 1, 'TH-07', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:56:16'),
(13, 5, 1, 'TH-08', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:56:16'),
(14, 5, 1, 'TH-09', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:56:16'),
(15, 5, 1, 'TH-10', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:56:16'),
(16, 5, 1, 'TH-11', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:03:49'),
(17, 5, 1, 'TH-12', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:03:49'),
(18, 5, 1, 'TH-13', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:03:49'),
(19, 5, 1, 'TH-14', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:03:49'),
(20, 5, 1, 'TH-15', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:03:49'),
(21, 5, 1, 'TH-16', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(22, 5, 1, 'TH-17', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(23, 5, 1, 'TH-18', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(24, 5, 1, 'TH-19', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(25, 5, 1, 'TH-20', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(26, 5, 1, 'TH-21', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(27, 5, 1, 'TH-22', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(28, 5, 1, 'TH-23', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(29, 5, 1, 'TH-24', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(30, 5, 1, 'TH-25', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(31, 5, 1, 'TH-26', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(32, 5, 1, 'TH-27', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(33, 5, 1, 'TH-28', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(34, 5, 1, 'TH-29', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(35, 5, 1, 'TH-30', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(36, 5, 1, 'TH-31', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(37, 5, 1, 'TH-32', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(38, 5, 1, 'TH-33', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(39, 5, 1, 'TH-34', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(40, 5, 1, 'TH-35', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(41, 5, 1, 'TH-36', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(42, 5, 1, 'TH-37', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(43, 5, 1, 'TH-38', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(44, 5, 1, 'TH-39', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(45, 5, 1, 'TH-40', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(46, 5, 1, 'TH-41', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(47, 5, 1, 'TH-42', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(48, 5, 1, 'TH-43', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(49, 5, 1, 'TH-44', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(50, 5, 1, 'TH-45', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(51, 5, 1, 'TH-46', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(52, 5, 1, 'TH-47', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(53, 5, 1, 'TH-48', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(54, 5, 1, 'TH-49', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(55, 5, 1, 'TH-50', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `building_id` int(11) DEFAULT NULL COMMENT 'Foreign key to buildings table',
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `building_id`, `name`, `created_at`) VALUES
(5, 1, 'IK501', '2025-11-22 14:24:13'),
(6, 2, 'IL501', '2025-11-22 14:36:23');

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
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `id_number`, `password`, `full_name`, `email`, `role`, `status`, `created_at`, `updated_at`, `last_login`) VALUES
(1, '22-0306', '$2y$10$NrA9Ob9vAY4MF436ROTd2ecE2iYcVMFWCtbEGcTdfD7zH.ErqYCV6', 'Admin User', 'admin@ams.edu', 'Administrator', 'Active', '2025-10-28 21:34:53', '2025-11-26 21:28:32', '2025-11-26 21:28:32'),
(2, '22-0307', '$2y$10$on5Q98KdJ3bnnvysSRbsBePxalUzs62G8F76Yk7pZLl8sDdW5WVUu', 'John Technician', 'technician@ams.edu', 'Technician', 'Active', '2025-10-28 21:34:53', '2025-11-26 21:14:12', '2025-11-26 21:14:12'),
(3, '22-0308', '$2y$10$bEBBQUTMdL1tBiviKwv0DubLn8QbWojiqmTVqUJzjxMp/xYH3SFFm', 'Maria Lab Staff', 'labstaff@ams.edu', 'Laboratory Staff', 'Active', '2025-10-28 21:34:53', '2025-11-27 11:51:32', '2025-11-27 11:51:32'),
(4, 'F2024-001', '12345', 'Dr. Jane Faculty', 'faculty@ams.edu', 'Faculty', 'Active', '2025-10-28 21:34:53', '2025-10-28 21:40:57', NULL),
(5, '22-0305', '$2y$10$clCXfgzls8VHen2k.aF6TuvTZ34Ntl.T3oWxfhzTn67A5mEEjI1QW', 'Student One', 'student1@ams.edu', 'Student', 'Active', '2025-10-28 21:34:53', '2025-11-27 11:50:24', '2025-11-27 11:50:24'),
(6, 'S2024-002', '12345', 'Student Two', 'student2@ams.edu', 'Student', 'Active', '2025-10-28 21:34:53', '2025-11-21 17:32:50', NULL),
(29, '22-0632', '$2y$10$TGmP8M7lub8Rgxc.RDvUkuLXEX38Gg.eybzS1/WXKECN85tXKkpO6', 'qweqwe qweqwe', 'sd@gmail.com', 'Administrator', 'Active', '2025-11-20 21:04:20', '2025-11-20 21:14:42', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action` (`action`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `entity_type` (`entity_type`,`entity_id`);

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pc_unit_id` (`pc_unit_id`);

--
-- Indexes for table `buildings`
--
ALTER TABLE `buildings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `issues`
--
ALTER TABLE `issues`
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
-- Indexes for table `pc_units`
--
ALTER TABLE `pc_units`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `building_id` (`building_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rooms_building` (`building_id`);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `buildings`
--
ALTER TABLE `buildings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `issues`
--
ALTER TABLE `issues`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `login_history`
--
ALTER TABLE `login_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pc_units`
--
ALTER TABLE `pc_units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`pc_unit_id`) REFERENCES `pc_units` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pc_units`
--
ALTER TABLE `pc_units`
  ADD CONSTRAINT `pc_units_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pc_units_ibfk_2` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `fk_rooms_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
