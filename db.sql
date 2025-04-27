-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 27, 2025 at 11:45 PM
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

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `created_by`, `target_roles`, `start_date`, `end_date`, `is_public`, `created_at`, `updated_at`) VALUES
(2, 'Staff Meeting & Professional Development Workshop', 'Dear Faculty Members,\r\n\r\nPlease be reminded of our mandatory staff meeting this Thursday immediately after classes. Agenda includes:\r\n\r\n    Review of term-end assessment procedures\r\n\r\n    New curriculum implementation updates\r\n\r\n    Classroom technology training\r\n\r\nFollowing the meeting, there will be a professional development workshop on \"Innovative Teaching Strategies for Diverse Learners\" conducted by Dr. Sarah Johnson from the Ministry of Education.\r\n\r\nKindly bring your laptops and review the pre-workshop materials shared via the staff portal. Light refreshments will be provided.', 4, '2', '2025-04-24 00:00:00', '2025-05-10 00:00:00', 0, '2025-04-24 19:54:22', '2025-04-25 06:06:13'),
(3, 'Flipper International Schools', '\"Nurturing Global Leaders with Local Values\"', 4, '', '2025-04-25 12:12:00', '2025-04-30 12:12:00', 1, '2025-04-25 00:14:00', '2025-04-25 10:19:58');

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
(75, 4, 'login', 'User logged in', '196.189.144.197', '2025-04-24 17:03:32'),
(76, 4, 'login', 'User logged in', '196.189.144.197', '2025-04-24 17:11:53'),
(77, 4, 'login', 'User logged in', '196.189.144.197', '2025-04-24 17:15:37'),
(78, 4, 'login', 'User logged in', '196.189.144.197', '2025-04-24 17:23:13'),
(79, 4, 'login', 'User logged in', '196.189.144.197', '2025-04-24 17:46:57'),
(80, 4, 'login', 'User logged in', '196.189.144.197', '2025-04-24 17:48:48'),
(81, 4, 'login', 'User logged in', '196.189.144.197', '2025-04-24 18:09:17'),
(82, 5, 'login', 'User logged in', '196.190.62.232', '2025-04-24 18:17:54'),
(83, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-24 18:24:04'),
(84, 4, 'logout', 'User logged out', '196.189.144.197', '2025-04-24 18:29:31'),
(85, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-24 18:29:47'),
(86, 4, 'login', 'User logged in', '196.189.144.197', '2025-04-24 19:20:19'),
(87, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-24 19:24:05'),
(88, 4, 'login', 'User logged in', '196.189.144.197', '2025-04-24 19:24:09'),
(89, 4, 'logout', 'User logged out', '196.189.144.197', '2025-04-24 19:34:24'),
(90, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-24 19:34:28'),
(91, 4, 'logout', 'User logged out', '196.189.144.197', '2025-04-24 19:42:19'),
(92, 4, 'login', 'User logged in', '196.189.144.197', '2025-04-24 19:42:23'),
(93, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-24 19:55:11'),
(94, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-24 19:55:44'),
(95, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-24 19:57:36'),
(96, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-24 19:57:39'),
(97, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-24 20:00:20'),
(98, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-24 20:00:23'),
(99, 4472, 'login', 'User logged in', '196.190.62.232', '2025-04-24 20:01:30'),
(100, 4472, 'logout', 'User logged out', '196.190.62.232', '2025-04-24 20:01:40'),
(101, 4472, 'login', 'User logged in', '196.190.62.232', '2025-04-24 20:01:46'),
(102, 5, 'login', 'User logged in', '196.190.62.232', '2025-04-24 20:04:17'),
(103, 5, 'logout', 'User logged out', '196.190.62.232', '2025-04-24 20:09:55'),
(104, 5, 'login', 'User logged in', '196.190.62.232', '2025-04-24 20:10:00'),
(105, 5, 'logout', 'User logged out', '196.190.62.232', '2025-04-24 20:14:39'),
(106, 5, 'login', 'User logged in', '196.190.62.232', '2025-04-24 20:14:58'),
(107, 4, 'login', 'User logged in', '196.189.144.197', '2025-04-24 20:17:34'),
(108, 5, 'login', 'User logged in', '196.190.62.232', '2025-04-24 20:21:25'),
(109, 5, 'login', 'User logged in', '196.190.62.232', '2025-04-24 21:00:56'),
(110, 4, 'login', 'User logged in', '196.190.62.232', '2025-04-24 21:23:19'),
(111, 4, 'login', 'User logged in', '196.190.62.232', '2025-04-24 22:05:54'),
(112, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-24 22:24:05'),
(113, 4458, 'login', 'User logged in', '196.190.62.232', '2025-04-24 22:24:25'),
(114, 4458, 'logout', 'User logged out', '196.190.62.232', '2025-04-24 22:28:01'),
(115, 4458, 'login', 'User logged in', '196.190.62.232', '2025-04-24 22:28:09'),
(116, 4458, 'logout', 'User logged out', '196.190.62.232', '2025-04-24 22:34:48'),
(117, 4472, 'login', 'User logged in', '196.190.62.232', '2025-04-24 22:34:52'),
(118, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-24 22:55:30'),
(119, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-24 22:55:45'),
(120, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-24 23:03:38'),
(121, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-24 23:03:44'),
(122, 4458, 'login', 'User logged in', '196.190.62.232', '2025-04-24 23:04:43'),
(123, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-24 23:11:08'),
(124, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-24 23:11:12'),
(125, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-24 23:11:26'),
(126, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-24 23:13:37'),
(127, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 00:05:39'),
(128, 4458, 'logout', 'User logged out', '196.190.62.232', '2025-04-25 00:05:51'),
(129, 4472, 'login', 'User logged in', '196.190.62.232', '2025-04-25 00:20:09'),
(130, 4458, 'login', 'User logged in', '196.189.144.197', '2025-04-25 00:20:27'),
(131, 4458, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 00:43:21'),
(132, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 00:43:27'),
(133, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 00:55:06'),
(134, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 00:55:15'),
(135, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 00:56:49'),
(136, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 00:57:10'),
(137, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 01:09:27'),
(138, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 01:09:30'),
(139, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 01:09:34'),
(140, 4472, 'logout', 'User logged out', '196.190.62.232', '2025-04-25 01:10:46'),
(141, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 01:12:49'),
(142, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 01:13:23'),
(143, 4458, 'login', 'User logged in', '196.190.62.232', '2025-04-25 01:19:33'),
(144, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 01:24:10'),
(145, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 01:24:28'),
(146, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 01:24:31'),
(147, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 01:24:41'),
(148, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 01:26:12'),
(149, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 01:26:16'),
(150, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 01:26:19'),
(151, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 01:26:36'),
(152, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 01:26:44'),
(153, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 01:29:30'),
(154, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 01:29:34'),
(155, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 01:32:32'),
(156, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 01:32:36'),
(157, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 01:32:48'),
(158, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 01:32:52'),
(159, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 01:34:21'),
(160, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 01:34:25'),
(161, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 01:35:46'),
(162, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 01:35:50'),
(163, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 01:36:29'),
(164, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 01:37:04'),
(165, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 01:37:36'),
(166, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 01:54:51'),
(167, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 01:55:13'),
(168, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 01:55:28'),
(169, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 01:56:24'),
(170, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 01:56:29'),
(171, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 01:57:36'),
(172, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 01:57:41'),
(173, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 02:01:42'),
(174, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 02:07:50'),
(175, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 02:13:55'),
(176, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 02:14:00'),
(177, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 02:15:05'),
(178, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 02:15:08'),
(179, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 02:19:11'),
(180, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 02:19:16'),
(181, 4472, 'logout', 'User logged out', '196.189.144.197', '2025-04-25 02:25:56'),
(182, 4472, 'login', 'User logged in', '196.189.144.197', '2025-04-25 02:28:52'),
(183, 4, 'login', 'User logged in', '196.190.62.232', '2025-04-26 16:46:14'),
(184, 4, 'login', 'User logged in', '196.190.62.232', '2025-04-26 19:21:37'),
(185, 4472, 'login', 'User logged in', '196.191.223.155', '2025-04-26 19:26:05'),
(186, 4472, 'login', 'User logged in', '196.191.223.155', '2025-04-26 20:35:14'),
(187, 4472, 'login', 'User logged in', '196.191.223.155', '2025-04-26 20:36:12'),
(188, 4472, 'login', 'User logged in', '196.191.223.155', '2025-04-26 20:36:52'),
(189, 4472, 'logout', 'User logged out', '196.191.223.155', '2025-04-26 20:37:22'),
(190, 4458, 'login', 'User logged in', '196.191.223.155', '2025-04-26 20:37:37'),
(191, 4458, 'logout', 'User logged out', '196.191.223.155', '2025-04-26 20:38:02'),
(192, 4472, 'login', 'User logged in', '196.191.223.155', '2025-04-26 20:40:03'),
(193, 4472, 'logout', 'User logged out', '196.191.223.155', '2025-04-26 20:40:05'),
(194, 4473, 'login', 'User logged in', '196.191.223.155', '2025-04-26 20:41:37'),
(195, 4472, 'login', 'User logged in', '196.191.223.155', '2025-04-26 20:49:13'),
(196, 4473, 'logout', 'User logged out', '196.191.223.155', '2025-04-26 20:49:40'),
(197, 4473, 'login', 'User logged in', '196.191.223.155', '2025-04-26 20:49:48'),
(198, 4458, 'login', 'User logged in', '196.191.223.155', '2025-04-26 20:50:02'),
(199, 4472, 'logout', 'User logged out', '196.191.223.155', '2025-04-26 21:10:49'),
(200, 4458, 'login', 'User logged in', '196.191.223.155', '2025-04-26 21:11:01'),
(201, 4458, 'logout', 'User logged out', '196.190.62.232', '2025-04-26 21:18:40'),
(202, 4472, 'login', 'User logged in', '196.190.62.232', '2025-04-26 21:18:50'),
(203, 4, 'login', 'User logged in', '196.190.62.232', '2025-04-26 21:20:02'),
(204, 4458, 'logout', 'User logged out', '196.191.223.155', '2025-04-26 21:27:39'),
(205, 4473, 'login', 'User logged in', '196.191.223.155', '2025-04-26 21:28:21'),
(206, 4, 'login', 'User logged in', '196.190.62.232', '2025-04-26 22:33:08'),
(207, 4, 'login', 'User logged in', '196.190.62.232', '2025-04-26 23:22:49'),
(208, 4458, 'login', 'User logged in', '196.190.62.232', '2025-04-26 23:36:38'),
(209, 4, 'logout', 'User logged out', '196.190.62.232', '2025-04-27 00:06:59'),
(210, 4, 'login', 'User logged in', '196.190.62.232', '2025-04-27 00:07:03'),
(211, 4, 'logout', 'User logged out', '196.190.62.232', '2025-04-27 00:07:07'),
(212, 4, 'login', 'User logged in', '196.190.62.232', '2025-04-27 00:11:23'),
(213, 4458, 'logout', 'User logged out', '196.190.62.232', '2025-04-27 00:15:07'),
(214, 4, 'login', 'User logged in', '196.191.223.155', '2025-04-27 06:32:16'),
(215, 4, 'login', 'User logged in', '196.190.62.232', '2025-04-27 06:42:23'),
(216, 4, 'login', 'User logged in', '196.190.62.232', '2025-04-27 06:43:27'),
(217, 4, 'login', 'User logged in', '196.191.223.155', '2025-04-27 10:02:11'),
(218, 4, 'login', 'User logged in', '196.190.62.232', '2025-04-27 10:18:43'),
(219, 4, 'login', 'User logged in', '196.190.62.232', '2025-04-27 11:39:47'),
(220, 4, 'login', 'User logged in', '196.191.223.155', '2025-04-27 11:58:45'),
(221, 4, 'login', 'User logged in', '196.190.62.232', '2025-04-27 12:09:26'),
(222, 4, 'logout', 'User logged out', '196.191.223.155', '2025-04-27 12:10:40'),
(223, 4, 'login', 'User logged in', '196.191.223.155', '2025-04-27 12:10:47'),
(224, 4, 'login', 'User logged in', '196.191.223.155', '2025-04-27 12:24:22'),
(225, 4, 'login', 'User logged in', '196.191.223.155', '2025-04-27 12:48:37'),
(226, 4, 'login', 'User logged in', '196.191.223.155', '2025-04-27 13:00:55'),
(227, 4, 'login', 'User logged in', '196.191.223.155', '2025-04-27 13:11:18'),
(228, 4, 'login', 'User logged in', '196.191.223.155', '2025-04-27 13:18:50'),
(229, 4, 'login', 'User logged in', '196.191.223.155', '2025-04-27 13:30:36');

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

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `user_id`, `title`, `description`, `start_date`, `end_date`, `created_at`) VALUES
(5, 4, 'For Students Only', 'Club Ceremony', '2025-04-28 00:00:00', '2025-05-01 00:00:00', '2025-04-26 23:36:27');

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
(0, 4470, 'Fee Request', 'great', 4, NULL, NULL, 'open', NULL, '2025-04-24 11:41:39'),
(0, 4472, 'Other', 'Great', 4, NULL, NULL, 'open', NULL, '2025-04-24 23:09:40'),
(0, 4458, 'Other', 'Good job', 4, NULL, NULL, 'open', NULL, '2025-04-25 00:26:03'),
(0, 4473, 'Other', 'Great', 5, NULL, NULL, 'open', NULL, '2025-04-26 20:46:21');

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
-- Dumping data for table `knowledge_base`
--

INSERT INTO `knowledge_base` (`id`, `title`, `content`, `created_at`, `updated_at`) VALUES
(1, 'what should do students in a school', '🎓 Academically:\r\n1.Attend classes regularly – Be present and punctual.\r\n\r\n2.Pay attention in class – Focus, take notes, and engage in discussions.\r\n\r\n3.Complete assignments – On time and to the best of their ability.\r\n\r\n4.Study consistently – Don’t wait until the last minute.\r\n\r\n5.Ask questions – If something’s unclear, speak up!\r\n\r\n6.Prepare for exams – Review material and practice regularly.\r\n\r\n7.Work on projects – Collaborate with classmates when needed.\r\n\r\n🧠 Personally and Socially:\r\n1.Attend classes regularly – Be present and punctual.\r\n\r\n2.Pay attention in class – Focus, take notes, and engage in discussions.\r\n\r\n3.Complete assignments – On time and to the best of their ability.\r\n\r\n4.Study consistently – Don’t wait until the last minute.\r\n\r\n5.Ask questions – If something’s unclear, speak up!\r\n\r\n6.Prepare for exams – Review material and practice regularly.\r\n\r\n7.Work on projects – Collaborate with classmates when needed.\r\n💬 Emotionally and Mentally:\r\n1.Take care of mental health – Talk to counselors or trusted adults if needed.\r\n\r\n2.Build confidence – Set goals and celebrate achievements.\r\n\r\n3.Stay curious – Learn beyond the classroom too.', '2025-04-25 05:11:52', '2025-04-25 05:11:52');

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
(1, 4, 4458, NULL, 'hi', '2025-04-25 03:20:41', 1, 0, 0, '2025-04-25 03:20:41'),
(2, 4, 4458, NULL, 'hi', '2025-04-25 03:36:21', 1, 0, 0, '2025-04-25 03:36:21'),
(3, 4, 4458, NULL, 'mmmmmm', '2025-04-25 04:05:18', 1, 0, 0, '2025-04-25 07:19:08'),
(4, 4, 4458, NULL, 'hello', '2025-04-25 04:22:02', 1, 0, 0, '2025-04-25 04:22:02'),
(5, 4, 4471, NULL, 'hello', '2025-04-25 04:22:02', 0, 0, 0, '2025-04-25 04:22:02'),
(6, 4, 4458, NULL, 'hello', '2025-04-25 04:24:28', 1, 1, 0, '2025-04-25 04:24:28'),
(7, 4, 4472, NULL, 'hello', '2025-04-25 04:24:28', 1, 0, 0, '2025-04-25 04:24:28'),
(8, 4, 4471, NULL, 'hello', '2025-04-25 04:24:28', 0, 0, 0, '2025-04-25 04:24:28'),
(9, 4472, 4, NULL, 'Hello', '2025-04-25 04:33:34', 1, 0, 0, '2025-04-25 04:33:34'),
(10, 4472, 4, NULL, 'Helllllòoooooooo', '2025-04-25 04:54:34', 1, 0, 0, '2025-04-25 04:54:34'),
(11, 4472, 5, NULL, 'hello', '2025-04-25 07:11:48', 0, 0, 0, '2025-04-25 07:11:48'),
(12, 4472, 4, NULL, 'hey', '2025-04-25 07:12:21', 1, 0, 0, '2025-04-25 07:12:21'),
(13, 4472, 4, NULL, 'uuuuuuuuuuuuuuuuuuuuuu', '2025-04-25 07:15:01', 1, 0, 0, '2025-04-25 07:15:01'),
(14, 4472, 4, NULL, 'iiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii', '2025-04-25 07:15:15', 1, 0, 0, '2025-04-25 07:15:15'),
(15, 4472, 4, NULL, 'haluuuuuuuuuuuuuuuuu', '2025-04-25 07:25:17', 1, 0, 0, '2025-04-25 07:25:17'),
(16, 4472, 5, NULL, 'Hey', '2025-04-25 08:24:58', 0, 0, 0, '2025-04-25 08:24:58'),
(17, 4472, 5, NULL, 'Hey', '2025-04-25 08:26:23', 0, 0, 0, '2025-04-25 08:26:23'),
(18, 4472, 5, NULL, 'Ttt', '2025-04-25 08:26:35', 0, 0, 0, '2025-04-25 08:26:35'),
(19, 4472, 5, NULL, 'Rrr', '2025-04-25 08:39:17', 0, 0, 0, '2025-04-25 08:39:17'),
(20, 4472, 4, NULL, 'Helloo', '2025-04-25 08:57:39', 1, 0, 0, '2025-04-25 08:57:39'),
(21, 4472, 4, NULL, 'Hi', '2025-04-25 09:06:09', 1, 0, 0, '2025-04-25 09:06:09'),
(22, 4458, 5, NULL, 'Hey There', '2025-04-25 09:50:52', 0, 0, 0, '2025-04-25 09:50:52'),
(23, 4458, 5, NULL, 'h', '2025-04-25 09:51:18', 0, 0, 0, '2025-04-25 09:51:18'),
(24, 4458, 5, NULL, 'hello', '2025-04-25 09:51:22', 0, 0, 0, '2025-04-25 09:51:22'),
(25, 4458, 5, NULL, 'test again', '2025-04-25 09:51:27', 0, 0, 0, '2025-04-25 09:51:27'),
(26, 4458, 4, NULL, 'hi', '2025-04-25 09:52:52', 1, 0, 0, '2025-04-25 09:52:52'),
(27, 4458, 4, NULL, 'Hi', '2025-04-25 10:21:28', 1, 0, 0, '2025-04-25 10:21:28'),
(28, 4472, 4, NULL, 'Hello', '2025-04-25 10:44:04', 1, 0, 0, '2025-04-25 10:44:04'),
(29, 4, 4472, NULL, 'hi', '2025-04-27 05:28:36', 1, 0, 0, '2025-04-27 05:28:36'),
(30, 4, 4472, NULL, 'nnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnn', '2025-04-27 05:35:10', 1, 0, 0, '2025-04-27 05:35:10'),
(31, 4, 4472, NULL, 'mmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmm', '2025-04-27 05:35:23', 1, 0, 0, '2025-04-27 05:35:23'),
(32, 4472, 4, NULL, 'hi', '2025-04-27 08:21:59', 1, 0, 0, '2025-04-27 08:21:59'),
(33, 4, 4472, NULL, 'Hello', '2025-04-27 22:11:23', 0, 0, 0, '2025-04-27 22:11:23');

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
(1, 65, 'TKT-67FD9BC48CF6B', 'Greetings', 'Hello', 'medium', 'resolved', NULL, '2025-04-14 13:35:32'),
(2, 4458, 'TICKET-680ACA2C11699', 'I need Support', 'Help needed', 'medium', 'open', NULL, '2025-04-24 23:33:00'),
(3, 4472, 'TICKET-680ACA308C129', 'Request for a clarification', 'Rff', 'medium', 'open', NULL, '2025-04-24 23:33:04'),
(5, 4472, 'TICKET-680D476B4713A', 'Other', 'Hello Support needed', 'high', 'in_progress', NULL, '2025-04-26 20:51:55');

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
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`answers`))
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

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `first_name`, `last_name`, `role_id`, `last_active`, `online`, `active`, `created_at`, `last_login`, `avatar`, `tracking_token`, `remember_token`) VALUES
(4, 'administrator', '$2y$10$NzdfGBS05PUk3gh0C9Cmfu6WL1bvexg4Xin/5hItCo2GcoMoOKTbO', 'adugna.gizaw@flipperschools.com', 'Admin', 'System', 1, '2025-04-27 23:30:36', 1, 1, '2025-03-24 16:50:31', '2025-04-27 13:30:36', 'admin_avatar.jpg', NULL, NULL),
(5, 'efream', '$2y$10$MVeN3l2MkGpfz7fvjOPGEORMcLh0zArHGtACBXvp7e2Vi14QH/Ldm', 'efreamdc@gmail.com', 'Efream', 'Yohannes', 1, '2025-04-25 07:00:56', 1, 1, '2025-03-25 11:47:11', '2025-04-24 21:00:56', 'avatar_5_7aa6215a045431e8.jpg', NULL, NULL),
(4458, 'ibrahim', '$2y$10$Djik3HGaTGfUIie2ZE4xq.FXCjQZs85AnoDnLRUc9z1FfNDRCNJle', 'ibrahimkebede@gmail.com', NULL, NULL, 2, '2025-04-27 09:36:38', 0, 1, '2025-04-23 15:50:30', '2025-04-26 23:36:38', 'avatar_4458_81c187c8c7f31937.jpg', NULL, NULL),
(4471, 'adugna', '$2y$10$qkVyRXmdutRbn73CKiAqJuUg8Ix.RBoE4EPjgcs/s.RqaNX5o5PCa', 'gizawadugna@gmail.com', NULL, NULL, 5, NULL, 0, 0, '2025-04-24 14:06:37', NULL, 'default.jpg', '928cf2c1d1a3f7b2da4ec1c34dbc5acb3780fefab512c53152f50b7ac5c58fc8', NULL),
(4472, 'Adugna1', '$2y$10$Yw1HI7aDDyJoN9wzudDugONHcSFAgc71IwW6dbEAVTGvcLgqqW51m', 'gizawadugna1@gmail.com', NULL, NULL, 3, '2025-04-27 08:17:13', 1, 1, '2025-04-24 18:23:15', '2025-04-26 21:18:50', 'avatar_4472_8c40dc58f6c9380a.jpg', NULL, NULL),
(4473, 'Adugna2', '$2y$10$5fZoAhWy5xV81OhEnUGmUu7NZfZnxNcKYo7hPLhPesighyB/MMu1q', 'gizawadugna2@gmail.com', NULL, NULL, 5, '2025-04-27 07:28:21', 1, 1, '2025-04-26 20:39:52', '2025-04-26 21:28:21', 'default.jpg', '8644cfe1c5717a7c3c2c826acb49f4406ccbff3a2bf5abfa024caf73d113ed1f', NULL);

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
-- Indexes for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `class_subject_id` (`class_subject_id`),
  ADD KEY `section_id` (`section_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=230;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

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
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4474;

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