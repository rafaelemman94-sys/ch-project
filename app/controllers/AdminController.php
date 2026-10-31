<?php
namespace App\Controllers;

use App\Models\AdminModel;
use App\Services\LegislativeContentFormatter;
use App\Services\LegislativeWorkflow;

class AdminController
{
    private AdminModel $model;

    public function __construct()
    {
        $this->model = new AdminModel();
    }

    public function handleRequest(): void
    {
        $this->synchronizeEndedSessionLceReviews();
        $action = $_POST['action'] ?? '';

        $workflowActions = [
            'receive_document', 'approve_agenda_po', 'collate_and_distribute_agenda', 'export_agenda_pdf',
            'process_session_outcomes', 'issue_award_certificate', 'save_committee_referral', 'save_committee_report',
            'edit_journal_transcript', 'finalize_journal_transcript', 'sign_journal_transcript',
            'sign_ordinance', 'sign_minutes',
            'save_journal_recording', 'export_ctfrb_report_pdf',
        ];
        $legacyLegislativeAction = $action === 'save_committee_referral'
            && !isset($_POST['document_id'])
            && isset($_POST['legislative_id']);
        if (in_array($action, $workflowActions, true) && !$legacyLegislativeAction) {
            $this->handleWorkflowAction($action);
            return;
        }

        if ($action === 'lookup_login_role') {
            $this->handleLoginRoleLookup();
            return;
        }

        if ($action === 'scan_legislative_integrity') {
            $this->handleLegislativeIntegrityScan();
            return;
        }

        if ($action === 'import_legislative_text') {
            $this->handleImportLegislativeText();
            return;
        }

        if ($action === 'login') {
            $this->handleLogin();
        }

        if ($action === 'logout') {
            $this->handleLogout();
        }

        if ($action === 'create_first_admin') {
            $this->handleCreateFirstAdmin();
        }

        if ($action === 'save_user') {
            $this->handleSaveUser();
        }

        if ($action === 'save_document') {
            $this->handleSaveDocument();
            return;
        }

        if ($action === 'save_session') {
            $this->handleSaveSession();
        }

        if ($action === 'end_session') {
            $this->handleEndSession();
            return;
        }

        if ($action === 'archive_completed_session') {
            $this->handleArchiveCompletedSession();
            return;
        }

        if ($action === 'save_agenda') {
            $this->handleSaveAgenda();
        }

        if ($action === 'save_ctfrb_report') {
            $this->handleSaveCtfrbReport();
        }

        if ($action === 'export_ctfrb_report') {
            $this->handleExportCtfrbReport();
            return;
        }

        if ($action === 'transcribe_audio') {
            $this->handleTranscribeAudio();
            return;
        }

        if ($action === 'save_recording') {
            $this->handleSaveRecording();
            return;
        }

        if ($action === 'upload_delivery_template') {
            $this->handleUploadDeliveryTemplate();
        }

        if ($action === 'publish_agenda') {
            $this->handlePublishAgenda();
        }

        if ($action === 'send_agenda_to_officer') {
            $this->handleSendAgendaToOfficer();
        }

        if ($action === 'approve_agenda') {
            $this->handleApproveAgenda();
        }

        if ($action === 'send_to_proceeding_officer') {
            $this->handleSendToProceedingOfficer();
        }

        if ($action === 'send_to_council') {
            $this->handleSendToCouncil();
        }

        if ($action === 'send_session_minutes') {
            $this->handleSendSessionMinutes();
        }

        if ($action === 'update_session_minutes_workflow') {
            $this->handleUpdateSessionMinutesWorkflow();
        }

        if ($action === 'send_legislative_to_recipients') {
            $this->handleSendLegislativeToRecipients();
        }

        if ($action === 'submit_legislative_to_admin') {
            $this->handleSubmitLegislativeToAdmin();
        }

        if ($action === 'prepare_submitted_bill_for_agenda') {
            $this->handlePrepareSubmittedBillForAgenda();
        }

        if ($action === 'create_agenda_from_legislative') {
            $this->handleCreateAgendaFromLegislative();
        }

        if ($action === 'approve_legislative') {
            $this->handleApproveLegislative();
        }
        if ($action === 'disapprove_legislative') {
            $this->handleDisapproveLegislative();
        }

        if ($action === 'process_lce_decision') {
            $this->handleProcessLceDecision();
        }

        if ($action === 'send_lce_approved_to_agenda') {
            $this->handleSendLceApprovedToAgenda();
        }

        if ($action === 'save_legislative') {
            $this->handleSaveLegislative();
        }

        if ($action === 'save_committee_referral') {
            $this->handleSaveCommitteeReferral();
        }

        if ($action === 'advance_legislative_workflow') {
            $this->handleAdvanceLegislativeWorkflow();
        }

        if ($action === 'assign_legislative_document') {
            $this->handleAssignLegislativeDocument();
        }

        if ($action === 'sign_legislative_document') {
            $this->handleSignLegislativeDocument();
        }

        if ($action === 'complete_legislative_filing_scan') {
            $this->handleCompleteLegislativeFilingScan();
        }

        if ($action === 'save_legislative_file_content') {
            $this->handleSaveLegislativeFileContent();
        }

        if ($action === 'publish_ordinance') {
            $this->handlePublishOrdinance();
        }

        if ($action === 'cast_vote') {
            $this->handleCastVote();
        }

        if ($action === 'poll_votes') {
            $this->handlePollVotes();
        }

        if ($action === 'set_presence') {
            $this->handleSetPresence();
        }

        if ($action === 'poll_presence') {
            $this->handlePollPresence();
        }

        if ($action === 'decide_attendance') {
            $this->handleDecideAttendance();
            return;
        }

        if ($action === 'save_annotation') {
            $this->handleSaveAnnotation();
        }
        if ($action === 'get_annotations') {
            $this->handleGetAnnotations();
        }
        if ($action === 'council_printed_agenda') {
            $this->handleCouncilPrintedAgenda();
        }

        if ($action === 'set_reading_stage') {
            $this->handleSetReadingStage();
        }

        if ($action === 'export_data') {
            $this->handleExportData();
        }
        if ($action === 'poll_analytics') {
            $this->handlePollAnalytics();
        }
        if ($action === 'mark_notifications_read') {
            $this->handleMarkNotificationsRead();
        }

        if ($action === 'remove_notification') {
            $this->handleRemoveNotification();
        }

        if ($action === 'delete_item') {
            $this->handleDelete();
        }
    }

    private function handleWorkflowAction(string $action): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $user = $this->getCurrentUser();
        $role = strtolower((string)($user['role'] ?? ''));
        $actionRoles = [
            'receive_document' => ['admin', 'secretary'],
            'approve_agenda_po' => ['admin', 'presiding officer'],
            'collate_and_distribute_agenda' => ['admin', 'secretary'],
            'export_agenda_pdf' => ['admin', 'secretary'],
            'process_session_outcomes' => ['admin', 'secretary', 'presiding officer'],
            'issue_award_certificate' => ['admin', 'secretary'],
            'save_committee_referral' => ['admin', 'secretary', 'council members'],
            'save_committee_report' => ['admin', 'secretary'],
            'edit_journal_transcript' => ['admin', 'secretary', 'stenographer', 'steno'],
            'finalize_journal_transcript' => ['admin', 'secretary', 'stenographer', 'steno'],
            'sign_journal_transcript' => ['admin', 'secretary', 'stenographer', 'steno'],
            'sign_ordinance' => ['admin', 'secretary', 'presiding officer'],
            'sign_minutes' => ['admin', 'secretary', 'council members', 'presiding officer'],
            'save_journal_recording' => ['admin', 'secretary', 'stenographer', 'steno'],
            'export_ctfrb_report_pdf' => ['ctrfb'],
        ];
        if (!$user || !in_array($role, $actionRoles[$action] ?? ['admin'], true)) {
            $this->workflowJson(false, 'UNAUTHORIZED', [], 'This role cannot perform workflow actions.', 403);
            return;
        }

        try {
            $data = match ($action) {
                'receive_document' => $this->workflowReceiveDocument($user),
                'approve_agenda_po' => $this->workflowApproveAgenda($user),
                'collate_and_distribute_agenda' => $this->workflowDistributeAgenda($user),
                'export_agenda_pdf' => $this->workflowExportDocument($user, 'AGENDA', 1),
                'process_session_outcomes' => $this->workflowSessionOutcome($user),
                'issue_award_certificate' => $this->workflowIssueAward($user),
                'save_committee_referral' => $this->workflowSaveReferral($user),
                'save_committee_report' => $this->workflowSaveCommitteeReport($user),
                'edit_journal_transcript' => $this->workflowEditTranscript($user),
                'finalize_journal_transcript' => $this->workflowUpdateStage($user, 'FINAL DRAFT'),
                'sign_journal_transcript' => $this->workflowSign($user, 'stenosec'),
                'sign_ordinance' => $this->workflowSign($user, trim((string)($_POST['signer_role'] ?? 'secretary'))),
                'sign_minutes' => $this->workflowSign($user, trim((string)($_POST['signer_role'] ?? 'secretary'))),
                'save_journal_recording' => $this->workflowSaveRecording($user),
                'export_ctfrb_report_pdf' => $this->workflowExportCtfrbPdf($user),
            };
            $this->workflowJson(true, (string)($data['stage'] ?? 'UPDATED'), $data);
        } catch (\InvalidArgumentException $exception) {
            $this->workflowJson(false, 'VALIDATION_ERROR', [], $exception->getMessage(), 422);
        } catch (\Throwable $exception) {
            $this->workflowJson(false, 'ERROR', [], $exception->getMessage(), 500);
        }
    }

    private function workflowJson(bool $success, string $stage, array $data = [], ?string $error = null, int $status = 200): void
    {
        http_response_code($status);
        $payload = ['success' => $success, 'stage' => $stage, 'data' => $data];
        if ($error !== null) {
            $payload['error'] = $error;
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    private function workflowRequiredDocument(array $user): array
    {
        $id = trim((string)($_POST['document_id'] ?? $_POST['id'] ?? ''));
        $document = $id !== '' ? $this->model->getWorkflowDocument($id) : null;
        if (!$document) {
            throw new \InvalidArgumentException('A valid document_id is required.');
        }
        return $document;
    }

    private function workflowReceiveDocument(array $user): array
    {
        $classification = trim((string)($_POST['classification'] ?? $_POST['intake_classification'] ?? 'Legislative Matters'));
        $allowed = ['Legislative Matters', 'Letters/Petitions', 'Executive Bills'];
        if (!in_array($classification, $allowed, true)) {
            throw new \InvalidArgumentException('Invalid intake classification.');
        }
        $forAgenda = filter_var($_POST['for_agenda'] ?? '1', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $forAgenda = $forAgenda === null ? true : $forAgenda;
        $stage = $forAgenda ? 'PREPARE AGENDA / NOTICE' : (($_POST['route'] ?? '') === 'posting' ? 'FILED' : 'TRANSMIT TO APPROPRIATE OFFICES');
        $metadata = ['recipient_office' => trim((string)($_POST['recipient_office'] ?? '')), 'transmitted_at' => date(DATE_ATOM), 'for_agenda' => $forAgenda];
        $id = $this->model->createWorkflowDocument([
            'title' => trim((string)($_POST['title'] ?? 'Received document')),
            'content' => (string)($_POST['content'] ?? ''),
            'workflow_type' => 'intake', 'workflow_stage' => $stage,
            'intake_classification' => $classification, 'metadata' => $metadata,
            'created_by_id' => $user['id'] ?? null, 'updated_by_id' => $user['id'] ?? null,
        ]);
        $data = ['document_id' => $id, 'classification' => $classification];
        if ($stage === 'FILED') {
            $certificateId = $this->model->createWorkflowRecord('posting_certificates', [
                'document_id' => $id, 'certificate_number' => 'POST-' . date('YmdHis'),
                'posted_at' => date('Y-m-d H:i:s'), 'posted_by_id' => $user['id'] ?? null,
                'filed_at' => date('Y-m-d H:i:s'),
            ]);
            $data['posting_certificate_id'] = $certificateId;
        }
        return ['stage' => $stage, 'document_id' => $id] + $data;
    }

    private function workflowApproveAgenda(array $user): array
    {
        $document = $this->workflowRequiredDocument($user);
        $approved = filter_var($_POST['approved'] ?? $_POST['approved_by_po'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $stage = $approved ? 'AGENDA' : 'REVISE AGENDA';
        $this->model->updateWorkflowDocument($document['id'], [
            'workflow_stage' => $stage,
            'metadata' => ['approved_by_po' => $approved, 'locked_for_edit' => !$approved, 'decision_at' => date(DATE_ATOM)],
            'updated_by_id' => $user['id'] ?? null,
        ]);
        return ['stage' => $stage, 'document_id' => $document['id'], 'locked_for_edit' => !$approved];
    }

    private function workflowDistributeAgenda(array $user): array
    {
        $document = $this->workflowRequiredDocument($user);
        $recipients = $_POST['recipient_ids'] ?? [];
        $recipients = is_array($recipients) ? $recipients : [$recipients];
        if (!$recipients) {
            throw new \InvalidArgumentException('At least one council member is required.');
        }
        foreach ($recipients as $recipientId) {
            $this->model->addUserNotification((string)$recipientId, 'A digital agenda package is ready.', 'index.php?page=agendas');
        }
        $this->model->updateWorkflowDocument($document['id'], ['workflow_stage' => 'DISTRIBUTE AGENDA', 'updated_by_id' => $user['id'] ?? null]);
        return ['stage' => 'DISTRIBUTE AGENDA', 'document_id' => $document['id'], 'recipient_count' => count($recipients)];
    }

    private function workflowSessionOutcome(array $user): array
    {
        $branch = strtoupper(trim((string)($_POST['branch'] ?? '')));
        $branches = ['A' => 'CTFRB', 'B' => 'AWARDING', 'C' => 'REFERRAL', 'D' => 'AUDIO RECORDING'];
        if (!isset($branches[$branch])) {
            throw new \InvalidArgumentException('Branch must be A, B, C, or D.');
        }
        if ($branch === 'B') {
            return $this->workflowIssueAward($user) + ['branch' => $branch];
        }
        if ($branch === 'C') {
            return $this->workflowSaveReferral($user) + ['branch' => $branch];
        }
        $document = $this->workflowRequiredDocument($user);
        $stage = $branches[$branch] === 'A' ? 'APPROVED TRICYCLE FRANCHISES (CTFRB)' : 'AUDIO RECORDING';
        $this->model->updateWorkflowDocument($document['id'], ['workflow_stage' => $stage, 'updated_by_id' => $user['id'] ?? null]);
        return ['stage' => $stage, 'document_id' => $document['id'], 'branch' => $branch, 'session_id' => $_POST['session_id'] ?? null];
    }

    private function workflowIssueAward(array $user): array
    {
        $type = trim((string)($_POST['award_type'] ?? ''));
        $allowed = ['Recognitions', 'Posthumous Awards', 'Accreditation of CSOs', 'Honest Drivers'];
        $recipient = trim((string)($_POST['recipient_name'] ?? ''));
        if (!in_array($type, $allowed, true) || $recipient === '') {
            throw new \InvalidArgumentException('Award type and recipient name are required.');
        }
        $awardId = $this->model->createWorkflowRecord('awards', [
            'document_id' => $_POST['document_id'] ?? null, 'award_type' => $type,
            'recipient_name' => $recipient, 'recipient_details' => trim((string)($_POST['recipient_details'] ?? '')),
            'issued_at' => date('Y-m-d H:i:s'), 'issued_by_id' => $user['id'] ?? null,
        ]);
        $pdf = $this->workflowCreatePdf('award-' . $awardId, 'AWARD CERTIFICATE', [
            '<h1>Certificate of ' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '</h1>',
            '<p>This certificate is awarded to</p><h2>' . htmlspecialchars($recipient, ENT_QUOTES, 'UTF-8') . '</h2>',
            '<p>' . nl2br(htmlspecialchars((string)($_POST['recipient_details'] ?? ''), ENT_QUOTES, 'UTF-8')) . '</p>',
            '<p>Issued ' . date('F j, Y') . '</p>',
        ], 1);
        $this->model->updateWorkflowRecord('awards', $awardId, ['certificate_path' => $pdf]);
        return ['stage' => 'AWARD CERTIFICATE ISSUED', 'award_id' => $awardId, 'pdf_path' => $pdf];
    }

    private function workflowExportCtfrbPdf(array $user): array
    {
        if (strtolower((string)($user['role'] ?? '')) !== 'ctrfb') {
            throw new \RuntimeException('Only CTFRB staff can export CTFRB records.');
        }
        $rows = [];
        foreach ($this->model->getCtfrbReports() as $report) {
            $rows[] = '<tr><td>' . htmlspecialchars((string)$report['applicant_name'], ENT_QUOTES, 'UTF-8') . '</td><td>' . htmlspecialchars((string)$report['address'], ENT_QUOTES, 'UTF-8') . '</td><td>' . htmlspecialchars((string)$report['franchise_number'], ENT_QUOTES, 'UTF-8') . '</td><td>' . htmlspecialchars((string)$report['application_type'], ENT_QUOTES, 'UTF-8') . '</td></tr>';
        }
        $pdf = $this->workflowCreatePdf('ctfrb-archive-' . date('YmdHis'), 'CTFRB ARCHIVE REPORT', ['<h1>Approved Tricycle Franchises</h1><table border="1" cellpadding="5"><tr><th>Applicant</th><th>Address</th><th>Franchise Number</th><th>Application Type</th></tr>' . implode('', $rows) . '</table>'], 1);
        return ['stage' => 'CTFRB ARCHIVE EXPORTED', 'pdf_path' => $pdf];
    }

    private function workflowSaveReferral(array $user): array
    {
        $document = $this->workflowRequiredDocument($user);
        $committee = trim((string)($_POST['committee'] ?? $_POST['assigned_committee'] ?? ''));
        if ($committee === '') {
            throw new \InvalidArgumentException('A designated committee is required.');
        }
        $id = $this->model->createWorkflowRecord('committee_referrals', [
            'document_id' => $document['id'], 'committee' => $committee,
            'referred_at' => date('Y-m-d H:i:s'), 'hearing_at' => ($_POST['hearing_at'] ?? null) ?: null,
            'status' => 'REFERRED TO APPROPRIATE COMMITTEES', 'notes' => trim((string)($_POST['notes'] ?? '')),
            'created_by_id' => $user['id'] ?? null,
        ]);
        $this->model->updateWorkflowDocument($document['id'], ['workflow_stage' => 'COMMITTEE HEARING', 'updated_by_id' => $user['id'] ?? null]);
        return ['stage' => 'COMMITTEE HEARING', 'document_id' => $document['id'], 'referral_id' => $id, 'committee' => $committee];
    }

    private function workflowSaveCommitteeReport(array $user): array
    {
        $document = $this->workflowRequiredDocument($user);
        $report = trim((string)($_POST['report_text'] ?? ''));
        if ($report === '') {
            throw new \InvalidArgumentException('Committee report text is required.');
        }
        $referralId = trim((string)($_POST['referral_id'] ?? ''));
        if ($referralId === '') {
            throw new \InvalidArgumentException('A referral_id is required.');
        }
        $id = $this->model->createWorkflowRecord('committee_reports', [
            'referral_id' => $referralId, 'report_text' => $report, 'status' => 'FINAL',
            'reported_at' => date('Y-m-d H:i:s'), 'created_by_id' => $user['id'] ?? null,
        ]);
        $this->model->updateWorkflowDocument($document['id'], ['workflow_stage' => 'FOR AGENDA', 'updated_by_id' => $user['id'] ?? null]);
        return ['stage' => 'FOR AGENDA', 'document_id' => $document['id'], 'committee_report_id' => $id];
    }

    private function workflowEditTranscript(array $user): array
    {
        $document = $this->workflowRequiredDocument($user);
        $content = trim((string)($_POST['transcript'] ?? $_POST['content'] ?? ''));
        if ($content === '') {
            throw new \InvalidArgumentException('Transcript content is required.');
        }
        $this->model->updateWorkflowDocument($document['id'], ['content' => $content, 'workflow_stage' => 'EDITING', 'updated_by_id' => $user['id'] ?? null]);
        return ['stage' => 'EDITING', 'document_id' => $document['id']];
    }

    private function workflowUpdateStage(array $user, string $stage): array
    {
        $document = $this->workflowRequiredDocument($user);
        $this->model->updateWorkflowDocument($document['id'], ['workflow_stage' => $stage, 'updated_by_id' => $user['id'] ?? null]);
        return ['stage' => $stage, 'document_id' => $document['id']];
    }

    private function workflowExportDocument(array $user, string $stage, int $copies): array
    {
        $document = $this->workflowRequiredDocument($user);
        $pdf = $this->workflowCreatePdf('document-' . $document['id'] . '-' . date('YmdHis'), $stage, [
            '<h1>' . htmlspecialchars((string)$document['title'], ENT_QUOTES, 'UTF-8') . '</h1>',
            '<p>' . nl2br(htmlspecialchars((string)$document['content'], ENT_QUOTES, 'UTF-8')) . '</p>',
        ], $copies);
        $this->model->updateWorkflowDocument($document['id'], ['workflow_stage' => $stage, 'updated_by_id' => $user['id'] ?? null]);
        return ['stage' => $stage, 'document_id' => $document['id'], 'repository_path' => $pdf, 'copies' => $copies];
    }

    private function workflowSign(array $user, string $signerRole): array
    {
        $document = $this->workflowRequiredDocument($user);
        $signerRole = strtolower($signerRole);
        if (!in_array($signerRole, ['secretary', 'council', 'presiding officer', 'stenosec', 'stenographer'], true)) {
            throw new \InvalidArgumentException('Invalid signer role.');
        }
        $id = $this->model->createWorkflowRecord('document_signatures', [
            'document_id' => $document['id'], 'signer_role' => $signerRole,
            'signer_id' => $user['id'] ?? '', 'signature' => $_POST['signature'] ?? null,
            'signed_at' => date('Y-m-d H:i:s'),
        ]);
        $stage = $signerRole === 'stenosec' || $signerRole === 'stenographer' ? 'FOR SIGNATURE STENOSEC' : 'SIGNED';
        $this->model->updateWorkflowDocument($document['id'], ['workflow_stage' => $stage, 'updated_by_id' => $user['id'] ?? null]);
        return ['stage' => $stage, 'document_id' => $document['id'], 'signature_id' => $id, 'signer_role' => $signerRole];
    }

    private function workflowSaveRecording(array $user): array
    {
        $audio = $_FILES['audio'] ?? null;
        if (!$audio || ($audio['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($audio['tmp_name'] ?? '')) {
            throw new \InvalidArgumentException('A valid audio recording is required.');
        }
        $extension = strtolower(pathinfo((string)($audio['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, ['webm', 'mp3', 'wav'], true)) {
            throw new \InvalidArgumentException('Audio must be WEBM, MP3, or WAV.');
        }
        $dir = __DIR__ . '/../../storage/uploads';
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            throw new \RuntimeException('Unable to create recording storage.');
        }
        $name = 'journal_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        if (!move_uploaded_file($audio['tmp_name'], $dir . DIRECTORY_SEPARATOR . $name)) {
            throw new \RuntimeException('Unable to save audio recording.');
        }
        $id = $this->model->createWorkflowDocument(['title' => $_POST['title'] ?? 'Session journal', 'content' => '', 'workflow_type' => 'journal', 'workflow_stage' => 'AUDIO RECORDING', 'metadata' => ['audio_path' => $name, 'session_id' => $_POST['session_id'] ?? null], 'created_by_id' => $user['id'] ?? null, 'updated_by_id' => $user['id'] ?? null]);
        return ['stage' => 'AUDIO RECORDING', 'document_id' => $id, 'audio_path' => $name];
    }

    private function workflowCreatePdf(string $name, string $label, array $blocks, int $copies): string
    {
        $autoload = dirname(__DIR__, 3) . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new \RuntimeException('PDF engine is not configured.');
        }
        require_once $autoload;
        $path = __DIR__ . '/../../storage/uploads/' . preg_replace('/[^A-Za-z0-9_-]/', '-', $name) . '.pdf';
        $pdf = new \TCPDF();
        $pdf->SetCreator('Kaya Portal');
        $pdf->SetTitle($label);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        for ($copy = 1; $copy <= $copies; $copy++) {
            $pdf->AddPage();
            $html = '<div style="text-align:center"><small>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ' - COPY ' . $copy . ' OF ' . $copies . '</small></div>' . implode('', $blocks);
            $pdf->writeHTML($html, true, false, true, false, '');
        }
        $pdf->Output($path, 'F');
        return basename($path);
    }

    private function handleLegislativeIntegrityScan(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $currentUser = $this->getCurrentUser();
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Authentication required.']);
            return;
        }

        $alerts = [];
        $isAdmin = $this->isAdminUser();
        $uploadDirectory = __DIR__ . '/../../storage/uploads';
        foreach ($this->model->getLegislatives() as $record) {
            if (empty($record['file_path'])) {
                continue;
            }
            if (!$isAdmin && (string)($record['createdById'] ?? '') !== (string)$currentUser['id']) {
                continue;
            }

            $filePath = $uploadDirectory . DIRECTORY_SEPARATOR . basename((string)$record['file_path']);
            $integrity = $this->model->verifyLegislativeFileIntegrity($record, $filePath);
            if (($integrity['newly_detected'] ?? false) === true) {
                $alerts[] = [
                    'control_number' => (string)($record['control_number'] ?? ''),
                    'title' => (string)($record['title'] ?? 'Untitled bill'),
                ];
            }
        }

        echo json_encode(['success' => true, 'alerts' => $alerts], JSON_UNESCAPED_UNICODE);
    }

    private function handleLoginRoleLookup(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['role' => null]);
            return;
        }

        $user = $this->model->findUserByEmail($email);
        $role = $user && ($user['status'] ?? '') === 'active' ? ($user['role'] ?? null) : null;

        echo json_encode(['role' => $role]);
    }

    private function handleImportLegislativeText(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Please sign in before importing a file.']);
            return;
        }

        $file = $_FILES['file'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Choose a readable file first.']);
            return;
        }
        if (($file['size'] ?? 0) > 10 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'File exceeds the 10MB limit.']);
            return;
        }

        $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        $mimeType = (string)($file['type'] ?? '');
        $allowedExtensions = ['docx', 'txt', 'rtf', 'pdf', 'json', 'xml', 'html', 'htm'];
        if (!in_array($extension, $allowedExtensions, true)) {
            echo json_encode(['success' => false, 'error' => 'This file type cannot be imported into the editor.']);
            return;
        }

        $workingPath = tempnam(sys_get_temp_dir(), 'legislative_import_');
        if ($workingPath === false || !copy((string)$file['tmp_name'], $workingPath)) {
            echo json_encode(['success' => false, 'error' => 'The uploaded file could not be read.']);
            return;
        }
        $extensionPath = $workingPath . '.' . $extension;
        rename($workingPath, $extensionPath);

        $content = $extension === 'docx'
            ? $this->extractAttachedHtml($extensionPath)
            : (in_array($extension, ['txt', 'rtf', 'json', 'xml', 'html', 'htm'], true)
                ? (string)@file_get_contents($extensionPath)
                : $this->extractAttachedText($extensionPath, $mimeType));
        if ($extension !== 'docx' && $content !== '') {
            $content = nl2br(htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }
        @unlink($extensionPath);

        if ($content === '') {
            $error = $extension === 'docx' && !class_exists('ZipArchive')
                ? 'DOCX import requires the PHP ZIP extension.'
                : 'No readable text was found in this file.';
            echo json_encode(['success' => false, 'error' => $error]);
            return;
        }

        echo json_encode(['success' => true, 'content' => $content]);
    }

    private function handleSaveUser(): void
    {
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = $_POST['role'] ?? 'user';
        if ($role === 'proceeding officer') {
            $role = 'presiding officer';
        }
        if ($role === 'mayor') {
            $role = 'lce';
        }
        $status = $_POST['status'] ?? 'active';
        $notes = trim($_POST['notes'] ?? '');

        if ($name === '' || $email === '') {
            $this->flash('error', 'Name and email are required.');
            $this->redirect('users', ['open' => 'user', 'type' => 'user', 'edit' => $id]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Enter a valid email address.');
            $this->redirect('users', ['open' => 'user', 'type' => 'user', 'edit' => $id]);
        }

        if ($password !== '' && !$this->isStrongPassword($password)) {
            $this->flash('error', 'Password must be at least 6 characters and include at least one special character.');
            $this->redirect('users', ['open' => 'user', 'type' => 'user', 'edit' => $id]);
        }

        $existing = array_filter($this->model->getUsers(), fn ($user) => $user['email'] === $email && $user['id'] !== $id);
        if (!$id && count($existing) > 0) {
            $this->flash('error', 'Email already exists.');
            $this->redirect('users', ['open' => 'user', 'type' => 'user']);
        }

        $payload = [
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'status' => $status,
            'notes' => $notes,
        ];

        if ($password !== '') {
            $payload['password'] = $password;
        }

        if ($id !== '') {
            $this->model->updateUser($id, $payload);
            $this->flash('success', 'User updated successfully.');
        } else {
            $id = $this->model->createUser($payload);
            if ($role === 'presiding officer') {
                $creator = $this->getCurrentUser();
                try {
                    $this->ensureUserSigningKey($id, $name, (string)($creator['id'] ?? ''), (string)($creator['name'] ?? 'Administrator'), 'agenda', 'Presiding Officer');
                    $this->flash('success', 'Presiding Officer created. Their signing key will download automatically on first login.');
                } catch (\Throwable $exception) {
                    error_log('Presiding Officer signing key creation failed: ' . $exception->getMessage());
                    $this->flash('warning', 'User created. The signing key will be generated on the Presiding Officer\'s first login.');
                }
            } else {
                $this->flash('success', 'User created successfully.');
            }
        }

        $this->redirect('users');
    }

    private function handleSaveDocument(): void
    {
        $isJson = str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json')
            || ($_POST['response_format'] ?? '') === 'json';

        if (!$this->isAuthenticated()) {
            $this->respondDocumentError('Please sign in before saving a document.', 401, $isJson);
        }

        $id = trim((string)($_POST['id'] ?? ''));
        $title = trim((string)($_POST['title'] ?? ''));
        $content = trim((string)($_POST['content'] ?? ''));
        $status = trim((string)($_POST['status'] ?? 'draft'));
        $authorId = trim((string)($_POST['author_id'] ?? '')) ?: null;
        $authorName = trim((string)($_POST['author_name'] ?? '')) ?: null;
        $sponsorId = trim((string)($_POST['sponsor_id'] ?? '')) ?: null;
        $sponsorName = trim((string)($_POST['sponsor_name'] ?? '')) ?: null;

        if ($title === '' || $content === '' || (!$authorId && !$authorName) || (!$sponsorId && !$sponsorName)) {
            $this->respondDocumentError('Title, content, author and sponsor are required.', 422, $isJson);
        }

        if (!in_array($status, ['draft', 'published'], true)) {
            $this->respondDocumentError('Select a valid document status.', 422, $isJson);
        }

        $fileData = [];
        $file = $_FILES['document_file'] ?? null;
        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
                $this->respondDocumentError('The document attachment could not be uploaded.', 422, $isJson);
            }
            if (($file['size'] ?? 0) > 10 * 1024 * 1024) {
                $this->respondDocumentError('Document attachments must be 10 MB or smaller.', 422, $isJson);
            }
            $extension = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, ['pdf', 'doc', 'docx', 'txt'], true)) {
                $this->respondDocumentError('Use a PDF, Word document, or text attachment.', 422, $isJson);
            }

            $uploadDir = __DIR__ . '/../../storage/uploads/documents';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $storedName = $this->model->createUidFileName((string)$file['name']);
            if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $storedName)) {
                $this->respondDocumentError('Unable to save the document attachment.', 500, $isJson);
            }
            $fileData = [
                'file_name' => basename((string)$file['name']),
                'file_path' => $storedName,
                'file_type' => (string)($file['type'] ?? 'application/octet-stream'),
                'file_size' => (int)($file['size'] ?? 0),
            ];
        }

        $payload = array_merge([
            'title' => $title, 'content' => $content, 'status' => $status,
            'author_id' => $authorId, 'author_name' => $authorName,
            'sponsor_id' => $sponsorId, 'sponsor_name' => $sponsorName,
            'updated_by_id' => $this->getCurrentUser()['id'] ?? null,
        ], $fileData);

        if ($id !== '') {
            if (!$this->model->getDocument($id)) {
                $this->respondDocumentError('Document not found.', 404, $isJson);
            }
            $this->model->updateDocument($id, $payload);
        } else {
            $id = $this->model->createDocument($payload);
        }

        if ($isJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'id' => $id, 'status' => $status]);
            return;
        }

        $this->flash('success', $status === 'published' ? 'Document published successfully.' : 'Document draft saved successfully.');
        $this->redirect('create-document', ['edit' => $id]);
    }

    private function respondDocumentError(string $message, int $statusCode, bool $isJson): void
    {
        if ($isJson) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => $message]);
            exit;
        }
        $this->flash('error', $message);
        $this->redirect('create-document');
    }

    private function handleEndSession(): void
    {
        $currentUser = $this->getCurrentUser();
        if (!$currentUser || ($currentUser['role'] ?? '') !== 'admin') {
            $this->flash('error', 'Only an admin can end a session.');
            $this->redirect('session-control');
        }

        $sessionId = trim((string)($_POST['session_id'] ?? ''));
        $session = $sessionId !== '' ? $this->model->findSession($sessionId) : null;
        if (!$session) {
            $this->flash('error', 'Session not found.');
            $this->redirect('session-control');
        }
        if (($session['status'] ?? '') === 'completed') {
            $this->synchronizeEndedSessionLceReviews();
            $this->flash('success', 'This session was already ended; eligible resolutions and ordinances have been processed for LCE review.');
            $this->redirect('session-control');
        }
        if (($session['status'] ?? '') !== 'ongoing') {
            $this->flash('error', 'Only an ongoing session can be ended.');
            $this->redirect('session-control');
        }
        if (!$this->model->updateSession($sessionId, ['status' => 'completed'])) {
            $this->flash('error', 'Unable to end this session. Please try again.');
            $this->redirect('session-control');
        }

        $routedCount = $this->synchronizeEndedSessionLceReviews();
        $this->flash('success', 'Session ended. ' . $routedCount . ' resolution/ordinance record(s) sent to the LCE.');
        $this->redirect('session-control');
    }

    private function handleArchiveCompletedSession(): void
    {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser || ($currentUser['role'] ?? '') !== 'admin') {
                $this->flash('error', 'Only an admin can remove a completed session from Session Control.');
                $this->redirect('session-control');
            }

            $sessionId = trim((string)($_POST['session_id'] ?? ''));
            $session = $sessionId !== '' ? $this->model->findSession($sessionId) : null;
            if (!$session || ($session['status'] ?? '') !== 'completed') {
                $this->flash('error', 'Only a completed session can be removed from Session Control.');
                $this->redirect('session-control');
            }
            if (!$this->model->updateSession($sessionId, ['status' => 'archived'])) {
                $this->flash('error', 'Unable to remove this session from Session Control.');
                $this->redirect('session-control');
            }

            $this->flash('success', 'Completed session removed from Session Control. Its history remains available in Sessions.');
            $this->redirect('session-control');
        }

    private function handleSaveSession(): void
    {
        $id = $_POST['id'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $date = $_POST['date'] ?? '';
        $startInput = trim((string)($_POST['start'] ?? ''));
        $endInput = trim((string)($_POST['end'] ?? ''));
        $start = $this->parseSessionTime($startInput) ?? $startInput;
        $end = $this->parseSessionTime($endInput) ?? $endInput;
        $location = trim($_POST['location'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 0);
        $status = $_POST['status'] ?? 'scheduled';
        $minutes = trim($_POST['minutes'] ?? '');

        $existingSession = $id !== '' ? $this->model->findSession((string)$id) : null;
        if ($existingSession
            && in_array($existingSession['minutes_workflow_status'] ?? '', ['AWAITING PRESIDING OFFICER', 'SIGNED AND SENT TO COUNCIL', 'FILE'], true)
            && $minutes !== trim((string)($existingSession['minutes'] ?? ''))) {
            $this->flash('error', 'Minutes cannot be changed while awaiting approval or after they have been signed and sent to Council.');
            $this->redirect('sessions');
            return;
        }

        if ($title === '' || $date === '' || $start === '' || $end === '') {
            $this->rememberSessionForm($id, $title, $desc, $date, $start, $end, $location, $capacity, $status, $minutes);
            $this->flash('error', 'Title, date and times are required.');
            $this->redirect('sessions', ['open' => 'session', 'type' => 'session', 'edit' => $id]);
        }

        if ($this->parseSessionTime($startInput) === null || $this->parseSessionTime($endInput) === null) {
            $this->rememberSessionForm($id, $title, $desc, $date, $startInput, $endInput, $location, $capacity, $status, $minutes);
            $this->flash('error', 'Enter session times in 12-hour format, such as 9:30 AM.');
            $this->redirect('sessions', ['open' => 'session', 'type' => 'session', 'edit' => $id]);
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Manila'));
        if ($date < $now->format('Y-m-d') || ($date === $now->format('Y-m-d') && $start <= $now->format('H:i'))) {
            $this->rememberSessionForm($id, $title, $desc, $date, $start, $end, $location, $capacity, $status, $minutes);
            $this->flash('error', 'The session start time must be in the future.');
            $this->redirect('sessions', ['open' => 'session', 'type' => 'session', 'edit' => $id]);
        }

        if ($start >= $end) {
            $this->rememberSessionForm($id, $title, $desc, $date, $start, $end, $location, $capacity, $status, $minutes);
            $this->flash('error', 'End time must be after start time.');
            $this->redirect('sessions', ['open' => 'session', 'type' => 'session', 'edit' => $id]);
        }

        $payload = [
            'title' => $title,
            'desc' => $desc,
            'date' => $date,
            'start' => $start,
            'end' => $end,
            'location' => $location,
            'capacity' => $capacity,
            'status' => $status,
        ];

        if (array_key_exists('minutes', $_POST)) {
            $payload['minutes'] = $minutes;
            $payload['minutes_recorded_at'] = $minutes !== '' ? date(DATE_ATOM) : null;
        }

        unset($_SESSION['session_form_input']);

        if ($id !== '') {
            $this->model->updateSession($id, $payload);
            $this->flash('success', 'Session updated successfully.');
        } else {
            $this->model->createSession($payload);
            $this->flash('success', 'Session scheduled successfully.');
        }

        $this->redirect('sessions');
    }

    private function handleSaveCtfrbReport(): void
    {
        $currentUser = $this->getCurrentUser();
        if (!$currentUser || ($currentUser['role'] ?? '') !== 'ctrfb') {
            $this->flash('error', 'Only CTRFB staff can manage CTFRB reports.');
            $this->redirect('ctfrb-report');
        }

        $id = trim((string)($_POST['id'] ?? ''));
        $applicantName = trim((string)($_POST['applicant_name'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $franchiseNumber = trim((string)($_POST['franchise_number'] ?? ''));
        $applicationType = trim((string)($_POST['application_type'] ?? ''));
        $allowedTypes = ['Renewal', 'Change Unit', 'New', 'Renewal/Transfer', 'Renewal/Change Unit'];

        if ($applicantName === '' || $address === '' || $franchiseNumber === '' || $applicationType === '') {
            $this->flash('error', 'Applicant name, address, franchise number and application type are required.');
            $this->redirect('ctfrb-report', ['open' => 'ctfrb-report', 'type' => 'ctfrb-report', 'edit' => $id]);
        }
        if (!in_array($applicationType, $allowedTypes, true)) {
            $this->flash('error', 'Select a valid CTFRB application type.');
            $this->redirect('ctfrb-report', ['open' => 'ctfrb-report', 'type' => 'ctfrb-report', 'edit' => $id]);
        }

        $payload = [
            'applicant_name' => $applicantName,
            'address' => $address,
            'franchise_number' => $franchiseNumber,
            'application_type' => $applicationType,
        ];
        if ($id !== '') {
            if (!$this->model->getCtfrbReport($id)) {
                $this->flash('error', 'CTFRB report entry not found.');
                $this->redirect('ctfrb-report');
            }
            $this->model->updateCtfrbReport($id, $payload);
            $this->flash('success', 'CTFRB report entry updated successfully.');
        } else {
            $this->model->createCtfrbReport($payload);
            $this->flash('success', 'CTFRB report entry created successfully.');
        }
        $this->redirect('ctfrb-report');
    }

    private function handleExportCtfrbReport(): void
    {
        $currentUser = $this->getCurrentUser();
        if (!$currentUser || ($currentUser['role'] ?? '') !== 'ctrfb') {
            http_response_code(403);
            echo 'Unauthorized';
            return;
        }

        $reports = $this->model->getCtfrbReports();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ctfrb-report-' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Applicant Name', 'Purok/Barangay Address', 'Franchise Number', 'Application Type']);
        foreach ($reports as $report) {
            fputcsv($output, [$report['applicant_name'], $report['address'], $report['franchise_number'], $report['application_type']]);
        }
        fclose($output);
    }

    private function handleUploadDeliveryTemplate(): void
    {
        $currentUser = $this->getCurrentUser();
        if (!$currentUser || !$this->isAdminUser()) {
            $this->flash('error', 'Unauthorized.');
            $this->redirect('agendas');
            return;
        }

        $file = $_FILES['template_file'] ?? null;
        $name = trim($_POST['template_name'] ?? '');
        $templateType = trim($_POST['template_type'] ?? 'general');
        $notes = trim($_POST['template_notes'] ?? '');

        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Please select a template file to upload.');
            $this->redirect('agendas');
            return;
        }

        if ($name === '') {
            $name = pathinfo($file['name'], PATHINFO_FILENAME);
        }

        $uploadDir = __DIR__ . '/../../storage/uploads/templates';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $targetName = $this->model->createUidFileName($file['name']);
        $targetPath = $uploadDir . '/' . $targetName;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->flash('error', 'Unable to save template file.');
            $this->redirect('agendas');
            return;
        }

        $this->model->createDeliveryTemplate([
            'name' => $name,
            'file_name' => $file['name'],
            'file_path' => $targetName,
            'template_type' => $templateType,
            'uploaded_by_id' => $currentUser['id'] ?? null,
            'uploaded_by_name' => $currentUser['name'] ?? null,
            'notes' => $notes,
        ]);

        $this->flash('success', 'Template uploaded successfully.');
        $this->redirect('agendas');
    }

    private function handleTranscribeAudio(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->getCurrentUser()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Please sign in before transcribing audio.']);
            return;
        }

        if (!$this->isAdminUser()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Only administrators can transcribe audio.']);
            return;
        }

        $audio = $_FILES['audio'] ?? null;
        $allowedTypes = [
            'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav', 'audio/mp4',
            'audio/x-m4a', 'audio/ogg', 'audio/webm', 'audio/flac',
        ];
        $config = require __DIR__ . '/../config.php';
        $maxMegabytes = max(1, (int)($config['ai']['transcription_max_mb'] ?? 100));
        $maxBytes = $maxMegabytes * 1024 * 1024;

        if (!$audio || ($audio['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Warning: the audio upload is missing or could not be read. Please choose the file again.']);
            return;
        }

        if (!is_uploaded_file($audio['tmp_name'] ?? '') || !is_file($audio['tmp_name']) || ($audio['size'] ?? 0) <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Warning: this is not a valid audio upload. Please choose a real audio file.']);
            return;
        }

        if (($audio['size'] ?? 0) > $maxBytes) {
            http_response_code(413);
            echo json_encode(['success' => false, 'error' => 'Warning: the audio file is too large. The maximum size is ' . $maxMegabytes . ' MB.']);
            return;
        }

        $mimeType = strtolower((string)(new \finfo(FILEINFO_MIME_TYPE))->file($audio['tmp_name']));
        if (!in_array($mimeType, $allowedTypes, true)) {
            http_response_code(415);
            echo json_encode(['success' => false, 'error' => 'Warning: invalid audio format. Use MP3, WAV, M4A, OGG, WEBM, or FLAC.']);
            return;
        }

        $apiKey = $config['ai']['api_key'] ?? getenv('OPENAI_API_KEY') ?: '';
        $endpoint = $config['ai']['transcription_endpoint'] ?? 'https://api.openai.com/v1/audio/transcriptions';
        $model = $config['ai']['transcription_model'] ?? 'gpt-4o-mini-transcribe';
        if ($apiKey === '') {
            http_response_code(503);
            echo json_encode(['success' => false, 'error' => 'Transcription is not configured. Add an API key in app/config.php or set OPENAI_API_KEY.']);
            return;
        }

        $curlFile = new \CURLFile($audio['tmp_name'], $mimeType, $audio['name']);
        $curl = curl_init($endpoint);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'file' => $curlFile,
                'model' => $model,
                'response_format' => 'text',
            ],
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
            CURLOPT_TIMEOUT => 120,
        ]);
        $response = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($response === false || $status < 200 || $status >= 300) {
            http_response_code(502);
            echo json_encode(['success' => false, 'error' => $curlError ?: 'The transcription service could not process this file.']);
            return;
        }

        $result = json_decode($response, true);
        $text = is_array($result)
            ? trim((string)($result['text'] ?? ''))
            : trim($response);
        if ($text === '') {
            http_response_code(502);
            echo json_encode(['success' => false, 'error' => 'The transcription service returned no text.']);
            return;
        }

        echo json_encode(['success' => true, 'text' => $text]);
    }

    private function handleSaveRecording(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->getCurrentUser()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Please sign in before saving a recording.']);
            return;
        }

        $audio = $_FILES['audio'] ?? null;
        $transcript = trim((string)($_POST['transcript'] ?? ''));
        $maxBytes = 100 * 1024 * 1024;
        $allowedTypes = [
            'audio/webm' => 'webm',
            'video/webm' => 'webm',
            'audio/ogg' => 'ogg',
            'audio/mp4' => 'm4a',
            'audio/mpeg' => 'mp3',
            'audio/wav' => 'wav',
            'audio/x-wav' => 'wav',
        ];

        if (!$audio || ($audio['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($audio['tmp_name'] ?? '')) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'The audio recording could not be uploaded.']);
            return;
        }

        if (($audio['size'] ?? 0) <= 0 || ($audio['size'] ?? 0) > $maxBytes) {
            http_response_code(413);
            echo json_encode(['success' => false, 'error' => 'The audio recording is empty or larger than 100 MB.']);
            return;
        }

        $mimeType = strtolower((string)(new \finfo(FILEINFO_MIME_TYPE))->file($audio['tmp_name']));
        if (!isset($allowedTypes[$mimeType])) {
            http_response_code(415);
            echo json_encode(['success' => false, 'error' => 'Unsupported audio recording format.']);
            return;
        }

        $uploadDir = __DIR__ . '/../../storage/uploads';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Unable to create the recording storage folder.']);
            return;
        }

        $baseName = 'recording_' . date('Ymd_His') . '_' . bin2hex(random_bytes(5));
        $audioName = $baseName . '.' . $allowedTypes[$mimeType];
        $textName = $baseName . '.txt';
        $audioPath = $uploadDir . DIRECTORY_SEPARATOR . $audioName;
        $textPath = $uploadDir . DIRECTORY_SEPARATOR . $textName;

        if (!move_uploaded_file($audio['tmp_name'], $audioPath) || file_put_contents($textPath, $transcript) === false) {
            if (is_file($audioPath)) {
                unlink($audioPath);
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Unable to save the recording and transcript.']);
            return;
        }

        echo json_encode([
            'success' => true,
            'audio_url' => 'download.php?file=' . rawurlencode($audioName) . '&inline=1',
            'audio_download_url' => 'download.php?file=' . rawurlencode($audioName),
            'transcript_download_url' => 'download.php?file=' . rawurlencode($textName),
        ]);
    }

    private function handleSaveAgenda(): void
    {
        $title = trim($_POST['title'] ?? '');
        $sessionId = $_POST['session_id'] ?? '';
        $notifyCouncil = isset($_POST['notify_council']) && ($_POST['notify_council'] === '1' || $_POST['notify_council'] === 'true');
        $publish = isset($_POST['publish']) && ($_POST['publish'] === '1' || $_POST['publish'] === 'true');
        $file = $_FILES['file'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Please select a file to upload.');
            $this->redirect('agendas', ['open' => 'agenda']);
        }

        if ($notifyCouncil && $sessionId === '') {
            $this->flash('error', 'Please choose a session to notify council members when uploading.');
            $this->redirect('agendas', ['open' => 'agenda']);
        }

        if ($notifyCouncil) {
            $publish = true;
        }

        // If no title provided, use the uploaded file name (without extension)
        if ($title === '') {
            $title = pathinfo($file['name'], PATHINFO_FILENAME);
        }

        ini_set('upload_max_filesize', '0');
        ini_set('post_max_size', '0');

        $uploadDir = __DIR__ . '/../../storage/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $targetName = $this->model->createUidFileName($file['name']);
        $targetPath = $uploadDir . '/' . $targetName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->flash('error', 'Unable to save uploaded file.');
            $this->redirect('agendas', ['open' => 'agenda']);
        }

        $agenda = [
            'title' => $title,
            'file_name' => $file['name'],
            'file_size' => $file['size'],
            'file_type' => $file['type'] ?: mime_content_type($targetPath),
            'session_id' => $sessionId !== '' ? $sessionId : null,
            'file_path' => $targetName,
            'published' => $publish ? 1 : 0,
            'published_at' => $publish ? date(DATE_ATOM) : null,
        ];

        $agendaId = $this->model->createAgenda($agenda);

        // Attempt to detect reading stage from filename/title (basic heuristic)
        $reading = null;
        $lower = strtolower($file['name'] ?? $title);
        if (str_contains($lower, 'first') || str_contains($lower, '1st') || str_contains($lower, 'first reading')) {
            $reading = 'first_reading';
        } elseif (str_contains($lower, 'second') || str_contains($lower, '2nd') || str_contains($lower, 'second reading')) {
            $reading = 'second_reading';
        }
        if ($reading !== null) {
            // store detected reading stage separately from approval workflow
            $this->model->updateAgenda($agendaId, ['reading_stage' => $reading]);
        }

        // If admin requested notify on upload and the agenda is published, send to council now
        $notify = isset($_POST['notify_council']) && ($_POST['notify_council'] === '1' || $_POST['notify_council'] === 'true');
        if ($notify && $agenda['published']) {
            $councilMembers = array_filter($this->model->getData()['users'], fn($u) => $u['role'] === 'council members');
            if (count($councilMembers) > 0) {
                $changes = [
                    'approval_stage' => 'council_notified',
                    'sent_to_council_at' => date(DATE_ATOM),
                    'approval_status' => 'published_to_council',
                ];
                $this->model->updateAgenda($agendaId, $changes);
                foreach ($councilMembers as $member) {
                    $notificationText = sprintf('Agenda "%s" has been uploaded and published for session %s.', $agenda['title'], $agenda['session_id'] ?? '');
                    $this->model->addUserNotification($member['id'], $notificationText, 'index.php?page=tablet');
                    if (!empty($member['email']) && filter_var($member['email'], FILTER_VALIDATE_EMAIL)) {
                        $this->sendNotificationEmail($member['email'], $agenda['title'], [], $agenda['file_path']);
                    }
                }
            }
        }

        // detect JSON / XHR requests
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isXhr = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
        $wantsJson = str_contains($accept, 'application/json') || $isXhr;

        if ($wantsJson) {
            $downloadUrl = 'download.php?file=' . urlencode($targetName);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'id' => $agendaId,
                'file_path' => $targetName,
                'download_url' => $downloadUrl,
                'published' => $agenda['published'] ? 1 : 0,
                'published_at' => $agenda['published_at'] ?? null,
            ]);
            return;
        }

        $totalAgendas = count($this->model->getAgendas());
        $this->flash('success', 'Agenda uploaded successfully. Total agendas uploaded: ' . $totalAgendas . '.');

        $currentUser = $this->getCurrentUser();
        if ($currentUser) {
            $this->model->addUserNotification($currentUser['id'], 'Agenda uploaded. Total agendas uploaded: ' . $totalAgendas . '.', 'index.php?page=agendas');
        }

        $this->redirect('agendas');
    }

    private function handleLogin(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $mode = $_POST['mode'] ?? 'admin';

        if ($email === '' || $password === '') {
            $this->flash('error', 'Email and password are required.');
            $this->redirect('login');
        }

        $user = $this->model->findUserByEmail($email);
        if (!$user || !$this->model->verifyPassword($user, $password)) {
            $this->flash('error', 'Invalid email or password.');
            $this->redirect('login');
        }

        if ($user['status'] !== 'active') {
            $this->flash('error', 'Your account is inactive or suspended.');
            $this->redirect('login');
        }

        if ($mode === 'admin' && !in_array($user['role'], ['admin'], true)) {
            $this->flash('error', 'This account does not have admin access. Use User Login instead.');
            $this->redirect('login');
        }

        if ($mode !== 'admin' && in_array($user['role'], ['admin'], true)) {
            $this->flash('error', 'Admin accounts must use Admin Login.');
            $this->redirect('login');
        }

        $isLceRoleMatch = $mode === 'lce' && in_array($user['role'], ['lce', 'mayor'], true);
        if ($mode !== 'admin' && $mode !== 'user' && $user['role'] !== $mode && !$isLceRoleMatch) {
            $this->flash('error', 'Login role mismatch. Please select the correct role for this account.');
            $this->redirect('login');
        }

        $_SESSION['user_id'] = $user['id'];

        if ($user['role'] === 'presiding officer') {
            try {
                $officerKey = $this->ensureUserSigningKey(
                    (string)$user['id'],
                    (string)$user['name'],
                    (string)$user['id'],
                    (string)$user['name'],
                    'agenda',
                    'Presiding Officer'
                );
                if (empty($officerKey['private_key_delivered_at'])) {
                    $_SESSION['officer_key_download_id'] = (string)$officerKey['id'];
                }
            } catch (\Throwable $exception) {
                error_log('Presiding Officer signing key setup failed: ' . $exception->getMessage());
                $this->flash('error', 'Your account is signed in, but its signing key could not be prepared. Contact an administrator.');
            }
        } elseif (in_array($user['role'], ['lce', 'mayor'], true)) {
            try {
                $lceKey = $this->ensureUserSigningKey(
                    (string)$user['id'],
                    (string)$user['name'],
                    (string)$user['id'],
                    (string)$user['name'],
                    'legislative',
                    'LCE Legislative'
                );
                if (empty($lceKey['private_key_delivered_at'])) {
                    $_SESSION['lce_key_download_id'] = (string)$lceKey['id'];
                }
            } catch (\Throwable $exception) {
                error_log('LCE signing key setup failed: ' . $exception->getMessage());
                $this->flash('error', 'Your account is signed in, but its signing key could not be prepared. Contact an administrator.');
            }
        }

        $this->flash('success', 'Welcome back!');
        $this->redirect('dashboard');
    }

    private function findCurrentSessionForUser(): ?array
    {
        $today = date('Y-m-d');
        $now = date('H:i');
        $sessions = $this->model->getSessions();
        foreach ($sessions as $session) {
            if ($session['date'] === $today && $session['start'] <= $now && $session['end'] >= $now && $session['status'] === 'ongoing') {
                return $session;
            }
        }
        return null;
    }

    private function findSessionForTablet(): ?array
    {
        $today = date('Y-m-d');
        $now = date('H:i');
        $sessions = $this->model->getSessions();
        $candidates = [];

        foreach ($sessions as $session) {
            if ($session['date'] !== $today) {
                continue;
            }
            if (!in_array($session['status'], ['ongoing', 'scheduled'], true)) {
                continue;
            }
            if ($session['end'] < $now) {
                continue;
            }
            $candidates[] = $session;
        }

        if (count($candidates) === 0) {
            return null;
        }

        usort($candidates, function ($a, $b) use ($now) {
            if ($a['status'] === 'ongoing' && $b['status'] !== 'ongoing') {
                return -1;
            }
            if ($b['status'] === 'ongoing' && $a['status'] !== 'ongoing') {
                return 1;
            }
            return strcmp($a['start'], $b['start']);
        });

        return $candidates[0];
    }

    private function handleLogout(): void
    {
        unset($_SESSION['user_id']);
        $this->flash('success', 'You have been logged out.');
        $this->redirect('login');
    }

    private function handleCreateFirstAdmin(): void
    {
        if ($this->model->hasUsers()) {
            $this->redirect('login');
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($name === '' || $email === '' || $password === '') {
            $this->flash('error', 'Name, email, and password are required.');
            $this->redirect('login');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Enter a valid email address.');
            $this->redirect('login');
        }

        if (!$this->isStrongPassword($password)) {
            $this->flash('error', 'Password must be at least 6 characters and include at least one special character.');
            $this->redirect('login');
        }

        $this->model->createUser([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 'admin',
            'status' => 'active',
            'notes' => 'Initial administrator account'
        ]);

        $this->flash('success', 'Administrator account created. Please log in.');
        $this->redirect('login');
    }

    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 6 && preg_match('/[^a-zA-Z0-9]/', $password) === 1;
    }

    private function isTextPreviewableMime(string $mimeType): bool
    {
        $mimeType = strtolower($mimeType);
        return str_starts_with($mimeType, 'text/')
            || $mimeType === 'application/pdf'
            || in_array($mimeType, ['application/json', 'application/xml', 'application/javascript', 'application/x-javascript', 'application/xhtml+xml', 'application/x-php', 'application/php', 'application/msword', 'application/vnd.ms-word', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], true);
    }

    private function isOfficeDocumentMime(string $mimeType): bool
    {
        $mimeType = strtolower($mimeType);
        return in_array($mimeType, ['application/msword', 'application/vnd.ms-word', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], true);
    }

    private function extractAttachedText(string $filePath, string $mimeType): string
    {
        $mimeType = strtolower($mimeType);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (str_starts_with($mimeType, 'text/') || in_array($mimeType, ['application/json', 'application/xml', 'application/javascript', 'application/x-javascript', 'application/xhtml+xml', 'application/x-php', 'application/php'], true)) {
            $content = @file_get_contents($filePath);
            return $content === false ? '' : $content;
        }

        if ($extension === 'pdf' && function_exists('shell_exec')) {
            $command = 'where pdftotext 2>NUL';
            $binary = trim((string)shell_exec($command));
            if ($binary !== '') {
                $output = shell_exec('pdftotext -layout ' . escapeshellarg($filePath) . ' - 2>NUL');
                return is_string($output) ? trim($output) : '';
            }
        }

        if ($extension === 'docx') {
            if (!class_exists('ZipArchive')) {
                return '';
            }

            $zip = new \ZipArchive();
            if ($zip->open($filePath) !== true) {
                return '';
            }

            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            if ($xml === false) {
                return '';
            }

            preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/', $xml, $matches);
            $texts = [];
            foreach ($matches[1] ?? [] as $match) {
                $texts[] = html_entity_decode(strip_tags($match), ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
            return trim(implode("\n", $texts));
        }

        return '';
    }

    private function detectDocumentTitle(string $content, string $fileName = ''): string
    {
        $plainText = html_entity_decode(strip_tags(preg_replace('/<\/(p|h[1-6]|br)>/i', "\n", $content)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lines = preg_split('/\R+/', $plainText) ?: [];
        $fallback = trim(pathinfo($fileName, PATHINFO_FILENAME));

        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/', ' ', $line));
            if ($line === '' || preg_match('/^(LEGISLATIVE RECORD|BILL CONTENT|={3,}|-{3,})$/i', $line)) {
                continue;
            }
            if (preg_match('/^title\s*:\s*(.+)$/i', $line, $match)) {
                return trim($match[1]);
            }
            if (preg_match('/^(AN|A)\s+(ACT|BILL|ORDINANCE|RESOLUTION)\b/i', $line)) {
                return mb_substr($line, 0, 255);
            }
            if (mb_strlen($line) >= 3 && mb_strlen($line) <= 255 && !preg_match('/^(TYPE|STATUS|REFERENCE|DESCRIPTION|CREATED|UPDATED|SECTION\s+\d+)/i', $line)) {
                return $line;
            }
        }

        return $fallback !== '' ? mb_substr($fallback, 0, 255) : '';
    }

    private function generateLegislativeSummary(string $content, string $author, string $sponsor): string
    {
        $plainText = trim(html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($plainText === '') {
            return '';
        }

        $config = require __DIR__ . '/../config.php';
        $apiKey = trim((string)($config['ai']['api_key'] ?? getenv('OPENAI_API_KEY') ?: ''));
        if ($apiKey !== '') {
            $payload = json_encode([
                'model' => $config['ai']['summary_model'] ?? 'gpt-4o-mini',
                'messages' => [[
                    'role' => 'user',
                    'content' => "Summarize this legislative document in 3 concise sentences. Include its purpose, main action, author, and sponsor. Do not invent details.\nAuthor: " . ($author ?: 'Not stated') . "\nSponsor: " . ($sponsor ?: 'Not stated') . "\nDocument:\n" . mb_substr($plainText, 0, 30000),
                ]],
                'temperature' => 0.2,
            ]);
            $curl = curl_init('https://api.openai.com/v1/chat/completions');
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
            ]);
            $response = curl_exec($curl);
            curl_close($curl);
            $decoded = is_string($response) ? json_decode($response, true) : null;
            $summary = trim((string)($decoded['choices'][0]['message']['content'] ?? ''));
            if ($summary !== '') {
                return $summary;
            }
        }

        $sentences = preg_split('/(?<=[.!?])\s+/', preg_replace('/\s+/', ' ', $plainText)) ?: [];
        $fallback = implode(' ', array_slice($sentences, 0, 3));
        $people = 'Author: ' . ($author ?: 'Not stated') . '; Sponsor: ' . ($sponsor ?: 'Not stated') . '.';
        return trim($fallback . ' ' . $people);
    }

    private function extractAttachedHtml(string $filePath): string
    {
        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) !== 'docx' || !class_exists('ZipArchive')) {
            return '';
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return '';
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false) {
            return '';
        }

        preg_match_all('/<w:p\b[^>]*>(.*?)<\/w:p>/s', $xml, $paragraphMatches);
        $paragraphs = [];
        foreach ($paragraphMatches[1] ?? [] as $paragraphXml) {
            $isHeading = preg_match('/<w:pStyle\b[^>]*w:val="Heading([1-6])"/i', $paragraphXml, $headingMatch);
            $runs = [];
            preg_match_all('/<w:r\b[^>]*>(.*?)<\/w:r>/s', $paragraphXml, $runMatches);
            foreach ($runMatches[1] ?? [] as $runXml) {
                preg_match_all('/<w:t\b[^>]*>(.*?)<\/w:t>/s', $runXml, $textMatches);
                $text = '';
                foreach ($textMatches[1] ?? [] as $textPart) {
                    $text .= html_entity_decode($textPart, ENT_QUOTES | ENT_XML1, 'UTF-8');
                }
                if (preg_match('/<w:tab\b/i', $runXml)) {
                    $text = "\t" . $text;
                }
                if (preg_match('/<w:br\b/i', $runXml)) {
                    $text .= '<br>';
                }
                $formatted = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                if (preg_match('/<w:b(?:\s|\/?>)/i', $runXml)) {
                    $formatted = '<strong>' . $formatted . '</strong>';
                }
                if (preg_match('/<w:i(?:\s|\/?>)/i', $runXml)) {
                    $formatted = '<em>' . $formatted . '</em>';
                }
                if (preg_match('/<w:u(?:\s|\/?>)/i', $runXml)) {
                    $formatted = '<u>' . $formatted . '</u>';
                }
                $runs[] = $formatted;
            }
            $paragraphText = implode('', $runs);
            $tag = $isHeading ? 'h' . $headingMatch[1] : 'p';
            $paragraphs[] = '<' . $tag . '>' . $paragraphText . '</' . $tag . '>';
        }

        return implode('', $paragraphs);
    }

    private function buildLocalLegislativeTemplate(string $title, string $type, string $ref = '', string $desc = ''): string
    {
        $safeTitle = trim($title) !== '' ? trim($title) : 'General Legislative Proposal';
        $safeType = strtolower(trim($type) !== '' ? trim($type) : 'ordinance');
        $safeRef = trim($ref) !== '' ? trim($ref) : '___';
        $safeDesc = trim($desc) !== '' ? trim($desc) : 'to promote public welfare, good governance, and responsive public policy.';
        $isResolution = str_contains($safeType, 'resolution');
        $documentLabel = $isResolution ? 'RESOLUTION' : 'ORDINANCE';
        $enactingClause = $isResolution
            ? 'RESOLVED, as it is hereby resolved, by the Sangguniang Panlungsod, in session assembled, that:'
            : 'BE IT ORDAINED by the Sangguniang Panlungsod, in session assembled, that:';
        $documentNoun = strtolower($documentLabel);

        return implode("\n", [
            sprintf('%s NO. %s', $documentLabel, strtoupper($safeRef)),
            '',
            sprintf('AN %s %s', $documentLabel, strtoupper($safeTitle)),
            '',
            $enactingClause,
            '',
            sprintf('SECTION 1. Short Title. - This %s shall be known as the "%s".', $documentNoun, $safeTitle),
            '',
            sprintf('SECTION 2. Declaration of Policy. - This %s is intended %s', $documentNoun, strtolower($safeDesc)),
            '',
            sprintf('SECTION 3. Implementation. - The concerned city offices shall implement this %s in accordance with existing laws, ordinances, and regulations.', $documentNoun),
            '',
            'SECTION 4. Separability. - If any provision of this measure is declared invalid, the remaining provisions shall remain in full force and effect.',
            '',
            'SECTION 5. Effectivity. - This measure shall take effect upon its approval, unless otherwise provided by law.',
            '',
            $isResolution ? 'ADOPTED,' : 'ENACTED,',
            '________________________',
            sprintf('Date: %s', date('F j, Y')),
        ]);
    }

    private function buildTextDiffSummary(string $previousContent, string $currentContent): ?string
    {
        if ($previousContent === $currentContent) {
            return null;
        }

        $previousLines = preg_split('/\r\n|\r|\n/', $previousContent) ?: [];
        $currentLines = preg_split('/\r\n|\r|\n/', $currentContent) ?: [];
        $lineDiff = [];
        $max = max(count($previousLines), count($currentLines));

        for ($index = 0; $index < $max && count($lineDiff) < 8; $index++) {
            $previousLine = $previousLines[$index] ?? '';
            $currentLine = $currentLines[$index] ?? '';
            if ($previousLine === $currentLine) {
                continue;
            }

            $lineDiff[] = [
                'type' => $previousLine === '' ? 'added' : ($currentLine === '' ? 'removed' : 'changed'),
                'line' => $index + 1,
                'content' => $currentLine !== '' ? $currentLine : $previousLine,
                'previous' => $previousLine !== '' ? $previousLine : null,
            ];
        }

        $previousWords = preg_split('/\s+/', trim($previousContent)) ?: [];
        $currentWords = preg_split('/\s+/', trim($currentContent)) ?: [];
        $wordDiff = [];
        $wordMax = max(count($previousWords), count($currentWords));
        for ($index = 0; $index < $wordMax && count($wordDiff) < 8; $index++) {
            $previousWord = $previousWords[$index] ?? '';
            $currentWord = $currentWords[$index] ?? '';
            if ($previousWord === $currentWord) {
                continue;
            }

            $wordDiff[] = [
                'type' => $previousWord === '' ? 'added' : ($currentWord === '' ? 'removed' : 'changed'),
                'from' => $previousWord,
                'to' => $currentWord,
            ];
        }

        $summary = [
            'lines' => $lineDiff,
            'words' => $wordDiff,
        ];

        return $summary['lines'] === [] && $summary['words'] === [] ? null : json_encode($summary, JSON_UNESCAPED_UNICODE);
    }

    private function buildFileDiffSummary(?string $previousPath, string $currentPath, string $mimeType): ?string
    {
        if ($previousPath === null || $previousPath === '' || !file_exists($previousPath) || !is_readable($previousPath)) {
            return null;
        }

        if (!is_readable($currentPath) || !file_exists($currentPath)) {
            return null;
        }

        if (!$this->isTextPreviewableMime($mimeType)) {
            return null;
        }

        $previousContent = @file_get_contents($previousPath);
        $currentContent = @file_get_contents($currentPath);
        if ($previousContent === false || $currentContent === false) {
            return null;
        }

        return $this->buildTextDiffSummary($previousContent, $currentContent);
    }

    private function normalizeFileChangeLog(?string $log): array
    {
        $decoded = json_decode($log ?? '[]', true);
        return is_array($decoded) ? $decoded : [];
    }

    private function appendFileChangeLog(?string $existingLog, array $entry): string
    {
        $entries = $this->normalizeFileChangeLog($existingLog);
        $entries[] = $entry;
        return json_encode($entries, JSON_UNESCAPED_UNICODE);
    }

    private function handleSaveLegislativeFileContent(): void
    {
        $currentUser = $this->getCurrentUser();
        if (!$currentUser) {
            $this->redirect('login');
        }

        $legislativeId = trim($_POST['legislative_id'] ?? '');
        $newContent = LegislativeContentFormatter::toPlainText((string)($_POST['file_content'] ?? ''));
        $changeReason = trim($_POST['change_reason'] ?? '');
        $redirectPage = $this->isAdminUser() ? 'legislative-admin' : 'legislative-user';

        if ($legislativeId === '') {
            $this->flash('error', 'Legislative record is required.');
            $this->redirect($redirectPage);
        }

        $legislative = $this->model->findLegislative($legislativeId);
        if (!$legislative) {
            $this->flash('error', 'Legislative record not found.');
            $this->redirect($redirectPage);
        }

        if (!empty($legislative['locked_at'])) {
            $this->flash('error', 'This signed document is locked and cannot be edited.');
            $this->redirect($redirectPage, ['edit' => $legislativeId, 'type' => 'legislative']);
        }
        if (!in_array($legislative['workflow_status'] ?? 'Receiving', ['Receiving', 'Received', 'Review', 'Under Review', 'Final Draft', 'For Revision'], true)) {
            $this->flash('error', 'This document must be returned for revision before its content can change.');
            $this->redirect($redirectPage, ['edit' => $legislativeId, 'type' => 'legislative']);
        }

        if (empty($legislative['file_path'])) {
            $this->flash('error', 'This record does not have an attached file to edit.');
            $this->redirect($redirectPage, ['edit' => $legislativeId, 'type' => 'legislative']);
        }

        $fullPath = __DIR__ . '/../../storage/uploads/' . basename($legislative['file_path']);
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            $this->flash('error', 'The attached file could not be found.');
            $this->redirect($redirectPage, ['edit' => $legislativeId, 'type' => 'legislative']);
        }

        $mimeType = $legislative['file_type'] ?: mime_content_type($fullPath);
        if (!$this->isTextPreviewableMime($mimeType)) {
            $this->flash('error', 'Only text-based attachments can be edited directly in the portal.');
            $this->redirect($redirectPage, ['edit' => $legislativeId, 'type' => 'legislative']);
        }

        $previousContent = $this->extractAttachedText($fullPath, $mimeType);
        if ($previousContent === '') {
            $this->flash('error', 'Unable to read the current file content.');
            $this->redirect($redirectPage, ['edit' => $legislativeId, 'type' => 'legislative']);
        }

        if (!$this->model->getLegislativeVersions($legislativeId)) {
            $this->model->createLegislativeVersion($legislativeId, $legislative, $currentUser, 1);
        }
        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
        $newFileName = 'revision_' . $legislativeId . '_' . bin2hex(random_bytes(6)) . ($extension !== '' ? '.' . $extension : '');
        $newFullPath = dirname($fullPath) . DIRECTORY_SEPARATOR . $newFileName;
        if (@file_put_contents($newFullPath, $newContent) === false) {
            $this->flash('error', 'Unable to save the updated file content.');
            $this->redirect($redirectPage, ['edit' => $legislativeId, 'type' => 'legislative']);
        }

        $fileRevisionHash = hash_file('sha256', $newFullPath) ?: null;
        $fileDiffSummary = $this->buildTextDiffSummary($previousContent, $newContent);
        $changeEntry = [
            'changed_at' => date(DATE_ATOM),
            'changed_by_id' => $currentUser['id'] ?? null,
            'changed_by_name' => $currentUser['name'] ?? 'Unknown',
            'change_reason' => $changeReason !== '' ? $changeReason : 'Updated file content',
            'summary' => $fileDiffSummary ? 'Updated file content' : 'Saved file content',
            'diffSummary' => $fileDiffSummary,
        ];

        $payload = [
            'fileRevisionHash' => $fileRevisionHash,
            'fileDiffSummary' => $fileDiffSummary,
            'fileChangeLog' => $this->appendFileChangeLog($legislative['fileChangeLog'] ?? null, $changeEntry),
            'file_name' => $legislative['file_name'] ?? null,
            'file_size' => filesize($newFullPath),
            'file_type' => $legislative['file_type'] ?? null,
            'file_path' => $newFileName,
            'content' => $newContent,
            'createdById' => $legislative['createdById'] ?? $currentUser['id'],
            'createdByName' => $legislative['createdByName'] ?? $currentUser['name'],
            'createdByRole' => $legislative['createdByRole'] ?? $currentUser['role'],
            'createdAt' => $legislative['createdAt'] ?? date(DATE_ATOM),
            'documentHash' => hash('sha256', sprintf(
                '%s|%s|%s|%s|%s|%s|%s|%s|%s',
                $legislative['title'] ?? '',
                $legislative['type'] ?? '',
                $legislative['status'] ?? 'draft',
                $legislative['desc'] ?? '',
                $newContent,
                $legislative['ref'] ?? '',
                $legislative['createdAt'] ?? date(DATE_ATOM),
                $legislative['file_name'] ?? '',
                $fileRevisionHash ?? ''
            )),
            'updatedAt' => date(DATE_ATOM),
        ];

        if (($legislative['createdById'] ?? '') !== ($currentUser['id'] ?? '')) {
            $payload['lastModifiedById'] = $currentUser['id'];
            $payload['lastModifiedByName'] = $currentUser['name'];
            $payload['lastModifiedAt'] = date(DATE_ATOM);
        }

        $this->model->updateLegislative($legislativeId, $payload);
        $savedRecord = $this->model->findLegislative($legislativeId);
        if ($savedRecord) {
            $versionNumber = $this->model->createLegislativeVersion($legislativeId, $savedRecord, $currentUser);
            $this->model->recordLegislativeAudit($legislativeId, 'revision_saved', $currentUser, ['version' => $versionNumber, 'reason' => $changeReason]);
            $this->model->recordLegislativeAudit($legislativeId, 'file_upload_hash_recorded', $currentUser, [
                'file_path' => basename($newFileName),
                'sha256' => $fileRevisionHash,
            ]);
        }
        $this->flash('success', 'A new document version was saved; earlier versions remain available.');
        $this->redirect($redirectPage, ['edit' => $legislativeId, 'type' => 'legislative']);
    }

    private function handleSaveLegislative(): void
    {
        $id = $_POST['id'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $type = trim((string)($_POST['type'] ?? ''));
        $authorName = trim((string)($_POST['author_name'] ?? ''));
        $sponsorName = trim((string)($_POST['sponsor_name'] ?? ''));
        $status = $_POST['status'] ?? 'draft';
        $content = LegislativeContentFormatter::toSafeHtml(trim((string)($_POST['content'] ?? '')));
        $desc = '';
        $ref = '';
        $intakeClassification = $_POST['intake_classification'] ?? 'Legislative Matters';
        $routingStatus = $_POST['routing_status'] ?? 'For Agenda';
        $streamType = $_POST['stream_type'] ?? 'Approved Resolutions/Ordinances';
        $assignedCommittee = trim($_POST['assigned_committee'] ?? '');
        $dateReferred = trim($_POST['date_referred'] ?? '');
        $committeeReportStatus = $_POST['committee_report_status'] ?? 'Pending';
        $hearingDate = trim($_POST['hearing_date'] ?? '');
        $currentUser = $this->getCurrentUser();
        $file = $_FILES['file'] ?? null;
        $exportAfterSave = ($_POST['export_after_save'] ?? '') === '1';
        $importAfterSave = ($_POST['import_after_save'] ?? '') === '1';
        $uploadedPdf = $file
            && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
            && (strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION)) === 'pdf'
                || strtolower((string)($file['type'] ?? '')) === 'application/pdf');
        if ($type === '') {
            $typeSource = strtolower($title . ' ' . $content);
            $type = preg_match('/\b(ordinance|ordained)\b/', $typeSource)
                ? 'Ordinance'
                : (preg_match('/\b(resolution|resolved|resolves)\b/', $typeSource) ? 'Resolution' : 'Bill');
        }
        $existing = $id !== '' ? $this->model->findLegislative($id) : null;
        $attachment = [];
        $uploadDir = __DIR__ . '/../../storage/uploads';
        $requestedPage = in_array($_GET['page'] ?? '', ['legislative-admin', 'legislative-user', 'legislative-bills'], true) ? $_GET['page'] : null;
        $redirectPage = $requestedPage ?? ($this->isAdminUser() ? 'legislative-admin' : 'legislative-user');

        if (!$currentUser) {
            $this->redirect('login');
        }

        if ($existing && !empty($existing['locked_at'])) {
            $this->flash('error', 'This signed document is locked. Create a new revision request instead of editing it.');
            $this->redirect($redirectPage, ['edit' => $id, 'type' => 'legislative']);
        }
        if ($existing && !in_array($existing['workflow_status'] ?? 'Receiving', ['Receiving', 'Received', 'Review', 'Under Review', 'Final Draft', 'For Revision'], true)) {
            $this->flash('error', 'This document must be returned for revision before its content can change.');
            $this->redirect($redirectPage, ['edit' => $id, 'type' => 'legislative']);
        }

        if (($currentUser['role'] ?? '') === 'council members' && $existing && ($existing['status'] ?? '') === 'submitted_to_admin') {
            $this->flash('error', 'This document is pending admin review and cannot be edited until the review is complete.');
            $this->redirect($redirectPage, ['edit' => $id, 'type' => 'legislative']);
        }
        if (($currentUser['role'] ?? '') === 'council members' && in_array($status, ['approved', 'published'], true)) {
            $status = 'draft';
        }

        $hasUpload = $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
        if ($content === '' && !$hasUpload) {
            $this->flash('error', 'Title and document content are required.');
            $this->redirect($redirectPage);
        }

        if (!in_array($type, ['Bill', 'Resolution', 'Ordinance'], true)) {
            $this->flash('error', 'Select Bill, Resolution, or Ordinance.');
            $this->redirect($redirectPage);
        }

        $allowedIntakeClassifications = ['Legislative Matters', 'Letters/Petitions', 'Executive Bills'];
        $allowedRoutingStatuses = ['For Agenda', 'Transmit to Appropriate Offices', 'For Posting', 'File'];
        $allowedStreamTypes = [
            'CTFRB (Tricycle Franchises)',
            'Approved Resolutions/Ordinances',
            'Approved Minutes',
            'Matters for Referral',
            'Audio Recording / Session Transcripts',
        ];
        if (!in_array($intakeClassification, $allowedIntakeClassifications, true)) {
            $intakeClassification = 'Legislative Matters';
        }
        if (!in_array($routingStatus, $allowedRoutingStatuses, true)) {
            $routingStatus = 'For Agenda';
        }
        if (!in_array($streamType, $allowedStreamTypes, true)) {
            $streamType = 'Approved Resolutions/Ordinances';
        }
        $workflowStatus = $existing['workflow_status'] ?? LegislativeWorkflow::initialStatus($streamType)->value;
        if (!$existing) {
            $status = 'draft';
        }
        if ($streamType === 'Matters for Referral' && $dateReferred === '') {
            $dateReferred = date('Y-m-d');
        }

        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->flash('error', 'Please select a valid file to upload.');
                $this->redirect($redirectPage, ['edit' => $id, 'type' => 'legislative']);
            }

            if (($file['size'] ?? 0) > 10 * 1024 * 1024) {
                $this->flash('error', 'File exceeds 10MB limit.');
                $this->redirect($redirectPage, ['edit' => $id, 'type' => 'legislative']);
            }

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $targetName = $this->model->createUidFileName($file['name']);
            $targetPath = $uploadDir . '/' . $targetName;

            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                $this->flash('error', 'Unable to save uploaded file.');
                $this->redirect($redirectPage, ['edit' => $id, 'type' => 'legislative']);
            }

            $attachment = [
                'file_name' => $file['name'],
                'file_size' => $file['size'],
                'file_type' => $file['type'] ?: mime_content_type($targetPath),
                'file_path' => $targetName,
            ];

            if ($existing && !empty($existing['file_path']) && $existing['file_path'] !== $targetName) {
                // Keep the previous attachment available to its archived version.
            }
        }

        $fileRevisionHash = $existing['fileRevisionHash'] ?? null;
        $fileDiffSummary = $existing['fileDiffSummary'] ?? null;
        if (!empty($attachment['file_path'])) {
            $currentFilePath = $uploadDir . '/' . $attachment['file_path'];
            if (file_exists($currentFilePath)) {
                $fileRevisionHash = hash_file('sha256', $currentFilePath) ?: null;
                $previousPath = $existing && !empty($existing['file_path']) ? $uploadDir . '/' . basename($existing['file_path']) : null;
                $fileDiffSummary = $this->buildFileDiffSummary($previousPath, $currentFilePath, $attachment['file_type'] ?? mime_content_type($currentFilePath));
                $attachmentExtension = strtolower(pathinfo($currentFilePath, PATHINFO_EXTENSION));
                $attachmentMimeType = $attachment['file_type'] ?? mime_content_type($currentFilePath);
                $canImportAttachment = $this->isTextPreviewableMime($attachmentMimeType) || in_array($attachmentExtension, ['doc', 'docx', 'txt', 'rtf'], true);
                if (($importAfterSave || $content === '') && $canImportAttachment) {
                    $importedText = $attachmentExtension === 'docx'
                        ? $this->extractAttachedHtml($currentFilePath)
                        : $this->extractAttachedText($currentFilePath, $attachmentMimeType);
                    if ($importedText !== '') {
                        $content = $importedText;
                    } elseif ($attachmentExtension === 'docx' && !class_exists('ZipArchive')) {
                        $this->flash('warning', 'The Word file was uploaded, but DOCX text import requires the PHP ZIP extension.');
                    } elseif (strtolower(pathinfo($currentFilePath, PATHINFO_EXTENSION)) === 'pdf') {
                        $this->flash('warning', 'The PDF was uploaded, but no readable text was found. Scanned PDFs require OCR.');
                    }
                }
            }
        }

        if ($title === '' && $existing) {
            $title = trim((string)($existing['title'] ?? ''));
        }
        if ($title === '') {
            $title = $this->detectDocumentTitle($content, (string)($attachment['file_name'] ?? ($existing['file_name'] ?? '')));
        }
        if ($title === '') {
            $this->flash('error', 'Unable to detect a document title. Enter a title before saving.');
            $this->redirect($redirectPage, ['edit' => $id, 'type' => 'legislative']);
        }
        if ($type === '') {
            $typeSource = strtolower($title . ' ' . $content);
            $type = preg_match('/\b(resolution|resolved|resolves)\b/', $typeSource) ? 'Resolution' : 'Bill';
        }
        $effectiveAuthor = $authorName !== '' ? $authorName : ($existing['author_name'] ?? '');
        $effectiveSponsor = $sponsorName !== '' ? $sponsorName : ($existing['sponsor_name'] ?? '');
        if ($effectiveAuthor === '' && preg_match('/^author\s*:\s*(.+)$/im', strip_tags($content), $match)) {
            $effectiveAuthor = trim($match[1]);
        }
        if ($effectiveSponsor === '' && preg_match('/^sponsor\s*:\s*(.+)$/im', strip_tags($content), $match)) {
            $effectiveSponsor = trim($match[1]);
        }
        $aiSummary = $this->generateLegislativeSummary($content, $effectiveAuthor, $effectiveSponsor);

        $payload = [
            'title' => $title,
            'type' => $type,
            'author_name' => $effectiveAuthor !== '' ? $effectiveAuthor : null,
            'sponsor_name' => $effectiveSponsor !== '' ? $effectiveSponsor : null,
            'ai_summary' => $aiSummary,
            'status' => $status,
            'workflow_status' => $workflowStatus,
            'control_number' => $existing['control_number'] ?? null,
            'received_at' => $existing['received_at'] ?? date('Y-m-d H:i:s'),
            'origin' => trim((string)($_POST['origin'] ?? ($existing['origin'] ?? ''))),
            'originating_office' => trim((string)($_POST['originating_office'] ?? ($existing['originating_office'] ?? 'Secretariat'))),
            'assigned_to_id' => $existing['assigned_to_id'] ?? (($currentUser['role'] ?? '') === 'secretary' ? $currentUser['id'] : null),
            'assigned_role' => $existing['assigned_role'] ?? 'Secretariat',
            'desc' => $desc,
            'content' => $content,
            'ref' => $ref,
            'file_name' => $attachment['file_name'] ?? ($existing['file_name'] ?? null),
            'file_size' => $attachment['file_size'] ?? ($existing['file_size'] ?? null),
            'file_type' => $attachment['file_type'] ?? ($existing['file_type'] ?? null),
            'file_path' => $attachment['file_path'] ?? ($existing['file_path'] ?? null),
            'fileRevisionHash' => $fileRevisionHash,
            'fileDiffSummary' => $fileDiffSummary,
            'intake_classification' => $intakeClassification,
            'routing_status' => $routingStatus,
            'stream_type' => $streamType,
            'assigned_committee' => $assignedCommittee !== '' ? $assignedCommittee : null,
            'date_referred' => $dateReferred !== '' ? $dateReferred : null,
            'committee_report_status' => $committeeReportStatus,
            'hearing_date' => $hearingDate !== '' ? $hearingDate : null,
        ];

        if ($id !== '') {
            if ($existing) {
                $payload['createdById'] = $existing['createdById'] ?? $currentUser['id'];
                $payload['createdByName'] = $existing['createdByName'] ?? $currentUser['name'];
                $payload['createdByRole'] = $existing['createdByRole'] ?? $currentUser['role'];
                $payload['createdAt'] = $existing['createdAt'] ?? date(DATE_ATOM);
                if (empty($attachment) && !empty($existing['file_path'])) {
                    $payload['file_name'] = $existing['file_name'] ?? null;
                    $payload['file_size'] = $existing['file_size'] ?? null;
                    $payload['file_type'] = $existing['file_type'] ?? null;
                    $payload['file_path'] = $existing['file_path'] ?? null;
                }
            } else {
                $payload['createdById'] = $currentUser['id'];
                $payload['createdByName'] = $currentUser['name'];
                $payload['createdByRole'] = $currentUser['role'];
                $payload['createdAt'] = date(DATE_ATOM);
            }

            $payload['documentHash'] = hash('sha256', sprintf(
                '%s|%s|%s|%s|%s|%s|%s|%s',
                $payload['title'],
                $payload['type'],
                $payload['status'],
                $payload['desc'],
                $payload['content'],
                $payload['ref'],
                $payload['createdAt'],
                $payload['file_name'] ?? ''
            ));
            if ($existing && $currentUser['id'] !== ($existing['createdById'] ?? '')) {
                $payload['lastModifiedById'] = $currentUser['id'];
                $payload['lastModifiedByName'] = $currentUser['name'];
                $payload['lastModifiedAt'] = date(DATE_ATOM);
                $this->flash('info', 'AI Alert: this document was modified by ' . htmlspecialchars($currentUser['name'], ENT_QUOTES, 'UTF-8') . '.');
            }
            if (!$this->model->getLegislativeVersions($id)) {
                $this->model->createLegislativeVersion($id, $existing ?? $payload, $currentUser, 1);
            }
            $this->model->updateLegislative($id, array_merge($payload, ['updatedAt' => date(DATE_ATOM)]));
            $savedRecord = $this->model->findLegislative($id);
            if ($savedRecord) {
                $versionNumber = $this->model->createLegislativeVersion($id, $savedRecord, $currentUser);
                $this->model->recordLegislativeAudit($id, 'revision_saved', $currentUser, ['version' => $versionNumber]);
                if (!empty($attachment['file_path'])) {
                    $this->model->recordLegislativeAudit($id, 'file_upload_hash_recorded', $currentUser, [
                        'file_path' => basename((string)$attachment['file_path']),
                        'sha256' => $fileRevisionHash,
                    ]);
                }
            }
            if ($importAfterSave) {
                $this->flash('success', 'Uploaded text imported into the document editor.');
                $this->redirect($redirectPage, ['edit' => $id, 'type' => 'legislative']);
            }
            if ($exportAfterSave && !empty($payload['file_path'])) {
                header('Location: download.php?file=' . rawurlencode(basename((string)$payload['file_path'])));
                exit;
            }
            $this->flash('success', 'Legislative record updated.');
            $this->redirect($redirectPage, ['edit' => $id, 'type' => 'legislative']);
        } else {
            $payload['createdById'] = $currentUser['id'];
            $payload['createdByName'] = $currentUser['name'];
            $payload['createdByRole'] = $currentUser['role'];
            $payload['createdAt'] = date(DATE_ATOM);
            $payload['documentHash'] = hash('sha256', sprintf(
                '%s|%s|%s|%s|%s|%s|%s|%s',
                $payload['title'],
                $payload['type'],
                $payload['status'],
                $payload['desc'],
                $payload['content'],
                $payload['ref'],
                $payload['createdAt'],
                $payload['file_name'] ?? ''
            ));
            $createdId = $this->model->createLegislative($payload);
            $createdRecord = $this->model->findLegislative($createdId);
            if ($createdRecord) {
                $this->model->createLegislativeVersion($createdId, $createdRecord, $currentUser, 1);
            }
            $this->model->recordLegislativeAudit($createdId, 'received_and_registered', $currentUser, [
                'control_number' => $createdRecord['control_number'] ?? '',
                'origin' => $payload['origin'],
                'originating_office' => $payload['originating_office'],
            ]);
            if (!empty($payload['file_path'])) {
                $this->model->recordLegislativeAudit($createdId, 'file_upload_hash_recorded', $currentUser, [
                    'file_path' => basename((string)$payload['file_path']),
                    'sha256' => $fileRevisionHash,
                ]);
            }
            if ($importAfterSave) {
                $this->flash('success', 'Uploaded text imported into the document editor.');
                $this->redirect($redirectPage, ['edit' => $createdId, 'type' => 'legislative']);
            }
            if ($exportAfterSave && !empty($payload['file_path'])) {
                header('Location: download.php?file=' . rawurlencode(basename((string)$payload['file_path'])));
                exit;
            }
            $this->flash('success', 'Legislative record created.');
            $this->redirect($redirectPage, ['edit' => $createdId, 'type' => 'legislative']);
        }
    }

    private function handleSaveCommitteeReferral(): void
    {
        $currentUser = $this->getCurrentUser();
        $legislativeId = trim($_POST['legislative_id'] ?? $_POST['id'] ?? '');
        if (!$currentUser || $legislativeId === '') {
            $this->flash('error', 'A signed-in user and legislative record are required.');
            $this->redirect('legislative-admin');
        }

        $legislative = $this->model->findLegislative($legislativeId);
        if (!$legislative) {
            $this->flash('error', 'Legislative record not found.');
            $this->redirect('legislative-admin');
        }

        $isAssignedUser = (string)($legislative['assigned_to_id'] ?? '') === (string)($currentUser['id'] ?? '');
        if ((!$this->isAdminUser() && !$isAssignedUser) || !in_array(($legislative['workflow_status'] ?? ''), ['SP Session', 'For Session'], true)) {
            $this->flash('error', 'Only the responsible document owner can refer a session item to committee.');
            $this->redirect('legislative-admin', ['edit' => $legislativeId, 'type' => 'legislative']);
        }

        $assignedCommittee = trim($_POST['assigned_committee'] ?? '');
        $dateReferred = trim($_POST['date_referred'] ?? date('Y-m-d'));
        $committeeReportStatus = $_POST['committee_report_status'] ?? 'Pending';
        $hearingDate = trim($_POST['hearing_date'] ?? '');
        if ($assignedCommittee === '' || $dateReferred === '') {
            $this->flash('error', 'Committee and referral date are required.');
            $this->redirect('legislative-admin', ['edit' => $legislativeId, 'type' => 'legislative']);
        }

        $this->model->updateLegislative($legislativeId, [
            'stream_type' => 'Matters for Referral',
            'routing_status' => 'Transmit to Appropriate Offices',
            'assigned_committee' => $assignedCommittee,
            'date_referred' => $dateReferred,
            'committee_report_status' => $committeeReportStatus,
            'hearing_date' => $hearingDate !== '' ? $hearingDate : null,
            'status' => 'referred_to_committee',
            'workflow_status' => 'Committee/Referral',
            'updatedAt' => date(DATE_ATOM),
            'lastModifiedById' => $currentUser['id'] ?? null,
            'lastModifiedByName' => $currentUser['name'] ?? null,
            'lastModifiedAt' => date(DATE_ATOM),
        ]);
        $this->model->recordLegislativeAudit($legislativeId, 'committee_assigned', $currentUser, ['committee' => $assignedCommittee]);

        $this->flash('success', 'Committee referral saved.');
        $this->redirect('legislative-admin', ['edit' => $legislativeId, 'type' => 'legislative']);
    }

    public function calculateDaysInCommittee(string $dateReferred): int
    {
        $referredAt = \DateTimeImmutable::createFromFormat('!Y-m-d', $dateReferred);
        if (!$referredAt) {
            return 0;
        }

        return max(0, (int)$referredAt->diff(new \DateTimeImmutable('today'))->format('%r%a'));
    }

    private function handleAdvanceLegislativeWorkflow(): void
    {
        $currentUser = $this->getCurrentUser();
        $legislativeId = trim($_POST['legislative_id'] ?? $_POST['id'] ?? '');
        $action = trim((string)($_POST['workflow_action'] ?? ''));
        $requestedReturnPage = $_POST['workflow_return_page'] ?? '';
        $redirectPage = in_array($requestedReturnPage, ['legislative-audit-trail', 'legislative-bills'], true)
            ? $requestedReturnPage
            : 'legislative-admin';
        $redirectParams = match ($redirectPage) {
            'legislative-audit-trail' => $legislativeId !== '' ? ['ledger_id' => $legislativeId] : [],
            'legislative-bills' => in_array($_POST['workflow_return_status'] ?? '', ['pending', 'all'], true)
                ? ['bill_status' => $_POST['workflow_return_status']]
                : [],
            default => ['edit' => $legislativeId, 'type' => 'legislative'],
        };
        $role = strtolower((string)($currentUser['role'] ?? ''));
        $canDecide = in_array($action, ['approval', 'distribution', 'filing', 'revision'], true) && in_array($role, ['mayor', 'lce'], true);
        if (!$currentUser || $legislativeId === '' || $action === '') {
            $this->flash('error', 'Unauthorized or invalid workflow action.');
            $this->redirect($redirectPage, $redirectParams);
        }

        if ($action === 'approval' && !in_array($role, ['mayor', 'lce'], true)) {
            $this->flash('error', 'Only the LCE can approve this legislative matter.');
            $this->redirect($redirectPage, $redirectParams);
        }

        $legislative = $this->model->findLegislative($legislativeId);
        if (!$legislative) {
            $this->flash('error', 'Legislative record not found.');
            $this->redirect($redirectPage, $redirectParams);
        }

        $isAssignedUser = (string)($legislative['assigned_to_id'] ?? '') === (string)($currentUser['id'] ?? '');
        $isLceRevision = $action === 'revision' && ($legislative['workflow_status'] ?? '') === 'LCE Approval';
        $isAssigneeAction = !$isLceRevision && !in_array($action, ['approval', 'distribution', 'filing', 'signature', 'archive'], true);
        if (!$this->isAdminUser() && !$canDecide && !($isAssignedUser && $isAssigneeAction)) {
            $this->flash('error', 'Only the assigned owner or an authorized approver can perform this workflow action.');
            $this->redirect($redirectPage, $redirectParams);
        }

        if (empty($legislative['assigned_to_id']) && !$this->isAdminUser()) {
            $this->flash('error', 'Assign a responsible Secretariat user before advancing this document.');
            $this->redirect($redirectPage, $redirectParams);
        }

        $stream = $legislative['stream_type'] ?? 'Approved Resolutions/Ordinances';
        $currentStatus = $legislative['workflow_status'] ?? LegislativeWorkflow::initialStatus($stream)->value;
        if ($currentStatus === 'Agenda' && $action === 'approval') {
            $this->flash('error', 'Add this document to a session agenda directly from the Agenda stage.');
            $this->redirect($redirectPage, $redirectParams);
        }
        try {
            $nextStatus = LegislativeWorkflow::transition($stream, $currentStatus, $action);
        } catch (\InvalidArgumentException $exception) {
            $this->flash('error', $exception->getMessage());
            $this->redirect($redirectPage, $redirectParams);
        }

        if ($nextStatus->value === 'Signatures') {
            $versions = $this->model->getLegislativeVersions($legislativeId);
            if (!$versions || !str_starts_with((string)$versions[0]['status'], 'Final Draft v')) {
                $this->flash('error', 'Only the current finalized document version can be sent for signature.');
                $this->redirect($redirectPage, $redirectParams);
            }
        }

        $archiveBackup = null;
        if ($nextStatus->value === 'Archive') {
            if (empty($legislative['signed_hash']) || empty($legislative['locked_at']) || empty($legislative['scan_file_path'])) {
                $this->flash('error', 'Complete the signed filing scan before archiving this record.');
                $this->redirect($redirectPage, $redirectParams);
            }
            try {
                $archiveBackup = $this->backupLegislativeRecord($legislative, $currentUser);
            } catch (\Throwable $exception) {
                $this->flash('error', 'The signed record could not be backed up, so it was not archived: ' . $exception->getMessage());
                $this->redirect($redirectPage, $redirectParams);
            }
        }

        $workflowChanges = [
            'workflow_status' => $nextStatus->value,
            'updatedAt' => date(DATE_ATOM),
            'lastModifiedById' => $currentUser['id'] ?? null,
            'lastModifiedByName' => $currentUser['name'] ?? null,
            'lastModifiedAt' => date(DATE_ATOM),
        ];
        if ($nextStatus->value === 'Final Draft' && !empty($legislative['locked_at'])) {
            $workflowChanges['locked_at'] = null;
        }
        $this->model->updateLegislative($legislativeId, $workflowChanges);
        if ($nextStatus->value === 'Final Draft') {
            $draft = $this->model->findLegislative($legislativeId);
            $versionNumber = $this->model->createLegislativeVersion($legislativeId, $draft ?? $legislative, $currentUser);
            $this->model->updateLegislativeVersion($legislativeId, $versionNumber, 'Final Draft v' . $versionNumber);
        }
        $this->model->recordLegislativeAudit($legislativeId, 'status_changed', $currentUser, [
            'from' => $currentStatus,
            'to' => $nextStatus->value,
            'backup' => $archiveBackup,
        ]);
        if (!empty($legislative['assigned_to_id'])) {
            $this->model->addUserNotification((string)$legislative['assigned_to_id'], 'Document ' . ($legislative['control_number'] ?? '') . ' is now ' . $nextStatus->value . '.', 'index.php?page=legislative-admin&edit=' . rawurlencode($legislativeId) . '&type=legislative');
        }
        $ownerId = (string)($legislative['createdById'] ?? '');
        if ($ownerId !== '' && $ownerId !== (string)($currentUser['id'] ?? '')) {
            $this->model->addUserNotification(
                $ownerId,
                sprintf('Your bill "%s" moved from %s to %s.', $legislative['title'] ?? 'Untitled', $currentStatus, $nextStatus->value),
                'index.php?page=legislative-audit-trail&ledger_id=' . rawurlencode($legislativeId)
            );
        }
        if ($nextStatus->value === 'SP Session') {
            foreach ($this->model->getUsers() as $recipient) {
                if (($recipient['role'] ?? '') === 'council members' && ($recipient['status'] ?? '') === 'active') {
                    $this->model->addUserNotification((string)$recipient['id'], 'Agenda item ' . ($legislative['control_number'] ?? '') . ' is ready for session.', 'index.php?page=legislative-bills');
                }
            }
        }
        $this->flash('success', 'Workflow advanced to ' . $nextStatus->value . '.');
        if ($this->isAdminUser() && $action === 'agenda') {
            $this->redirect('legislative-bills', ['bill_status' => 'pending']);
        }
        $this->redirect($redirectPage, $redirectParams);
    }

    private function backupLegislativeRecord(array $document, array $user): array
    {
        $configuredDirectory = getenv('LEGISLATIVE_BACKUP_DIR');
        $backupDirectory = is_string($configuredDirectory) && trim($configuredDirectory) !== ''
            ? trim($configuredDirectory)
            : __DIR__ . '/../../storage/backups/legislative';
        if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0700, true) && !is_dir($backupDirectory)) {
            throw new \RuntimeException('Unable to create the backup directory.');
        }
        if (!is_writable($backupDirectory)) {
            throw new \RuntimeException('The configured backup directory is not writable.');
        }

        $controlNumber = preg_replace('/[^A-Za-z0-9_-]/', '_', (string)($document['control_number'] ?? $document['id']));
        $versions = $this->model->getLegislativeVersions((string)$document['id']);
        $fileBackups = [];
        $sourceFiles = [];
        if (!empty($document['file_path'])) {
            $sourceFiles['current'] = (string)$document['file_path'];
        }
        foreach ($versions as $version) {
            if (!empty($version['file_path'])) {
                $sourceFiles['v' . (int)$version['version_number']] = (string)$version['file_path'];
            }
        }
        if (!empty($document['scan_file_path'])) {
            $sourceFiles['filing_scan'] = (string)$document['scan_file_path'];
        }
        foreach ($sourceFiles as $label => $filePath) {
            $source = __DIR__ . '/../../storage/uploads/' . basename($filePath);
            if (!is_file($source)) {
                continue;
            }
            $backupName = $controlNumber . '_' . $label . '_' . basename($filePath);
            if (!copy($source, rtrim($backupDirectory, '/\\') . DIRECTORY_SEPARATOR . $backupName)) {
                throw new \RuntimeException('Unable to back up an attached document version.');
            }
            @chmod(rtrim($backupDirectory, '/\\') . DIRECTORY_SEPARATOR . $backupName, 0600);
            $fileBackups[$label] = $backupName;
        }

        $manifest = [
            'control_number' => $document['control_number'] ?? '',
            'archived_at' => date(DATE_ATOM),
            'archived_by' => ['id' => $user['id'] ?? null, 'name' => $user['name'] ?? '', 'role' => $user['role'] ?? ''],
            'document' => [
                'title' => $document['title'] ?? '', 'type' => $document['type'] ?? '',
                'content' => $document['content'] ?? '', 'origin' => $document['origin'] ?? '',
                'originating_office' => $document['originating_office'] ?? '',
                'received_at' => $document['received_at'] ?? null,
                'signed_hash' => $document['signed_hash'] ?? '',
                'filing_scan_file' => $document['scan_file_path'] ?? '',
            ],
            'versions' => $versions,
            'audit_events' => $this->model->getLegislativeAudit((string)$document['id']),
            'signature' => $this->model->getLatestLegislativeSignature((string)$document['id']),
            'signatures' => $this->model->getLegislativeSignatures((string)$document['id']),
            'attachment_backups' => $fileBackups,
        ];
        $json = json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $backupName = $controlNumber . '_' . date('Ymd_His') . '.json';
        $target = rtrim($backupDirectory, '/\\') . DIRECTORY_SEPARATOR . $backupName;
        $temporary = $target . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (file_put_contents($temporary, $json, LOCK_EX) === false || !rename($temporary, $target)) {
            @unlink($temporary);
            throw new \RuntimeException('Unable to write the signed-record backup manifest.');
        }
        @chmod($target, 0600);
        return ['file' => $backupName, 'sha256' => hash_file('sha256', $target) ?: ''];
    }

    private function handleAssignLegislativeDocument(): void
    {
        $currentUser = $this->getCurrentUser();
        $documentId = trim((string)($_POST['legislative_id'] ?? ''));
        $assigneeId = trim((string)($_POST['assigned_to_id'] ?? ''));
        $document = $documentId !== '' ? $this->model->findLegislative($documentId) : null;
        $assignee = $assigneeId !== '' ? $this->model->getUserById($assigneeId) : null;
        if (!$currentUser || !$this->isAdminUser() || !$document || !$assignee || ($assignee['status'] ?? '') !== 'active') {
            $this->flash('error', 'Select an active user to assign this document.');
            $this->redirect('legislative-admin');
        }
        $this->model->updateLegislative($documentId, [
            'assigned_to_id' => $assigneeId,
            'assigned_role' => trim((string)($_POST['assigned_role'] ?? $assignee['role'])),
            'updatedAt' => date(DATE_ATOM),
        ]);
        $this->model->recordLegislativeAudit($documentId, 'assigned', $currentUser, ['assigned_to' => $assignee['name'], 'role' => $_POST['assigned_role'] ?? $assignee['role']]);
        $this->model->addUserNotification($assigneeId, 'Document ' . ($document['control_number'] ?? '') . ' was assigned to you.', 'index.php?page=legislative-admin&edit=' . rawurlencode($documentId) . '&type=legislative');
        $this->flash('success', 'Document assigned to ' . $assignee['name'] . '.');
        $this->redirect('legislative-admin', ['edit' => $documentId, 'type' => 'legislative']);
    }

    private function ensureUserSigningKey(string $userId, string $userName, string $createdById, string $createdByName, string $purpose, string $roleLabel): array
    {
        $existingKey = $this->model->getUserSigningKey($userId, $purpose);
        if ($existingKey) {
            return $existingKey;
        }
        if (!function_exists('openssl_pkey_new') || !function_exists('openssl_pkey_export')) {
            throw new \RuntimeException('OpenSSL is required to generate signing keys.');
        }

        $configuredOpenSslFile = getenv('OPENSSL_CONF');
        $configCandidates = [
            is_string($configuredOpenSslFile) ? $configuredOpenSslFile : '',
            (string)ini_get('openssl.default_config'),
            dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . 'extras' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'openssl.cnf',
            dirname(PHP_BINARY, 2) . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'extras' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'openssl.cnf',
            dirname(PHP_BINARY, 3) . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'extras' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'openssl.cnf',
            dirname(PHP_BINARY, 2) . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'openssl.cnf',
            dirname(PHP_BINARY, 2) . DIRECTORY_SEPARATOR . 'apache' . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'openssl.cnf',
            dirname(PHP_BINARY, 3) . DIRECTORY_SEPARATOR . 'apache' . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'openssl.cnf',
        ];
        $configCandidates = array_values(array_unique(array_filter(
            $configCandidates,
            static fn(string $path): bool => $path !== '' && is_file($path)
        )));
        $configCandidates[] = null;

        $privateKeyPem = '';
        $publicKeyPem = '';
        foreach ($configCandidates as $configFile) {
            $options = ['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 3072];
            $exportOptions = [];
            if ($configFile !== null) {
                $options['config'] = $configFile;
                $exportOptions['config'] = $configFile;
            }
            $privateKey = @openssl_pkey_new($options);
            $details = $privateKey ? @openssl_pkey_get_details($privateKey) : false;
            if ($privateKey && $details && @openssl_pkey_export($privateKey, $privateKeyPem, null, $exportOptions)) {
                $publicKeyPem = (string)($details['key'] ?? '');
                break;
            }
            if ($privateKey) {
                openssl_pkey_free($privateKey);
            }
        }
        if ($privateKeyPem === '' || $publicKeyPem === '') {
            throw new \RuntimeException('The user signing key could not be generated.');
        }

        $this->model->createLegislativeSigningKey([
            'key_name' => $roleLabel . ' ' . $userName . ' Signing Key',
            'key_purpose' => $purpose, 'algorithm' => 'RSA-SHA256',
            'public_key_pem' => $publicKeyPem, 'encrypted_private_key_pem' => $privateKeyPem,
            'fingerprint' => hash('sha256', $publicKeyPem), 'owner_user_id' => $userId,
            'created_by_id' => $createdById, 'created_by_name' => $createdByName,
        ]);
        return $this->model->getUserSigningKey($userId, $purpose)
            ?? throw new \RuntimeException('The user signing key was not saved.');
    }

    private function handleDownloadSigningKey(): void
    {
        $currentUser = $this->getCurrentUser();
        $role = $currentUser['role'] ?? '';
        $purpose = $role === 'presiding officer' ? 'agenda' : (in_array($role, ['lce', 'mayor'], true) ? 'legislative' : '');
        $keyId = (string)($purpose === 'agenda'
            ? ($_SESSION['officer_key_download_id'] ?? '')
            : ($_SESSION['lce_key_download_id'] ?? ''));
        if (!$currentUser || $purpose === '' || $keyId === '') {
            http_response_code(403);
            exit('Unauthorized.');
        }

        $key = $this->model->getUserSigningKey((string)$currentUser['id'], $purpose);
        if (!$key || (string)$key['id'] !== $keyId || !empty($key['private_key_delivered_at']) || empty($key['private_key_pem'])) {
            unset($_SESSION['officer_key_download_id'], $_SESSION['lce_key_download_id']);
            http_response_code(404);
            exit('Signing key download is unavailable.');
        }

        $filename = preg_replace('/[^A-Za-z0-9_-]/', '_', (string)$key['key_name']) . '.txt';
        $keyLabel = $purpose === 'agenda' ? 'PRESIDING OFFICER AGENDA' : 'LCE LEGISLATIVE';
        $contents = $keyLabel . " PKI SIGNING KEY\r\n"
            . 'Key ID: ' . $key['id'] . "\r\n"
            . 'Algorithm: ' . $key['algorithm'] . "\r\n"
            . 'SHA-256 fingerprint: ' . $key['fingerprint'] . "\r\n\r\n"
            . "PUBLIC KEY\r\n" . $key['public_key_pem'] . "\r\n"
            . "PRIVATE KEY\r\n" . $key['private_key_pem'] . "\r\n";
        if (!$this->model->markUserSigningKeyDelivered($keyId)) {
            http_response_code(404);
            exit('Signing key download is unavailable.');
        }
        unset($_SESSION['officer_key_download_id'], $_SESSION['lce_key_download_id']);
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, private');
        header('X-Content-Type-Options: nosniff');
        echo $contents;
        exit;
    }

    private function handleSignLegislativeDocument(): void
    {
        $currentUser = $this->getCurrentUser();
        $documentId = trim((string)($_POST['legislative_id'] ?? ''));
        $document = $documentId !== '' ? $this->model->findLegislative($documentId) : null;
        $role = strtolower((string)($currentUser['role'] ?? ''));
        $signerRoles = ['mayor', 'lce', 'secretary', 'presiding officer'];
        $isLceSigner = in_array($role, ['mayor', 'lce'], true);
        $isLceApprovalSigner = $isLceSigner && $document
            && ($document['lce_action'] ?? '') === 'Pending LCE Review';
        $returnPage = $isLceSigner ? 'lce' : 'legislative-admin';
        if (!$currentUser || !$document || !in_array($role, $signerRoles, true)
            || (!$isLceApprovalSigner && ((string)($document['assigned_to_id'] ?? '') !== (string)($currentUser['id'] ?? '')
                || ($document['workflow_status'] ?? '') !== 'Signatures' || !empty($document['locked_at'])))) {
            $this->flash('error', 'Only the assigned authorized signer or the reviewing LCE may sign this document.');
            $this->redirect($returnPage, $returnPage === 'lce' ? [] : ['edit' => $documentId, 'type' => 'legislative']);
        }
        if (!function_exists('openssl_sign') || !function_exists('openssl_verify')) {
            $this->flash('error', 'OpenSSL is required for digital signatures.');
            $this->redirect($returnPage, $returnPage === 'lce' ? [] : ['edit' => $documentId, 'type' => 'legislative']);
        }

        $key = $isLceSigner
            ? $this->model->getUserSigningKey((string)$currentUser['id'], 'legislative')
            : $this->model->getActiveLegislativeSigningKey('legislative');
        if ($isLceSigner) {
            $keyFile = $_FILES['signing_key_file'] ?? null;
            $keyText = '';
            $keyExtension = strtolower(pathinfo((string)($keyFile['name'] ?? ''), PATHINFO_EXTENSION));
            if ($keyFile && ($keyFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
                && in_array($keyExtension, ['txt', 'pem'], true) && ($keyFile['size'] ?? 0) <= 65536
                && is_uploaded_file($keyFile['tmp_name'] ?? '')) {
                $keyText = (string)file_get_contents($keyFile['tmp_name']);
            }
            $privateKeyPem = '';
            if (preg_match('/-----BEGIN (?:RSA )?PRIVATE KEY-----[\s\S]+?-----END (?:RSA )?PRIVATE KEY-----/', $keyText, $privateKeyMatch)) {
                $privateKeyPem = $privateKeyMatch[0];
            }
            $uploadedKeyId = '';
            if (preg_match('/^Key ID:\s*([a-f0-9]{1,32})\s*$/mi', $keyText, $keyIdMatch)) {
                $uploadedKeyId = strtolower($keyIdMatch[1]);
            }
            $privateKey = $privateKeyPem !== '' ? openssl_pkey_get_private($privateKeyPem) : false;
        } else {
            $passphrase = (string)($_POST['key_passphrase'] ?? '');
            $privateKey = $key && $passphrase !== '' ? openssl_pkey_get_private((string)($key['private_key_pem'] ?? ''), $passphrase) : false;
        }
        $publicKey = $key ? openssl_pkey_get_public((string)$key['public_key_pem']) : false;
        if (!$key || !$privateKey || !$publicKey || ($isLceSigner && $uploadedKeyId !== strtolower((string)$key['id']))) {
            $this->flash('error', $isLceSigner
                ? 'Upload the personal PKI signing key downloaded when you first logged in.'
                : 'An active signing key and its correct passphrase are required. Contact an administrator.');
            $this->redirect($returnPage, $returnPage === 'lce' ? [] : ['edit' => $documentId, 'type' => 'legislative']);
        }
        $privateDetails = openssl_pkey_get_details($privateKey);
        $publicDetails = openssl_pkey_get_details($publicKey);
        if (!$privateDetails || !$publicDetails || ($privateDetails['type'] ?? null) !== OPENSSL_KEYTYPE_RSA
            || !hash_equals(hash('sha256', (string)$privateDetails['key']), hash('sha256', (string)$publicDetails['key']))) {
            $this->flash('error', 'The private key does not match the active public key.');
            $this->redirect($returnPage, $returnPage === 'lce' ? [] : ['edit' => $documentId, 'type' => 'legislative']);
        }

        $versions = $this->model->getLegislativeVersions($documentId);
        $latestVersion = $versions[0] ?? null;
        $currentFilePath = !empty($document['file_path']) ? __DIR__ . '/../../storage/uploads/' . basename((string)$document['file_path']) : '';
        $versionFilePath = !empty($latestVersion['file_path']) ? __DIR__ . '/../../storage/uploads/' . basename((string)$latestVersion['file_path']) : '';
        $currentFileHash = $currentFilePath !== '' && is_file($currentFilePath) ? (string)hash_file('sha256', $currentFilePath) : '';
        $versionFileHash = $versionFilePath !== '' && is_file($versionFilePath) ? (string)hash_file('sha256', $versionFilePath) : '';
        $versionMatchesDocument = $latestVersion
            && !str_starts_with((string)$latestVersion['status'], 'Signed v')
            && (string)$latestVersion['title'] === (string)($document['title'] ?? '')
            && (string)$latestVersion['content'] === (string)($document['content'] ?? '')
            && hash_equals($currentFileHash, $versionFileHash);
        if (!$versionMatchesDocument) {
            $versionNumber = $this->model->createLegislativeVersion($documentId, $document, $currentUser);
            $this->model->updateLegislativeVersion($documentId, $versionNumber, 'Final Draft v' . $versionNumber);
            $this->model->recordLegislativeAudit($documentId, 'final_draft_snapshot_created', $currentUser, ['version' => $versionNumber]);
            $versions = $this->model->getLegislativeVersions($documentId);
        }
        if ($versions && !str_starts_with((string)$versions[0]['status'], 'Final Draft v')) {
            $versionNumber = (int)$versions[0]['version_number'];
            $this->model->updateLegislativeVersion($documentId, $versionNumber, 'Final Draft v' . $versionNumber);
            $this->model->recordLegislativeAudit($documentId, 'final_draft_confirmed_for_signature', $currentUser, ['version' => $versionNumber]);
            $versions = $this->model->getLegislativeVersions($documentId);
        }
        if (!$versions || !str_starts_with((string)$versions[0]['status'], 'Final Draft v')) {
            $this->flash('error', 'Only a finalized document version can be signed.');
            $this->redirect($returnPage, $returnPage === 'lce' ? [] : ['edit' => $documentId, 'type' => 'legislative']);
        }
        $versionNumber = (int)$versions[0]['version_number'];
        $hash = $this->legislativeContentHash($document);
        $signature = '';
        if (!openssl_sign($hash, $signature, $privateKey, OPENSSL_ALGO_SHA256)
            || openssl_verify($hash, $signature, $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
            $this->flash('error', 'The document signature could not be verified.');
            $this->redirect($returnPage, $returnPage === 'lce' ? [] : ['edit' => $documentId, 'type' => 'legislative']);
        }

        $this->model->createLegislativeSignature([
            'document_id' => $documentId, 'version_number' => $versionNumber,
            'signer_id' => $currentUser['id'], 'signer_name' => $currentUser['name'],
            'signer_role' => $currentUser['role'], 'algorithm' => 'RSA-SHA256',
            'signature' => base64_encode($signature), 'public_key_pem' => $key['public_key_pem'],
            'signing_key_id' => $key['id'], 'certificate_fingerprint' => $key['fingerprint'],
            'document_hash' => $hash,
        ]);
        $this->model->updateLegislativeVersion($documentId, $versionNumber, 'Signed v' . $versionNumber, $hash);
        if ($isLceApprovalSigner) {
            $signedAt = date(DATE_ATOM);
            $this->model->updateLegislative($documentId, [
                'documentHash' => $hash, 'signed_hash' => $hash, 'workflow_status' => 'Archive',
                'routing_status' => 'Archived for Local Download', 'lce_action' => 'LCE Approved',
                'lce_action_at' => $signedAt, 'lce_action_by_id' => $currentUser['id'],
                'archive_status' => 'FILE - Local Offline Archive', 'status' => 'approved_by_lce',
                'locked_at' => date('Y-m-d H:i:s'), 'signature_verified_at' => date('Y-m-d H:i:s'),
                'updatedAt' => $signedAt, 'lastModifiedById' => $currentUser['id'],
                'lastModifiedByName' => $currentUser['name'], 'lastModifiedAt' => $signedAt,
            ]);
            $auditDetails = ['document_hash' => $hash, 'version' => $versionNumber, 'signing_key_id' => $key['id']];
            $this->model->recordLegislativeAudit($documentId, 'signed_and_verified', $currentUser, $auditDetails);
            $this->model->recordLegislativeAudit($documentId, 'lce_approved', $currentUser, [
                'from' => 'LCE Approval', 'to' => 'Archive', 'document_hash' => $hash,
            ]);
            $ownerId = $document['createdById'] ?? null;
            if ($ownerId) {
                $this->model->addUserNotification(
                    (string)$ownerId,
                    'Your legislative matter "' . ($document['title'] ?? 'Untitled') . '" was approved and digitally signed by the LCE, then stored in the local archive.',
                    'index.php?page=legislative-user&edit=' . rawurlencode($documentId) . '&type=legislative'
                );
                $owner = $this->model->getUserById((string)$ownerId);
                if ($owner && !empty($owner['email']) && filter_var($owner['email'], FILTER_VALIDATE_EMAIL)) {
                    $this->sendLegislativeNotificationEmail($owner['email'], $document, $currentUser);
                }
            }
            $this->flash('success', 'Approved, digitally signed, and stored in the local archive.');
            $this->redirect('lce');
        }

        $this->model->updateLegislative($documentId, [
            'documentHash' => $hash, 'signed_hash' => $hash, 'workflow_status' => 'LCE Approval',
            'routing_status' => 'Sent to LCE', 'lce_action' => 'Pending LCE Review',
            'lce_action_at' => date(DATE_ATOM), 'lce_action_by_id' => $currentUser['id'],
            'status' => 'signed', 'locked_at' => date('Y-m-d H:i:s'),
            'signature_verified_at' => date('Y-m-d H:i:s'), 'updatedAt' => date(DATE_ATOM),
            'lastModifiedById' => $currentUser['id'], 'lastModifiedByName' => $currentUser['name'],
            'lastModifiedAt' => date(DATE_ATOM),
        ]);
        $this->model->recordLegislativeAudit($documentId, 'signed_and_verified', $currentUser, [
            'document_hash' => $hash, 'version' => $versionNumber, 'signing_key_id' => $key['id'],
        ]);
        foreach ($this->model->getUsers() as $lceUser) {
            if (in_array(($lceUser['role'] ?? ''), ['lce', 'mayor'], true) && ($lceUser['status'] ?? 'active') === 'active') {
                $this->model->addUserNotification((string)$lceUser['id'], 'A signed legislative version is ready for LCE approval: ' . ($document['title'] ?? 'Untitled'), 'index.php?page=lce');
            }
        }
        $this->flash('success', 'Digital signature verified. The signed version is locked and awaiting LCE approval.');
        $this->redirect('legislative-admin', ['edit' => $documentId, 'type' => 'legislative']);
    }

    private function handleCompleteLegislativeFilingScan(): void
    {
        $currentUser = $this->getCurrentUser();
        $documentId = trim((string)($_POST['legislative_id'] ?? ''));
        $document = $documentId !== '' ? $this->model->findLegislative($documentId) : null;
        $isAssignedUser = $document && (string)($document['assigned_to_id'] ?? '') === (string)($currentUser['id'] ?? '');
        $isLce = in_array(($currentUser['role'] ?? ''), ['lce', 'mayor'], true);
        if (!$currentUser || !$document || ($document['workflow_status'] ?? '') !== 'Filing/Scan'
            || empty($document['signed_hash']) || empty($document['locked_at'])
            || (!$this->isAdminUser() && !$isLce && !$isAssignedUser)) {
            $this->flash('error', 'Only an authorized user can file the scan for a signed, LCE-approved version.');
            $this->redirect($returnPage, $returnPage === 'lce' ? [] : ['edit' => $documentId, 'type' => 'legislative']);
        }
        $scan = $_FILES['scan_file'] ?? null;
        if (!$scan || ($scan['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($scan['size'] ?? 0) > 25 * 1024 * 1024) {
            $this->flash('error', 'Upload a filing scan smaller than 25 MB.');
            $this->redirect('legislative-admin', ['edit' => $documentId, 'type' => 'legislative']);
        }
        $extension = strtolower(pathinfo((string)($scan['name'] ?? ''), PATHINFO_EXTENSION));
        $mimeType = mime_content_type((string)$scan['tmp_name']);
        $allowedTypes = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
        if (!isset($allowedTypes[$extension]) || $allowedTypes[$extension] !== $mimeType) {
            $this->flash('error', 'Filing scans must be PDF, JPG, or PNG files.');
            $this->redirect('legislative-admin', ['edit' => $documentId, 'type' => 'legislative']);
        }
        $uploadDirectory = __DIR__ . '/../../storage/uploads';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
            $this->flash('error', 'Unable to prepare the filing scan directory.');
            $this->redirect('legislative-admin', ['edit' => $documentId, 'type' => 'legislative']);
        }
        $scanName = $this->model->createUidFileName((string)$scan['name']);
        $scanPath = $uploadDirectory . DIRECTORY_SEPARATOR . $scanName;
        if (!move_uploaded_file((string)$scan['tmp_name'], $scanPath)) {
            $this->flash('error', 'Unable to save the filing scan.');
            $this->redirect('legislative-admin', ['edit' => $documentId, 'type' => 'legislative']);
        }
        $this->model->updateLegislative($documentId, [
            'scan_file_path' => $scanName, 'archive_status' => 'Filing/Scan complete', 'updatedAt' => date(DATE_ATOM),
        ]);
        $this->model->recordLegislativeAudit($documentId, 'filing_scan_completed', $currentUser, [
            'file' => $scanName, 'sha256' => hash_file('sha256', $scanPath),
        ]);
        $this->flash('success', 'Filing scan recorded without changing the signed document.');
        $this->redirect('legislative-admin', ['edit' => $documentId, 'type' => 'legislative']);
    }

    private function legislativeContentHash(array $document): string
    {
        $fileHash = '';
        if (!empty($document['file_path'])) {
            $path = __DIR__ . '/../../storage/uploads/' . basename((string)$document['file_path']);
            $fileHash = is_file($path) ? (string)hash_file('sha256', $path) : '';
        }
        return hash('sha256', json_encode([
            'control_number' => $document['control_number'] ?? '',
            'title' => $document['title'] ?? '',
            'content' => $document['content'] ?? '',
            'file_hash' => $fileHash,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function handleSendLegislativeToRecipients(): void
    {
        $currentUser = $this->getCurrentUser();
        $legislativeId = trim($_POST['legislative_id'] ?? '');
        $rawRecipientIds = $_POST['recipients'] ?? $_POST['recipient_ids'] ?? [];

        if (!$currentUser || !in_array($currentUser['role'] ?? '', ['admin', 'council members'], true)) {
            $this->flash('error', 'Unauthorized.');
            $this->redirect('dashboard');
        }

        $requestedPage = in_array($_GET['page'] ?? '', ['legislative-admin', 'legislative-user', 'legislative-bills'], true) ? $_GET['page'] : null;
        $redirectPage = $requestedPage ?? ($this->isAdminUser() ? 'legislative-admin' : 'legislative-user');

        if ($legislativeId === '') {
            $this->flash('error', 'Legislative ID is required.');
            $this->redirect($redirectPage);
        }

        $legislative = $this->model->findLegislative($legislativeId);
        if (!$legislative) {
            $this->flash('error', 'Legislative record not found.');
            $this->redirect($redirectPage);
        }

        if (($currentUser['role'] ?? '') === 'council members' && ($legislative['createdById'] ?? '') !== ($currentUser['id'] ?? '')) {
            $this->flash('error', 'You can only send legislative documents that you created.');
            $this->redirect($redirectPage);
        }

        $allowedRecipients = $this->getCouncilRecipients($currentUser['id'] ?? null);
        $allowedRecipientMap = [];
        foreach ($allowedRecipients as $recipient) {
            $allowedRecipientMap[$recipient['id']] = $recipient;
        }

        $recipientIds = is_array($rawRecipientIds) ? $rawRecipientIds : [$rawRecipientIds];
        $recipientIds = array_values(array_unique(array_filter(array_map('strval', $recipientIds), function (string $recipientId) use ($allowedRecipientMap): bool {
            return $recipientId !== '' && isset($allowedRecipientMap[$recipientId]);
        })));

        if (empty($recipientIds)) {
            $this->flash('error', 'Please choose at least one council member to receive this legislative draft.');
            $this->redirect($redirectPage);
        }

        $updateOk = $this->model->updateLegislative($legislativeId, [
            'sent_to_user_ids' => json_encode($recipientIds),
            'sent_by_id' => $currentUser['id'] ?? null,
            'sent_by_name' => $currentUser['name'] ?? null,
            'sent_at' => date(DATE_ATOM),
            'updatedAt' => date(DATE_ATOM),
        ]);

        if (!$updateOk) {
            $this->flash('error', 'Unable to save the send record.');
            $this->redirect($this->isAdminUser() ? 'legislative-admin' : 'legislative-user');
        }

        $sentCount = 0;
        foreach ($recipientIds as $recipientId) {
            $recipient = $allowedRecipientMap[$recipientId] ?? null;
            if (!$recipient) {
                continue;
            }

            $message = sprintf(
                '%s sent legislative draft "%s" to you.',
                $currentUser['name'] ?? 'A user',
                $legislative['title'] ?? 'Untitled'
            );

            if ($this->model->addUserNotification($recipientId, $message, 'index.php?page=legislative-user')) {
                $sentCount++;
            }

            if (!empty($recipient['email']) && filter_var($recipient['email'], FILTER_VALIDATE_EMAIL)) {
                $this->sendLegislativeNotificationEmail($recipient['email'], $legislative, $currentUser);
            }
        }

        $this->flash('success', sprintf('Legislative draft sent to %d council member(s).', $sentCount));
        $this->redirect($redirectPage);
    }

    private function handleSubmitLegislativeToAdmin(): void
    {
        $currentUser = $this->getCurrentUser();
        $legislativeId = trim($_POST['legislative_id'] ?? '');
        if (!$currentUser || ($currentUser['role'] ?? '') !== 'council members') {
            $this->flash('error', 'Only council members can submit documents to the admin.');
            $this->redirect('legislative-user');
        }

        $legislative = $this->model->findLegislative($legislativeId);
        if (!$legislative || ($legislative['createdById'] ?? '') !== ($currentUser['id'] ?? '')) {
            $this->flash('error', 'You can only submit documents that you created.');
            $this->redirect('legislative-user');
        }

        $submittedAt = date(DATE_ATOM);
        $documentType = strtolower(trim((string)($legislative['type'] ?? '')));
        $workflowStatus = in_array($documentType, ['resolution', 'ordinance'], true) ? 'Review' : 'Agenda';
        $this->model->updateLegislative($legislativeId, [
            'status' => 'submitted_to_admin',
            'workflow_status' => $workflowStatus,
            'routing_status' => 'For Agenda',
            'sent_by_id' => $currentUser['id'],
            'sent_by_name' => $currentUser['name'] ?? 'Council Member',
            'sent_at' => $submittedAt,
            'updatedAt' => $submittedAt,
        ]);

        $admins = array_filter($this->model->getUsers(), fn (array $user): bool => ($user['role'] ?? '') === 'admin' && ($user['status'] ?? 'active') === 'active');
        $message = sprintf('Council member %s submitted "%s" for admin agenda preparation.', $currentUser['name'] ?? 'A council member', $legislative['title'] ?? 'Untitled document');
        foreach ($admins as $admin) {
            $this->model->addUserNotification($admin['id'], $message, 'index.php?page=legislative-admin&edit=' . rawurlencode($legislativeId) . '&type=legislative');
        }
        $this->model->addUserNotification(
            $currentUser['id'],
            sprintf('Your document "%s" was sent to the admin and is waiting for agenda preparation.', $legislative['title'] ?? 'Untitled document'),
            'index.php?page=legislative-user&edit=' . rawurlencode($legislativeId) . '&type=legislative'
        );

        $this->flash('success', 'Document submitted to the admin for agenda preparation.');
        $this->redirect('legislative-user', ['edit' => $legislativeId, 'type' => 'legislative']);
    }

    private function handlePrepareSubmittedBillForAgenda(): void
    {
        $currentUser = $this->getCurrentUser();
        $legislativeId = trim((string)($_POST['legislative_id'] ?? ''));
        if (!$currentUser || !$this->isAdminUser()) {
            $this->flash('error', 'Only an admin can prepare a submitted bill for agenda review.');
            $this->redirect('legislative-bills', ['bill_status' => 'pending']);
        }

        $legislative = $this->model->findLegislative($legislativeId);
        if (!$legislative || ($legislative['status'] ?? '') !== 'submitted_to_admin') {
            $this->flash('error', 'Only a bill submitted to the admin can be prepared for agenda review.');
            $this->redirect('legislative-bills', ['bill_status' => 'pending']);
        }

        $previousStatus = (string)($legislative['workflow_status'] ?? 'Unknown');
        $now = date(DATE_ATOM);
        $this->model->updateLegislative($legislativeId, [
            'workflow_status' => 'Agenda',
            'updatedAt' => $now,
            'lastModifiedById' => $currentUser['id'] ?? null,
            'lastModifiedByName' => $currentUser['name'] ?? null,
            'lastModifiedAt' => $now,
        ]);
        $this->model->recordLegislativeAudit($legislativeId, 'status_changed', $currentUser, [
            'from' => $previousStatus,
            'to' => 'Agenda',
            'reason' => 'Submitted bill prepared for agenda review',
        ]);

        $this->flash('success', 'Bill is ready for admin review and agenda setup.');
        $this->redirect('legislative-bills', ['bill_status' => 'pending']);
    }

    private function handleCreateAgendaFromLegislative(): void
    {
        $currentUser = $this->getCurrentUser();
        $legislativeId = trim($_POST['legislative_id'] ?? '');
        $sessionId = trim($_POST['session_id'] ?? '');
        if (!$currentUser || !$this->isAdminUser()) {
            $this->flash('error', 'Only an admin can create an agenda from a council submission.');
            $this->redirect('legislative-admin');
        }

        $legislative = $this->model->findLegislative($legislativeId);
        $session = $sessionId !== '' ? $this->model->findSession($sessionId) : null;
        if (!$legislative || !$session) {
            $this->flash('error', 'A valid council document and session are required.');
            $this->redirect('legislative-admin');
        }
        if (empty($legislative['file_path'])) {
            $this->flash('error', 'The council document needs an uploaded file before it can become an agenda.');
            $this->redirect('legislative-admin', ['edit' => $legislativeId, 'type' => 'legislative']);
        }
        if (($legislative['status'] ?? '') !== 'approved') {
            $this->flash('error', 'The bill must be approved before it can be added to an agenda.');
            $this->redirect('legislative-admin', ['edit' => $legislativeId, 'type' => 'legislative']);
        }

        $agendaId = $this->model->createAgenda([
            'title' => $legislative['title'],
            'file_name' => $legislative['file_name'] ?? basename($legislative['file_path']),
            'file_size' => (int)($legislative['file_size'] ?? 0),
            'file_type' => $legislative['file_type'] ?? 'application/pdf',
            'session_id' => $sessionId,
            'file_path' => $legislative['file_path'],
            'source_legislative_id' => $legislativeId,
        ]);

        $this->model->updateLegislative($legislativeId, [
            'status' => 'agenda_created',
            'routing_status' => 'For Agenda',
            'updatedAt' => date(DATE_ATOM),
        ]);

        $ownerId = $legislative['createdById'] ?? '';
        if ($ownerId !== '') {
            $this->model->addUserNotification(
                $ownerId,
                sprintf('Your document "%s" was added to the agenda for session "%s".', $legislative['title'], $session['title']),
                'index.php?page=tablet'
            );
        }

        $this->flash('success', 'Council document added to the agenda for ' . $session['title'] . '.');
        $this->redirect('legislative-admin', ['edit' => $legislativeId, 'type' => 'legislative']);
    }

    private function handleApproveLegislative(): void
    {
        $currentUser = $this->getCurrentUser();
        $legislativeId = trim($_POST['legislative_id'] ?? '');
        $sessionId = trim($_POST['session_id'] ?? '');
        $redirectPage = in_array($_GET['page'] ?? '', ['legislative-admin', 'legislative-user', 'legislative-bills', 'legislative-audit-trail'], true) ? $_GET['page'] : 'legislative-admin';

        if (!$currentUser || !$this->isAdminUser()) {
            $this->flash('error', 'Unauthorized.');
            $this->redirect('dashboard');
        }

        if ($legislativeId === '') {
            $this->flash('error', 'Legislative ID is required.');
            $this->redirect('legislative-admin');
        }

        $legislative = $this->model->findLegislative($legislativeId);
        if (!$legislative) {
            $this->flash('error', 'Legislative record not found.');
            $this->redirect('legislative-admin');
        }

        $workflowStatus = (string)($legislative['workflow_status'] ?? '');
        $canCreateAgendaFromAgenda = $workflowStatus === 'Agenda';
        if (!in_array($workflowStatus, ['Approval', 'Distribution'], true) && !$canCreateAgendaFromAgenda) {
            $this->flash('error', 'Move this record to the appropriate agenda stage before setting its session agenda.');
            $redirectParams = match ($redirectPage) {
                'legislative-bills' => in_array($_GET['bill_status'] ?? '', ['pending', 'all'], true) ? ['bill_status' => $_GET['bill_status']] : [],
                'legislative-audit-trail' => ['ledger_id' => $legislativeId],
                default => ['edit' => $legislativeId, 'type' => 'legislative'],
            };
            $this->redirect($redirectPage, $redirectParams);
        }

        $session = $sessionId !== '' ? $this->model->findSession($sessionId) : null;
        if (!$session) {
            $this->flash('error', 'Select a valid session before making this agenda.');
            $this->redirect($redirectPage);
        }

        $officers = array_values(array_filter($this->model->getUsers(), static fn(array $user): bool =>
            ($user['role'] ?? '') === 'presiding officer' && ($user['status'] ?? 'active') === 'active'
        ));
        if (!$officers) {
            $this->flash('error', 'Create an active Presiding Officer account before setting this agenda.');
            $this->redirect($redirectPage);
        }
        $officerIds = array_column($officers, 'id');

        $agendaFile = $_FILES['agenda_file'] ?? null;
        if ($agendaFile && ($agendaFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $agendaUpload = $this->saveApprovedAgendaUpload($agendaFile, $legislative['title'] ?? 'agenda');
        } elseif (!empty($legislative['file_path'])) {
            $existingPath = __DIR__ . '/../../storage/uploads/' . basename((string)$legislative['file_path']);
            $agendaUpload = is_file($existingPath) ? [
                'file_name' => $legislative['file_name'] ?? basename($existingPath),
                'file_size' => filesize($existingPath),
                'file_type' => $legislative['file_type'] ?? mime_content_type($existingPath),
                'file_path' => basename($existingPath),
            ] : null;
        } else {
            $agendaUpload = null;
        }
        if ($agendaUpload === null) {
            $this->flash('error', 'Attach or upload a readable agenda document before publishing.');
            $this->redirect($redirectPage);
        }

        $this->model->updateLegislative($legislativeId, [
            'status' => 'agenda_created',
            'workflow_status' => 'Agenda Signing',
            'routing_status' => 'Awaiting Presiding Officer signature',
            'updatedAt' => date(DATE_ATOM),
            'lastModifiedById' => $currentUser['id'] ?? null,
            'lastModifiedByName' => $currentUser['name'] ?? null,
            'lastModifiedAt' => date(DATE_ATOM),
        ]);

        $agendas = $this->model->getData()['agendas'] ?? [];
        $existingAgenda = null;
        foreach ($agendas as $agenda) {
            if (($agenda['source_legislative_id'] ?? '') === $legislativeId) {
                $existingAgenda = $agenda;
                break;
            }
        }

        if ($existingAgenda) {
            $agendaId = $existingAgenda['id'];
            $this->model->updateAgenda($agendaId, [
                'title' => $legislative['title'] ?? 'Legislative document',
                'file_name' => $agendaUpload['file_name'],
                'file_size' => $agendaUpload['file_size'],
                'file_type' => $agendaUpload['file_type'],
                'session_id' => $sessionId,
                'file_path' => $agendaUpload['file_path'],
                'published' => 0,
                'published_at' => null,
                'approval_status' => 'awaiting_proceeding_review',
                'approval_stage' => 'awaiting_proceeding',
                'sent_to_proceeding_id' => json_encode($officerIds),
                'sent_to_proceeding_date' => date(DATE_ATOM),
                'sent_to_council_at' => null,
                'distribution_status' => 'queued',
                'distributed_at' => null,
            ]);
            $agenda = $this->model->findAgenda($agendaId);
        } else {
            $agendaId = $this->model->createAgenda([
                'title' => $legislative['title'] ?? 'Legislative document',
                'file_name' => $agendaUpload['file_name'],
                'file_size' => $agendaUpload['file_size'],
                'file_type' => $agendaUpload['file_type'],
                'session_id' => $sessionId,
                'file_path' => $agendaUpload['file_path'],
                'published' => 0,
                'approval_status' => 'awaiting_proceeding_review',
                'approval_stage' => 'awaiting_proceeding',
                'sent_to_proceeding_id' => json_encode($officerIds),
                'sent_to_proceeding_date' => date(DATE_ATOM),
                'source_legislative_id' => $legislativeId,
            ]);
            $agenda = $this->model->findAgenda($agendaId);
        }

        $officerMessage = sprintf('Agenda "%s" is ready for your PKI signature.', $agenda['title'] ?? $legislative['title'] ?? 'Untitled');
        foreach ($officers as $officer) {
            $this->model->addUserNotification((string)$officer['id'], $officerMessage, 'index.php?page=proceeding-officer');
        }
        $this->model->recordLegislativeAudit($legislativeId, 'agenda_set_for_officer_signature', $currentUser, [
            'agenda_id' => $agendaId, 'session_id' => $sessionId, 'stage' => 'Agenda Signing',
        ]);

        $ownerId = $legislative['createdById'] ?? '';
        if ($ownerId !== '' && $ownerId !== ($currentUser['id'] ?? '')) {
            $this->model->addUserNotification(
                $ownerId,
                sprintf('Your bill "%s" was placed on the agenda for "%s" and is waiting for Presiding Officer signature.', $legislative['title'] ?? 'Untitled document', $session['title'] ?? 'the selected session'),
                'index.php?page=legislative-audit-trail&ledger_id=' . rawurlencode($legislativeId)
            );
        }

        $this->flash('success', 'Agenda set for Presiding Officer signature. Council will be notified after it is signed.');
        $this->redirect($redirectPage);
    }

    private function handleDisapproveLegislative(): void
    {
        $currentUser = $this->getCurrentUser();
        $legislativeId = trim((string)($_POST['legislative_id'] ?? ''));
        $reason = trim((string)($_POST['disapproval_reason'] ?? ''));
        if (!$currentUser || !$this->isAdminUser()) {
            $this->flash('error', 'Only an admin can disapprove a submitted legislative matter.');
            $this->redirect('legislative-admin');
        }
        if ($legislativeId === '' || $reason === '') {
            $this->flash('error', 'A legislative record and disapproval reason are required.');
            $this->redirect('legislative-bills', ['bill_status' => 'pending']);
        }

        $legislative = $this->model->findLegislative($legislativeId);
        if (!$legislative || ($legislative['status'] ?? '') !== 'submitted_to_admin') {
            $this->flash('error', 'Only submitted legislative matters can be disapproved.');
            $this->redirect('legislative-bills', ['bill_status' => 'pending']);
        }

        $now = date(DATE_ATOM);
        $this->model->updateLegislative($legislativeId, [
            'status' => 'disapproved',
            'routing_status' => 'Returned for Revision',
            'workflow_status' => 'Final Draft',
            'admin_decision_notes' => $reason,
            'admin_decision_at' => $now,
            'lastModifiedById' => $currentUser['id'] ?? null,
            'lastModifiedByName' => $currentUser['name'] ?? 'Admin',
            'lastModifiedAt' => $now,
            'updatedAt' => $now,
        ]);
        $this->model->recordLegislativeAudit($legislativeId, 'returned_for_revision', $currentUser, ['reason' => $reason]);

        $ownerId = $legislative['createdById'] ?? null;
        if ($ownerId) {
            $this->model->addUserNotification(
                $ownerId,
                sprintf('Your legislative matter "%s" was returned for revision: %s', $legislative['title'] ?? 'Untitled document', $reason),
                'index.php?page=legislative-user&edit=' . rawurlencode($legislativeId) . '&type=legislative'
            );
        }
        $this->flash('success', 'Legislative matter disapproved and returned for revision.');
        $this->redirect('legislative-bills', ['bill_status' => 'pending']);
    }

    private function handleSendLceApprovedToAgenda(): void
    {
        $currentUser = $this->getCurrentUser();
        $legislativeId = trim((string)($_POST['legislative_id'] ?? ''));
        $sessionId = trim((string)($_POST['session_id'] ?? ''));
        if (!$currentUser || !$this->isAdminUser()) {
            $this->flash('error', 'Only an admin can add an LCE-approved matter to an agenda.');
            $this->redirect('session-control');
        }
        $legislative = $this->model->findLegislative($legislativeId);
        $session = $sessionId !== '' ? $this->model->findSession($sessionId) : null;
        if (!$legislative || ($legislative['lce_action'] ?? '') !== 'LCE Approved' || !$session) {
            $this->flash('error', 'Only an LCE-approved matter and a valid session can be added to the agenda.');
            $this->redirect('session-control');
        }
        if (empty($legislative['file_path'])) {
            $this->flash('error', 'This legislative matter has no locally stored file to add to the agenda.');
            $this->redirect('session-control');
        }

        $agendaId = $this->model->createAgenda([
            'title' => $legislative['title'] ?? 'LCE-approved legislative matter',
            'file_name' => $legislative['file_name'] ?? basename((string)$legislative['file_path']),
            'file_size' => $legislative['file_size'] ?? filesize(__DIR__ . '/../../storage/uploads/' . basename((string)$legislative['file_path'])),
            'file_type' => $legislative['file_type'] ?? 'application/octet-stream',
            'session_id' => $sessionId,
            'file_path' => basename((string)$legislative['file_path']),
            'published' => 1,
            'published_at' => date(DATE_ATOM),
            'source_legislative_id' => $legislativeId,
        ]);
        $agenda = $this->model->findAgenda($agendaId);
        $this->notifyCouncilOfAgenda($agenda);
        $this->model->updateLegislative($legislativeId, [
            'status' => 'agenda_created',
            'routing_status' => 'For Agenda',
            'updatedAt' => date(DATE_ATOM),
        ]);
        $this->flash('success', 'LCE-approved legislative matter added as the agenda item for ' . ($session['title'] ?? 'the selected session') . '.');
        $this->redirect('session-control');
    }

    private function handleProcessLceDecision(): void
    {
        $currentUser = $this->getCurrentUser();
        $legislativeId = trim((string)($_POST['legislative_id'] ?? ''));
        $decision = strtolower(trim((string)($_POST['decision'] ?? '')));
        if (!$currentUser || !in_array(($currentUser['role'] ?? ''), ['lce', 'mayor'], true)) {
            $this->flash('error', 'Only the LCE can process this decision.');
            $this->redirect('lce');
        }
        if (!in_array($decision, ['approved', 'vetoed'], true)) {
            $this->flash('error', 'Choose Approved or Vetoed.');
            $this->redirect('lce');
        }
        $legislative = $this->model->findLegislative($legislativeId);
        if (!$legislative) {
            $this->flash('error', 'Legislative record not found.');
            $this->redirect('lce');
        }
        if (($legislative['lce_action'] ?? '') !== 'Pending LCE Review') {
            $this->flash('error', 'Only a matter pending LCE review can be approved or vetoed.');
            $this->redirect('lce');
        }
        if ($decision === 'approved') {
            $this->flash('error', 'Approve this matter through the PKI signing form using your personal signing key.');
            $this->redirect('lce');
        }

        $now = date(DATE_ATOM);
        $approved = $decision === 'approved';
        $signedWorkflowDecision = ($legislative['workflow_status'] ?? '') === 'LCE Approval'
            && !empty($legislative['signed_hash']) && !empty($legislative['locked_at']);
        $this->model->updateLegislative($legislativeId, [
            'status' => $approved ? 'approved_by_lce' : 'vetoed_by_lce',
            'routing_status' => $approved ? 'Archived for Local Download' : 'Returned to Council - Vetoed',
            'workflow_status' => $signedWorkflowDecision
                ? ($approved ? 'Archive' : 'Final Draft')
                : ($approved ? 'FURNISH COPIES' : 'VETOED'),
            'lce_action' => $approved ? 'LCE Approved' : 'Vetoed',
            'lce_action_at' => $now,
            'lce_action_by_id' => $currentUser['id'] ?? null,
            'archive_status' => $signedWorkflowDecision
                ? ($approved ? 'FILE - Local Offline Archive' : 'Returned for revision')
                : ($approved ? 'FILE - Local Offline Archive' : 'VETOED - Council Review Required'),
            'locked_at' => $signedWorkflowDecision && !$approved ? null : ($legislative['locked_at'] ?? null),
            'updatedAt' => $now,
            'lastModifiedById' => $currentUser['id'] ?? null,
            'lastModifiedByName' => $currentUser['name'] ?? 'LCE',
            'lastModifiedAt' => $now,
        ]);
        if ($signedWorkflowDecision) {
            $this->model->recordLegislativeAudit($legislativeId, $approved ? 'lce_approved' : 'lce_vetoed', $currentUser, [
                'from' => 'LCE Approval', 'to' => $approved ? 'Filing/Scan' : 'Final Draft',
            ]);
            if (!$approved) {
                $revisedDocument = $this->model->findLegislative($legislativeId);
                $revisionNumber = $this->model->createLegislativeVersion($legislativeId, $revisedDocument ?? $legislative, $currentUser);
                $this->model->updateLegislativeVersion($legislativeId, $revisionNumber, 'Final Draft v' . $revisionNumber);
            }
        }

        $ownerId = $legislative['createdById'] ?? null;
        if ($ownerId) {
            $message = $approved
                ? 'Your legislative matter "' . ($legislative['title'] ?? 'Untitled') . '" was approved by the LCE and stored in the local archive.'
                : 'Your legislative matter "' . ($legislative['title'] ?? 'Untitled') . '" was vetoed by the LCE.';
            $this->model->addUserNotification($ownerId, $message, 'index.php?page=legislative-user&edit=' . rawurlencode($legislativeId) . '&type=legislative');
            $owner = $this->model->getUserById((string)$ownerId);
            if ($owner && !empty($owner['email']) && filter_var($owner['email'], FILTER_VALIDATE_EMAIL)) {
                $this->sendLegislativeNotificationEmail($owner['email'], $legislative, $currentUser);
            }
        }
        $this->flash('success', $approved ? 'Approved by LCE and stored for offline download.' : 'LCE veto recorded and submitter notified.');
        $this->redirect('lce');
    }

    private function saveApprovedAgendaUpload(?array $file, string $title): ?array
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Upload the agenda file before approving this bill or resolution.');
            return null;
        }

        $uploadDir = __DIR__ . '/../../storage/uploads';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            $this->flash('error', 'Unable to prepare the agenda upload folder.');
            return null;
        }

        $targetName = $this->model->createUidFileName($file['name'] ?? $title);
        $targetPath = $uploadDir . '/' . $targetName;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->flash('error', 'Unable to save the uploaded agenda file.');
            return null;
        }

        return [
            'file_name' => $file['name'] ?? $targetName,
            'file_size' => (int)($file['size'] ?? filesize($targetPath)),
            'file_type' => $file['type'] ?: mime_content_type($targetPath),
            'file_path' => $targetName,
        ];
    }

    private function getCouncilRecipients(?string $excludeUserId = null): array
    {
        $users = $this->model->getUsers();

        return array_values(array_filter($users, function (array $user) use ($excludeUserId): bool {
            if (($user['role'] ?? '') !== 'council members') {
                return false;
            }

            if (($user['status'] ?? 'active') !== 'active') {
                return false;
            }

            if ($excludeUserId !== null && $excludeUserId !== '' && ($user['id'] ?? '') === $excludeUserId) {
                return false;
            }

            return true;
        }));
    }

    private function canViewLegislativeRecord(array $item, array $currentUser): bool
    {
        $userId = $currentUser['id'] ?? '';
        if ($userId === '') {
            return false;
        }

        if (($item['createdById'] ?? '') === $userId) {
            return true;
        }

        if (in_array(($currentUser['role'] ?? ''), ['lce', 'mayor'], true)
            && (in_array($item['routing_status'] ?? '', ['Sent to LCE', 'Archived for Local Download', 'Returned to Council - Vetoed'], true)
                || in_array($item['lce_action'] ?? '', ['Pending LCE Review', 'LCE Approved', 'Vetoed'], true))) {
            return true;
        }

        if (($item['status'] ?? '') === 'approved') {
            return true;
        }

        $sentTo = $item['sent_to_user_ids'] ?? '';
        if ($sentTo !== '') {
            $decoded = json_decode($sentTo, true);
            if (is_array($decoded) && in_array($userId, $decoded, true)) {
                return true;
            }
            if ($sentTo === $userId) {
                return true;
            }
        }

        return false;
    }

    private function sendLegislativeNotificationEmail(string $email, array $legislative, array $sender): void
    {
        $subject = 'Legislative Draft: ' . ($legislative['title'] ?? 'Untitled');
        $body = 'You have received a legislative draft.' . "\n\n";
        $body .= 'Title: ' . ($legislative['title'] ?? 'Untitled') . "\n";
        $body .= 'Type: ' . ($legislative['type'] ?? 'N/A') . "\n";
        $body .= 'Status: ' . ($legislative['status'] ?? 'draft') . "\n";
        $body .= 'Sent by: ' . ($sender['name'] ?? 'Unknown') . "\n\n";

        if (!empty($legislative['desc'])) {
            $body .= 'Description: ' . $legislative['desc'] . "\n\n";
        }

        if (!empty($legislative['file_path'])) {
            $downloadUrl = sprintf('%s/download.php?file=%s', $this->getBaseUrl(), rawurlencode($legislative['file_path']));
            $body .= 'Attachment: ' . $downloadUrl . "\n\n";
        }

        $headers = "From: no-reply@localhost\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        @mail($email, $subject, $body, $headers);
    }

    private function getCurrentUser(): ?array
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return null;
        }
        return $this->model->getUserById($userId);
    }

    private function isAuthenticated(): bool
    {
        return $this->getCurrentUser() !== null;
    }

    private function isAdminUser(): bool
    {
        $user = $this->getCurrentUser();
        return $user !== null && in_array($user['role'], ['admin'], true);
    }

    private function handleDelete(): void
    {
        $type = $_POST['item_type'] ?? '';
        $id = $_POST['item_id'] ?? '';
        $currentUser = $this->getCurrentUser();

        if ($type === 'user') {
            if ($currentUser && $currentUser['id'] === $id) {
                $this->flash('error', 'You cannot delete your own admin account. You can still edit your credentials.');
                $this->redirect('users');
            }

            $this->model->deleteUser($id);
            $this->flash('success', 'User deleted successfully.');
            $this->redirect('users');
        }

        if ($type === 'session') {
            $this->model->deleteSession($id);
            $this->flash('success', 'Session deleted successfully.');
            $this->redirect('sessions');
        }

        if ($type === 'agenda') {
            $agenda = $this->model->findAgenda($id);
            if ($agenda && !empty($agenda['file_path'])) {
                $filePath = __DIR__ . '/../../storage/uploads/' . basename($agenda['file_path']);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            $this->model->deleteAgenda($id);
            $this->flash('success', 'Agenda deleted successfully.');
            $this->redirect('agendas');
        }

        if ($type === 'legislative') {
            $legislative = $this->model->findLegislative($id);
            if (!$legislative || (!$this->isAdminUser() && ($legislative['createdById'] ?? '') !== ($currentUser['id'] ?? ''))) {
                $this->flash('error', 'You can only delete legislative records that you created.');
                $this->redirect('legislative-user');
            }
            if (!empty($legislative['file_path'])) {
                $filePath = __DIR__ . '/../../storage/uploads/' . basename($legislative['file_path']);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            $this->model->deleteLegislative($id);
            $this->flash('success', 'Legislative record deleted successfully.');
            $redirectPage = $_GET['page'] ?? ($this->isAdminUser() ? 'legislative-admin' : 'legislative-user');
            $this->redirect($redirectPage);
        }

        $this->flash('error', 'Unable to delete item.');
        $this->redirect('dashboard');
    }

    private function handlePublishAgenda(): void
    {
        $id = $_POST['id'] ?? '';
        $publish = isset($_POST['publish']) && ($_POST['publish'] === '1' || $_POST['publish'] === 'true');
        $sessionId = trim($_POST['session_id'] ?? '');
        
        if ($id === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing id']);
            return;
        }
        
        // If approving, require a session
        if ($publish && $sessionId === '') {
            $this->flash('error', 'Please select a schedule first.');
            $this->redirect('agendas');
            return;
        }
        
        $changes = [];
        if ($publish) {
            $changes['published'] = 1;
            $changes['published_at'] = date(DATE_ATOM);
            $changes['session_id'] = $sessionId;
            $changes['distribution_status'] = 'queued';
            $changes['distributed_at'] = null;
        } else {
            $changes['published'] = 0;
            $changes['published_at'] = null;
        }
        $ok = $this->model->updateAgenda($id, $changes);
        if ($ok && $publish) {
            $agenda = $this->model->findAgenda($id);
            $this->notifyCouncilOfAgenda($agenda);
            $this->model->updateAgenda($id, [
                'distribution_status' => 'sent',
                'distributed_at' => date(DATE_ATOM),
            ]);
        }
        $isXhr = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
        if ($isXhr) {
            header('Content-Type: application/json');
            echo json_encode(['success' => (bool)$ok, 'published' => $changes['published'], 'published_at' => $changes['published_at']]);
            return;
        }
        $this->flash('success', $publish ? 'Agenda schedule saved.' : 'Agenda schedule removed.');
        $this->redirect('agendas');
    }

    private function handleSendAgendaToOfficer(): void
    {
        $agendaId = $_POST['agenda_id'] ?? '';
        $officerIds = $_POST['officer_ids'] ?? [];
        $templateId = trim($_POST['template_id'] ?? '');
        
        if (!is_array($officerIds)) {
            $officerIds = [];
        }
        $officerIds = array_filter($officerIds, fn($id) => $id !== '');

        if ($agendaId === '' || count($officerIds) === 0) {
            $this->flash('error', 'Please select at least one recipient.');
            $this->redirect('agendas');
            return;
        }

        $invalidOfficers = [];
        foreach ($officerIds as $officerId) {
            $officer = $this->model->getUserById($officerId);
            if (!$officer || ($officer['status'] ?? 'active') !== 'active' || $officer['role'] !== 'presiding officer') {
                $invalidOfficers[] = $officerId;
            }
        }

        if (count($invalidOfficers) > 0) {
            $this->flash('error', 'One or more selected officers are invalid.');
            $this->redirect('agendas');
            return;
        }

        $agenda = $this->model->findAgenda($agendaId);
        if (!$agenda) {
            $this->flash('error', 'Agenda not found.');
            $this->redirect('agendas');
            return;
        }

        if (empty($agenda['session_id'])) {
            $this->flash('error', 'Set a session schedule before sending this agenda for signature.');
            $this->redirect('agendas');
            return;
        }

        // Store officer IDs as JSON for tracking multiple reviewers
        $changes = [
            'approval_status' => 'awaiting_proceeding_review',
            'approval_stage' => 'awaiting_proceeding',
            'sent_to_proceeding_id' => json_encode($officerIds),
            'sent_to_proceeding_date' => date(DATE_ATOM),
        ];

        $this->model->updateAgenda($agendaId, $changes);
        
        $template = null;
        if ($templateId !== '') {
            $template = $this->findDeliveryTemplate($templateId);
        }

        $officerNames = array_map(fn($id) => $this->model->getUserById($id)['name'] ?? 'Officer', $officerIds);
        $message = 'Agenda sent to ' . implode(', ', $officerNames) . ' for approval.';
        $this->logDeliveryHistory('agenda', $agendaId, $agenda['title'] ?? 'Agenda', 'sent_to_officer', $officerIds, $agenda['session_id'] ?? null, $message, $template);
        
        $this->flash('success', $message);
        $this->redirect('agendas');
    }

    private function handleSendSessionMinutes(): void
    {
        $currentUser = $this->getCurrentUser();
        if (!$currentUser || !$this->isAdminUser()) {
            $this->flash('error', 'Unauthorized.');
            $this->redirect('sessions');
            return;
        }

        $sessionId = trim($_POST['session_id'] ?? '');
        $recipientIds = $_POST['recipient_ids'] ?? [];
        $templateId = trim($_POST['template_id'] ?? '');
        if (!is_array($recipientIds)) {
            $recipientIds = [];
        }
        $recipientIds = array_values(array_unique(array_filter(array_map('strval', $recipientIds), fn ($id) => $id !== '')));
        $notes = trim($_POST['notes'] ?? '');

        if ($sessionId === '' || count($recipientIds) === 0) {
            $this->flash('error', 'Please select at least one recipient and a session.');
            $this->redirect('sessions');
            return;
        }

        $session = $this->model->findSession($sessionId);
        if (!$session) {
            $this->flash('error', 'Session not found.');
            $this->redirect('sessions');
            return;
        }

        if (!$this->isSessionMinutesDeliveryAvailable($session, $this->model->getDeliveryHistory())) {
            $this->flash('error', 'Minutes can only be sent once the session has ended, and only once per session.');
            $this->redirect('sessions');
            return;
        }

        $presidingOfficerIds = [];
        foreach ($recipientIds as $recipientId) {
            $recipient = $this->model->getUserById($recipientId);
            if ($recipient && ($recipient['role'] ?? '') === 'presiding officer' && ($recipient['status'] ?? 'active') === 'active') {
                $presidingOfficerIds[] = (string)$recipient['id'];
            }
        }
        if (count($presidingOfficerIds) !== count($recipientIds) || count($presidingOfficerIds) === 0) {
            $this->flash('error', 'Session minutes can only be sent to an active presiding officer for approval.');
            $this->redirect('sessions');
            return;
        }

        $recipientNames = [];
        foreach ($presidingOfficerIds as $recipientId) {
            $recipient = $this->model->getUserById($recipientId);
            if ($recipient) {
                $recipientNames[] = $recipient['name'] ?? $recipientId;
            }
        }

        $template = null;
        if ($templateId !== '') {
            $template = $this->findDeliveryTemplate($templateId);
        }

        $sentAt = date(DATE_ATOM);
        $this->model->updateSession($sessionId, [
            'minutes' => $this->formatSessionMinutesForDelivery($session),
            'minutes_recorded_at' => $sentAt,
            'minutes_workflow_status' => 'AWAITING PRESIDING OFFICER',
            'minutes_sent_at' => $sentAt,
        ]);
        $this->logDeliveryHistory('session_minutes', $sessionId, $session['title'] ?? 'Session Minutes', 'sent_to_presiding_officer', $presidingOfficerIds, $sessionId, $notes, $template);
        foreach ($presidingOfficerIds as $recipientId) {
            $this->model->addUserNotification($recipientId, 'Session minutes are ready for your approval and signature: ' . ($session['title'] ?? 'Session'), 'index.php?page=session-control');
        }
        $this->flash('success', 'Session minutes sent to the presiding officer for approval and signature.');
        $this->redirect('sessions');
    }

    private function formatSessionMinutesForDelivery(array $session): string
    {
        $sessionDate = trim((string)($session['date'] ?? ''));
        $dateTimestamp = $sessionDate !== '' ? strtotime($sessionDate) : false;
        $sessionDate = $dateTimestamp !== false ? date('F j, Y', $dateTimestamp) : ($sessionDate ?: 'Not recorded');

        $start = trim((string)($session['start'] ?? ''));
        $end = trim((string)($session['end'] ?? ''));
        $startTimestamp = $start !== '' ? strtotime($start) : false;
        $endTimestamp = $end !== '' ? strtotime($end) : false;
        $sessionTime = ($startTimestamp !== false ? date('g:i A', $startTimestamp) : ($start ?: 'Not recorded'))
            . ' - '
            . ($endTimestamp !== false ? date('g:i A', $endTimestamp) : ($end ?: 'Not recorded'));

        $attendance = array_values(array_filter(
            $this->model->getPresenceForSession((string)($session['id'] ?? '')),
            static fn(array $person): bool => ($person['role'] ?? '') === 'council members'
        ));
        $councilNames = array_values(array_unique(array_filter(array_map(
            static fn(array $person): string => trim((string)($person['name'] ?? '')),
            $attendance
        ))));

        return "SESSION DETAILS\n"
            . 'Date: ' . $sessionDate . "\n"
            . 'Time: ' . $sessionTime . "\n"
            . 'Location: ' . (trim((string)($session['location'] ?? '')) ?: 'Not recorded') . "\n"
            . 'Council present: ' . ($councilNames ? implode(', ', $councilNames) : 'No council attendance recorded')
            . "\n\nMINUTES\n"
            . (trim((string)($session['minutes'] ?? '')) !== ''
                ? trim((string)$session['minutes'])
                : 'No additional proceedings were entered in Session Control.');
    }

    private function isSessionMinutesDeliveryAvailable(array $session, array $deliveryHistory): bool
    {
        if (($session['status'] ?? '') === 'cancelled') {
            return false;
        }

        try {
            $timezone = new \DateTimeZone('Asia/Manila');
            $sessionEnd = new \DateTimeImmutable(trim((string)($session['date'] ?? '')) . ' ' . trim((string)($session['end'] ?? '')), $timezone);
        } catch (\Exception) {
            return false;
        }

        if ($sessionEnd > new \DateTimeImmutable('now', $timezone)) {
            return false;
        }

        $sessionId = (string)($session['id'] ?? '');
        foreach ($deliveryHistory as $entry) {
            $deliverySessionId = (string)($entry['session_id'] ?? $entry['item_id'] ?? '');
            if (($entry['item_type'] ?? '') === 'session_minutes' && $deliverySessionId === $sessionId) {
                return false;
            }
        }

        return true;
    }

    private function handleUpdateSessionMinutesWorkflow(): void
    {
        $currentUser = $this->getCurrentUser();
        $sessionId = trim((string)($_POST['session_id'] ?? ''));
        $operation = trim((string)($_POST['operation'] ?? ''));
        $isPresidingOfficerSignature = $operation === 'approve_and_sign'
            && ($currentUser['role'] ?? '') === 'presiding officer';
        if (!$currentUser || (!$this->isAdminUser() && !$isPresidingOfficerSignature)) {
            $this->flash('error', 'Only an admin can prepare minutes or the presiding officer can approve and sign them.');
            $this->redirect('session-control');
            return;
        }
        $session = $this->model->findSession($sessionId);
        if (!$session) {
            $this->flash('error', 'Session not found.');
            $this->redirect('session-control');
            return;
        }

        $now = date(DATE_ATOM);
        $changes = [];
        if ($operation === 'correct') {
            if (!in_array($session['minutes_workflow_status'] ?? 'APPROVED MINUTES', ['APPROVED MINUTES', 'CORRECT'], true)) {
                $this->flash('error', 'Minutes can only be corrected before they are sent for approval.');
                $this->redirect('session-control');
                return;
            }
            $minutes = trim((string)($_POST['minutes'] ?? ''));
            if ($minutes === '') {
                $this->flash('error', 'Enter the corrected minutes before saving.');
                $this->redirect('session-control');
            }
            $changes = ['minutes' => $minutes, 'minutes_recorded_at' => $now, 'minutes_workflow_status' => 'CORRECT', 'minutes_correction_notes' => trim((string)($_POST['correction_notes'] ?? ''))];
        } elseif ($operation === 'finalize') {
            if (!in_array($session['minutes_workflow_status'] ?? 'APPROVED MINUTES', ['APPROVED MINUTES', 'CORRECT'], true)) {
                $this->flash('error', 'Minutes can only be finalized before they are sent for approval.');
                $this->redirect('session-control');
                return;
            }
            $changes = ['minutes_workflow_status' => 'FINAL DRAFT'];
        } elseif ($operation === 'send') {
            if (($session['minutes_workflow_status'] ?? 'APPROVED MINUTES') !== 'FINAL DRAFT') {
                $this->flash('error', 'Minutes must be in Final Draft before sending them for signatures.');
                $this->redirect('session-control');
            }
            if (!$this->isSessionMinutesDeliveryAvailable($session, $this->model->getDeliveryHistory())) {
                $this->flash('error', 'Minutes can only be sent once the session has ended, and only once per session.');
                $this->redirect('session-control');
                return;
            }
            $presidingOfficerIds = [];
            foreach ($this->model->getUsers() as $recipient) {
                if (($recipient['role'] ?? '') === 'presiding officer' && ($recipient['status'] ?? 'active') === 'active') {
                    $presidingOfficerIds[] = (string)$recipient['id'];
                }
            }
            if (empty($presidingOfficerIds)) {
                $this->flash('error', 'No active presiding officer is available to receive the minutes.');
                $this->redirect('session-control');
                return;
            }
            $changes = [
                'minutes' => $this->formatSessionMinutesForDelivery($session),
                'minutes_recorded_at' => $now,
                'minutes_workflow_status' => 'AWAITING PRESIDING OFFICER',
                'minutes_sent_at' => $now,
            ];
            $this->model->updateSession($sessionId, $changes);
            $this->logDeliveryHistory('session_minutes', $sessionId, $session['title'] ?? 'Session Minutes', 'sent_to_presiding_officer', $presidingOfficerIds, $sessionId);
            foreach ($presidingOfficerIds as $recipientId) {
                $this->model->addUserNotification($recipientId, 'Session minutes are ready for your approval and signature: ' . ($session['title'] ?? 'Session'), 'index.php?page=session-control');
            }
            $this->flash('success', 'Session minutes sent to the presiding officer for approval and signature.');
            $this->redirect('session-control');
            return;
        } elseif ($operation === 'approve_and_sign') {
            if (($session['minutes_workflow_status'] ?? '') !== 'AWAITING PRESIDING OFFICER' || ($_POST['confirm_minutes_signature'] ?? '') !== '1') {
                $this->flash('error', 'The minutes must be awaiting your approval, and you must confirm your signature.');
                $this->redirect('session-control');
                return;
            }
            if (trim((string)($session['minutes'] ?? '')) === '') {
                $this->flash('error', 'There are no minutes available to approve and sign.');
                $this->redirect('session-control');
                return;
            }
            $councilRecipientIds = [];
            foreach ($this->model->getUsers() as $recipient) {
                if (($recipient['role'] ?? '') === 'council members' && ($recipient['status'] ?? 'active') === 'active') {
                    $councilRecipientIds[] = (string)$recipient['id'];
                }
            }
            if (empty($councilRecipientIds)) {
                $this->flash('error', 'No active council members are available to receive the signed minutes.');
                $this->redirect('session-control');
                return;
            }
            $changes = [
                'minutes_workflow_status' => 'SIGNED AND SENT TO COUNCIL',
                'minutes_sent_at' => $now,
                'minutes_signed_by_id' => (string)$currentUser['id'],
                'minutes_signed_by_name' => (string)$currentUser['name'],
                'minutes_signed_at' => $now,
                'minutes_signature_hash' => hash('sha256', (string)$session['minutes']),
            ];
            $this->model->updateSession($sessionId, $changes);
            $this->logDeliveryHistory('session_minutes', $sessionId, $session['title'] ?? 'Session Minutes', 'approved_and_signed_by_presiding_officer', $councilRecipientIds, $sessionId, 'Signed by ' . (string)$currentUser['name']);
            foreach ($councilRecipientIds as $recipientId) {
                $this->model->addUserNotification($recipientId, 'Approved and signed session minutes are available: ' . ($session['title'] ?? 'Session'), 'index.php?page=minutes-history');
            }
            $this->flash('success', 'Minutes approved and signed. They have been sent to Council.');
            $this->redirect('session-control');
            return;
        } elseif ($operation === 'archive') {
            if (($session['minutes_workflow_status'] ?? '') !== 'SIGNED AND SENT TO COUNCIL') {
                $this->flash('error', 'Minutes can only be archived after the presiding officer signs and sends them to Council.');
                $this->redirect('session-control');
                return;
            }
            $changes = ['minutes_workflow_status' => 'FILE', 'minutes_archived_at' => $now, 'minutes_archive_status' => 'ARCHIVED LOCAL COPY'];
        } else {
            $this->flash('error', 'Invalid minutes workflow operation.');
            $this->redirect('session-control');
        }

        $this->model->updateSession($sessionId, $changes);
        $this->flash('success', 'Session minutes updated to ' . ($changes['minutes_workflow_status'] ?? 'CORRECT') . '.');
        $this->redirect('session-control');
    }

    private function handleApproveAgenda(): void
    {
        $agendaId = $_POST['agenda_id'] ?? '';
        $signature = $_POST['signature'] ?? '';
        $comments = trim($_POST['comments'] ?? '');
        $status = $_POST['status'] ?? 'approved';
        $pkiSignatureData = null;

        if ($agendaId === '') {
            $this->flash('error', 'Agenda ID is required.');
            $this->redirect('proceeding-officer');
            return;
        }

        $currentUser = $this->getCurrentUser();
        if (!$currentUser || $currentUser['role'] !== 'presiding officer') {
            $this->flash('error', 'Unauthorized.');
            $this->redirect('proceeding-officer');
            return;
        }

        $agenda = $this->model->findAgenda($agendaId);
        if (!$agenda) {
            $this->flash('error', 'Agenda not found.');
            $this->redirect('proceeding-officer');
            return;
        }

        $currentStage = $agenda['approval_stage'] ?? 'ctrfb';
        if ($currentStage !== 'awaiting_proceeding') {
            $this->flash('error', 'This agenda is not awaiting Presiding Officer action.');
            $this->redirect('proceeding-officer');
            return;
        }

        if ($status === 'rejected') {
            $changes = [
                'approval_status' => 'rejected',
                'officer_comments' => $_POST['rejection_reason'] ?? 'No reason provided.',
            ];
        } else {
            $pkiKey = null;
            $pkiPrivateKey = false;
            $pkiPublicKey = false;
            if ($currentUser['role'] === 'presiding officer') {
                if (!function_exists('openssl_pkey_get_private') || !function_exists('openssl_sign') || !function_exists('openssl_verify')) {
                    $this->flash('error', 'OpenSSL is required for the Presiding Officer PKI signature.');
                    $this->redirect('proceeding-officer');
                    return;
                }
                $pkiKey = $this->model->getOfficerAgendaSigningKey((string)$currentUser['id']);
                $uploadedKey = $_FILES['signing_key_file'] ?? null;
                $uploadedKeyText = '';
                $uploadedKeyExtension = strtolower(pathinfo((string)($uploadedKey['name'] ?? ''), PATHINFO_EXTENSION));
                if ($uploadedKey && ($uploadedKey['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
                    && $uploadedKeyExtension === 'txt' && ($uploadedKey['size'] ?? 0) <= 65536
                    && is_uploaded_file($uploadedKey['tmp_name'] ?? '')) {
                    $uploadedKeyText = (string)file_get_contents($uploadedKey['tmp_name']);
                }
                $privateKeyPem = '';
                if (preg_match('/-----BEGIN (?:RSA )?PRIVATE KEY-----[\s\S]+?-----END (?:RSA )?PRIVATE KEY-----/', $uploadedKeyText, $privateKeyMatch)) {
                    $privateKeyPem = $privateKeyMatch[0];
                }
                $uploadedKeyId = '';
                if (preg_match('/^Key ID:\s*([a-f0-9]{1,32})\s*$/mi', $uploadedKeyText, $keyIdMatch)) {
                    $uploadedKeyId = strtolower($keyIdMatch[1]);
                }
                $pkiPrivateKey = $privateKeyPem !== '' ? openssl_pkey_get_private($privateKeyPem) : false;
                $pkiPublicKey = $pkiKey ? openssl_pkey_get_public((string)$pkiKey['public_key_pem']) : false;
                $privateDetails = $pkiPrivateKey ? openssl_pkey_get_details($pkiPrivateKey) : false;
                $publicDetails = $pkiPublicKey ? openssl_pkey_get_details($pkiPublicKey) : false;
                if (!$pkiKey || !$privateDetails || !$publicDetails
                    || $uploadedKeyId !== strtolower((string)$pkiKey['id'])
                    || !hash_equals(hash('sha256', (string)$privateDetails['key']), hash('sha256', (string)$publicDetails['key']))) {
                    $this->flash('error', 'Upload the signing key TXT file issued to your Presiding Officer account.');
                    $this->redirect('proceeding-officer');
                    return;
                }
            }
            if ($currentUser['role'] === 'presiding officer') {
                $agendaFileName = basename((string)($agenda['file_path'] ?? ''));
                $agendaFilePath = __DIR__ . '/../../storage/uploads/' . $agendaFileName;
                $agendaHash = $agendaFileName !== '' && is_file($agendaFilePath) ? hash_file('sha256', $agendaFilePath) : false;
                $rawPkiSignature = '';
                if ($agendaHash === false || !openssl_sign($agendaHash, $rawPkiSignature, $pkiPrivateKey, OPENSSL_ALGO_SHA256)
                    || openssl_verify($agendaHash, $rawPkiSignature, $pkiPublicKey, OPENSSL_ALGO_SHA256) !== 1) {
                    $this->flash('error', 'The agenda SHA-256 signature could not be verified.');
                    $this->redirect('proceeding-officer');
                    return;
                }
                $signature = '';
                $pkiSignatureData = [
                    'agenda_id' => $agendaId, 'signer_id' => $currentUser['id'], 'signer_name' => $currentUser['name'],
                    'signer_role' => $currentUser['role'], 'signature' => base64_encode($rawPkiSignature),
                    'public_key_pem' => $pkiKey['public_key_pem'], 'signing_key_id' => $pkiKey['id'],
                    'key_fingerprint' => $pkiKey['fingerprint'], 'document_hash' => $agendaHash,
                ];
            }

            $changes = [
                'approval_stage' => 'approved_for_council',
                'proceeding_approved_at' => date(DATE_ATOM),
                'proceeding_signature' => $signature,
                'proceeding_comments' => $comments,
                'approval_status' => 'approved',
            ];
            $redirectPage = 'proceeding-officer';
            $message = 'Agenda approved and digitally signed by Presiding Officer.';

        }

        $this->model->updateAgenda($agendaId, $changes);
        if ($pkiSignatureData !== null) {
            $pkiVersion = $this->model->createAgendaPkiSignature($pkiSignatureData);
            $changes['pki_signature_version'] = $pkiVersion;
            $message .= ' PKI signature verified (version ' . $pkiVersion . ').';
            if (!empty($agenda['source_legislative_id'])) {
                $publishedAt = date(DATE_ATOM);
                $this->model->updateAgenda($agendaId, [
                    'approval_stage' => 'council_notified',
                    'published' => 1,
                    'published_at' => $publishedAt,
                    'sent_to_council_at' => $publishedAt,
                    'distribution_status' => 'queued',
                    'distributed_at' => null,
                ]);
                $signedAgenda = $this->model->findAgenda($agendaId);
                $this->notifyCouncilOfAgenda($signedAgenda);
                $this->model->updateAgenda($agendaId, [
                    'distribution_status' => 'sent',
                    'distributed_at' => date(DATE_ATOM),
                ]);

                $legislativeId = (string)$agenda['source_legislative_id'];
                $linkedBill = $this->model->findLegislative($legislativeId);
                if ($linkedBill) {
                    $this->model->updateLegislative($legislativeId, [
                        'status' => 'agenda_created',
                        'workflow_status' => 'SP Session',
                        'routing_status' => 'Signed agenda sent to Council',
                        'updatedAt' => $publishedAt,
                        'lastModifiedById' => $currentUser['id'] ?? null,
                        'lastModifiedByName' => $currentUser['name'] ?? null,
                        'lastModifiedAt' => $publishedAt,
                    ]);
                    $this->model->recordLegislativeAudit($legislativeId, 'agenda_signed_and_sent_to_council', $currentUser, [
                        'agenda_id' => $agendaId, 'agenda_signature_version' => $pkiVersion,
                    ]);
                    $ownerId = (string)($linkedBill['createdById'] ?? '');
                    if ($ownerId !== '') {
                        $this->model->addUserNotification(
                            $ownerId,
                            sprintf('Your bill "%s" was signed by the Presiding Officer and sent to Council. Open the audit trail to verify the signed agenda.', $linkedBill['title'] ?? 'Untitled'),
                            'index.php?page=legislative-audit-trail&ledger_id=' . rawurlencode($legislativeId)
                        );
                    }
                }
                $message .= ' It has been sent to Council Members.';
            }
        }
        $finalMessage = $status === 'rejected' ? 'Agenda rejected successfully.' : $message;
        $this->flash('success', $finalMessage);
        $this->redirect($redirectPage ?? 'proceeding-officer');
    }

    private function stampApprovalSignature(array $agenda, string $signature): ?array
    {
        $sourceName = basename((string)($agenda['file_path'] ?? ''));
        $sourcePath = __DIR__ . '/../../storage/uploads/' . $sourceName;
        $extension = strtolower(pathinfo($sourceName, PATHINFO_EXTENSION));
        if ($sourceName === '' || !is_file($sourcePath) || $extension !== 'pdf') {
            return null;
        }

        $autoload = dirname(__DIR__, 3) . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            $this->flash('error', 'PDF signing is not configured on the server.');
            return null;
        }

        $signaturePath = $this->createSignatureImageFile($signature);
        if ($signaturePath === null) {
            $this->flash('error', 'The digital signature image could not be prepared.');
            return null;
        }

        $outputName = pathinfo($sourceName, PATHINFO_FILENAME) . '_signed_' . date('Ymd_His') . '.pdf';
        $outputPath = __DIR__ . '/../../storage/uploads/' . $outputName;

        try {
            require_once $autoload;
            $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
            $pageCount = $pdf->setSourceFile($sourcePath);
            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                $template = $pdf->importPage($pageNumber);
                $size = $pdf->getTemplateSize($template);
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($template);

                if ($pageNumber === $pageCount) {
                    $signatureWidth = min(48.0, max(30.0, $size['width'] * 0.22));
                    $signatureHeight = $signatureWidth * 0.35;
                    $margin = 14.0;
                    $x = $size['width'] - $signatureWidth - $margin;
                    $y = $size['height'] - $signatureHeight - $margin;
                    $pdf->Image($signaturePath, $x, $y, $signatureWidth, $signatureHeight, '', '', '', false, 300, '', false, false, 0, 'B', false, false);
                    $pdf->SetFont('helvetica', '', 7);
                    $pdf->SetTextColor(80, 80, 80);
                    $pdf->Text($x, $y + $signatureHeight + 2, 'Digitally signed');
                }
            }
            $pdf->Output($outputPath, 'F');
        } catch (\Throwable $exception) {
            $this->flash('error', 'The signature could not be embedded into the PDF.');
            return null;
        } finally {
            @unlink($signaturePath);
        }

        if (!is_file($outputPath)) {
            $this->flash('error', 'The signed PDF could not be saved.');
            return null;
        }

        return [
            'file_path' => $outputName,
            'file_name' => $outputName,
            'file_size' => filesize($outputPath),
            'file_type' => 'application/pdf',
        ];
    }

    private function createSignatureImageFile(string $signature): ?string
    {
        $signaturePath = tempnam(sys_get_temp_dir(), 'approval_signature_');
        if ($signaturePath === false) {
            return null;
        }

        if (str_starts_with($signature, 'data:image/')) {
            $parts = explode(',', $signature, 2);
            $contents = count($parts) === 2 ? base64_decode($parts[1], true) : false;
        } else {
            $query = parse_url($signature, PHP_URL_QUERY) ?: '';
            parse_str($query, $queryParams);
            $signatureName = basename((string)($queryParams['file'] ?? ''));
            $signaturePathSource = __DIR__ . '/../../storage/uploads/' . $signatureName;
            $contents = is_file($signaturePathSource) ? file_get_contents($signaturePathSource) : false;
        }

        if ($contents === false || $contents === '') {
            @unlink($signaturePath);
            return null;
        }

        $imagePath = $signaturePath . '.png';
        if (file_put_contents($imagePath, $contents) === false) {
            @unlink($signaturePath);
            return null;
        }
        @unlink($signaturePath);
        return $imagePath;
    }

    private function handleSendToProceedingOfficer(): void
    {
        $agendaId = $_POST['agenda_id'] ?? '';
        
        if ($agendaId === '') {
            $this->flash('error', 'Agenda ID is required.');
            $this->redirect('agendas');
            return;
        }

        $currentUser = $this->getCurrentUser();
        if (!$currentUser || !in_array($currentUser['role'], ['admin'])) {
            $this->flash('error', 'Unauthorized.');
            $this->redirect('agendas');
            return;
        }

        $agenda = $this->model->findAgenda($agendaId);
        $approvalStage = $agenda['approval_stage'] ?? 'ctrfb';
        if (!$agenda || !in_array($approvalStage, ['ctrfb', 'awaiting_proceeding'], true) || !empty($agenda['sent_to_proceeding_id'])) {
            $this->flash('error', 'Agenda is not ready for Presiding Officer review.');
            $this->redirect('agendas');
            return;
        }
        if (empty($agenda['session_id'])) {
            $this->flash('error', 'Set a session schedule before sending this agenda for signature.');
            $this->redirect('agendas');
            return;
        }

        $officerIds = $_POST['officer_ids'] ?? [];
        $officerIds = is_array($officerIds) ? $officerIds : [$officerIds];

        if (count($officerIds) === 0) {
            $this->flash('error', 'Please select at least one Presiding Officer.');
            $this->redirect('agendas');
            return;
        }

        // Validate all officers
        $invalidOfficers = [];
        foreach ($officerIds as $officerId) {
            $officer = $this->model->getUserById($officerId);
            if (!$officer || ($officer['status'] ?? 'active') !== 'active' || $officer['role'] !== 'presiding officer') {
                $invalidOfficers[] = $officerId;
            }
        }

        if (count($invalidOfficers) > 0) {
            $this->flash('error', 'One or more selected officers are invalid.');
            $this->redirect('agendas');
            return;
        }

        $changes = [
            'approval_stage' => 'awaiting_proceeding',
            'approval_status' => 'awaiting_proceeding_review',
            'sent_to_proceeding_id' => json_encode($officerIds),
            'sent_to_proceeding_date' => date(DATE_ATOM),
        ];

        $this->model->updateAgenda($agendaId, $changes);
        
        $officerNames = array_map(fn($id) => $this->model->getUserById($id)['name'] ?? 'Officer', $officerIds);
        $message = 'Agenda sent to ' . implode(', ', $officerNames) . ' for signature by the Presiding Officer.';
        $template = null;
        if (!empty($_POST['template_id'])) {
            $template = $this->findDeliveryTemplate(trim($_POST['template_id']));
        }

        $this->logDeliveryHistory('agenda', $agendaId, $agenda['title'] ?? 'Agenda', 'sent_to_proceeding_officer', $officerIds, $agenda['session_id'] ?? null, $message, $template);
        
        $this->flash('success', $message);
        $this->redirect('agendas');
    }

    private function notifyCouncilOfAgenda(?array $agenda): void
    {
        if (!$agenda || empty($agenda['session_id'])) {
            return;
        }

        $allSessions = $this->model->getData()['sessions'] ?? [];
        $session = null;
        foreach ($allSessions as $sess) {
            if ($sess['id'] === $agenda['session_id']) {
                $session = $sess;
                break;
            }
        }

        $councilMembers = $this->getCouncilRecipients();
        
        if (count($councilMembers) === 0) {
            return;
        }

        $notificationText = sprintf(
            'Agenda "%s" has been sent for session "%s" on %s at %s.',
            $agenda['title'],
            $session['title'] ?? 'Unknown session',
            $session['date'] ?? 'Unknown date',
            $session['start'] ?? 'Unknown time'
        );
        
        foreach ($councilMembers as $member) {
            $this->model->addUserNotification($member['id'], $notificationText, 'index.php?page=tablet');
            $deliveryStatus = 'failed';
            if (!empty($member['email']) && filter_var($member['email'], FILTER_VALIDATE_EMAIL)) {
                $deliveryStatus = $this->sendNotificationEmail($member['email'], $agenda['title'], $session, $agenda['file_path'] ?? null) ? 'sent' : 'failed';
            }
            $this->model->logAgendaDistribution([
                'agenda_id' => $agenda['id'],
                'target_user_id' => $member['id'],
                'delivery_status' => $deliveryStatus,
                'file_path' => $agenda['file_path'] ?? null,
            ]);
        }
    }
    
    private function handleSendToCouncil(): void
    {
        $agendaId = $_POST['agenda_id'] ?? '';
        
        if ($agendaId === '') {
            $this->flash('error', 'Agenda ID is required.');
            $this->redirect('agendas');
            return;
        }

        $currentUser = $this->getCurrentUser();
        if (!$currentUser || !in_array($currentUser['role'], ['admin'])) {
            $this->flash('error', 'Unauthorized.');
            $this->redirect('agendas');
            return;
        }

        $agenda = $this->model->findAgenda($agendaId);
        if (!$agenda) {
            $this->flash('error', 'Agenda not found.');
            $this->redirect('agendas');
            return;
        }

        if (empty($agenda['session_id'])) {
            $this->flash('error', 'Agenda must have a schedule assigned before sending to Council Members.');
            $this->redirect('agendas');
            return;
        }

        // Check that agenda is approved by reviewer flow
        $approvalStatus = $agenda['approval_status'] ?? 'pending';
        $approvalStage = $agenda['approval_stage'] ?? '';
        if ($approvalStatus !== 'approved' && $approvalStage !== 'approved_for_council') {
            $this->flash('error', 'Agenda must be approved by CTRFB/Presiding Officer before sending to Council Members.');
            $this->redirect('agendas');
            return;
        }

        $changes = [
            'approval_stage' => 'council_notified',
            'sent_to_council_at' => date(DATE_ATOM),
            'approval_status' => 'published_to_council',
            'distribution_status' => 'queued',
            'distributed_at' => null,
        ];

        $this->model->updateAgenda($agendaId, $changes);
        $this->notifyCouncilOfAgenda($agenda);
        $this->model->updateAgenda($agendaId, [
            'distribution_status' => 'sent',
            'distributed_at' => date(DATE_ATOM),
        ]);

        $councilMembers = array_values(array_filter($this->model->getData()['users'], fn($u) => $u['role'] === 'council members'));
        $councilRecipientIds = array_map(fn($user) => $user['id'], $councilMembers);
        $template = null;
        if (!empty($_POST['template_id'])) {
            $template = $this->findDeliveryTemplate(trim($_POST['template_id']));
        }
        $this->logDeliveryHistory('agenda', $agendaId, $agenda['title'] ?? 'Agenda', 'sent_to_council', $councilRecipientIds, $agenda['session_id'] ?? null, 'Agenda sent to council members', $template);
        
        // Get session for message
        $session = null;
        $allSessions = $this->model->getData()['sessions'] ?? [];
        foreach ($allSessions as $sess) {
            if ($sess['id'] === $agenda['session_id']) {
                $session = $sess;
                break;
            }
        }

        $councilMembers = array_filter($this->model->getData()['users'], fn($u) => $u['role'] === 'council members');
        $councilCount = count($councilMembers);
        $message = "Agenda sent to $councilCount Council Members";
        
        if ($session) {
            $message .= ".\n🗓️ Session: " . $session['title'] . "\n📅 Date: " . $session['date'] . " at " . $session['start'];
        }
        
        $this->flash('success', $message);
        $this->redirect('agendas');
    }
    
    private function findDeliveryTemplate(string $templateId): ?array
    {
        $templates = $this->model->getDeliveryTemplates();
        foreach ($templates as $template) {
            if (($template['id'] ?? '') === $templateId) {
                return $template;
            }
        }
        return null;
    }

    private function logDeliveryHistory(string $itemType, string $itemId, string $itemTitle, string $action, array $recipientIds, ?string $sessionId = null, ?string $notes = null, ?array $template = null): void
    {
        $currentUser = $this->getCurrentUser();
        $recipientNames = [];
        foreach ($recipientIds as $recipientId) {
            $user = $this->model->getUserById($recipientId);
            if ($user) {
                $recipientNames[] = $user['name'] ?? $recipientId;
            }
        }

        $this->model->logDeliveryHistory([
            'item_type' => $itemType,
            'item_id' => $itemId,
            'item_title' => $itemTitle,
            'session_id' => $sessionId,
            'action' => $action,
            'recipient_ids' => $recipientIds,
            'recipient_names' => $recipientNames,
            'sent_by_id' => $currentUser['id'] ?? null,
            'sent_by_name' => $currentUser['name'] ?? null,
            'notes' => $notes,
            'template_id' => $template['id'] ?? null,
            'template_name' => $template['name'] ?? null,
            'template_type' => $template['template_type'] ?? null,
        ]);
    }

    private function sendNotificationEmail(string $email, string $agendaTitle, array $session, ?string $filePath = null): bool
    {
        $subject = "Council Meeting Agenda: " . $agendaTitle;
        $body = "You have been invited to review the agenda: " . $agendaTitle . "\n\n";
        if (!empty($session)) {
            $body .= "Session: " . ($session['title'] ?? 'N/A') . "\n";
            $body .= "Date: " . ($session['date'] ?? 'N/A') . "\n";
            $body .= "Time: " . ($session['start'] ?? 'N/A') . " - " . ($session['end'] ?? 'N/A') . "\n\n";
        }
        // Provide a link to download the agenda if available
        if (!empty($filePath)) {
            $downloadUrl = sprintf('%s/download.php?file=%s', $this->getBaseUrl(), rawurlencode($filePath));
            $body .= "Download: " . $downloadUrl . "\n\n";
        }

        $headers = "From: no-reply@localhost\r\n";
        $attachmentPath = $filePath ? __DIR__ . '/../../storage/uploads/' . basename($filePath) : '';
        if ($attachmentPath !== '' && is_readable($attachmentPath)) {
            $boundary = 'agenda_' . bin2hex(random_bytes(12));
            $attachmentName = basename($attachmentPath);
            $attachment = chunk_split(base64_encode((string)file_get_contents($attachmentPath)));
            $textBody = $body;
            $body = "--{$boundary}\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n" . $textBody . "\r\n";
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: application/pdf; name=\"{$attachmentName}\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n";
            $body .= "Content-Disposition: attachment; filename=\"{$attachmentName}\"\r\n\r\n";
            $body .= $attachment . "\r\n--{$boundary}--\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        }

        return (bool)@mail($email, $subject, $body, $headers);
    }

    private function getBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        return rtrim($scheme . '://' . $host . $script, '/');
    }

    private function notifyAdminsOfCouncilPrint(array $session, int $agendaCount): void
    {
        $admins = array_filter($this->model->getUsers(), fn($user) => ($user['role'] ?? '') === 'admin');
        if (count($admins) === 0) {
            return;
        }

        $message = sprintf(
            'Council printed %d agenda item(s) for session "%s" on %s at %s.',
            $agendaCount,
            $session['title'] ?? 'Unknown session',
            $session['date'] ?? 'Unknown date',
            $session['start'] ?? 'Unknown time'
        );

        foreach ($admins as $admin) {
            $this->model->addUserNotification($admin['id'], $message, 'index.php?page=agendas');
        }
    }

    private function handleCouncilPrintedAgenda(): void
    {
        header('Content-Type: application/json');
        $currentUser = $this->getCurrentUser();
        if (!$currentUser || ($currentUser['role'] ?? '') !== 'council members') {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $sessionId = $_POST['session_id'] ?? '';
        $agendaIds = $_POST['agenda_ids'] ?? [];
        if ($sessionId === '' || !is_array($agendaIds)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing session or agenda IDs']);
            return;
        }

        $session = null;
        foreach ($this->model->getSessions() as $sess) {
            if ($sess['id'] === $sessionId) {
                $session = $sess;
                break;
            }
        }

        $agendaCount = count($agendaIds);
        if ($session) {
            $this->notifyAdminsOfCouncilPrint($session, $agendaCount);
        }

        echo json_encode(['success' => true, 'printed' => $agendaCount]);
    }

    private function handlePublishOrdinance(): void
    {
        $id = $_POST['id'] ?? '';
        $publish = isset($_POST['publish']) && ($_POST['publish'] === '1' || $_POST['publish'] === 'true');
        
            if ($id === '') {
                $this->flash('error', 'Ordinance ID is required.');
                $this->redirect('legislative-admin');
                return;
            }
        
            $currentUser = $this->getCurrentUser();
            if (!$currentUser || !in_array($currentUser['role'], ['admin'])) {
                $this->flash('error', 'Unauthorized. Only admins can publish ordinances.');
                $this->redirect('legislative-admin');
                return;
            }
        
            $item = $this->model->findLegislative($id);
            if (!$item) {
                $this->flash('error', 'Ordinance not found.');
                $this->redirect('legislative-admin');
                return;
            }
        
            if ($item['status'] !== 'approved') {
                $this->flash('error', 'Only approved ordinances can be published to the public.');
                $this->redirect('legislative-admin');
                return;
            }
        
            $changes = [];
            if ($publish) {
                $changes['published_to_public'] = 1;
                $changes['published_to_public_at'] = date(DATE_ATOM);
            } else {
                $changes['published_to_public'] = 0;
                $changes['published_to_public_at'] = null;
            }
        
            $this->model->updateLegislative($id, $changes);
        
            $this->flash('success', $publish ? 'Ordinance published to public view.' : 'Ordinance unpublished from public view.');
            $this->redirect('legislative-admin');
        }

        // --- Real-time voting API ---
        private function handleCastVote(): void
        {
            header('Content-Type: application/json');
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                return;
            }

            $agendaId = $_POST['agenda_id'] ?? '';
            $sessionId = $_POST['session_id'] ?? null;
            $vote = strtoupper(trim($_POST['vote'] ?? ''));

            if ($agendaId === '' || !in_array($vote, ['FAVOR', 'AGAINST', 'ABSTAIN'], true)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
                return;
            }

            $this->model->recordVote($agendaId, $sessionId, $currentUser['id'], $vote);
            $tally = $this->model->tallyVotesByAgenda($agendaId);
            echo json_encode(['success' => true, 'tally' => $tally]);
        }

        private function handlePollVotes(): void
        {
            header('Content-Type: application/json');
            $agendaId = $_POST['agenda_id'] ?? $_GET['agenda_id'] ?? '';
            if ($agendaId === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing agenda_id']);
                return;
            }
            $tally = $this->model->tallyVotesByAgenda($agendaId);
            echo json_encode(['success' => true, 'tally' => $tally]);
        }

        // --- Presence APIs ---
        private function handleSetPresence(): void
        {
            header('Content-Type: application/json');
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                return;
            }
            $sessionId = $_POST['session_id'] ?? '';
            $deviceId = $_POST['device_id'] ?? null;
            if ($sessionId === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing session_id']);
                return;
            }
            if (($currentUser['role'] ?? '') !== 'council members') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Only council members can request attendance.']);
                return;
            }
            $request = $this->model->requestAttendance($sessionId, $currentUser['id'], $deviceId);
            if (($request['status'] ?? '') === 'pending') {
                $admins = array_filter($this->model->getData()['users'], fn($user) => ($user['role'] ?? '') === 'admin');
                foreach ($admins as $admin) {
                    $this->model->addUserNotification($admin['id'], $currentUser['name'] . ' requested attendance for session ' . ($this->model->findSession($sessionId)['title'] ?? 'session') . '.', 'index.php?page=sessions');
                }
            }
            $presence = $this->model->getPresenceForSession($sessionId);
            echo json_encode(['success' => true, 'request' => $request, 'presence' => $presence]);
        }

        private function handlePollPresence(): void
        {
            header('Content-Type: application/json');
            $sessionId = $_POST['session_id'] ?? $_GET['session_id'] ?? '';
            if ($sessionId === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing session_id']);
                return;
            }
            $presence = $this->model->getPresenceForSession($sessionId);
            $currentUser = $this->getCurrentUser();
            $request = $currentUser ? $this->model->getAttendanceRequest($sessionId, $currentUser['id']) : null;
            echo json_encode(['success' => true, 'presence' => $presence, 'request' => $request]);
        }

        private function handleDecideAttendance(): void
        {
            header('Content-Type: application/json');
            $admin = $this->getCurrentUser();
            if (!$admin || !$this->isAdminUser()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Only administrators can decide attendance requests.']);
                return;
            }

            $requestId = trim((string)($_POST['request_id'] ?? ''));
            $status = trim((string)($_POST['status'] ?? ''));
            $request = $this->model->decideAttendanceRequest($requestId, $status, $admin['id']);
            if (!$request) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'This attendance request is no longer pending.']);
                return;
            }

            $message = $status === 'approved'
                ? 'Your attendance request was approved.'
                : 'Your attendance request was rejected.';
            $this->model->addUserNotification($request['user_id'], $message, 'index.php?page=session-control');
            echo json_encode(['success' => true, 'status' => $status]);
        }

        // --- Annotations ---
        private function handleSaveAnnotation(): void
        {
            header('Content-Type: application/json');
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                return;
            }
            $resourceType = $_POST['resource_type'] ?? 'agenda';
            $resourceId = $_POST['resource_id'] ?? '';
            $type = $_POST['type'] ?? 'note';
            $data = $_POST['data'] ?? '';
            $page = isset($_POST['page']) ? (int)$_POST['page'] : null;

            if ($resourceId === '' || $data === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing resource_id or data']);
                return;
            }

            $id = $this->model->createAnnotation([
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'user_id' => $currentUser['id'],
                'type' => $type,
                'data' => $data,
                'page' => $page,
            ]);

            $annotations = $this->model->getAnnotationsForResource($resourceType, $resourceId);
            echo json_encode(['success' => true, 'id' => $id, 'annotations' => $annotations]);
        }

        private function handleGetAnnotations(): void
        {
            header('Content-Type: application/json');
            $resourceType = $_POST['resource_type'] ?? 'agenda_reading';
            $resourceId = $_POST['resource_id'] ?? '';
            if ($resourceId === '') {
                echo json_encode(['success' => false, 'error' => 'Missing resource_id']);
                return;
            }
            $annotations = $this->model->getAnnotationsForResource($resourceType, $resourceId);
            echo json_encode(['success' => true, 'annotations' => $annotations]);
        }

        private function handleSetReadingStage(): void
        {
            header('Content-Type: application/json');
            $currentUser = $this->getCurrentUser();
            if (!$currentUser || !$this->isAdminUser()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                return;
            }
            $agendaId = $_POST['agenda_id'] ?? $_GET['agenda_id'] ?? '';
            $reading = $_POST['reading'] ?? $_GET['reading'] ?? '';
            if ($agendaId === '' || $reading === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing parameters']);
                return;
            }
            $map = ['first' => 'first_reading', 'second' => 'second_reading', 'third' => 'third_reading'];
            $stage = $map[$reading] ?? ($reading);
            $ok = $this->model->updateAgenda($agendaId, ['reading_stage' => $stage]);
            echo json_encode(['success' => (bool)$ok, 'reading_stage' => $stage]);
        }

        private function handleExportData(): void
        {
            header('Content-Type: application/json');
            $data = $this->model->getData();
            echo json_encode(['success' => true, 'data' => $data]);
        }

        private function handlePollAnalytics(): void
        {
            header('Content-Type: application/json');
            $currentUser = $this->getCurrentUser();
            if (!$currentUser || !$this->isAdminUser()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                return;
            }

            $analytics = $this->model->getLegislativeStats();
            $attendance = $this->model->getAttendanceStats();

            // add a notification for the admin but avoid duplicates within configured window
            $msg = 'Analytics refreshed at ' . date('H:i');
            $cfg = require __DIR__ . '/../config.php';
            $window = $cfg['notifications']['dedupe_window_seconds'] ?? 60;
            $latest = $this->model->getLatestNotification($currentUser['id']);
            $shouldAdd = true;
            if ($latest && isset($latest['message']) && $latest['message'] === $msg) {
                $created = isset($latest['created_at']) ? strtotime($latest['created_at']) : null;
                if ($created !== null && $created >= (time() - (int)$window)) {
                    $shouldAdd = false;
                }
            }
            if ($shouldAdd) {
                $this->model->addUserNotification($currentUser['id'], $msg, 'index.php?page=legislative-admin');
            }

            // return updated notifications (most recent first)
            $updatedUser = $this->model->getUserById($currentUser['id']);
            $notes = [];
            if (!empty($updatedUser['notifications'])) {
                $decoded = json_decode($updatedUser['notifications'], true);
                if (is_array($decoded)) {
                    $notes = array_reverse($decoded);
                }
            }
            $unread = count(array_filter($notes, fn($n) => empty($n['read'])));

            echo json_encode(['success' => true, 'analytics' => $analytics, 'attendance' => $attendance, 'notifications' => $notes, 'unread' => $unread]);
        }

        private function handleMarkNotificationsRead(): void
        {
            header('Content-Type: application/json');
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                return;
            }
            try {
                $ok = $this->model->markNotificationsRead($currentUser['id']);
            } catch (\Throwable $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
                return;
            }
            $updatedUser = $this->model->getUserById($currentUser['id']);
            $notes = [];
            if (!empty($updatedUser['notifications'])) {
                $decoded = json_decode($updatedUser['notifications'], true);
                if (is_array($decoded)) {
                    $notes = array_reverse($decoded);
                }
            }
            $unread = count(array_filter($notes, fn($n) => empty($n['read'])));
            if (!$ok) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Unable to update notifications', 'notifications' => $notes, 'unread' => $unread]);
                return;
            }
            echo json_encode(['success' => true, 'notifications' => $notes, 'unread' => $unread]);
        }

        private function handleRemoveNotification(): void
        {
            header('Content-Type: application/json');
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                return;
            }
            $message = $_POST['message'] ?? '';
            if (empty($message)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Message is required']);
                return;
            }
            try {
                $ok = $this->model->removeNotification($currentUser['id'], $message);
            } catch (\Throwable $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
                return;
            }
            if (!$ok) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Unable to remove notification']);
                return;
            }
            $updatedUser = $this->model->getUserById($currentUser['id']);
            $notes = [];
            if (!empty($updatedUser['notifications'])) {
                $decoded = json_decode($updatedUser['notifications'], true);
                if (is_array($decoded)) {
                    $notes = array_reverse($decoded);
                }
            }
            $unread = count(array_filter($notes, fn($n) => empty($n['read'])));
            echo json_encode(['success' => true, 'notifications' => $notes, 'unread' => $unread]);
        }

    private function renderAgendaVerification(string $agendaId): void
    {
        $agenda = $this->model->findAgenda($agendaId);
        $linkedBill = $agenda && !empty($agenda['source_legislative_id'])
            ? $this->model->findLegislative((string)$agenda['source_legislative_id'])
            : null;
        $signatures = $agenda ? $this->model->getAgendaPkiSignatures($agendaId) : [];
        $signature = $signatures[0] ?? null;
        $filePath = $agenda && !empty($agenda['file_path'])
            ? __DIR__ . '/../../storage/uploads/' . basename((string)$agenda['file_path'])
            : '';
        $currentHash = $filePath !== '' && is_file($filePath) ? hash_file('sha256', $filePath) : false;
        $verified = false;
        if ($agenda && $signature && $currentHash !== false && function_exists('openssl_pkey_get_public') && function_exists('openssl_verify')) {
            $publicKeyPem = (string)$signature['public_key_pem'];
            $publicKey = openssl_pkey_get_public($publicKeyPem);
            $rawSignature = base64_decode((string)$signature['signature'], true);
            $verified = hash_equals((string)$signature['document_hash'], (string)$currentHash)
                && hash_equals((string)$signature['key_fingerprint'], hash('sha256', $publicKeyPem))
                && $publicKey && $rawSignature !== false
                && openssl_verify((string)$signature['document_hash'], $rawSignature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
        }
        if (!$agenda || !$signature) {
            http_response_code(404);
        } elseif (!$verified) {
            http_response_code(409);
        }
        $safe = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Agenda Signature Verification</title>';
        echo '<body style="font:16px system-ui,sans-serif;max-width:680px;margin:48px auto;padding:0 20px;color:#17212b"><h1>Agenda Signature Verification</h1>';
        if (!$agenda || !$signature) {
            echo '<p>No PKI-signed agenda version was found.</p>';
        } else {
            echo '<p><strong>' . ($verified ? 'Signature verified and agenda integrity confirmed.' : 'Verification failed or the signed agenda has changed.') . '</strong></p>';
            echo '<dl><dt>Agenda</dt><dd>' . $safe($agenda['title']) . '</dd>'
                . '<dt>Signed file</dt><dd>' . $safe($agenda['file_name']) . '</dd>'
                . '<dt>Version</dt><dd>' . (int)$signature['version_number'] . '</dd>'
                . '<dt>Signer</dt><dd>' . $safe($signature['signer_name']) . ' · ' . $safe($signature['signer_role']) . '</dd>'
                . '<dt>Signed at</dt><dd>' . $safe($signature['signed_at']) . '</dd>'
                . '<dt>Signature algorithm</dt><dd>' . $safe($signature['algorithm']) . '</dd>'
                . '<dt>Digital signature</dt><dd>' . ($verified ? 'VALID' : 'INVALID') . '</dd>'
                . '<dt>SHA-256</dt><dd style="overflow-wrap:anywhere">' . $safe($signature['document_hash']) . '</dd>'
                . '<dt>Key fingerprint</dt><dd style="overflow-wrap:anywhere">' . $safe($signature['key_fingerprint']) . '</dd>';
            if ($linkedBill) {
                echo '<dt>Linked bill control number</dt><dd>' . $safe($linkedBill['control_number'] ?? '') . '</dd>'
                    . '<dt>Bill title</dt><dd>' . $safe($linkedBill['title'] ?? '') . '</dd>'
                    . '<dt>Bill type</dt><dd>' . $safe($linkedBill['type'] ?? '') . '</dd>'
                    . '<dt>Bill status</dt><dd>' . $safe($linkedBill['status'] ?? '') . '</dd>'
                    . '<dt>Bill description</dt><dd>' . $safe(LegislativeContentFormatter::toPlainText((string)($linkedBill['desc'] ?? ''))) . '</dd>'
                    . '<dt>Bill content</dt><dd style="overflow-wrap:anywhere">' . $safe(LegislativeContentFormatter::toPlainText((string)($linkedBill['content'] ?? ''))) . '</dd>';
            }
            echo '</dl>';
        }
        echo '</body></html>';
    }

    private function renderLegislativeVerification(string $controlNumber, ?int $versionNumber = null): void
    {
        $document = $this->model->findLegislativeByControlNumber($controlNumber);
        if (!$document) {
            http_response_code(404);
        }
        $signature = $document
            ? ($versionNumber !== null
                ? $this->model->getLegislativeSignatureForVersion((string)$document['id'], $versionNumber)
                : $this->model->getLatestLegislativeSignature((string)$document['id']))
            : null;
        $verificationDocument = $document;
        if ($document && $versionNumber !== null) {
            $version = null;
            foreach ($this->model->getLegislativeVersions((string)$document['id']) as $candidateVersion) {
                if ((int)$candidateVersion['version_number'] === $versionNumber) {
                    $version = $candidateVersion;
                    break;
                }
            }
            if ($version) {
                $verificationDocument = array_merge($document, [
                    'title' => $version['title'], 'content' => $version['content'], 'file_path' => $version['file_path'],
                ]);
            } else {
                $signature = null;
            }
        }
        $versionHash = $verificationDocument ? $this->legislativeContentHash($verificationDocument) : '';
        $hashMatches = $signature && hash_equals((string)$signature['document_hash'], $versionHash)
            && ($versionNumber !== null || (!empty($document['signed_hash']) && hash_equals((string)$document['signed_hash'], $versionHash)));
        $verified = false;
        if ($document && $signature && function_exists('openssl_pkey_get_public') && function_exists('openssl_verify') && $hashMatches) {
            $certificatePem = (string)($signature['certificate_pem'] ?? '');
            $certificate = $certificatePem !== '' ? openssl_x509_read($certificatePem) : false;
            $publicKeyPem = (string)($signature['public_key_pem'] ?? '');
            $publicKey = $publicKeyPem !== ''
                ? openssl_pkey_get_public($publicKeyPem)
                : ($certificate ? openssl_x509_get_pubkey($certificate) : false);
            $rawSignature = base64_decode((string)$signature['signature'], true);
            $certificateInfo = $certificate ? openssl_x509_parse($certificate) : false;
            $signedAt = strtotime((string)$signature['created_at']);
            $fingerprintValid = $publicKeyPem !== ''
                ? hash_equals((string)$signature['certificate_fingerprint'], hash('sha256', $publicKeyPem))
                : ($certificate && hash_equals((string)$signature['certificate_fingerprint'], hash('sha256', $certificatePem)));
            $certificateValid = $publicKeyPem !== '' || ($certificateInfo
                && $signedAt >= (int)$certificateInfo['validFrom_time_t'] && $signedAt <= (int)$certificateInfo['validTo_time_t']);
            $verified = $publicKey && $rawSignature !== false && $fingerprintValid && $certificateValid
                && openssl_verify((string)$signature['document_hash'], $rawSignature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
        }
        if ($document && !$verified) {
            http_response_code(409);
        }
        $isPublishedSignedRecord = $document && $signature && ($versionNumber !== null
            || (!empty($document['signed_hash']) && in_array($document['workflow_status'] ?? '', ['LCE Approval', 'Filing/Scan', 'Archive', 'Signed', 'Archived'], true)));
        if ($document && !$isPublishedSignedRecord) {
            http_response_code(404);
            $document = null;
            $signature = null;
            $verificationDocument = null;
        }
        $safe = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Document Verification</title>';
        echo '<body style="font:16px system-ui,sans-serif;max-width:680px;margin:48px auto;padding:0 20px;color:#17212b"><h1>Legislative Document Verification</h1>';
        if (!$document) {
            echo '<p>Document not found.</p>';
        } else {
            echo '<p><strong>' . ($verified ? 'Signature verified and document integrity confirmed.' : 'Verification failed or signed content has changed.') . '</strong></p>';
            echo '<dl><dt>Control number</dt><dd>' . $safe($document['control_number']) . '</dd>'
                . '<dt>Title</dt><dd>' . $safe($verificationDocument['title']) . '</dd>'
                . '<dt>Type</dt><dd>' . $safe($document['type']) . '</dd>'
                . '<dt>Version</dt><dd>' . $safe($signature['version_number'] ?? $versionNumber ?? '') . '</dd>'
                . '<dt>Digitally signed</dt><dd>' . $safe($signature['created_at'] ?? '') . '</dd>'
                . '<dt>Signature algorithm</dt><dd>' . $safe($signature['algorithm'] ?? '') . '</dd>'
                . '<dt>Digital signature</dt><dd>' . ($verified ? 'VALID' : 'INVALID') . '</dd>'
                . '<dt>SHA-256</dt><dd style="overflow-wrap:anywhere">' . $safe($signature['document_hash'] ?? 'Not signed') . '</dd>'
                . '<dt>Signer</dt><dd>' . $safe($signature['signer_name'] ?? 'None') . ' · ' . $safe($signature['signer_role'] ?? '') . '</dd>'
                . '<dt>Verification key</dt><dd style="overflow-wrap:anywhere">' . $safe($signature['certificate_fingerprint'] ?? '') . '</dd></dl>';
        }
        echo '</body></html>';
    }

    public function render(string $page): void
    {
        $this->synchronizeEndedSessionLceReviews();
        if (isset($_GET['download_signing_key']) || isset($_GET['download_officer_signing_key'])) {
            $this->handleDownloadSigningKey();
            return;
        }
        $verificationAgendaId = trim((string)($_GET['verify_agenda'] ?? ''));
        if ($verificationAgendaId !== '') {
            $this->renderAgendaVerification($verificationAgendaId);
            return;
        }
        if ($page === 'legislative-ledger') {
            $page = 'legislative-audit-trail';
        }
        $allowedPages = ['dashboard', 'users', 'sessions', 'minutes-history', 'agendas', 'create-document', 'proceeding-officer', 'ctrfb', 'ctfrb-report', 'lce', 'login', 'legislative-admin', 'legislative-user', 'legislative-bills', 'legislative-audit-trail', 'ordinances', 'session-control', 'tablet', 'analytics', 'notifications'];
        $page = in_array($page, $allowedPages, true) ? $page : 'dashboard';

        $verificationNumber = trim((string)($_GET['verify'] ?? ''));
        if ($verificationNumber !== '') {
            $versionNumber = isset($_GET['version']) ? max(1, (int)$_GET['version']) : null;
            $this->renderLegislativeVerification($verificationNumber, $versionNumber);
            return;
        }

        $currentUser = $this->getCurrentUser();
        $shouldDownloadSigningKey = $currentUser && (
            (($currentUser['role'] ?? '') === 'presiding officer' && !empty($_SESSION['officer_key_download_id']))
            || (in_array(($currentUser['role'] ?? ''), ['lce', 'mayor'], true) && !empty($_SESSION['lce_key_download_id']))
        );
        $needsSetup = !$this->model->hasUsers();

        if (!$currentUser && $page !== 'login') {
            $page = 'login';
        }

        if ($currentUser && $page === 'login') {
            $page = 'dashboard';
        }

        if ($page === 'ctfrb-report' && ($currentUser['role'] ?? '') !== 'ctrfb') {
            $this->flash('error', 'Only CTRFB staff can access CTFRB reports.');
            $this->redirect($currentUser ? 'dashboard' : 'login');
        }

        if ($currentUser && $page === 'notifications') {
            $this->model->markNotificationsRead((string)$currentUser['id']);
            $currentUser = $this->model->getUserById((string)$currentUser['id']) ?? $currentUser;
        }

        $data = $this->model->getData();
        if ($currentUser && time() - (int)($_SESSION['last_legislative_integrity_scan_at'] ?? 0) >= 60) {
            $uploadDirectory = __DIR__ . '/../../storage/uploads';
            foreach ($data['legislatives'] ?? [] as $record) {
                if (empty($record['file_path'])) {
                    continue;
                }
                if (!$this->isAdminUser() && (string)($record['createdById'] ?? '') !== (string)($currentUser['id'] ?? '')) {
                    continue;
                }
                $attachmentPath = $uploadDirectory . DIRECTORY_SEPARATOR . basename((string)$record['file_path']);
                $this->model->verifyLegislativeFileIntegrity($record, $attachmentPath);
            }
            $_SESSION['last_legislative_integrity_scan_at'] = time();
            $currentUser = $this->model->getUserById((string)$currentUser['id']) ?? $currentUser;
        }
        $search = trim($_GET['search'] ?? '');
        $hashSearch = trim($_GET['hash_search'] ?? '');
        $statusFilter = $_GET['status'] ?? 'all';
        $openPanel = $_GET['open'] ?? null;
        $billOnly = ($page === 'legislative-bills' && ($_GET['bill_status'] ?? '') !== 'pending') || filter_var($_GET['bill_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $editId = $_GET['edit'] ?? null;
        $editType = $_GET['type'] ?? null;
        $confirm = $_GET['confirm'] ?? null;
        $confirmId = $_GET['id'] ?? null;

        $users = $data['users'];
        $sessions = $data['sessions'];
        $sendMinutesSessions = array_values(array_filter($sessions, fn (array $session): bool => $this->isSessionMinutesDeliveryAvailable($session, $data['delivery_history'] ?? [])));
        $sendMinutesSessionIds = array_fill_keys(array_map('strval', array_column($sendMinutesSessions, 'id')), true);
        $agendas = $data['agendas'];
        if ($page === 'proceeding-officer') {
            foreach ($agendas as &$agenda) {
                $agenda['pki_signatures'] = $this->model->getAgendaPkiSignatures((string)$agenda['id']);
            }
            unset($agenda);
        }
        $currentSession = null;
        $currentSessionAgendas = [];
        $notifications = [];
        $legislativeVersions = [];
        $legislativeAudit = [];
        $legislativeSignatures = [];
        $ledgerAgendaSignatures = [];
        $attendanceRequests = [];
        $ctfrbReports = $page === 'ctfrb-report' ? $this->model->getCtfrbReports() : [];
        $authors = $page === 'create-document' ? $this->model->getAuthors() : [];
        $sponsors = $page === 'create-document' ? $this->model->getSponsors() : [];
        $document = ($page === 'create-document' && $editId) ? $this->model->getDocument((string)$editId) : null;
        $sessionIndex = [];
        foreach ($sessions as $sess) {
            $sessionIndex[$sess['id']] = $sess;
        }

        if ($currentUser) {
            $role = $currentUser['role'] ?? '';

            if ($this->isAdminUser() && $page === 'sessions') {
                $attendanceRequests = $this->model->getPendingAttendanceRequests();
            }

            if (in_array($role, ['council members', 'admin'], true)) {
                $currentSession = $this->findSessionForTablet();
                $currentSessionAgendas = array_values(array_filter($agendas, fn ($agenda) => !empty($agenda['session_id']) && ($agenda['approval_status'] ?? '') === 'published_to_council'));

                if ($currentSession) {
                    $currentSessionAgendas = array_values(array_filter($currentSessionAgendas, fn ($agenda) => ($agenda['session_id'] ?? '') === $currentSession['id']));
                } elseif (count($currentSessionAgendas) > 0) {
                    $selectedSession = null;
                    $selectedTime = null;
                    foreach ($currentSessionAgendas as $agenda) {
                        $session = $sessionIndex[$agenda['session_id']] ?? null;
                        if (!$session || ($session['status'] ?? '') === 'archived') {
                            continue;
                        }
                        $sessionTime = strtotime($session['date'] . ' ' . $session['start']);
                        if ($selectedTime === null || $sessionTime < $selectedTime) {
                            $selectedSession = $session;
                            $selectedTime = $sessionTime;
                        }
                    }
                    if ($selectedSession !== null) {
                        $currentSession = $selectedSession;
                    }
                }
            } elseif ($role === 'presiding officer') {
                $isAssignedAgenda = function ($agenda) use ($currentUser) {
                    $userId = $currentUser['id'] ?? '';
                    if ($userId === '') {
                        return false;
                    }

                    $recipientIds = $agenda['sent_to_proceeding_id'] ?? '';
                    if (!empty($recipientIds)) {
                        $decoded = json_decode($recipientIds, true);
                        if (is_array($decoded) && in_array($userId, $decoded, true)) {
                            return true;
                        }
                        if ($recipientIds === $userId) {
                            return true;
                        }
                    }

                    $recipientIds = $agenda['sent_to_officer_id'] ?? '';
                    if (!empty($recipientIds)) {
                        $decoded = json_decode($recipientIds, true);
                        if (is_array($decoded) && in_array($userId, $decoded, true)) {
                            return true;
                        }
                        if ($recipientIds === $userId) {
                            return true;
                        }
                    }

                    return false;
                };

                $currentSessionAgendas = array_values(array_filter($agendas, fn ($agenda) => $isAssignedAgenda($agenda) && !empty($agenda['session_id'])));
                usort($currentSessionAgendas, function ($a, $b) use ($sessionIndex) {
                    $dateA = $sessionIndex[$a['session_id']]['date'] ?? '';
                    $timeA = $sessionIndex[$a['session_id']]['start'] ?? '';
                    $dateB = $sessionIndex[$b['session_id']]['date'] ?? '';
                    $timeB = $sessionIndex[$b['session_id']]['start'] ?? '';
                    if ($dateA === $dateB) {
                        return strcmp($timeA, $timeB);
                    }
                    return strcmp($dateA, $dateB);
                });

                if (count($currentSessionAgendas) > 0) {
                    $firstAgenda = $currentSessionAgendas[0];
                    $currentSession = $sessionIndex[$firstAgenda['session_id']] ?? null;
                }
            }
        }

        if (count($currentSessionAgendas) > 0) {
            foreach ($currentSessionAgendas as &$agenda) {
                $session = $sessionIndex[$agenda['session_id']] ?? null;
                if ($session) {
                    $agenda['session_title'] = $session['title'];
                    $agenda['session_date'] = $session['date'];
                    $agenda['session_start'] = $session['start'];
                    $agenda['session_end'] = $session['end'];
                }
                if (empty($agenda['reading_stage'])) {
                    $legacyReading = $agenda['approval_status'] ?? '';
                    if (in_array($legacyReading, ['first_reading', 'second_reading', 'third_reading'], true)) {
                        $agenda['reading_stage'] = $legacyReading;
                    }
                }
            }
            unset($agenda);
        }

        if ($currentUser && !empty($currentUser['notifications'])) {
            $decodedNotifications = json_decode($currentUser['notifications'], true);
            $notifications = is_array($decodedNotifications) ? $decodedNotifications : [];
        }
        usort($notifications, static function (array $left, array $right): int {
            $leftTime = strtotime((string)($left['created_at'] ?? '')) ?: 0;
            $rightTime = strtotime((string)($right['created_at'] ?? '')) ?: 0;
            return $rightTime <=> $leftTime;
        });
        // Only show published agendas to non-admin users (except for approval pages)
        if ($currentUser && !$this->isAdminUser() && $page !== 'proceeding-officer' && $page !== 'ctrfb') {
            $agendas = array_filter($agendas, fn ($a) => !empty($a['published']) || ($a['approval_status'] ?? '') === 'published_to_council' || !empty($a['sent_to_council_at']));
        }
        $legislatives = $data['legislatives'] ?? [];
        $myLegislatives = $currentUser
            ? array_values(array_filter($legislatives, fn (array $item): bool => ($item['createdById'] ?? '') === ($currentUser['id'] ?? '')))
            : [];

        if ($currentUser && !$this->isAdminUser() && ($page === 'legislative-user' || $page === 'legislative-audit-trail')) {
            $legislatives = array_values(array_filter($legislatives, fn (array $item) => $this->canViewLegislativeRecord($item, $currentUser)));
        }

        if ($billOnly && in_array($page, ['legislative-admin', 'legislative-user', 'legislative-bills', 'legislative-audit-trail'], true)) {
            $legislatives = array_values(array_filter($legislatives, fn (array $item) => stripos((string)($item['type'] ?? ''), 'bill') !== false));
        }

        if (!$billOnly && in_array($page, ['legislative-admin', 'legislative-user'], true)) {
            $legislatives = array_values(array_filter($legislatives, fn (array $item) => stripos((string)($item['type'] ?? ''), 'bill') === false));
        }

        if ($page === 'legislative-admin') {
            $legislatives = array_values(array_filter($legislatives, function (array $item) use ($editId): bool {
                $type = strtolower((string)($item['type'] ?? ''));
                $isBillOrResolution = str_contains($type, 'bill') || str_contains($type, 'resolution') || str_contains($type, 'ordinance');
                $isSelectedForReview = $editId !== null && (string)($item['id'] ?? '') === (string)$editId;
                return $isSelectedForReview || !($isBillOrResolution && ($item['status'] ?? '') === 'submitted_to_admin');
            }));
        }

        $isPendingBillsPage = $page === 'legislative-bills'
            && ($currentUser['role'] ?? '') === 'admin'
            && ($_GET['bill_status'] ?? '') === 'pending';

        if ($page === 'legislative-bills' && !$isPendingBillsPage && ($currentUser['role'] ?? '') !== 'presiding officer') {
            $legislatives = array_values(array_filter($legislatives, function (array $item): bool {
                $type = strtolower((string)($item['type'] ?? ''));
                $isBillOrResolution = str_contains($type, 'bill') || str_contains($type, 'resolution') || str_contains($type, 'ordinance');
                return !($isBillOrResolution && ($item['status'] ?? '') === 'submitted_to_admin');
            }));
        }

        if ($page === 'legislative-bills' && ($currentUser['role'] ?? '') === 'admin' && ($_GET['bill_status'] ?? 'pending') === 'pending') {
            $agendaSourceIds = array_flip(array_filter(array_column($agendas, 'source_legislative_id')));
            $legislatives = array_values(array_filter($legislatives, function (array $item) use ($agendaSourceIds): bool {
                $type = strtolower((string)($item['type'] ?? ''));
                $isBillOrResolution = str_contains($type, 'bill') || str_contains($type, 'resolution') || str_contains($type, 'ordinance');
                $isSubmittedOrApproved = in_array($item['status'] ?? '', ['submitted_to_admin', 'approved'], true);
                return $isBillOrResolution
                    && $isSubmittedOrApproved
                    && (($item['status'] ?? '') !== 'approved' || !isset($agendaSourceIds[$item['id'] ?? '']));
            }));
        }

        // Compute analytics for legislative admin page
        $analytics = [];
        $attendanceStats = [];
        if ($page === 'legislative-admin') {
            $analytics = $this->model->getLegislativeStats();
            $attendanceStats = $this->model->getAttendanceStats();
        }
        if ($page === 'analytics') {
            $analytics = $this->model->getAnalytics();
        }

        if ($page === 'legislative-user') {
            if ($statusFilter === 'approved') {
                $legislatives = array_filter($legislatives, fn ($item) => ($item['status'] ?? '') === 'approved');
            }
            if ($statusFilter === 'mine' && $currentUser) {
                $legislatives = array_filter($legislatives, fn ($item) => ($item['createdById'] ?? '') === $currentUser['id']);

                    if ($page === 'ordinances') {
                        // Show only published-to-public ordinances
                        $legislatives = array_filter($legislatives, fn ($item) => !empty($item['published_to_public']));
            
                        if ($statusFilter === 'approved') {
                            $legislatives = array_filter($legislatives, fn ($item) => ($item['status'] ?? '') === 'approved');
                        }
                        if ($statusFilter === 'review') {
                            $legislatives = array_filter($legislatives, fn ($item) => ($item['status'] ?? '') === 'review');
                        }
                    }
            }
        }

        $statusCounts = [
            'all' => count($legislatives),
            'approved' => count(array_filter($legislatives, fn ($item) => ($item['status'] ?? '') === 'approved')),
            'mine' => $currentUser ? count(array_filter($legislatives, fn ($item) => ($item['createdById'] ?? '') === $currentUser['id'])) : 0,
        ];

        $searchQuery = $hashSearch !== '' ? $hashSearch : $search;
        if ($searchQuery !== '') {
            if ($page === 'users') {
                $users = array_filter($users, fn ($user) => str_contains(strtolower($user['name']), strtolower($searchQuery)) || str_contains(strtolower($user['email']), strtolower($searchQuery)));
            }
            if ($page === 'sessions') {
                $sessions = array_filter($sessions, fn ($session) => str_contains(strtolower($session['title']), strtolower($searchQuery)) || str_contains(strtolower($session['location']), strtolower($searchQuery)));
            }
            if ($page === 'legislative-admin' || $page === 'legislative-user' || $page === 'legislative-audit-trail') {
                $legislatives = array_filter($legislatives, function ($item) use ($searchQuery): bool {
                    $searchableValues = [
                        $item['title'] ?? '',
                        $item['type'] ?? '',
                        $item['control_number'] ?? '',
                        $item['documentHash'] ?? '',
                        $item['fileRevisionHash'] ?? '',
                        $item['signed_hash'] ?? '',
                        $item['workflow_status'] ?? '',
                        $item['assigned_role'] ?? '',
                    ];
                    $searchTerm = strtolower($searchQuery);
                    foreach ($searchableValues as $searchableValue) {
                        if (str_contains(strtolower((string)$searchableValue), $searchTerm)) {
                            return true;
                        }
                    }
                    return false;
                });
            }
        }

        foreach ($legislatives as &$legislativeItem) {
            if (!empty($legislativeItem['fileDiffSummary'])) {
                continue;
            }

            $changeLog = $this->normalizeFileChangeLog($legislativeItem['fileChangeLog'] ?? null);
            foreach (array_reverse($changeLog) as $entry) {
                if (empty($entry['diffSummary'])) {
                    continue;
                }

                $legislativeItem['fileDiffSummary'] = is_string($entry['diffSummary']) ? $entry['diffSummary'] : json_encode($entry['diffSummary'], JSON_UNESCAPED_UNICODE);
                if (empty($legislativeItem['lastModifiedAt']) && !empty($entry['changed_at'])) {
                    $legislativeItem['lastModifiedAt'] = $entry['changed_at'];
                }
                if (empty($legislativeItem['lastModifiedByName']) && !empty($entry['changed_by_name'])) {
                    $legislativeItem['lastModifiedByName'] = $entry['changed_by_name'];
                }
                break;
            }
        }
        unset($legislativeItem);

        $selectedLedgerItem = null;
        if ($page === 'legislative-audit-trail') {
            $ledgerId = trim($_GET['ledger_id'] ?? $_GET['id'] ?? '');
            if ($ledgerId !== '') {
                $candidate = $this->model->findLegislative($ledgerId);
                if ($candidate && ($this->isAdminUser() || $this->canViewLegislativeRecord($candidate, $currentUser))) {
                    $selectedLedgerItem = $candidate;
                }
            }
        }

        $attachmentIntegrity = null;
        if ($selectedLedgerItem && !empty($selectedLedgerItem['file_path'])) {
            $attachmentPath = __DIR__ . '/../../storage/uploads/' . basename((string)$selectedLedgerItem['file_path']);
            $attachmentIntegrity = $this->model->verifyLegislativeFileIntegrity($selectedLedgerItem, $attachmentPath);
        }

        $editItem = null;
        if ($editId !== null && $editId !== '' && $editType !== '') {
            if ($editType === 'user') {
                $editItem = $this->model->findUser($editId);
            }
            if ($editType === 'session') {
                $editItem = $this->model->findSession($editId);
            }
            if ($editType === 'agenda') {
                $editItem = $this->model->findAgenda($editId);
            }
            if ($editType === 'legislative') {
                $editItem = $this->model->findLegislative($editId);
                if ($editItem && $page === 'legislative-user' && !$this->isAdminUser() && ($editItem['createdById'] ?? '') !== ($currentUser['id'] ?? '')) {
                    $editItem = null;
                }
                if ($editItem && $currentUser) {
                    $this->model->recordLegislativeAudit((string)$editItem['id'], 'viewed', $currentUser);
                    $legislativeVersions = $this->model->getLegislativeVersions((string)$editItem['id']);
                    $legislativeAudit = $this->model->getLegislativeAudit((string)$editItem['id']);
                }
            }
            if ($editType === 'ctfrb-report') {
                $editItem = $this->model->getCtfrbReport($editId);
            }
        }

        if ($selectedLedgerItem) {
            $this->model->recordLegislativeAudit((string)$selectedLedgerItem['id'], 'viewed_in_records', $currentUser);
            $legislativeVersions = $this->model->getLegislativeVersions((string)$selectedLedgerItem['id']);
            $legislativeAudit = $this->model->getLegislativeAudit((string)$selectedLedgerItem['id']);
            $legislativeSignatures = $this->model->getLegislativeSignatures((string)$selectedLedgerItem['id']);
            $ledgerAgendaSignatures = $this->model->getAgendaPkiSignaturesForLegislative((string)$selectedLedgerItem['id']);
        }

        if ($page === 'sessions' && $openPanel === 'session' && isset($_SESSION['session_form_input'])) {
            $editItem = array_merge($editItem ?? [], $_SESSION['session_form_input']);
            unset($_SESSION['session_form_input']);
        }

        $confirmItem = null;
        if ($confirm === 'delete' && $confirmId !== null && $confirmId !== '' && in_array($editType, ['user', 'session', 'agenda', 'legislative'], true)) {
            if ($editType === 'user') {
                $confirmItem = $this->model->findUser($confirmId);
            }
            if ($editType === 'session') {
                $confirmItem = $this->model->findSession($confirmId);
            }
            if ($editType === 'agenda') {
                $confirmItem = $this->model->findAgenda($confirmId);
            }
            if ($editType === 'legislative') {
                $confirmItem = $this->model->findLegislative($confirmId);
            }
        }

        $flashes = $this->getFlash();
        $sessionOptions = $sessions;

        if ($page === 'login') {
            require __DIR__ . '/../views/login.php';
            return;
        }

        require __DIR__ . '/../views/layout.php';
    }

    private function redirect(string $page, array $params = []): void
    {
        $params = array_merge(['page' => $page], $params);
        $query = http_build_query($params);
        header('Location: index.php?' . $query);
        exit;
    }

    private function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    private function getFlash(): array
    {
        $flashes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flashes;
    }

    private function rememberSessionForm(string $id, string $title, string $desc, string $date, string $start, string $end, string $location, int $capacity, string $status, string $minutes): void
    {
        $_SESSION['session_form_input'] = [
            'id' => $id,
            'title' => $title,
            'desc' => $desc,
            'date' => $date,
            'start' => $start,
            'end' => $end,
            'location' => $location,
            'capacity' => $capacity,
            'status' => $status,
            'minutes' => $minutes,
        ];
    }

    private function parseSessionTime(string $time): ?string
    {
        $normalized = preg_replace('/\s+/', ' ', strtoupper(trim($time)));
        foreach (['!g:i A', '!g:iA', '!H:i'] as $format) {
            $parsed = \DateTimeImmutable::createFromFormat($format, $normalized);
            $errors = \DateTimeImmutable::getLastErrors();
            if ($parsed && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $parsed->format('H:i');
            }
        }
        return null;
    }

    private function synchronizeEndedSessionLceReviews(): int
    {
        $timezone = new \DateTimeZone('Asia/Manila');
        $now = new \DateTimeImmutable('now', $timezone);
        $data = $this->model->getData();
        $legislatives = [];
        foreach ($data['legislatives'] as $item) {
            $legislatives[(string)$item['id']] = $item;
        }
        $lceUsers = array_values(array_filter($data['users'], static fn(array $user): bool =>
            in_array(($user['role'] ?? ''), ['lce', 'mayor'], true) && ($user['status'] ?? 'active') === 'active'
        ));
        $routedIds = [];
        $routedCount = 0;

        foreach ($data['sessions'] as $session) {
            if (in_array(($session['status'] ?? ''), ['cancelled', 'archived'], true)) {
                continue;
            }
            try {
                $sessionEnd = new \DateTimeImmutable((string)$session['date'] . ' ' . (string)$session['end'], $timezone);
            } catch (\Throwable $exception) {
                continue;
            }
            $isCompleted = ($session['status'] ?? '') === 'completed';
            if (!$isCompleted && $sessionEnd > $now) {
                continue;
            }
            if (!$isCompleted) {
                $this->model->updateSession((string)$session['id'], ['status' => 'completed']);
            }

            foreach ($data['agendas'] as $agenda) {
                if ((string)($agenda['session_id'] ?? '') !== (string)$session['id']) {
                    continue;
                }
                $legislativeId = (string)($agenda['source_legislative_id'] ?? '');
                if ($legislativeId === '' || isset($routedIds[$legislativeId]) || !isset($legislatives[$legislativeId])) {
                    continue;
                }
                $legislative = $legislatives[$legislativeId];
                if (($legislative['stream_type'] ?? '') !== 'Approved Resolutions/Ordinances'
                    || in_array(($legislative['lce_action'] ?? ''), ['Pending LCE Review', 'LCE Approved', 'Vetoed'], true)) {
                    continue;
                }

                $nowString = $now->format(DATE_ATOM);
                if (!$this->model->updateLegislative($legislativeId, [
                    'workflow_status' => 'LCE Approval',
                    'routing_status' => 'Sent to LCE',
                    'lce_action' => 'Pending LCE Review',
                    'lce_action_at' => $nowString,
                    'updatedAt' => $nowString,
                ])) {
                    continue;
                }
                $routedIds[$legislativeId] = true;
                $routedCount++;
                $this->model->recordLegislativeAudit($legislativeId, 'sent_to_lce_after_session', null, [
                    'session_id' => (string)$session['id'],
                    'session_title' => (string)($session['title'] ?? ''),
                ]);
                foreach ($lceUsers as $lceUser) {
                    $this->model->addUserNotification(
                        (string)$lceUser['id'],
                        'A legislative matter is ready for LCE review: ' . ($legislative['title'] ?? 'Untitled'),
                        'index.php?page=lce'
                    );
                }
            }
        }

        return $routedCount;
    }
}
