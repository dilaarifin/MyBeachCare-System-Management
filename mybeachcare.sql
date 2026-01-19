-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 13, 2026 at 07:37 AM
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
(3, 'Top Voter', 'Awarded for being an active participant in voting/reporting.', 'img/badges/top_voter.png', 50);

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
(4, 'Pantai Tanjung Dawai', 'Kedah', '', '🗑️ Trash / litter (marine debris and fishing waste)', 'img/needs_att.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 6),
(5, 'Pantai Merdeka', 'Kedah', 'Pantai Merdeka', '💧 Water quality (coastal runoff and pollution)', 'img/good.png', 'Good', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 2),
(6, 'Pantai Kuala Muda', 'Kedah', '', '🐢 Wildlife concern (habitat disturbance and erosion)', 'img/excellent.png', 'Excellent', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 2),
(7, 'Pantai Senok', 'Kelantan', '', '🗑️ Trash / litter (plastic accumulation)', 'img/needs_att.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 0),
(8, 'Pantai Cahaya Bulan', 'Kelantan', '', '💧 Water quality (sewage and river discharge)', 'img/good.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 2),
(9, 'Pantai Sabak', 'Kelantan', '', '⚠️ Safety hazard (strong currents and waves)', 'img/needs_att.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 0),
(10, 'Pantai Bisikan Bayu', 'Kelantan', '', '🗑️ Trash / litter (visitor-generated waste)', 'img/good.png', 'Good', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 0),
(11, 'Pantai Batu Buruk', 'Terengganu', '', '🗑️ Trash / litter (urban beach pollution)', 'img/needs_att.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 0),
(12, 'Pantai UMT', 'Terengganu', '', '💧 Water quality (algal bloom and organic runoff)', 'img/good.png', 'Good', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 0),
(13, 'Rantau Abang Beach', 'Terengganu', '', '🐢 Wildlife concern (decline of turtle nesting)', 'img/excellent.png', 'Excellent', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 0),
(14, 'Pantai Teluk Ketapang', 'Terengganu', '', '⚠️ Safety hazard (monsoon waves)', 'img/needs_att.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 0),
(15, 'Pantai Teluk Gadung', 'Terengganu', '', '🗑️ Trash / litter (coastal debris)', 'img/good.png', 'Good', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 0),
(16, 'Teluk Cempedak', 'Pahang', '', '⚠️ Safety hazard (dangerous currents / red-flag warnings)', 'img/needs_att.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 0),
(17, 'Pantai Sepat', 'Pahang', '', '🗑️ Trash / litter (local dumping and unmanaged waste)', 'img/needs_att.png', 'Needs Attention', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 0),
(18, 'Pantai Kempadang', 'Pahang', '', '💧 Water quality (nearby waste discharge)', 'img/good.png', 'Good', NULL, NULL, '2025-12-22 21:22:09', 0, '2025-12-23', 0),
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
(1, 82, 6, '2026-01-13 06:08:14'),
(2, 82, 5, '2026-01-13 06:13:12');

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
(1, 'Hana Sofea', 'hanasofea@gmail.com', 'Payment issues', 'Hi. Do I have to pay the fee on the day of the event or is there a platform provided to pay it?', '2026-01-13 06:11:14');

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
  `certificates_released` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `image`, `beach_id`, `organizer_id`, `event_date`, `status`, `created_at`, `leader_id`, `certificates_released`) VALUES
(1, 'Cenang Beach Morning Cleanup', 'Join us for a refreshing morning cleanup at Pantai Cenang. We will focus on removing plastic waste and debris from the shoreline.', 'uploads/events/event_694a334d7593d.jpg', 11, 1, '2026-01-01 10:00:00', 'Cancelled', '2025-12-22 21:36:21', 1, 0),
(2, 'Batu Ferringhi Sunset Cleanup & Audit', 'A sunset beach cleanup where we also perform a waste audit to better understand the sources of pollution on our shores.', 'uploads/events/event_694a332620e33.jpg', 27, 4, '2026-01-21 09:30:00', 'Completed', '2025-12-22 21:36:21', 4, 1),
(3, 'Redang Island Coral Protection Drive', 'A special event at Redang Island involving beach cleanup and an educational session on coral reef protection.', 'uploads/events/hd_event_ocean_talk_workshop.png', 6, 2, '2026-01-11 10:00:00', 'Upcoming', '2025-12-22 21:36:21', 2, 0),
(4, 'Tioman Community Cleanup & BBQ', 'Join the locals and tourists for a massive cleanup drive followed by a community BBQ. Fun, food, and impact!', 'uploads/events/hd_event_beach_cleanup_community.png', 5, 1, '2026-02-15 08:00:00', 'Upcoming', '2026-01-11 15:18:56', 1, 0),
(5, 'Mangrove Restoration Day', 'Help us plant 500 mangrove saplings to protect the coastline from erosion. Muddy but rewarding work!', 'uploads/events/hd_event_mangrove_planting.png', 20, 4, '2026-02-20 09:00:00', 'Upcoming', '2026-01-11 15:18:56', 4, 0),
(6, 'Sea Turtle Hatchling Release', 'Witness the magic of life! We will be releasing rehabilitated hatchlings back to the ocean. Educational session included.', 'uploads/events/hd_event_turtle_hatchling_release.png', 13, 2, '2025-12-20 18:30:00', 'Completed', '2026-01-11 15:18:56', 2, 0);

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
(1, 1, 1, 'Registered', 0, '2025-12-22 21:39:29'),
(4, 1, 2, 'Cancelled', 0, '2025-12-23 06:18:00'),
(5, 1, 4, 'Registered', 0, '2025-12-23 07:00:28'),
(6, 4, 81, 'Registered', 0, '2026-01-11 15:20:38'),
(7, 5, 82, 'Registered', 0, '2026-01-13 05:13:55');

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
(2, 1, 28, 'Trash/Litter', 'Banyak sampah-sarap', 'uploads/reports/6949c19a7d255.png', 'Resolved', '2025-12-22 22:09:30'),
(5, 4, 8, 'Trash/Litter', 'Botol-botol wastes', 'uploads/reports/694a41d389a0f.png', 'Dismissed', '2025-12-23 07:16:35'),
(7, 4, 27, 'Wildlife Concern', 'Penyu banyak mati', 'uploads/reports/694aadf85fdef.png', 'Pending', '2025-12-23 14:58:00'),
(8, 82, 21, 'Trash/Litter', 'Trash is scattered around the shore', 'uploads/reports/6965d43157f0c.jpg', 'Pending', '2026-01-13 05:12:17');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(12) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `points` int(11) DEFAULT 0,
  `volunteer_hours` decimal(10,2) DEFAULT 0.00,
  `voucher_claimed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `profile_image`, `role`, `points`, `volunteer_hours`, `created_at`) VALUES
(1, 'minah', 'minah@gmail.com', '$2y$10$IE6x8FZhES5lTLYJsrjF8ezCa8NQY5E/4Oj1.VJP4PnrGMnqE2qg2', 'Minah Bt Jumaat', '018736354263', 'uploads/profiles/user_694a4b2abfcd7.jpg', 'user', 0, 0.00, '2025-12-22 18:12:10'),
(2, 'dila', 'dila@gmail.com', '$2y$10$McSmxA6y1bTWi2oQ50PDe.G7Wv5yHOr1UWq2DoXN4A0xp5xzCRbdu', 'Dila Arifin', '019764637463', 'uploads/profiles/profile_2_1766445827.JPG', 'admin', 0, 0.00, '2025-12-22 18:14:53'),
(4, 'Abu bin Ali', 'abu@gmail.com', '$2y$10$QzQuRid9RvzAxwSboNkdM.gknjPc41JeWT8Pqqm6.KeN9kCflb1WC', 'AbuAli', '018764526372', 'uploads/profiles/profile_4_1766473212.png', 'user', 0, 0.00, '2025-12-23 06:59:21'),
(5, 'absa', 'absa@gmail.com', '$2y$10$kpy3D.ZM5A.OUW6NWZvZTeh/RuYDsOzcL.8q9kqSsffzHFLuSIzMq', 'absarina', '018764765372', 'uploads/profiles/user_694ab011d836c.jpg', 'admin', 0, 0.00, '2025-12-23 15:06:57'),
(6, 'alia', 'alia@gmail.com', '$2y$10$sw4cscSrkvGtmNOjuN7jZ.fi2dmKqQj8R7EaYzn6XGy/0CKhiHKi2', 'aliaizzati', '+60162537627', 'uploads/profiles/profile_80_1767107970.jpg', 'admin', 0, 0.00, '2025-12-30 15:18:49'),
(81, 'amira', 'amira@gmail.com', '$2y$10$NtQDRwhVnGkJTNUcKd2oC.k86PqB6Su31DAPgHYtt3Xqxmwx8s/yi', 'Amira Aisyah', NULL, NULL, 'user', 10, 0.00, '2026-01-11 15:20:03'),
(82, 'hana', 'hanasofea@gmail.com', '$2y$10$YVNSluU/0UEleNLThC7LlebLb/XR1.jSK4Xj0qfMcsgejmKZYvLc6', 'Hana Sofea', NULL, NULL, 'user', 10, 0.00, '2026-01-13 05:00:05');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `event_participants`
--
ALTER TABLE `event_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `user_badges`
--
ALTER TABLE `user_badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
