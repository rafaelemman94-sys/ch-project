<?php
$legislatives = $legislatives ?? [];
$selectedLedgerItem = $selectedLedgerItem ?? null;
$legislativeSignatures = $legislativeSignatures ?? [];
$legislativeVersions = $legislativeVersions ?? [];
$legislativeAudit = $legislativeAudit ?? [];
$ledgerAgendaSignatures = $ledgerAgendaSignatures ?? [];
$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
?>
<section class="page-section">
  <header class="section-heading">
    <div>
      <h2>Legislative Audit Trail</h2>
      <p>Users, actions, approvals, versions, signatures, and timestamps for each legislative record.</p>
    </div>
  </header>

  <div class="ledger-layout">
    <aside class="ledger-side-panel">
      <div class="ledger-side-header"><span class="ledger-kicker"><?= count($legislatives) ?> registered records</span></div>
      <?php if (!$legislatives): ?>
        <div class="empty-state">No records match the current search.</div>
      <?php else: ?>
        <div class="ledger-record-list">
          <?php foreach ($legislatives as $item): ?>
            <a class="ledger-record<?= ($selectedLedgerItem['id'] ?? '') === ($item['id'] ?? null) ? ' active' : '' ?>" href="?page=legislative-audit-trail&ledger_id=<?= rawurlencode((string)$item['id']) ?>">
              <div class="record-main-row">
                <span class="record-dot"></span>
                <div class="record-copy">
                  <div class="record-topline"><strong><?= $escape($item['control_number'] ?? 'Unregistered') ?></strong><span class="badge badge-info"><?= $escape($item['workflow_status'] ?? 'Receiving') ?></span></div>
                  <div class="record-meta"><?= $escape($item['title'] ?? 'Untitled') ?> · <?= $escape($item['type'] ?? 'Document') ?></div>
                </div>
              </div>
              <div class="record-date"><?= $escape($item['assigned_role'] ?? 'Secretariat') ?> · <?= $escape($item['assigned_to_name'] ?? $item['createdByName'] ?? 'Unassigned') ?></div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </aside>

    <main class="ledger-main-panel">
      <?php if (!$selectedLedgerItem): ?>
        <div class="ledger-empty-state"><h3>Select a record</h3><p>Choose a registered document to inspect its versions, integrity hash, signature, and audit history.</p></div>
      <?php else: ?>
        <?php
          $verificationUrl = 'index.php?verify=' . rawurlencode((string)($selectedLedgerItem['control_number'] ?? ''));
          $ledgerStatus = $selectedLedgerItem['workflow_status'] ?? 'Receiving';
          $ledgerStatus = ['For Agenda' => 'Agenda', 'For Approval' => 'Approval'][$ledgerStatus] ?? $ledgerStatus;
          $canMakeAgenda = $ledgerStatus === 'Agenda';
          $ledgerWorkflowActions = [];
          $ledgerActions = $ledgerWorkflowActions[$ledgerStatus] ?? [];
        ?>
        <article class="ledger-detail-shell">
          <header class="detail-header">
            <div><span class="eyebrow"><?= $escape($selectedLedgerItem['control_number'] ?? '') ?></span><h3><?= $escape($selectedLedgerItem['title'] ?? '') ?></h3></div>
            <span class="badge badge-info"><?= $escape($selectedLedgerItem['workflow_status'] ?? 'Receiving') ?></span>
          </header>
          <div class="detail-metrics">
            <div class="metric-box"><span>Type</span><strong><?= $escape($selectedLedgerItem['type'] ?? '') ?></strong></div>
            <div class="metric-box"><span>Received</span><strong><?= $escape(fmtDateTime($selectedLedgerItem['received_at'] ?? $selectedLedgerItem['createdAt'] ?? '')) ?></strong></div>
            <div class="metric-box"><span>Origin / Office</span><strong><?= $escape($selectedLedgerItem['origin'] ?? 'Not recorded') ?> · <?= $escape($selectedLedgerItem['originating_office'] ?? 'Not recorded') ?></strong></div>
            <div class="metric-box"><span>Responsible</span><strong><?= $escape($selectedLedgerItem['assigned_role'] ?? 'Secretariat') ?> · <?= $escape($selectedLedgerItem['assigned_to_name'] ?? $selectedLedgerItem['createdByName'] ?? 'Unassigned') ?></strong></div>
            <div class="metric-box"><span><?= !empty($selectedLedgerItem['file_path']) ? 'Uploaded document SHA-256' : 'Record SHA-256' ?></span><strong class="ledger-document-hash"><?= $escape(!empty($selectedLedgerItem['file_path']) ? ($attachmentIntegrity['expected_hash'] ?? $selectedLedgerItem['fileRevisionHash'] ?? 'Not recorded') : ($selectedLedgerItem['documentHash'] ?? 'Not computed')) ?></strong></div>
            <?php if (!empty($selectedLedgerItem['file_path'])): ?>
              <div class="metric-box"><span>File integrity</span><strong><?= ($attachmentIntegrity['status'] ?? '') === 'verified' ? 'Verified' : ((($attachmentIntegrity['status'] ?? '') === 'tampered') ? 'TAMPERING DETECTED' : 'Not verified') ?></strong></div>
              <?php if (($attachmentIntegrity['status'] ?? '') === 'tampered'): ?>
                <div class="metric-box"><span>Observed file SHA-256</span><strong class="ledger-document-hash"><?= $escape($attachmentIntegrity['actual_hash'] ?? 'Missing') ?></strong></div>
              <?php endif; ?>
            <?php endif; ?>
            <div class="metric-box"><span>Signed SHA-256</span><strong class="ledger-document-hash"><?= $escape($selectedLedgerItem['signed_hash'] ?? 'Not signed') ?></strong></div>
            <div class="metric-box"><span>Signature</span><strong><?= !empty($selectedLedgerItem['signature_verified_at']) ? 'Verified · ' . $escape(fmtDateTime($selectedLedgerItem['signature_verified_at'])) : 'Not verified' ?></strong></div>
            <div class="metric-box"><span>Version lock</span><strong><?= !empty($selectedLedgerItem['locked_at']) ? 'Locked · ' . $escape(fmtDateTime($selectedLedgerItem['locked_at'])) : 'Editable' ?></strong></div>
            <?php if (!empty($selectedLedgerItem['scan_file_path'])): ?><div class="metric-box"><span>Filing scan</span><strong><a class="link" href="download.php?file=<?= rawurlencode((string)$selectedLedgerItem['scan_file_path']) ?>" target="_blank" rel="noopener">Open scanned filing</a></strong></div><?php endif; ?>
          </div>
          <?php if (!empty($selectedLedgerItem['file_path'])): ?>
            <div class="ledger-history-section">
              <h4>Uploaded Attachment</h4>
              <?php if (($attachmentIntegrity['status'] ?? '') === 'tampered'): ?>
                <p class="field-note" role="alert">Integrity alert: the file in storage/uploads no longer matches its recorded SHA-256. Its download has been blocked and the bill owner has been notified.</p>
              <?php else: ?>
                <a class="btn btn-secondary btn-xs" href="download.php?file=<?= rawurlencode((string)$selectedLedgerItem['file_path']) ?>">Download verified attachment</a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <?php if (($currentUser['role'] ?? '') === 'admin' && ($ledgerActions || $canMakeAgenda)): ?>
            <section class="ledger-history-section">
              <h4>Workflow Action</h4>
              <?php if (empty($selectedLedgerItem['assigned_to_id'])): ?>
                <p class="field-note">No responsible Secretariat user is assigned. An admin can still advance this record.</p>
              <?php endif; ?>
              <?php if ($canMakeAgenda): ?>
                <form method="post" action="index.php?page=legislative-audit-trail&amp;ledger_id=<?= rawurlencode((string)$selectedLedgerItem['id']) ?>" enctype="multipart/form-data" onsubmit="return confirm('Add this document to the selected session agenda and send it for Presiding Officer signature?')">
                  <input type="hidden" name="action" value="approve_legislative" />
                  <input type="hidden" name="legislative_id" value="<?= $escape($selectedLedgerItem['id']) ?>" />
                  <label class="fg"><span>Session</span><select name="session_id" required><option value="">Select session</option><?php foreach (($data['sessions'] ?? []) as $session): ?><option value="<?= $escape($session['id']) ?>"><?= $escape($session['title']) ?> · <?= $escape($session['date']) ?></option><?php endforeach; ?></select></label>
                  <label class="fg"><span>Agenda file (optional)</span><input type="file" name="agenda_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp,.txt" /></label>
                  <button class="btn btn-primary" type="submit">Make Agenda</button>
                </form>
              <?php endif; ?>
              <?php foreach ($ledgerActions as $workflowAction => $actionLabel): ?>
                <form method="post" action="index.php?page=legislative-audit-trail&amp;ledger_id=<?= rawurlencode((string)$selectedLedgerItem['id']) ?>">
                  <input type="hidden" name="action" value="advance_legislative_workflow" />
                  <input type="hidden" name="legislative_id" value="<?= $escape($selectedLedgerItem['id']) ?>" />
                  <input type="hidden" name="workflow_action" value="<?= $escape($workflowAction) ?>" />
                  <input type="hidden" name="workflow_return_page" value="legislative-audit-trail" />
                  <button class="btn btn-primary" type="submit"><?= $escape($actionLabel) ?></button>
                </form>
              <?php endforeach; ?>
            </section>
          <?php endif; ?>

          <section class="ledger-history-section">
            <h4>Signature History</h4>
            <?php if (!$legislativeSignatures && !$ledgerAgendaSignatures): ?><p class="field-note">No signed versions are recorded.</p><?php endif; ?>
            <?php foreach ($legislativeSignatures as $signature): ?>
              <div class="ledger-history-entry"><strong>Version <?= (int)$signature['version_number'] ?> · <?= $escape($signature['signer_name']) ?> · <?= $escape($signature['signer_role']) ?></strong><span><?= $escape($signature['algorithm']) ?> · <?= $escape(fmtDateTime($signature['created_at'])) ?></span><code>Document SHA-256: <?= $escape($signature['document_hash']) ?></code><code>Key fingerprint: <?= $escape($signature['certificate_fingerprint']) ?></code><a class="btn btn-secondary btn-xs" href="download.php?docx=1&legislative_id=<?= rawurlencode((string)$selectedLedgerItem['id']) ?>&version=<?= (int)$signature['version_number'] ?>">Download signed copy</a><a class="btn btn-secondary btn-xs" href="index.php?verify=<?= rawurlencode((string)($selectedLedgerItem['control_number'] ?? '')) ?>&version=<?= (int)$signature['version_number'] ?>" target="_blank" rel="noopener">Verify signed version</a></div>
            <?php endforeach; ?>
            <?php foreach ($ledgerAgendaSignatures as $signature): ?>
              <div class="ledger-history-entry">
                <strong>Agenda signature · <?= $escape($signature['agenda_title']) ?> · <?= $escape($signature['signer_name']) ?> · <?= $escape($signature['signer_role']) ?></strong>
                <span><?= $escape($signature['algorithm']) ?> · <?= $escape(fmtDateTime($signature['signed_at'])) ?></span>
                <?php if (!empty($signature['proceeding_signature'])): ?><img class="ledger-signature-image" src="<?= $escape($signature['proceeding_signature']) ?>" alt="Signature representation by <?= $escape($signature['signer_name']) ?>" /><?php endif; ?>
                <code>Agenda SHA-256: <?= $escape($signature['document_hash']) ?></code>
                <a class="btn btn-secondary btn-xs" href="index.php?verify_agenda=<?= rawurlencode((string)$signature['agenda_id']) ?>" target="_blank" rel="noopener">Verify agenda signature</a>
              </div>
            <?php endforeach; ?>
          </section>

          <section class="ledger-history-section">
            <h4>Version & Audit History</h4>
            <?php include __DIR__ . '/legislative-history.php'; ?>
          </section>
        </article>
      <?php endif; ?>
    </main>
  </div>
</section>