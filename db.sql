-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 31, 2025 at 03:11 AM
-- Server version: 10.6.21-MariaDB-cll-lve
-- PHP Version: 8.3.19
--
-- Database: `flipperschool_parent_survey_system`
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
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` enum('survey','ticket','chat','feedback','login','logout') NOT NULL,
  `description` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `hours_worked` decimal(5,2) DEFAULT NULL,
  `status` enum('present','absent','late','leave') NOT NULL
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
-- Table structure for table `chats`
--

CREATE TABLE `chats` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `last_activity` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `thread_id`, `user_id`, `message`, `attachment`, `is_admin`, `created_at`) VALUES
(0, 0, 65, 'Hello\r\n', NULL, 0, '2025-03-30 09:56:10');

-- --------------------------------------------------------

--
-- Table structure for table `chat_threads`
--

CREATE TABLE `chat_threads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_requests`
--

CREATE TABLE `contact_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `contact_requests`
--

INSERT INTO `contact_requests` (`id`, `user_id`, `name`, `email`, `subject`, `message`, `created_at`) VALUES
(1, 3, 'aaaa', 'gizawadugna@gmail.com', 'aaaaaaaaaa', 'aaaaaaaaaaa', '2025-03-25 23:55:24');

-- --------------------------------------------------------

--
-- Table structure for table `contact_responses`
--

CREATE TABLE `contact_responses` (
  `id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `manager_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `hire_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `status` enum('open','in_progress','resolved') DEFAULT 'open',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `user_id`, `subject`, `message`, `rating`, `status`, `admin_notes`, `created_at`) VALUES
(1, 3, 'Regarding Qay of Teaching(example)', 'You are Doing Great', 5, 'open', NULL, '2025-03-26 12:03:54'),
(2, 3, 'Great', 'Fantastic', 3, 'open', NULL, '2025-03-26 15:06:19'),
(3, 3, 'goodyes good', 'very good', 3, 'open', NULL, '2025-03-26 16:43:24'),
(4, 5, 'about meeting', 'have you meet parents today', 1, 'open', NULL, '2025-03-26 18:20:41');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `sent_at` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  `is_email` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payrolls`
--

CREATE TABLE `payrolls` (
  `id` int(11) NOT NULL,
  `payroll_month` date NOT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('draft','processed','paid') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_items`
--

CREATE TABLE `payroll_items` (
  `id` int(11) NOT NULL,
  `payroll_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('salary','allowance','deduction','bonus') NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `department_id` int(11) NOT NULL,
  `salary_grade` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `survey_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','text','rating') NOT NULL,
  `options` text DEFAULT NULL,
  `is_required` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `responses`
--

CREATE TABLE `responses` (
  `id` int(11) NOT NULL,
  `survey_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `response` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `response_data`
--

CREATE TABLE `response_data` (
  `id` int(11) NOT NULL,
  `response_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `field_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `response_data`
--

INSERT INTO `response_data` (`id`, `response_id`, `field_id`, `field_value`) VALUES
(1, 1, 9, 'Very Fine'),
(2, 1, 10, 'I said'),
(3, 1, 11, 'Yes'),
(4, 1, 12, '2'),
(5, 2, 8, ''),
(6, 4, 9, 'Very Fine'),
(7, 4, 10, 'I said'),
(8, 4, 11, 'No'),
(9, 4, 12, '5'),
(10, 5, 13, NULL),
(11, 5, 14, '7'),
(12, 6, 8, ''),
(13, 7, 13, NULL),
(14, 7, 14, '6');

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
(2, 'principal', 'School Principal', '2025-03-28 16:01:21'),
(3, 'teacher', 'Teaching Staff', '2025-03-28 16:52:46'),
(4, 'parent', 'Student Parent', '2025-03-28 16:53:05'),
(5, 'student', 'School Student', '2025-03-28 16:53:39'),
(8, 'HOD', 'Head Of Departments role.', '2025-03-29 11:41:10'),
(9, 'new', 'New comers!', '2025-03-29 12:24:29'),
(10, 'hr', 'Human Resources Manager', '2025-03-29 16:22:06'),
(11, 'payroll_manager', 'Payroll Manager', '2025-03-29 16:22:06');

-- --------------------------------------------------------

--
-- Table structure for table `salary_structures`
--

CREATE TABLE `salary_structures` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `base_salary` decimal(10,2) NOT NULL,
  `allowances` decimal(10,2) DEFAULT 0.00,
  `deductions` decimal(10,2) DEFAULT 0.00,
  `effective_date` date NOT NULL
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
(1, 3, 'TKT-67E364962248E', 'Greetings', 'Hello there', 'medium', 'open', NULL, '2025-03-26 02:21:10'),
(2, 3, 'TKT-67E3EC39228B1', 'I need Help', 'Urgent', 'high', 'open', NULL, '2025-03-26 11:59:53'),
(3, 3, 'TKT-67E41BDEC5C68', 'Seeking support', 'Other Question', 'low', 'open', NULL, '2025-03-26 15:23:10'),
(4, 3, 'TKT-67E41C406EB5A', 'next', 'next', 'medium', 'open', NULL, '2025-03-26 15:24:48'),
(5, 3, 'TKT-67E420C0CB8D8', 'bbbbbbbbbbb', 'c', 'high', 'open', NULL, '2025-03-26 15:44:00'),
(6, 3, 'TKT-67E4270525ECD', 'dfdfdfdfddf', 'fdfdfdfd', 'medium', 'open', NULL, '2025-03-26 16:10:45'),
(7, 3, 'TKT-67E427B701943', 'dfdfdfdfddf', 'fdfdfdfd', 'medium', 'open', NULL, '2025-03-26 16:13:43');

-- --------------------------------------------------------

--
-- Table structure for table `surveys`
--

CREATE TABLE `surveys` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `target_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`target_roles`)),
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `status` enum('draft','active','inactive','archived') NOT NULL DEFAULT 'draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `surveys`
--

INSERT INTO `surveys` (`id`, `title`, `description`, `category_id`, `target_roles`, `created_by`, `created_at`, `starts_at`, `ends_at`, `is_anonymous`, `is_active`, `status`) VALUES
(1, 'survey 1', 'desc 1', 1, '[\"parent\"]', 4, '2025-03-26 13:41:45', '2025-03-26 11:11:00', '2025-03-28 11:11:00', 0, 1, 'draft'),
(2, 'Sample Survey', 'Just to check', 1, '[\"student\",\"teacher\",\"parent\"]', 4, '2025-03-26 19:33:11', '2025-03-26 11:11:00', '2025-03-28 11:11:00', 1, 1, 'inactive'),
(3, 'Secon Survey For Teachers', 'For Teachers Only', 2, '[\"teacher\"]', 4, '2025-03-26 19:37:05', '2025-03-26 11:11:00', '2025-03-29 11:01:00', 0, 1, 'active'),
(4, 'Survey for all Three', 'For all', 1, '[\"student\",\"teacher\",\"parent\"]', 4, '2025-03-26 19:41:38', '2025-03-26 00:00:00', '2025-03-31 11:11:00', 0, 1, 'active'),
(5, 'LEts Do Survey', 'Yes Lets Do it', 3, '[\"student\",\"teacher\",\"parent\"]', 4, '2025-03-26 20:54:53', '2025-03-26 11:11:00', '2025-04-01 11:11:00', 0, 1, 'active'),
(6, 'Formal Survey', 'This is a formal Survey', 1, '[\"student\",\"teacher\",\"parent\"]', 4, '2025-03-27 17:49:02', '2025-03-27 20:47:00', '2025-04-03 20:47:00', 1, 1, 'active');

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
(1, 'Behaviour Survey', 'This is to know how behave our employees are.(example)', '2025-03-26 13:36:15'),
(2, 'Survey on Teachers', 'Description', '2025-03-26 13:38:51'),
(3, 'Students Performance Survey', 'Descritpion', '2025-03-26 13:40:18'),
(4, 'contact survey', '', '2025-03-28 16:47:46');

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
  `placeholder` varchar(255) DEFAULT NULL,
  `field_name` varchar(100) NOT NULL,
  `field_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`field_options`)),
  `is_required` tinyint(1) DEFAULT 1,
  `validation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_rules`)),
  `display_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `survey_fields`
--

INSERT INTO `survey_fields` (`id`, `survey_id`, `field_type`, `field_label`, `placeholder`, `field_name`, `field_options`, `is_required`, `validation_rules`, `display_order`) VALUES
(5, 3, 'radio', 'Hello', '', 'field_1', '[\"Hi\",\"how are you\",\"hello there\"]', 1, NULL, 0),
(6, 3, 'radio', 'All good?', '', 'field_2', '[\"Yes\",\"No\"]', 1, NULL, 1),
(7, 3, 'text', 'If No you want you write why?', '', 'field_3', NULL, 0, NULL, 2),
(8, 4, 'checkbox', 'Hello', 'Hello,Hello there, how you', 'field_1', '[\"\"]', 1, NULL, 0),
(9, 2, 'checkbox', 'How Are you?', '', 'field_1', '[\"Fine\",\"Very Fine\",\"Who cares\"]', 1, NULL, 0),
(10, 2, 'radio', 'What Do you mean?', '', 'field_2', '[\"I said\",\"Who\",\"Cares\"]', 1, NULL, 1),
(11, 2, 'radio', 'Are you Kidding?', '', 'field_3', '[\"Yes\",\"No\"]', 1, NULL, 2),
(12, 2, 'rating', 'Okay Final Words?', '', 'field_4', NULL, 1, NULL, 3),
(15, 6, 'checkbox', 'Surv 1', '', 'field_1', '[\"1\",\"2\",\"3\",\"4\",\"5\"]', 0, NULL, 0),
(16, 6, 'checkbox', 'Surv2', '', 'field_2', '[\"H1\",\"H2\",\"H3\"]', 0, NULL, 1),
(0, 5, 'radio', '1+1', '', 'field_1', '[\"2\",\"3\",\"4\",\"5\",\"6\"]', 1, NULL, 0),
(0, 5, 'checkbox', '5+5', '', 'field_2', '[\"5\",\"6\",\"7\"]', 1, NULL, 1);

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
  `answers` JSON DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `survey_responses`
--

INSERT INTO `survey_responses` (`id`, `survey_id`, `user_id`, `submitted_at`) VALUES
(1, 2, 5, '2025-03-26 20:45:25'),
(2, 4, 5, '2025-03-26 20:47:46'),
(3, 1, 3, '2025-03-26 20:50:54'),
(4, 2, 3, '2025-03-26 20:52:24'),
(5, 5, 3, '2025-03-26 20:58:49'),
(6, 4, 3, '2025-03-26 20:59:38'),
(7, 5, 5, '2025-03-26 21:18:55');

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_group`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'School Survey System', 'general', '2025-03-26 04:43:42', '2025-03-26 04:43:42'),
(2, 'site_email', 'adugna.gizaw@flipperschools.com', 'general', '2025-03-26 04:43:42', '2025-03-27 18:06:53'),
(3, 'timezone', 'Africa/Addis_Ababa', 'general', '2025-03-26 04:43:42', '2025-03-27 18:06:53'),
(4, 'items_per_page', '10', 'general', '2025-03-26 04:43:42', '2025-03-26 04:43:42'),
(5, 'admin_menu', '[{\"title\":\"Dashboard\",\"url\":\"dashboard.php\",\"icon\":\"fa-home\"}]', 'general', '2025-03-26 04:43:42', '2025-03-26 04:43:42'),
(6, 'site_logo', '', 'appearance', '2025-03-26 04:43:42', '2025-03-26 04:43:42'),
(7, 'favicon', '', 'appearance', '2025-03-26 04:43:42', '2025-03-26 04:43:42'),
(8, 'theme_color', '#3498db', 'appearance', '2025-03-26 04:43:42', '2025-03-26 04:43:42'),
(9, 'smtp_provider', 'gmail', 'email', '2025-03-26 04:43:42', '2025-03-27 18:06:53'),
(10, 'smtp_host', 'smtp.gmail.com', 'email', '2025-03-26 04:43:42', '2025-03-27 18:06:53'),
(11, 'smtp_port', '587', 'email', '2025-03-26 04:43:42', '2025-03-26 04:43:42'),
(12, 'smtp_username', 'adugna.gizaw@flipperschools.com', 'email', '2025-03-26 04:43:42', '2025-03-27 18:06:53'),
(13, 'smtp_password', 'SutumaJigi25582067s-', 'email', '2025-03-26 04:43:42', '2025-03-27 18:06:53'),
(14, 'smtp_secure', 'tls', 'email', '2025-03-26 04:43:42', '2025-03-26 04:43:42');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_replies`
--

CREATE TABLE `ticket_replies` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `ticket_replies`
--

INSERT INTO `ticket_replies` (`id`, `ticket_id`, `user_id`, `message`, `created_at`) VALUES
(1, 5, 4, 'D', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_responses`
--

CREATE TABLE `ticket_responses` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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
  `role_id` int(11) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `last_activity` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `avatar` varchar(255) DEFAULT 'default.jpg',
  `notification_prefs` longtext DEFAULT '{"email": true, "push": true}',
  `social_provider` varchar(20) DEFAULT NULL,
  `social_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role_id`, `active`, `created_at`, `last_login`, `last_activity`, `reset_token`, `reset_token_expires`, `avatar`, `notification_prefs`, `social_provider`, `social_id`) VALUES
(4, 'administrator', '$2y$10$NzdfGBS05PUk3gh0C9Cmfu6WL1bvexg4Xin/5hItCo2GcoMoOKTbO', 'adugna.gizaw@flipperschools.com', 1, 1, '2025-03-25 14:50:31', '2025-03-30 15:36:47', '2025-03-31 02:36:47', NULL, NULL, 'default.jpg', '{\"email\": true, \"push\": true}', NULL, NULL),
(5, 'efream', '$2y$10$MVeN3l2MkGpfz7fvjOPGEORMcLh0zArHGtACBXvp7e2Vi14QH/Ldm', 'efreamyohannes@gmail.com', 1, 1, '2025-03-25 22:47:11', '2025-03-28 21:13:37', '2025-03-29 22:43:32', NULL, NULL, 'default.jpg', '{\"email\": true, \"push\": true}', NULL, NULL),
(65, 'Adugna1', '$2y$10$H.mSTrcOoWO1azAEZ0HBvuEs5x1jpKGw29wvVxPtM/s..FsqHQ6lG', 'gizawadugna@gmail.com', 5, 1, '2025-03-29 12:03:37', '2025-03-30 12:13:23', '2025-03-30 23:13:23', 'bc8bcd5e47b94c4d751529ad4165b83d', '2025-03-30 07:07:30', 'avatar_65_d1bb19e4e9524942.jpeg', '{\"email\": true, \"push\": true}', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `manager_user_id` (`manager_user_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `position_id` (`position_id`);

--
-- Indexes for table `payrolls`
--
ALTER TABLE `payrolls`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payroll_items`
--
ALTER TABLE `payroll_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payroll_id` (`payroll_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `salary_structures`
--
ALTER TABLE `salary_structures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_user_role` (`role_id`),
  ADD KEY `idx_role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payrolls`
--
ALTER TABLE `payrolls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_items`
--
ALTER TABLE `payroll_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `salary_structures`
--
ALTER TABLE `salary_structures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`manager_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_items`
--
ALTER TABLE `payroll_items`
  ADD CONSTRAINT `payroll_items_ibfk_1` FOREIGN KEY (`payroll_id`) REFERENCES `payrolls` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_items_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `positions`
--
ALTER TABLE `positions`
  ADD CONSTRAINT `positions_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `salary_structures`
--
ALTER TABLE `salary_structures`
  ADD CONSTRAINT `salary_structures_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;

--
-- Modify column type for `target_roles` in `surveys` table
--
ALTER TABLE surveys MODIFY COLUMN target_roles JSON NOT NULL;

-- Add column `answers` to `survey_responses` table if it does not already exist
ALTER TABLE survey_responses 
ADD COLUMN IF NOT EXISTS answers JSON DEFAULT NULL;

--
-- Ensure `answers` column in `survey_responses` table is properly defined as a JSON column
--
ALTER TABLE survey_responses MODIFY COLUMN answers JSON DEFAULT NULL;

--
-- Add foreign key relationships for `survey_responses`
ALTER TABLE survey_responses
ADD CONSTRAINT fk_survey_responses_survey_id FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
ADD CONSTRAINT fk_survey_responses_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Add foreign key relationships for `survey_fields`
ALTER TABLE survey_fields
ADD CONSTRAINT fk_survey_fields_survey_id FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE;

-- Add foreign key relationships for `surveys`
ALTER TABLE surveys
ADD CONSTRAINT fk_surveys_category_id FOREIGN KEY (category_id) REFERENCES survey_categories(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_surveys_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- Add foreign key relationships for `response_data`
ALTER TABLE response_data
ADD CONSTRAINT fk_response_data_response_id FOREIGN KEY (response_id) REFERENCES responses(id) ON DELETE CASCADE,
ADD CONSTRAINT fk_response_data_field_id FOREIGN KEY (field_id) REFERENCES survey_fields(id) ON DELETE CASCADE;

-- Add foreign key relationships for `responses`
ALTER TABLE responses
ADD CONSTRAINT fk_responses_survey_id FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
ADD CONSTRAINT fk_responses_question_id FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
ADD CONSTRAINT fk_responses_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Add foreign key relationships for `questions`
ALTER TABLE questions
ADD CONSTRAINT fk_questions_survey_id FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE;

-- Add foreign key relationships for `audit_logs`
ALTER TABLE audit_logs
ADD CONSTRAINT fk_audit_logs_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Add foreign key relationships for `contact_requests`
ALTER TABLE contact_requests
ADD CONSTRAINT fk_contact_requests_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Add foreign key relationships for `contact_responses`
ALTER TABLE contact_responses
ADD CONSTRAINT fk_contact_responses_contact_id FOREIGN KEY (contact_id) REFERENCES contact_requests(id) ON DELETE CASCADE,
ADD CONSTRAINT fk_contact_responses_admin_id FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL;

-- Add foreign key relationships for `feedback`
ALTER TABLE feedback
ADD CONSTRAINT fk_feedback_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Add foreign key relationships for `roles`
ALTER TABLE users
ADD CONSTRAINT fk_users_role_id FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL;

-- Add foreign key relationships for `chat_messages`
ALTER TABLE chat_messages
ADD CONSTRAINT fk_chat_messages_thread_id FOREIGN KEY (thread_id) REFERENCES chat_threads(id) ON DELETE CASCADE,
ADD CONSTRAINT fk_chat_messages_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Add foreign key relationships for `chat_threads`
ALTER TABLE chat_threads
ADD CONSTRAINT fk_chat_threads_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Add foreign key relationships for `support_tickets`
ALTER TABLE support_tickets
ADD CONSTRAINT fk_support_tickets_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Add foreign key relationships for `ticket_replies`
ALTER TABLE ticket_replies
ADD CONSTRAINT fk_ticket_replies_ticket_id FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
ADD CONSTRAINT fk_ticket_replies_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Add foreign key relationships for `ticket_responses`
ALTER TABLE ticket_responses
ADD CONSTRAINT fk_ticket_responses_ticket_id FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
ADD CONSTRAINT fk_ticket_responses_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Add foreign key relationships for `notifications`
ALTER TABLE notifications
ADD CONSTRAINT fk_notifications_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Add foreign key relationships for `departments`
ALTER TABLE departments
ADD CONSTRAINT fk_departments_manager_user_id FOREIGN KEY (manager_user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Add foreign key relationships for `positions`
ALTER TABLE positions
ADD CONSTRAINT fk_positions_department_id FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE;

-- Add foreign key relationships for `employees`
ALTER TABLE employees
ADD CONSTRAINT fk_employees_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
ADD CONSTRAINT fk_employees_position_id FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE;

-- Add foreign key relationships for `salary_structures`
ALTER TABLE salary_structures
ADD CONSTRAINT fk_salary_structures_employee_id FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE;

-- Add foreign key relationships for `payroll_items`
ALTER TABLE payroll_items
ADD CONSTRAINT fk_payroll_items_payroll_id FOREIGN KEY (payroll_id) REFERENCES payrolls(id) ON DELETE CASCADE,
ADD CONSTRAINT fk_payroll_items_employee_id FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE;

-- Add new table `survey_statuses`
CREATE TABLE `survey_statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(50) NOT NULL UNIQUE,
  `label` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default statuses
INSERT INTO `survey_statuses` (`status`, `label`, `icon`) VALUES
('draft', 'Draft', 'fa-file'),
('active', 'Active', 'fa-rocket'),
('inactive', 'Inactive', 'fa-pause'),
('archived', 'Archived', 'fa-archive');

-- Update `surveys` table to reference `survey_statuses`
ALTER TABLE `surveys`
  ADD CONSTRAINT `fk_surveys_status` FOREIGN KEY (`status`) REFERENCES `survey_statuses` (`status`) ON DELETE SET NULL;

-- Add table for ticket priorities
CREATE TABLE `ticket_priorities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `value` VARCHAR(50) NOT NULL UNIQUE,
    `label` VARCHAR(100) NOT NULL,
    `color` VARCHAR(20) NOT NULL
);

-- Insert default priorities
INSERT INTO `ticket_priorities` (`value`, `label`, `color`) VALUES
('low', 'Low', 'green'),
('medium', 'Medium', 'orange'),
('high', 'High', 'red');

-- Add table for templates
CREATE TABLE `templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default templates (optional)
INSERT INTO `templates` (`name`, `content`) VALUES
('Welcome Email', 'Dear [Name], Welcome to our platform!'),
('Password Reset', 'Click the link below to reset your password: [Reset Link]');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
