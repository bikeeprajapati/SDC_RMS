-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2025 at 09:37 PM
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
-- Database: `rms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_terms`
--

CREATE TABLE `academic_terms` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `term_number` int(11) NOT NULL,
  `term_name` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_terms`
--

INSERT INTO `academic_terms` (`id`, `course_id`, `term_number`, `term_name`, `is_active`, `created_at`) VALUES
(2, 2, 1, 'Semester 1', 1, '2025-05-16 16:27:13'),
(3, 3, 1, 'Semester 1', 1, '2025-05-16 16:27:13'),
(4, 4, 1, 'Year 1', 1, '2025-05-16 16:27:13'),
(6, 2, 2, 'Semester 2', 1, '2025-05-16 16:27:13'),
(7, 3, 2, 'Semester 2', 1, '2025-05-16 16:27:13'),
(8, 4, 2, 'Year 2', 1, '2025-05-16 16:27:13'),
(10, 2, 3, 'Semester 3', 1, '2025-05-16 16:27:13'),
(11, 3, 3, 'Semester 3', 1, '2025-05-16 16:27:13'),
(13, 2, 4, 'Semester 4', 1, '2025-05-16 16:27:13'),
(14, 3, 4, 'Semester 4', 1, '2025-05-16 16:27:13'),
(16, 2, 5, 'Semester 5', 1, '2025-05-16 16:27:13'),
(18, 2, 6, 'Semester 6', 1, '2025-05-16 16:27:13'),
(20, 2, 7, 'Semester 7', 1, '2025-05-16 16:27:13'),
(22, 2, 8, 'Semester 8', 1, '2025-05-16 16:27:13'),
(39, 12, 1, 'Semester 1', 1, '2025-05-16 17:24:09'),
(40, 12, 2, 'Semester 2', 1, '2025-05-16 17:24:09'),
(41, 12, 3, 'Semester 3', 1, '2025-05-16 17:24:09'),
(42, 12, 4, 'Semester 4', 1, '2025-05-16 17:24:09'),
(43, 12, 5, 'Semester 5', 1, '2025-05-16 17:24:09'),
(44, 12, 6, 'Semester 6', 1, '2025-05-16 17:24:09'),
(45, 12, 7, 'Semester 7', 1, '2025-05-16 17:24:09'),
(46, 12, 8, 'Semester 8', 1, '2025-05-16 17:24:09'),
(47, 13, 1, 'Semester 1', 1, '2025-05-16 17:26:21'),
(48, 13, 2, 'Semester 2', 1, '2025-05-16 17:26:21'),
(49, 13, 3, 'Semester 3', 1, '2025-05-16 17:26:21'),
(50, 13, 4, 'Semester 4', 1, '2025-05-16 17:26:21'),
(51, 13, 5, 'Semester 5', 1, '2025-05-16 17:26:21'),
(52, 13, 6, 'Semester 6', 1, '2025-05-16 17:26:21'),
(53, 13, 7, 'Semester 7', 1, '2025-05-16 17:26:21'),
(54, 13, 8, 'Semester 8', 1, '2025-05-16 17:26:21');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `full_name`, `created_at`) VALUES
(1, 'superadmin', '$2y$10$JDgcPhSkfM2GR2iSRraAv.gLBN53k8oGaPw02DihzeoXq7f6UzVyy', 'System Administrator', '2025-05-16 16:27:13');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `is_semester_system` tinyint(1) NOT NULL DEFAULT 1,
  `total_years` int(11) NOT NULL,
  `terms_per_year` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_code`, `course_name`, `is_semester_system`, `total_years`, `terms_per_year`, `description`, `is_active`, `created_at`) VALUES
(2, 'BCA', 'Bachelor of Computer Application', 1, 4, 2, 'Four-year undergraduate computer application program with semester system', 1, '2025-05-16 16:27:13'),
(3, 'MBA', 'Master of Business Administration', 1, 2, 2, 'Two-year postgraduate business program with semester system', 1, '2025-05-16 16:27:13'),
(4, 'A-Levels', 'Cambridge A-Levels', 0, 2, 1, 'Two-year pre-university program with yearly system', 1, '2025-05-16 16:27:13'),
(12, 'BBA', 'Bachelor of Business Administration', 1, 4, 2, 'asdasd', 1, '2025-05-16 17:24:09'),
(13, 'BIM', 'Bachelor of Information Management', 1, 4, 2, 'asdasdasd', 1, '2025-05-16 17:26:21');

-- --------------------------------------------------------

--
-- Table structure for table `exam_types`
--

CREATE TABLE `exam_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_types`
--

INSERT INTO `exam_types` (`id`, `type_name`, `description`, `is_active`, `created_at`) VALUES
(1, 'First Term', 'First term examination of the academic term', 1, '2025-05-16 16:27:13'),
(2, 'Mid Term', 'Mid-term examination of the academic term', 1, '2025-05-16 16:27:13'),
(3, 'Pre-Board', 'Pre-board examination before final board exams', 1, '2025-05-16 16:27:13'),
(4, 'Final', 'Final board examination', 1, '2025-05-16 16:27:13');

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notices`
--

INSERT INTO `notices` (`id`, `title`, `content`, `is_active`, `created_at`) VALUES
(1, 'Result Published for 4th Semester 2025', 'Result Published for 4th Semester 2025', 1, '2025-05-16 18:38:55');

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `exam_type_id` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `marks` decimal(5,2) NOT NULL,
  `grade` varchar(2) NOT NULL,
  `grade_point` decimal(3,2) NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `results`
--

INSERT INTO `results` (`id`, `student_id`, `subject_id`, `exam_type_id`, `term_id`, `marks`, `grade`, `grade_point`, `remarks`, `created_at`, `created_by`) VALUES
(1, 1, 1, 3, 47, 76.00, 'B+', 3.30, 'Pass', '2025-05-16 18:57:00', 1),
(2, 1, 1, 2, 47, 40.00, 'B', 3.00, 'Pass', '2025-05-16 19:03:23', 1);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `roll_number` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `course_id` int(11) NOT NULL,
  `current_term_id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `roll_number`, `full_name`, `course_id`, `current_term_id`, `email`, `phone`, `created_at`) VALUES
(1, '1002', 'Anish Basnet', 13, 49, 'basnet.anish10@gmail.com', '9880524357', '2025-05-16 18:17:02');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `course_id` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `subject_type` enum('IT','Management') NOT NULL,
  `max_marks` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `course_id`, `term_id`, `subject_type`, `max_marks`, `created_at`) VALUES
(1, 'IT-201', 'Fundamental of IT', 13, 47, 'IT', 60, '2025-05-16 18:27:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_terms`
--
ALTER TABLE `academic_terms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_term_course` (`course_id`,`term_number`),
  ADD KEY `idx_course_term` (`course_id`,`term_number`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`);

--
-- Indexes for table `exam_types`
--
ALTER TABLE `exam_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_result` (`student_id`,`subject_id`,`exam_type_id`,`term_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `exam_type_id` (`exam_type_id`),
  ADD KEY `term_id` (`term_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roll_number` (`roll_number`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `current_term_id` (`current_term_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `term_id` (`term_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_terms`
--
ALTER TABLE `academic_terms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `exam_types`
--
ALTER TABLE `exam_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_terms`
--
ALTER TABLE `academic_terms`
  ADD CONSTRAINT `academic_terms_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`),
  ADD CONSTRAINT `fk_academic_terms_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `results_ibfk_3` FOREIGN KEY (`exam_type_id`) REFERENCES `exam_types` (`id`),
  ADD CONSTRAINT `results_ibfk_4` FOREIGN KEY (`term_id`) REFERENCES `academic_terms` (`id`),
  ADD CONSTRAINT `results_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`),
  ADD CONSTRAINT `students_ibfk_3` FOREIGN KEY (`current_term_id`) REFERENCES `academic_terms` (`id`);

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`),
  ADD CONSTRAINT `subjects_ibfk_3` FOREIGN KEY (`term_id`) REFERENCES `academic_terms` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
