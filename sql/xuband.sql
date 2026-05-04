-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 04, 2026 at 09:33 PM
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
-- Database: `xuband`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `pinned` tinyint(1) NOT NULL DEFAULT 0,
  `expires_at` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `body`, `created_by`, `pinned`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'Welcome to XUBand Digital System', 'The Xavier University Band Digital Filing System is now live. All members are encouraged to log in and update their profiles.', 1, 1, NULL, '2026-05-04 23:28:00', '2026-05-04 23:28:00'),
(2, 'Upcoming Homecoming Parade', 'All band members are required to attend the Homecoming Parade rehearsals. Please check the events calendar for schedules.', 2, 1, NULL, '2026-05-04 23:28:00', '2026-05-04 23:28:00'),
(3, 'Music Sheet Upload Reminder', 'Officers: Please upload all pending music sheets for the upcoming competition before the end of the week.', 2, 0, NULL, '2026-05-04 23:28:00', '2026-05-04 23:28:00');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `status` enum('present','absent','late') NOT NULL DEFAULT 'absent',
  `penalty_points` int(11) NOT NULL DEFAULT 0,
  `remarks` varchar(255) DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `event_id`, `status`, `penalty_points`, `remarks`, `recorded_by`, `created_at`) VALUES
(1, 3, 1, 'present', 0, NULL, 2, '2026-05-04 23:28:00'),
(2, 4, 1, 'present', 0, NULL, 2, '2026-05-04 23:28:00'),
(3, 5, 1, 'absent', 150, NULL, 2, '2026-05-04 23:28:00'),
(4, 6, 1, 'late', 75, NULL, 2, '2026-05-04 23:28:00'),
(5, 7, 1, 'present', 0, NULL, 2, '2026-05-04 23:28:00');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `type` enum('rehearsal','performance','meeting','competition','other') NOT NULL DEFAULT 'rehearsal',
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `type`, `event_date`, `event_time`, `location`, `description`, `created_by`, `created_at`) VALUES
(1, 'Weekly Rehearsal', 'rehearsal', '2026-05-06', '16:00:00', 'XU Band Hall', 'Regular weekly rehearsal.', 2, '2026-05-04 23:28:00'),
(2, 'Homecoming Parade', 'performance', '2026-05-18', '08:00:00', 'XU Campus', 'Annual homecoming parade.', 2, '2026-05-04 23:28:00'),
(3, 'Band Meeting', 'meeting', '2026-05-09', '17:00:00', 'Band Room 101', 'Monthly general assembly.', 2, '2026-05-04 23:28:00');

-- --------------------------------------------------------

--
-- Table structure for table `music_assignments`
--

CREATE TABLE `music_assignments` (
  `id` int(11) NOT NULL,
  `sheet_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `music_assignments`
--

INSERT INTO `music_assignments` (`id`, `sheet_id`, `user_id`, `assigned_by`, `assigned_at`) VALUES
(6, 1, 5, 1, '2026-05-04 23:36:37'),
(7, 1, 3, 1, '2026-05-04 23:36:37');

-- --------------------------------------------------------

--
-- Table structure for table `music_folders`
--

CREATE TABLE `music_folders` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `music_folders`
--

INSERT INTO `music_folders` (`id`, `name`, `description`, `created_by`, `created_at`) VALUES
(1, 'ABBA Medley', 'ABBA arrangements for the homecoming parade', 2, '2026-05-04 23:28:00'),
(2, 'Kyle Rusty Brazil', 'Imoha nana gaw', 1, '2026-05-04 23:35:52');

-- --------------------------------------------------------

--
-- Table structure for table `music_sheets`
--

CREATE TABLE `music_sheets` (
  `id` int(11) NOT NULL,
  `folder_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `instrument` varchar(100) DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `music_sheets`
--

INSERT INTO `music_sheets` (`id`, `folder_id`, `title`, `instrument`, `file_path`, `file_name`, `file_size`, `file_type`, `uploaded_by`, `created_at`) VALUES
(1, 2, 'HAHAH', 'AHAHAHAHA', 'uploads/music-sheets/69f8bcfa1720d7.15725613_simag.png', 'simag.png', 207543, 'image/png', 1, '2026-05-04 23:36:26');

-- --------------------------------------------------------

--
-- Table structure for table `penalty_summary`
--

CREATE TABLE `penalty_summary` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_points` int(11) NOT NULL DEFAULT 0,
  `last_computed` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penalty_summary`
--

INSERT INTO `penalty_summary` (`id`, `user_id`, `total_points`, `last_computed`) VALUES
(1, 3, 0, '2026-05-04 23:28:00'),
(2, 4, 0, '2026-05-04 23:28:00'),
(3, 5, 150, '2026-05-04 23:28:00'),
(4, 6, 75, '2026-05-04 23:28:00'),
(5, 7, 0, '2026-05-04 23:28:00');

-- --------------------------------------------------------

--
-- Table structure for table `scholarships`
--

CREATE TABLE `scholarships` (
  `id` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('Full Scholar','Half Scholar','Not Scholar') NOT NULL DEFAULT 'Not Scholar',
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scholarships`
--

INSERT INTO `scholarships` (`id`, `term_id`, `user_id`, `status`, `updated_by`, `updated_at`) VALUES
(1, 1, 3, 'Full Scholar', 2, '2026-05-04 23:28:00'),
(2, 1, 4, 'Full Scholar', 2, '2026-05-04 23:28:00'),
(3, 1, 5, 'Half Scholar', 2, '2026-05-04 23:28:00'),
(4, 1, 6, 'Full Scholar', 2, '2026-05-04 23:28:00'),
(5, 1, 7, 'Half Scholar', 2, '2026-05-04 23:28:00');

-- --------------------------------------------------------

--
-- Table structure for table `scholarship_terms`
--

CREATE TABLE `scholarship_terms` (
  `id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `term` enum('1st Semester','2nd Semester','Summer') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scholarship_terms`
--

INSERT INTO `scholarship_terms` (`id`, `school_year_id`, `term`) VALUES
(1, 1, '1st Semester'),
(2, 1, '2nd Semester'),
(3, 1, 'Summer');

-- --------------------------------------------------------

--
-- Table structure for table `school_years`
--

CREATE TABLE `school_years` (
  `id` int(11) NOT NULL,
  `label` varchar(50) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_years`
--

INSERT INTO `school_years` (`id`, `label`, `created_by`, `created_at`) VALUES
(1, '2024-2025', 1, '2026-05-04 23:28:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('moderator','officer','member') NOT NULL DEFAULT 'member',
  `instrument` varchar(100) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `profile_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `instrument`, `year_level`, `student_id`, `contact_number`, `status`, `profile_notes`, `created_at`, `updated_at`) VALUES
(1, 'Band Moderator', 'moderator@xuband.edu.ph', '$2b$10$bgJSY64smLKwTYVtv0SXhuXDjij9BzVnpm45cPQqrcOg8OWjIrIia', 'moderator', NULL, NULL, 'MOD-001', NULL, 'active', NULL, '2026-05-04 23:28:00', '2026-05-04 23:28:00'),
(2, 'Guen Alexis Gabutin', 'gabutin@xuband.edu.ph', '$2b$10$bgJSY64smLKwTYVtv0SXhuXDjij9BzVnpm45cPQqrcOg8OWjIrIia', 'officer', 'Trumpet', '3rd Year', 'XU-2022-001', NULL, 'active', NULL, '2026-05-04 23:28:00', '2026-05-05 00:00:36'),
(3, 'Jude P. Macalaguing', 'macalaguing@xuband.edu.ph', '$2b$10$bgJSY64smLKwTYVtv0SXhuXDjij9BzVnpm45cPQqrcOg8OWjIrIia', 'member', 'Trombone', '2nd Year', 'XU-2023-001', NULL, 'active', NULL, '2026-05-04 23:28:00', '2026-05-04 23:28:00'),
(4, 'Nicole Sai Sophie Gabutan', 'gabutan@xuband.edu.ph', '$2b$10$bgJSY64smLKwTYVtv0SXhuXDjij9BzVnpm45cPQqrcOg8OWjIrIia', 'member', 'Flute', '3rd Year', 'XU-2022-002', NULL, 'active', NULL, '2026-05-04 23:28:00', '2026-05-04 23:28:00'),
(5, 'Christopher Basin', 'basin@xuband.edu.ph', '$2b$10$bgJSY64smLKwTYVtv0SXhuXDjij9BzVnpm45cPQqrcOg8OWjIrIia', 'member', 'Percussion', '1st Year', 'XU-2024-001', NULL, 'active', NULL, '2026-05-04 23:28:00', '2026-05-04 23:28:00'),
(6, 'Kyle Rusty Brazil', 'brazil@xuband.edu.ph', '$2b$10$bgJSY64smLKwTYVtv0SXhuXDjij9BzVnpm45cPQqrcOg8OWjIrIia', 'member', 'Saxophone', '2nd Year', 'XU-2023-002', NULL, 'active', NULL, '2026-05-04 23:28:00', '2026-05-04 23:28:00'),
(7, 'Mayeoh Fay D. Barangot', 'barangot@xuband.edu.ph', '$2b$10$bgJSY64smLKwTYVtv0SXhuXDjij9BzVnpm45cPQqrcOg8OWjIrIia', 'member', 'Clarinet', '1st Year', 'XU-2024-002', NULL, 'active', NULL, '2026-05-04 23:28:00', '2026-05-04 23:28:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`user_id`,`event_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `music_assignments`
--
ALTER TABLE `music_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assign` (`sheet_id`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `music_folders`
--
ALTER TABLE `music_folders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `music_sheets`
--
ALTER TABLE `music_sheets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `folder_id` (`folder_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `penalty_summary`
--
ALTER TABLE `penalty_summary`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `scholarships`
--
ALTER TABLE `scholarships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_scholarship` (`term_id`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `scholarship_terms`
--
ALTER TABLE `scholarship_terms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_term` (`school_year_id`,`term`);

--
-- Indexes for table `school_years`
--
ALTER TABLE `school_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `label` (`label`),
  ADD KEY `created_by` (`created_by`);

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
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `music_assignments`
--
ALTER TABLE `music_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `music_folders`
--
ALTER TABLE `music_folders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `music_sheets`
--
ALTER TABLE `music_sheets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `penalty_summary`
--
ALTER TABLE `penalty_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `scholarships`
--
ALTER TABLE `scholarships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `scholarship_terms`
--
ALTER TABLE `scholarship_terms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `school_years`
--
ALTER TABLE `school_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `music_assignments`
--
ALTER TABLE `music_assignments`
  ADD CONSTRAINT `music_assignments_ibfk_1` FOREIGN KEY (`sheet_id`) REFERENCES `music_sheets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `music_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `music_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `music_folders`
--
ALTER TABLE `music_folders`
  ADD CONSTRAINT `music_folders_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `music_sheets`
--
ALTER TABLE `music_sheets`
  ADD CONSTRAINT `music_sheets_ibfk_1` FOREIGN KEY (`folder_id`) REFERENCES `music_folders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `music_sheets_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `penalty_summary`
--
ALTER TABLE `penalty_summary`
  ADD CONSTRAINT `penalty_summary_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `scholarships`
--
ALTER TABLE `scholarships`
  ADD CONSTRAINT `scholarships_ibfk_1` FOREIGN KEY (`term_id`) REFERENCES `scholarship_terms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scholarships_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scholarships_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `scholarship_terms`
--
ALTER TABLE `scholarship_terms`
  ADD CONSTRAINT `scholarship_terms_ibfk_1` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `school_years`
--
ALTER TABLE `school_years`
  ADD CONSTRAINT `school_years_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
