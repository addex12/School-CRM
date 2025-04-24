-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 25, 2025 at 03:06 AM
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
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `action` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
(1, NULL, 'login', 'User logged in', '196.190.62.29', '2025-04-22 20:48:37'),
(2, NULL, 'login', 'User logged in', '196.190.62.29', '2025-04-22 21:21:46'),
(3, NULL, 'login', 'User logged in', '196.190.62.29', '2025-04-22 21:26:14'),
(4, NULL, 'login', 'User logged in', '196.190.62.29', '2025-04-23 01:41:22'),
(5, NULL, 'login', 'User logged in', '196.189.144.197', '2025-04-23 12:28:02'),
(6, NULL, 'login', 'User logged in', '196.189.144.197', '2025-04-23 13:25:19'),
(7, NULL, 'login', 'User logged in', '196.189.144.197', '2025-04-23 14:09:36'),
(8, NULL, 'login', 'User logged in', '196.189.144.197', '2025-04-23 15:42:10'),
(9, NULL, 'login', 'User logged in', '196.189.144.197', '2025-04-23 15:44:03'),
(10, NULL, 'login', 'User logged in', '196.189.144.197', '2025-04-23 16:35:56'),
(11, NULL, 'login', 'User logged in', '196.189.144.197', '2025-04-23 16:59:53'),
(12, NULL, 'login', 'User logged in', '196.189.144.197', '2025-04-23 18:50:56'),
(13, NULL, 'login', 'User logged in', '196.189.144.197', '2025-04-23 18:58:10'),
(14, NULL, 'login', 'User logged in', '196.190.62.232', '2025-04-23 19:04:48'),
(15, NULL, 'login', 'User logged in', '196.189.144.197', '2025-04-23 20:08:56'),
(16, NULL, 'login', 'User logged in', '196.189.144.197', '2025-04-23 20:20:28'),
(17, NULL, 'logout', 'User logged out', '196.189.144.197', '2025-04-23 20:35:34'),
(18, 4458, 'login', 'User logged in', '196.189.144.197', '2025-04-23 20:35:41'),
(19, 4458, 'logout', 'User logged out', '196.189.144.197', '2025-04-23 20:57:09'),
(20, 4458, 'login', 'User logged in', '196.189.144.197', '2025-04-23 20:57:16'),
(21, 4458, 'logout', 'User logged out', '196.189.144.197', '2025-04-23 21:19:44'),
(22, 4458, 'login', 'User logged in', '196.189.144.197', '2025-04-23 21:20:00'),
(23, NULL, 'login', 'User logged in', '196.189.144.197', '2025-04-23 21:20:51'),
(24, NULL, 'login', 'User logged in', '196.190.62.232', '2025-04-23 22:13:57'),
(25, NULL, 'login', 'User logged in', '196.190.62.232', '2025-04-23 22:54:26'),
(26, NULL, 'login', 'User logged in', '196.190.62.232', '2025-04-23 23:19:52'),
(27, NULL, 'login', 'User logged in', '196.190.62.232', '2025-04-23 23:45:38'),
(28, NULL, 'login', 'User logged in', '196.190.62.232', '2025-04-24 08:28:46'),
(29, NULL, 'login', 'User logged in', '196.190.62.232', '2025-04-24 10:12:06'),
(30, 4458, 'login', 'User logged in', '196.190.62.232', '2025-04-24 10:14:59'),
(31, 4458, 'logout', 'User logged out', '196.190.62.232', '2025-04-24 10:15:02'),
(32, 4458, 'login', 'User logged in', '196.190.62.232', '2025-04-24 10:15:20'),
(33, 4458, 'login', 'User logged in', '196.190.62.232', '2025-04-24 10:32:33'),
(34, 4458, 'logout', 'User logged out', '196.190.62.232', '2025-04-24 10:38:37'),
(35, NULL, 'login', 'User logged in', '196.190.62.232', '2025-04-24 10:38:44'),
(36, NULL, 'logout', 'User logged out', '196.190.62.232', '2025-04-24 10:39:03'),
(37, 4458, 'login', 'User logged in', '196.190.62.232', '2025-04-24 10:39:08'),
(38, 4458, 'logout', 'User logged out', '196.190.62.232', '2025-04-24 10:39:30'),
(39, NULL, 'login', 'User logged in', '196.190.62.232', '2025-04-24 10:39:38'),
(40, NULL, 'login', 'User logged in', '196.190.62.232', '2025-04-24 10:39:49'),
(41, NULL, 'login', 'User logged in', '196.189.144.197', '2025-04-24 10:43:57'),
(42, NULL, 'logout', 'User logged out', '196.189.144.197', '2025-04-24 10:44:06'),
(43, 4458, 'login', 'User logged in', '196.189.144.197', '2025-04-24 10:44:24'),
(44, NULL, 'logout', 'User logged out', '196.190.62.232', '2025-04-24 10:47:03'),
(45, 4458, 'logout', 'User logged out', '196.189.144.197', '2025-04-24 10:47:18'),
(46, NULL, 'login', 'User logged in', '196.189.144.197', '2025-04-24 10:47:25'),
(47, 4458, 'login', 'User logged in', '196.190.62.232', '2025-04-24 10:47:37'),
(48, 4458, 'logout', 'User logged out', '196.190.62.232', '2025-04-24 11:00:56'),
(49, NULL, 'login', 'User logged in', '196.190.62.232', '2025-04-24 11:03:03'),
(52, 4, 'login', 'User logged in', '196.190.62.232', '2025-04-24 11:16:23'),
(53, NULL, 'login', 'User logged in', '196.190.62.232', '2025-04-24 11:38:24'),
(54, 4, 'login', 'User logged in', '196.190.62.232', '2025-04-24 11:54:51'),
(55, 4, 'login', 'User logged in', '196.190.62.232', '2025-04-24 12:15:33'),
(56, 4, 'login', 'User logged in', '196.190.62.232', '2025-04-24 12:27:41'),
(57, 4, 'login', 'User logged in', '196.190.62.232', '2025-04-24 12:35:36'),
(58, 4458, 'login', 'User logged in', '196.190.62.232', '2025-04-24 13:02:22'),
(59, 4458, 'logout', 'User logged out', '196.190.62.232', '2025-04-24 13:05:32'),
(60, 4458, 'login', 'User logged in', '196.190.62.232', '2025-04-24 13:06:05'),
(61, 4, 'login', 'User logged in', '196.189.144.197', '2025-04-24 13:41:43'),
(62, 4, 'login', 'User logged in', '196.189.144.197', '2025-04-24 13:50:14'),
(63, 4, 'login', 'User logged in', '196.189.144.197', '2025-04-24 14:02:49'),
(64, 4, 'login', 'User logged in', '196.189.144.197', '2025-04-24 14:34:51'),
(65, 4, 'login', 'User logged in', '196.189.144.197', '2025-04-24 14:50:35'),
(66, 4, 'login', 'User logged in', '196.189.144.197', '2025-04-24 15:23:23'),
(67, 4, 'login', 'User logged in', '196.189.144.197', '2025-04-24 15:29:00'),
(68, 4458, 'login', 'User logged in', '196.190.62.232', '2025-04-24 15:55:17'),
(69, 5, 'login', 'User logged in', '196.190.62.232', '2025-04-24 15:55:25'),
(70, 5, 'logout', 'User logged out', '196.190.62.232', '2025-04-24 16:04:35'),
(71, 5, 'login', 'User logged in', '196.190.62.232', '2025-04-24 16:04:48'),
(72, 4, 'login', 'User logged in', '196.189.144.197', '2025-04-24 16:39:05'),
(73, 4, 'login', 'User logged in', '196.189.144.197', '2025-04-24 16:47:59'),
(74, 4458, 'login', 'User logged in', '196.190.62.232', '2025-04-24 17:02:35'),
(75, 4, 'login', 'User logged in', '196.189.144.197', '2025-04-24 17:03:32');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `admin_reply` text DEFAULT NULL,
  `user_reply` text DEFAULT NULL,
  `status` enum('open','in_progress','resolved') DEFAULT 'open',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `user_id`, `subject`, `message`, `rating`, `admin_reply`, `user_reply`, `status`, `admin_notes`, `created_at`) VALUES
(1, 65, 'Good Job', 'great', 5, 'Thank you', 'You\'re Welcome', 'open', NULL, '2025-04-14 13:30:19'),
(3, 65, 'I feel Good', 'Great job again', 1, NULL, NULL, 'open', NULL, '2025-04-15 03:14:52'),
(4, 65, 'Academic Progress', 'Great progress', 5, NULL, NULL, 'open', NULL, '2025-04-15 03:29:58'),
(5, 5, 'Cafeteria/Food Services', 'Delicious', 3, NULL, NULL, 'open', NULL, '2025-04-15 03:30:48'),
(6, 65, 'Let&#39;s Check', '4 stars', 4, 'Okay', NULL, 'open', NULL, '2025-04-15 03:43:00'),
(8, 65, 'Cafeteria/Food Services', 'check', 2, NULL, NULL, 'open', NULL, '2025-04-15 03:54:34'),
(9, 65, 'Discipline and Safety', 'test again', 3, NULL, NULL, 'open', NULL, '2025-04-15 03:56:39'),
(0, 4470, 'Fee Request', 'great', 4, NULL, NULL, 'open', NULL, '2025-04-24 11:39:00'),
(0, 4470, 'Fee Request', 'great', 4, NULL, NULL, 'open', NULL, '2025-04-24 11:41:39');

-- --------------------------------------------------------

--
-- Table structure for table `feedback_subjects`
--

CREATE TABLE `feedback_subjects` (
  `id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback_subjects`
--

INSERT INTO `feedback_subjects` (`id`, `subject`, `status`) VALUES
(1, 'General', 'active'),
(2, 'Fee Request', 'active'),
(3, 'Technical Request', 'active'),
(4, 'Complaint', 'active'),
(5, 'Other', 'active');

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
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_by_sender` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_by_receiver` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `subject`, `content`, `sent_at`, `is_read`, `deleted_by_sender`, `deleted_by_receiver`, `created_at`) VALUES
(3, 4, 4458, NULL, 'hello', '2025-04-25 02:16:53', 1, 0, 0, '2025-04-25 02:58:20'),
(4, 5, 4458, NULL, 'hi are fine', '2025-04-25 02:17:10', 0, 0, 0, '2025-04-25 02:58:20'),
(5, 5, 4471, NULL, 'test 123', '2025-04-25 02:18:01', 0, 0, 0, '2025-04-25 02:58:20'),
(7, 4, 4471, NULL, 'hey', '2025-04-25 02:41:35', 0, 0, 0, '2025-04-25 02:58:20'),
(8, 4, 4458, NULL, 'hu', '2025-04-25 02:42:58', 0, 0, 0, '2025-04-25 02:58:20'),
(9, 4, 4458, NULL, 'hi', '2025-04-25 02:51:39', 0, 0, 0, '2025-04-25 02:58:20'),
(10, 4, 4458, NULL, 'hi', '2025-04-25 02:53:40', 0, 0, 0, '2025-04-25 02:58:20'),
(11, 4, 4458, NULL, 'hi', '2025-04-25 03:04:06', 0, 0, 0, '2025-04-25 03:04:06');

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
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `label` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `label`) VALUES
(1, 'dashboard', 'Access Dashboard'),
(2, 'users_view', 'View Users'),
(3, 'users_add', 'Add User'),
(4, 'users_edit', 'Edit User'),
(5, 'users_delete', 'Delete User'),
(6, 'surveys_view', 'View Surveys'),
(7, 'surveys_create', 'Create Survey'),
(8, 'surveys_edit', 'Edit Survey'),
(9, 'surveys_delete', 'Delete Survey'),
(10, 'survey_results', 'View Survey Results'),
(11, 'survey_categories', 'Manage Survey Categories'),
(12, 'students_view', 'View Students'),
(13, 'students_add', 'Add Student'),
(14, 'students_edit', 'Edit Student'),
(15, 'students_delete', 'Delete Student'),
(16, 'teachers_view', 'View Teachers'),
(17, 'teachers_add', 'Add Teacher'),
(18, 'teachers_edit', 'Edit Teacher'),
(19, 'teachers_delete', 'Delete Teacher'),
(20, 'parents_view', 'View Parents'),
(21, 'parents_add', 'Add Parent'),
(22, 'parents_edit', 'Edit Parent'),
(23, 'parents_delete', 'Delete Parent'),
(24, 'messages', 'Send/Receive Messages'),
(25, 'announcements', 'Manage Announcements'),
(26, 'bulk_email', 'Send Bulk Emails'),
(27, 'support_tickets', 'Manage Support Tickets'),
(28, 'knowledge_base', 'Access Knowledge Base'),
(29, 'feedback_manage', 'Manage Feedback'),
(30, 'feedback_view', 'View Feedback'),
(31, 'settings_general', 'General Settings'),
(32, 'settings_roles', 'Manage User Roles'),
(33, 'settings_permissions', 'Manage Permissions'),
(34, 'settings_backup', 'Backup & Restore'),
(35, 'settings_logs', 'View System Logs'),
(36, 'settings_audit', 'View Audit Trail'),
(37, 'role_management', 'Manage Roles'),
(38, 'user_permissions', 'Manage User Permissions'),
(39, 'classes_manage', 'Manage Classes'),
(40, 'sections_manage', 'Manage Sections'),
(41, 'grades_manage', 'Manage Grades'),
(42, 'reports_view', 'View Reports');

-- --------------------------------------------------------

--
-- Table structure for table `response_data`
--

CREATE TABLE `response_data` (
  `id` int(11) NOT NULL,
  `response_id` int(11) NOT NULL,
  `survey_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `field_value` text DEFAULT NULL
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
(4, 'student', 'School Student', '2025-03-28 16:53:39'),
(5, 'new', NULL, '2025-04-24 13:07:03');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(1, 13),
(1, 14),
(1, 15),
(1, 16),
(1, 17),
(1, 18),
(1, 19),
(1, 20),
(1, 21),
(1, 22),
(1, 23),
(1, 24),
(1, 25),
(1, 26),
(1, 27),
(1, 28),
(1, 29),
(1, 30),
(1, 31),
(1, 32),
(1, 33),
(1, 34),
(1, 35),
(1, 36),
(1, 37),
(1, 38),
(1, 39),
(1, 40),
(1, 41),
(1, 42),
(2, 3),
(2, 6),
(2, 7),
(2, 8),
(2, 11),
(2, 13),
(2, 17),
(2, 19),
(2, 20),
(2, 21),
(2, 24),
(2, 25),
(2, 26),
(2, 27),
(2, 28),
(2, 30),
(2, 34),
(2, 36),
(2, 37),
(2, 38),
(2, 39),
(2, 40),
(2, 41),
(2, 42);

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
(3, 5, 12, 3, 'STU001', '2010-05-15', 'Male', '123 Student Street, Addis Ababa', 'active', '2025-04-23 05:23:51'),
(497, 4458, NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-04-24 01:50:30');

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
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `status` enum('open','in_progress','on_hold','resolved') DEFAULT 'open',
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `user_id`, `ticket_number`, `subject`, `message`, `priority`, `status`, `attachment`, `created_at`) VALUES
(1, 65, 'TKT-67FD9BC48CF6B', 'Greetings', 'Hello', 'medium', 'open', NULL, '2025-04-14 13:35:32');

-- --------------------------------------------------------

--
-- Table structure for table `surveys`
--

CREATE TABLE `surveys` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `surveys`
--

INSERT INTO `surveys` (`id`, `title`, `description`, `category_id`, `status`, `created_by`, `starts_at`, `ends_at`, `is_anonymous`, `is_active`, `created_at`) VALUES
(3, 'Teachers Survey', 'This is survey for teachers', 2, 2, 4, '2025-04-24 14:50:00', '2025-05-24 15:50:00', 1, 1, '2025-04-24 15:52:57');

-- --------------------------------------------------------

--
-- Table structure for table `survey_categories`
--

CREATE TABLE `survey_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `survey_categories`
--

INSERT INTO `survey_categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Behaviour Survey', 'This is to know how behave our employees are.(example)', '2025-03-26 02:36:15'),
(2, 'Survey on Teachers', 'Description', '2025-03-26 02:38:51'),
(3, 'Students Performance Survey', 'Descritpion', '2025-03-26 02:40:18'),
(4, 'contact survey', '', '2025-03-28 05:47:46');

-- --------------------------------------------------------

--
-- Table structure for table `survey_conditions`
--

CREATE TABLE `survey_conditions` (
  `id` int(11) NOT NULL,
  `survey_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `operator` enum('=','!=','>','<','>=','<=','contains') NOT NULL,
  `compare_value` varchar(255) NOT NULL,
  `logic_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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

--
-- Dumping data for table `survey_fields`
--

INSERT INTO `survey_fields` (`id`, `survey_id`, `field_type`, `field_label`, `field_name`, `field_options`, `is_required`, `display_order`) VALUES
(3, 3, 'rating', 'On a scale of 1-5, how satisfied are you with your current teaching workload?', '', NULL, 1, 0),
(4, 3, 'radio', 'Do you feel you have adequate planning time during the school day?', '', '[\"Yes\",\"No\"]', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `survey_logic`
--

CREATE TABLE `survey_logic` (
  `id` int(11) NOT NULL,
  `survey_id` int(11) NOT NULL,
  `source_field_id` int(11) NOT NULL,
  `trigger_value` varchar(255) NOT NULL,
  `target_field_id` int(11) NOT NULL,
  `action` enum('show','hide','enable','disable') NOT NULL,
  `condition` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `survey_responses`
--

CREATE TABLE `survey_responses` (
  `id` int(11) NOT NULL,
  `survey_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `answers` longtext CHARACTER SET utf8mb4 COLLATE=utf8mb4_bin NOT NULL CHECK (json_valid(`answers`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `survey_roles`
--

CREATE TABLE `survey_roles` (
  `id` int(11) NOT NULL,
  `survey_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `survey_roles`
--

INSERT INTO `survey_roles` (`id`, `survey_id`, `role_id`) VALUES
(2, 3, 2);

-- --------------------------------------------------------

--
-- Table structure for table `survey_statuses`
--

CREATE TABLE `survey_statuses` (
  `id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `survey_statuses`
--

INSERT INTO `survey_statuses` (`id`, `status`, `label`, `icon`) VALUES
(1, 'draft', 'Draft', 'fa-file'),
(2, 'active', 'Active', 'fa-rocket'),
(3, 'inactive', 'Inactive', 'fa-pause'),
(4, 'archived', 'Archived', 'fa-archive');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `academic_year` varchar(50) DEFAULT NULL,
  `term` varchar(50) DEFAULT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `academic_year`, `term`, `setting_value`, `setting_group`, `created_at`, `updated_at`) VALUES
(1, 'site_name', NULL, NULL, 'Flipper International School CRM System', 'general', '2025-04-22 23:46:38', '2025-04-24 14:09:16'),
(2, 'site_email', NULL, NULL, 'admin@school.edu', 'general', '2025-04-22 23:46:38', '2025-04-22 23:46:38'),
(3, 'timezone', NULL, NULL, 'Africa/Nairobi', 'general', '2025-04-22 23:46:38', '2025-04-22 23:47:40'),
(4, 'items_per_page', NULL, NULL, '20', 'general', '2025-04-22 23:46:38', '2025-04-22 23:47:40'),
(11, 'enable_surveys', NULL, NULL, '1', 'features', '2025-04-23 00:11:40', '2025-04-23 00:11:40'),
(12, 'enable_notifications', NULL, NULL, '1', 'features', '2025-04-23 00:11:40', '2025-04-23 00:11:40'),
(13, 'enable_chat', NULL, NULL, '1', 'features', '2025-04-23 00:11:40', '2025-04-23 00:11:40'),
(16, 'admin_email', NULL, NULL, 'adugna.gizaw@flipperschools.com', 'general', '2025-04-24 13:12:58', '2025-04-24 13:12:58'),
(18, 'language', NULL, NULL, 'English', 'general', '2025-04-24 13:12:58', '2025-04-24 13:12:58'),
(19, 'smtp_host', NULL, NULL, '', 'general', '2025-04-24 13:12:58', '2025-04-24 13:12:58'),
(20, 'smtp_port', NULL, NULL, '', 'general', '2025-04-24 13:12:58', '2025-04-24 13:12:58'),
(21, 'smtp_user', NULL, NULL, '', 'general', '2025-04-24 13:12:58', '2025-04-24 13:12:58'),
(22, 'smtp_pass', NULL, NULL, '', 'general', '2025-04-24 13:12:58', '2025-04-24 13:12:58'),
(23, 'smtp_secure', NULL, NULL, '', 'general', '2025-04-24 13:12:58', '2025-04-24 13:12:58'),
(24, 'from_email', NULL, NULL, '', 'general', '2025-04-24 13:12:58', '2025-04-24 13:12:58'),
(25, 'password_min_length', NULL, NULL, '', 'general', '2025-04-24 13:12:58', '2025-04-24 13:12:58'),
(26, 'session_timeout', NULL, NULL, '', 'general', '2025-04-24 13:12:58', '2025-04-24 13:12:58');

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
(460, 4458, NULL, NULL, NULL, NULL, NULL, 'active', '2025-04-24 18:34:34');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_subjects`
--

CREATE TABLE `teacher_subjects` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `class_subject_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transcripts`
--

CREATE TABLE `transcripts` (
  `id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `academic_year_id` int(11) NOT NULL,
  `gpa` decimal(4,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
  `last_active` datetime DEFAULT NULL,
  `online` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `avatar` varchar(255) DEFAULT 'default.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `first_name`, `last_name`, `role_id`, `last_active`, `online`, `active`, `created_at`, `last_login`, `avatar`) VALUES
(4, 'administrator', '$2y$10$NzdfGBS05PUk3gh0C9Cmfu6WL1bvexg4Xin/5hItCo2GcoMoOKTbO', 'adugna.gizaw@flipperschools.com', 'Admin', 'System', 1, '2025-04-25 03:03:32', 1, 1, '2025-03-24 16:50:31', '2025-04-24 17:03:32', 'admin_avatar.jpg'),
(5, 'efream', '$2y$10$MVeN3l2MkGpfz7fvjOPGEORMcLh0zArHGtACBXvp7e2Vi14QH/Ldm', 'mcdc@gmail.com', 'Efream', 'Yohannes', 1, '2025-04-25 02:04:48', 1, 1, '2025-03-25 11:47:11', '2025-04-24 16:04:48', 'avatar_5_7aa6215a045431e8.jpg'),
(4458, 'ibrahim', '$2y$10$Djik3HGaTGfUIie2ZE4xq.FXCjQZs85AnoDnLRUc9z1FfNDRCNJle', 'ibrahimkebede@gmail.com', NULL, NULL, 2, '2025-04-25 03:03:36', 1, 1, '2025-04-23 15:50:30', '2025-04-24 17:02:35', 'default.jpg'),
(4471, 'adugna', '$2y$10$qkVyRXmdutRbn73CKiAqJuUg8Ix.RBoE4EPjgcs/s.RqaNX5o5PCa', 'gizawadugna@gmail.com', NULL, NULL, 5, NULL, 0, 0, '2025-04-24 14:06:37', NULL, 'default.jpg');

--
-- Table structure for table `knowledge_base`
--

CREATE TABLE IF NOT EXISTS `knowledge_base` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `feedback_subjects`
--
ALTER TABLE `feedback_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_unique` (`subject`);

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
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `response_data`
--
ALTER TABLE `response_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `response_id` (`response_id`),
  ADD KEY `survey_id` (`survey_id`),
  ADD KEY `field_id` (`field_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

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
-- Indexes for table `surveys`
--
ALTER TABLE `surveys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `survey_categories`
--
ALTER TABLE `survey_categories`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `survey_roles`
--
ALTER TABLE `survey_roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `survey_id` (`survey_id`),
  ADD KEY `role_id` (`role_id`);

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
  ADD KEY `class_subject_id` (`class_subject_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `transcripts`
--
ALTER TABLE `transcripts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enrollment_id` (`enrollment_id`),
  ADD KEY `academic_year_id` (`academic_year_id`);

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
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `feedback_subjects`
--
ALTER TABLE `feedback_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `parents`
--
ALTER TABLE `parents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=437;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `response_data`
--
ALTER TABLE `response_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=505;

--
-- AUTO_INCREMENT for table `student_parents`
--
ALTER TABLE `student_parents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `surveys`
--
ALTER TABLE `surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `survey_categories`
--
ALTER TABLE `survey_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `survey_fields`
--
ALTER TABLE `survey_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `survey_responses`
--
ALTER TABLE `survey_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `survey_roles`
--
ALTER TABLE `survey_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=461;

--
-- AUTO_INCREMENT for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `transcripts`
--
ALTER TABLE `transcripts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4472;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
-- Constraints for table `response_data`
--
ALTER TABLE `response_data`
  ADD CONSTRAINT `fk_response_data_field` FOREIGN KEY (`field_id`) REFERENCES `survey_fields` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_response_data_response` FOREIGN KEY (`response_id`) REFERENCES `survey_responses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_response_data_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `surveys`
--
ALTER TABLE `surveys`
  ADD CONSTRAINT `surveys_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `surveys_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `survey_categories` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `survey_roles`
--
ALTER TABLE `survey_roles`
  ADD CONSTRAINT `fk_survey_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_survey_roles_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `fk_teacher_subjects_class_subjects` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_teacher_subjects_sections` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_teacher_subjects_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transcripts`
--
ALTER TABLE `transcripts`
  ADD CONSTRAINT `transcripts_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transcripts_ibfk_2` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;