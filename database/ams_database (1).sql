-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 03, 2025 at 02:32 AM
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
(1, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-27 to 2025-11-26)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 13:33:21'),
(2, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-29 to 2025-11-28)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 08:14:05'),
(3, 1, 'export', 'report', NULL, 'Generated users report', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 09:27:47'),
(4, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-29 to 2025-11-28)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 09:27:52'),
(5, 1, 'view', 'report', NULL, 'Previewed assets report (2025-10-29 to 2025-11-28)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 09:28:38'),
(6, 1, 'view', 'report', NULL, 'Previewed borrowing report (2025-10-29 to 2025-11-28)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 09:28:39'),
(7, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-29 to 2025-11-28)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 19:59:49'),
(8, 1, 'view', 'report', NULL, 'Previewed borrowing report (2025-10-29 to 2025-11-28)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 19:59:57'),
(9, 1, 'view', 'report', NULL, 'Previewed borrowing report (2025-10-29 to 2025-11-28)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 19:59:58'),
(10, 1, 'view', 'report', NULL, 'Previewed borrowing report (2025-10-29 to 2025-11-28)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 20:00:01'),
(11, 1, 'view', 'report', NULL, 'Previewed borrowing report (2025-10-28 to 2025-11-28)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 20:00:03'),
(12, 1, 'view', 'report', NULL, 'Previewed borrowing report (2025-10-28 to 2025-11-28)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 20:00:04'),
(13, 1, 'view', 'report', NULL, 'Previewed borrowing report (2025-10-28 to 2025-11-30)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 20:00:06'),
(14, 1, 'view', 'report', NULL, 'Previewed borrowing report (2025-10-28 to 2025-11-30)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 20:00:07'),
(15, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-30 to 2025-11-29)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:20'),
(16, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-16 to 2025-11-29)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:24'),
(17, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-16 to 2025-11-27)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:29'),
(18, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-16 to 2025-11-27)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:29'),
(19, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-16 to 2025-11-29)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:32'),
(20, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-16 to 2025-11-29)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:33'),
(21, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-16 to 2025-11-30)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:35'),
(22, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-16 to 2025-11-30)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:36'),
(23, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-01 to 2025-11-30)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:38'),
(24, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-01 to 2025-11-30)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:39'),
(25, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-01 to 2025-11-30)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:39'),
(26, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-01 to 2025-11-30)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:40'),
(27, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-01 to 2025-11-30)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:40'),
(28, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-01 to 2025-11-30)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:40'),
(29, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-01 to 2025-11-30)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:41'),
(30, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-01 to 2025-11-30)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:41'),
(31, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-01 to 2025-11-30)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:41'),
(32, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-01 to 2025-11-30)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:41'),
(33, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-01 to 2025-11-30)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:41'),
(34, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-01 to 2025-11-30)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:42'),
(35, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-01 to 2025-11-30)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:42'),
(36, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-10-01 to 2025-11-30)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 02:43:42'),
(37, 3, 'export', 'disposal_list', NULL, 'Exported disposal list with 0 assets', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 12:18:58'),
(38, 3, 'login', 'user', NULL, 'User logged in to Laboratory Staff panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 13:30:48'),
(39, 3, 'login', 'user', NULL, 'User logged in to Laboratory Staff panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 16:10:24'),
(40, 3, 'login', 'user', NULL, 'User logged in to Laboratory Staff panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 22:03:01'),
(41, 3, 'login', 'user', NULL, 'User logged in to Laboratory Staff panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 03:13:04'),
(42, 3, 'assign', 'ticket', 26, 'Assigned ticket #26 to John Technician', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 03:34:23'),
(43, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-11-02 to 2025-12-02)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 03:55:26'),
(44, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-11-02 to 2025-12-01)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 03:55:30'),
(45, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-11-01 to 2025-12-01)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 03:55:35'),
(46, 1, 'view', 'report', NULL, 'Previewed assets report (2025-11-01 to 2025-12-01)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 03:55:44'),
(47, 1, 'view', 'report', NULL, 'Previewed assets report (2025-11-01 to 2025-12-02)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 03:55:51'),
(48, 1, 'view', 'report', NULL, 'Previewed assets report (2025-11-01 to 2025-12-02)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 03:55:52'),
(49, 2, 'update', 'ticket', 26, 'Changed ticket #26 status to In Progress', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 04:09:49'),
(50, 2, 'update', 'ticket', 25, 'Changed ticket #25 status to In Progress', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 04:09:55'),
(51, 2, 'update', 'ticket', 24, 'Changed ticket #24 status to In Progress', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 04:09:57'),
(52, 2, 'update', 'ticket', 23, 'Changed ticket #23 status to In Progress', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 04:09:59'),
(53, 2, 'logout', 'user', NULL, 'User logged out from Technician panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 04:27:03'),
(54, 3, 'login', 'user', NULL, 'User logged in to Laboratory Staff panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 04:27:11'),
(55, 3, 'logout', 'user', NULL, 'User logged out from Laboratory Staff panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 04:29:23'),
(56, 3, 'login', 'user', NULL, 'User logged in to Laboratory Staff panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 04:34:38'),
(57, 3, 'assign', 'ticket', 27, 'Assigned ticket #27 to John Technician', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 04:35:55'),
(58, 3, 'dispose', 'asset', 682, 'Disposed asset: 12-01-2025-TH-272-IK501-KEYBOARD-001 - KEYBOARD for PC-272', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 07:27:47'),
(59, 2, 'login', 'user', NULL, 'User logged in to Technician panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 07:29:28'),
(60, 2, 'update_condition', NULL, NULL, 'Updated asset condition: 12-01-2025-TH-272-IK501-KEYBOARD-001 (KEYBOARD for PC-272) from \'Good\' to \'Poor\'', NULL, NULL, '2025-12-02 07:54:52'),
(61, 2, 'update_condition', NULL, NULL, 'Updated asset condition: 12-01-2025-TH-269-IK501-KEYBOARD-001 (ADAPTER) from \'Good\' to \'Poor\'', NULL, NULL, '2025-12-02 07:56:33'),
(62, 2, 'bulk_update_condition', NULL, NULL, 'Bulk updated 12 asset(s) condition to \'Poor\': 12-01-2025-TH-269-IK501-KEYBOARD-001 (ADAPTER), 12-01-2025-TH-270-IK501-KEYBOARD-001 (KEYBOARD for PC-270), 12-01-2025-TH-271-IK501-KEYBOARD-001 (KEYBOARD for PC-271), 12-01-2025-TH-272-IK501-KEYBOARD-001 (KEYBOARD for PC-272), 12-01-2025-TH-273-IK501-KEYBOARD-001 (KEYBOARD for PC-273) and 7 more', NULL, NULL, '2025-12-02 07:56:40'),
(63, 2, 'update_condition', NULL, NULL, 'Updated asset condition: 12-02-2025-LAPTOP-NOROOM-002 (LAPTOP) from \'Good\' to \'Fair\'', NULL, NULL, '2025-12-02 07:58:00'),
(64, 2, 'bulk_update_condition', NULL, NULL, 'Bulk updated 12 asset(s) condition to \'Damaged\': 12-02-2025-LAPTOP-NOROOM-002 (LAPTOP), 12-02-2025-LAPTOP-IK501-001 (LAPTOP), 12-02-2025-ADAPTER-NOROOM-001 (ADAPTER), 12-01-2025-TH-269-IK501-KEYBOARD-001 (ADAPTER), 12-01-2025-TH-270-IK501-KEYBOARD-001 (KEYBOARD for PC-270) and 7 more', NULL, NULL, '2025-12-02 07:58:24'),
(65, 2, 'update_condition', NULL, NULL, 'Updated asset condition: 12-02-2025-LAPTOP-NOROOM-002 (LAPTOP) from \'\' to \'Poor\'', NULL, NULL, '2025-12-02 07:59:30'),
(66, 2, 'bulk_update_condition', NULL, NULL, 'Bulk updated 12 asset(s) condition to \'Poor\': 12-02-2025-LAPTOP-NOROOM-002 (LAPTOP), 12-02-2025-LAPTOP-IK501-001 (LAPTOP), 12-02-2025-ADAPTER-NOROOM-001 (ADAPTER), 12-01-2025-TH-269-IK501-KEYBOARD-001 (ADAPTER), 12-01-2025-TH-270-IK501-KEYBOARD-001 (KEYBOARD for PC-270) and 7 more', NULL, NULL, '2025-12-02 07:59:45'),
(67, 3, 'assign', 'ticket', 27, 'Assigned ticket #27 to John Technician', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 08:18:28'),
(68, 3, 'logout', 'user', NULL, 'User logged out from Laboratory Staff panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 09:28:19'),
(69, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-11-02 to 2025-12-02)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 09:30:50'),
(70, 1, 'view', 'report', NULL, 'Previewed assets report (2025-11-02 to 2025-12-02)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 09:30:55'),
(71, 1, 'view', 'report', NULL, 'Previewed assets report (2025-11-02 to 2025-12-02)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 09:30:57'),
(72, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-11-01 to 2025-12-02)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 09:31:05'),
(73, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-11-01 to 2025-12-02)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 09:31:07'),
(74, 1, 'view', 'report', NULL, 'Previewed assets report (2025-11-01 to 2025-12-02)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 09:31:09'),
(75, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-11-02 to 2025-12-02)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 09:31:20'),
(76, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-11-01 to 2025-12-02)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 09:31:25'),
(77, 1, 'view', 'report', NULL, 'Previewed assets report (2025-11-01 to 2025-12-02)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 09:31:29'),
(78, 1, 'view', 'report', NULL, 'Previewed assets report (2025-11-01 to 2025-12-02)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 09:31:31'),
(79, 1, 'view', 'report', NULL, 'Previewed tickets report (2025-11-02 to 2025-12-02)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 09:32:26'),
(80, 1, 'view', 'report', NULL, 'Previewed assets report (2025-11-02 to 2025-12-02)', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-02 09:32:39'),
(81, 3, 'login', 'user', NULL, 'User logged in to Laboratory Staff panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 09:34:27'),
(82, 3, 'login', 'user', NULL, 'User logged in to Laboratory Staff panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 09:50:23'),
(83, 3, 'logout', 'user', NULL, 'User logged out from Laboratory Staff panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 10:37:40'),
(84, 2, 'login', 'user', NULL, 'User logged in to Technician panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 10:37:51'),
(85, 2, 'logout', 'user', NULL, 'User logged out from Technician panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 10:38:20'),
(86, 2, 'login', 'user', NULL, 'User logged in to Technician panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 10:38:25'),
(87, 2, 'logout', 'user', NULL, 'User logged out from Technician panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 10:39:24'),
(88, 2, 'login', 'user', NULL, 'User logged in to Technician panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 10:39:28'),
(89, 2, 'logout', 'user', NULL, 'User logged out from Technician panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 10:39:52'),
(90, 3, 'login', 'user', NULL, 'User logged in to Laboratory Staff panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 10:39:58'),
(91, 3, 'logout', 'user', NULL, 'User logged out from Laboratory Staff panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 10:41:31'),
(92, 3, 'login', 'user', NULL, 'User logged in to Laboratory Staff panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 10:43:01'),
(93, 3, 'logout', 'user', NULL, 'User logged out from Laboratory Staff panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 10:50:15'),
(94, 3, 'login', 'user', NULL, 'User logged in to Laboratory Staff panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 10:50:30'),
(95, 3, 'logout', 'user', NULL, 'User logged out from Laboratory Staff panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 10:56:40'),
(96, 3, 'login', 'user', NULL, 'User logged in to Laboratory Staff panel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 11:27:42');

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
  `end_of_life` date DEFAULT NULL COMMENT 'Expected end of life date for the asset',
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

INSERT INTO `assets` (`id`, `asset_tag`, `asset_name`, `asset_type`, `category`, `brand`, `model`, `serial_number`, `specifications`, `room_id`, `pc_unit_id`, `location`, `terminal_number`, `purchase_date`, `purchase_cost`, `supplier`, `warranty_expiry`, `end_of_life`, `status`, `condition`, `is_borrowable`, `assigned_to`, `assigned_date`, `assigned_by`, `last_maintenance_date`, `next_maintenance_date`, `maintenance_notes`, `notes`, `qr_code`, `image`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(679, '12-01-2025-TH-269-IK501-KEYBOARD-001', 'ADAPTER', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 269, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Archive', 'Poor', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-269-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-269%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, 3, '2025-12-01 16:25:16', '2025-12-02 09:59:05'),
(680, '12-01-2025-TH-270-IK501-KEYBOARD-001', 'KEYBOARD for PC-270', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 270, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Poor', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-270-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-270%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, 2, '2025-12-01 16:25:16', '2025-12-02 07:59:45'),
(681, '12-01-2025-TH-271-IK501-KEYBOARD-001', 'KEYBOARD for PC-271', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 271, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Poor', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-271-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-271%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, 2, '2025-12-01 16:25:16', '2025-12-02 07:59:45'),
(682, '12-01-2025-TH-272-IK501-KEYBOARD-001', 'KEYBOARD for PC-272', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 272, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Disposed', 'Poor', 0, NULL, NULL, NULL, NULL, NULL, NULL, '\n\n[DISPOSAL - 2025-12-02 15:27:47]\nAsset marked for disposal by  ', 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-272-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-272%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, 2, '2025-12-01 16:25:16', '2025-12-02 07:59:45'),
(683, '12-01-2025-TH-273-IK501-KEYBOARD-001', 'KEYBOARD for PC-273', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 273, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Poor', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-273-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-273%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, 2, '2025-12-01 16:25:16', '2025-12-02 07:59:45'),
(684, '12-01-2025-TH-274-IK501-KEYBOARD-001', 'KEYBOARD for PC-274', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 274, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Poor', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-274-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-274%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, 2, '2025-12-01 16:25:16', '2025-12-02 07:59:45'),
(685, '12-01-2025-TH-275-IK501-KEYBOARD-001', 'KEYBOARD for PC-275', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 275, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Poor', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-275-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-275%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, 2, '2025-12-01 16:25:16', '2025-12-02 07:59:45'),
(686, '12-01-2025-TH-276-IK501-KEYBOARD-001', 'KEYBOARD for PC-276', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 276, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Poor', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-276-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-276%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, 2, '2025-12-01 16:25:16', '2025-12-02 07:59:45'),
(687, '12-01-2025-TH-277-IK501-KEYBOARD-001', 'KEYBOARD for PC-277', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 277, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Poor', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-277-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-277%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, 2, '2025-12-01 16:25:16', '2025-12-02 07:59:45'),
(688, '12-01-2025-TH-278-IK501-KEYBOARD-001', 'KEYBOARD for PC-278', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 278, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Poor', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-278-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-278%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, 2, '2025-12-01 16:25:16', '2025-12-02 07:56:40'),
(689, '12-01-2025-TH-279-IK501-KEYBOARD-001', 'KEYBOARD for PC-279', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 279, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Poor', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-279-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-279%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, 2, '2025-12-01 16:25:16', '2025-12-02 07:56:40'),
(690, '12-01-2025-TH-280-IK501-KEYBOARD-001', 'KEYBOARD for PC-280', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 280, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Poor', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-280-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-280%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, 2, '2025-12-01 16:25:16', '2025-12-02 07:56:40'),
(691, '12-01-2025-TH-281-IK501-KEYBOARD-001', 'KEYBOARD for PC-281', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 281, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-281-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-281%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(692, '12-01-2025-TH-282-IK501-KEYBOARD-001', 'KEYBOARD for PC-282', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 282, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-282-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-282%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(693, '12-01-2025-TH-283-IK501-KEYBOARD-001', 'KEYBOARD for PC-283', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 283, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-283-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-283%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(694, '12-01-2025-TH-284-IK501-KEYBOARD-001', 'KEYBOARD for PC-284', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 284, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-284-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-284%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(695, '12-01-2025-TH-285-IK501-KEYBOARD-001', 'KEYBOARD for PC-285', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 285, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-285-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-285%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(696, '12-01-2025-TH-286-IK501-KEYBOARD-001', 'KEYBOARD for PC-286', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 286, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-286-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-286%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(697, '12-01-2025-TH-287-IK501-KEYBOARD-001', 'KEYBOARD for PC-287', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 287, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-287-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-287%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(698, '12-01-2025-TH-288-IK501-KEYBOARD-001', 'KEYBOARD for PC-288', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 288, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-288-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-288%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(699, '12-01-2025-TH-289-IK501-KEYBOARD-001', 'KEYBOARD for PC-289', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 289, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-289-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-289%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(700, '12-01-2025-TH-290-IK501-KEYBOARD-001', 'KEYBOARD for PC-290', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 290, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-290-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-290%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(701, '12-01-2025-TH-291-IK501-KEYBOARD-001', 'KEYBOARD for PC-291', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 291, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-291-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-291%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(702, '12-01-2025-TH-292-IK501-KEYBOARD-001', 'KEYBOARD for PC-292', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 292, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-292-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-292%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(703, '12-01-2025-TH-293-IK501-KEYBOARD-001', 'KEYBOARD for PC-293', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 293, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-293-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-293%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(704, '12-01-2025-TH-294-IK501-KEYBOARD-001', 'KEYBOARD for PC-294', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 294, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-294-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-294%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(705, '12-01-2025-TH-295-IK501-KEYBOARD-001', 'KEYBOARD for PC-295', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 295, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-295-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-295%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(706, '12-01-2025-TH-296-IK501-KEYBOARD-001', 'KEYBOARD for PC-296', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 296, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-296-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-296%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(707, '12-01-2025-TH-297-IK501-KEYBOARD-001', 'KEYBOARD for PC-297', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 297, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-297-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-297%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(708, '12-01-2025-TH-298-IK501-KEYBOARD-001', 'KEYBOARD for PC-298', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 298, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-298-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-298%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(709, '12-01-2025-TH-299-IK501-KEYBOARD-001', 'KEYBOARD for PC-299', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 299, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-299-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-299%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(710, '12-01-2025-TH-300-IK501-KEYBOARD-001', 'KEYBOARD for PC-300', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 300, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-300-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-300%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(711, '12-01-2025-TH-301-IK501-KEYBOARD-001', 'KEYBOARD for PC-301', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 301, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-301-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-301%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(712, '12-01-2025-TH-302-IK501-KEYBOARD-001', 'KEYBOARD for PC-302', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 302, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-302-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-302%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(713, '12-01-2025-TH-303-IK501-KEYBOARD-001', 'KEYBOARD for PC-303', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 303, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-303-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-303%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(714, '12-01-2025-TH-304-IK501-KEYBOARD-001', 'KEYBOARD for PC-304', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 304, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-304-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-304%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(715, '12-01-2025-TH-305-IK501-KEYBOARD-001', 'KEYBOARD for PC-305', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 305, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-305-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-305%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(716, '12-01-2025-TH-306-IK501-KEYBOARD-001', 'KEYBOARD for PC-306', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 306, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-306-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-306%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(717, '12-01-2025-TH-307-IK501-KEYBOARD-001', 'KEYBOARD for PC-307', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 307, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-307-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-307%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(718, '12-01-2025-TH-308-IK501-KEYBOARD-001', 'KEYBOARD for PC-308', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 308, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-308-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-308%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(719, '12-01-2025-TH-309-IK501-KEYBOARD-001', 'KEYBOARD for PC-309', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 309, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-309-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-309%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(720, '12-01-2025-TH-310-IK501-KEYBOARD-001', 'KEYBOARD for PC-310', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 310, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-310-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-310%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(721, '12-01-2025-TH-311-IK501-KEYBOARD-001', 'KEYBOARD for PC-311', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 311, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-311-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-311%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(722, '12-01-2025-TH-312-IK501-KEYBOARD-001', 'KEYBOARD for PC-312', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 312, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-312-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-312%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(723, '12-01-2025-TH-313-IK501-KEYBOARD-001', 'KEYBOARD for PC-313', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 313, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-313-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-313%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(724, '12-01-2025-TH-314-IK501-KEYBOARD-001', 'KEYBOARD for PC-314', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 314, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-314-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-314%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(725, '12-01-2025-TH-315-IK501-KEYBOARD-001', 'KEYBOARD for PC-315', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 315, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-315-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-315%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(726, '12-01-2025-TH-316-IK501-KEYBOARD-001', 'KEYBOARD for PC-316', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 316, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-316-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-316%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(727, '12-01-2025-TH-317-IK501-KEYBOARD-001', 'KEYBOARD for PC-317', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 317, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-317-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-317%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(728, '12-01-2025-TH-318-IK501-KEYBOARD-001', 'KEYBOARD for PC-318', 'Hardware', '5', 'DSA', 'SDA', 'DSADSA', NULL, 7, 318, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-01-2025-TH-318-IK501-KEYBOARD-001%22%2C%22asset_name%22%3A%22KEYBOARD+for+PC-318%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%2C%22room_name%22%3A%22IK5', NULL, 3, NULL, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(729, '12-02-2025-ADAPTER-NOROOM-001', 'ADAPTER', 'Hardware', NULL, '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Poor', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-02-2025-ADAPTER-NOROOM-001%22%2C%22asset_name%22%3A%22ADAPTER%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%7D', NULL, 3, 2, '2025-12-02 04:28:49', '2025-12-02 07:59:45'),
(730, '12-02-2025-LAPTOP-IK501-001', 'LAPTOP', 'Hardware', NULL, '', '', '', NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Poor', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-02-2025-LAPTOP-IK501-001%22%2C%22asset_name%22%3A%22LAPTOP%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3A7%7D', NULL, 3, 2, '2025-12-02 04:38:55', '2025-12-02 07:59:45'),
(731, '12-02-2025-LAPTOP-NOROOM-002', 'LAPTOP', 'Hardware', NULL, 'asd', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Available', 'Poor', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%7B%22asset_tag%22%3A%2212-02-2025-LAPTOP-NOROOM-002%22%2C%22asset_name%22%3A%22LAPTOP%22%2C%22asset_type%22%3A%22Hardware%22%2C%22room_id%22%3Anull%7D', NULL, 3, 2, '2025-12-02 04:40:43', '2025-12-02 07:59:45');

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
(1, 4, 5, 'Student One', '2025-11-04 00:00:00', '2025-11-05 00:00:00', '2025-11-19 14:47:20', 'dasdsa', 'Returned', 3, '2025-11-19 14:47:02', 'Excellent', '', '2025-11-02 04:03:48', '2025-11-19 06:47:20'),
(2, 476, 5, 'Student One', '2025-11-29 00:00:00', '2025-11-30 00:00:00', '2025-11-29 11:28:57', 'asdsada', 'Returned', 3, '2025-11-29 11:28:51', 'Excellent', 'dsada', '2025-11-29 03:19:25', '2025-11-29 03:28:57');

-- --------------------------------------------------------

--
-- Table structure for table `asset_categories`
--

CREATE TABLE `asset_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_pc_category` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `asset_categories`
--

INSERT INTO `asset_categories` (`id`, `name`, `created_at`, `updated_at`, `is_pc_category`) VALUES
(1, 'LAPTOP', '2025-11-28 03:37:54', '2025-11-28 03:37:54', 0),
(2, 'PC', '2025-11-28 03:37:54', '2025-11-28 03:37:54', 0),
(3, 'RAM', '2025-11-28 03:37:54', '2025-11-30 10:10:53', 1),
(4, 'MONITOR', '2025-11-28 03:37:54', '2025-11-30 10:10:53', 1),
(5, 'KEYBOARD', '2025-11-28 03:37:54', '2025-11-30 10:10:53', 1),
(6, 'MOUSE', '2025-11-28 03:37:54', '2025-12-02 10:04:59', 0),
(7, 'PRINTER', '2025-11-28 03:37:54', '2025-11-28 03:37:54', 0),
(8, 'PROJECTOR', '2025-11-28 03:37:54', '2025-11-28 03:37:54', 0),
(9, 'ROUTER', '2025-11-28 03:37:54', '2025-11-28 03:37:54', 0),
(10, 'SWITCH', '2025-11-28 03:37:54', '2025-11-28 03:37:54', 0),
(11, 'SERVER', '2025-11-28 03:37:54', '2025-11-28 03:37:54', 0),
(12, 'STORAGE', '2025-11-28 03:37:54', '2025-12-02 09:24:10', 1),
(13, 'CABLE', '2025-11-28 03:37:54', '2025-11-28 03:37:54', 0),
(14, 'ADAPTER', '2025-11-28 03:37:54', '2025-12-02 09:27:41', 0),
(15, 'OTHER', '2025-11-28 03:37:54', '2025-11-28 03:37:54', 0),
(66, 'TABLE', '2025-11-28 09:38:30', '2025-11-28 09:38:30', 0),
(68, 'MOTHERBOARD', '2025-12-02 10:05:50', '2025-12-02 10:05:50', 1),
(69, 'POWERSUPPLY', '2025-12-02 10:05:58', '2025-12-02 10:05:58', 1),
(70, 'VGA', '2025-12-02 10:06:10', '2025-12-02 10:06:10', 1),
(71, 'HDMI', '2025-12-02 10:06:15', '2025-12-02 10:06:15', 1);

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
(16, 'IC', '2025-12-01 12:54:02', '2025-12-01 12:54:02');

-- --------------------------------------------------------

--
-- Table structure for table `issues`
--

CREATE TABLE `issues` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `category` enum('hardware','software','network','laboratory','other') NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `pc_id` int(11) DEFAULT NULL,
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
  `archived_at` datetime DEFAULT NULL,
  `building_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `issues`
--

INSERT INTO `issues` (`id`, `user_id`, `category`, `room_id`, `pc_id`, `hardware_component`, `hardware_component_other`, `software_name`, `network_issue_type`, `network_issue_type_other`, `laboratory_concern_type`, `laboratory_concern_other`, `other_concern_category`, `other_concern_other`, `title`, `description`, `priority`, `status`, `created_at`, `updated_at`, `assigned_technician`, `submitted_by`, `assigned_group`, `is_archived`, `archived_at`, `building_id`) VALUES
(23, 5, 'hardware', 7, 62, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'dsa', 'dsadsadsa', 'Medium', 'In Progress', '2025-11-28 14:22:14', '2025-12-02 04:09:59', 'John Technician', NULL, NULL, 0, NULL, 1),
(24, 5, 'hardware', 7, 108, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'dsa', 'dasddsa', 'Medium', 'In Progress', '2025-11-28 14:24:25', '2025-12-02 04:09:57', 'John Technician', NULL, NULL, 0, NULL, 1),
(25, 5, 'hardware', 7, 108, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'dsadsa', 'dsad', 'Medium', 'In Progress', '2025-11-28 14:24:43', '2025-12-02 04:09:55', 'John Technician', NULL, NULL, 0, NULL, 1),
(26, 5, 'hardware', 7, 108, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'dsadsa', 'dsadsad', 'Medium', 'In Progress', '2025-11-28 14:28:10', '2025-12-02 04:09:49', 'John Technician', NULL, NULL, 0, NULL, 1),
(27, 5, 'hardware', 7, 108, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'dsadsa', 'dsadsa', 'Medium', 'In Progress', '2025-11-28 14:28:39', '2025-12-02 08:18:28', 'John Technician', NULL, NULL, 0, NULL, 1);

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
(18, 3, '2025-11-28 06:06:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(19, 1, '2025-11-28 08:12:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(20, 1, '2025-11-28 08:13:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(21, 3, '2025-11-28 08:14:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(22, 5, '2025-11-28 08:17:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(23, 3, '2025-11-28 08:18:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(24, 1, '2025-11-28 08:36:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(25, 2, '2025-11-28 08:37:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(26, 3, '2025-11-28 08:38:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(27, 3, '2025-11-28 09:17:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(28, 5, '2025-11-28 09:26:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(29, 1, '2025-11-28 09:27:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(30, 1, '2025-11-28 09:29:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(31, 2, '2025-11-28 09:30:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(32, 3, '2025-11-28 09:34:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(33, 5, '2025-11-28 13:42:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(34, 1, '2025-11-28 19:59:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(35, 3, '2025-11-28 20:01:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(36, 2, '2025-11-28 20:03:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(37, 5, '2025-11-29 02:18:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(38, 3, '2025-11-29 02:22:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(39, 5, '2025-11-29 02:28:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(40, 5, '2025-11-29 02:42:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(41, 1, '2025-11-29 02:43:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(42, 2, '2025-11-29 02:44:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(43, 3, '2025-11-29 03:07:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(44, 5, '2025-11-29 03:24:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(45, 3, '2025-11-30 08:55:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(46, 3, '2025-12-01 12:00:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(47, 3, '2025-12-01 13:30:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(48, 3, '2025-12-01 16:10:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(49, 3, '2025-12-01 22:03:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(50, 3, '2025-12-02 03:13:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(51, 1, '2025-12-02 03:54:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(52, 2, '2025-12-02 04:06:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(53, 5, '2025-12-02 04:19:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(54, 3, '2025-12-02 04:27:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(55, 5, '2025-12-02 04:31:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(56, 5, '2025-12-02 04:34:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(57, 3, '2025-12-02 04:34:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(58, 5, '2025-12-02 04:39:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(59, 2, '2025-12-02 07:29:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(60, 1, '2025-12-02 09:28:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(61, 3, '2025-12-02 09:34:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(62, 3, '2025-12-02 09:50:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(63, 2, '2025-12-02 10:37:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(64, 2, '2025-12-02 10:38:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(65, 2, '2025-12-02 10:39:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(66, 3, '2025-12-02 10:39:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(67, 3, '2025-12-02 10:43:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(68, 3, '2025-12-02 10:50:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(69, 1, '2025-12-02 10:56:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(70, 0, '2025-12-02 10:59:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(71, 1, '2025-12-02 11:03:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(72, 5, '2025-12-02 11:04:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(73, 5, '2025-12-02 11:21:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop'),
(74, 3, '2025-12-02 11:27:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop');

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
(42, 5, 'Borrowing Request #2 Approved', 'Your borrowing request has been approved. You can now pick up the asset.', 'success', 'borrowing', 2, 0, '2025-11-29 03:28:51'),
(43, 5, 'Asset Returned - Request #2', 'Your borrowed asset has been returned and marked as \'Excellent\'. Thank you!', 'success', 'borrowing', 2, 0, '2025-11-29 03:28:57'),
(44, 5, 'Ticket #26 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 26, 0, '2025-11-30 09:10:34'),
(45, 2, 'New Ticket Assigned #26', 'You have been assigned to a hardware ticket: \"dsadsa\". Please review and take action.', 'info', 'issue', 26, 0, '2025-11-30 09:10:34'),
(46, 5, 'Ticket #27 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 27, 0, '2025-11-30 11:38:40'),
(47, 2, 'New Ticket Assigned #27', 'You have been assigned to a hardware ticket: \"dsadsa\". Please review and take action.', 'info', 'issue', 27, 0, '2025-11-30 11:38:40'),
(48, 5, 'Ticket #27 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 27, 0, '2025-11-30 11:38:45'),
(49, 2, 'New Ticket Assigned #27', 'You have been assigned to a hardware ticket: \"dsadsa\". Please review and take action.', 'info', 'issue', 27, 0, '2025-11-30 11:38:45'),
(50, 5, 'Ticket #25 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 25, 0, '2025-11-30 11:38:48'),
(51, 2, 'New Ticket Assigned #25', 'You have been assigned to a hardware ticket: \"dsadsa\". Please review and take action.', 'info', 'issue', 25, 0, '2025-11-30 11:38:48'),
(52, 5, 'Ticket #24 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 24, 0, '2025-11-30 11:38:53'),
(53, 2, 'New Ticket Assigned #24', 'You have been assigned to a hardware ticket: \"dsa\". Please review and take action.', 'info', 'issue', 24, 0, '2025-11-30 11:38:53'),
(54, 5, 'Ticket #23 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 23, 0, '2025-11-30 11:39:54'),
(55, 2, 'New Ticket Assigned #23', 'You have been assigned to a hardware ticket: \"dsa\". Please review and take action.', 'info', 'issue', 23, 0, '2025-11-30 11:39:54'),
(56, 5, 'Ticket #27 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 27, 0, '2025-11-30 11:45:04'),
(57, 2, 'New Ticket Assigned #27', 'You have been assigned to a hardware ticket: \"dsadsa\". Please review and take action.', 'info', 'issue', 27, 0, '2025-11-30 11:45:04'),
(58, 5, 'Ticket #27 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 27, 0, '2025-11-30 11:45:28'),
(59, 2, 'New Ticket Assigned #27', 'You have been assigned to a hardware ticket: \"dsadsa\". Please review and take action.', 'info', 'issue', 27, 0, '2025-11-30 11:45:28'),
(60, 5, 'Ticket #27 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 27, 0, '2025-11-30 11:50:18'),
(61, 2, 'New Ticket Assigned #27', 'You have been assigned to a hardware ticket: \"dsadsa\". Please review and take action.', 'info', 'issue', 27, 0, '2025-11-30 11:50:18'),
(62, 5, 'Ticket #27 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 27, 0, '2025-11-30 11:51:28'),
(63, 2, 'New Ticket Assigned #27', 'You have been assigned to a hardware ticket: \"dsadsa\". Please review and take action.', 'info', 'issue', 27, 0, '2025-11-30 11:51:28'),
(64, 5, 'Ticket #26 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 26, 0, '2025-12-02 03:34:23'),
(65, 2, 'New Ticket Assigned #26', 'You have been assigned to a hardware ticket: \"dsadsa\". Please review and take action.', 'info', 'issue', 26, 0, '2025-12-02 03:34:23'),
(66, 5, 'Ticket #26 - Status Updated', 'Your ticket is now being worked on by John Technician.', 'info', 'issue', 26, 0, '2025-12-02 04:09:49'),
(67, 5, 'Ticket #25 - Status Updated', 'Your ticket is now being worked on by John Technician.', 'info', 'issue', 25, 0, '2025-12-02 04:09:55'),
(68, 5, 'Ticket #24 - Status Updated', 'Your ticket is now being worked on by John Technician.', 'info', 'issue', 24, 0, '2025-12-02 04:09:57'),
(69, 5, 'Ticket #23 - Status Updated', 'Your ticket is now being worked on by John Technician.', 'info', 'issue', 23, 0, '2025-12-02 04:09:59'),
(70, 5, 'Ticket #27 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 27, 0, '2025-12-02 04:35:55'),
(71, 2, 'New Ticket Assigned #27', 'You have been assigned to a hardware ticket: \"dsadsa\". Please review and take action.', 'info', 'issue', 27, 0, '2025-12-02 04:35:55'),
(72, 5, 'Ticket #27 Assigned', 'Your ticket has been assigned to John Technician. They will be working on your issue soon.', 'info', 'issue', 27, 0, '2025-12-02 08:18:28'),
(73, 2, 'New Ticket Assigned #27', 'You have been assigned to a hardware ticket: \"dsadsa\". Please review and take action.', 'info', 'issue', 27, 0, '2025-12-02 08:18:28');

-- --------------------------------------------------------

--
-- Table structure for table `pc_units`
--

CREATE TABLE `pc_units` (
  `id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `building_id` int(11) DEFAULT NULL,
  `terminal_number` varchar(50) DEFAULT NULL,
  `asset_tag` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive','Under Maintenance','Retired','Archive') NOT NULL DEFAULT 'Active',
  `condition` enum('Excellent','Good','Fair','Poor','Non-Functional') NOT NULL DEFAULT 'Good',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pc_units`
--

INSERT INTO `pc_units` (`id`, `room_id`, `building_id`, `terminal_number`, `asset_tag`, `status`, `condition`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(269, 7, NULL, 'PC-01', '12-01-2025-IK501-TH-01', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(270, 7, NULL, 'PC-02', '12-01-2025-IK501-TH-02', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(271, 7, NULL, 'PC-03', '12-01-2025-IK501-TH-03', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(272, 7, NULL, 'PC-04', '12-01-2025-IK501-TH-04', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(273, 7, NULL, 'PC-05', '12-01-2025-IK501-TH-05', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(274, 7, NULL, 'PC-06', '12-01-2025-IK501-TH-06', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(275, 7, NULL, 'PC-07', '12-01-2025-IK501-TH-07', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(276, 7, NULL, 'PC-08', '12-01-2025-IK501-TH-08', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(277, 7, NULL, 'PC-09', '12-01-2025-IK501-TH-09', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(278, 7, NULL, 'PC-10', '12-01-2025-IK501-TH-10', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(279, 7, NULL, 'PC-11', '12-01-2025-IK501-TH-11', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(280, 7, NULL, 'PC-12', '12-01-2025-IK501-TH-12', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(281, 7, NULL, 'PC-13', '12-01-2025-IK501-TH-13', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(282, 7, NULL, 'PC-14', '12-01-2025-IK501-TH-14', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(283, 7, NULL, 'PC-15', '12-01-2025-IK501-TH-15', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(284, 7, NULL, 'PC-16', '12-01-2025-IK501-TH-16', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(285, 7, NULL, 'PC-17', '12-01-2025-IK501-TH-17', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(286, 7, NULL, 'PC-18', '12-01-2025-IK501-TH-18', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(287, 7, NULL, 'PC-19', '12-01-2025-IK501-TH-19', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(288, 7, NULL, 'PC-20', '12-01-2025-IK501-TH-20', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(289, 7, NULL, 'PC-21', '12-01-2025-IK501-TH-21', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(290, 7, NULL, 'PC-22', '12-01-2025-IK501-TH-22', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(291, 7, NULL, 'PC-23', '12-01-2025-IK501-TH-23', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(292, 7, NULL, 'PC-24', '12-01-2025-IK501-TH-24', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(293, 7, NULL, 'PC-25', '12-01-2025-IK501-TH-25', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(294, 7, NULL, 'PC-26', '12-01-2025-IK501-TH-26', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(295, 7, NULL, 'PC-27', '12-01-2025-IK501-TH-27', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(296, 7, NULL, 'PC-28', '12-01-2025-IK501-TH-28', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(297, 7, NULL, 'PC-29', '12-01-2025-IK501-TH-29', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(298, 7, NULL, 'PC-30', '12-01-2025-IK501-TH-30', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(299, 7, NULL, 'PC-31', '12-01-2025-IK501-TH-31', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(300, 7, NULL, 'PC-32', '12-01-2025-IK501-TH-32', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(301, 7, NULL, 'PC-33', '12-01-2025-IK501-TH-33', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(302, 7, NULL, 'PC-34', '12-01-2025-IK501-TH-34', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(303, 7, NULL, 'PC-35', '12-01-2025-IK501-TH-35', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(304, 7, NULL, 'PC-36', '12-01-2025-IK501-TH-36', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(305, 7, NULL, 'PC-37', '12-01-2025-IK501-TH-37', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(306, 7, NULL, 'PC-38', '12-01-2025-IK501-TH-38', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(307, 7, NULL, 'PC-39', '12-01-2025-IK501-TH-39', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(308, 7, NULL, 'PC-40', '12-01-2025-IK501-TH-40', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(309, 7, NULL, 'PC-41', '12-01-2025-IK501-TH-41', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(310, 7, NULL, 'PC-42', '12-01-2025-IK501-TH-42', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(311, 7, NULL, 'PC-43', '12-01-2025-IK501-TH-43', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(312, 7, NULL, 'PC-44', '12-01-2025-IK501-TH-44', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(313, 7, NULL, 'PC-45', '12-01-2025-IK501-TH-45', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(314, 7, NULL, 'PC-46', '12-01-2025-IK501-TH-46', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(315, 7, NULL, 'PC-47', '12-01-2025-IK501-TH-47', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(316, 7, NULL, 'PC-48', '12-01-2025-IK501-TH-48', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(317, 7, NULL, 'PC-49', '12-01-2025-IK501-TH-49', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16'),
(318, 7, NULL, 'PC-50', '12-01-2025-IK501-TH-50', 'Active', 'Good', '', 3, '2025-12-01 16:25:16', '2025-12-01 16:25:16');

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
(0, 1, 'IK601', '2025-12-02 10:55:47'),
(6, 2, 'IL501', '2025-11-22 14:36:23'),
(7, 1, 'IK501', '2025-11-28 06:54:16'),
(8, 1, 'IK502', '2025-11-28 06:55:02'),
(9, 1, 'IK503', '2025-11-28 08:31:20');

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
(0, '22-0310', '$2y$10$uFCacUgr5bnbkzONa4XVF.zuUtfz80srvoF6ImEqtAYQBldBm5h9u', 'Kim  Gamot', 'uhugamot@gmail.com', 'Administrator', 'Active', '2025-12-02 18:58:11', '2025-12-02 18:59:21', '2025-12-02 18:59:21', NULL),
(1, '22-0306', '$2y$10$NrA9Ob9vAY4MF436ROTd2ecE2iYcVMFWCtbEGcTdfD7zH.ErqYCV6', 'Admin User', 'admin@ams.edu', 'Administrator', 'Active', '2025-10-28 21:34:53', '2025-12-02 19:03:08', '2025-12-02 19:03:08', NULL),
(2, '22-0307', '$2y$10$on5Q98KdJ3bnnvysSRbsBePxalUzs62G8F76Yk7pZLl8sDdW5WVUu', 'John Technician', 'technician@ams.edu', 'Technician', 'Active', '2025-10-28 21:34:53', '2025-12-02 18:39:28', '2025-12-02 18:39:28', NULL),
(3, '22-0308', '$2y$10$bEBBQUTMdL1tBiviKwv0DubLn8QbWojiqmTVqUJzjxMp/xYH3SFFm', 'Maria Lab Staff', 'labstaff@ams.edu', 'Laboratory Staff', 'Active', '2025-10-28 21:34:53', '2025-12-02 19:27:42', '2025-12-02 19:27:42', NULL),
(4, 'F2024-001', '12345', 'Dr. Jane Faculty', 'faculty@ams.edu', 'Faculty', 'Active', '2025-10-28 21:34:53', '2025-10-28 21:40:57', NULL, NULL),
(5, '22-0305', '$2y$10$clCXfgzls8VHen2k.aF6TuvTZ34Ntl.T3oWxfhzTn67A5mEEjI1QW', 'Student One', 'student1@ams.edu', 'Student', 'Active', '2025-10-28 21:34:53', '2025-12-02 19:21:31', '2025-12-02 19:21:31', 'signature_5_1764382897.png'),
(6, 'S2024-002', '12345', 'Student Two', 'student2@ams.edu', 'Student', 'Active', '2025-10-28 21:34:53', '2025-11-21 17:32:50', NULL, NULL),
(29, '22-0632', '$2y$10$TGmP8M7lub8Rgxc.RDvUkuLXEX38Gg.eybzS1/WXKECN85tXKkpO6', 'qweqwe qweqwe', 'sd@gmail.com', 'Administrator', 'Active', '2025-11-20 21:04:20', '2025-11-20 21:14:42', NULL, NULL);

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
-- Indexes for table `asset_borrowing`
--
ALTER TABLE `asset_borrowing`
  ADD PRIMARY KEY (`id`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_issues_building` (`building_id`),
  ADD KEY `fk_issues_room` (`room_id`),
  ADD KEY `fk_issues_pc` (`pc_id`);

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
  ADD UNIQUE KEY `asset_tag` (`asset_tag`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `building_id` (`building_id`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_asset_tag` (`asset_tag`);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=732;

--
-- AUTO_INCREMENT for table `asset_borrowing`
--
ALTER TABLE `asset_borrowing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `asset_categories`
--
ALTER TABLE `asset_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `buildings`
--
ALTER TABLE `buildings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `issues`
--
ALTER TABLE `issues`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `login_history`
--
ALTER TABLE `login_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `pc_units`
--
ALTER TABLE `pc_units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=319;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
