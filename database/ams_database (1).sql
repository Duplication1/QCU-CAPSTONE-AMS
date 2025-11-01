-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 01, 2025 at 02:47 PM
-- Server version: 10.4.11-MariaDB
-- PHP Version: 7.4.6

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
-- Table structure for table `hardware_issues`
--

CREATE TABLE `hardware_issues` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `requester_name` varchar(150) DEFAULT NULL,
  `assigned_technician` varchar(150) DEFAULT NULL,
  `room` varchar(50) NOT NULL,
  `terminal` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('Low','Medium','High') DEFAULT 'Medium',
  `status` enum('Open','In Progress','Resolved','Closed') DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `technician_notes` text DEFAULT NULL,
  `issue_type` enum('Hardware','Software','Network') DEFAULT 'Hardware',
  `submitted_by` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `hardware_issues`
--

INSERT INTO `hardware_issues` (`id`, `user_id`, `requester_name`, `assigned_technician`, `room`, `terminal`, `title`, `description`, `priority`, `status`, `created_at`, `updated_at`, `technician_notes`, `issue_type`, `submitted_by`) VALUES
(1, 5, 'Student One', 'John Technician', 'IK501', '1', 'Keyboard - asdas', 'dasdasd', 'Low', 'In Progress', '2025-11-01 13:34:23', '2025-11-01 13:46:34', '\n2025-11-01 14:46:34 - Status changed to In Progress: ', 'Hardware', 'Student One'),
(2, 5, 'Student One', 'John Technician', 'IK502', '1', 'qwrq - rqwrwq', 'qwrqw', 'Low', 'Resolved', '2025-11-01 13:34:31', '2025-11-01 13:46:31', '\n2025-11-01 14:46:31 - Status changed to Resolved: ', 'Hardware', 'Student One'),
(3, 5, 'Student One', 'John Technician', 'IK501', '1', 'Google - Walang Bold Sir', 'Di maka nood ng bold naka block', 'Medium', 'Resolved', '2025-11-01 13:44:57', '2025-11-01 13:46:27', '\n2025-11-01 14:46:27 - Status changed to Resolved: ', 'Software', 'Student One'),
(4, 5, 'Student One', 'John Technician', 'IK501', '3', 'WiFi Problem - Mahina WIFI', 'Mahina wifi di maka load ng bold tanginangyan', 'Medium', 'Resolved', '2025-11-01 13:45:17', '2025-11-01 13:46:20', '\n2025-11-01 14:46:20 - Status changed to Resolved: ', 'Network', 'Student One');

-- --------------------------------------------------------

--
-- Table structure for table `network_issues`
--

CREATE TABLE `network_issues` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `requester_name` varchar(150) DEFAULT NULL,
  `assigned_technician` varchar(150) DEFAULT NULL,
  `room` varchar(100) NOT NULL,
  `terminal` varchar(20) NOT NULL,
  `issue_type` enum('No Connection','Slow Internet','WiFi Problem','Other') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('Low','Medium','High') NOT NULL DEFAULT 'Medium',
  `status` enum('Open','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open',
  `technician_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
-- Table structure for table `software_issues`
--

CREATE TABLE `software_issues` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `requester_name` varchar(150) DEFAULT NULL,
  `assigned_technician` varchar(150) DEFAULT NULL,
  `room` varchar(100) NOT NULL,
  `terminal` varchar(20) NOT NULL,
  `software_name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('Low','Medium','High') NOT NULL DEFAULT 'Medium',
  `status` enum('Open','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open',
  `technician_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `id_number`, `password`, `full_name`, `email`, `role`, `status`, `created_at`, `updated_at`, `last_login`) VALUES
(1, '22-0306', '$2y$10$NrA9Ob9vAY4MF436ROTd2ecE2iYcVMFWCtbEGcTdfD7zH.ErqYCV6', 'Admin User', 'admin@ams.edu', 'Administrator', 'Active', '2025-10-28 21:34:53', '2025-10-28 22:08:12', NULL),
(2, '22-0307', '$2y$10$on5Q98KdJ3bnnvysSRbsBePxalUzs62G8F76Yk7pZLl8sDdW5WVUu', 'John Technician', 'technician@ams.edu', 'Technician', 'Active', '2025-10-28 21:34:53', '2025-10-28 23:03:14', NULL),
(3, '22-0308', '$2y$10$bEBBQUTMdL1tBiviKwv0DubLn8QbWojiqmTVqUJzjxMp/xYH3SFFm', 'Maria Lab Staff', 'labstaff@ams.edu', 'Laboratory Staff', 'Active', '2025-10-28 21:34:53', '2025-10-28 23:03:26', NULL),
(4, 'F2024-001', '12345', 'Dr. Jane Faculty', 'faculty@ams.edu', 'Faculty', 'Active', '2025-10-28 21:34:53', '2025-10-28 21:40:57', NULL),
(5, '22-0305', '$2y$10$clCXfgzls8VHen2k.aF6TuvTZ34Ntl.T3oWxfhzTn67A5mEEjI1QW', 'Student One', 'student1@ams.edu', 'Student', 'Active', '2025-10-28 21:34:53', '2025-10-28 22:01:26', NULL),
(6, 'S2024-002', '12345', 'Student Two', 'student2@ams.edu', 'Student', 'Active', '2025-10-28 21:34:53', '2025-10-28 21:41:09', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `hardware_issues`
--
ALTER TABLE `hardware_issues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room` (`room`),
  ADD KEY `priority` (`priority`),
  ADD KEY `status` (`status`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `network_issues`
--
ALTER TABLE `network_issues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room` (`room`),
  ADD KEY `priority` (`priority`),
  ADD KEY `status` (`status`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_technician` (`assigned_technician`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `software_issues`
--
ALTER TABLE `software_issues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room` (`room`),
  ADD KEY `priority` (`priority`),
  ADD KEY `status` (`status`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_technician` (`assigned_technician`);

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
-- AUTO_INCREMENT for table `hardware_issues`
--
ALTER TABLE `hardware_issues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `network_issues`
--
ALTER TABLE `network_issues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `software_issues`
--
ALTER TABLE `software_issues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
