USE `kaya_portal`;

ALTER TABLE `documents`
  ADD COLUMN IF NOT EXISTS `workflow_type` varchar(60) DEFAULT 'intake',
  ADD COLUMN IF NOT EXISTS `workflow_stage` varchar(100) DEFAULT 'RECEIVED',
  ADD COLUMN IF NOT EXISTS `intake_classification` varchar(60) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `metadata` longtext DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `committee_referrals` (
  `id` varchar(32) NOT NULL PRIMARY KEY, `document_id` varchar(32) NOT NULL,
  `committee` varchar(255) NOT NULL, `referred_at` datetime(6) NOT NULL,
  `hearing_at` datetime(6) DEFAULT NULL, `status` varchar(60) NOT NULL DEFAULT 'REFERRED',
  `notes` text DEFAULT NULL, `created_by_id` varchar(32) DEFAULT NULL, `created_at` datetime(6) NOT NULL,
  KEY `idx_committee_referrals_document` (`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `committee_reports` (
  `id` varchar(32) NOT NULL PRIMARY KEY, `referral_id` varchar(32) NOT NULL,
  `report_text` longtext NOT NULL, `status` varchar(60) NOT NULL DEFAULT 'DRAFT',
  `reported_at` datetime(6) DEFAULT NULL, `created_by_id` varchar(32) DEFAULT NULL, `created_at` datetime(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `awards` (
  `id` varchar(32) NOT NULL PRIMARY KEY, `document_id` varchar(32) DEFAULT NULL,
  `award_type` varchar(60) NOT NULL, `recipient_name` varchar(255) NOT NULL,
  `recipient_details` text DEFAULT NULL, `certificate_path` varchar(255) DEFAULT NULL,
  `issued_at` datetime(6) NOT NULL, `issued_by_id` varchar(32) DEFAULT NULL, `created_at` datetime(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `document_signatures` (
  `id` varchar(32) NOT NULL PRIMARY KEY, `document_id` varchar(32) NOT NULL,
  `signer_role` varchar(60) NOT NULL, `signer_id` varchar(32) NOT NULL,
  `signature` longtext DEFAULT NULL, `signed_at` datetime(6) NOT NULL,
  UNIQUE KEY `uq_document_signer_role` (`document_id`, `signer_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `posting_certificates` (
  `id` varchar(32) NOT NULL PRIMARY KEY, `document_id` varchar(32) NOT NULL,
  `certificate_number` varchar(80) NOT NULL, `posted_at` datetime(6) NOT NULL,
  `posted_by_id` varchar(32) DEFAULT NULL, `filed_at` datetime(6) DEFAULT NULL, `created_at` datetime(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

