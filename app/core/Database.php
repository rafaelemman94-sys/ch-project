<?php
namespace App\Core;

use App\Services\SigningKeyStorage;
use PDO;
use PDOException;

class Database
{
    private PDO $pdo;

    public function __construct()
    {
        $config = require __DIR__ . '/../config.php';
        $dbConfig = $config['db'] ?? [];

        if (($dbConfig['driver'] ?? '') !== 'mysql') {
            throw new \RuntimeException('Only MySQL driver is supported in this conversion.');
        }

        $host = $dbConfig['host'] ?? '127.0.0.1';
        $port = (int)($dbConfig['port'] ?? 3306);
        $dbname = $dbConfig['name'] ?? 'kaya_portal';
        $user = $dbConfig['user'] ?? 'root';
        $pass = $dbConfig['pass'] ?? '';
        $charset = $dbConfig['charset'] ?? 'utf8mb4';

        $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $host, $port, $charset);
        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $this->pdo->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE %s_unicode_ci', $dbname, $charset, $charset));
        $this->pdo->exec(sprintf('USE `%s`', $dbname));

        $schemaMarker = __DIR__ . '/../../storage/.schema-ready-v4';
        if (!is_file($schemaMarker)) {
            $this->initializeSchema();
            $this->migrateJsonData();
            @touch($schemaMarker);
        }

        $this->ensureLegislativeAiColumns();
        $this->ensureSessionMinutesColumns();

        $presidingOfficerRoleMarker = __DIR__ . '/../../storage/.presiding-officer-role-v1';
        if (!is_file($presidingOfficerRoleMarker)) {
            $this->pdo->exec("UPDATE `users` SET `role` = 'presiding officer' WHERE `role` = 'proceeding officer'");
            @touch($presidingOfficerRoleMarker);
        }

        $this->executeSchemaStatement(
            'CREATE TABLE IF NOT EXISTS `attendance_requests` (
                `id` varchar(32) NOT NULL PRIMARY KEY,
                `session_id` varchar(32) NOT NULL,
                `user_id` varchar(32) NOT NULL,
                `device_id` varchar(128) DEFAULT NULL,
                `status` varchar(20) NOT NULL DEFAULT "pending",
                `requested_at` datetime(6) NOT NULL,
                `decided_at` datetime(6) DEFAULT NULL,
                `decided_by` varchar(32) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
            'attendance_requests'
        );

        $this->executeSchemaStatement(
            'CREATE TABLE IF NOT EXISTS `ctfrb_reports` (
                `id` varchar(32) NOT NULL PRIMARY KEY,
                `applicant_name` varchar(255) NOT NULL,
                `address` varchar(255) NOT NULL,
                `franchise_number` varchar(100) NOT NULL,
                `application_type` varchar(50) NOT NULL,
                `created_at` datetime(6) NOT NULL,
                `updated_at` datetime(6) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
            'ctfrb_reports'
        );

        $this->ensureWorkflowNodeTables();
        $this->ensureLegislativeRecordTables();
        $this->migrateSigningPrivateKeysToStorage();
        $this->removeBlockchainStorage();
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    private function ensureSessionMinutesColumns(): void
    {
        $existingColumns = $this->pdo->query(
            'SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS`
             WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = "sessions"'
        )->fetchAll(PDO::FETCH_COLUMN);
        $existingColumns = array_fill_keys($existingColumns, true);

        $columns = [
            'minutes' => 'TEXT DEFAULT NULL',
            'minutes_recorded_at' => 'DATETIME(6) DEFAULT NULL',
            'minutes_workflow_status' => "VARCHAR(80) NOT NULL DEFAULT 'APPROVED MINUTES'",
            'minutes_correction_notes' => 'TEXT DEFAULT NULL',
            'minutes_sent_at' => 'DATETIME(6) DEFAULT NULL',
            'minutes_signed_by_id' => 'VARCHAR(32) DEFAULT NULL',
            'minutes_signed_by_name' => 'VARCHAR(255) DEFAULT NULL',
            'minutes_signed_at' => 'DATETIME(6) DEFAULT NULL',
            'minutes_signature_hash' => 'VARCHAR(64) DEFAULT NULL',
            'minutes_archived_at' => 'DATETIME(6) DEFAULT NULL',
            'minutes_archive_status' => 'VARCHAR(80) DEFAULT NULL',
            'council_attendance' => 'TEXT DEFAULT NULL',
        ];
        foreach ($columns as $column => $definition) {
            if (isset($existingColumns[$column])) {
                continue;
            }
            $this->pdo->exec("ALTER TABLE `sessions` ADD COLUMN `$column` $definition");
        }
    }

    private function migrateSigningPrivateKeysToStorage(): void
    {
        $column = $this->pdo->query("SHOW COLUMNS FROM `legislative_signing_keys` LIKE 'encrypted_private_key_pem'")->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            return;
        }

        $keys = $this->pdo->query('SELECT `id`, `encrypted_private_key_pem` FROM `legislative_signing_keys`')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($keys as $key) {
            $privateKeyPem = (string)($key['encrypted_private_key_pem'] ?? '');
            if ($privateKeyPem !== '') {
                SigningKeyStorage::store((string)$key['id'], $privateKeyPem);
            }
        }

        $this->pdo->exec('ALTER TABLE `legislative_signing_keys` DROP COLUMN `encrypted_private_key_pem`');
    }

    private function ensureWorkflowNodeTables(): void
    {
        $this->executeSchemaStatement(
            'CREATE TABLE IF NOT EXISTS `documents` (
                `id` varchar(32) NOT NULL PRIMARY KEY,
                `title` varchar(255) NOT NULL,
                `content` longtext NOT NULL,
                `author_id` varchar(32) DEFAULT NULL,
                `author_name` varchar(255) DEFAULT NULL,
                `sponsor_id` varchar(32) DEFAULT NULL,
                `sponsor_name` varchar(255) DEFAULT NULL,
                `status` varchar(20) NOT NULL DEFAULT "draft",
                `version` varchar(20) NOT NULL DEFAULT "1.0",
                `file_name` varchar(255) DEFAULT NULL,
                `file_path` varchar(255) DEFAULT NULL,
                `file_type` varchar(100) DEFAULT NULL,
                `file_size` int unsigned DEFAULT NULL,
                `created_by_id` varchar(32) DEFAULT NULL,
                `updated_by_id` varchar(32) DEFAULT NULL,
                `created_at` datetime(6) NOT NULL,
                `updated_at` datetime(6) NOT NULL,
                `published_at` datetime(6) DEFAULT NULL,
                `workflow_type` varchar(60) DEFAULT "intake",
                `workflow_stage` varchar(100) DEFAULT "RECEIVED",
                `intake_classification` varchar(60) DEFAULT NULL,
                `metadata` longtext DEFAULT NULL,
                KEY `idx_documents_workflow_stage` (`workflow_stage`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'documents'
        );
        foreach ([
            'ALTER TABLE `documents` ADD COLUMN IF NOT EXISTS `workflow_type` VARCHAR(60) DEFAULT \'intake\'',
            'ALTER TABLE `documents` ADD COLUMN IF NOT EXISTS `workflow_stage` VARCHAR(100) DEFAULT \'RECEIVED\'',
            'ALTER TABLE `documents` ADD COLUMN IF NOT EXISTS `intake_classification` VARCHAR(60) DEFAULT NULL',
            'ALTER TABLE `documents` ADD COLUMN IF NOT EXISTS `metadata` LONGTEXT DEFAULT NULL',
        ] as $statement) {
            try {
                $this->pdo->exec($statement);
            } catch (PDOException $ignored) {
            }
        }

        $tables = [
            'committee_referrals' => 'CREATE TABLE IF NOT EXISTS `committee_referrals` (`id` varchar(32) NOT NULL PRIMARY KEY, `document_id` varchar(32) NOT NULL, `committee` varchar(255) NOT NULL, `referred_at` datetime(6) NOT NULL, `hearing_at` datetime(6) DEFAULT NULL, `status` varchar(60) NOT NULL DEFAULT "REFERRED", `notes` text DEFAULT NULL, `created_by_id` varchar(32) DEFAULT NULL, `created_at` datetime(6) NOT NULL, KEY `idx_committee_referrals_document` (`document_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'committee_reports' => 'CREATE TABLE IF NOT EXISTS `committee_reports` (`id` varchar(32) NOT NULL PRIMARY KEY, `referral_id` varchar(32) NOT NULL, `report_text` longtext NOT NULL, `status` varchar(60) NOT NULL DEFAULT "DRAFT", `reported_at` datetime(6) DEFAULT NULL, `created_by_id` varchar(32) DEFAULT NULL, `created_at` datetime(6) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'awards' => 'CREATE TABLE IF NOT EXISTS `awards` (`id` varchar(32) NOT NULL PRIMARY KEY, `document_id` varchar(32) DEFAULT NULL, `award_type` varchar(60) NOT NULL, `recipient_name` varchar(255) NOT NULL, `recipient_details` text DEFAULT NULL, `certificate_path` varchar(255) DEFAULT NULL, `issued_at` datetime(6) NOT NULL, `issued_by_id` varchar(32) DEFAULT NULL, `created_at` datetime(6) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'document_signatures' => 'CREATE TABLE IF NOT EXISTS `document_signatures` (`id` varchar(32) NOT NULL PRIMARY KEY, `document_id` varchar(32) NOT NULL, `signer_role` varchar(60) NOT NULL, `signer_id` varchar(32) NOT NULL, `signature` longtext DEFAULT NULL, `signed_at` datetime(6) NOT NULL, UNIQUE KEY `uq_document_signer_role` (`document_id`, `signer_role`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'posting_certificates' => 'CREATE TABLE IF NOT EXISTS `posting_certificates` (`id` varchar(32) NOT NULL PRIMARY KEY, `document_id` varchar(32) NOT NULL, `certificate_number` varchar(80) NOT NULL, `posted_at` datetime(6) NOT NULL, `posted_by_id` varchar(32) DEFAULT NULL, `filed_at` datetime(6) DEFAULT NULL, `created_at` datetime(6) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'agenda_pki_signatures' => 'CREATE TABLE IF NOT EXISTS `agenda_pki_signatures` (`id` varchar(32) NOT NULL PRIMARY KEY, `agenda_id` varchar(32) NOT NULL, `version_number` int unsigned NOT NULL, `signer_id` varchar(32) NOT NULL, `signer_name` varchar(255) NOT NULL, `signer_role` varchar(60) NOT NULL, `algorithm` varchar(40) NOT NULL, `signature` longtext NOT NULL, `public_key_pem` longtext NOT NULL, `signing_key_id` varchar(32) NOT NULL, `key_fingerprint` char(64) NOT NULL, `document_hash` char(64) NOT NULL, `signed_at` datetime(6) NOT NULL, `verified_at` datetime(6) NOT NULL, `created_at` datetime(6) NOT NULL, UNIQUE KEY `uq_agenda_pki_version` (`agenda_id`, `version_number`), KEY `idx_agenda_pki_signatures_agenda` (`agenda_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        ];
        foreach ($tables as $table => $statement) {
            $this->executeSchemaStatement($statement, $table);
        }
    }

    private function ensureLegislativeRecordTables(): void
    {
        foreach ([
            'control_number' => 'VARCHAR(40) DEFAULT NULL',
            'received_at' => 'DATETIME(6) DEFAULT NULL',
            'origin' => 'VARCHAR(255) DEFAULT NULL',
            'originating_office' => 'VARCHAR(255) DEFAULT NULL',
            'assigned_to_id' => 'VARCHAR(32) DEFAULT NULL',
            'assigned_role' => 'VARCHAR(60) DEFAULT NULL',
            'signed_hash' => 'CHAR(64) DEFAULT NULL',
            'locked_at' => 'DATETIME(6) DEFAULT NULL',
            'signature_verified_at' => 'DATETIME(6) DEFAULT NULL',
            'scan_file_path' => 'VARCHAR(255) DEFAULT NULL',
        ] as $column => $definition) {
            try {
                $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `$column` $definition");
            } catch (PDOException $ignored) {
            }
        }
        try {
            $this->pdo->exec('ALTER TABLE `legislatives` ADD UNIQUE KEY `uq_legislatives_control_number` (`control_number`)');
        } catch (PDOException $ignored) {
        }

        $tables = [
            'legislative_versions' => 'CREATE TABLE IF NOT EXISTS `legislative_versions` (`id` varchar(32) NOT NULL PRIMARY KEY, `document_id` varchar(32) NOT NULL, `version_number` int unsigned NOT NULL, `title` varchar(255) NOT NULL, `content` longtext NOT NULL, `file_name` varchar(255) DEFAULT NULL, `file_path` varchar(255) DEFAULT NULL, `document_hash` char(64) NOT NULL, `status` varchar(60) NOT NULL, `created_by_id` varchar(32) DEFAULT NULL, `created_by_name` varchar(255) DEFAULT NULL, `created_at` datetime(6) NOT NULL, UNIQUE KEY `uq_legislative_version` (`document_id`, `version_number`), KEY `idx_legislative_versions_document` (`document_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'legislative_audit_events' => 'CREATE TABLE IF NOT EXISTS `legislative_audit_events` (`id` varchar(32) NOT NULL PRIMARY KEY, `document_id` varchar(32) NOT NULL, `event_type` varchar(60) NOT NULL, `details` text DEFAULT NULL, `user_id` varchar(32) DEFAULT NULL, `user_name` varchar(255) DEFAULT NULL, `user_role` varchar(60) DEFAULT NULL, `created_at` datetime(6) NOT NULL, KEY `idx_legislative_audit_document` (`document_id`, `created_at`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'legislative_signatures' => 'CREATE TABLE IF NOT EXISTS `legislative_signatures` (`id` varchar(32) NOT NULL PRIMARY KEY, `document_id` varchar(32) NOT NULL, `version_number` int unsigned NOT NULL, `signer_id` varchar(32) NOT NULL, `signer_name` varchar(255) NOT NULL, `signer_role` varchar(60) NOT NULL, `algorithm` varchar(40) NOT NULL, `signature` longtext NOT NULL, `certificate_pem` longtext DEFAULT NULL, `public_key_pem` longtext DEFAULT NULL, `signing_key_id` varchar(32) DEFAULT NULL, `certificate_fingerprint` char(64) NOT NULL, `document_hash` char(64) NOT NULL, `verified_at` datetime(6) NOT NULL, `created_at` datetime(6) NOT NULL, KEY `idx_legislative_signatures_document` (`document_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'legislative_signing_keys' => 'CREATE TABLE IF NOT EXISTS `legislative_signing_keys` (`id` varchar(32) NOT NULL PRIMARY KEY, `key_name` varchar(120) NOT NULL, `key_purpose` varchar(30) NOT NULL DEFAULT "legislative", `algorithm` varchar(40) NOT NULL, `public_key_pem` longtext NOT NULL, `fingerprint` char(64) NOT NULL, `is_active` tinyint(1) NOT NULL DEFAULT 1, `owner_user_id` varchar(32) DEFAULT NULL, `private_key_delivered_at` datetime(6) DEFAULT NULL, `created_by_id` varchar(32) DEFAULT NULL, `created_by_name` varchar(255) DEFAULT NULL, `created_at` datetime(6) NOT NULL, `retired_at` datetime(6) DEFAULT NULL, KEY `idx_legislative_signing_keys_active` (`key_purpose`, `is_active`), KEY `idx_legislative_signing_keys_owner` (`owner_user_id`, `key_purpose`, `is_active`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        ];
        foreach ($tables as $table => $statement) {
            $this->executeSchemaStatement($statement, $table);
        }
        $versionIndex = $this->pdo->prepare(
            'SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`STATISTICS`
             WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = :table AND `INDEX_NAME` = :index
             ORDER BY `SEQ_IN_INDEX`'
        );
        $versionIndex->execute(['table' => 'legislative_versions', 'index' => 'uq_legislative_version']);
        $versionIndexColumns = $versionIndex->fetchAll(PDO::FETCH_COLUMN);
        if ($versionIndexColumns !== ['document_id', 'version_number']) {
            if ($versionIndexColumns !== []) {
                $this->pdo->exec('ALTER TABLE `legislative_versions` DROP INDEX `uq_legislative_version`');
            }
            $this->pdo->exec('ALTER TABLE `legislative_versions` ADD UNIQUE KEY `uq_legislative_version` (`document_id`, `version_number`)');
        }
        try {
            $this->pdo->exec("ALTER TABLE `legislative_signing_keys` ADD COLUMN IF NOT EXISTS `key_purpose` VARCHAR(30) NOT NULL DEFAULT 'legislative'");
        } catch (PDOException $ignored) {
        }
        foreach ([
            'owner_user_id' => 'VARCHAR(32) DEFAULT NULL',
            'private_key_delivered_at' => 'DATETIME(6) DEFAULT NULL',
        ] as $column => $definition) {
            try {
                $this->pdo->exec("ALTER TABLE `legislative_signing_keys` ADD COLUMN IF NOT EXISTS `$column` $definition");
            } catch (PDOException $ignored) {
            }
        }
        try {
            $this->pdo->exec('ALTER TABLE `legislative_signatures` ADD COLUMN IF NOT EXISTS `certificate_pem` LONGTEXT DEFAULT NULL');
        } catch (PDOException $ignored) {
        }
        try {
            $this->pdo->exec('ALTER TABLE `legislative_signatures` ADD COLUMN IF NOT EXISTS `public_key_pem` LONGTEXT DEFAULT NULL');
        } catch (PDOException $ignored) {
        }
        try {
            $this->pdo->exec('ALTER TABLE `legislative_signatures` ADD COLUMN IF NOT EXISTS `signing_key_id` VARCHAR(32) DEFAULT NULL');
        } catch (PDOException $ignored) {
        }
        try {
            $this->pdo->exec("UPDATE `legislatives` SET `control_number` = CONCAT('LEG-', YEAR(`createdAt`), '-', UPPER(LEFT(`id`, 8))) WHERE `control_number` IS NULL OR `control_number` = ''");
            $this->pdo->exec('UPDATE `legislatives` SET `received_at` = `createdAt` WHERE `received_at` IS NULL');
            $this->pdo->exec("UPDATE `legislatives` SET `originating_office` = 'Secretariat' WHERE `originating_office` IS NULL OR `originating_office` = ''");
            $this->pdo->exec("UPDATE `legislatives` SET `assigned_role` = 'Secretariat' WHERE `assigned_role` IS NULL OR `assigned_role` = ''");
            $this->pdo->exec("UPDATE `legislatives` SET `workflow_status` = CASE UPPER(`workflow_status`) WHEN 'EDITING' THEN 'Receiving' WHEN 'RECEIVING' THEN 'Receiving' WHEN 'UNDER_REVIEW' THEN 'Review' WHEN 'UNDER REVIEW' THEN 'Review' WHEN 'FOR_SESSION' THEN 'SP Session' WHEN 'FOR SESSION' THEN 'SP Session' WHEN 'FOR_REVISION' THEN 'Final Draft' WHEN 'FOR REVISION' THEN 'Final Draft' WHEN 'FOR_APPROVAL' THEN 'Approval' WHEN 'FOR APPROVAL' THEN 'Approval' WHEN 'FOR_SIGNATURE' THEN 'Signatures' WHEN 'FOR SIGNATURE' THEN 'Signatures' WHEN 'CORRECT' THEN 'Final Draft' WHEN 'FINAL DRAFT' THEN 'Final Draft' WHEN 'FOR_AGENDA' THEN 'Agenda' WHEN 'FOR AGENDA' THEN 'Agenda' WHEN 'REFER TO APPROPRIATE COMMITTEES' THEN 'Committee/Referral' WHEN 'COMMITTEE HEARING' THEN 'Committee/Referral' WHEN 'COMMITTEE REPORT' THEN 'Committee/Referral' WHEN 'COMMITTEE' THEN 'Committee/Referral' WHEN 'VETOED' THEN 'Final Draft' WHEN 'ARCHIVED' THEN 'Archive' WHEN 'EXTERNAL DRIVE' THEN 'Filing/Scan' WHEN 'SCAN TO PDF' THEN 'Filing/Scan' WHEN 'FILE' THEN 'Filing/Scan' WHEN 'FURNISH COPIES' THEN 'Filing/Scan' WHEN 'FILE LITERAL COPIES' THEN 'Filing/Scan' WHEN 'PRINTED (6 COPIES)' THEN 'Filing/Scan' WHEN 'PRINTED (1 COPY)' THEN 'Filing/Scan' WHEN 'FOR SIGNATURE SEC/P.O.' THEN 'Signatures' WHEN 'FOR SIGNATURE SEC/COUNCIL/P.O.' THEN 'Signatures' WHEN 'SIGNED' THEN 'LCE Approval' WHEN 'RECEIVED' THEN 'Receiving' ELSE `workflow_status` END");
            $this->pdo->exec("UPDATE `legislatives` SET `workflow_status` = 'Signatures' WHERE `workflow_status` IN ('LCE Approval', 'Archive') AND (`signed_hash` IS NULL OR `locked_at` IS NULL)");
        } catch (PDOException $ignored) {
        }
    }

    private function removeBlockchainStorage(): void
    {
        try {
            $this->pdo->exec('DROP TABLE IF EXISTS `ronin_transactions`');
        } catch (PDOException $ignored) {
        }
        foreach (['blockchainHash', 'previousHash', 'lastHash'] as $column) {
            try {
                $this->pdo->exec("ALTER TABLE `legislatives` DROP COLUMN IF EXISTS `$column`");
            } catch (PDOException $ignored) {
            }
        }
    }

    private function ensureLegislativeAiColumns(): void
    {
        foreach ([
            'author_name' => 'VARCHAR(255) DEFAULT NULL',
            'sponsor_name' => 'VARCHAR(255) DEFAULT NULL',
            'ai_summary' => 'TEXT DEFAULT NULL',
            'workflow_status' => "VARCHAR(80) NOT NULL DEFAULT 'EDITING'",
            'admin_decision_notes' => 'TEXT DEFAULT NULL',
            'admin_decision_at' => 'DATETIME(6) DEFAULT NULL',
        ] as $column => $definition) {
            try {
                $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `$column` $definition");
            } catch (PDOException $e) {
            }
        }
    }

    private function initializeSchema(): void
    {
        $this->executeSchemaStatement(
            'CREATE TABLE IF NOT EXISTS `users` (
                `id` varchar(32) NOT NULL PRIMARY KEY,
                `name` varchar(255) NOT NULL,
                `email` varchar(255) NOT NULL UNIQUE,
                `password` varchar(255) NOT NULL,
                `role` varchar(50) NOT NULL,
                `status` varchar(50) NOT NULL,
                `notes` text,
                `notifications` text DEFAULT NULL,
                `created_at` datetime(6) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
            'users'
        );

        $this->executeSchemaStatement(
            'CREATE TABLE IF NOT EXISTS `sessions` (
                `id` varchar(32) NOT NULL PRIMARY KEY,
                `title` varchar(255) NOT NULL,
                `desc` text,
                `date` date NOT NULL,
                `start` varchar(10) NOT NULL,
                `end` varchar(10) NOT NULL,
                `location` varchar(255),
                `capacity` int NOT NULL DEFAULT 0,
                `status` varchar(50) NOT NULL DEFAULT "scheduled",
                `minutes` text DEFAULT NULL,
                `minutes_recorded_at` datetime(6) DEFAULT NULL,
                `minutes_workflow_status` varchar(80) NOT NULL DEFAULT "APPROVED MINUTES",
                `minutes_correction_notes` text DEFAULT NULL,
                `minutes_sent_at` datetime(6) DEFAULT NULL,
                `minutes_signed_by_id` varchar(32) DEFAULT NULL,
                `minutes_signed_by_name` varchar(255) DEFAULT NULL,
                `minutes_signed_at` datetime(6) DEFAULT NULL,
                `minutes_signature_hash` varchar(64) DEFAULT NULL,
                `minutes_archived_at` datetime(6) DEFAULT NULL,
                `minutes_archive_status` varchar(80) DEFAULT NULL,
                `council_attendance` text DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
            'sessions'
        );

        $this->executeSchemaStatement(
            "CREATE TABLE IF NOT EXISTS `agendas` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            'agendas'
        );

        $this->executeSchemaStatement(
            'CREATE TABLE IF NOT EXISTS `delivery_history` (
                `id` varchar(32) NOT NULL PRIMARY KEY,
                `item_type` varchar(30) NOT NULL,
                `item_id` varchar(32) NOT NULL,
                `item_title` varchar(255) NOT NULL,
                `session_id` varchar(32) DEFAULT NULL,
                `action` varchar(50) NOT NULL,
                `recipient_ids` text DEFAULT NULL,
                `recipient_names` text DEFAULT NULL,
                `sent_by_id` varchar(32) DEFAULT NULL,
                `sent_by_name` varchar(255) DEFAULT NULL,
                `notes` text DEFAULT NULL,
                `template_id` varchar(32) DEFAULT NULL,
                `template_name` varchar(255) DEFAULT NULL,
                `template_type` varchar(50) DEFAULT NULL,
                `created_at` datetime(6) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
            'delivery_history'
        );

        $this->executeSchemaStatement(
            'CREATE TABLE IF NOT EXISTS `agenda_distributions` (
                `id` varchar(32) NOT NULL PRIMARY KEY,
                `agenda_id` varchar(32) NOT NULL,
                `target_user_id` varchar(32) NOT NULL,
                `sent_at` datetime(6) NOT NULL,
                `delivery_status` varchar(20) NOT NULL DEFAULT "sent",
                `read_at` datetime(6) DEFAULT NULL,
                `file_path` varchar(255) DEFAULT NULL,
                KEY `idx_agenda_distributions_agenda` (`agenda_id`),
                KEY `idx_agenda_distributions_target` (`target_user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
            'agenda_distributions'
        );

        $this->executeSchemaStatement(
            'CREATE TABLE IF NOT EXISTS `delivery_templates` (
                `id` varchar(32) NOT NULL PRIMARY KEY,
                `name` varchar(255) NOT NULL,
                `file_name` varchar(255) NOT NULL,
                `file_path` varchar(255) NOT NULL,
                `template_type` varchar(50) NOT NULL DEFAULT "general",
                `uploaded_by_id` varchar(32) DEFAULT NULL,
                `uploaded_by_name` varchar(255) DEFAULT NULL,
                `notes` text DEFAULT NULL,
                `created_at` datetime(6) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
            'delivery_templates'
        );

        // Ensure existing `agendas` table has new columns for published state and approval workflow
        try {
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `published` TINYINT(1) NOT NULL DEFAULT 0");
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `published_at` DATETIME(6) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `approval_status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `reading_stage` VARCHAR(50) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `sent_to_officer_id` VARCHAR(32) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `sent_to_officer_date` DATETIME(6) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `officer_approved` TINYINT(1) NOT NULL DEFAULT 0");
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `approved_at` DATETIME(6) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `officer_signature` LONGTEXT DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `officer_comments` TEXT DEFAULT NULL");
            // Multi-stage approval workflow columns
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `approval_stage` VARCHAR(50) DEFAULT 'ctrfb'");
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `ctrfb_approved_at` DATETIME(6) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `ctrfb_signature` LONGTEXT DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `ctrfb_comments` TEXT DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `sent_to_proceeding_id` VARCHAR(32) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `sent_to_proceeding_date` DATETIME(6) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `proceeding_approved_at` DATETIME(6) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `proceeding_signature` LONGTEXT DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `proceeding_comments` TEXT DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `sent_to_council_at` DATETIME(6) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `distribution_status` VARCHAR(20) NOT NULL DEFAULT 'pending'");
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `distributed_at` DATETIME(6) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `agendas` ADD COLUMN IF NOT EXISTS `source_legislative_id` VARCHAR(32) DEFAULT NULL");

            // Ensure recipient ID columns can store JSON arrays of multiple IDs
            $this->pdo->exec("ALTER TABLE `agendas` MODIFY `sent_to_officer_id` TEXT DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `agendas` MODIFY `sent_to_proceeding_id` TEXT DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `agendas` MODIFY `file_size` BIGINT UNSIGNED NOT NULL");
            $this->pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `notifications` TEXT DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `is_council_member` TINYINT(1) NOT NULL DEFAULT 0");
            $this->pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `email_notifications_enabled` TINYINT(1) NOT NULL DEFAULT 1");
            $this->pdo->exec("ALTER TABLE `sessions` ADD COLUMN IF NOT EXISTS `minutes` TEXT DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `sessions` ADD COLUMN IF NOT EXISTS `minutes_recorded_at` DATETIME(6) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `sessions` ADD COLUMN IF NOT EXISTS `minutes_workflow_status` VARCHAR(80) NOT NULL DEFAULT 'APPROVED MINUTES'");
            $this->pdo->exec("ALTER TABLE `sessions` ADD COLUMN IF NOT EXISTS `minutes_correction_notes` TEXT DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `sessions` ADD COLUMN IF NOT EXISTS `minutes_sent_at` DATETIME(6) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `sessions` ADD COLUMN IF NOT EXISTS `minutes_signed_by_id` VARCHAR(32) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `sessions` ADD COLUMN IF NOT EXISTS `minutes_signed_by_name` VARCHAR(255) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `sessions` ADD COLUMN IF NOT EXISTS `minutes_signed_at` DATETIME(6) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `sessions` ADD COLUMN IF NOT EXISTS `minutes_signature_hash` VARCHAR(64) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `sessions` ADD COLUMN IF NOT EXISTS `minutes_archived_at` DATETIME(6) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `sessions` ADD COLUMN IF NOT EXISTS `minutes_archive_status` VARCHAR(80) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `sessions` ADD COLUMN IF NOT EXISTS `council_attendance` TEXT DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `delivery_history` ADD COLUMN IF NOT EXISTS `template_id` VARCHAR(32) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `delivery_history` ADD COLUMN IF NOT EXISTS `template_name` VARCHAR(255) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `delivery_history` ADD COLUMN IF NOT EXISTS `template_type` VARCHAR(50) DEFAULT NULL");
        } catch (\PDOException $e) {
            // ignore if ALTER not supported on older MySQL versions
        }

        // Ensure existing `legislatives` table has public publication tracking
        try {
            $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `published_to_public` TINYINT(1) NOT NULL DEFAULT 0");
            $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `published_to_public_at` DATETIME(6) DEFAULT NULL");
                $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `sent_to_user_ids` TEXT DEFAULT NULL");
                $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `sent_by_id` VARCHAR(32) DEFAULT NULL");
                $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `sent_by_name` VARCHAR(255) DEFAULT NULL");
                $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `sent_at` DATETIME(6) DEFAULT NULL");
                $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `fileRevisionHash` VARCHAR(255) DEFAULT NULL");
                $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `fileDiffSummary` TEXT DEFAULT NULL");
                $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `fileChangeLog` TEXT DEFAULT NULL");
                $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `intake_classification` VARCHAR(50) NOT NULL DEFAULT 'Legislative Matters'");
                $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `routing_status` VARCHAR(80) NOT NULL DEFAULT 'For Agenda'");
                $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `stream_type` VARCHAR(80) NOT NULL DEFAULT 'Approved Resolutions/Ordinances'");
                $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `assigned_committee` VARCHAR(255) DEFAULT NULL");
                $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `date_referred` DATE DEFAULT NULL");
                $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `committee_report_status` VARCHAR(50) DEFAULT 'Pending'");
                $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `hearing_date` DATE DEFAULT NULL");
                $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `lce_action` VARCHAR(50) DEFAULT NULL");
                $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `lce_action_at` DATETIME(6) DEFAULT NULL");
                $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `lce_action_by_id` VARCHAR(32) DEFAULT NULL");
                $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `archive_status` VARCHAR(80) DEFAULT NULL");
                $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `workflow_status` VARCHAR(80) NOT NULL DEFAULT 'EDITING'");
        } catch (\PDOException $e) {
            // ignore if ALTER not supported on older MySQL versions
        }

        // Ensure legislative attachment fields exist for uploaded files
        try {
            $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `file_name` VARCHAR(255) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `file_size` INT DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `file_type` VARCHAR(255) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `file_path` VARCHAR(255) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `author_name` VARCHAR(255) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `sponsor_name` VARCHAR(255) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE `legislatives` ADD COLUMN IF NOT EXISTS `ai_summary` TEXT DEFAULT NULL");
        } catch (\PDOException $e) {
            // ignore if ALTER not supported on older MySQL versions
        }

        $this->executeSchemaStatement(
            'CREATE TABLE IF NOT EXISTS `legislatives` (
                `id` varchar(32) NOT NULL PRIMARY KEY,
                `title` varchar(255) NOT NULL,
                `type` varchar(100) NOT NULL,
                `author_name` varchar(255) DEFAULT NULL,
                `sponsor_name` varchar(255) DEFAULT NULL,
                `ai_summary` text DEFAULT NULL,
                `status` varchar(50) NOT NULL DEFAULT "draft",
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
                `fileRevisionHash` varchar(255) DEFAULT NULL,
                `fileDiffSummary` text DEFAULT NULL,
                `fileChangeLog` text DEFAULT NULL,
                `documentHash` varchar(255) DEFAULT NULL,
                `lastModifiedById` varchar(32) DEFAULT NULL,
                `lastModifiedByName` varchar(255) DEFAULT NULL,
                `lastModifiedAt` datetime(6) DEFAULT NULL,
                `sent_to_user_ids` text DEFAULT NULL,
                `sent_by_id` varchar(32) DEFAULT NULL,
                `sent_by_name` varchar(255) DEFAULT NULL,
                `sent_at` datetime(6) DEFAULT NULL,
                `intake_classification` varchar(50) NOT NULL DEFAULT "Legislative Matters",
                `routing_status` varchar(80) NOT NULL DEFAULT "For Agenda",
                `stream_type` varchar(80) NOT NULL DEFAULT "Approved Resolutions/Ordinances",
                `assigned_committee` varchar(255) DEFAULT NULL,
                `date_referred` date DEFAULT NULL,
                `committee_report_status` varchar(50) DEFAULT "Pending",
                `hearing_date` date DEFAULT NULL,
                `lce_action` varchar(50) DEFAULT NULL,
                `lce_action_at` datetime(6) DEFAULT NULL,
                `lce_action_by_id` varchar(32) DEFAULT NULL,
                `archive_status` varchar(80) DEFAULT NULL,
                `workflow_status` varchar(80) NOT NULL DEFAULT "Receiving",
                `control_number` varchar(40) DEFAULT NULL,
                `received_at` datetime(6) DEFAULT NULL,
                `origin` varchar(255) DEFAULT NULL,
                `originating_office` varchar(255) DEFAULT NULL,
                `assigned_to_id` varchar(32) DEFAULT NULL,
                `assigned_role` varchar(60) DEFAULT NULL,
                `signed_hash` char(64) DEFAULT NULL,
                `locked_at` datetime(6) DEFAULT NULL,
                `signature_verified_at` datetime(6) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
            'legislatives'
        );

        // Votes table for live voting
        $this->executeSchemaStatement(
            'CREATE TABLE IF NOT EXISTS `votes` (
                `id` varchar(32) NOT NULL PRIMARY KEY,
                `agenda_id` varchar(32) DEFAULT NULL,
                `session_id` varchar(32) DEFAULT NULL,
                `user_id` varchar(32) NOT NULL,
                `vote` varchar(16) NOT NULL,
                `created_at` datetime(6) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
            'votes'
        );

        // Presence table for session attendance / device-based check-ins
        $this->executeSchemaStatement(
            'CREATE TABLE IF NOT EXISTS `presence` (
                `id` varchar(32) NOT NULL PRIMARY KEY,
                `session_id` varchar(32) NOT NULL,
                `user_id` varchar(32) NOT NULL,
                `device_id` varchar(128) DEFAULT NULL,
                `present_at` datetime(6) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
            'presence'
        );

        // Annotations for agendas / legislatives
        $this->executeSchemaStatement(
            'CREATE TABLE IF NOT EXISTS `annotations` (
                `id` varchar(32) NOT NULL PRIMARY KEY,
                `resource_type` varchar(32) NOT NULL,
                `resource_id` varchar(32) NOT NULL,
                `user_id` varchar(32) NOT NULL,
                `type` varchar(32) NOT NULL,
                `data` longtext NOT NULL,
                `page` int DEFAULT NULL,
                `created_at` datetime(6) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
            'annotations'
        );
    }

    private function executeSchemaStatement(string $sql, string $table): void
    {
        try {
            $this->pdo->exec($sql);
            return;
        } catch (PDOException $e) {
            if (!$this->isTablespaceImportError($e)) {
                throw $e;
            }
        }

        $this->attemptTablespaceRecovery($table, $sql);
    }

    private function attemptTablespaceRecovery(string $table, string $sql): void
    {
        $this->discardCorruptTablespace($table);
        $this->tryDeleteOrphanedTablespaceFile($table);

        try {
            $this->pdo->exec($sql);
            return;
        } catch (PDOException $e) {
            if ($this->isTablespaceImportError($e)) {
                throw new PDOException(
                    sprintf(
                        'MySQL reported an orphaned InnoDB tablespace for table `%s`. ' .
                        'If the server is still running, stop it, remove the stale .ibd file from the MySQL data directory, restart MySQL, and retry. ' .
                        'If the file cannot be removed automatically, use phpMyAdmin or the MySQL shell to drop and recreate the table manually.',
                        $table
                    ),
                    (int)$e->getCode(),
                    $e
                );
            }

            throw $e;
        }
    }

    private function discardCorruptTablespace(string $table): void
    {
        try {
            $this->pdo->exec("DROP TABLE IF EXISTS `$table`");
        } catch (PDOException $ignored) {
            try {
                $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
                $this->pdo->exec("DROP TABLE IF EXISTS `$table`");
            } catch (PDOException $ignored) {
                // ignore further failures when trying to drop a corrupt table.
            } finally {
                try {
                    $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
                } catch (PDOException $ignored) {
                }
            }
        }
    }

    private function tryDeleteOrphanedTablespaceFile(string $table): void
    {
        try {
            $datadir = $this->pdo->query('SELECT @@datadir')->fetchColumn();
            $dbname = $this->pdo->query('SELECT DATABASE()')->fetchColumn();

            if (!$datadir || !$dbname) {
                return;
            }

            $datadir = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $datadir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $dbPath = $datadir . $dbname . DIRECTORY_SEPARATOR;

            foreach ([$table . '.ibd', $table . '.frm'] as $file) {
                $path = $dbPath . $file;
                if (is_file($path) && is_writable($path)) {
                    @unlink($path);
                }
            }
        } catch (PDOException $ignored) {
            // ignore filesystem cleanup failures; the user may need to remove files manually.
        }
    }

    private function isTablespaceImportError(PDOException $e): bool
    {
        $errorInfo = $e->errorInfo ?? [];
        $sqlState = $errorInfo[0] ?? '';
        $driverCode = (int)($errorInfo[1] ?? 0);

        return $sqlState === 'HY000' && $driverCode === 1813;
    }

    private function migrateJsonData(): void
    {
        $jsonFile = __DIR__ . '/../../storage/data.json';
        if (!file_exists($jsonFile)) {
            return;
        }

        $data = json_decode((string)file_get_contents($jsonFile), true);
        if (!is_array($data)) {
            return;
        }

        if (!$this->isTableEmpty('users') || !$this->isTableEmpty('sessions') || !$this->isTableEmpty('agendas') || !$this->isTableEmpty('legislatives')) {
            return;
        }

        $this->pdo->beginTransaction();
        try {
            foreach ($data['users'] ?? [] as $user) {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `notes`, `created_at`)
                     VALUES (:id, :name, :email, :password, :role, :status, :notes, :created_at)'
                );
                $stmt->execute([
                    'id' => $user['id'],
                    'name' => $user['name'] ?? '',
                    'email' => $user['email'] ?? '',
                    'password' => $user['password'] ?? '',
                    'role' => $user['role'] ?? 'user',
                    'status' => $user['status'] ?? 'active',
                    'notes' => $user['notes'] ?? '',
                    'created_at' => $user['created_at'] ?? date(DATE_ATOM),
                ]);
            }

            foreach ($data['sessions'] ?? [] as $session) {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO `sessions` (`id`, `title`, `desc`, `date`, `start`, `end`, `location`, `capacity`, `status`)
                     VALUES (:id, :title, :desc, :date, :start, :end, :location, :capacity, :status)'
                );
                $stmt->execute([
                    'id' => $session['id'],
                    'title' => $session['title'] ?? '',
                    'desc' => $session['desc'] ?? '',
                    'date' => $session['date'] ?? date('Y-m-d'),
                    'start' => $session['start'] ?? '',
                    'end' => $session['end'] ?? '',
                    'location' => $session['location'] ?? '',
                    'capacity' => $session['capacity'] ?? 0,
                    'status' => $session['status'] ?? 'scheduled',
                ]);
            }

            foreach ($data['agendas'] ?? [] as $agenda) {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO `agendas` (`id`, `title`, `file_name`, `file_size`, `file_type`, `session_id`, `file_path`, `uploaded_at`)
                     VALUES (:id, :title, :file_name, :file_size, :file_type, :session_id, :file_path, :uploaded_at)'
                );
                $stmt->execute([
                    'id' => $agenda['id'],
                    'title' => $agenda['title'] ?? '',
                    'file_name' => $agenda['file_name'] ?? '',
                    'file_size' => $agenda['file_size'] ?? 0,
                    'file_type' => $agenda['file_type'] ?? '',
                    'session_id' => $agenda['session_id'] ?? null,
                    'file_path' => $agenda['file_path'] ?? '',
                    'uploaded_at' => $agenda['uploaded_at'] ?? date(DATE_ATOM),
                ]);
            }

            foreach ($data['legislatives'] ?? [] as $item) {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO `legislatives`
                     (`id`, `title`, `type`, `status`, `desc`, `content`, `ref`, `createdById`, `createdByName`, `createdByRole`, `createdAt`, `updatedAt`, `documentHash`, `lastModifiedById`, `lastModifiedByName`, `lastModifiedAt`, `control_number`, `received_at`, `workflow_status`)
                     VALUES (:id, :title, :type, :status, :desc, :content, :ref, :createdById, :createdByName, :createdByRole, :createdAt, :updatedAt, :documentHash, :lastModifiedById, :lastModifiedByName, :lastModifiedAt, :control_number, :received_at, :workflow_status)'
                );
                $stmt->execute([
                    'id' => $item['id'],
                    'title' => $item['title'] ?? '',
                    'type' => $item['type'] ?? '',
                    'status' => $item['status'] ?? 'draft',
                    'desc' => $item['desc'] ?? '',
                    'content' => $item['content'] ?? '',
                    'ref' => $item['ref'] ?? '',
                    'createdById' => $item['createdById'] ?? '',
                    'createdByName' => $item['createdByName'] ?? '',
                    'createdByRole' => $item['createdByRole'] ?? '',
                    'createdAt' => $item['createdAt'] ?? date(DATE_ATOM),
                    'updatedAt' => $item['updatedAt'] ?? null,
                    'documentHash' => $item['documentHash'] ?? null,
                    'lastModifiedById' => $item['lastModifiedById'] ?? null,
                    'lastModifiedByName' => $item['lastModifiedByName'] ?? null,
                    'lastModifiedAt' => $item['lastModifiedAt'] ?? null,
                    'control_number' => $item['control_number'] ?? ('LEG-' . date('Y') . '-' . strtoupper(substr((string)$item['id'], 0, 8))),
                    'received_at' => $item['received_at'] ?? ($item['createdAt'] ?? date(DATE_ATOM)),
                    'workflow_status' => $item['workflow_status'] ?? 'Received',
                ]);
            }

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
        }
    }

    private function isTableEmpty(string $table): bool
    {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM `$table`");
            return ((int)$stmt->fetchColumn()) === 0;
        } catch (PDOException $e) {
            if ($this->isTableMissingOrCorrupt($e)) {
                $this->repairTable($table);
                return true;
            }

            throw $e;
        }
    }

    private function isTableMissingOrCorrupt(PDOException $e): bool
    {
        $errorInfo = $e->errorInfo ?? [];
        $sqlState = $errorInfo[0] ?? '';
        $driverCode = (int)($errorInfo[1] ?? 0);

        return $sqlState === '42S02' || $driverCode === 1932;
    }

    private function repairTable(string $table): void
    {
        try {
            $this->pdo->exec("DROP TABLE IF EXISTS `$table`");
        } catch (PDOException $ignored) {
            // If the table is corrupt or the DROP fails, continue and attempt schema reinitialization.
        }

        $this->initializeSchema();
    }
}
