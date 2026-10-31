<?php
$role = $currentUser['role'] ?? '';
$lceRecords = array_values(array_filter($legislatives ?? [], static function (array $item): bool {
    return in_array($item['routing_status'] ?? '', ['Sent to LCE', 'Archived for Local Download', 'Returned to Council - Vetoed'], true)
        || in_array($item['lce_action'] ?? '', ['Pending LCE Review', 'LCE Approved', 'Vetoed'], true);
}));
$workflowSteps = ['Receiving', 'Review', 'Agenda', 'Agenda Signing', 'SP Session', 'Committee/Referral', 'Final Draft', 'Signatures', 'LCE Approval', 'Archive'];
?>
<div class="page-section">
  <div class="section-heading">
    <h2>LCE Legislative Review</h2>
    <p>View ordinances and resolutions sent for your review, then approve or veto them.</p>
  </div>
  <section class="card" style="margin-bottom:18px;">
    <div class="card-head">
      <div>
        <div class="card-title">Document Workflow</div>
        <div class="field-note">Workflow progress for legislative matters routed to the LCE.</div>
      </div>
    </div>
    <div class="card-body" style="display:grid;gap:12px;">
      <?php if (empty($lceRecords)): ?>
        <div class="empty-state">No legislative matters have been sent to the LCE.</div>
      <?php else: ?>
        <?php foreach ($lceRecords as $record): ?>
          <?php
            $workflowStage = $record['workflow_status'] ?? 'Receiving';
            if ($workflowStage === 'Filing/Scan' && ($record['lce_action'] ?? '') === 'LCE Approved') {
              $workflowStage = 'Archive';
            }
            $workflowStepIndex = array_search($workflowStage, $workflowSteps, true);
          ?>
          <article style="border:1px solid #d8dee7;border-radius:8px;padding:14px;background:#fff;">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
              <div>
                <strong><?= esc($record['control_number'] ?? 'Unregistered') ?> · <?= esc($record['title'] ?? 'Untitled document') ?></strong>
                <div class="field-note" style="margin-top:4px;">Received <?= esc(fmtDateTime($record['received_at'] ?? $record['createdAt'] ?? '')) ?> · <?= esc($record['lce_action'] ?? 'Pending LCE Review') ?></div>
              </div>
              <span class="badge badge-info"><?= esc($workflowStage) ?></span>
            </div>
            <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:12px;" aria-label="Document workflow progress">
              <?php foreach ($workflowSteps as $stepIndex => $step): ?>
                <span style="font-size:11px;padding:5px 7px;border-radius:4px;background:<?= $stepIndex === $workflowStepIndex ? '#dbeafe' : ($workflowStepIndex !== false && $stepIndex < $workflowStepIndex ? '#dcfce7' : '#f1f5f9') ?>;color:#334155;"><?= esc($step) ?></span>
              <?php endforeach; ?>
            </div>
            <?php if (trim((string)($record['content'] ?? '')) !== ''): ?>
              <details style="margin-top:12px;border-top:1px solid #e5e7eb;padding-top:10px;">
                <summary style="cursor:pointer;font-weight:600;">View ordinance / resolution text</summary>
                <div style="margin-top:10px;max-height:360px;overflow:auto;overflow-wrap:anywhere;"><?= \App\Services\LegislativeContentFormatter::toSafeHtml((string)$record['content']) ?></div>
              </details>
            <?php endif; ?>
              <?php if (empty($record['file_path'])): ?><div class="field-note" style="margin-top:12px;">No document file is attached.</div><?php endif; ?>
            <?php if (in_array($role, ['lce', 'mayor'], true) && ($record['lce_action'] ?? '') === 'Pending LCE Review'): ?>
              <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:12px;">
                <form method="post" action="index.php?page=lce" enctype="multipart/form-data" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                  <input type="hidden" name="action" value="sign_legislative_document" />
                  <input type="hidden" name="legislative_id" value="<?= esc($record['id']) ?>" />
                  <label class="field-note">PKI key <input type="file" name="signing_key_file" accept=".txt,.pem,text/plain,application/x-pem-file" required /></label>
                  <button class="btn btn-success btn-xs" type="submit">Approve &amp; Sign</button>
                </form>
                <form method="post" action="index.php?page=lce" onsubmit="return confirm('Record an LCE veto for this legislative matter?')">
                  <input type="hidden" name="action" value="process_lce_decision" />
                  <input type="hidden" name="legislative_id" value="<?= esc($record['id']) ?>" />
                  <input type="hidden" name="decision" value="vetoed" />
                  <button class="btn btn-danger btn-xs" type="submit">Veto</button>
                </form>
              </div>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>
  <section class="card">
    <div class="card-head">
      <div>
        <div class="card-title">Local Legislative Archive</div>
        <div class="field-note">Files are stored on this server and remain downloadable when internet access is unavailable.</div>
      </div>
      <span class="badge b-approved"><?= count($lceRecords) ?> record<?= count($lceRecords) === 1 ? '' : 's' ?></span>
    </div>
    <div class="card-body" style="display:grid;gap:12px;">
      <?php if (empty($lceRecords)): ?>
        <div class="empty-state">No legislative matters have been sent to the LCE.</div>
      <?php else: ?>
        <?php foreach ($lceRecords as $record): ?>
          <article style="border:1px solid #e5e7eb;border-radius:10px;padding:14px;background:#fff;">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
              <div>
                <strong><?= esc($record['title'] ?? 'Untitled legislative matter') ?></strong>
                <div class="field-note" style="margin-top:5px;">Status: <?= esc($record['lce_action'] ?? 'Pending LCE Review') ?> · <?= esc($record['routing_status'] ?? '') ?></div>
                <?php if (!empty($record['admin_decision_notes'])): ?><div class="field-note" style="margin-top:5px;">Admin note: <?= esc($record['admin_decision_notes']) ?></div><?php endif; ?>
              </div>
              <span class="badge <?= badgeClass($record['status'] ?? 'draft') ?>"><?= esc($record['status'] ?? 'draft') ?></span>
            </div>
            <?php if (trim((string)($record['content'] ?? '')) !== ''): ?>
              <details style="margin-top:12px;border-top:1px solid #e5e7eb;padding-top:10px;">
                <summary style="cursor:pointer;font-weight:600;">View ordinance / resolution text</summary>
                <div style="margin-top:10px;max-height:360px;overflow:auto;overflow-wrap:anywhere;"><?= \App\Services\LegislativeContentFormatter::toSafeHtml((string)$record['content']) ?></div>
              </details>
            <?php endif; ?>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
              <?php if (($record['lce_action'] ?? '') === 'Pending LCE Review'): ?>
                <span class="field-note">Approve or veto this pending matter in Document Workflow above.</span>
              <?php endif; ?>
              <?php if (($record['lce_action'] ?? '') === 'LCE Approved'): ?><span class="field-note" style="color:#047857;font-weight:600;">Approved and stored locally</span><?php elseif (($record['lce_action'] ?? '') === 'Vetoed'): ?><span class="field-note" style="color:#b91c1c;font-weight:600;">Submitter notified of LCE veto</span><?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>
</div>
