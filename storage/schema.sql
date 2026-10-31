-- MySQL schema for Kaya Paba admin portal
--
-- If InnoDB reports an orphaned tablespace for `users` (error 1813), stop MySQL,
-- remove the stale `users.ibd` and/or `users.frm` file from the MySQL data folder
-- under `kaya_portal`, restart MySQL, then rerun this schema or restart the app.

CREATE DATABASE IF NOT EXISTS `kaya_portal` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kaya_portal`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` varchar(32) NOT NULL PRIMARY KEY,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL,
  `notes` text,
  `is_council_member` tinyint(1) NOT NULL DEFAULT 0,
  `email_notifications_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(32) NOT NULL PRIMARY KEY,
  `title` varchar(255) NOT NULL,
  `desc` text,
  `date` date NOT NULL,
  `start` varchar(10) NOT NULL,
  `end` varchar(10) NOT NULL,
  `location` varchar(255),
  `capacity` int NOT NULL DEFAULT 0,
  `status` varchar(50) NOT NULL DEFAULT 'scheduled',
  `minutes` text DEFAULT NULL,
  `minutes_recorded_at` datetime(6) DEFAULT NULL,
  `minutes_workflow_status` varchar(80) NOT NULL DEFAULT 'APPROVED MINUTES',
  `minutes_correction_notes` text DEFAULT NULL,
  `minutes_sent_at` datetime(6) DEFAULT NULL,
  `minutes_signed_by_id` varchar(32) DEFAULT NULL,
  `minutes_signed_by_name` varchar(255) DEFAULT NULL,
  `minutes_signed_at` datetime(6) DEFAULT NULL,
  `minutes_signature_hash` varchar(64) DEFAULT NULL,
  `minutes_archived_at` datetime(6) DEFAULT NULL,
  `minutes_archive_status` varchar(80) DEFAULT NULL,
  `council_attendance` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `agendas` (
  `id` varchar(32) NOT NULL PRIMARY KEY,
  `title` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` bigint unsigned NOT NULL,
  `file_type` varchar(255) NOT NULL,
  `session_id` varchar(32) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `source_legislative_id` varchar(32) DEFAULT NULL,
  `uploaded_at` datetime(6) NOT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` datetime(6) DEFAULT NULL,
  `approval_status` varchar(50) NOT NULL DEFAULT 'pending',
  `reading_stage` varchar(50) DEFAULT NULL,
  `sent_to_officer_id` text DEFAULT NULL,
  `sent_to_officer_date` datetime(6) DEFAULT NULL,
  `officer_approved` tinyint(1) NOT NULL DEFAULT 0,
  `approved_at` datetime(6) DEFAULT NULL,
  `officer_signature` longtext DEFAULT NULL,
  `officer_comments` text DEFAULT NULL,
  `approval_stage` varchar(50) DEFAULT 'ctrfb',
  `ctrfb_approved_at` datetime(6) DEFAULT NULL,
  `sent_to_proceeding_id` text DEFAULT NULL,
  `sent_to_proceeding_date` datetime(6) DEFAULT NULL,
  `proceeding_approved_at` datetime(6) DEFAULT NULL,
  `proceeding_signature` longtext DEFAULT NULL,
  `proceeding_comments` text DEFAULT NULL,
  `sent_to_council_at` datetime(6) DEFAULT NULL,
  `distribution_status` varchar(20) NOT NULL DEFAULT 'pending',
  `distributed_at` datetime(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `legislatives` (
  `id` varchar(32) NOT NULL PRIMARY KEY,
  `title` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `desc` text,
  `content` text,
  `ref` varchar(255),
  `createdById` varchar(32) NOT NULL,
  `createdByName` varchar(255) NOT NULL,
  `createdByRole` varchar(50) NOT NULL,
  `createdAt` datetime(6) NOT NULL,
  `updatedAt` datetime(6) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` int DEFAULT NULL,
  `file_type` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `scan_file_path` varchar(255) DEFAULT NULL,
  `documentHash` varchar(255) DEFAULT NULL,
  `lastModifiedById` varchar(32) DEFAULT NULL,
  `lastModifiedByName` varchar(255) DEFAULT NULL,
  `lastModifiedAt` datetime(6) DEFAULT NULL,
  `sent_to_user_ids` text DEFAULT NULL,
  `sent_by_id` varchar(32) DEFAULT NULL,
  `sent_by_name` varchar(255) DEFAULT NULL,
  `sent_at` datetime(6) DEFAULT NULL,
  `intake_classification` varchar(50) NOT NULL DEFAULT 'Legislative Matters',
  `routing_status` varchar(80) NOT NULL DEFAULT 'For Agenda',
  `stream_type` varchar(80) NOT NULL DEFAULT 'Approved Resolutions/Ordinances',
  `assigned_committee` varchar(255) DEFAULT NULL,
  `date_referred` date DEFAULT NULL,
  `committee_report_status` varchar(50) DEFAULT 'Pending',
  `hearing_date` date DEFAULT NULL,
  `lce_action` varchar(50) DEFAULT NULL,
  `lce_action_at` datetime(6) DEFAULT NULL,
  `lce_action_by_id` varchar(32) DEFAULT NULL,
  `archive_status` varchar(80) DEFAULT NULL,
  `workflow_status` varchar(80) NOT NULL DEFAULT 'Receiving',
  `control_number` varchar(40) DEFAULT NULL UNIQUE,
  `received_at` datetime(6) DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL,
  `originating_office` varchar(255) DEFAULT NULL,
  `assigned_to_id` varchar(32) DEFAULT NULL,
  `assigned_role` varchar(60) DEFAULT NULL,
  `signed_hash` char(64) DEFAULT NULL,
  `locked_at` datetime(6) DEFAULT NULL,
  `signature_verified_at` datetime(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `legislative_versions` (
  `id` varchar(32) NOT NULL PRIMARY KEY,
  `document_id` varchar(32) NOT NULL,
  `version_number` int unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `document_hash` char(64) NOT NULL,
  `status` varchar(60) NOT NULL,
  `created_by_id` varchar(32) DEFAULT NULL,
  `created_by_name` varchar(255) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL,
  UNIQUE KEY `uq_legislative_version` (`document_id`, `version_number`),
  KEY `idx_legislative_versions_document` (`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `legislative_audit_events` (
  `id` varchar(32) NOT NULL PRIMARY KEY,
  `document_id` varchar(32) NOT NULL,
  `event_type` varchar(60) NOT NULL,
  `details` text DEFAULT NULL,
  `user_id` varchar(32) DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `user_role` varchar(60) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL,
  KEY `idx_legislative_audit_document` (`document_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `legislative_signatures` (
  `id` varchar(32) NOT NULL PRIMARY KEY,
  `document_id` varchar(32) NOT NULL,
  `version_number` int unsigned NOT NULL,
  `signer_id` varchar(32) NOT NULL,
  `signer_name` varchar(255) NOT NULL,
  `signer_role` varchar(60) NOT NULL,
  `algorithm` varchar(40) NOT NULL,
  `signature` longtext NOT NULL,
  `certificate_pem` longtext DEFAULT NULL,
  `public_key_pem` longtext DEFAULT NULL,
  `signing_key_id` varchar(32) DEFAULT NULL,
  `certificate_fingerprint` char(64) NOT NULL,
  `document_hash` char(64) NOT NULL,
  `verified_at` datetime(6) NOT NULL,
  `created_at` datetime(6) NOT NULL,
  KEY `idx_legislative_signatures_document` (`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `legislative_signing_keys` (
  `id` varchar(32) NOT NULL PRIMARY KEY,
  `key_name` varchar(120) NOT NULL,
  `key_purpose` varchar(30) NOT NULL DEFAULT 'legislative',
  `algorithm` varchar(40) NOT NULL,
  `public_key_pem` longtext NOT NULL,
  `fingerprint` char(64) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `owner_user_id` varchar(32) DEFAULT NULL,
  `private_key_delivered_at` datetime(6) DEFAULT NULL,
  `created_by_id` varchar(32) DEFAULT NULL,
  `created_by_name` varchar(255) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL,
  `retired_at` datetime(6) DEFAULT NULL,
  KEY `idx_legislative_signing_keys_active` (`key_purpose`, `is_active`),
  KEY `idx_legislative_signing_keys_owner` (`owner_user_id`, `key_purpose`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `agenda_distributions` (
  `id` varchar(32) NOT NULL PRIMARY KEY,
  `agenda_id` varchar(32) NOT NULL,
  `target_user_id` varchar(32) NOT NULL,
  `sent_at` datetime(6) NOT NULL,
  `delivery_status` varchar(20) NOT NULL DEFAULT 'sent',
  `read_at` datetime(6) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  KEY `idx_agenda_distributions_agenda` (`agenda_id`),
  KEY `idx_agenda_distributions_target` (`target_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `agenda_pki_signatures` (
  `id` varchar(32) NOT NULL PRIMARY KEY,
  `agenda_id` varchar(32) NOT NULL,
  `version_number` int unsigned NOT NULL,
  `signer_id` varchar(32) NOT NULL,
  `signer_name` varchar(255) NOT NULL,
  `signer_role` varchar(60) NOT NULL,
  `algorithm` varchar(40) NOT NULL,
  `signature` longtext NOT NULL,
  `public_key_pem` longtext NOT NULL,
  `signing_key_id` varchar(32) NOT NULL,
  `key_fingerprint` char(64) NOT NULL,
  `document_hash` char(64) NOT NULL,
  `signed_at` datetime(6) NOT NULL,
  `verified_at` datetime(6) NOT NULL,
  `created_at` datetime(6) NOT NULL,
  UNIQUE KEY `uq_agenda_pki_version` (`agenda_id`, `version_number`),
  KEY `idx_agenda_pki_signatures_agenda` (`agenda_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
