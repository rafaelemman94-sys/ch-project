-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Database: `city_office_db`
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Create database
CREATE DATABASE IF NOT EXISTS `city_office_db`;
USE `city_office_db`;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `department_id` varchar(191) COLLATE utf8mb4_unicode_ci,
  `notifications` longtext COLLATE utf8mb4_unicode_ci COMMENT 'JSON array of notifications',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `departments`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `departments` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `head_id` varchar(191) COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  FOREIGN KEY (`head_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `agendas`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `agendas` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `session_id` varchar(191) COLLATE utf8mb4_unicode_ci,
  `created_by_id` varchar(191) COLLATE utf8mb4_unicode_ci,
  `approval_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `reading_stage` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sent_to_officer_id` varchar(191) COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci,
  `file_type` varchar(50) COLLATE utf8mb4_unicode_ci,
  `published` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_created_by_id` (`created_by_id`),
  KEY `idx_approval_status` (`approval_status`),
  FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`sent_to_officer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `agenda_comments`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `agenda_comments` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `agenda_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_agenda_id` (`agenda_id`),
  KEY `idx_user_id` (`user_id`),
  FOREIGN KEY (`agenda_id`) REFERENCES `agendas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `agenda_recipients`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `agenda_recipients` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `agenda_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` datetime,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_agenda_id` (`agenda_id`),
  KEY `idx_user_id` (`user_id`),
  UNIQUE KEY `unique_agenda_user` (`agenda_id`, `user_id`),
  FOREIGN KEY (`agenda_id`) REFERENCES `agendas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `agenda_resos`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `agenda_resos` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `agenda_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resolution_number` varchar(50) COLLATE utf8mb4_unicode_ci,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `votes` longtext COLLATE utf8mb4_unicode_ci COMMENT 'JSON object with vote counts',
  `created_by_id` varchar(191) COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_agenda_id` (`agenda_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_by_id` (`created_by_id`),
  FOREIGN KEY (`agenda_id`) REFERENCES `agendas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `letters`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `letters` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_user_id` varchar(191) COLLATE utf8mb4_unicode_ci,
  `to_department_id` varchar(191) COLLATE utf8mb4_unicode_ci,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `priority` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_from_user_id` (`from_user_id`),
  KEY `idx_to_department_id` (`to_department_id`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`from_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`to_department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `letter_attachments`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `letter_attachments` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `letter_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci,
  `file_type` varchar(50) COLLATE utf8mb4_unicode_ci,
  `file_size` bigint DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_letter_id` (`letter_id`),
  FOREIGN KEY (`letter_id`) REFERENCES `letters`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `letter_comments`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `letter_comments` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `letter_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_letter_id` (`letter_id`),
  KEY `idx_user_id` (`user_id`),
  FOREIGN KEY (`letter_id`) REFERENCES `letters`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `letter_resos`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `letter_resos` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `letter_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resolution_number` varchar(50) COLLATE utf8mb4_unicode_ci,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `votes` longtext COLLATE utf8mb4_unicode_ci COMMENT 'JSON object with vote counts',
  `created_by_id` varchar(191) COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_letter_id` (`letter_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_by_id` (`created_by_id`),
  FOREIGN KEY (`letter_id`) REFERENCES `letters`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `notifications`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci,
  `message` longtext COLLATE utf8mb4_unicode_ci,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci,
  `read_at` datetime,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_read_at` (`read_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `guest_sessions`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `guest_sessions` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_token` varchar(255) COLLATE utf8mb4_unicode_ci UNIQUE,
  `user_agent` longtext COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci,
  `data` longtext COLLATE utf8mb4_unicode_ci COMMENT 'JSON serialized session data',
  `expires_at` datetime,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_session_token` (`session_token`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `ai_logs`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `ai_logs` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` varchar(191) COLLATE utf8mb4_unicode_ci,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci,
  `prompt` longtext COLLATE utf8mb4_unicode_ci,
  `response` longtext COLLATE utf8mb4_unicode_ci,
  `model` varchar(50) COLLATE utf8mb4_unicode_ci,
  `tokens_used` int DEFAULT 0,
  `cost` decimal(10, 6) DEFAULT 0,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'success',
  `error_message` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Insert sample data
-- --------------------------------------------------------

INSERT IGNORE INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `created_at`) 
VALUES 
  ('c59939d58ad69391', 'pogi', 'pogiemman521@gmail.com', '$2y$10$JeMqXHFZK6Mx3ueIN3L5eudvvFPmRHjV4A3e8kslMf2GyUGvGwH52', 'admin', 'active', NOW()),
  ('ee0b209bbb9e588f', 'emman', 'emman@gmail.com', '$2y$10$B/fWUbegBcrgRlF3eElMjOilqwmJflZ48PLjGdabwJi9nVZE9LHEa', 'user', 'active', NOW());

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
