-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 28, 2025 at 08:30 AM
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
(376, '11-28-2025-MOUSE-IK501-001', 'MOUSE #1', 'Hardware', '6', '', '', NULL, NULL, 7, 56, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-001%22%2C%22asset_name%22%3A%22MOUSE+%231%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22', NULL, 3, NULL, '2025-11-28 07:20:59', '2025-11-28 07:25:13'),
(377, '11-28-2025-MOUSE-IK501-002', 'MOUSE #2', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-002%22%2C%22asset_name%22%3A%22MOUSE+%232%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22', NULL, 3, NULL, '2025-11-28 07:20:59', '2025-11-28 07:20:59'),
(378, '11-28-2025-MOUSE-IK501-003', 'MOUSE #3', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-003%22%2C%22asset_name%22%3A%22MOUSE+%233%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22', NULL, 3, NULL, '2025-11-28 07:20:59', '2025-11-28 07:20:59'),
(379, '11-28-2025-MOUSE-IK501-004', 'MOUSE #4', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-004%22%2C%22asset_name%22%3A%22MOUSE+%234%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22', NULL, 3, NULL, '2025-11-28 07:20:59', '2025-11-28 07:20:59'),
(380, '11-28-2025-MOUSE-IK501-005', 'MOUSE #5', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-005%22%2C%22asset_name%22%3A%22MOUSE+%235%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22', NULL, 3, NULL, '2025-11-28 07:20:59', '2025-11-28 07:20:59'),
(381, '11-28-2025-MOUSE-IK501-006', 'MOUSE #6', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-006%22%2C%22asset_name%22%3A%22MOUSE+%236%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22', NULL, 3, NULL, '2025-11-28 07:20:59', '2025-11-28 07:20:59'),
(382, '11-28-2025-MOUSE-IK501-007', 'MOUSE #7', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-007%22%2C%22asset_name%22%3A%22MOUSE+%237%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22', NULL, 3, NULL, '2025-11-28 07:20:59', '2025-11-28 07:20:59'),
(383, '11-28-2025-MOUSE-IK501-008', 'MOUSE #8', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-008%22%2C%22asset_name%22%3A%22MOUSE+%238%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22', NULL, 3, NULL, '2025-11-28 07:20:59', '2025-11-28 07:20:59'),
(384, '11-28-2025-MOUSE-IK501-009', 'MOUSE #9', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-009%22%2C%22asset_name%22%3A%22MOUSE+%239%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22', NULL, 3, NULL, '2025-11-28 07:20:59', '2025-11-28 07:20:59'),
(385, '11-28-2025-MOUSE-IK501-010', 'MOUSE #10', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-010%22%2C%22asset_name%22%3A%22MOUSE+%2310%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:20:59', '2025-11-28 07:20:59'),
(386, '11-28-2025-MOUSE-IK501-011', 'MOUSE #11', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-011%22%2C%22asset_name%22%3A%22MOUSE+%2311%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:20:59', '2025-11-28 07:20:59'),
(387, '11-28-2025-MOUSE-IK501-012', 'MOUSE #12', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-012%22%2C%22asset_name%22%3A%22MOUSE+%2312%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(388, '11-28-2025-MOUSE-IK501-013', 'MOUSE #13', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-013%22%2C%22asset_name%22%3A%22MOUSE+%2313%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(389, '11-28-2025-MOUSE-IK501-014', 'MOUSE #14', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-014%22%2C%22asset_name%22%3A%22MOUSE+%2314%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(390, '11-28-2025-MOUSE-IK501-015', 'MOUSE #15', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-015%22%2C%22asset_name%22%3A%22MOUSE+%2315%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(391, '11-28-2025-MOUSE-IK501-016', 'MOUSE #16', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-016%22%2C%22asset_name%22%3A%22MOUSE+%2316%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(392, '11-28-2025-MOUSE-IK501-017', 'MOUSE #17', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-017%22%2C%22asset_name%22%3A%22MOUSE+%2317%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(393, '11-28-2025-MOUSE-IK501-018', 'MOUSE #18', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-018%22%2C%22asset_name%22%3A%22MOUSE+%2318%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(394, '11-28-2025-MOUSE-IK501-019', 'MOUSE #19', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-019%22%2C%22asset_name%22%3A%22MOUSE+%2319%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(395, '11-28-2025-MOUSE-IK501-020', 'MOUSE #20', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-020%22%2C%22asset_name%22%3A%22MOUSE+%2320%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(396, '11-28-2025-MOUSE-IK501-021', 'MOUSE #21', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-021%22%2C%22asset_name%22%3A%22MOUSE+%2321%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(397, '11-28-2025-MOUSE-IK501-022', 'MOUSE #22', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-022%22%2C%22asset_name%22%3A%22MOUSE+%2322%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(398, '11-28-2025-MOUSE-IK501-023', 'MOUSE #23', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-023%22%2C%22asset_name%22%3A%22MOUSE+%2323%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(399, '11-28-2025-MOUSE-IK501-024', 'MOUSE #24', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-024%22%2C%22asset_name%22%3A%22MOUSE+%2324%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(400, '11-28-2025-MOUSE-IK501-025', 'MOUSE #25', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-025%22%2C%22asset_name%22%3A%22MOUSE+%2325%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(401, '11-28-2025-MOUSE-IK501-026', 'MOUSE #26', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-026%22%2C%22asset_name%22%3A%22MOUSE+%2326%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(402, '11-28-2025-MOUSE-IK501-027', 'MOUSE #27', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-027%22%2C%22asset_name%22%3A%22MOUSE+%2327%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(403, '11-28-2025-MOUSE-IK501-028', 'MOUSE #28', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-028%22%2C%22asset_name%22%3A%22MOUSE+%2328%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(404, '11-28-2025-MOUSE-IK501-029', 'MOUSE #29', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-029%22%2C%22asset_name%22%3A%22MOUSE+%2329%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(405, '11-28-2025-MOUSE-IK501-030', 'MOUSE #30', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-030%22%2C%22asset_name%22%3A%22MOUSE+%2330%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(406, '11-28-2025-MOUSE-IK501-031', 'MOUSE #31', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-031%22%2C%22asset_name%22%3A%22MOUSE+%2331%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(407, '11-28-2025-MOUSE-IK501-032', 'MOUSE #32', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-032%22%2C%22asset_name%22%3A%22MOUSE+%2332%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(408, '11-28-2025-MOUSE-IK501-033', 'MOUSE #33', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-033%22%2C%22asset_name%22%3A%22MOUSE+%2333%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(409, '11-28-2025-MOUSE-IK501-034', 'MOUSE #34', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-034%22%2C%22asset_name%22%3A%22MOUSE+%2334%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(410, '11-28-2025-MOUSE-IK501-035', 'MOUSE #35', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-035%22%2C%22asset_name%22%3A%22MOUSE+%2335%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(411, '11-28-2025-MOUSE-IK501-036', 'MOUSE #36', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-036%22%2C%22asset_name%22%3A%22MOUSE+%2336%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(412, '11-28-2025-MOUSE-IK501-037', 'MOUSE #37', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-037%22%2C%22asset_name%22%3A%22MOUSE+%2337%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(413, '11-28-2025-MOUSE-IK501-038', 'MOUSE #38', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-038%22%2C%22asset_name%22%3A%22MOUSE+%2338%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(414, '11-28-2025-MOUSE-IK501-039', 'MOUSE #39', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-039%22%2C%22asset_name%22%3A%22MOUSE+%2339%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(415, '11-28-2025-MOUSE-IK501-040', 'MOUSE #40', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-040%22%2C%22asset_name%22%3A%22MOUSE+%2340%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(416, '11-28-2025-MOUSE-IK501-041', 'MOUSE #41', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-041%22%2C%22asset_name%22%3A%22MOUSE+%2341%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(417, '11-28-2025-MOUSE-IK501-042', 'MOUSE #42', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-042%22%2C%22asset_name%22%3A%22MOUSE+%2342%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(418, '11-28-2025-MOUSE-IK501-043', 'MOUSE #43', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-043%22%2C%22asset_name%22%3A%22MOUSE+%2343%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(419, '11-28-2025-MOUSE-IK501-044', 'MOUSE #44', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-044%22%2C%22asset_name%22%3A%22MOUSE+%2344%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(420, '11-28-2025-MOUSE-IK501-045', 'MOUSE #45', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-045%22%2C%22asset_name%22%3A%22MOUSE+%2345%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(421, '11-28-2025-MOUSE-IK501-046', 'MOUSE #46', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-046%22%2C%22asset_name%22%3A%22MOUSE+%2346%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(422, '11-28-2025-MOUSE-IK501-047', 'MOUSE #47', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-047%22%2C%22asset_name%22%3A%22MOUSE+%2347%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(423, '11-28-2025-MOUSE-IK501-048', 'MOUSE #48', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-048%22%2C%22asset_name%22%3A%22MOUSE+%2348%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(424, '11-28-2025-MOUSE-IK501-049', 'MOUSE #49', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-049%22%2C%22asset_name%22%3A%22MOUSE+%2349%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(425, '11-28-2025-MOUSE-IK501-050', 'MOUSE #50', 'Hardware', '6', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MOUSE-IK501-050%22%2C%22asset_name%22%3A%22MOUSE+%2350%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%2', NULL, 3, NULL, '2025-11-28 07:21:00', '2025-11-28 07:21:00'),
(426, '11-28-2025-MONITOR-IK501-001', 'MONITOR #1', 'Hardware', '4', '', '', NULL, NULL, 7, 56, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-001%22%2C%22asset_name%22%3A%22MONITOR+%231%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bran', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:29:06'),
(427, '11-28-2025-MONITOR-IK501-002', 'MONITOR #2', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-002%22%2C%22asset_name%22%3A%22MONITOR+%232%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bran', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(428, '11-28-2025-MONITOR-IK501-003', 'MONITOR #3', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-003%22%2C%22asset_name%22%3A%22MONITOR+%233%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bran', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(429, '11-28-2025-MONITOR-IK501-004', 'MONITOR #4', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-004%22%2C%22asset_name%22%3A%22MONITOR+%234%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bran', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(430, '11-28-2025-MONITOR-IK501-005', 'MONITOR #5', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-005%22%2C%22asset_name%22%3A%22MONITOR+%235%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bran', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(431, '11-28-2025-MONITOR-IK501-006', 'MONITOR #6', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-006%22%2C%22asset_name%22%3A%22MONITOR+%236%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bran', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(432, '11-28-2025-MONITOR-IK501-007', 'MONITOR #7', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-007%22%2C%22asset_name%22%3A%22MONITOR+%237%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bran', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(433, '11-28-2025-MONITOR-IK501-008', 'MONITOR #8', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-008%22%2C%22asset_name%22%3A%22MONITOR+%238%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bran', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(434, '11-28-2025-MONITOR-IK501-009', 'MONITOR #9', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-009%22%2C%22asset_name%22%3A%22MONITOR+%239%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bran', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(435, '11-28-2025-MONITOR-IK501-010', 'MONITOR #10', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-010%22%2C%22asset_name%22%3A%22MONITOR+%2310%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(436, '11-28-2025-MONITOR-IK501-011', 'MONITOR #11', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-011%22%2C%22asset_name%22%3A%22MONITOR+%2311%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(437, '11-28-2025-MONITOR-IK501-012', 'MONITOR #12', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-012%22%2C%22asset_name%22%3A%22MONITOR+%2312%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(438, '11-28-2025-MONITOR-IK501-013', 'MONITOR #13', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-013%22%2C%22asset_name%22%3A%22MONITOR+%2313%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(439, '11-28-2025-MONITOR-IK501-014', 'MONITOR #14', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-014%22%2C%22asset_name%22%3A%22MONITOR+%2314%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(440, '11-28-2025-MONITOR-IK501-015', 'MONITOR #15', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-015%22%2C%22asset_name%22%3A%22MONITOR+%2315%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(441, '11-28-2025-MONITOR-IK501-016', 'MONITOR #16', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-016%22%2C%22asset_name%22%3A%22MONITOR+%2316%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(442, '11-28-2025-MONITOR-IK501-017', 'MONITOR #17', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-017%22%2C%22asset_name%22%3A%22MONITOR+%2317%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(443, '11-28-2025-MONITOR-IK501-018', 'MONITOR #18', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-018%22%2C%22asset_name%22%3A%22MONITOR+%2318%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(444, '11-28-2025-MONITOR-IK501-019', 'MONITOR #19', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-019%22%2C%22asset_name%22%3A%22MONITOR+%2319%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(445, '11-28-2025-MONITOR-IK501-020', 'MONITOR #20', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-020%22%2C%22asset_name%22%3A%22MONITOR+%2320%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(446, '11-28-2025-MONITOR-IK501-021', 'MONITOR #21', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-021%22%2C%22asset_name%22%3A%22MONITOR+%2321%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(447, '11-28-2025-MONITOR-IK501-022', 'MONITOR #22', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-022%22%2C%22asset_name%22%3A%22MONITOR+%2322%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(448, '11-28-2025-MONITOR-IK501-023', 'MONITOR #23', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-023%22%2C%22asset_name%22%3A%22MONITOR+%2323%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(449, '11-28-2025-MONITOR-IK501-024', 'MONITOR #24', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-024%22%2C%22asset_name%22%3A%22MONITOR+%2324%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(450, '11-28-2025-MONITOR-IK501-025', 'MONITOR #25', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-025%22%2C%22asset_name%22%3A%22MONITOR+%2325%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(451, '11-28-2025-MONITOR-IK501-026', 'MONITOR #26', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-026%22%2C%22asset_name%22%3A%22MONITOR+%2326%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(452, '11-28-2025-MONITOR-IK501-027', 'MONITOR #27', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-027%22%2C%22asset_name%22%3A%22MONITOR+%2327%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(453, '11-28-2025-MONITOR-IK501-028', 'MONITOR #28', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-028%22%2C%22asset_name%22%3A%22MONITOR+%2328%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(454, '11-28-2025-MONITOR-IK501-029', 'MONITOR #29', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-029%22%2C%22asset_name%22%3A%22MONITOR+%2329%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(455, '11-28-2025-MONITOR-IK501-030', 'MONITOR #30', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-030%22%2C%22asset_name%22%3A%22MONITOR+%2330%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(456, '11-28-2025-MONITOR-IK501-031', 'MONITOR #31', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-031%22%2C%22asset_name%22%3A%22MONITOR+%2331%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(457, '11-28-2025-MONITOR-IK501-032', 'MONITOR #32', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-032%22%2C%22asset_name%22%3A%22MONITOR+%2332%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(458, '11-28-2025-MONITOR-IK501-033', 'MONITOR #33', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-033%22%2C%22asset_name%22%3A%22MONITOR+%2333%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(459, '11-28-2025-MONITOR-IK501-034', 'MONITOR #34', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-034%22%2C%22asset_name%22%3A%22MONITOR+%2334%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(460, '11-28-2025-MONITOR-IK501-035', 'MONITOR #35', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-035%22%2C%22asset_name%22%3A%22MONITOR+%2335%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(461, '11-28-2025-MONITOR-IK501-036', 'MONITOR #36', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-036%22%2C%22asset_name%22%3A%22MONITOR+%2336%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(462, '11-28-2025-MONITOR-IK501-037', 'MONITOR #37', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-037%22%2C%22asset_name%22%3A%22MONITOR+%2337%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(463, '11-28-2025-MONITOR-IK501-038', 'MONITOR #38', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-038%22%2C%22asset_name%22%3A%22MONITOR+%2338%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(464, '11-28-2025-MONITOR-IK501-039', 'MONITOR #39', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-039%22%2C%22asset_name%22%3A%22MONITOR+%2339%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(465, '11-28-2025-MONITOR-IK501-040', 'MONITOR #40', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-040%22%2C%22asset_name%22%3A%22MONITOR+%2340%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(466, '11-28-2025-MONITOR-IK501-041', 'MONITOR #41', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-041%22%2C%22asset_name%22%3A%22MONITOR+%2341%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(467, '11-28-2025-MONITOR-IK501-042', 'MONITOR #42', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-042%22%2C%22asset_name%22%3A%22MONITOR+%2342%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(468, '11-28-2025-MONITOR-IK501-043', 'MONITOR #43', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-043%22%2C%22asset_name%22%3A%22MONITOR+%2343%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(469, '11-28-2025-MONITOR-IK501-044', 'MONITOR #44', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-044%22%2C%22asset_name%22%3A%22MONITOR+%2344%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(470, '11-28-2025-MONITOR-IK501-045', 'MONITOR #45', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-045%22%2C%22asset_name%22%3A%22MONITOR+%2345%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47');
INSERT INTO `assets` (`id`, `asset_tag`, `asset_name`, `asset_type`, `category`, `brand`, `model`, `serial_number`, `specifications`, `room_id`, `pc_unit_id`, `location`, `terminal_number`, `purchase_date`, `purchase_cost`, `supplier`, `warranty_expiry`, `status`, `condition`, `is_borrowable`, `assigned_to`, `assigned_date`, `assigned_by`, `last_maintenance_date`, `next_maintenance_date`, `maintenance_notes`, `notes`, `qr_code`, `image`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(471, '11-28-2025-MONITOR-IK501-046', 'MONITOR #46', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-046%22%2C%22asset_name%22%3A%22MONITOR+%2346%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(472, '11-28-2025-MONITOR-IK501-047', 'MONITOR #47', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-047%22%2C%22asset_name%22%3A%22MONITOR+%2347%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(473, '11-28-2025-MONITOR-IK501-048', 'MONITOR #48', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-048%22%2C%22asset_name%22%3A%22MONITOR+%2348%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(474, '11-28-2025-MONITOR-IK501-049', 'MONITOR #49', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-049%22%2C%22asset_name%22%3A%22MONITOR+%2349%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(475, '11-28-2025-MONITOR-IK501-050', 'MONITOR #50', 'Hardware', '4', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-MONITOR-IK501-050%22%2C%22asset_name%22%3A%22MONITOR+%2350%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22bra', NULL, 3, NULL, '2025-11-28 07:25:47', '2025-11-28 07:25:47'),
(476, '11-28-2025-KEYBOARD-IK501-001', 'KEYBOARD #1', 'Hardware', '5', '', '', NULL, NULL, 7, 56, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-001%22%2C%22asset_name%22%3A%22KEYBOARD+%231%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22br', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:29:00'),
(477, '11-28-2025-KEYBOARD-IK501-002', 'KEYBOARD #2', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-002%22%2C%22asset_name%22%3A%22KEYBOARD+%232%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22br', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(478, '11-28-2025-KEYBOARD-IK501-003', 'KEYBOARD #3', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-003%22%2C%22asset_name%22%3A%22KEYBOARD+%233%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22br', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(479, '11-28-2025-KEYBOARD-IK501-004', 'KEYBOARD #4', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-004%22%2C%22asset_name%22%3A%22KEYBOARD+%234%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22br', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(480, '11-28-2025-KEYBOARD-IK501-005', 'KEYBOARD #5', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-005%22%2C%22asset_name%22%3A%22KEYBOARD+%235%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22br', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(481, '11-28-2025-KEYBOARD-IK501-006', 'KEYBOARD #6', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-006%22%2C%22asset_name%22%3A%22KEYBOARD+%236%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22br', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(482, '11-28-2025-KEYBOARD-IK501-007', 'KEYBOARD #7', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-007%22%2C%22asset_name%22%3A%22KEYBOARD+%237%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22br', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(483, '11-28-2025-KEYBOARD-IK501-008', 'KEYBOARD #8', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-008%22%2C%22asset_name%22%3A%22KEYBOARD+%238%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22br', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(484, '11-28-2025-KEYBOARD-IK501-009', 'KEYBOARD #9', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-009%22%2C%22asset_name%22%3A%22KEYBOARD+%239%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22br', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(485, '11-28-2025-KEYBOARD-IK501-010', 'KEYBOARD #10', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-010%22%2C%22asset_name%22%3A%22KEYBOARD+%2310%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(486, '11-28-2025-KEYBOARD-IK501-011', 'KEYBOARD #11', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-011%22%2C%22asset_name%22%3A%22KEYBOARD+%2311%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(487, '11-28-2025-KEYBOARD-IK501-012', 'KEYBOARD #12', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-012%22%2C%22asset_name%22%3A%22KEYBOARD+%2312%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(488, '11-28-2025-KEYBOARD-IK501-013', 'KEYBOARD #13', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-013%22%2C%22asset_name%22%3A%22KEYBOARD+%2313%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(489, '11-28-2025-KEYBOARD-IK501-014', 'KEYBOARD #14', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-014%22%2C%22asset_name%22%3A%22KEYBOARD+%2314%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(490, '11-28-2025-KEYBOARD-IK501-015', 'KEYBOARD #15', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-015%22%2C%22asset_name%22%3A%22KEYBOARD+%2315%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(491, '11-28-2025-KEYBOARD-IK501-016', 'KEYBOARD #16', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-016%22%2C%22asset_name%22%3A%22KEYBOARD+%2316%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(492, '11-28-2025-KEYBOARD-IK501-017', 'KEYBOARD #17', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-017%22%2C%22asset_name%22%3A%22KEYBOARD+%2317%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(493, '11-28-2025-KEYBOARD-IK501-018', 'KEYBOARD #18', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-018%22%2C%22asset_name%22%3A%22KEYBOARD+%2318%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(494, '11-28-2025-KEYBOARD-IK501-019', 'KEYBOARD #19', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-019%22%2C%22asset_name%22%3A%22KEYBOARD+%2319%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(495, '11-28-2025-KEYBOARD-IK501-020', 'KEYBOARD #20', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-020%22%2C%22asset_name%22%3A%22KEYBOARD+%2320%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(496, '11-28-2025-KEYBOARD-IK501-021', 'KEYBOARD #21', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-021%22%2C%22asset_name%22%3A%22KEYBOARD+%2321%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(497, '11-28-2025-KEYBOARD-IK501-022', 'KEYBOARD #22', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-022%22%2C%22asset_name%22%3A%22KEYBOARD+%2322%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(498, '11-28-2025-KEYBOARD-IK501-023', 'KEYBOARD #23', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-023%22%2C%22asset_name%22%3A%22KEYBOARD+%2323%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(499, '11-28-2025-KEYBOARD-IK501-024', 'KEYBOARD #24', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-024%22%2C%22asset_name%22%3A%22KEYBOARD+%2324%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(500, '11-28-2025-KEYBOARD-IK501-025', 'KEYBOARD #25', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-025%22%2C%22asset_name%22%3A%22KEYBOARD+%2325%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(501, '11-28-2025-KEYBOARD-IK501-026', 'KEYBOARD #26', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-026%22%2C%22asset_name%22%3A%22KEYBOARD+%2326%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(502, '11-28-2025-KEYBOARD-IK501-027', 'KEYBOARD #27', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-027%22%2C%22asset_name%22%3A%22KEYBOARD+%2327%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(503, '11-28-2025-KEYBOARD-IK501-028', 'KEYBOARD #28', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-028%22%2C%22asset_name%22%3A%22KEYBOARD+%2328%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(504, '11-28-2025-KEYBOARD-IK501-029', 'KEYBOARD #29', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-029%22%2C%22asset_name%22%3A%22KEYBOARD+%2329%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(505, '11-28-2025-KEYBOARD-IK501-030', 'KEYBOARD #30', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-030%22%2C%22asset_name%22%3A%22KEYBOARD+%2330%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(506, '11-28-2025-KEYBOARD-IK501-031', 'KEYBOARD #31', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-031%22%2C%22asset_name%22%3A%22KEYBOARD+%2331%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(507, '11-28-2025-KEYBOARD-IK501-032', 'KEYBOARD #32', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-032%22%2C%22asset_name%22%3A%22KEYBOARD+%2332%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(508, '11-28-2025-KEYBOARD-IK501-033', 'KEYBOARD #33', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-033%22%2C%22asset_name%22%3A%22KEYBOARD+%2333%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(509, '11-28-2025-KEYBOARD-IK501-034', 'KEYBOARD #34', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-034%22%2C%22asset_name%22%3A%22KEYBOARD+%2334%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(510, '11-28-2025-KEYBOARD-IK501-035', 'KEYBOARD #35', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-035%22%2C%22asset_name%22%3A%22KEYBOARD+%2335%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(511, '11-28-2025-KEYBOARD-IK501-036', 'KEYBOARD #36', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-036%22%2C%22asset_name%22%3A%22KEYBOARD+%2336%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(512, '11-28-2025-KEYBOARD-IK501-037', 'KEYBOARD #37', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-037%22%2C%22asset_name%22%3A%22KEYBOARD+%2337%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(513, '11-28-2025-KEYBOARD-IK501-038', 'KEYBOARD #38', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-038%22%2C%22asset_name%22%3A%22KEYBOARD+%2338%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(514, '11-28-2025-KEYBOARD-IK501-039', 'KEYBOARD #39', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-039%22%2C%22asset_name%22%3A%22KEYBOARD+%2339%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(515, '11-28-2025-KEYBOARD-IK501-040', 'KEYBOARD #40', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-040%22%2C%22asset_name%22%3A%22KEYBOARD+%2340%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(516, '11-28-2025-KEYBOARD-IK501-041', 'KEYBOARD #41', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-041%22%2C%22asset_name%22%3A%22KEYBOARD+%2341%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(517, '11-28-2025-KEYBOARD-IK501-042', 'KEYBOARD #42', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-042%22%2C%22asset_name%22%3A%22KEYBOARD+%2342%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(518, '11-28-2025-KEYBOARD-IK501-043', 'KEYBOARD #43', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-043%22%2C%22asset_name%22%3A%22KEYBOARD+%2343%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(519, '11-28-2025-KEYBOARD-IK501-044', 'KEYBOARD #44', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-044%22%2C%22asset_name%22%3A%22KEYBOARD+%2344%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(520, '11-28-2025-KEYBOARD-IK501-045', 'KEYBOARD #45', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-045%22%2C%22asset_name%22%3A%22KEYBOARD+%2345%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(521, '11-28-2025-KEYBOARD-IK501-046', 'KEYBOARD #46', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-046%22%2C%22asset_name%22%3A%22KEYBOARD+%2346%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(522, '11-28-2025-KEYBOARD-IK501-047', 'KEYBOARD #47', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-047%22%2C%22asset_name%22%3A%22KEYBOARD+%2347%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(523, '11-28-2025-KEYBOARD-IK501-048', 'KEYBOARD #48', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-048%22%2C%22asset_name%22%3A%22KEYBOARD+%2348%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(524, '11-28-2025-KEYBOARD-IK501-049', 'KEYBOARD #49', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-049%22%2C%22asset_name%22%3A%22KEYBOARD+%2349%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(525, '11-28-2025-KEYBOARD-IK501-050', 'KEYBOARD #50', 'Hardware', '5', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-KEYBOARD-IK501-050%22%2C%22asset_name%22%3A%22KEYBOARD+%2350%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22b', NULL, 3, NULL, '2025-11-28 07:26:19', '2025-11-28 07:26:19'),
(526, '11-28-2025-RAM-IK501-001', 'RAM #1', 'Hardware', '3', '', '', NULL, NULL, 7, 56, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-001%22%2C%22asset_name%22%3A%22RAM+%231%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A%', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:29:33'),
(527, '11-28-2025-RAM-IK501-002', 'RAM #2', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-002%22%2C%22asset_name%22%3A%22RAM+%232%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A%', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(528, '11-28-2025-RAM-IK501-003', 'RAM #3', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-003%22%2C%22asset_name%22%3A%22RAM+%233%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A%', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(529, '11-28-2025-RAM-IK501-004', 'RAM #4', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-004%22%2C%22asset_name%22%3A%22RAM+%234%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A%', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(530, '11-28-2025-RAM-IK501-005', 'RAM #5', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-005%22%2C%22asset_name%22%3A%22RAM+%235%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A%', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(531, '11-28-2025-RAM-IK501-006', 'RAM #6', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-006%22%2C%22asset_name%22%3A%22RAM+%236%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A%', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(532, '11-28-2025-RAM-IK501-007', 'RAM #7', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-007%22%2C%22asset_name%22%3A%22RAM+%237%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A%', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(533, '11-28-2025-RAM-IK501-008', 'RAM #8', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-008%22%2C%22asset_name%22%3A%22RAM+%238%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A%', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(534, '11-28-2025-RAM-IK501-009', 'RAM #9', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-009%22%2C%22asset_name%22%3A%22RAM+%239%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A%', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(535, '11-28-2025-RAM-IK501-010', 'RAM #10', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-010%22%2C%22asset_name%22%3A%22RAM+%2310%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(536, '11-28-2025-RAM-IK501-011', 'RAM #11', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-011%22%2C%22asset_name%22%3A%22RAM+%2311%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(537, '11-28-2025-RAM-IK501-012', 'RAM #12', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-012%22%2C%22asset_name%22%3A%22RAM+%2312%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(538, '11-28-2025-RAM-IK501-013', 'RAM #13', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-013%22%2C%22asset_name%22%3A%22RAM+%2313%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(539, '11-28-2025-RAM-IK501-014', 'RAM #14', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-014%22%2C%22asset_name%22%3A%22RAM+%2314%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(540, '11-28-2025-RAM-IK501-015', 'RAM #15', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-015%22%2C%22asset_name%22%3A%22RAM+%2315%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(541, '11-28-2025-RAM-IK501-016', 'RAM #16', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-016%22%2C%22asset_name%22%3A%22RAM+%2316%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(542, '11-28-2025-RAM-IK501-017', 'RAM #17', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-017%22%2C%22asset_name%22%3A%22RAM+%2317%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(543, '11-28-2025-RAM-IK501-018', 'RAM #18', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-018%22%2C%22asset_name%22%3A%22RAM+%2318%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(544, '11-28-2025-RAM-IK501-019', 'RAM #19', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-019%22%2C%22asset_name%22%3A%22RAM+%2319%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(545, '11-28-2025-RAM-IK501-020', 'RAM #20', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-020%22%2C%22asset_name%22%3A%22RAM+%2320%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(546, '11-28-2025-RAM-IK501-021', 'RAM #21', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-021%22%2C%22asset_name%22%3A%22RAM+%2321%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(547, '11-28-2025-RAM-IK501-022', 'RAM #22', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-022%22%2C%22asset_name%22%3A%22RAM+%2322%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(548, '11-28-2025-RAM-IK501-023', 'RAM #23', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-023%22%2C%22asset_name%22%3A%22RAM+%2323%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(549, '11-28-2025-RAM-IK501-024', 'RAM #24', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-024%22%2C%22asset_name%22%3A%22RAM+%2324%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(550, '11-28-2025-RAM-IK501-025', 'RAM #25', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-025%22%2C%22asset_name%22%3A%22RAM+%2325%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(551, '11-28-2025-RAM-IK501-026', 'RAM #26', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-026%22%2C%22asset_name%22%3A%22RAM+%2326%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(552, '11-28-2025-RAM-IK501-027', 'RAM #27', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-027%22%2C%22asset_name%22%3A%22RAM+%2327%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(553, '11-28-2025-RAM-IK501-028', 'RAM #28', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-028%22%2C%22asset_name%22%3A%22RAM+%2328%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(554, '11-28-2025-RAM-IK501-029', 'RAM #29', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-029%22%2C%22asset_name%22%3A%22RAM+%2329%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(555, '11-28-2025-RAM-IK501-030', 'RAM #30', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-030%22%2C%22asset_name%22%3A%22RAM+%2330%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(556, '11-28-2025-RAM-IK501-031', 'RAM #31', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-031%22%2C%22asset_name%22%3A%22RAM+%2331%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(557, '11-28-2025-RAM-IK501-032', 'RAM #32', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-032%22%2C%22asset_name%22%3A%22RAM+%2332%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(558, '11-28-2025-RAM-IK501-033', 'RAM #33', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-033%22%2C%22asset_name%22%3A%22RAM+%2333%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(559, '11-28-2025-RAM-IK501-034', 'RAM #34', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-034%22%2C%22asset_name%22%3A%22RAM+%2334%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(560, '11-28-2025-RAM-IK501-035', 'RAM #35', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-035%22%2C%22asset_name%22%3A%22RAM+%2335%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(561, '11-28-2025-RAM-IK501-036', 'RAM #36', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-036%22%2C%22asset_name%22%3A%22RAM+%2336%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(562, '11-28-2025-RAM-IK501-037', 'RAM #37', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-037%22%2C%22asset_name%22%3A%22RAM+%2337%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(563, '11-28-2025-RAM-IK501-038', 'RAM #38', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-038%22%2C%22asset_name%22%3A%22RAM+%2338%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(564, '11-28-2025-RAM-IK501-039', 'RAM #39', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-039%22%2C%22asset_name%22%3A%22RAM+%2339%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(565, '11-28-2025-RAM-IK501-040', 'RAM #40', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-040%22%2C%22asset_name%22%3A%22RAM+%2340%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49');
INSERT INTO `assets` (`id`, `asset_tag`, `asset_name`, `asset_type`, `category`, `brand`, `model`, `serial_number`, `specifications`, `room_id`, `pc_unit_id`, `location`, `terminal_number`, `purchase_date`, `purchase_cost`, `supplier`, `warranty_expiry`, `status`, `condition`, `is_borrowable`, `assigned_to`, `assigned_date`, `assigned_by`, `last_maintenance_date`, `next_maintenance_date`, `maintenance_notes`, `notes`, `qr_code`, `image`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(566, '11-28-2025-RAM-IK501-041', 'RAM #41', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-041%22%2C%22asset_name%22%3A%22RAM+%2341%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(567, '11-28-2025-RAM-IK501-042', 'RAM #42', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-042%22%2C%22asset_name%22%3A%22RAM+%2342%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(568, '11-28-2025-RAM-IK501-043', 'RAM #43', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-043%22%2C%22asset_name%22%3A%22RAM+%2343%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(569, '11-28-2025-RAM-IK501-044', 'RAM #44', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-044%22%2C%22asset_name%22%3A%22RAM+%2344%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(570, '11-28-2025-RAM-IK501-045', 'RAM #45', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-045%22%2C%22asset_name%22%3A%22RAM+%2345%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(571, '11-28-2025-RAM-IK501-046', 'RAM #46', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-046%22%2C%22asset_name%22%3A%22RAM+%2346%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(572, '11-28-2025-RAM-IK501-047', 'RAM #47', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-047%22%2C%22asset_name%22%3A%22RAM+%2347%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(573, '11-28-2025-RAM-IK501-048', 'RAM #48', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-048%22%2C%22asset_name%22%3A%22RAM+%2348%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(574, '11-28-2025-RAM-IK501-049', 'RAM #49', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-049%22%2C%22asset_name%22%3A%22RAM+%2349%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49'),
(575, '11-28-2025-RAM-IK501-050', 'RAM #50', 'Hardware', '3', '', '', NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Good', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2211-28-2025-RAM-IK501-050%22%2C%22asset_name%22%3A%22RAM+%2350%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK501%22%2C%22brand%22%3A', NULL, 3, NULL, '2025-11-28 07:26:49', '2025-11-28 07:26:49');

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
-- Table structure for table `asset_categories`
--

CREATE TABLE `asset_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `asset_categories`
--

INSERT INTO `asset_categories` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'LAPTOP', '2025-11-28 03:37:54', '2025-11-28 03:37:54'),
(2, 'PC', '2025-11-28 03:37:54', '2025-11-28 03:37:54'),
(3, 'RAM', '2025-11-28 03:37:54', '2025-11-28 03:37:54'),
(4, 'MONITOR', '2025-11-28 03:37:54', '2025-11-28 03:37:54'),
(5, 'KEYBOARD', '2025-11-28 03:37:54', '2025-11-28 03:37:54'),
(6, 'MOUSE', '2025-11-28 03:37:54', '2025-11-28 03:37:54'),
(7, 'PRINTER', '2025-11-28 03:37:54', '2025-11-28 03:37:54'),
(8, 'PROJECTOR', '2025-11-28 03:37:54', '2025-11-28 03:37:54'),
(9, 'ROUTER', '2025-11-28 03:37:54', '2025-11-28 03:37:54'),
(10, 'SWITCH', '2025-11-28 03:37:54', '2025-11-28 03:37:54'),
(11, 'SERVER', '2025-11-28 03:37:54', '2025-11-28 03:37:54'),
(12, 'STORAGE', '2025-11-28 03:37:54', '2025-11-28 03:37:54'),
(13, 'CABLE', '2025-11-28 03:37:54', '2025-11-28 03:37:54'),
(14, 'ADAPTER', '2025-11-28 03:37:54', '2025-11-28 03:37:54'),
(15, 'OTHER', '2025-11-28 03:37:54', '2025-11-28 03:37:54');

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
(16, 3, '2025-11-27 03:51:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(17, 3, '2025-11-28 03:20:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(18, 3, '2025-11-28 06:06:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop');

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
(1, NULL, 1, 'TH-01', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:56:26'),
(7, NULL, 1, 'TH-02', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:56:26'),
(8, NULL, 1, 'TH-03', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:56:26'),
(9, NULL, 1, 'TH-04', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:56:26'),
(10, NULL, 1, 'TH-05', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:56:26'),
(11, NULL, 1, 'TH-06', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:56:26'),
(12, NULL, 1, 'TH-07', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:56:16'),
(13, NULL, 1, 'TH-08', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:56:16'),
(14, NULL, 1, 'TH-09', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:56:16'),
(15, NULL, 1, 'TH-10', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:56:16'),
(16, NULL, 1, 'TH-11', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:03:49'),
(17, NULL, 1, 'TH-12', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:03:49'),
(18, NULL, 1, 'TH-13', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:03:49'),
(19, NULL, 1, 'TH-14', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:03:49'),
(20, NULL, 1, 'TH-15', 'Archive', 'Good', '', '2025-11-23 12:35:19', '2025-11-27 12:03:49'),
(21, NULL, 1, 'TH-16', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(22, NULL, 1, 'TH-17', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(23, NULL, 1, 'TH-18', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(24, NULL, 1, 'TH-19', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(25, NULL, 1, 'TH-20', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(26, NULL, 1, 'TH-21', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(27, NULL, 1, 'TH-22', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(28, NULL, 1, 'TH-23', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(29, NULL, 1, 'TH-24', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(30, NULL, 1, 'TH-25', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(31, NULL, 1, 'TH-26', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(32, NULL, 1, 'TH-27', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(33, NULL, 1, 'TH-28', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(34, NULL, 1, 'TH-29', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(35, NULL, 1, 'TH-30', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(36, NULL, 1, 'TH-31', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(37, NULL, 1, 'TH-32', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(38, NULL, 1, 'TH-33', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(39, NULL, 1, 'TH-34', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(40, NULL, 1, 'TH-35', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(41, NULL, 1, 'TH-36', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(42, NULL, 1, 'TH-37', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(43, NULL, 1, 'TH-38', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(44, NULL, 1, 'TH-39', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(45, NULL, 1, 'TH-40', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(46, NULL, 1, 'TH-41', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(47, NULL, 1, 'TH-42', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(48, NULL, 1, 'TH-43', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(49, NULL, 1, 'TH-44', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(50, NULL, 1, 'TH-45', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(51, NULL, 1, 'TH-46', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(52, NULL, 1, 'TH-47', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(53, NULL, 1, 'TH-48', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(54, NULL, 1, 'TH-49', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(55, NULL, 1, 'TH-50', 'Active', 'Good', '', '2025-11-23 12:35:19', '2025-11-23 12:35:19'),
(56, 7, 1, 'TH-01', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(57, 7, 1, 'TH-02', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(58, 7, 1, 'TH-03', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(59, 7, 1, 'TH-04', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(60, 7, 1, 'TH-05', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(61, 7, 1, 'TH-06', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(62, 7, 1, 'TH-07', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(63, 7, 1, 'TH-08', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(64, 7, 1, 'TH-09', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(65, 7, 1, 'TH-10', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(66, 7, 1, 'TH-11', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(67, 7, 1, 'TH-12', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(68, 7, 1, 'TH-13', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(69, 7, 1, 'TH-14', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(70, 7, 1, 'TH-15', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(71, 7, 1, 'TH-16', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(72, 7, 1, 'TH-17', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(73, 7, 1, 'TH-18', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(74, 7, 1, 'TH-19', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(75, 7, 1, 'TH-20', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(76, 7, 1, 'TH-21', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(77, 7, 1, 'TH-22', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(78, 7, 1, 'TH-23', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(79, 7, 1, 'TH-24', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(80, 7, 1, 'TH-25', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(81, 7, 1, 'TH-26', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(82, 7, 1, 'TH-27', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(83, 7, 1, 'TH-28', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(84, 7, 1, 'TH-29', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(85, 7, 1, 'TH-30', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(86, 7, 1, 'TH-31', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(87, 7, 1, 'TH-32', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(88, 7, 1, 'TH-33', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(89, 7, 1, 'TH-34', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(90, 7, 1, 'TH-35', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(91, 7, 1, 'TH-36', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(92, 7, 1, 'TH-37', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(93, 7, 1, 'TH-38', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(94, 7, 1, 'TH-39', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(95, 7, 1, 'TH-40', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(96, 7, 1, 'TH-41', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(97, 7, 1, 'TH-42', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(98, 7, 1, 'TH-43', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(99, 7, 1, 'TH-44', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(100, 7, 1, 'TH-45', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(101, 7, 1, 'TH-46', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(102, 7, 1, 'TH-47', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(103, 7, 1, 'TH-48', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(104, 7, 1, 'TH-49', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32'),
(105, 7, 1, 'TH-50', 'Active', 'Good', '', '2025-11-28 06:54:32', '2025-11-28 06:54:32');

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
(6, 2, 'IL501', '2025-11-22 14:36:23'),
(7, 1, 'IK501', '2025-11-28 06:54:16'),
(8, 1, 'IK502', '2025-11-28 06:55:02');

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
(3, '22-0308', '$2y$10$bEBBQUTMdL1tBiviKwv0DubLn8QbWojiqmTVqUJzjxMp/xYH3SFFm', 'Maria Lab Staff', 'labstaff@ams.edu', 'Laboratory Staff', 'Active', '2025-10-28 21:34:53', '2025-11-28 14:06:05', '2025-11-28 14:06:05'),
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
-- Indexes for table `asset_categories`
--
ALTER TABLE `asset_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=576;

--
-- AUTO_INCREMENT for table `asset_categories`
--
ALTER TABLE `asset_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pc_units`
--
ALTER TABLE `pc_units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
