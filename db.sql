-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 04, 2025 at 05:12 AM
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
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` enum('survey','ticket','chat','feedback','login','logout','system') NOT NULL,
  `description` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `activity_type`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'login', 'User logged in', '196.189.127.63', '2025-04-02 18:31:43'),
(2, 1, 'login', 'User logged in', '196.189.127.63', '2025-04-02 18:37:53'),
(3, NULL, '', 'Failed login attempt for username: Adugna1', '196.189.127.63', '2025-04-02 18:38:03'),
(4, NULL, '', 'Failed login attempt for username: Adugna1', '196.189.127.63', '2025-04-02 18:38:06'),
(5, NULL, '', 'Failed login attempt for username: Adugna1', '196.189.127.63', '2025-04-02 18:42:00'),
(6, NULL, '', 'Failed login attempt for username: Adugna1', '196.189.127.63', '2025-04-02 18:42:14'),
(7, NULL, '', 'Failed login attempt for username: Adugna1', '196.189.127.63', '2025-04-02 18:44:19'),
(8, 3, 'login', 'User logged in', '196.189.127.63', '2025-04-02 18:58:12'),
(9, 1, 'login', 'User logged in', '196.189.127.63', '2025-04-02 18:58:22'),
(10, 1, 'login', 'User logged in', '196.189.127.63', '2025-04-02 19:02:28'),
(11, 1, 'login', 'User logged in', '196.189.127.63', '2025-04-02 19:52:17'),
(12, 1, 'login', 'User logged in', '196.189.127.63', '2025-04-02 19:53:09'),
(13, 1, 'login', 'User logged in', '196.189.127.63', '2025-04-02 19:53:16'),
(14, 1, 'login', 'User logged in', '196.189.127.63', '2025-04-02 19:53:35'),
(15, 1, 'login', 'User logged in', '196.189.127.63', '2025-04-03 03:41:43'),
(16, 1, 'login', 'User logged in', '196.189.127.63', '2025-04-03 03:42:06'),
(17, 1, 'login', 'User logged in', '196.189.127.63', '2025-04-03 03:50:21'),
(18, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 05:47:53'),
(19, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 05:48:29'),
(20, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 05:48:36'),
(21, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 05:49:03'),
(22, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 05:50:00'),
(23, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 05:50:21'),
(24, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 06:05:01'),
(25, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 06:28:36'),
(26, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 06:28:50'),
(27, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 06:29:14'),
(28, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 06:29:38'),
(29, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 06:29:55'),
(30, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 07:00:12'),
(31, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 07:46:26'),
(32, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 07:46:51'),
(33, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 07:47:55'),
(34, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 07:48:16'),
(35, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 07:48:19'),
(36, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 07:48:21'),
(37, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 07:48:25'),
(38, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 07:48:52'),
(39, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 07:50:15'),
(40, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 07:50:17'),
(41, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 07:50:59'),
(42, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 07:51:10'),
(43, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 07:51:37'),
(44, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 07:51:40'),
(45, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 07:52:06'),
(46, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 07:52:15'),
(47, 1, 'login', 'User logged in', '196.188.33.156', '2025-04-03 07:53:10'),
(48, 1, 'login', 'User logged in', '196.190.60.137', '2025-04-03 14:02:30'),
(49, 66, 'login', 'User logged in', '196.189.144.194', '2025-04-03 15:22:39'),
(50, 1, 'login', 'User logged in', '196.189.144.194', '2025-04-03 15:24:18'),
(51, 1, 'login', 'User logged in', '196.189.144.194', '2025-04-03 15:24:55'),
(52, 1, 'login', 'User logged in', '196.190.62.178', '2025-04-03 17:19:09'),
(53, 1, 'login', 'User logged in', '196.190.62.178', '2025-04-03 17:19:13'),
(54, 1, 'login', 'User logged in', '196.190.62.178', '2025-04-03 17:19:27'),
(55, 66, 'login', 'User logged in', '196.190.62.178', '2025-04-03 18:04:20'),
(56, 3, 'login', 'User logged in', '196.190.62.178', '2025-04-03 18:04:24'),
(57, 3, 'login', 'User logged in', '196.190.62.178', '2025-04-03 18:04:33'),
(58, 3, 'login', 'User logged in', '196.190.62.178', '2025-04-03 18:04:36'),
(59, 3, 'login', 'User logged in', '196.190.62.178', '2025-04-03 18:04:52'),
(60, NULL, '', 'Failed login attempt for username: adugna.gizaw@flipperschools.com', '196.190.62.178', '2025-04-03 18:04:57'),
(61, 66, 'login', 'User logged in', '196.190.62.178', '2025-04-03 18:05:09'),
(62, 3, 'login', 'User logged in', '196.190.62.178', '2025-04-03 18:06:17'),
(63, 1, 'login', 'User logged in', '196.190.62.178', '2025-04-03 18:08:06'),
(64, 3, 'login', 'User logged in', '196.190.62.178', '2025-04-03 18:08:09'),
(65, 1, 'login', 'User logged in', '196.190.62.178', '2025-04-03 18:10:56'),
(66, 1, 'login', 'User logged in', '196.190.62.178', '2025-04-03 18:11:00');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bulk_email_logs`
--

CREATE TABLE `bulk_email_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `total_recipients` int(11) NOT NULL,
  `success_count` int(11) NOT NULL,
  `error_count` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `send_method` enum('csv','role','all') NOT NULL,
  `send_to_role` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bulk_email_recipients`
--

CREATE TABLE `bulk_email_recipients` (
  `id` int(11) NOT NULL,
  `bulk_email_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL
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
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_status`
--

CREATE TABLE `chat_status` (
  `user_id` int(11) NOT NULL,
  `is_online` tinyint(1) NOT NULL DEFAULT 0,
  `last_active` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_status`
--

INSERT INTO `chat_status` (`user_id`, `is_online`, `last_active`) VALUES
(1, 0, '2025-04-03 17:51:31'),
(2, 0, '2025-04-03 17:51:31'),
(3, 0, '2025-04-03 17:51:31'),
(66, 0, '2025-04-03 17:51:31');

-- --------------------------------------------------------

--
-- Table structure for table `chat_threads`
--

CREATE TABLE `chat_threads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_requests`
--

CREATE TABLE `contact_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `manager_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_templates`
--

CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `email_templates`
--

INSERT INTO `email_templates` (`id`, `name`, `subject`, `body`, `created_at`, `updated_at`) VALUES
(1, 'Welcome Email', 'Welcome to Our Platform', 'Hello {name}, welcome to our platform!', '2025-04-03 16:35:05', '2025-04-03 16:35:05'),
(2, 'Password Reset', 'Reset Your Password', 'Hi {name}, click here to reset your password: {reset_link}', '2025-04-03 16:35:05', '2025-04-03 16:35:05');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `hire_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `status` enum('open','in_progress','resolved') NOT NULL DEFAULT 'open',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `is_email` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `action_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payrolls`
--

CREATE TABLE `payrolls` (
  `id` int(11) NOT NULL,
  `payroll_month` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','processed','paid') NOT NULL DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `salary_grade` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `dashboard_path` varchar(255) NOT NULL DEFAULT '/user/dashboard.php',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `description`, `dashboard_path`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'System Administrator with full access', '/admin/dashboard.php', '2025-03-28 16:53:20', '2025-03-28 16:53:20'),
(2, 'principal', 'School Principal with elevated privileges', '/user/dashboard.php', '2025-03-28 16:01:21', '2025-03-28 16:01:21'),
(3, 'teacher', 'Teaching staff with classroom access', '/user/dashboard.php', '2025-03-28 16:52:46', '2025-03-28 16:52:46'),
(4, 'parent', 'Student parent with limited access', '/user/dashboard.php', '2025-03-28 16:53:05', '2025-03-28 16:53:05'),
(5, 'student', 'School student with basic access', '/user/dashboard.php', '2025-03-28 16:53:39', '2025-03-28 16:53:39'),
(8, 'HOD', 'Head Of Departments role', '/user/dashboard.php', '2025-03-29 11:41:10', '2025-03-29 11:41:10'),
(9, 'new', 'New users with limited access', '/user/dashboard.php', '2025-03-29 12:24:29', '2025-03-29 12:24:29'),
(10, 'hr', 'Human Resources Manager', '/user/dashboard.php', '2025-03-29 16:22:06', '2025-03-29 16:22:06'),
(11, 'payroll_manager', 'Payroll Manager', '/user/dashboard.php', '2025-03-29 16:22:06', '2025-03-29 16:22:06'),
(12, 'rol', 'role test', '/user/dashboard.php', '2025-04-03 04:56:59', '2025-04-03 04:56:59');

-- --------------------------------------------------------

--
-- Table structure for table `salary_structures`
--

CREATE TABLE `salary_structures` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `base_salary` decimal(10,2) NOT NULL,
  `allowances` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deductions` decimal(10,2) NOT NULL DEFAULT 0.00,
  `effective_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `priority_id` int(11) NOT NULL DEFAULT 2,
  `status` enum('open','in_progress','on_hold','resolved') NOT NULL DEFAULT 'open',
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `admin_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `user_id`, `ticket_number`, `subject`, `message`, `priority_id`, `status`, `attachment`, `created_at`, `updated_at`, `admin_notes`) VALUES
(5, 1, 'TICKET-00005', 'Sample Ticket', 'Initial ticket message', 1, 'in_progress', NULL, '2025-04-02 18:18:27', '2025-04-03 04:55:50', 'lower');

-- --------------------------------------------------------

--
-- Table structure for table `surveys`
--

CREATE TABLE `surveys` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `status_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `surveys`
--

INSERT INTO `surveys` (`id`, `title`, `description`, `category_id`, `created_by`, `created_at`, `updated_at`, `starts_at`, `ends_at`, `is_anonymous`, `is_active`, `status_id`) VALUES
(3, 'Teachers Survey', 'ddfdgfdgfhgfhgf', 1, 1, '2025-04-02 18:06:07', '2025-04-02 18:06:07', '2025-04-03 08:20:00', '2025-05-10 00:00:00', 1, 1, 2),
(4, 'Writing Prompt', 'dddddddddddddd', 1, 1, '2025-04-02 20:57:09', '2025-04-02 20:57:09', '2025-04-03 12:00:00', '2025-04-26 12:00:00', 1, 1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `survey_categories`
--

CREATE TABLE `survey_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `survey_categories`
--

INSERT INTO `survey_categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Behaviour Survey', 'This is to know how behave our employees are.(example)', '2025-03-26 13:36:15', '2025-04-02 18:18:27'),
(2, 'Survey on Teachers', 'Description', '2025-03-26 13:38:51', '2025-04-02 18:18:27'),
(3, 'Students Performance Survey', 'Descritpion', '2025-03-26 13:40:18', '2025-04-02 18:18:27'),
(4, 'contact survey', '', '2025-03-28 16:47:46', '2025-04-02 18:18:27');

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
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `validation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_rules`)),
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `survey_fields`
--

INSERT INTO `survey_fields` (`id`, `survey_id`, `field_type`, `field_label`, `placeholder`, `field_name`, `field_options`, `is_required`, `validation_rules`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 4, 'select', 'Hello there', '', 'hello', '\"hi\\r\\nhow\\r\\nare\\r\\nyou\"', 1, NULL, 0, '2025-04-03 07:57:09', '2025-04-03 07:57:09'),
(2, 4, 'checkbox', '1+1', '', 'add', '\"2\\r\\n3\\r\\n4\\r\\n5\\r\\n6\\r\\n7\"', 1, NULL, 1, '2025-04-03 07:57:09', '2025-04-03 07:57:09'),
(3, 4, 'radio', '2+3', '', 'add', '\"3\\r\\n4\\r\\n5\\r\\n6\"', 1, NULL, 2, '2025-04-03 07:57:09', '2025-04-03 07:57:09');

-- --------------------------------------------------------

--
-- Table structure for table `survey_responses`
--

CREATE TABLE `survey_responses` (
  `id` int(11) NOT NULL,
  `survey_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `survey_roles`
--

CREATE TABLE `survey_roles` (
  `id` int(11) NOT NULL,
  `survey_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 1,
  `can_respond` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `survey_roles`
--

INSERT INTO `survey_roles` (`id`, `survey_id`, `role_id`, `can_view`, `can_respond`, `created_at`) VALUES
(1, 3, 3, 1, 1, '2025-04-03 05:06:07'),
(2, 4, 3, 1, 1, '2025-04-03 07:57:09');

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
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) NOT NULL DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `templates`
--

CREATE TABLE `templates` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `templates`
--

INSERT INTO `templates` (`id`, `name`, `content`, `created_at`, `updated_at`) VALUES
(1, 'Welcome Email', 'Dear [Name], Welcome to our platform!', '2025-03-31 22:03:48', '2025-04-02 18:18:27'),
(2, 'Password Reset', 'Click the link below to reset your password: [Reset Link]', '2025-03-31 22:03:48', '2025-04-02 18:18:27');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_priorities`
--

CREATE TABLE `ticket_priorities` (
  `id` int(11) NOT NULL,
  `value` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `color` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ticket_priorities`
--

INSERT INTO `ticket_priorities` (`id`, `value`, `label`, `color`) VALUES
(1, 'low', 'Low', 'green'),
(2, 'medium', 'Medium', 'orange'),
(3, 'high', 'High', 'red');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_replies`
--

CREATE TABLE `ticket_replies` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ticket_replies`
--

INSERT INTO `ticket_replies` (`id`, `ticket_id`, `user_id`, `message`, `is_admin`, `created_at`, `updated_at`) VALUES
(1, 5, 1, 'This is an admin response to the ticket', 1, '2025-03-30 00:00:00', '2025-04-02 18:18:27'),
(2, 5, 1, 'responded', 1, '2025-04-03 04:37:56', '2025-04-03 04:37:56'),
(3, 5, 1, 'responded', 1, '2025-04-03 04:38:13', '2025-04-03 04:38:13'),
(4, 5, 1, 'other response', 1, '2025-04-03 04:47:06', '2025-04-03 04:47:06'),
(5, 5, 1, 'other', 1, '2025-04-03 04:47:20', '2025-04-03 04:47:20');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role_id` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `last_activity` timestamp NULL DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` timestamp NULL DEFAULT NULL,
  `avatar` varchar(255) NOT NULL DEFAULT 'default.jpg',
  `notification_prefs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_prefs`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role_id`, `active`, `created_at`, `last_login`, `last_activity`, `reset_token`, `reset_token_expires`, `avatar`, `notification_prefs`) VALUES
(1, 'administrator', '$2y$10$NzdfGBS05PUk3gh0C9Cmfu6WL1bvexg4Xin/5hItCo2GcoMoOKTbO', 'adugna.gizaw@flipperschools.com', 1, 1, '2025-03-25 14:50:31', '2025-04-03 18:11:00', '2025-04-01 13:11:40', NULL, NULL, 'default.jpg', '{\"email\": true, \"push\": true}'),
(2, 'efream', '$2y$10$MVeN3l2MkGpfz7fvjOPGEORMcLh0zArHGtACBXvp7e2Vi14QH/Ldm', 'efreamyohannes@gmail.com', 1, 1, '2025-03-25 22:47:11', '2025-03-28 21:13:37', '2025-03-29 22:43:32', NULL, NULL, 'default.jpg', '{\"email\": true, \"push\": true}'),
(3, 'Adugna1', '$2y$10$BSTvJlNIFODcgtoQX9JLnule.CjEr3HTg5wtKNj85IIfJ8f467K7O', 'gizawadugna@gmail.com', 5, 1, '2025-03-29 12:03:37', '2025-04-03 18:08:09', '2025-04-01 13:11:59', NULL, NULL, 'avatar_65_d1bb19e4e9524942.jpeg', '{\"email\": true, \"push\": true}'),
(66, 'Adugna', '$2y$10$NByNbKJuJ6JQj/QWfiAz9.gRuBdHqVtMAMEkZPDmLILF/D7WE0vAC', 'gizawadugna1@gmail.com', 5, 1, '2025-04-03 15:19:49', '2025-04-03 18:05:09', NULL, NULL, NULL, 'default.jpg', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `bulk_email_logs`
--
ALTER TABLE `bulk_email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `template_id` (`template_id`);

--
-- Indexes for table `bulk_email_recipients`
--
ALTER TABLE `bulk_email_recipients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bulk_email_id` (`bulk_email_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `thread_id` (`thread_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `chat_status`
--
ALTER TABLE `chat_status`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `chat_threads`
--
ALTER TABLE `chat_threads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `contact_requests`
--
ALTER TABLE `contact_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `contact_responses`
--
ALTER TABLE `contact_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contact_id` (`contact_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `manager_user_id` (`manager_user_id`);

--
-- Indexes for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `position_id` (`position_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payrolls`
--
ALTER TABLE `payrolls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

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
-- Indexes for table `response_data`
--
ALTER TABLE `response_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `response_id` (`response_id`),
  ADD KEY `field_id` (`field_id`);

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
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `priority_id` (`priority_id`);

--
-- Indexes for table `surveys`
--
ALTER TABLE `surveys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `status_id` (`status_id`);

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
  ADD UNIQUE KEY `survey_role` (`survey_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `survey_statuses`
--
ALTER TABLE `survey_statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `status` (`status`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `templates`
--
ALTER TABLE `templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ticket_priorities`
--
ALTER TABLE `ticket_priorities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `value` (`value`);

--
-- Indexes for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `user_id` (`user_id`);

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
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bulk_email_logs`
--
ALTER TABLE `bulk_email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bulk_email_recipients`
--
ALTER TABLE `bulk_email_recipients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_threads`
--
ALTER TABLE `chat_threads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_requests`
--
ALTER TABLE `contact_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_responses`
--
ALTER TABLE `contact_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
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
-- AUTO_INCREMENT for table `response_data`
--
ALTER TABLE `response_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `salary_structures`
--
ALTER TABLE `salary_structures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `surveys`
--
ALTER TABLE `surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `survey_categories`
--
ALTER TABLE `survey_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `survey_fields`
--
ALTER TABLE `survey_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `survey_responses`
--
ALTER TABLE `survey_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `survey_roles`
--
ALTER TABLE `survey_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `survey_statuses`
--
ALTER TABLE `survey_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `templates`
--
ALTER TABLE `templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ticket_priorities`
--
ALTER TABLE `ticket_priorities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `fk_activitylog_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_auditlog_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bulk_email_logs`
--
ALTER TABLE `bulk_email_logs`
  ADD CONSTRAINT `fk_bulkemail_template` FOREIGN KEY (`template_id`) REFERENCES `email_templates` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_bulkemail_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bulk_email_recipients`
--
ALTER TABLE `bulk_email_recipients`
  ADD CONSTRAINT `fk_bulkemailrecipient_bulkemail` FOREIGN KEY (`bulk_email_id`) REFERENCES `bulk_email_logs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `fk_chatmessage_thread` FOREIGN KEY (`thread_id`) REFERENCES `chat_threads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_chatmessage_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_status`
--
ALTER TABLE `chat_status`
  ADD CONSTRAINT `fk_chatstatus_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_threads`
--
ALTER TABLE `chat_threads`
  ADD CONSTRAINT `fk_chatthread_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contact_requests`
--
ALTER TABLE `contact_requests`
  ADD CONSTRAINT `fk_contactrequest_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contact_responses`
--
ALTER TABLE `contact_responses`
  ADD CONSTRAINT `fk_contactresponse_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_contactresponse_contact` FOREIGN KEY (`contact_id`) REFERENCES `contact_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_department_manager` FOREIGN KEY (`manager_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employee_position` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_employee_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `fk_feedback_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_message_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_message_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payrolls`
--
ALTER TABLE `payrolls`
  ADD CONSTRAINT `fk_payroll_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_items`
--
ALTER TABLE `payroll_items`
  ADD CONSTRAINT `fk_payrollitem_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payrollitem_payroll` FOREIGN KEY (`payroll_id`) REFERENCES `payrolls` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `positions`
--
ALTER TABLE `positions`
  ADD CONSTRAINT `fk_position_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `response_data`
--
ALTER TABLE `response_data`
  ADD CONSTRAINT `fk_responsedata_field` FOREIGN KEY (`field_id`) REFERENCES `survey_fields` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_responsedata_response` FOREIGN KEY (`response_id`) REFERENCES `survey_responses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `salary_structures`
--
ALTER TABLE `salary_structures`
  ADD CONSTRAINT `fk_salarystructure_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `fk_ticket_priority` FOREIGN KEY (`priority_id`) REFERENCES `ticket_priorities` (`id`),
  ADD CONSTRAINT `fk_ticket_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `surveys`
--
ALTER TABLE `surveys`
  ADD CONSTRAINT `fk_survey_category` FOREIGN KEY (`category_id`) REFERENCES `survey_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_survey_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_survey_status` FOREIGN KEY (`status_id`) REFERENCES `survey_statuses` (`id`);

--
-- Constraints for table `survey_fields`
--
ALTER TABLE `survey_fields`
  ADD CONSTRAINT `fk_surveyfield_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `survey_responses`
--
ALTER TABLE `survey_responses`
  ADD CONSTRAINT `fk_surveyresponse_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_surveyresponse_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `survey_roles`
--
ALTER TABLE `survey_roles`
  ADD CONSTRAINT `fk_surveyrole_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_surveyrole_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  ADD CONSTRAINT `fk_ticketreply_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ticketreply_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

-- Remove tables related to employees, attendance, payrolls, surveys, and other roles.
DROP TABLE IF EXISTS `attendance`, `audit_logs`, `bulk_email_logs`, `bulk_email_recipients`, 
`chat_messages`, `chat_status`, `chat_threads`, `contact_requests`, `contact_responses`, 
`departments`, `email_templates`, `employees`, `feedback`, `messages`, `payrolls`, 
`payroll_items`, `positions`, `response_data`, `salary_structures`, `support_tickets`, 
`survey_categories`, `survey_fields`, `survey_responses`, `survey_roles`, `survey_statuses`, 
`surveys`, `ticket_priorities`, `ticket_replies`;

-- Modify the `roles` table to include only Admin and Parent roles.
DELETE FROM `roles` WHERE `role_name` NOT IN ('admin', 'parent');

-- Modify the `users` table to ensure only Admin and Parent users exist.
DELETE FROM `users` WHERE `role_id` NOT IN (SELECT `id` FROM `roles`);

-- Remove foreign key constraints and columns referencing removed tables.
ALTER TABLE `activity_log` DROP FOREIGN KEY `fk_activitylog_user`;
ALTER TABLE `activity_log` ADD CONSTRAINT `fk_activitylog_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- Remove unused settings from `system_settings`.
DELETE FROM `system_settings` WHERE `setting_key` IN ('smtp_provider', 'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_secure');

-- Remove unused templates.
DELETE FROM `templates`;

-- Update the `roles` table to reflect only Admin and Parent roles.
UPDATE `roles` SET `description` = 'System Administrator with full access' WHERE `role_name` = 'admin';
UPDATE `roles` SET `description` = 'Parent with limited access' WHERE `role_name` = 'parent';

-- Remove unnecessary indexes and constraints from the database;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
