-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 21, 2025 at 12:24 PM
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
-- Database: `online_counseling_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `content` text NOT NULL,
  `posted_by` int(11) DEFAULT NULL,
  `posted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `availability`
--

CREATE TABLE `availability` (
  `id` int(11) NOT NULL,
  `counselor_id` int(11) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `counseling_sessions`
--

CREATE TABLE `counseling_sessions` (
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `counselor_id` int(11) NOT NULL,
  `schedule` datetime NOT NULL,
  `status` enum('pending','approved','completed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `counselor_availability`
--

CREATE TABLE `counselor_availability` (
  `avail_id` int(11) NOT NULL,
  `counselor_id` int(11) NOT NULL,
  `weekday` tinyint(4) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `counselor_availability`
--

INSERT INTO `counselor_availability` (`avail_id`, `counselor_id`, `weekday`, `start_time`, `end_time`) VALUES
(88, 2, 1, '09:00:00', '12:00:00'),
(89, 2, 1, '13:00:00', '17:00:00'),
(90, 2, 2, '09:00:00', '12:00:00'),
(91, 2, 2, '13:00:00', '17:00:00'),
(92, 2, 3, '09:00:00', '12:00:00'),
(93, 2, 3, '13:00:00', '17:00:00'),
(94, 2, 4, '09:00:00', '12:00:00'),
(95, 2, 4, '13:00:00', '17:00:00'),
(96, 2, 5, '09:00:00', '12:00:00'),
(97, 2, 5, '13:00:00', '17:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comments` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `attachment_mime` varchar(100) DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL,
  `attachment_type` varchar(64) DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `message`, `attachment_path`, `attachment_mime`, `sent_at`, `read_at`, `attachment_type`, `attachment_name`) VALUES
(7, 10, 2, 'I’m feeling anxious about exams.', NULL, NULL, '2025-09-27 12:31:02', NULL, NULL, NULL),
(8, 3, 2, 'sa', NULL, NULL, '2025-10-22 08:19:38', NULL, NULL, NULL),
(9, 3, 2, 'I’m feeling anxious about exams.', NULL, NULL, '2025-10-22 08:19:59', NULL, NULL, NULL),
(10, 3, 2, 'dsadsadsa', NULL, NULL, '2025-10-22 08:20:03', NULL, NULL, NULL),
(11, 2, 4, 'dsadada', NULL, NULL, '2025-10-22 08:21:04', NULL, NULL, NULL),
(12, 2, 3, '32131235651321', NULL, NULL, '2025-10-22 08:21:38', NULL, NULL, NULL),
(13, 3, 2, 'dasdsa', NULL, NULL, '2025-10-22 08:21:49', NULL, NULL, NULL),
(14, 3, 2, 'dsa', NULL, NULL, '2025-11-18 08:23:49', NULL, NULL, NULL),
(15, 2, 3, 'k', NULL, NULL, '2025-11-18 08:24:01', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rtc_signals`
--

CREATE TABLE `rtc_signals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `thread_key` varchar(64) NOT NULL,
  `to_user` int(11) NOT NULL,
  `type` enum('offer','answer','candidate','bye') NOT NULL,
  `payload` mediumtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `counselor_id` int(11) DEFAULT NULL,
  `started_at` datetime DEFAULT current_timestamp(),
  `status` enum('pending','approved','cancelled') NOT NULL,
  `type` enum('In-Person','Online-Video','Online-Chat') DEFAULT 'In-Person',
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `student_id`, `counselor_id`, `started_at`, `status`, `type`, `note`) VALUES
(19, 3, 2, '2025-11-25 18:00:00', 'pending', 'In-Person', NULL),
(20, 4, 2, '2025-11-25 13:30:00', 'pending', 'In-Person', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` enum('student','counselor','admin') NOT NULL DEFAULT 'student',
  `gender` varchar(10) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `school_id_path` varchar(255) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verification_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `id_file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `role`, `gender`, `age`, `civil_status`, `year_level`, `school_id_path`, `course`, `created_at`, `verification_status`, `id_file_path`) VALUES
(1, 'System Admin', 'admin@tcc.local', 'admin123', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-26 01:50:03', 'pending', NULL),
(2, 'Guidance Counselor', 'counselor@tcc.local', 'counselor123', 'counselor', 'Female', 30, 'Single', NULL, NULL, 'Guidance', '2025-09-26 01:50:03', 'pending', NULL),
(3, 'Juan Dela Cruz', 'juan@tcc.local', 'student123', 'student', 'Male', 20, 'Single', '3rd Year', NULL, 'BSIndTech - CompTech', '2025-09-26 01:50:03', 'pending', NULL),
(4, 'Maria Santos', 'maria@tcc.local', 'student123', 'student', 'Female', 21, 'Single', '3rd Year', NULL, 'BSEd', '2025-09-26 01:50:03', 'pending', NULL),
(10, 'Shiena Mae Escala', 'shiena@tcc.local', '$2y$10$xv/km63s2w4EOSaE6ATqJ.1VtPN0BD.ENbOnJpF6lp21KAU0b1YKe', 'student', NULL, NULL, NULL, '4th Year', 'uploads/1758976237_Yeji Checkmate Photoshoot.jpg', 'BS IndTech', '2025-09-27 12:30:37', 'pending', NULL),
(11, 'marvin abolencia', 'marvin@school.edu', '$2y$10$Glp.1td7498eEaQ.qUeGTOptZcaZnJ2Mu4vOS.uynH2DEOvGrUXJm', 'student', NULL, NULL, NULL, '3rd Year', NULL, 'BS indtech', '2025-10-22 08:06:33', 'pending', 'uploads/ids/b6b85ae3d40ab0d4_1761120393.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `fk_ann_posted_by` (`posted_by`);

--
-- Indexes for table `availability`
--
ALTER TABLE `availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_slot` (`counselor_id`,`day_of_week`,`start_time`,`end_time`);

--
-- Indexes for table `counseling_sessions`
--
ALTER TABLE `counseling_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `idx_schedule` (`schedule`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_cs_user` (`user_id`),
  ADD KEY `fk_cs_counselor` (`counselor_id`);

--
-- Indexes for table `counselor_availability`
--
ALTER TABLE `counselor_availability`
  ADD PRIMARY KEY (`avail_id`),
  ADD KEY `counselor_id` (`counselor_id`,`weekday`,`start_time`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `fk_fb_session` (`session_id`),
  ADD KEY `fk_fb_user` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_pair_time` (`sender_id`,`receiver_id`,`sent_at`),
  ADD KEY `idx_recv_time` (`receiver_id`,`sent_at`);

--
-- Indexes for table `rtc_signals`
--
ALTER TABLE `rtc_signals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_thread_to` (`thread_key`,`to_user`,`id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `availability`
--
ALTER TABLE `availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `counseling_sessions`
--
ALTER TABLE `counseling_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `counselor_availability`
--
ALTER TABLE `counselor_availability`
  MODIFY `avail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `rtc_signals`
--
ALTER TABLE `rtc_signals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_ann_posted_by` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `availability`
--
ALTER TABLE `availability`
  ADD CONSTRAINT `availability_ibfk_1` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `counseling_sessions`
--
ALTER TABLE `counseling_sessions`
  ADD CONSTRAINT `fk_cs_counselor` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `fk_fb_session` FOREIGN KEY (`session_id`) REFERENCES `counseling_sessions` (`session_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fb_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
