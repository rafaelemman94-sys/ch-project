-- Document creation and management tables for kaya_portal.
USE `kaya_portal`;

CREATE TABLE IF NOT EXISTS `authors` (
  `id` varchar(32) NOT NULL PRIMARY KEY,
  `name` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  UNIQUE KEY `uq_authors_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sponsors` (
  `id` varchar(32) NOT NULL PRIMARY KEY,
  `name` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  UNIQUE KEY `uq_sponsors_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `documents` (
  `id` varchar(32) NOT NULL PRIMARY KEY,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `author_id` varchar(32) DEFAULT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `sponsor_id` varchar(32) DEFAULT NULL,
  `sponsor_name` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `version` varchar(20) NOT NULL DEFAULT '1.0',
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` int unsigned DEFAULT NULL,
  `created_by_id` varchar(32) DEFAULT NULL,
  `updated_by_id` varchar(32) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL,
  `updated_at` datetime(6) NOT NULL,
  `published_at` datetime(6) DEFAULT NULL,
  KEY `idx_documents_status` (`status`),
  KEY `idx_documents_author` (`author_id`),
  KEY `idx_documents_sponsor` (`sponsor_id`),
  CONSTRAINT `fk_documents_author` FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_documents_sponsor` FOREIGN KEY (`sponsor_id`) REFERENCES `sponsors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;