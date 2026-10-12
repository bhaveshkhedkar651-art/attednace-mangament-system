-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 12, 2026 at 07:07 AM
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
-- Database: `attendance_data`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `qr_session_id` int(11) DEFAULT NULL,
  `qr_token` varchar(500) DEFAULT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `status` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `subject_id`, `qr_session_id`, `qr_token`, `date`, `time`, `status`) VALUES
(31, 91, 14, 501, NULL, '2026-10-11', '17:26:54', 'PRESENT'),
(32, 91, 15, 502, NULL, '2026-10-11', '17:56:56', 'PRESENT');

-- --------------------------------------------------------

--
-- Table structure for table `qr_session`
--

CREATE TABLE `qr_session` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `qr_token` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qr_session`
--

INSERT INTO `qr_session` (`id`, `teacher_id`, `subject_id`, `qr_token`, `created_at`, `expires_at`) VALUES
(500, 11, 14, 'c4617f7c2ddd14450dfc634444fa2fe1', '2026-10-10 00:30:35', '2026-10-10 10:30:35'),
(501, 11, 14, '87a15f27ec6a6238a37842147099d1f4', '2026-10-11 16:46:40', '2026-10-12 02:46:40'),
(502, 11, 15, '88e7b2d82eaf88a8a69eb660899c81d4', '2026-10-11 17:55:11', '2026-10-12 03:55:11');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `roll_no` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `class` varchar(10) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `PASSWORD` varchar(255) NOT NULL,
  `photo` varchar(500) DEFAULT NULL,
  `reset_code` varchar(10) DEFAULT NULL,
  `reset_code_expiry` datetime DEFAULT NULL,
  `reset_attempts` int(11) DEFAULT 0,
  `reset_requested_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `roll_no`, `name`, `class`, `email`, `PASSWORD`, `photo`, `reset_code`, `reset_code_expiry`, `reset_attempts`, `reset_requested_at`) VALUES
(59, 2021, 'Manish kumbhar ', 'TYBCA', 'kumbharmanish9890@gmail.com', '$2y$10$RGUbQcfVsAs2TDaEFcY3cuuSfrOrlAfrC.yAQA0rH0j', '1788501491_1000301230.jpg', NULL, NULL, 0, NULL),
(65, 102, 'Sachin khedkar', 'SYBCA', 'sachinkhedkar123@gmail.com', '$2y$10$6Byoxc5F5/wjuCrXBkfUPOqMvi6q1lZbe1VGgEGgSWT', '1789827131_1000308046.jpg', NULL, NULL, 0, NULL),
(74, 132, 'Manish Kumbhar', 'TYBCA', 'mkumbhar1801@gmail.com', '$2y$10$5T4tIfs3paUeZzVBsdMhc.PHfo4p8RfsVHZ.0Symfgt', '1790055990_151719.jpg', NULL, NULL, 0, NULL),
(79, 52, 'Nakul Ahire', 'TYBCA', 'nakulahire9@gmail.com', '$2y$10$4B25YLNGm74w98yz71booO/Sl9Ltbsrg8PTTUs3DhAKnJUOaSV0Km', '1791315242_IMG_20250811_192529_365.webp', NULL, NULL, 0, NULL),
(91, 101, 'Bhavesh khedkar', 'TYBCA', 'bhaveshkhedkar651@gmail.com', '$2y$10$60SBtpPMMeTWLa2s8kSXtuheV15P9rpnha79ybqmqG9UyUTZADOc2', 'd5bf362548d0b545bc272c8f24fc511a.png', NULL, NULL, 0, NULL),
(92, 5460009, 'Shahema Lohar', 'TYBCA', 'shahemalohar@gmail.com', '$2y$10$4QsFxw9E7pny3Vbav3EcNOHCrTP35ceDPDS5bW.LOCuF3StTWOpbi', 'e8def15c52c742e6c7d8a283a8167130.jpg', '491513', '2026-10-07 22:38:37', 0, '2026-10-07 22:28:37'),
(96, 25101060, 'Pranav bagul', 'SYBCA', 'pranavbagul942@gmail.com', '$2y$10$VWBbEz.2xucbAgq7jMAXX.0xlxr4ykr1LV4aX.7y7ROEUzvpEu2RG', '189c6aabc36823e6b8573a4d41e82c0d.jpg', NULL, NULL, 0, NULL),
(98, 7, 'Jk', 'TYBCA', 'jkdreamer007@gmail.com', '$2y$10$B9BQ66plVTkn8fzUlRlgeuXf98mJW9KLY8q0vF0A.UHGQmeOa1jDm', '799a4c31c3a5d22ef9c0de16ad426f84.png', '807064', '2026-10-07 22:58:29', 0, '2026-10-07 22:48:29'),
(99, 191, 'Saurav Pathak', 'TYBCA', 'sauravpathak1pl@gmail.com', '$2y$10$GnMhcbunMbAB/EDMc4TED.uETRi4/lpGwEYm61Xo5oZH.jeMtZHMm', '884c27ebed3228f8603d58dbfa7edf36.jpg', NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_name`, `teacher_id`) VALUES
(14, 'C#', 11),
(15, 'C++', 11);

-- --------------------------------------------------------

--
-- Table structure for table `teacher`
--

CREATE TABLE `teacher` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher`
--

INSERT INTO `teacher` (`id`, `name`, `email`, `password`) VALUES
(11, 'BHAVESH KHEDKAR', 'bhaveshkhedkar651@gmail.com', '$2y$10$OJnmkEABRpTe24Z8vFHsmOHsbs/Oaxyd5AsmUyLoRMe8fUHLo8zsK');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `qr_token` (`qr_token`),
  ADD KEY `qr_session_id` (`qr_session_id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_subject_date` (`subject_id`,`date`),
  ADD KEY `idx_student_subject_date` (`student_id`,`subject_id`,`date`);

--
-- Indexes for table `qr_session`
--
ALTER TABLE `qr_session`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `qr_token` (`qr_token`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `idx_token` (`qr_token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roll_no` (`roll_no`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `teacher`
--
ALTER TABLE `teacher`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `qr_session`
--
ALTER TABLE `qr_session`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=503;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `teacher`
--
ALTER TABLE `teacher`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`qr_session_id`) REFERENCES `qr_session` (`id`);

--
-- Constraints for table `qr_session`
--
ALTER TABLE `qr_session`
  ADD CONSTRAINT `qr_session_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teacher` (`id`),
  ADD CONSTRAINT `qr_session_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teacher` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
