-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 08, 2025 at 03:53 AM
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
-- Database: `pdrims`
--

-- --------------------------------------------------------

--
-- Table structure for table `aid_records`
--

CREATE TABLE `aid_records` (
  `id` int(11) NOT NULL,
  `household_id` int(11) NOT NULL,
  `recipient_name` varchar(150) DEFAULT NULL,
  `aid_type` varchar(50) NOT NULL,
  `quantity` varchar(100) DEFAULT NULL,
  `date_distributed` date NOT NULL,
  `distributed_by` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `aid_records`
--

INSERT INTO `aid_records` (`id`, `household_id`, `recipient_name`, `aid_type`, `quantity`, `date_distributed`, `distributed_by`, `notes`, `created_at`) VALUES
(2, 1, 'Lapid, Jefferson B', 'Food Pack', '6767', '2025-12-06', 'oms', 'oms', '2025-12-06 21:25:34'),
(3, 3, 'Lapid, Kyle Grant G', 'Food Pack', '500 pesos', '2025-12-06', 'LGU', 'with love', '2025-12-06 21:51:17'),
(4, 2, 'Bacsarsa, Vin M', 'Food Pack', '500 pesos', '2025-12-06', 'LGU', 'with love', '2025-12-06 21:51:17'),
(5, 1, 'Lapid, Jefferson B', 'Food Pack', '500 pesos', '2025-12-06', 'LGU', 'with love', '2025-12-06 21:51:17'),
(6, 3, 'Lapid, Kyle Grant G', 'Food Pack', '400 pesos', '2025-12-06', 'zaza', 'with love', '2025-12-06 22:06:10'),
(11, 10, '4, 4 4.', 'Hygiene Kit', '400 pesos', '2025-12-07', 'LGU', 'with love', '2025-12-07 15:57:15'),
(12, 3, 'Lapid, Kyle Grant G.', 'Hygiene Kit', '400 pesos', '2025-12-07', 'LGU', 'with love', '2025-12-07 15:57:15'),
(13, 2, 'Bacsarsa, Vin M.', 'Hygiene Kit', '400 pesos', '2025-12-07', 'LGU', 'with love', '2025-12-07 15:57:15'),
(14, 2, 'Bacsarsa, Vin M.', 'Medicine', '500 pesos', '2025-12-11', 'zz', 'omsim', '2025-12-07 15:59:30');

-- --------------------------------------------------------

--
-- Table structure for table `concerns`
--

CREATE TABLE `concerns` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `purok` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `concerns`
--

INSERT INTO `concerns` (`id`, `user_id`, `subject`, `description`, `purok`, `status`, `response`, `created_at`) VALUES
(1, 8, 'BRUHHH', 'omsimnida', '', 'Resolved', 'zzzz', '2025-12-07 19:53:28'),
(2, 8, 'aa', 'aaaaaaaaaaaaaa', '1', 'Resolved', '', '2025-12-07 20:00:24'),
(3, 8, 'qwqwq', 'wqwqw', '', 'Acknowledged', '', '2025-12-07 20:04:08'),
(4, 8, 'aasasasa', 'asasasa', '', 'Pending', NULL, '2025-12-07 20:26:21');

-- --------------------------------------------------------

--
-- Table structure for table `households`
--

CREATE TABLE `households` (
  `id` int(11) NOT NULL,
  `head_surname` varchar(100) NOT NULL,
  `head_firstname` varchar(100) NOT NULL,
  `head_middle_name` varchar(50) DEFAULT NULL,
  `head_age` int(11) DEFAULT NULL,
  `head_gender` varchar(20) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `purok` varchar(50) NOT NULL,
  `post_disaster_condition` varchar(50) DEFAULT NULL,
  `livelihood_status` varchar(50) DEFAULT NULL,
  `damage_status` int(11) NOT NULL,
  `initial_needs` text DEFAULT NULL,
  `member_count` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `households`
--

INSERT INTO `households` (`id`, `head_surname`, `head_firstname`, `head_middle_name`, `head_age`, `head_gender`, `contact_number`, `purok`, `post_disaster_condition`, `livelihood_status`, `damage_status`, `initial_needs`, `member_count`, `notes`, `created_at`) VALUES
(1, 'Lapid', 'Jefferson', 'B', 30, 'Male', '123', 'Purok 1', 'Alive', 'Employed', 25, 'Food, Shelter', 1, NULL, '2025-12-06 20:25:03'),
(2, 'Bacsarsa', 'Vin', 'M', 21, 'Male', '123', 'Purok 2', 'Injured', 'Employed', 75, 'Medicine', 2, NULL, '2025-12-06 20:39:05'),
(3, 'Lapid', 'Kyle Grant', 'G', 21, 'Male', '123', 'Purok 1', 'Alive', 'Employed', 0, 'Food', 1, NULL, '2025-12-06 21:27:07'),
(8, '1', '1', '1', 11, 'Male', '1', 'Purok 2', 'Injured', 'Employed', 100, '11', 0, NULL, '2025-12-06 22:41:14'),
(9, 'Esio', 'Jazcel', 'A', 20, 'Female', '123', 'Purok 1', 'Alive', 'Employed', 0, 'Medicine', 0, NULL, '2025-12-07 07:41:41'),
(10, '4', '4', '4', 22, 'Male', '44', 'Purok 2', 'Deceased', 'Self-Employed', 100, '66666', 0, NULL, '2025-12-07 07:43:37'),
(12, 'Esio', 'Joel', 'A', 55, 'Male', '123456778899', 'Purok 1', 'Alive', 'Employed', 100, '1 million', 1, NULL, '2025-12-07 15:56:17');

-- --------------------------------------------------------

--
-- Table structure for table `household_members`
--

CREATE TABLE `household_members` (
  `id` int(11) NOT NULL,
  `household_id` int(11) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_initial` varchar(10) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `relationship` varchar(50) DEFAULT NULL,
  `livelihood_status` varchar(50) DEFAULT NULL,
  `condition_status` varchar(50) DEFAULT NULL,
  `residence_status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `household_members`
--

INSERT INTO `household_members` (`id`, `household_id`, `surname`, `first_name`, `middle_initial`, `age`, `gender`, `relationship`, `livelihood_status`, `condition_status`, `residence_status`) VALUES
(1, 1, 'Lapid', 'Syntyche', 'G', 30, 'Female', 'Spouse', 'Unemployed', 'Alive', 'Resident'),
(2, 2, 'z', 'z', 'z', 21, 'Female', 'Child', 'Unemployed', 'Alive', 'Transferred'),
(3, 2, 'a', 'a', 'a', 12, 'Male', 'Sibling', 'Unemployed', 'Injured', 'Outside'),
(4, 3, 'Severino', 'Nicole', 'I', 21, 'Female', 'Spouse', 'Retired', 'Alive', 'Resident'),
(7, 12, 'Avelino', 'Rachel', 'L', 50, 'Female', 'Spouse', 'Employed', 'Alive', 'Resident');

-- --------------------------------------------------------

--
-- Table structure for table `officials`
--

CREATE TABLE `officials` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `surname` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `officials`
--

INSERT INTO `officials` (`id`, `first_name`, `middle_name`, `surname`, `email`, `password_hash`, `role`, `position`, `contact_number`, `is_verified`, `created_at`) VALUES
(1, 'System', NULL, 'Admin', 'admin@pdrims.gov', '$2y$10$5Xh.GonKXHkL321/9yUKxuybcUJ3geAvl7rpGAEnR4.8.faYr0bJi', 'System Administrator', 'IT Head', NULL, 1, '2025-12-06 17:29:50'),
(2, 'Nicole', 'I', 'Severino', 'nicole@gmail.com', '$2y$10$GNnEp.4sXGvcRFZXymMYPOYXafCoo2OQA7lPeQ2KZnA2.aJYmuI5W', 'Barangay Official', NULL, NULL, 1, '2025-12-06 17:44:01'),
(6, 'Kyle Grant', 'G', 'Lapid', 'kyle@gmail.com', '$2y$10$m9fvUF0FyxYXcaohf2jxI.rij8rDagi7yWtZO6yN3ybn.ydRa5s0K', 'Barangay Captain', NULL, NULL, 1, '2025-12-06 22:56:01'),
(7, 'Vin', NULL, 'Ysl M. Bacsarsa', 'vin@gmail.com', '$2y$10$PMYZyKRD0gEAuA7lfH3R8ehn27/d3Q/ZZwc1b.EAlBuKwiUObIiMO', 'Barangay Official', NULL, NULL, 1, '2025-12-06 23:38:53'),
(9, 'Jazcel', 'A', 'Esio', 'jazzy@gmail.com', '$2y$10$IurIa76K99bdj12ZeK/wDea4V0JH9uUEuQCntR/nwlXthPsfDuJxy', 'Barangay Official', NULL, NULL, 1, '2025-12-07 15:51:56');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `target` varchar(255) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `user_id`, `user_name`, `action`, `target`, `timestamp`) VALUES
(1, 'SYSTEM', 'System Admin', 'System Initialized', 'Logs Integration Complete', '2025-12-07 14:00:53'),
(3, 'off_6', 'Kyle Grant Lapid', 'Added Household Profile', 'Household: 4 4', '2025-12-07 15:55:41'),
(4, 'off_2', 'Nicole Severino', 'Recorded Aid Distribution', 'Records: 1', '2025-12-07 23:14:07'),
(5, 'N/A', 'System Administrator', 'Approved User', 'User ID: off_9', '2025-12-07 23:52:25'),
(6, 'off_9', 'Jazcel Esio', 'Added Household Profile', 'Household: Joel Esio', '2025-12-07 23:56:17'),
(7, 'off_9', 'Jazcel Esio', 'Recorded Aid Distribution', 'Records: 5', '2025-12-07 23:57:15'),
(8, 'off_9', 'Jazcel Esio', 'Recorded Aid Distribution', 'Records: 1', '2025-12-07 23:59:30'),
(9, 'N/A', 'System Administrator', 'Deleted User', 'User ID: off_8', '2025-12-08 02:14:36');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_initial` varchar(5) DEFAULT NULL,
  `surname` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `is_member` tinyint(1) DEFAULT 0,
  `purok` varchar(50) DEFAULT NULL,
  `is_head` tinyint(1) DEFAULT 0,
  `household_head` varchar(150) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'viewer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `middle_initial`, `surname`, `contact_number`, `age`, `is_member`, `purok`, `is_head`, `household_head`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'Test', NULL, 'User', NULL, NULL, 0, NULL, 0, NULL, 'test@example.com', '$2y$10$61Ls5ORp2Xt7VGYAJKSvSu1zBIbuCMcFjz5Y7XUa328IR7bOGu3em', 'viewer', '2025-12-06 17:40:33'),
(2, 'Nicole', 'I', 'Severino', '123', 21, 1, 'Purok 1', 0, 'Severino', 'nicole@gmail.com', '$2y$10$OQ9zcN6xlp5UYN1GZRDPeukfTvHxommoUFESmoqYehIrLKRZWoqIe', 'viewer', '2025-12-06 17:41:57'),
(4, 'Jazcel', 'A', 'Esio', '123', 20, 0, NULL, 0, NULL, 'jaz@gmail.com', '$2y$10$LA4UOEEeNpbRnjBPrKdKZ.rMeFXDffw9m5ucRrnavoGh13O2KRL2q', 'viewer', '2025-12-06 17:48:22'),
(6, 'haha', '', 'User', '123', 21, 0, NULL, 0, NULL, 'haha@gmail.com', '$2y$10$2qXNXmq60.U5y.Pt7DB3y.HvXnGj3E3KOoYJbto2nkFqTIxCBGgxW', 'viewer', '2025-12-06 23:40:51'),
(7, 'Jazcel', 'A', 'Esio', '09123456789', 19, 1, 'Purok 2', 0, 'Joel Esio', 'jazcelesio@gmail.com', '$2y$10$HPCV3PFGcQsvt8Q0I5BrNOIHiJK0V/2ejYsEbTXnoptWvDAIaxy7a', 'viewer', '2025-12-07 15:50:00'),
(8, 'Kai', 'G', 'Cassano', '123', 1, 1, 'Purok 1', 0, 'Cassano', 'kai@gmail.com', '$2y$10$1RqxuYGFDHLwNXrey89B2uWWHBsD44wwBGM1XCHhsVXiSSqDmcQue', 'viewer', '2025-12-07 19:07:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `aid_records`
--
ALTER TABLE `aid_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `household_id` (`household_id`);

--
-- Indexes for table `concerns`
--
ALTER TABLE `concerns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `households`
--
ALTER TABLE `households`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `household_members`
--
ALTER TABLE `household_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `household_id` (`household_id`);

--
-- Indexes for table `officials`
--
ALTER TABLE `officials`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `aid_records`
--
ALTER TABLE `aid_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `concerns`
--
ALTER TABLE `concerns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `households`
--
ALTER TABLE `households`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `household_members`
--
ALTER TABLE `household_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `officials`
--
ALTER TABLE `officials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `aid_records`
--
ALTER TABLE `aid_records`
  ADD CONSTRAINT `aid_records_ibfk_1` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `household_members`
--
ALTER TABLE `household_members`
  ADD CONSTRAINT `household_members_ibfk_1` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
