-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 23, 2025 at 07:24 AM
-- Server version: 10.6.21-MariaDB-cll-lve
-- PHP Version: 8.3.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `flipperschool_parent_survey_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_terms`
--

CREATE TABLE `academic_terms` (
  `id` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `term_name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_terms`
--

INSERT INTO `academic_terms` (`id`, `academic_year`, `term_name`, `start_date`, `end_date`, `is_current`) VALUES
(1, '2025-2026', 'Term 1', '2025-09-01', '2025-12-15', 1),
(2, '2025-2026', 'Term 2', '2026-01-07', '2026-03-25', 0),
(3, '2025-2026', 'Term 3', '2026-04-08', '2026-06-30', 0);

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `target_roles` varchar(255) DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 4, 'login', 'User logged in', '196.190.62.29', '2025-04-22 20:48:37'),
(2, 4, 'login', 'User logged in', '196.190.62.29', '2025-04-22 21:21:46');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `class_name` varchar(100) NOT NULL,
  `class_level_id` int(11) DEFAULT NULL,
  `curriculum_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `class_name`, `class_level_id`, `curriculum_id`, `created_at`) VALUES
(1, 'Grade 1', 1, 1, '2025-04-23 05:15:10'),
(2, 'Grade 2', 1, 1, '2025-04-23 05:15:10'),
(3, 'Grade 3', 1, 1, '2025-04-23 05:15:10'),
(4, 'Grade 4', 1, 1, '2025-04-23 05:15:10'),
(5, 'Grade 5', 1, 1, '2025-04-23 05:15:10'),
(6, 'Grade 6', 1, 1, '2025-04-23 05:15:10'),
(7, 'Grade 7', 2, 1, '2025-04-23 05:15:10'),
(8, 'Grade 8', 2, 1, '2025-04-23 05:15:10'),
(9, 'Grade 9', 2, 1, '2025-04-23 05:15:10'),
(10, 'Grade 10 (IGCSE Year 1)', 3, 1, '2025-04-23 05:15:10'),
(11, 'Grade 11 (IGCSE Year 2)', 3, 1, '2025-04-23 05:15:10'),
(12, 'Grade 12 (AS Level)', 4, 1, '2025-04-23 05:15:10'),
(13, 'Grade 13 (A Level)', 4, 1, '2025-04-23 05:15:10');

-- --------------------------------------------------------

--
-- Table structure for table `class_levels`
--

CREATE TABLE `class_levels` (
  `id` int(11) NOT NULL,
  `curriculum_id` int(11) NOT NULL,
  `level_name` varchar(100) NOT NULL,
  `level_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_levels`
--

INSERT INTO `class_levels` (`id`, `curriculum_id`, `level_name`, `level_order`) VALUES
(1, 1, 'Primary (Grades 1-6)', 1),
(2, 1, 'Lower Secondary (Grades 7-9)', 2),
(3, 1, 'Upper Secondary (IGCSE - Grades 10-11)', 3),
(4, 1, 'Advanced (AS & A Level - Grades 12-13)', 4);

-- --------------------------------------------------------

--
-- Table structure for table `class_subjects`
--

CREATE TABLE `class_subjects` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `is_core` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_subjects`
--

INSERT INTO `class_subjects` (`id`, `class_id`, `subject_id`, `is_core`) VALUES
(1, 10, 16, 1),
(2, 10, 18, 1),
(3, 10, 20, 1),
(4, 10, 21, 1),
(5, 10, 22, 1),
(6, 10, 23, 0),
(7, 10, 24, 0),
(8, 11, 16, 1),
(9, 11, 18, 1),
(10, 11, 20, 1),
(11, 11, 21, 1),
(12, 11, 22, 1),
(13, 11, 23, 0),
(14, 11, 24, 0);

-- --------------------------------------------------------

--
-- Table structure for table `curriculums`
--

CREATE TABLE `curriculums` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `curriculums`
--

INSERT INTO `curriculums` (`id`, `name`, `country`, `description`) VALUES
(1, 'Cambridge Curriculum', 'International', 'This is Cambridge Curriculum'),
(2, 'US K-12', 'USA', NULL),
(3, 'British Curriculum', 'UK', NULL),
(4, 'CBSE', 'India', NULL),
(5, 'IB', 'International', NULL),
(6, 'IGCSE', 'International', NULL),
(7, 'Ethiopian Curriculum', 'Ethiopia', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `grading_scale_id` int(11) DEFAULT NULL,
  `score` decimal(5,2) NOT NULL,
  `grade_letter` varchar(10) DEFAULT NULL,
  `term` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grading_scales`
--

CREATE TABLE `grading_scales` (
  `id` int(11) NOT NULL,
  `curriculum_id` int(11) DEFAULT NULL,
  `scale_name` varchar(100) NOT NULL,
  `min_score` decimal(5,2) NOT NULL,
  `max_score` decimal(5,2) NOT NULL,
  `grade_letter` varchar(10) NOT NULL,
  `remark` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grading_scales`
--

INSERT INTO `grading_scales` (`id`, `curriculum_id`, `scale_name`, `min_score`, `max_score`, `grade_letter`, `remark`, `description`) VALUES
(1, 1, 'Primary Checkpoint', 90.00, 100.00, 'A*', 'Outstanding', 'Primary Checkpoint Assessment Scale'),
(2, 1, 'Primary Checkpoint', 80.00, 89.99, 'A', 'Excellent', 'Primary Checkpoint Assessment Scale'),
(3, 1, 'Primary Checkpoint', 70.00, 79.99, 'B', 'Good', 'Primary Checkpoint Assessment Scale'),
(4, 1, 'Primary Checkpoint', 60.00, 69.99, 'C', 'Satisfactory', 'Primary Checkpoint Assessment Scale'),
(5, 1, 'Primary Checkpoint', 50.00, 59.99, 'D', 'Needs Improvement', 'Primary Checkpoint Assessment Scale'),
(6, 1, 'Primary Checkpoint', 0.00, 49.99, 'E', 'Below Standard', 'Primary Checkpoint Assessment Scale'),
(7, 1, 'Lower Secondary Checkpoint', 90.00, 100.00, 'A*', 'Outstanding', 'Lower Secondary Checkpoint Assessment Scale'),
(8, 1, 'Lower Secondary Checkpoint', 80.00, 89.99, 'A', 'Excellent', 'Lower Secondary Checkpoint Assessment Scale'),
(9, 1, 'Lower Secondary Checkpoint', 70.00, 79.99, 'B', 'Good', 'Lower Secondary Checkpoint Assessment Scale'),
(10, 1, 'Lower Secondary Checkpoint', 60.00, 69.99, 'C', 'Satisfactory', 'Lower Secondary Checkpoint Assessment Scale'),
(11, 1, 'Lower Secondary Checkpoint', 50.00, 59.99, 'D', 'Needs Improvement', 'Lower Secondary Checkpoint Assessment Scale'),
(12, 1, 'Lower Secondary Checkpoint', 0.00, 49.99, 'E', 'Below Standard', 'Lower Secondary Checkpoint Assessment Scale'),
(13, 1, 'IGCSE', 90.00, 100.00, 'A*', 'Exceptional', 'IGCSE Grading Scale'),
(14, 1, 'IGCSE', 80.00, 89.99, 'A', 'Excellent', 'IGCSE Grading Scale'),
(15, 1, 'IGCSE', 70.00, 79.99, 'B', 'Good', 'IGCSE Grading Scale'),
(16, 1, 'IGCSE', 60.00, 69.99, 'C', 'Satisfactory', 'IGCSE Grading Scale'),
(17, 1, 'IGCSE', 50.00, 59.99, 'D', 'Minimum Pass', 'IGCSE Grading Scale'),
(18, 1, 'IGCSE', 40.00, 49.99, 'E', 'Below Pass', 'IGCSE Grading Scale'),
(19, 1, 'IGCSE', 0.00, 39.99, 'F', 'Fail', 'IGCSE Grading Scale'),
(20, 1, 'IGCSE', 0.00, 39.99, 'G', 'Fail', 'IGCSE Grading Scale'),
(21, 1, 'AS Level', 90.00, 100.00, 'a', 'Outstanding', 'AS Level Grading Scale'),
(22, 1, 'AS Level', 80.00, 89.99, 'b', 'Good', 'AS Level Grading Scale'),
(23, 1, 'AS Level', 70.00, 79.99, 'c', 'Satisfactory', 'AS Level Grading Scale'),
(24, 1, 'AS Level', 60.00, 69.99, 'd', 'Minimum Pass', 'AS Level Grading Scale'),
(25, 1, 'AS Level', 50.00, 59.99, 'e', 'Below Pass', 'AS Level Grading Scale'),
(26, 1, 'AS Level', 0.00, 49.99, 'f', 'Fail', 'AS Level Grading Scale'),
(27, 1, 'A Level', 90.00, 100.00, 'A*', 'Exceptional', 'A Level Grading Scale'),
(28, 1, 'A Level', 80.00, 89.99, 'A', 'Excellent', 'A Level Grading Scale'),
(29, 1, 'A Level', 70.00, 79.99, 'B', 'Good', 'A Level Grading Scale'),
(30, 1, 'A Level', 60.00, 69.99, 'C', 'Satisfactory', 'A Level Grading Scale'),
(31, 1, 'A Level', 50.00, 59.99, 'D', 'Minimum Pass', 'A Level Grading Scale'),
(32, 1, 'A Level', 40.00, 49.99, 'E', 'Below Pass', 'A Level Grading Scale'),
(33, 1, 'A Level', 0.00, 39.99, 'F', 'Fail', 'A Level Grading Scale');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `sent_at` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parents`
--

CREATE TABLE `parents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `description`, `created_at`) VALUES
(1, 'admin', 'System Administrator', '2025-03-28 16:53:20'),
(2, 'teacher', 'Teaching Staff', '2025-03-28 16:52:46'),
(3, 'parent', 'Student Parent', '2025-03-28 16:53:05'),
(4, 'student', 'School Student', '2025-03-28 16:53:39');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `section_name` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `class_id`, `section_name`, `created_at`) VALUES
(1, 1, 'A', '2025-04-23 05:15:28'),
(2, 1, 'B', '2025-04-23 05:15:28'),
(3, 1, 'C', '2025-04-23 05:15:28'),
(4, 2, 'A', '2025-04-23 05:15:28'),
(5, 2, 'B', '2025-04-23 05:15:28'),
(6, 2, 'C', '2025-04-23 05:15:28'),
(40, 13, 'A', '2025-04-23 05:15:28'),
(41, 13, 'B', '2025-04-23 05:15:28');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `enrollment_no` varchar(50) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `class_id`, `section_id`, `enrollment_no`, `date_of_birth`, `gender`, `address`, `status`, `created_at`) VALUES
(3, 5, 10, 3, 'STU001', '2010-05-15', 'Male', '123 Student Street, Addis Ababa', 'active', '2025-04-23 05:23:51'),
(4, 65, 10, 3, 'STU002', '2011-03-22', 'Male', '456 Learner Avenue, Addis Ababa', 'active', '2025-04-23 05:23:51');

-- --------------------------------------------------------

--
-- Table structure for table `student_parents`
--

CREATE TABLE `student_parents` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `relationship` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `curriculum_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `curriculum_id`, `subject_name`, `description`, `created_at`) VALUES
(1, 1, 'English', 'Primary English', '2025-04-23 05:16:40'),
(2, 1, 'Mathematics', 'Primary Mathematics', '2025-04-23 05:16:40'),
(3, 1, 'Science', 'Primary Science', '2025-04-23 05:16:40'),
(4, 1, 'Global Perspectives', 'Primary Global Perspectives', '2025-04-23 05:16:40'),
(5, 1, 'ICT', 'Primary Information and Communication Technology', '2025-04-23 05:16:40'),
(6, 1, 'Art & Design', 'Primary Art and Design', '2025-04-23 05:16:40'),
(7, 1, 'Music', 'Primary Music', '2025-04-23 05:16:40'),
(8, 1, 'Physical Education', 'Primary PE', '2025-04-23 05:16:40'),
(9, 1, 'English', 'Lower Secondary English', '2025-04-23 05:16:40'),
(10, 1, 'Mathematics', 'Lower Secondary Mathematics', '2025-04-23 05:16:40'),
(11, 1, 'Science', 'Lower Secondary Science (Biology, Chemistry, Physics)', '2025-04-23 05:16:40'),
(12, 1, 'Global Perspectives', 'Lower Secondary Global Perspectives', '2025-04-23 05:16:40'),
(13, 1, 'ICT', 'Lower Secondary ICT', '2025-04-23 05:16:40'),
(14, 1, 'First Language (Amharic)', 'Lower Secondary First Language', '2025-04-23 05:16:40'),
(15, 1, 'Foreign Language (French)', 'Lower Secondary Foreign Language', '2025-04-23 05:16:40'),
(16, 1, 'English - First Language', 'IGCSE English First Language', '2025-04-23 05:16:40'),
(17, 1, 'English - Second Language', 'IGCSE English Second Language', '2025-04-23 05:16:40'),
(18, 1, 'Mathematics', 'IGCSE Mathematics', '2025-04-23 05:16:40'),
(19, 1, 'Additional Mathematics', 'IGCSE Additional Mathematics', '2025-04-23 05:16:40'),
(20, 1, 'Biology', 'IGCSE Biology', '2025-04-23 05:16:40'),
(21, 1, 'Chemistry', 'IGCSE Chemistry', '2025-04-23 05:16:40'),
(22, 1, 'Physics', 'IGCSE Physics', '2025-04-23 05:16:40'),
(23, 1, 'Business Studies', 'IGCSE Business Studies', '2025-04-23 05:16:40'),
(24, 1, 'Economics', 'IGCSE Economics', '2025-04-23 05:16:40'),
(25, 1, 'Accounting', 'IGCSE Accounting', '2025-04-23 05:16:40'),
(26, 1, 'ICT', 'IGCSE Information and Communication Technology', '2025-04-23 05:16:40'),
(27, 1, 'Computer Science', 'IGCSE Computer Science', '2025-04-23 05:16:40'),
(28, 1, 'Geography', 'IGCSE Geography', '2025-04-23 05:16:40'),
(29, 1, 'History', 'IGCSE History', '2025-04-23 05:16:40'),
(30, 1, 'Foreign Language (French)', 'IGCSE French', '2025-04-23 05:16:40'),
(31, 1, 'Foreign Language (Spanish)', 'IGCSE Spanish', '2025-04-23 05:16:40'),
(32, 1, 'Foreign Language (Arabic)', 'IGCSE Arabic', '2025-04-23 05:16:40'),
(33, 1, 'Art & Design', 'IGCSE Art and Design', '2025-04-23 05:16:40'),
(34, 1, 'Music', 'IGCSE Music', '2025-04-23 05:16:40'),
(35, 1, 'Physical Education', 'IGCSE PE', '2025-04-23 05:16:40'),
(36, 1, 'English - Language & Literature', 'AS/A Level English Language and Literature', '2025-04-23 05:16:40'),
(37, 1, 'Mathematics', 'AS/A Level Mathematics', '2025-04-23 05:16:40'),
(38, 1, 'Further Mathematics', 'AS/A Level Further Mathematics', '2025-04-23 05:16:40'),
(39, 1, 'Biology', 'AS/A Level Biology', '2025-04-23 05:16:40'),
(40, 1, 'Chemistry', 'AS/A Level Chemistry', '2025-04-23 05:16:40'),
(41, 1, 'Physics', 'AS/A Level Physics', '2025-04-23 05:16:40'),
(42, 1, 'Business', 'AS/A Level Business', '2025-04-23 05:16:40'),
(43, 1, 'Economics', 'AS/A Level Economics', '2025-04-23 05:16:40'),
(44, 1, 'Accounting', 'AS/A Level Accounting', '2025-04-23 05:16:40'),
(45, 1, 'Computer Science', 'AS/A Level Computer Science', '2025-04-23 05:16:40'),
(46, 1, 'Psychology', 'AS/A Level Psychology', '2025-04-23 05:16:40'),
(47, 1, 'Global Perspectives', 'AS/A Level Global Perspectives', '2025-04-23 05:16:40'),
(48, 1, 'Geography', 'AS/A Level Geography', '2025-04-23 05:16:40'),
(49, 1, 'History', 'AS/A Level History', '2025-04-23 05:16:40'),
(50, 1, 'Art & Design', 'AS/A Level Art and Design', '2025-04-23 05:16:40'),
(51, 1, 'Music', 'AS/A Level Music', '2025-04-23 05:16:40');

-- --------------------------------------------------------

--
-- Table structure for table `surveys`
--

CREATE TABLE `surveys` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `survey_fields`
--

CREATE TABLE `survey_fields` (
  `id` int(11) NOT NULL,
  `survey_id` int(11) NOT NULL,
  `field_type` enum('text','textarea','radio','checkbox','select','number','date','rating','file') NOT NULL,
  `field_label` varchar(255) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `field_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`field_options`)),
  `is_required` tinyint(1) DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `survey_responses`
--

CREATE TABLE `survey_responses` (
  `id` int(11) NOT NULL,
  `survey_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`answers`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `subject_specialization` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `qualification`, `subject_specialization`, `date_of_birth`, `gender`, `address`, `status`, `created_at`) VALUES
(1, 69, 'BSc Degree in Business', 'Business Studies', '1985-08-10', 'Male', '789 Educator Road, Addis Ababa', 'active', '2025-04-23 05:28:17');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_subjects`
--

CREATE TABLE `teacher_subjects` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `class_subject_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `avatar` varchar(255) DEFAULT 'default.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `first_name`, `last_name`, `role_id`, `active`, `created_at`, `last_login`, `avatar`) VALUES
(4, 'administrator', '$2y$10$NzdfGBS05PUk3gh0C9Cmfu6WL1bvexg4Xin/5hItCo2GcoMoOKTbO', 'adugna.gizaw@flipperschools.com', 'Admin', 'System', 1, 1, '2025-03-25 03:50:31', '2025-04-22 21:21:46', 'admin_avatar.jpg'),
(5, 'efream', '$2y$10$MVeN3l2MkGpfz7fvjOPGEORMcLh0zArHGtACBXvp7e2Vi14QH/Ldm', 'mcdc@gmail.com', 'Efream', 'Yohannes', 4, 1, '2025-03-25 11:47:11', '2025-04-21 07:40:11', 'student_avatar5.jpg'),
(65, 'Adugna1', '$2y$10$mVnaYcK/FyHuL7meR9J5susyTa.6T4tgUt6Ci7xcLpMsREPWX6R3G', 'gizawadugna@gmail.com', 'Adugna', 'Gizaw', 4, 1, '2025-03-29 01:03:37', '2025-04-21 12:52:56', 'avatar_65_053303628160f3c6.png'),
(66, 'gizawadugna1', '$2y$10$lc./P6NQpbQoCJ8j6PkI.ecLmF5mJ3n5ykcwXZ2DzZ8IGk/E5w/2W', 'gizawadugna1@gmail.com', 'Gizaw', 'Parent', 3, 1, '2025-04-21 07:18:47', '2025-04-21 09:07:43', 'parent_avatar66.jpg'),
(67, 'abel', '$2y$10$bGlkJRnMYCgBgcmpmCKtR.Kej4OY9UEb8h66IAXmtxuBpDBWZZ7wu', 'efreamyohannes@gmail.com', 'Abel', 'Manager', 1, 1, '2025-04-21 08:03:05', '2025-04-21 09:16:38', 'admin_avatar67.jpg'),
(68, 'developermustafa', '$2y$10$ztkwBLG9mvtcipjuNnhc5Otu3obOiAQKQYO.ScHvZpIFBrFu6RluK', 'mustafarahman792@gmail.com', 'Mustafa', 'Rahman', 1, 1, '2025-04-21 15:55:48', '2025-04-21 15:55:53', 'default.jpg'),
(69, 'gizawadugna3', '$2y$10$Zhuo9Q3Efpz5Y1o8AckTpOeXCTEjK3138VIBRFiQ8bNy1joC.kx7u', 'gizawadugna3@gmail.com', 'Adugna', 'Gizaw', 2, 1, '2025-04-22 06:13:36', NULL, 'teacher_avatar69.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_terms`
--
ALTER TABLE `academic_terms`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_level_id` (`class_level_id`),
  ADD KEY `curriculum_id` (`curriculum_id`);

--
-- Indexes for table `class_levels`
--
ALTER TABLE `class_levels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `curriculum_id` (`curriculum_id`);

--
-- Indexes for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `class_subject_unique` (`class_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `curriculums`
--
ALTER TABLE `curriculums`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `grading_scale_id` (`grading_scale_id`);

--
-- Indexes for table `grading_scales`
--
ALTER TABLE `grading_scales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `curriculum_id` (`curriculum_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `student_parents`
--
ALTER TABLE `student_parents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `curriculum_id` (`curriculum_id`),
  ADD KEY `subject_name` (`subject_name`) USING BTREE;

--
-- Indexes for table `surveys`
--
ALTER TABLE `surveys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `survey_fields`
--
ALTER TABLE `survey_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `survey_id` (`survey_id`);

--
-- Indexes for table `survey_responses`
--
ALTER TABLE `survey_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `survey_id` (`survey_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `fk_teacher_subjects_class_subjects` (`class_subject_id`),
  ADD KEY `fk_teacher_subjects_sections` (`section_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_terms`
--
ALTER TABLE `academic_terms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `class_levels`
--
ALTER TABLE `class_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `class_subjects`
--
ALTER TABLE `class_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `curriculums`
--
ALTER TABLE `curriculums`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grading_scales`
--
ALTER TABLE `grading_scales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parents`
--
ALTER TABLE `parents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `student_parents`
--
ALTER TABLE `student_parents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `surveys`
--
ALTER TABLE `surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `survey_fields`
--
ALTER TABLE `survey_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `survey_responses`
--
ALTER TABLE `survey_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

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
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`class_level_id`) REFERENCES `class_levels` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `classes_ibfk_2` FOREIGN KEY (`curriculum_id`) REFERENCES `curriculums` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `class_levels`
--
ALTER TABLE `class_levels`
  ADD CONSTRAINT `class_levels_ibfk_1` FOREIGN KEY (`curriculum_id`) REFERENCES `curriculums` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD CONSTRAINT `class_subjects_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_3` FOREIGN KEY (`grading_scale_id`) REFERENCES `grading_scales` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `grading_scales`
--
ALTER TABLE `grading_scales`
  ADD CONSTRAINT `grading_scales_ibfk_1` FOREIGN KEY (`curriculum_id`) REFERENCES `curriculums` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parents`
--
ALTER TABLE `parents`
  ADD CONSTRAINT `parents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `students_ibfk_3` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_parents`
--
ALTER TABLE `student_parents`
  ADD CONSTRAINT `student_parents_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_parents_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`curriculum_id`) REFERENCES `curriculums` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `surveys`
--
ALTER TABLE `surveys`
  ADD CONSTRAINT `surveys_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `survey_fields`
--
ALTER TABLE `survey_fields`
  ADD CONSTRAINT `survey_fields_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `survey_responses`
--
ALTER TABLE `survey_responses`
  ADD CONSTRAINT `survey_responses_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `survey_responses_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `fk_teacher_subjects_class_subjects` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects` (`id`),
  ADD CONSTRAINT `fk_teacher_subjects_sections` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`),
  ADD CONSTRAINT `teacher_subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_subjects_ibfk_2` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;