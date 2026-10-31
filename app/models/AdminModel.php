<?php
namespace App\Models;

use App\Core\Database;
use App\Services\SigningKeyStorage;
use PDO;

class AdminModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = (new Database())->getConnection();
    }

    private function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function uid(): string
    {
        return bin2hex(random_bytes(8));
    }

    private function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function getData(): array
    {
        return [
            'users' => $this->getUsers(),
            'sessions' => $this->getSessions(),
            'agendas' => $this->getAgendas(),
            'legislatives' => $this->getLegislatives(),
            'delivery_history' => $this->getDeliveryHistory(),
            'delivery_templates' => $this->getDeliveryTemplates(),
            'ctfrb_reports' => $this->getCtfrbReports(),
            'agenda_distributions' => $this->getAgendaDistributions(),
        ];
    }

    public function createWorkflowDocument(array $data): string
    {
        $id = $this->uid();
        $stmt = $this->pdo->prepare(
            'INSERT INTO `documents`
             (`id`, `title`, `content`, `status`, `workflow_type`, `workflow_stage`, `intake_classification`, `metadata`, `created_by_id`, `updated_by_id`, `created_at`, `updated_at`)
             VALUES (:id, :title, :content, :status, :workflow_type, :workflow_stage, :intake_classification, :metadata, :created_by_id, :updated_by_id, :created_at, :updated_at)'
        );
        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            'id' => $id,
            'title' => $data['title'] ?? 'Untitled document',
            'content' => $data['content'] ?? '',
            'status' => $data['status'] ?? 'active',
            'workflow_type' => $data['workflow_type'] ?? 'intake',
            'workflow_stage' => $data['workflow_stage'] ?? 'RECEIVED',
            'intake_classification' => $data['intake_classification'] ?? null,
            'metadata' => is_string($data['metadata'] ?? null) ? $data['metadata'] : json_encode($data['metadata'] ?? [], JSON_UNESCAPED_UNICODE),
            'created_by_id' => $data['created_by_id'] ?? null,
            'updated_by_id' => $data['updated_by_id'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return $id;
    }

    public function updateWorkflowDocument(string $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id, 'updated_at' => date('Y-m-d H:i:s')];
        foreach (['status', 'workflow_stage', 'metadata', 'content', 'updated_by_id'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`$field` = :$field";
                $params[$field] = $field === 'metadata' && !is_string($data[$field])
                    ? json_encode($data[$field], JSON_UNESCAPED_UNICODE)
                    : $data[$field];
            }
        }
        if (!$fields) {
            return false;
        }
        $fields[] = '`updated_at` = :updated_at';
        return $this->pdo->prepare('UPDATE `documents` SET ' . implode(', ', $fields) . ' WHERE `id` = :id')->execute($params);
    }

    public function getWorkflowDocument(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `documents` WHERE `id` = :id', ['id' => $id]);
    }

    public function createWorkflowRecord(string $table, array $data): string
    {
        $allowedTables = ['committee_referrals', 'committee_reports', 'awards', 'document_signatures', 'posting_certificates'];
        if (!in_array($table, $allowedTables, true)) {
            throw new \InvalidArgumentException('Invalid workflow table.');
        }
        $id = $this->uid();
        $data['id'] = $id;
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $columns = array_keys($data);
        $quotedColumns = array_map(static fn(string $column): string => '`' . $column . '`', $columns);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);
        $stmt = $this->pdo->prepare(sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $table,
            implode(', ', $quotedColumns),
            implode(', ', $placeholders)
        ));
        $stmt->execute($data);
        return $id;
    }

    public function updateWorkflowRecord(string $table, string $id, array $data): bool
    {
        $allowedTables = ['committee_referrals', 'committee_reports', 'awards', 'document_signatures', 'posting_certificates'];
        if (!in_array($table, $allowedTables, true) || !$data) {
            return false;
        }
        $fields = [];
        $params = ['id' => $id];
        foreach ($data as $field => $value) {
            $fields[] = "`$field` = :$field";
            $params[$field] = $value;
        }
        return $this->pdo->prepare('UPDATE `' . $table . '` SET ' . implode(', ', $fields) . ' WHERE `id` = :id')->execute($params);
    }

    public function recordLegislativeAudit(string $documentId, string $eventType, ?array $user, array|string|null $details = null): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `legislative_audit_events` (`id`, `document_id`, `event_type`, `details`, `user_id`, `user_name`, `user_role`, `created_at`)
             VALUES (:id, :document_id, :event_type, :details, :user_id, :user_name, :user_role, :created_at)'
        );
        $stmt->execute([
            'id' => $this->uid(),
            'document_id' => $documentId,
            'event_type' => $eventType,
            'details' => is_array($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : $details,
            'user_id' => $user['id'] ?? null,
            'user_name' => $user['name'] ?? 'System',
            'user_role' => $user['role'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getLegislativeAudit(string $documentId): array
    {
        return $this->fetchAll('SELECT * FROM `legislative_audit_events` WHERE `document_id` = :id ORDER BY `created_at` DESC', ['id' => $documentId]);
    }

    public function verifyLegislativeFileIntegrity(array $document, string $filePath): array
    {
        $expectedHash = strtolower(trim((string)($document['fileRevisionHash'] ?? '')));
        if (!preg_match('/\A[a-f0-9]{64}\z/', $expectedHash)) {
            return ['status' => 'unverified', 'expected_hash' => $expectedHash, 'actual_hash' => null, 'newly_detected' => false];
        }

        $actualHash = is_file($filePath) ? hash_file('sha256', $filePath) : false;
        $actualHash = is_string($actualHash) ? strtolower($actualHash) : 'MISSING';
        if ($actualHash !== 'MISSING' && hash_equals($expectedHash, $actualHash)) {
            return ['status' => 'verified', 'expected_hash' => $expectedHash, 'actual_hash' => $actualHash, 'newly_detected' => false];
        }

        $eventType = 'file_integrity_tampered';
        $existingEvents = $this->fetchAll(
            'SELECT `details` FROM `legislative_audit_events` WHERE `document_id` = :document_id AND `event_type` = :event_type',
            ['document_id' => (string)$document['id'], 'event_type' => $eventType]
        );
        foreach ($existingEvents as $event) {
            $details = json_decode((string)($event['details'] ?? ''), true);
            if (is_array($details)
                && ($details['expected_sha256'] ?? '') === $expectedHash
                && ($details['actual_sha256'] ?? '') === $actualHash) {
                return ['status' => 'tampered', 'expected_hash' => $expectedHash, 'actual_hash' => $actualHash, 'newly_detected' => false];
            }
        }

        $this->recordLegislativeAudit((string)$document['id'], $eventType, null, [
            'file_path' => basename($filePath),
            'expected_sha256' => $expectedHash,
            'actual_sha256' => $actualHash,
        ]);
        $ownerId = trim((string)($document['createdById'] ?? ''));
        if ($ownerId !== '') {
            $label = trim((string)($document['control_number'] ?? $document['title'] ?? 'Bill'));
            $this->addUserNotification(
                $ownerId,
                'Integrity alert: the uploaded file for ' . $label . ' does not match its recorded SHA-256. The changed file was detected and its download was blocked.',
                'index.php?page=legislative-audit-trail&ledger_id=' . rawurlencode((string)$document['id'])
            );
        }

        return ['status' => 'tampered', 'expected_hash' => $expectedHash, 'actual_hash' => $actualHash, 'newly_detected' => true];
    }

    public function findLegislativeByControlNumber(string $controlNumber): ?array
    {
        return $this->fetchOne('SELECT * FROM `legislatives` WHERE `control_number` = :control_number', ['control_number' => $controlNumber]);
    }

    public function getLegislativeVersions(string $documentId): array
    {
        return $this->fetchAll('SELECT * FROM `legislative_versions` WHERE `document_id` = :id ORDER BY `version_number` DESC', ['id' => $documentId]);
    }

    public function updateLegislativeVersion(string $documentId, int $versionNumber, string $status, ?string $documentHash = null): bool
    {
        $sql = 'UPDATE `legislative_versions` SET `status` = :status';
        $params = ['id' => $documentId, 'version_number' => $versionNumber, 'status' => $status];
        if ($documentHash !== null) {
            $sql .= ', `document_hash` = :document_hash';
            $params['document_hash'] = $documentHash;
        }
        $sql .= ' WHERE `document_id` = :id AND `version_number` = :version_number';
        return $this->pdo->prepare($sql)->execute($params);
    }

    public function createLegislativeSignature(array $signature): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `legislative_signatures` (`id`, `document_id`, `version_number`, `signer_id`, `signer_name`, `signer_role`, `algorithm`, `signature`, `certificate_pem`, `public_key_pem`, `signing_key_id`, `certificate_fingerprint`, `document_hash`, `verified_at`, `created_at`)
             VALUES (:id, :document_id, :version_number, :signer_id, :signer_name, :signer_role, :algorithm, :signature, :certificate_pem, :public_key_pem, :signing_key_id, :certificate_fingerprint, :document_hash, :verified_at, :created_at)'
        );
        $stmt->execute([
            'id' => $this->uid(), 'document_id' => $signature['document_id'],
            'version_number' => $signature['version_number'], 'signer_id' => $signature['signer_id'],
            'signer_name' => $signature['signer_name'], 'signer_role' => $signature['signer_role'],
            'algorithm' => $signature['algorithm'], 'signature' => $signature['signature'],
            'certificate_pem' => $signature['certificate_pem'] ?? null,
            'public_key_pem' => $signature['public_key_pem'] ?? null,
            'signing_key_id' => $signature['signing_key_id'] ?? null,
            'certificate_fingerprint' => $signature['certificate_fingerprint'],
            'document_hash' => $signature['document_hash'], 'verified_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function createLegislativeSigningKey(array $key): string
    {
        $id = $this->uid();
        SigningKeyStorage::store($id, (string)($key['encrypted_private_key_pem'] ?? ''));
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO `legislative_signing_keys` (`id`, `key_name`, `key_purpose`, `algorithm`, `public_key_pem`, `fingerprint`, `is_active`, `owner_user_id`, `created_by_id`, `created_by_name`, `created_at`)
                 VALUES (:id, :key_name, :key_purpose, :algorithm, :public_key_pem, :fingerprint, 1, :owner_user_id, :created_by_id, :created_by_name, :created_at)'
            );
            $stmt->execute([
                'id' => $id, 'key_name' => $key['key_name'], 'key_purpose' => $key['key_purpose'] ?? 'legislative', 'algorithm' => $key['algorithm'],
                'public_key_pem' => $key['public_key_pem'],
                'fingerprint' => $key['fingerprint'], 'owner_user_id' => $key['owner_user_id'] ?? null, 'created_by_id' => $key['created_by_id'],
                'created_by_name' => $key['created_by_name'], 'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $exception) {
            SigningKeyStorage::delete($id);
            throw $exception;
        }
        return $id;
    }

    public function getActiveLegislativeSigningKey(string $purpose = 'legislative'): ?array
    {
        $key = $this->fetchOne('SELECT * FROM `legislative_signing_keys` WHERE `is_active` = 1 AND `key_purpose` = :purpose ORDER BY `created_at` DESC LIMIT 1', ['purpose' => $purpose]);
        if ($key) {
            $key['private_key_pem'] = SigningKeyStorage::read((string)$key['id']);
        }
        return $key;
    }

    public function getOfficerAgendaSigningKey(string $userId): ?array
    {
        return $this->getUserSigningKey($userId, 'agenda');
    }

    public function getUserSigningKey(string $userId, string $purpose): ?array
    {
        $key = $this->fetchOne(
            'SELECT * FROM `legislative_signing_keys` WHERE `owner_user_id` = :owner_user_id AND `key_purpose` = :purpose AND `is_active` = 1 ORDER BY `created_at` DESC LIMIT 1',
            ['owner_user_id' => $userId, 'purpose' => $purpose]
        );
        if ($key) {
            $key['private_key_pem'] = SigningKeyStorage::read((string)$key['id']);
        }
        return $key;
    }

    public function markOfficerSigningKeyDelivered(string $keyId): bool
    {
        return $this->markUserSigningKeyDelivered($keyId);
    }

    public function markUserSigningKeyDelivered(string $keyId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE `legislative_signing_keys` SET `private_key_delivered_at` = NOW(6) WHERE `id` = :id AND `private_key_delivered_at` IS NULL');
        $stmt->execute(['id' => $keyId]);
        if ($stmt->rowCount() !== 1) {
            return false;
        }
        SigningKeyStorage::delete($keyId);
        return true;
    }

    public function getLatestLegislativeSignature(string $documentId): ?array
    {
        return $this->fetchOne('SELECT * FROM `legislative_signatures` WHERE `document_id` = :id ORDER BY `created_at` DESC LIMIT 1', ['id' => $documentId]);
    }

    public function getLegislativeSignatures(string $documentId): array
    {
        return $this->fetchAll('SELECT * FROM `legislative_signatures` WHERE `document_id` = :id ORDER BY `created_at` DESC', ['id' => $documentId]);
    }

    public function getLegislativeSignatureForVersion(string $documentId, int $versionNumber): ?array
    {
        return $this->fetchOne('SELECT * FROM `legislative_signatures` WHERE `document_id` = :id AND `version_number` = :version_number ORDER BY `created_at` DESC LIMIT 1', [
            'id' => $documentId, 'version_number' => $versionNumber,
        ]);
    }

    public function createLegislativeVersion(string $documentId, array $record, ?array $user, ?int $versionNumber = null): int
    {
        if ($versionNumber === null) {
            $latest = $this->fetchOne('SELECT MAX(`version_number`) AS version_number FROM `legislative_versions` WHERE `document_id` = :id', ['id' => $documentId]);
            $versionNumber = (int)($latest['version_number'] ?? 0) + 1;
        }
        $filePath = $record['file_path'] ?? null;
        if ($filePath !== null && $filePath !== '') {
            $source = dirname(__DIR__, 2) . '/storage/uploads/' . basename((string)$filePath);
            if (is_file($source)) {
                $filePath = 'revision_' . $documentId . '_v' . $versionNumber . '_' . basename((string)$filePath);
                $target = dirname(__DIR__, 2) . '/storage/uploads/' . $filePath;
                if (!copy($source, $target)) {
                    throw new \RuntimeException('Unable to preserve the previous document attachment.');
                }
            }
        }
        $hash = (string)($record['documentHash'] ?? hash('sha256', (string)($record['title'] ?? '') . '|' . (string)($record['content'] ?? '')));
        $versionStatus = ($record['workflow_status'] ?? '') === 'Final Draft'
            ? 'Final Draft v' . $versionNumber
            : ($versionNumber === 1 ? 'Draft v1' : 'Revision v' . $versionNumber);
        $stmt = $this->pdo->prepare(
            'INSERT INTO `legislative_versions` (`id`, `document_id`, `version_number`, `title`, `content`, `file_name`, `file_path`, `document_hash`, `status`, `created_by_id`, `created_by_name`, `created_at`)
             VALUES (:id, :document_id, :version_number, :title, :content, :file_name, :file_path, :document_hash, :status, :created_by_id, :created_by_name, :created_at)'
        );
        $stmt->execute([
            'id' => $this->uid(), 'document_id' => $documentId, 'version_number' => $versionNumber,
            'title' => $record['title'] ?? 'Untitled document', 'content' => $record['content'] ?? '',
            'file_name' => $record['file_name'] ?? null, 'file_path' => $filePath,
            'document_hash' => $hash, 'status' => $versionStatus,
            'created_by_id' => $user['id'] ?? null, 'created_by_name' => $user['name'] ?? 'System',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $versionNumber;
    }

    public function getUsers(): array
    {
        return $this->fetchAll('SELECT * FROM `users` ORDER BY `created_at` DESC');
    }

    public function getAuthors(): array
    {
        return $this->fetchAll('SELECT * FROM `authors` WHERE `status` = :status ORDER BY `name` ASC', ['status' => 'active']);
    }

    public function getSponsors(): array
    {
        return $this->fetchAll('SELECT * FROM `sponsors` WHERE `status` = :status ORDER BY `name` ASC', ['status' => 'active']);
    }

    public function getDocument(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `documents` WHERE `id` = :id', ['id' => $id]);
    }

    public function createDocument(array $data): string
    {
        $id = $this->uid();
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO `documents`
             (`id`, `title`, `content`, `author_id`, `author_name`, `sponsor_id`, `sponsor_name`, `status`, `version`, `file_name`, `file_path`, `file_type`, `file_size`, `created_by_id`, `updated_by_id`, `created_at`, `updated_at`, `published_at`)
             VALUES (:id, :title, :content, :author_id, :author_name, :sponsor_id, :sponsor_name, :status, :version, :file_name, :file_path, :file_type, :file_size, :created_by_id, :updated_by_id, :created_at, :updated_at, :published_at)'
        );
        $stmt->execute([
            'id' => $id, 'title' => $data['title'], 'content' => $data['content'],
            'author_id' => $data['author_id'] ?? null, 'author_name' => $data['author_name'] ?? null,
            'sponsor_id' => $data['sponsor_id'] ?? null, 'sponsor_name' => $data['sponsor_name'] ?? null,
            'status' => $data['status'] ?? 'draft', 'version' => $data['version'] ?? '1.0',
            'file_name' => $data['file_name'] ?? null, 'file_path' => $data['file_path'] ?? null,
            'file_type' => $data['file_type'] ?? null, 'file_size' => $data['file_size'] ?? null,
            'created_by_id' => $data['created_by_id'] ?? $data['updated_by_id'] ?? null,
            'updated_by_id' => $data['updated_by_id'] ?? null, 'created_at' => $now,
            'updated_at' => $now, 'published_at' => ($data['status'] ?? 'draft') === 'published' ? $now : null,
        ]);
        return $id;
    }

    public function updateDocument(string $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id, 'updated_at' => date('Y-m-d H:i:s')];
        foreach (['title', 'content', 'author_id', 'author_name', 'sponsor_id', 'sponsor_name', 'status', 'version', 'file_name', 'file_path', 'file_type', 'file_size', 'updated_by_id'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`$field` = :$field";
                $params[$field] = $data[$field];
            }
        }
        if (array_key_exists('status', $data) && $data['status'] === 'published') {
            $fields[] = '`published_at` = COALESCE(`published_at`, :published_at)';
            $params['published_at'] = $params['updated_at'];
        }
        if (!$fields) {
            return false;
        }
        $fields[] = '`updated_at` = :updated_at';
        return $this->pdo->prepare('UPDATE `documents` SET ' . implode(', ', $fields) . ' WHERE `id` = :id')->execute($params);
    }

    public function getSessions(): array
    {
        return $this->fetchAll('SELECT * FROM `sessions` ORDER BY `date` DESC, `start` DESC');
    }

    public function getAgendas(): array
    {
        return $this->fetchAll('SELECT * FROM `agendas` ORDER BY `uploaded_at` DESC');
    }

    public function getDeliveryHistory(): array
    {
        return $this->fetchAll('SELECT * FROM `delivery_history` ORDER BY `created_at` DESC');
    }

    public function getDeliveryTemplates(): array
    {
        return $this->fetchAll('SELECT * FROM `delivery_templates` ORDER BY `created_at` DESC');
    }

    public function getCtfrbReports(): array
    {
        return $this->fetchAll('SELECT * FROM `ctfrb_reports` ORDER BY `created_at` DESC');
    }

    public function getAgendaDistributions(?string $agendaId = null): array
    {
        $sql = 'SELECT * FROM `agenda_distributions`';
        $params = [];
        if ($agendaId !== null && $agendaId !== '') {
            $sql .= ' WHERE `agenda_id` = :agenda_id';
            $params['agenda_id'] = $agendaId;
        }
        return $this->fetchAll($sql . ' ORDER BY `sent_at` DESC', $params);
    }

    public function logAgendaDistribution(array $distribution): string
    {
        $id = $this->uid();
        $stmt = $this->pdo->prepare(
            'INSERT INTO `agenda_distributions`
             (`id`, `agenda_id`, `target_user_id`, `sent_at`, `delivery_status`, `read_at`, `file_path`)
             VALUES (:id, :agenda_id, :target_user_id, :sent_at, :delivery_status, :read_at, :file_path)'
        );
        $stmt->execute([
            'id' => $id,
            'agenda_id' => $distribution['agenda_id'],
            'target_user_id' => $distribution['target_user_id'],
            'sent_at' => $distribution['sent_at'] ?? date(DATE_ATOM),
            'delivery_status' => $distribution['delivery_status'] ?? 'sent',
            'read_at' => $distribution['read_at'] ?? null,
            'file_path' => $distribution['file_path'] ?? null,
        ]);
        return $id;
    }

    public function markAgendaDistributionRead(string $agendaId, string $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE `agenda_distributions`
             SET `delivery_status` = :delivery_status, `read_at` = :read_at
             WHERE `agenda_id` = :agenda_id AND `target_user_id` = :target_user_id'
        );
        return $stmt->execute([
            'delivery_status' => 'read',
            'read_at' => date(DATE_ATOM),
            'agenda_id' => $agendaId,
            'target_user_id' => $userId,
        ]);
    }

    public function getCtfrbReport(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `ctfrb_reports` WHERE `id` = :id', ['id' => $id]);
    }

    public function createCtfrbReport(array $report): string
    {
        $id = $this->uid();
        $stmt = $this->pdo->prepare(
            'INSERT INTO `ctfrb_reports` (`id`, `applicant_name`, `address`, `franchise_number`, `application_type`, `created_at`) VALUES (:id, :applicant_name, :address, :franchise_number, :application_type, :created_at)'
        );
        $stmt->execute([
            'id' => $id,
            'applicant_name' => $report['applicant_name'],
            'address' => $report['address'],
            'franchise_number' => $report['franchise_number'],
            'application_type' => $report['application_type'],
            'created_at' => date(DATE_ATOM),
        ]);
        return $id;
    }

    public function updateCtfrbReport(string $id, array $changes): bool
    {
        $fields = [];
        $params = ['id' => $id];
        foreach (['applicant_name', 'address', 'franchise_number', 'application_type'] as $field) {
            if (array_key_exists($field, $changes)) {
                $fields[] = "`$field` = :$field";
                $params[$field] = $changes[$field];
            }
        }
        if (empty($fields)) {
            return false;
        }
        $fields[] = '`updated_at` = :updated_at';
        $params['updated_at'] = date(DATE_ATOM);
        $stmt = $this->pdo->prepare('UPDATE `ctfrb_reports` SET ' . implode(', ', $fields) . ' WHERE `id` = :id');
        return $stmt->execute($params);
    }

    public function createDeliveryTemplate(array $template): string
    {
        $id = $this->uid();
        $createdAt = date(DATE_ATOM);
        $stmt = $this->pdo->prepare(
            'INSERT INTO `delivery_templates` (`id`, `name`, `file_name`, `file_path`, `template_type`, `uploaded_by_id`, `uploaded_by_name`, `notes`, `created_at`)
             VALUES (:id, :name, :file_name, :file_path, :template_type, :uploaded_by_id, :uploaded_by_name, :notes, :created_at)'
        );
        $stmt->execute([
            'id' => $id,
            'name' => $template['name'] ?? '',
            'file_name' => $template['file_name'] ?? '',
            'file_path' => $template['file_path'] ?? '',
            'template_type' => $template['template_type'] ?? 'general',
            'uploaded_by_id' => $template['uploaded_by_id'] ?? null,
            'uploaded_by_name' => $template['uploaded_by_name'] ?? null,
            'notes' => $template['notes'] ?? null,
            'created_at' => $createdAt,
        ]);
        return $id;
    }

    public function logDeliveryHistory(array $entry): string
    {
        $id = $this->uid();
        $createdAt = date(DATE_ATOM);
        $stmt = $this->pdo->prepare(
            'INSERT INTO `delivery_history` (`id`, `item_type`, `item_id`, `item_title`, `session_id`, `action`, `recipient_ids`, `recipient_names`, `sent_by_id`, `sent_by_name`, `notes`, `template_id`, `template_name`, `template_type`, `created_at`)
             VALUES (:id, :item_type, :item_id, :item_title, :session_id, :action, :recipient_ids, :recipient_names, :sent_by_id, :sent_by_name, :notes, :template_id, :template_name, :template_type, :created_at)'
        );
        $stmt->execute([
            'id' => $id,
            'item_type' => $entry['item_type'] ?? 'agenda',
            'item_id' => $entry['item_id'] ?? '',
            'item_title' => $entry['item_title'] ?? '',
            'session_id' => $entry['session_id'] ?? null,
            'action' => $entry['action'] ?? 'sent',
            'recipient_ids' => is_array($entry['recipient_ids'] ?? null) ? json_encode($entry['recipient_ids']) : ($entry['recipient_ids'] ?? null),
            'recipient_names' => is_array($entry['recipient_names'] ?? null) ? json_encode($entry['recipient_names']) : ($entry['recipient_names'] ?? null),
            'sent_by_id' => $entry['sent_by_id'] ?? null,
            'sent_by_name' => $entry['sent_by_name'] ?? null,
            'notes' => $entry['notes'] ?? null,
            'template_id' => $entry['template_id'] ?? null,
            'template_name' => $entry['template_name'] ?? null,
            'template_type' => $entry['template_type'] ?? null,
            'created_at' => $createdAt,
        ]);
        return $id;
    }

    public function getLegislatives(): array
    {
        return $this->fetchAll('SELECT l.*, u.name AS assigned_to_name FROM `legislatives` l LEFT JOIN `users` u ON u.id = l.assigned_to_id ORDER BY l.createdAt DESC');
    }

    public function hasUsers(): bool
    {
        $count = $this->fetchOne('SELECT COUNT(*) AS total FROM `users`');
        return isset($count['total']) && (int)$count['total'] > 0;
    }

    public function getUserById(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `users` WHERE `id` = :id', ['id' => $id]);
    }

    public function findUserByEmail(string $email): ?array
    {
        return $this->fetchOne('SELECT * FROM `users` WHERE `email` = :email', ['email' => $email]);
    }

    public function verifyPassword(array $user, string $password): bool
    {
        if (empty($user['password'])) {
            return false;
        }
        return password_verify($password, $user['password']);
    }

    public function createUser(array $user): string
    {
        $id = $this->uid();
        $createdAt = date(DATE_ATOM);
        $password = !empty($user['password']) ? $this->hashPassword($user['password']) : '';

        $stmt = $this->pdo->prepare(
            'INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `notes`, `is_council_member`, `email_notifications_enabled`, `created_at`)
             VALUES (:id, :name, :email, :password, :role, :status, :notes, :is_council_member, :email_notifications_enabled, :created_at)'
        );
        $stmt->execute([
            'id' => $id,
            'name' => $user['name'],
            'email' => $user['email'],
            'password' => $password,
            'role' => $user['role'] ?? 'user',
            'status' => $user['status'] ?? 'active',
            'notes' => $user['notes'] ?? '',
            'is_council_member' => !empty($user['is_council_member']) ? 1 : 0,
            'email_notifications_enabled' => array_key_exists('email_notifications_enabled', $user) ? (int)(bool)$user['email_notifications_enabled'] : 1,
            'created_at' => $createdAt,
        ]);

        return $id;
    }

    public function updateUser(string $id, array $changes): bool
    {
        $fields = [];
        $params = ['id' => $id];

        if (isset($changes['name'])) {
            $fields[] = '`name` = :name';
            $params['name'] = $changes['name'];
        }
        if (isset($changes['email'])) {
            $fields[] = '`email` = :email';
            $params['email'] = $changes['email'];
        }
        if (isset($changes['role'])) {
            $fields[] = '`role` = :role';
            $params['role'] = $changes['role'];
        }
        if (isset($changes['status'])) {
            $fields[] = '`status` = :status';
            $params['status'] = $changes['status'];
        }
        if (isset($changes['notes'])) {
            $fields[] = '`notes` = :notes';
            $params['notes'] = $changes['notes'];
        }
        if (array_key_exists('is_council_member', $changes)) {
            $fields[] = '`is_council_member` = :is_council_member';
            $params['is_council_member'] = (int)(bool)$changes['is_council_member'];
        }
        if (array_key_exists('email_notifications_enabled', $changes)) {
            $fields[] = '`email_notifications_enabled` = :email_notifications_enabled';
            $params['email_notifications_enabled'] = (int)(bool)$changes['email_notifications_enabled'];
        }
        if (!empty($changes['password'])) {
            $fields[] = '`password` = :password';
            $params['password'] = $this->hashPassword($changes['password']);
        }

        if (empty($fields)) {
            return false;
        }

        $stmt = $this->pdo->prepare('UPDATE `users` SET ' . implode(', ', $fields) . ' WHERE `id` = :id');
        return $stmt->execute($params);
    }

    public function deleteUser(string $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `users` WHERE `id` = :id');
        $stmt->execute(['id' => $id]);
    }

    public function createSession(array $session): string
    {
        $id = $this->uid();
        $stmt = $this->pdo->prepare(
            'INSERT INTO `sessions` (`id`, `title`, `desc`, `date`, `start`, `end`, `location`, `capacity`, `status`, `minutes`, `minutes_recorded_at`, `council_attendance`)
             VALUES (:id, :title, :desc, :date, :start, :end, :location, :capacity, :status, :minutes, :minutes_recorded_at, :council_attendance)'
        );
        $stmt->execute([
            'id' => $id,
            'title' => $session['title'],
            'desc' => $session['desc'] ?? '',
            'date' => $session['date'],
            'start' => $session['start'],
            'end' => $session['end'],
            'location' => $session['location'] ?? '',
            'capacity' => $session['capacity'] ?? 0,
            'status' => $session['status'] ?? 'scheduled',
            'minutes' => $session['minutes'] ?? null,
            'minutes_recorded_at' => $session['minutes_recorded_at'] ?? null,
            'council_attendance' => $session['council_attendance'] ?? null,
        ]);
        return $id;
    }

    public function updateSession(string $id, array $changes): bool
    {
        $fields = [];
        $params = ['id' => $id];

        foreach (['title', 'desc', 'date', 'start', 'end', 'location', 'capacity', 'status'] as $field) {
            if (isset($changes[$field])) {
                $fields[] = "`$field` = :$field";
                $params[$field] = $changes[$field];
            }
        }

        foreach (['minutes', 'minutes_recorded_at', 'council_attendance', 'minutes_workflow_status', 'minutes_correction_notes', 'minutes_sent_at', 'minutes_signed_by_id', 'minutes_signed_by_name', 'minutes_signed_at', 'minutes_signature_hash', 'minutes_archived_at', 'minutes_archive_status'] as $field) {
            if (array_key_exists($field, $changes)) {
                $fields[] = "`$field` = :$field";
                $params[$field] = $changes[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $stmt = $this->pdo->prepare('UPDATE `sessions` SET ' . implode(', ', $fields) . ' WHERE `id` = :id');
        return $stmt->execute($params);
    }

    public function addUserNotification(string $userId, string $message, string $url = ''): bool
    {
        $user = $this->getUserById($userId);
        if (!$user) {
            return false;
        }

        $notifications = [];
        if (!empty($user['notifications'])) {
            $decoded = json_decode($user['notifications'], true);
            if (is_array($decoded)) {
                $notifications = $decoded;
            }
        }

        $notification = [
            'message' => $message,
            'created_at' => date(DATE_ATOM),
            'read' => false,
        ];
        if ($url !== '') {
            $notification['url'] = $url;
        }

        $notifications[] = $notification;

        $stmt = $this->pdo->prepare('UPDATE `users` SET `notifications` = :notifications WHERE `id` = :id');
        return $stmt->execute(['notifications' => json_encode($notifications), 'id' => $userId]);
    }

    public function recordSessionAttendance(string $sessionId, string $userId, string $timestamp): bool
    {
        $session = $this->findSession($sessionId);
        if (!$session) {
            return false;
        }

        $attendance = [];
        if (!empty($session['council_attendance'])) {
            $decoded = json_decode($session['council_attendance'], true);
            if (is_array($decoded)) {
                $attendance = $decoded;
            }
        }

        if (isset($attendance[$userId])) {
            return true;
        }

        $attendance[$userId] = [
            'timestamp' => $timestamp,
        ];

        return $this->updateSession($sessionId, ['council_attendance' => json_encode($attendance)]);
    }

    public function deleteSession(string $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `sessions` WHERE `id` = :id');
        $stmt->execute(['id' => $id]);
    }

    public function createAgenda(array $agenda): string
    {
        $id = $this->uid();
        $uploadedAt = date(DATE_ATOM);
        $stmt = $this->pdo->prepare(
            'INSERT INTO `agendas` (`id`, `title`, `file_name`, `file_size`, `file_type`, `session_id`, `file_path`, `uploaded_at`, `published`, `published_at`, `source_legislative_id`)
             VALUES (:id, :title, :file_name, :file_size, :file_type, :session_id, :file_path, :uploaded_at, :published, :published_at, :source_legislative_id)'
        );
        $stmt->execute([
            'id' => $id,
            'title' => $agenda['title'],
            'file_name' => $agenda['file_name'],
            'file_size' => $agenda['file_size'],
            'file_type' => $agenda['file_type'],
            'session_id' => $agenda['session_id'] ?: null,
            'file_path' => $agenda['file_path'],
            'uploaded_at' => $uploadedAt,
            'published' => !empty($agenda['published']) ? 1 : 0,
            'published_at' => $agenda['published_at'] ?? null,
            'source_legislative_id' => $agenda['source_legislative_id'] ?? null,
        ]);
        return $id;
    }

    public function deleteAgenda(string $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `agendas` WHERE `id` = :id');
        $stmt->execute(['id' => $id]);
    }

    public function updateAgenda(string $id, array $changes): bool
    {
        $existing = $this->findAgenda($id);
        if ($existing && array_key_exists('file_path', $changes) && (string)$changes['file_path'] !== (string)$existing['file_path']
            && $this->getLatestAgendaPkiSignature($id)) {
            throw new \RuntimeException('A PKI-signed agenda file is locked and cannot be replaced.');
        }
        $fields = [];
        $params = ['id' => $id];
        foreach (['title','file_name','file_size','file_type','session_id','file_path','uploaded_at','published','published_at','source_legislative_id','approval_status','reading_stage','sent_to_officer_id','sent_to_officer_date','officer_approved','approved_at','officer_signature','officer_comments','approval_stage','ctrfb_approved_at','ctrfb_signature','ctrfb_comments','sent_to_proceeding_id','sent_to_proceeding_date','proceeding_approved_at','proceeding_signature','proceeding_comments','sent_to_council_at','distribution_status','distributed_at'] as $field) {
            if (array_key_exists($field, $changes)) {
                $fields[] = "`$field` = :$field";
                $params[$field] = $changes[$field];
            }
        }
        if (empty($fields)) return false;
        $stmt = $this->pdo->prepare('UPDATE `agendas` SET ' . implode(', ', $fields) . ' WHERE `id` = :id');
        return $stmt->execute($params);
    }

    public function createAgendaPkiSignature(array $signature): int
    {
        $latest = $this->fetchOne('SELECT MAX(`version_number`) AS version_number FROM `agenda_pki_signatures` WHERE `agenda_id` = :agenda_id', ['agenda_id' => $signature['agenda_id']]);
        $versionNumber = (int)($latest['version_number'] ?? 0) + 1;
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO `agenda_pki_signatures` (`id`, `agenda_id`, `version_number`, `signer_id`, `signer_name`, `signer_role`, `algorithm`, `signature`, `public_key_pem`, `signing_key_id`, `key_fingerprint`, `document_hash`, `signed_at`, `verified_at`, `created_at`)
             VALUES (:id, :agenda_id, :version_number, :signer_id, :signer_name, :signer_role, :algorithm, :signature, :public_key_pem, :signing_key_id, :key_fingerprint, :document_hash, :signed_at, :verified_at, :created_at)'
        );
        $stmt->execute([
            'id' => $this->uid(), 'agenda_id' => $signature['agenda_id'], 'version_number' => $versionNumber,
            'signer_id' => $signature['signer_id'], 'signer_name' => $signature['signer_name'],
            'signer_role' => $signature['signer_role'], 'algorithm' => 'RSA-SHA256',
            'signature' => $signature['signature'], 'public_key_pem' => $signature['public_key_pem'],
            'signing_key_id' => $signature['signing_key_id'], 'key_fingerprint' => $signature['key_fingerprint'],
            'document_hash' => $signature['document_hash'], 'signed_at' => $now,
            'verified_at' => $now, 'created_at' => $now,
        ]);
        return $versionNumber;
    }

    public function getLatestAgendaPkiSignature(string $agendaId): ?array
    {
        return $this->fetchOne('SELECT * FROM `agenda_pki_signatures` WHERE `agenda_id` = :agenda_id ORDER BY `version_number` DESC LIMIT 1', ['agenda_id' => $agendaId]);
    }

    public function getAgendaPkiSignatures(string $agendaId): array
    {
        return $this->fetchAll('SELECT * FROM `agenda_pki_signatures` WHERE `agenda_id` = :agenda_id ORDER BY `version_number` DESC', ['agenda_id' => $agendaId]);
    }

    public function getAgendaPkiSignaturesForLegislative(string $legislativeId): array
    {
        return $this->fetchAll(
            'SELECT `signatures`.*, `agendas`.`id` AS `agenda_id`, `agendas`.`title` AS `agenda_title`, `agendas`.`proceeding_signature`
             FROM `agenda_pki_signatures` AS `signatures`
             INNER JOIN `agendas` ON `agendas`.`id` = `signatures`.`agenda_id`
             WHERE `agendas`.`source_legislative_id` = :legislative_id
             ORDER BY `signatures`.`signed_at` DESC, `signatures`.`version_number` DESC',
            ['legislative_id' => $legislativeId]
        );
    }

    public function createLegislative(array $legislative): string
    {
        $id = $this->uid();
        $controlNumber = $legislative['control_number'] ?? ('LEG-' . date('Y') . '-' . strtoupper(substr($id, 0, 8)));
        $stmt = $this->pdo->prepare(
            'INSERT INTO `legislatives`
             (`id`, `title`, `type`, `author_name`, `sponsor_name`, `ai_summary`, `status`, `workflow_status`, `desc`, `content`, `ref`, `createdById`, `createdByName`, `createdByRole`, `createdAt`, `updatedAt`, `file_name`, `file_size`, `file_type`, `file_path`, `scan_file_path`, `fileRevisionHash`, `fileDiffSummary`, `fileChangeLog`, `documentHash`, `lastModifiedById`, `lastModifiedByName`, `lastModifiedAt`, `sent_to_user_ids`, `sent_by_id`, `sent_by_name`, `sent_at`, `intake_classification`, `routing_status`, `stream_type`, `assigned_committee`, `date_referred`, `committee_report_status`, `hearing_date`, `lce_action`, `lce_action_at`, `lce_action_by_id`, `archive_status`, `control_number`, `received_at`, `origin`, `originating_office`, `assigned_to_id`, `assigned_role`, `signed_hash`, `locked_at`, `signature_verified_at`)
             VALUES
             (:id, :title, :type, :author_name, :sponsor_name, :ai_summary, :status, :workflow_status, :desc, :content, :ref, :createdById, :createdByName, :createdByRole, :createdAt, :updatedAt, :file_name, :file_size, :file_type, :file_path, :scan_file_path, :fileRevisionHash, :fileDiffSummary, :fileChangeLog, :documentHash, :lastModifiedById, :lastModifiedByName, :lastModifiedAt, :sent_to_user_ids, :sent_by_id, :sent_by_name, :sent_at, :intake_classification, :routing_status, :stream_type, :assigned_committee, :date_referred, :committee_report_status, :hearing_date, :lce_action, :lce_action_at, :lce_action_by_id, :archive_status, :control_number, :received_at, :origin, :originating_office, :assigned_to_id, :assigned_role, :signed_hash, :locked_at, :signature_verified_at)'
        );
        $stmt->execute([
            'id' => $id,
            'title' => $legislative['title'],
            'type' => $legislative['type'],
            'author_name' => $legislative['author_name'] ?? null,
            'sponsor_name' => $legislative['sponsor_name'] ?? null,
            'ai_summary' => $legislative['ai_summary'] ?? null,
            'status' => $legislative['status'],
            'workflow_status' => $legislative['workflow_status'] ?? 'Receiving',
            'desc' => $legislative['desc'] ?? '',
            'content' => $legislative['content'] ?? '',
            'ref' => $legislative['ref'] ?? '',
            'createdById' => $legislative['createdById'],
            'createdByName' => $legislative['createdByName'],
            'createdByRole' => $legislative['createdByRole'],
            'createdAt' => $legislative['createdAt'],
            'updatedAt' => $legislative['updatedAt'] ?? null,
            'file_name' => $legislative['file_name'] ?? null,
            'file_size' => $legislative['file_size'] ?? null,
            'file_type' => $legislative['file_type'] ?? null,
            'file_path' => $legislative['file_path'] ?? null,
            'scan_file_path' => $legislative['scan_file_path'] ?? null,
            'fileRevisionHash' => $legislative['fileRevisionHash'] ?? null,
            'fileDiffSummary' => $legislative['fileDiffSummary'] ?? null,
            'fileChangeLog' => $legislative['fileChangeLog'] ?? null,
            'documentHash' => $legislative['documentHash'] ?? '',
            'lastModifiedById' => $legislative['lastModifiedById'] ?? null,
            'lastModifiedByName' => $legislative['lastModifiedByName'] ?? null,
            'lastModifiedAt' => $legislative['lastModifiedAt'] ?? null,
            'sent_to_user_ids' => $legislative['sent_to_user_ids'] ?? null,
            'sent_by_id' => $legislative['sent_by_id'] ?? null,
            'sent_by_name' => $legislative['sent_by_name'] ?? null,
            'sent_at' => $legislative['sent_at'] ?? null,
            'intake_classification' => $legislative['intake_classification'] ?? 'Legislative Matters',
            'routing_status' => $legislative['routing_status'] ?? 'For Agenda',
            'stream_type' => $legislative['stream_type'] ?? 'Approved Resolutions/Ordinances',
            'assigned_committee' => $legislative['assigned_committee'] ?? null,
            'date_referred' => $legislative['date_referred'] ?? null,
            'committee_report_status' => $legislative['committee_report_status'] ?? 'Pending',
            'hearing_date' => $legislative['hearing_date'] ?? null,
            'lce_action' => $legislative['lce_action'] ?? null,
            'lce_action_at' => $legislative['lce_action_at'] ?? null,
            'lce_action_by_id' => $legislative['lce_action_by_id'] ?? null,
            'archive_status' => $legislative['archive_status'] ?? null,
            'control_number' => $controlNumber,
            'received_at' => $legislative['received_at'] ?? date('Y-m-d H:i:s'),
            'origin' => $legislative['origin'] ?? null,
            'originating_office' => $legislative['originating_office'] ?? null,
            'assigned_to_id' => $legislative['assigned_to_id'] ?? null,
            'assigned_role' => $legislative['assigned_role'] ?? 'Secretariat',
            'signed_hash' => $legislative['signed_hash'] ?? null,
            'locked_at' => $legislative['locked_at'] ?? null,
            'signature_verified_at' => $legislative['signature_verified_at'] ?? null,
        ]);

        return $id;
    }

    public function updateLegislative(string $id, array $changes): bool
    {
        $existing = $this->findLegislative($id);
        if ($existing && !empty($existing['locked_at']) && (array_key_exists('content', $changes) || array_key_exists('file_path', $changes) || array_key_exists('title', $changes))) {
            throw new \RuntimeException('Signed and archived document versions are locked.');
        }
        $fields = [];
        $params = ['id' => $id];

        foreach ([
            'title', 'type', 'author_name', 'sponsor_name', 'ai_summary', 'status', 'desc', 'content', 'ref',
            'createdById', 'createdByName', 'createdByRole', 'createdAt',
            'updatedAt', 'file_name', 'file_size', 'file_type', 'file_path', 'fileRevisionHash', 'fileDiffSummary', 'fileChangeLog', 'documentHash',
            'lastModifiedById', 'lastModifiedByName', 'lastModifiedAt', 'workflow_status',
            'sent_to_user_ids', 'sent_by_id', 'sent_by_name', 'sent_at',
            'published_to_public', 'published_to_public_at', 'intake_classification', 'scan_file_path',
            'routing_status', 'stream_type', 'assigned_committee', 'date_referred',
            'committee_report_status', 'hearing_date', 'lce_action', 'lce_action_at',
            'lce_action_by_id', 'archive_status', 'admin_decision_notes', 'admin_decision_at',
            'control_number', 'received_at', 'origin', 'originating_office', 'assigned_to_id', 'assigned_role',
            'signed_hash', 'locked_at', 'signature_verified_at'
        ] as $field) {
            if (array_key_exists($field, $changes)) {
                $fields[] = "`$field` = :$field";
                $params[$field] = $changes[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $stmt = $this->pdo->prepare('UPDATE `legislatives` SET ' . implode(', ', $fields) . ' WHERE `id` = :id');
        return $stmt->execute($params);
    }

    public function deleteLegislative(string $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `legislatives` WHERE `id` = :id');
        $stmt->execute(['id' => $id]);
    }

    public function findLegislative(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `legislatives` WHERE `id` = :id', ['id' => $id]);
    }

    public function findLegislativeByFilePath(string $filePath): ?array
    {
        $fileName = basename($filePath);
        $document = $this->fetchOne('SELECT * FROM `legislatives` WHERE `file_path` = :file_path LIMIT 1', ['file_path' => $fileName]);
        if ($document) {
            return $document;
        }

        $events = $this->fetchAll(
            'SELECT `document_id`, `details` FROM `legislative_audit_events` WHERE `event_type` = :event_type ORDER BY `created_at` DESC',
            ['event_type' => 'file_upload_hash_recorded']
        );
        foreach ($events as $event) {
            $details = json_decode((string)($event['details'] ?? ''), true);
            if (!is_array($details) || basename((string)($details['file_path'] ?? '')) !== $fileName) {
                continue;
            }
            $document = $this->findLegislative((string)$event['document_id']);
            if ($document) {
                $document['fileRevisionHash'] = $details['sha256'] ?? null;
                return $document;
            }
        }

        return null;
    }

    public function findUser(string $id): ?array
    {
        return $this->getUserById($id);
    }

    public function findSession(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `sessions` WHERE `id` = :id', ['id' => $id]);
    }

    public function findAgenda(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `agendas` WHERE `id` = :id', ['id' => $id]);
    }

    // Voting APIs
    public function recordVote(string $agendaId, ?string $sessionId, string $userId, string $vote): string
    {
        $id = $this->uid();
        $stmt = $this->pdo->prepare('INSERT INTO `votes` (`id`, `agenda_id`, `session_id`, `user_id`, `vote`, `created_at`) VALUES (:id, :agenda_id, :session_id, :user_id, :vote, :created_at)');
        $stmt->execute([
            'id' => $id,
            'agenda_id' => $agendaId ?: null,
            'session_id' => $sessionId ?: null,
            'user_id' => $userId,
            'vote' => $vote,
            'created_at' => date(DATE_ATOM),
        ]);
        return $id;
    }

    public function getVotesByAgenda(string $agendaId): array
    {
        return $this->fetchAll('SELECT * FROM `votes` WHERE `agenda_id` = :agenda_id', ['agenda_id' => $agendaId]);
    }

    public function tallyVotesByAgenda(string $agendaId): array
    {
        $rows = $this->fetchAll('SELECT `vote`, COUNT(*) AS cnt FROM `votes` WHERE `agenda_id` = :agenda_id GROUP BY `vote`', ['agenda_id' => $agendaId]);
        $tally = ['FAVOR' => 0, 'AGAINST' => 0, 'ABSTAIN' => 0];
        foreach ($rows as $r) {
            $k = strtoupper($r['vote']);
            if (!isset($tally[$k])) $tally[$k] = 0;
            $tally[$k] = (int)$r['cnt'];
        }
        $tally['total'] = array_sum($tally);
        return $tally;
    }

    // Presence / attendance
    public function setPresence(string $sessionId, string $userId, ?string $deviceId = null): string
    {
        // prevent duplicate: if exists, update timestamp
        $existing = $this->fetchOne('SELECT * FROM `presence` WHERE `session_id` = :session_id AND `user_id` = :user_id', ['session_id' => $sessionId, 'user_id' => $userId]);
        $now = date(DATE_ATOM);
        if ($existing) {
            $stmt = $this->pdo->prepare('UPDATE `presence` SET `present_at` = :present_at, `device_id` = :device_id WHERE `id` = :id');
            $stmt->execute(['present_at' => $now, 'device_id' => $deviceId, 'id' => $existing['id']]);
            return $existing['id'];
        }
        $id = $this->uid();
        $stmt = $this->pdo->prepare('INSERT INTO `presence` (`id`, `session_id`, `user_id`, `device_id`, `present_at`) VALUES (:id, :session_id, :user_id, :device_id, :present_at)');
        $stmt->execute(['id' => $id, 'session_id' => $sessionId, 'user_id' => $userId, 'device_id' => $deviceId, 'present_at' => $now]);
        return $id;
    }

    public function requestAttendance(string $sessionId, string $userId, ?string $deviceId = null): array
    {
        $existing = $this->fetchOne(
            'SELECT * FROM `attendance_requests` WHERE `session_id` = :session_id AND `user_id` = :user_id AND `status` = "pending" ORDER BY `requested_at` DESC LIMIT 1',
            ['session_id' => $sessionId, 'user_id' => $userId]
        );
        if ($existing) {
            return $existing;
        }

        $id = $this->uid();
        $stmt = $this->pdo->prepare(
            'INSERT INTO `attendance_requests` (`id`, `session_id`, `user_id`, `device_id`, `status`, `requested_at`) VALUES (:id, :session_id, :user_id, :device_id, "pending", :requested_at)'
        );
        $stmt->execute([
            'id' => $id,
            'session_id' => $sessionId,
            'user_id' => $userId,
            'device_id' => $deviceId,
            'requested_at' => date(DATE_ATOM),
        ]);
        return $this->fetchOne('SELECT * FROM `attendance_requests` WHERE `id` = :id', ['id' => $id]) ?: [];
    }

    public function getAttendanceRequest(string $sessionId, string $userId): ?array
    {
        return $this->fetchOne(
            'SELECT r.*, s.title AS session_title, u.name, u.role FROM `attendance_requests` r LEFT JOIN `sessions` s ON s.id = r.session_id LEFT JOIN `users` u ON u.id = r.user_id WHERE r.session_id = :session_id AND r.user_id = :user_id ORDER BY r.requested_at DESC LIMIT 1',
            ['session_id' => $sessionId, 'user_id' => $userId]
        );
    }

    public function getPendingAttendanceRequests(): array
    {
        return $this->fetchAll(
            'SELECT r.*, s.title AS session_title, s.date, s.start, u.name, u.email FROM `attendance_requests` r LEFT JOIN `sessions` s ON s.id = r.session_id LEFT JOIN `users` u ON u.id = r.user_id WHERE r.status = "pending" ORDER BY r.requested_at ASC'
        );
    }

    public function decideAttendanceRequest(string $requestId, string $status, string $adminId): ?array
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            return null;
        }
        $request = $this->fetchOne('SELECT * FROM `attendance_requests` WHERE `id` = :id AND `status` = "pending"', ['id' => $requestId]);
        if (!$request) {
            return null;
        }

        $stmt = $this->pdo->prepare('UPDATE `attendance_requests` SET `status` = :status, `decided_at` = :decided_at, `decided_by` = :decided_by WHERE `id` = :id');
        $stmt->execute(['status' => $status, 'decided_at' => date(DATE_ATOM), 'decided_by' => $adminId, 'id' => $requestId]);
        if ($status === 'approved') {
            $this->setPresence($request['session_id'], $request['user_id'], $request['device_id']);
        }
        return $request;
    }

    public function getPresenceForSession(string $sessionId): array
    {
        return $this->fetchAll('SELECT p.*, u.name, u.role FROM `presence` p LEFT JOIN `users` u ON u.id = p.user_id WHERE p.session_id = :session_id', ['session_id' => $sessionId]);
    }

    // Annotations
    public function createAnnotation(array $annotation): string
    {
        $id = $this->uid();
        $stmt = $this->pdo->prepare('INSERT INTO `annotations` (`id`, `resource_type`, `resource_id`, `user_id`, `type`, `data`, `page`, `created_at`) VALUES (:id, :resource_type, :resource_id, :user_id, :type, :data, :page, :created_at)');
        $stmt->execute([
            'id' => $id,
            'resource_type' => $annotation['resource_type'],
            'resource_id' => $annotation['resource_id'],
            'user_id' => $annotation['user_id'],
            'type' => $annotation['type'],
            'data' => $annotation['data'],
            'page' => $annotation['page'] ?? null,
            'created_at' => date(DATE_ATOM),
        ]);
        return $id;
    }

    public function getAnnotationsForResource(string $resourceType, string $resourceId): array
    {
        return $this->fetchAll('SELECT a.*, u.name FROM `annotations` a LEFT JOIN `users` u ON u.id = a.user_id WHERE a.resource_type = :resource_type AND a.resource_id = :resource_id ORDER BY a.created_at ASC', ['resource_type' => $resourceType, 'resource_id' => $resourceId]);
    }

    public function createUidFileName(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9\.\-_]/', '_', basename($name));
        return sprintf('%s_%s', $this->uid(), $name);
    }

    // --- Analytics helpers ---
    public function getLegislativeStats(): array
    {
        $totalRow = $this->fetchOne('SELECT COUNT(*) AS cnt FROM `legislatives`');
        $approvedRow = $this->fetchOne('SELECT COUNT(*) AS cnt FROM `legislatives` WHERE `status` = :status', ['status' => 'approved']);
        $total = isset($totalRow['cnt']) ? (int)$totalRow['cnt'] : 0;
        $approved = isset($approvedRow['cnt']) ? (int)$approvedRow['cnt'] : 0;
        $percent = $total > 0 ? round(($approved / $total) * 100, 1) : 0.0;
        return ['total' => $total, 'approved' => $approved, 'percent_approved' => $percent];
    }

    public function getAttendanceStats(): array
    {
        // Count council members
        $councilCountRow = $this->fetchOne('SELECT COUNT(*) AS cnt FROM `users` WHERE `role` = :role', ['role' => 'council members']);
        $councilCount = isset($councilCountRow['cnt']) ? (int)$councilCountRow['cnt'] : 0;

        // For each session compute attendance
        $sessions = $this->getSessions();
        $stats = [];
        foreach ($sessions as $s) {
            $presence = $this->fetchAll('SELECT COUNT(DISTINCT user_id) AS cnt FROM `presence` WHERE `session_id` = :sid', ['sid' => $s['id']]);
            $present = isset($presence[0]['cnt']) ? (int)$presence[0]['cnt'] : 0;
            $percent = $councilCount > 0 ? round(($present / $councilCount) * 100, 1) : 0.0;
            $stats[] = [
                'session_id' => $s['id'],
                'title' => $s['title'] ?? '',
                'date' => $s['date'] ?? '',
                'present' => $present,
                'council_total' => $councilCount,
                'percent_attendance' => $percent,
            ];
        }
        return $stats;
    }

    public function getLatestNotification(string $userId): ?array
    {
        $user = $this->getUserById($userId);
        if (!$user || empty($user['notifications'])) return null;
        $decoded = json_decode($user['notifications'], true);
        if (!is_array($decoded) || count($decoded) === 0) return null;
        return end($decoded) ?: null;
    }

    public function markNotificationsRead(string $userId): bool
    {
        $user = $this->getUserById($userId);
        if (!$user) {
            error_log("markNotificationsRead: User not found: $userId");
            return false;
        }
        $notifications = [];
        if (!empty($user['notifications'])) {
            $decoded = json_decode($user['notifications'], true);
            if (!is_array($decoded)) {
                error_log("markNotificationsRead: Failed to decode JSON for user $userId: " . $user['notifications']);
                return false;
            }
            foreach ($decoded as &$n) {
                $n['read'] = true;
            }
            unset($n);
            $notifications = $decoded;
        }
        $newJson = json_encode($notifications);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("markNotificationsRead: Failed to encode JSON: " . json_last_error_msg());
            return false;
        }
        $stmt = $this->pdo->prepare('UPDATE `users` SET `notifications` = :notifications WHERE `id` = :id');
        $result = $stmt->execute(['notifications' => $newJson, 'id' => $userId]);
        if (!$result) {
            error_log("markNotificationsRead: DB update failed for user $userId. Errors: " . json_encode($stmt->errorInfo()));
            return false;
        }
        return true;
    }

    public function removeNotification(string $userId, string $notificationMessage): bool
    {
        $user = $this->getUserById($userId);
        if (!$user) {
            return false;
        }
        $notifications = [];
        if (!empty($user['notifications'])) {
            $decoded = json_decode($user['notifications'], true);
            if (!is_array($decoded)) {
                return false;
            }
            // Filter out the notification with matching message
            $notifications = array_filter($decoded, fn($n) => ($n['message'] ?? '') !== $notificationMessage);
            // Re-index array
            $notifications = array_values($notifications);
        }
        $newJson = json_encode($notifications);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        $stmt = $this->pdo->prepare('UPDATE `users` SET `notifications` = :notifications WHERE `id` = :id');
        return $stmt->execute(['notifications' => $newJson, 'id' => $userId]);
    }

    public function getAnalytics(): array
    {
        // Get monthly agenda approvals
        $monthlyApprovals = $this->fetchAll("
            SELECT 
                DATE_FORMAT(`uploaded_at`, '%Y-%m') AS month,
                COUNT(*) AS total,
                SUM(CASE WHEN `approval_status` = 'approved' THEN 1 ELSE 0 END) AS approved
            FROM `agendas`
            WHERE `uploaded_at` IS NOT NULL
            GROUP BY DATE_FORMAT(`uploaded_at`, '%Y-%m')
            ORDER BY month DESC
            LIMIT 12
        ");

        // Get monthly session attendance
        $monthlySessions = $this->fetchAll("
            SELECT 
                DATE_FORMAT(`date`, '%Y-%m') AS month,
                COUNT(*) AS total_sessions,
                SUM(
                    CASE 
                        WHEN `council_attendance` IS NOT NULL AND `council_attendance` > 0 
                        THEN `council_attendance` 
                        ELSE 0 
                    END
                ) AS total_present,
                SUM(
                    CASE 
                        WHEN `capacity` > 0 THEN `capacity` 
                        ELSE 0 
                    END
                ) AS total_capacity
            FROM `sessions`
            WHERE `date` IS NOT NULL
            GROUP BY DATE_FORMAT(`date`, '%Y-%m')
            ORDER BY month DESC
            LIMIT 12
        ");

        return [
            'monthlyApprovals' => array_reverse($monthlyApprovals),
            'monthlySessions' => array_reverse($monthlySessions),
        ];
    }
}
