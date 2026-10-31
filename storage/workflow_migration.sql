-- Municipal legislative workflow migration for existing kaya_portal installations.
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `is_council_member` TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `email_notifications_enabled` TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE `agendas`
    ADD COLUMN IF NOT EXISTS `source_legislative_id` VARCHAR(32) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `distribution_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS `distributed_at` DATETIME(6) DEFAULT NULL;

ALTER TABLE `legislatives`
    ADD COLUMN IF NOT EXISTS `intake_classification` VARCHAR(50) NOT NULL DEFAULT 'Legislative Matters',
    ADD COLUMN IF NOT EXISTS `routing_status` VARCHAR(80) NOT NULL DEFAULT 'For Agenda',
    ADD COLUMN IF NOT EXISTS `stream_type` VARCHAR(80) NOT NULL DEFAULT 'Approved Resolutions/Ordinances',
    ADD COLUMN IF NOT EXISTS `assigned_committee` VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `date_referred` DATE DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `committee_report_status` VARCHAR(50) DEFAULT 'Pending',
    ADD COLUMN IF NOT EXISTS `hearing_date` DATE DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `lce_action` VARCHAR(50) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `lce_action_at` DATETIME(6) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `lce_action_by_id` VARCHAR(32) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `archive_status` VARCHAR(80) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `admin_decision_notes` TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `admin_decision_at` DATETIME(6) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `workflow_status` VARCHAR(80) NOT NULL DEFAULT 'EDITING';

CREATE TABLE IF NOT EXISTS `agenda_distributions` (
    `id` VARCHAR(32) NOT NULL PRIMARY KEY,
    `agenda_id` VARCHAR(32) NOT NULL,
    `target_user_id` VARCHAR(32) NOT NULL,
    `sent_at` DATETIME(6) NOT NULL,
    `delivery_status` VARCHAR(20) NOT NULL DEFAULT 'sent',
    `read_at` DATETIME(6) DEFAULT NULL,
    `file_path` VARCHAR(255) DEFAULT NULL,
    KEY `idx_agenda_distributions_agenda` (`agenda_id`),
    KEY `idx_agenda_distributions_target` (`target_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
