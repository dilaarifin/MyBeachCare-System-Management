-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 19, 2026 at 12:11 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mybeachcare`
--

-- --------------------------------------------------------

--
-- Table structure for table `badges`
--

CREATE TABLE `badges` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `points_required` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `badges`
--

INSERT INTO `badges` (`id`, `name`, `description`, `image`, `points_required`) VALUES
(1, 'First Clean-Up', 'Awarded for joining your first beach cleanup event.', 'img/badges/first_cleanup.png', 0),
(2, '5 Events Joined', 'Awarded for participating in 5 beach cleanup events.', 'img/badges/5_events.png', 0),
(3, 'Top Voter', 'Awarded to users who have voted or reported issues 10 times.', 'img/badges/top_voter.png', 50);

-- --------------------------------------------------------

--
-- Table structure for table `beaches`
--

CREATE TABLE `beaches` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `state` varchar(50) DEFAULT NULL,
  `location` text NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('Excellent','Good','Needs Attention','Clean','Needs Cleaning','In Progress') DEFAULT 'Clean',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `volunteers_count` int(11) DEFAULT 0,
  `last_cleaned` date DEFAULT curdate(),
  `clean_votes` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `beaches`
--

INSERT INTO `beaches` (`id`, `name`, `state`, `location`, `description`, `image`, `status`, `latitude`, `longitude`, `created_at`, `volunteers_count`, `last_cleaned`, `clean_votes`) VALUES
(4, 'Pantai Tanjung Dawai', 'Kedah', '', '🗑️ Trash / litter (marine debris and fishing waste)', 'img/needs_att.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 8),
(5, 'Pantai Merdeka', 'Kedah', 'Pantai Merdeka', '💧 Water quality (coastal runoff and pollution)', 'img/good.png', 'Good', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 4),
(6, 'Pantai Kuala Muda', 'Kedah', '', '🐢 Wildlife concern (habitat disturbance and erosion)', 'img/excellent.png', 'Excellent', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 4),
(7, 'Pantai Senok', 'Kelantan', '', '🗑️ Trash / litter (plastic accumulation)', 'img/needs_att.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 1),
(8, 'Pantai Cahaya Bulan', 'Kelantan', '', '💧 Water quality (sewage and river discharge)', 'img/good.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 3),
(9, 'Pantai Sabak', 'Kelantan', '', '⚠️ Safety hazard (strong currents and waves)', 'img/needs_att.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 1),
(10, 'Pantai Bisikan Bayu', 'Kelantan', '', '🗑️ Trash / litter (visitor-generated waste)', 'img/good.png', 'Good', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 1),
(11, 'Pantai Batu Buruk', 'Terengganu', '', '🗑️ Trash / litter (urban beach pollution)', 'img/needs_att.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 1),
(12, 'Pantai UMT', 'Terengganu', '', '💧 Water quality (algal bloom and organic runoff)', 'img/good.png', 'Good', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 1),
(13, 'Rantau Abang Beach', 'Terengganu', '', '🐢 Wildlife concern (decline of turtle nesting)', 'img/excellent.png', 'Excellent', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 1),
(14, 'Pantai Teluk Ketapang', 'Terengganu', '', '⚠️ Safety hazard (monsoon waves)', 'img/needs_att.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 1),
(15, 'Pantai Teluk Gadung', 'Terengganu', '', '🗑️ Trash / litter (coastal debris)', 'img/good.png', 'Good', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 1),
(16, 'Teluk Cempedak', 'Pahang', '', '⚠️ Safety hazard (dangerous currents / red-flag warnings)', 'img/needs_att.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 1),
(17, 'Pantai Sepat', 'Pahang', '', '🗑️ Trash / litter (local dumping and unmanaged waste)', 'img/needs_att.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 1),
(18, 'Pantai Kempadang', 'Pahang', '', '💧 Water quality (nearby waste discharge)', 'img/good.png', 'Good', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 1),
(19, 'Batu Hitam Beach', 'Pahang', '', '⚠️ Safety hazard (rocky shore and strong waves)', 'img/needs_att.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 0),
(20, 'Pantai Redang (Sekinchan)', 'Selangor', '', '🗑️ Trash / litter (fishing and plastic waste)', 'img/needs_att.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 0),
(21, 'Pantai Morib', 'Selangor', '', '💧 Water quality (muddy water and polluted runoff)', 'img/good.png', 'Good', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 0),
(22, 'Pantai Bagan Lalang', 'Selangor', '', '🗑️ Trash / litter (tourism pressure)', 'img/good.png', 'Good', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 0),
(23, 'Teluk Kemang', 'Negeri Sembilan', '', '💧 Water quality (sewage outfall issues)', 'img/good.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 0),
(24, 'Pantai Tanjung Gemuk', 'Negeri Sembilan', '', '💧 Water quality (urban runoff)', 'img/good.png', 'Good', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 0),
(25, 'Blue Lagoon (Bagan Pinang)', 'Negeri Sembilan', '', '⚠️ Safety hazard (hidden currents)', 'img/needs_att.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 0),
(26, 'Pantai Klebang', 'Melaka', '', '🗑️ Trash / litter (high visitor waste)', 'img/needs_att.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 0),
(27, 'Pantai Puteri', 'Melaka', '', '💧 Water quality (coastal erosion and pollution)', 'img/good.png', 'Good', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 0),
(28, 'Pantai Lido', 'Johor', '', '💧 Water quality (industrial and port activity)', 'img/good.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 0);

-- --------------------------------------------------------

--
-- Table structure for table `beach_votes`
--

CREATE TABLE `beach_votes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `beach_id` int(11) NOT NULL,
  `voted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `beach_votes`
--

INSERT INTO `beach_votes` (`id`, `user_id`, `beach_id`, `voted_at`) VALUES
(6, 1, 4, '2026-01-18 09:27:42'),
(7, 1, 6, '2026-01-18 10:00:11'),
(8, 1, 7, '2026-01-18 10:00:19'),
(9, 1, 5, '2026-01-18 10:13:33'),
(10, 1, 8, '2026-01-18 10:13:34'),
(11, 1, 9, '2026-01-18 10:13:34'),
(12, 1, 10, '2026-01-18 10:13:36'),
(13, 1, 11, '2026-01-18 10:13:36'),
(14, 1, 12, '2026-01-18 10:13:37'),
(15, 1, 13, '2026-01-18 10:21:28'),
(16, 1, 14, '2026-01-18 10:21:29'),
(17, 1, 15, '2026-01-18 10:21:29'),
(18, 1, 18, '2026-01-18 10:21:31'),
(19, 1, 17, '2026-01-18 10:21:32'),
(20, 1, 16, '2026-01-18 10:21:32');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `created_at`) VALUES
(1, 'Hana Sofea', 'hanasofea@gmail.com', 'Payment issues', 'Hi. Do I have to pay the fee on the day of the event or is there a platform provided to pay it?', '2026-01-13 06:11:14'),
(2, 'ahmad', 'ahmad@gmail.com', 'Saya suka mybeachcare', 'Hi! Saya amat suka platform website kamu. Moga kita dapat bekerjasama lagi! Saya sangat gembira! :D', '2026-01-18 09:35:12');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `beach_id` int(11) NOT NULL,
  `organizer_id` int(11) NOT NULL,
  `event_date` datetime NOT NULL,
  `status` enum('Upcoming','Completed','Cancelled') NOT NULL DEFAULT 'Upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `leader_id` int(11) DEFAULT NULL,
  `certificates_released` tinyint(1) DEFAULT 0,
  `provision_details` text DEFAULT NULL,
  `provision_image` varchar(255) DEFAULT NULL,
  `kit_details` text DEFAULT NULL,
  `kit_image` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 25.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `image`, `beach_id`, `organizer_id`, `event_date`, `status`, `created_at`, `leader_id`, `certificates_released`, `provision_details`, `provision_image`, `kit_details`, `kit_image`, `price`) VALUES
(15, 'Pantai Puteri Beach Clean Up Programme', 'You are invited to participate in a community beach clean up at Pantai Puteri, Melaka. This programme aims to reduce coastal pollution, protect marine ecosystems, and maintain a clean and safe beach environment for the public and tourists.', 'uploads/events/event_696c80f40f0c0.jpg', 27, 3, '2026-02-11 08:00:00', 'Upcoming', '2026-01-18 06:43:00', 3, 0, 'Nasi Lemak & Spritzer', 'uploads/events/prov_696cbfbea71a3.png', 'Light Blue MyBeachCare T-Shirt', 'uploads/events/kit_696cbfbea7680.png', 15.00),
(21, 'Pantai Cahaya Bulan Beach Clean Up Programme', 'You are invited to take part in a community beach clean up at Pantai Cahaya Bulan. This programme focuses on reducing coastal pollution, protecting marine ecosystems, and keeping the beach clean for public use and tourism.', 'uploads/events/event_696cd6f891e10.jpg', 8, 1, '2026-02-13 07:30:00', 'Completed', '2026-01-18 12:50:00', 1, 1, 'Nasi Minyak & Spritzer', 'uploads/events/prov_696cd6f89251b.png', 'White Japan MyBeachCarexUiTM', 'uploads/events/kit_696cd6f892ade.png', 30.00),
(22, 'Pantai Batu Buruk Clean Up Day', 'You are invited to join a community beach clean up at Tanjung Jara Beach. This event focuses on removing litter, protecting marine life, and improving the beach environment for visitors and local residents.', 'uploads/events/event_696cd7668b51e.jpg', 11, 2, '2026-03-24 07:00:00', 'Completed', '2026-01-18 12:51:50', 2, 0, 'Gardenia Breads & Spritzer', 'uploads/events/prov_696cd7668b9e2.png', 'Black MyBeachCare T-shirt', 'uploads/events/kit_696cd7668c007.png', 25.00);

-- --------------------------------------------------------

--
-- Table structure for table `event_participants`
--

CREATE TABLE `event_participants` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('Registered','Attended','Cancelled') DEFAULT 'Registered',
  `certificate_downloaded` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_participants`
--

INSERT INTO `event_participants` (`id`, `event_id`, `user_id`, `status`, `certificate_downloaded`, `created_at`) VALUES
(21, 22, 1, 'Registered', 0, '2026-01-18 13:00:16'),
(22, 21, 4, 'Registered', 0, '2026-01-18 13:38:05'),
(23, 21, 5, 'Attended', 0, '2026-01-18 13:39:25');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `beach_id` int(11) NOT NULL,
  `type` enum('Trash/Litter','Trash Accumulation','Safety Hazard','Wildlife Concern','Other') NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Resolved','Dismissed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `user_id`, `beach_id`, `type`, `description`, `image`, `status`, `created_at`) VALUES
(9, 1, 28, 'Wildlife Concern', 'Penyu banyak mati', 'uploads/reports/696caa0fe6869.jpg', 'Pending', '2026-01-18 09:38:23');

-- --------------------------------------------------------

--
-- Table structure for table `rewards`
--

CREATE TABLE `rewards` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `points_required` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `voucher_code` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rewards`
--

INSERT INTO `rewards` (`id`, `name`, `description`, `points_required`, `image`, `voucher_code`, `is_active`, `created_at`, `expiry_date`) VALUES
(1, 'ZUS Coffee Voucher', 'Redeem for 1 free coffee.', 50, 'img/zusvoucher.png', 'ZUS-BEACH-2026', 1, '2026-01-18 10:08:48', '2026-03-05'),
(2, 'Tealive 1 Free', 'Get one free Boba Milk Tea', 50, 'uploads/rewards/reward_696cc92255712.png', 'TEA-BEACH-09', 1, '2026-01-18 10:25:58', '2026-02-07'),
(3, 'Marrybrown 1 Free Set A Porridge', 'Get 1 free Set A Porridge & Curly Fries + Pepsi S', 70, 'uploads/rewards/reward_696cc9284baae.png', 'MAR-POR-E34', 1, '2026-01-18 11:30:00', '2026-01-29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `security_question` varchar(255) DEFAULT NULL,
  `security_answer` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(12) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `points` int(11) DEFAULT 0,
  `volunteer_hours` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `voucher_claimed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `security_question`, `security_answer`, `full_name`, `phone`, `profile_image`, `role`, `points`, `volunteer_hours`, `created_at`, `voucher_claimed`) VALUES
(1, 'dila', 'dila@gmail.com', '$2y$10$WHdR.7LdwXlPeOlr2idIP.GU7XrA1C9y6BMEFh0bSgYbE/S6rPcra', 'What is the name of your first pet?', 'nescafe', 'dilaarifin', '', 'uploads/profiles/profile_1_1768717494.png', 'admin', 30, 0.00, '2026-01-18 06:21:30', 1),
(2, 'absa', 'absa@gmail.com', '$2y$10$b.BfoO4HCILiGk.QC5Ngge.HJdIj8f0fceJrU0W4V9TGr7kTfaeIS', 'What was your first car?', 'bugatti', 'nurulabsa', '', 'uploads/profiles/profile_2_1768735488.png', 'admin', 0, 0.00, '2026-01-18 06:22:16', 0),
(3, 'alia', 'alia@gmail.com', '$2y$10$AcBHDomnnD6tTgLyqAn8ruE4rsKQzCQSj2O2MKUBddBSa.BHr7mU6', 'What was your first car?', 'proton', 'nuralia', '', 'uploads/profiles/profile_3_1768735373.png', 'admin', 0, 0.00, '2026-01-18 06:22:49', 0),
(4, 'minah', 'minah@gmail.com', '$2y$10$mZWshKHkYyG.nxwyfck3ze3JRkQ44DYxTmPDjMDijB6lM2136F8YC', 'What is the name of your first pet?', 'belang', 'minahjumaat', '', 'uploads/profiles/profile_4_1768735526.jpg', 'user', 10, 0.00, '2026-01-18 06:23:56', 0),
(5, 'ali', 'aliabu@gmail.com', '$2y$10$LwE5X9i7HacVP6ifrBblJOqI3bZqPp16QYFhR2U85pMNACZhLQDSe', 'What was your first car?', 'lambo', 'aliabu', '', 'uploads/profiles/profile_5_1768735656.png', 'user', 10, 0.00, '2026-01-18 06:24:19', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_badges`
--

CREATE TABLE `user_badges` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `awarded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_badges`
--

INSERT INTO `user_badges` (`id`, `user_id`, `badge_id`, `awarded_at`) VALUES
(3, 1, 1, '2026-01-18 09:35:45'),
(4, 1, 3, '2026-01-18 10:21:28'),
(5, 4, 1, '2026-01-18 13:38:05'),
(6, 5, 1, '2026-01-18 13:39:25');

-- --------------------------------------------------------

--
-- Table structure for table `user_rewards`
--

CREATE TABLE `user_rewards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  `claimed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Active','Used') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_rewards`
--

INSERT INTO `user_rewards` (`id`, `user_id`, `reward_id`, `claimed_at`, `status`) VALUES
(1, 1, 1, '2026-01-18 10:11:35', 'Active'),
(2, 1, 2, '2026-01-18 10:26:12', 'Used');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `badges`
--
ALTER TABLE `badges`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `beaches`
--
ALTER TABLE `beaches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `beach_votes`
--
ALTER TABLE `beach_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vote` (`user_id`,`beach_id`),
  ADD KEY `beach_id` (`beach_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `beach_id` (`beach_id`),
  ADD KEY `organizer_id` (`organizer_id`);

--
-- Indexes for table `event_participants`
--
ALTER TABLE `event_participants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `beach_id` (`beach_id`);

--
-- Indexes for table `rewards`
--
ALTER TABLE `rewards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `badge_id` (`badge_id`);

--
-- Indexes for table `user_rewards`
--
ALTER TABLE `user_rewards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reward_id` (`reward_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `badges`
--
ALTER TABLE `badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `beaches`
--
ALTER TABLE `beaches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `beach_votes`
--
ALTER TABLE `beach_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `event_participants`
--
ALTER TABLE `event_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `rewards`
--
ALTER TABLE `rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_badges`
--
ALTER TABLE `user_badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_rewards`
--
ALTER TABLE `user_rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `beach_votes`
--
ALTER TABLE `beach_votes`
  ADD CONSTRAINT `beach_votes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `beach_votes_ibfk_2` FOREIGN KEY (`beach_id`) REFERENCES `beaches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`beach_id`) REFERENCES `beaches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_participants`
--
ALTER TABLE `event_participants`
  ADD CONSTRAINT `event_participants_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`beach_id`) REFERENCES `beaches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD CONSTRAINT `user_badges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_badges_ibfk_2` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_rewards`
--
ALTER TABLE `user_rewards`
  ADD CONSTRAINT `user_rewards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_rewards_ibfk_2` FOREIGN KEY (`reward_id`) REFERENCES `rewards` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
