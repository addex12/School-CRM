-- Simplified School CRM Schema for Parent-School Interaction
-- Roles: Admin (School Staff) and User (Parent)
-- Focus Areas: Surveys, Communication, Feedback, Activity Tracking

-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `school_crm` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `school_crm`;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;

-- --------------------------------------------------------
-- Core Tables
-- --------------------------------------------------------

--
-- Table structure for `roles`
--
CREATE TABLE `roles` (
  `id` INT(11) PRIMARY KEY AUTO_INCREMENT,
  `role_name` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT,
  `dashboard_path` VARCHAR(255) NOT NULL DEFAULT '/dashboard'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `roles` (`id`, `role_name`, `description`) VALUES
(1, 'admin', 'School administrators with full system access'),
(2, 'user', 'Parents with access to surveys and communication features');

-- --------------------------------------------------------
-- User Management
-- --------------------------------------------------------

--
-- Table structure for `users`
--
CREATE TABLE `users` (
  `id` INT(11) PRIMARY KEY AUTO_INCREMENT,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role_id` INT(11) NOT NULL,
  `child_name` VARCHAR(100),
  `last_login` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Communication System
-- --------------------------------------------------------

--
-- Table structure for `chat_threads`
--
CREATE TABLE `chat_threads` (
  `id` INT(11) PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `status` ENUM('open','resolved') DEFAULT 'open',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for `chat_messages`
--
CREATE TABLE `chat_messages` (
  `id` INT(11) PRIMARY KEY AUTO_INCREMENT,
  `thread_id` INT(11) NOT NULL,
  `sender_id` INT(11) NOT NULL,
  `message` TEXT NOT NULL,
  `is_admin` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`thread_id`) REFERENCES `chat_threads`(`id`),
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Survey System
-- --------------------------------------------------------

--
-- Table structure for `surveys`
--
CREATE TABLE `surveys` (
  `id` INT(11) PRIMARY KEY AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `created_by` INT(11) NOT NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for `survey_questions`
--
CREATE TABLE `survey_questions` (
  `id` INT(11) PRIMARY KEY AUTO_INCREMENT,
  `survey_id` INT(11) NOT NULL,
  `question_type` ENUM('text','radio','checkbox','rating') NOT NULL,
  `question_text` TEXT NOT NULL,
  `options` JSON,
  FOREIGN KEY (`survey_id`) REFERENCES `surveys`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for `survey_responses`
--
CREATE TABLE `survey_responses` (
  `id` INT(11) PRIMARY KEY AUTO_INCREMENT,
  `survey_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `question_id` INT(11) NOT NULL,
  `response` JSON NOT NULL,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`survey_id`) REFERENCES `surveys`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`question_id`) REFERENCES `survey_questions`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Activity & Feedback System
-- --------------------------------------------------------

--
-- Table structure for `activity_log`
--
CREATE TABLE `activity_log` (
  `id` INT(11) PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT(11),
  `activity_type` VARCHAR(50) NOT NULL,
  `description` TEXT NOT NULL,
  `ip_address` VARCHAR(45),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for `feedback`
--
CREATE TABLE `feedback` (
  `id` INT(11) PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT(11),
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('open','in_progress','resolved') DEFAULT 'open',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- System Configuration
-- --------------------------------------------------------

--
-- Table structure for `system_settings`
--
CREATE TABLE `system_settings` (
  `id` INT(11) PRIMARY KEY AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `category` VARCHAR(50) DEFAULT 'general'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `category`) VALUES
('school_name', 'Flipper School', 'general'),
('school_email', 'info@flipperschool.com', 'general'),
('survey_default_active_days', '14', 'surveys');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;