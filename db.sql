-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 30, 2025 at 02:01 AM
-- Server version: 10.6.21-MariaDB-cll-lve
-- PHP Version: 8.3.20

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
  `user_id` varchar(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `element` varchar(50) DEFAULT NULL,
  `element_id` varchar(255) DEFAULT NULL,
  `element_class` text DEFAULT NULL,
  `text` text DEFAULT NULL,
  `value` text DEFAULT NULL,
  `href` text DEFAULT NULL,
  `page` text NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `timestamp` datetime NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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


-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
-- Table structure for table `inbox`
--

CREATE TABLE `inbox` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `knowledge_base`
--

CREATE TABLE `knowledge_base` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `persistent_tracking`
--

CREATE TABLE `persistent_tracking` (
  `id` bigint(20) NOT NULL,
  `tracking_id` varchar(64) NOT NULL,
  `fingerprint` text NOT NULL,
  `first_seen` datetime NOT NULL,
  `last_seen` datetime NOT NULL,
  `user_agent` text NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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

--
-- Dumping data for table `response_data`
--

INSERT INTO `response_data` (`id`, `response_id`, `survey_id`, `field_id`, `field_value`) VALUES
(5, 4, 3, 3, '5'),
(6, 4, 3, 4, 'Yes'),
(7, 5, 3, 3, '4'),
(8, 5, 3, 4, 'No'),
(9, 6, 5, 18, 'Very Supported'),
(10, 6, 5, 19, 'Yes'),
(11, 6, 5, 20, 'Student Engagement'),
(12, 7, 5, 18, 'Very Supported'),
(13, 7, 5, 19, 'No response'),
(14, 7, 5, 20, 'Workload/Time Management'),
(15, 8, 5, 18, 'Very Unsupported'),
(16, 8, 5, 19, 'Shdhdjd'),
(17, 8, 5, 20, 'Workload/Time Management'),
(18, 9, 5, 18, 'Very Supported'),
(19, 9, 5, 19, ' Lots of tools'),
(20, 9, 5, 20, 'Student Engagement'),
(21, 10, 5, 18, 'Neutral'),
(22, 10, 5, 19, 'Lots of tools'),
(23, 10, 5, 20, 'Classroom Behavior'),
(26, 12, 7, 38, 'Sometimes'),
(27, 12, 7, 39, 'None'),
(28, 16, 8, 42, 'Good'),
(29, 16, 8, 43, '2'),
(30, 17, 8, 42, 'Good'),
(31, 17, 8, 43, '2'),
(32, 18, 8, 42, 'Great'),
(33, 18, 8, 43, '1'),
(34, 19, 8, 42, 'Fair'),
(35, 19, 8, 43, '5'),
(36, 20, 6, 33, '1'),
(37, 20, 6, 34, 'Strongly Agree'),
(38, 20, 6, 35, 'Somehow Supportive'),
(39, 21, 6, 33, '1'),
(40, 21, 6, 34, 'Strongly Agree'),
(41, 21, 6, 35, 'Somehow Supportive'),
(42, 22, 6, 33, '2'),
(43, 22, 6, 34, 'Strongly Agree'),
(44, 22, 6, 35, 'Very Supportive'),
(45, 23, 8, 42, 'Good'),
(46, 23, 8, 43, '2'),
(47, 24, 7, 38, 'Always'),
(48, 24, 7, 39, 'no challenge'),
(49, 25, 7, 38, 'Sometimes'),
(50, 25, 7, 39, 'aaaaaaaaaaaa');

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
(1, 65, 'TKT-67FD9BC48CF6B', 'Greetings', 'Hello', 'medium', 'resolved', NULL, '2025-04-14 13:35:32'),
(2, 4458, 'TICKET-680ACA2C11699', 'I need Support', 'Help needed', 'medium', 'open', NULL, '2025-04-24 23:33:00'),
(3, 4472, 'TICKET-680ACA308C129', 'Request for a clarification', 'Rff', 'medium', 'open', NULL, '2025-04-24 23:33:04'),
(5, 4472, 'TICKET-680D476B4713A', 'Other', 'Hello Support needed', 'high', 'in_progress', NULL, '2025-04-26 20:51:55'),
(6, 4472, 'TICKET-680E61EE0CC64', 'Billing Inquiry', 'Hello', 'medium', 'open', NULL, '2025-04-27 16:57:18');

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
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `surveys`
--

INSERT INTO `surveys` (`id`, `title`, `description`, `category_id`, `status`, `created_by`, `starts_at`, `ends_at`, `is_anonymous`, `is_active`, `is_public`, `created_at`) VALUES
(3, 'Teachers Survey', 'This is survey for teachers', 2, 2, 4, '2025-04-24 14:50:00', '2025-05-24 15:50:00', 1, 1, 0, '2025-04-24 15:52:57'),
(4, 'Survey For Parents', 'This is a survey for all Parents', 2, 2, 4, '2025-04-28 19:34:00', '2025-05-27 19:34:00', 0, 1, 0, '2025-04-27 19:43:38'),
(5, 'Teachers Survey', 'Surveys for Teachers', 2, 2, 4, '2025-04-27 19:44:00', '2025-05-27 19:44:00', 1, 1, 0, '2025-04-27 19:46:43'),
(6, 'Public Survey', 'This is Publicly Available survey', 1, 2, 4, '2025-04-28 08:30:00', '2025-05-28 13:30:00', 0, 1, 1, '2025-04-28 13:32:09'),
(7, 'Survey For All', 'Public Survey', 1, 2, 4, '2025-04-28 06:32:00', '2025-05-28 18:32:00', 0, 1, 1, '2025-04-28 18:33:30'),
(8, 'Public Survey1', 'Public Survey2', 1, 2, 4, '2025-04-28 06:38:00', '2025-05-28 18:38:00', 1, 1, 1, '2025-04-28 18:40:05');

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
(4, 3, 'radio', 'Do you feel you have adequate planning time during the school day?', '', '[\"Yes\",\"No\"]', 1, 1),
(10, 4, 'radio', 'How satisfied are you with the school’s communication about your child’s progress?', '', '[\"Very Satisfied\",\"Satisfied\",\"Neutral\",\"Dissatisfied\",\"Very Dissatisfied\"]', 1, 0),
(11, 4, 'textarea', '2. Open-Ended Question  What improvements would you suggest to enhance your child’s learning experience at school?', '', '[]', 1, 1),
(12, 4, 'checkbox', 'Which method do you prefer for receiving updates about school events?', '', '[\"Email\",\"Mobile App Notifications\",\"Text Messages\",\"Printed Newsletters\",\"Phone Calls\"]', 1, 2),
(13, 4, 'radio', 'Have you attended a parent-teacher conference this academic year?', '', '[\"Yes\",\"No\"]', 1, 3),
(14, 4, 'select', 'Rank the following school priorities in order of importance (1 = Most Important, 5 = Least Important):', '', '[\"Academic Support\",\"Social-Emotional Learning\",\"Extracurricular Opportunities\",\"Safety and Discipline\",\"Technology Integration\"]', 0, 4),
(18, 5, 'radio', 'How supported do you feel by school leadership in your teaching role?', '', '[\"Very Supported\",\"Supported\",\"Neutral\",\"Unsupported\",\"Very Unsupported\"]', 1, 0),
(19, 5, 'textarea', 'What resources or tools would help you be more effective in the classroom?', '', '[]', 1, 1),
(20, 5, 'radio', 'What is your biggest challenge this academic year?', '', '[\"Student Engagement\",\"Workload\\/Time Management\",\"Classroom Behavior\",\"Lack of Resources\",\"Parent Communication\"]', 0, 2),
(33, 6, 'radio', 'On a scale of 1-5, how satisfied are you with your current teaching workload?', '', '[\"1\",\"2\",\"3\",\"4\",\"5\"]', 1, 0),
(34, 6, 'radio', 'Class Assessments are Crucial to measure Students Performance', '', '[\"Strongly Agree\",\"Agree\",\"Disagree\",\"Strongly Disagree\"]', 1, 1),
(35, 6, 'radio', 'How supported do you feel by school leadership in your teaching role?', '', '[\"Very Supportive\",\"Somehow Supportive\"]', 1, 2),
(38, 7, 'radio', 'How often do you feel stressed due to work-related tasks?', '', '[\"Always\",\"Sometimes\",\"Never\"]', 1, 0),
(39, 7, 'textarea', 'What is your biggest challenge this academic year?', '', '[]', 0, 1),
(42, 8, 'radio', 'How supported do you feel by school leadership in your teaching role?', '', '[\"Great\",\"Good\",\"Fair\"]', 1, 0),
(43, 8, 'radio', 'Rank the following school priorities in order of importance (1 = Most Important, 5 = Least Important):', '', '[\"1\",\"2\",\"3\",\"4\",\"5\"]', 1, 1);

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
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`answers`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `survey_responses`
--

INSERT INTO `survey_responses` (`id`, `survey_id`, `user_id`, `email`, `submitted_at`, `answers`) VALUES
(4, 3, 4472, NULL, '2025-04-27 19:07:28', '{\"3\":\"5\",\"4\":\"Yes\"}'),
(5, 3, 4458, NULL, '2025-04-27 19:16:30', '{\"3\":\"4\",\"4\":\"No\"}'),
(6, 5, 4458, NULL, '2025-04-27 19:54:41', '{\"18\":\"Very Supported\",\"19\":\"Yes\",\"20\":\"Student Engagement\"}'),
(7, 5, 4458, NULL, '2025-04-27 19:55:05', '{\"18\":\"Very Supported\",\"19\":\"No response\",\"20\":\"Workload\\/Time Management\"}'),
(8, 5, 4458, NULL, '2025-04-27 19:55:18', '{\"18\":\"Very Unsupported\",\"19\":\"Shdhdjd\",\"20\":\"Workload\\/Time Management\"}'),
(9, 5, 4458, NULL, '2025-04-27 19:57:24', '{\"18\":\"Very Supported\",\"19\":\" Lots of tools\",\"20\":\"Student Engagement\"}'),
(10, 5, 4472, NULL, '2025-04-27 19:58:21', '{\"18\":\"Neutral\",\"19\":\"Lots of tools\",\"20\":\"Classroom Behavior\"}'),
(11, 6, 4458, NULL, '2025-04-28 18:23:51', '{\"29\":\"3\",\"30\":\"Agree\"}'),
(12, 7, 4458, NULL, '2025-04-28 18:34:13', '{\"38\":\"Sometimes\",\"39\":\"None\"}'),
(16, 8, NULL, NULL, '2025-04-28 19:54:00', '{\"42\":\"Good\",\"43\":\"2\"}'),
(17, 8, NULL, NULL, '2025-04-28 19:54:55', '{\"42\":\"Good\",\"43\":\"2\"}'),
(18, 8, NULL, NULL, '2025-04-28 19:55:25', '{\"42\":\"Great\",\"43\":\"1\"}'),
(19, 8, NULL, NULL, '2025-04-28 20:01:23', '{\"42\":\"Fair\",\"43\":\"5\"}'),
(20, 6, NULL, 'gizawadugna@gmail.com', '2025-04-28 20:07:55', '{\"33\":\"1\",\"34\":\"Strongly Agree\",\"35\":\"Somehow Supportive\"}'),
(21, 6, NULL, 'gizawadugna@gmail.com', '2025-04-28 20:08:23', '{\"33\":\"1\",\"34\":\"Strongly Agree\",\"35\":\"Somehow Supportive\"}'),
(22, 6, NULL, 'adugna.gsr-1468-17@aau.edu.et', '2025-04-28 20:09:06', '{\"33\":\"2\",\"34\":\"Strongly Agree\",\"35\":\"Very Supportive\"}'),
(23, 8, NULL, NULL, '2025-04-28 20:09:42', '{\"42\":\"Good\",\"43\":\"2\"}'),
(24, 7, NULL, 'adugna.gsr-1468-17@aau.edu.et', '2025-04-28 20:13:25', '{\"38\":\"Always\",\"39\":\"no challenge\"}'),
(25, 7, NULL, 'gizawadugna@gmail.com', '2025-04-28 20:16:59', '{\"38\":\"Sometimes\",\"39\":\"aaaaaaaaaaaa\"}');

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
(2, 3, 2),
(6, 4, 3),
(8, 5, 2);

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
(1, 'site_name', NULL, NULL, 'FIS CRM System', 'general', '2025-04-22 23:46:38', '2025-04-27 00:21:15'),
(2, 'site_email', NULL, NULL, 'admin@school.edu', 'general', '2025-04-22 23:46:38', '2025-04-22 23:46:38'),
(3, 'timezone', NULL, NULL, 'Africa/Nairobi', 'general', '2025-04-22 23:46:38', '2025-04-22 23:47:40'),
(4, 'items_per_page', NULL, NULL, '20', 'general', '2025-04-22 23:46:38', '2025-04-22 23:47:40'),
(11, 'enable_surveys', NULL, NULL, '1', 'features', '2025-04-23 00:11:40', '2025-04-23 00:11:40'),
(12, 'enable_notifications', NULL, NULL, '1', 'features', '2025-04-23 00:11:40', '2025-04-23 00:11:40'),
(13, 'enable_chat', NULL, NULL, '1', 'features', '2025-04-23 00:11:40', '2025-04-23 00:11:40'),
(16, 'admin_email', NULL, NULL, 'adugna.gizaw@flipperschools.com', 'general', '2025-04-24 13:12:58', '2025-04-24 13:12:58'),
(18, 'language', NULL, NULL, 'English', 'general', '2025-04-24 13:12:58', '2025-04-24 13:12:58'),
(19, 'smtp_host', NULL, NULL, 'smtp.gmail.com', 'general', '2025-04-24 13:12:58', '2025-04-27 00:14:57'),
(20, 'smtp_port', NULL, NULL, '', 'general', '2025-04-24 13:12:58', '2025-04-24 13:12:58'),
(21, 'smtp_user', NULL, NULL, '', 'general', '2025-04-24 13:12:58', '2025-04-24 13:12:58'),
(22, 'smtp_pass', NULL, NULL, '', 'general', '2025-04-24 13:12:58', '2025-04-24 13:12:58'),
(23, 'smtp_secure', NULL, NULL, 'ssl', 'general', '2025-04-24 13:12:58', '2025-04-27 00:14:57'),
(24, 'from_email', NULL, NULL, '', 'general', '2025-04-24 13:12:58', '2025-04-24 13:12:58'),
(25, 'password_min_length', NULL, NULL, '', 'general', '2025-04-24 13:12:58', '2025-04-24 13:12:58'),
(26, 'session_timeout', NULL, NULL, '', 'general', '2025-04-24 13:12:58', '2025-04-24 13:12:58'),
(52, 'site_logo', NULL, NULL, '../uploads/FIS Logo.png', 'general', '2025-04-24 22:13:13', '2025-04-27 00:14:57'),
(56, 'dashboard_cards', NULL, NULL, '', 'general', '2025-04-24 22:13:13', '2025-04-24 22:13:13'),
(57, 'primary_color', NULL, NULL, '', 'general', '2025-04-24 22:13:13', '2025-04-24 22:13:13'),
(58, 'secondary_color', NULL, NULL, '', 'general', '2025-04-24 22:13:13', '2025-04-24 22:13:13'),
(59, 'sidebar_bg', NULL, NULL, '', 'general', '2025-04-24 22:13:13', '2025-04-24 22:13:13'),
(60, 'sidebar_text_color', NULL, NULL, '', 'general', '2025-04-24 22:13:13', '2025-04-24 22:13:13'),
(69, 'allow_user_registration', NULL, NULL, '1', 'general', '2025-04-24 22:13:13', '2025-04-24 22:13:13');

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

-- --------------------------------------------------------

--
-- Table structure for table `ticket_replies`
--

CREATE TABLE `ticket_replies` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `ticket_replies`
--

INSERT INTO `ticket_replies` (`id`, `ticket_id`, `user_id`, `message`, `created_at`) VALUES
(1, 2, 4458, 'any update?', '2025-04-24 23:44:19');

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
  `avatar` varchar(255) DEFAULT 'default.jpg',
  `tracking_token` varchar(255) DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_activity`
--

CREATE TABLE `user_activity` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `tracking_id` varchar(64) NOT NULL,
  `fingerprint` text DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `timestamp` datetime NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(64) NOT NULL,
  `tracking_token` varchar(64) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `started_at` datetime NOT NULL,
  `last_activity` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `duration` int(11) DEFAULT NULL COMMENT 'In seconds'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_timestamp` (`timestamp`);

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
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `feedback_subjects`
--
ALTER TABLE `feedback_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_unique` (`subject`);

--
-- Indexes for table `inbox`
--
ALTER TABLE `inbox`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `knowledge_base`
--
ALTER TABLE `knowledge_base`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `persistent_tracking`
--
ALTER TABLE `persistent_tracking`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_tracking_id` (`tracking_id`),
  ADD KEY `idx_fingerprint` (`fingerprint`(64)),
  ADD KEY `idx_user` (`user_id`);

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
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
-- Indexes for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

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
-- Indexes for table `user_activity`
--
ALTER TABLE `user_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_tracking` (`tracking_id`),
  ADD KEY `idx_action` (`action_type`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_session` (`session_token`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_token` (`tracking_token`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=295;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `feedback_subjects`
--
ALTER TABLE `feedback_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inbox`
--
ALTER TABLE `inbox`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `knowledge_base`
--
ALTER TABLE `knowledge_base`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

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
-- AUTO_INCREMENT for table `persistent_tracking`
--
ALTER TABLE `persistent_tracking`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `response_data`
--
ALTER TABLE `response_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

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
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `surveys`
--
ALTER TABLE `surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `survey_categories`
--
ALTER TABLE `survey_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `survey_fields`
--
ALTER TABLE `survey_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `survey_responses`
--
ALTER TABLE `survey_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `survey_roles`
--
ALTER TABLE `survey_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=461;

--
-- AUTO_INCREMENT for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transcripts`
--
ALTER TABLE `transcripts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_activity`
--
ALTER TABLE `user_activity`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=365;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_survey_id` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_survey_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_survey_roles_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  ADD CONSTRAINT `ticket_replies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ticket_replies_ibfk_2` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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