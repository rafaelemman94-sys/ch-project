<?php
/**
 * Session Control Panel
 * Variables available from controller: $currentUser, $currentSession, $currentSessionAgendas, $data
 */
$sessionOutcomeItems = array_values(array_filter($data['legislatives'] ?? [], fn (array $item): bool => ($item['stream_type'] ?? '') === 'Approved Resolutions/Ordinances'));
$sessionAgendaSourceIds = array_flip(array_filter(array_column($currentSessionAgendas ?? [], 'source_legislative_id')));
$sessionOutcomeItems = array_values(array_filter($data['legislatives'] ?? [], static function (array $item) use ($sessionAgendaSourceIds): bool {
  return ($item['stream_type'] ?? '') === 'Approved Resolutions/Ordinances'
    && isset($sessionAgendaSourceIds[$item['id'] ?? '']);
}));
?>
<div id="pg-session-control" class="page active session-control-page" data-session-id="<?= $currentSession ? esc($currentSession['id']) : '' ?>">
  <div class="card session-control-hero">
    <div class="card-head">
      <div>
        <div class="card-title">Session Control Panel</div>
        <div class="session-control-kicker">Live attendance and agenda voting</div>
      </div>
      <span class="badge <?= ($currentSession['status'] ?? '') === 'ongoing' ? 'b-ongoing' : 'b-approved' ?>"><?= ($currentSession['status'] ?? '') === 'ongoing' ? '● Live' : esc(ucfirst((string)($currentSession['status'] ?? 'scheduled'))) ?></span>
      <?php if (($currentUser['role'] ?? '') === 'admin' && ($currentSession['status'] ?? '') === 'completed'): ?>
        <form method="post" action="index.php?page=session-control" onsubmit="return confirm('Remove this completed session from Session Control? Its session history will be kept.')">
          <input type="hidden" name="action" value="archive_completed_session" />
          <input type="hidden" name="session_id" value="<?= esc($currentSession['id']) ?>" />
          <button class="btn btn-secondary btn-xs" type="submit">Remove Completed Session</button>
        </form>
      <?php endif; ?>
    </div>
  <?php if (!$currentSession): ?>
    <div class="empty-state"><div class="empty-icon">📅</div><div class="empty-txt">No active session found for your account or currently none is ongoing.</div></div>
  <?php else: ?>
    <div class="session-control-grid">
      <div class="session-control-column">
        <div class="section-label">Current Session</div>
        <div class="session-title"><?= esc($currentSession['title']) ?></div>
        <div class="session-meta"><?= fmtDate($currentSession['date']) ?> <span>•</span> <?= esc(date('g:i A', strtotime((string)$currentSession['start']))) ?> - <?= esc(date('g:i A', strtotime((string)$currentSession['end']))) ?></div>
        <?php if (($currentUser['role'] ?? '') === 'admin' && ($currentSession['status'] ?? '') === 'ongoing'): ?>
          <form method="post" action="index.php?page=session-control" style="margin-top:12px" onsubmit="return confirm('End this session now and send its resolutions and ordinances to the LCE for review?')">
            <input type="hidden" name="action" value="end_session" />
            <input type="hidden" name="session_id" value="<?= esc($currentSession['id']) ?>" />
            <button class="btn btn-danger btn-xs" type="submit">End Session &amp; Send to LCE</button>
          </form>
        <?php endif; ?>
        <div class="attendance-stat"><span>Attendance</span><strong><span id="attendanceCount">-</span> / <?= count(array_filter($data['users'], fn($u)=> $u['role']==='council members')) ?></strong></div>

        <div class="section-label agenda-section-label">Agendas in this Session</div>
        <ul id="agendaList" class="session-agenda-list">
          <?php if (count($currentSessionAgendas) === 0): ?>
            <li>No approved agendas for this session.</li>
          <?php else: ?>
            <?php foreach ($currentSessionAgendas as $a): ?>
              <li data-id="<?= esc($a['id']) ?>" class="agenda-item">
                <button class="btn-plain select-agenda" data-id="<?= esc($a['id']) ?>">📄 <?= esc($a['title']) ?></button>
                <a class="agenda-link" href="download.php?file=<?= urlencode($a['file_path']) ?>" target="_blank" title="Open agenda">↗</a>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>

        <div class="session-control-action">
          <button id="checkInBtn" class="btn btn-primary">✓ Check In</button>
          <div id="attendanceRequestStatus" style="margin-top:8px;font-size:12px;color:#64748b" aria-live="polite"></div>
        </div>
      </div>

      <div class="session-control-column session-live-column">
        <div class="section-label">Selected Agenda</div>
        <h3 id="currentAgendaTitle">Select an agenda</h3>
        <div id="votingControls" class="voting-controls" style="display:none">
          <div class="vote-actions">
            <button class="btn btn-primary voteBtn" data-vote="FAVOR">FAVOR</button>
            <button class="btn btn-danger voteBtn" data-vote="AGAINST">AGAINST</button>
            <button class="btn btn-ghost voteBtn" data-vote="ABSTAIN">ABSTAIN</button>
          </div>
          <div class="tally-box"><span>Current Tally</span><strong id="tallyDisplay">—</strong></div>
        </div>

        <div class="section-label presence-label">Presence</div>
        <div id="presenceList" class="presence-list">Loading…</div>
      </div>
    </div>

    <section class="session-outcome-board">
      <div class="section-label">Session Outcome Workflow</div>
      <div class="session-outcome-title">Approved Resolutions / Ordinances</div>
      <div class="session-outcome-flow">RECEIVING → REVIEW → AGENDA → AGENDA SIGNING → SP SESSION → COMMITTEE/REFERRAL → FINAL DRAFT → SIGNATURES → LCE APPROVAL → FILING/SCAN → ARCHIVE</div>
      <?php if (empty($sessionOutcomeItems)): ?>
        <div class="session-outcome-empty">No approved resolutions or ordinances are linked to this session workflow.</div>
      <?php else: ?>
        <div class="session-outcome-list">
          <?php foreach ($sessionOutcomeItems as $outcome): ?>
            <?php $outcomeStage = $outcome['workflow_status'] ?? 'Receiving'; ?>
            <article class="session-outcome-item">
              <div>
                <strong><?= esc($outcome['title'] ?? 'Untitled resolution') ?></strong>
                <div class="session-outcome-meta">Stage: <?= esc($outcomeStage) ?> · LCE: <?= esc($outcome['lce_action'] ?? 'Pending') ?><?php if (!empty($outcome['archive_status'])): ?> · <?= esc($outcome['archive_status']) ?><?php endif; ?></div>
              </div>
              <div class="session-outcome-actions">
                <?php $nextActions = ['Receiving' => ['review', 'Start Review'], 'Review' => ['agenda', 'Send to Agenda'], 'SP Session' => ['final_draft', 'Complete Session Review'], 'Committee/Referral' => ['final_draft', 'Complete Final Draft'], 'Final Draft' => ['signature', 'Send for Signatures'], 'Filing/Scan' => ['archive', 'Archive Signed Record']]; $nextAction = $nextActions[$outcomeStage] ?? null; ?>
                <?php $isLce = in_array(($currentUser['role'] ?? ''), ['mayor', 'lce'], true); ?>
                <?php if (is_array($nextAction) && ((($currentUser['role'] ?? '') === 'admin' && $nextAction[0] !== 'approval') || ($isLce && in_array($nextAction[0], ['approval', 'revision'], true)))): ?>
                  <form method="post" action="index.php?page=session-control"><input type="hidden" name="action" value="advance_legislative_workflow" /><input type="hidden" name="legislative_id" value="<?= esc($outcome['id']) ?>" /><input type="hidden" name="workflow_action" value="<?= esc($nextAction[0]) ?>" /><button class="btn btn-secondary btn-xs" type="submit"><?= esc($nextAction[1]) ?></button></form>
                <?php endif; ?>
                <?php if ($outcomeStage === 'Signatures' && empty($outcome['locked_at']) && (string)($outcome['assigned_to_id'] ?? '') === (string)($currentUser['id'] ?? '') && in_array(($currentUser['role'] ?? ''), ['mayor', 'lce', 'secretary', 'presiding officer'], true)): ?>
                  <form method="post" action="index.php?page=session-control" enctype="multipart/form-data" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;"><input type="hidden" name="action" value="sign_legislative_document" /><input type="hidden" name="legislative_id" value="<?= esc($outcome['id']) ?>" /><?php if (in_array(($currentUser['role'] ?? ''), ['mayor', 'lce'], true)): ?><input type="file" name="signing_key_file" accept=".txt,.pem,text/plain,application/x-pem-file" aria-label="Personal PKI signing key file" required /><?php else: ?><input type="password" name="key_passphrase" autocomplete="current-password" placeholder="Private key passphrase" aria-label="Private key passphrase" required /><?php endif; ?><button class="btn btn-primary btn-xs" type="submit">PKI Sign & Verify</button></form>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="session-minutes-board">
      <div class="section-label">Approved Minutes</div>
      <div class="session-outcome-title">Session Minutes Workflow</div>
      <div class="session-outcome-flow">APPROVED MINUTES → CORRECT or FINAL DRAFT → PRESIDING OFFICER APPROVAL &amp; SIGNATURE → COUNCIL DELIVERY → FILE</div>
      <?php $minutesStage = $currentSession['minutes_workflow_status'] ?? 'APPROVED MINUTES'; ?>
      <div class="session-minutes-status">Current stage: <strong><?= esc($minutesStage) ?></strong><?php if (!empty($currentSession['minutes_signed_by_name'])): ?> · Signed by <?= esc($currentSession['minutes_signed_by_name']) ?> on <?= esc(fmtDateTime($currentSession['minutes_signed_at'] ?? '')) ?><?php endif; ?><?php if (!empty($currentSession['minutes_archived_at'])): ?> · Archived <?= esc($currentSession['minutes_archived_at']) ?><?php endif; ?></div>
      <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
        <?php if (in_array($minutesStage, ['APPROVED MINUTES', 'CORRECT'], true)): ?>
          <form method="post" action="index.php?page=session-control" class="session-minutes-editor">
            <input type="hidden" name="action" value="update_session_minutes_workflow" />
            <input type="hidden" name="session_id" value="<?= esc($currentSession['id']) ?>" />
            <input type="hidden" name="operation" value="correct" />
            <label class="fg"><span>Correct current minutes</span><textarea name="minutes" required><?= esc($currentSession['minutes'] ?? '') ?></textarea></label>
            <label class="fg"><span>Correction notes</span><input type="text" name="correction_notes" value="<?= esc($currentSession['minutes_correction_notes'] ?? '') ?>" placeholder="What was corrected?" /></label>
            <button class="btn btn-secondary btn-xs" type="submit">Save Correction</button>
          </form>
          <form method="post" action="index.php?page=session-control" class="session-minutes-inline-form"><input type="hidden" name="action" value="update_session_minutes_workflow" /><input type="hidden" name="session_id" value="<?= esc($currentSession['id']) ?>" /><input type="hidden" name="operation" value="finalize" /><button class="btn btn-primary btn-xs" type="submit">No Correction / Final Draft</button></form>
        <?php elseif ($minutesStage === 'FINAL DRAFT'): ?>
          <form method="post" action="index.php?page=session-control" class="session-minutes-inline-form"><input type="hidden" name="action" value="update_session_minutes_workflow" /><input type="hidden" name="session_id" value="<?= esc($currentSession['id']) ?>" /><input type="hidden" name="operation" value="send" /><button class="btn btn-primary btn-xs" type="submit">Send to Presiding Officer</button></form>
        <?php elseif ($minutesStage === 'AWAITING PRESIDING OFFICER'): ?>
          <div class="session-minutes-archived">Waiting for the presiding officer to approve and sign. Council has not been notified.</div>
        <?php elseif ($minutesStage === 'SIGNED AND SENT TO COUNCIL'): ?>
          <form method="post" action="index.php?page=session-control" class="session-minutes-inline-form"><input type="hidden" name="action" value="update_session_minutes_workflow" /><input type="hidden" name="session_id" value="<?= esc($currentSession['id']) ?>" /><input type="hidden" name="operation" value="archive" /><button class="btn btn-success btn-xs" type="submit">Archive Current Minutes</button></form>
        <?php else: ?>
          <div class="session-minutes-archived">The current minutes are archived. Future corrections update this same session record.</div>
        <?php endif; ?>
      <?php endif; ?>
    </section>
  <?php endif; ?>
  <?php if (($currentUser['role'] ?? '') === 'presiding officer'): ?>
    <?php $minutesAwaitingOfficer = array_values(array_filter($data['sessions'] ?? [], static fn (array $session): bool => ($session['minutes_workflow_status'] ?? '') === 'AWAITING PRESIDING OFFICER')); ?>
    <section class="session-minutes-board" style="margin-top:16px">
      <div class="section-label">Presiding Officer</div>
      <div class="session-outcome-title">Minutes Awaiting Approval and Signature</div>
      <?php if (empty($minutesAwaitingOfficer)): ?>
        <div class="session-outcome-empty">No session minutes are awaiting your approval.</div>
      <?php else: ?>
        <div style="display:grid;gap:12px">
          <?php foreach ($minutesAwaitingOfficer as $pendingMinutes): ?>
            <article style="border:1px solid #d8dee7;border-radius:8px;padding:14px;background:#fff">
              <strong><?= esc($pendingMinutes['title'] ?? 'Untitled session') ?></strong>
              <div class="field-note" style="margin-top:4px"><?= esc(fmtDate($pendingMinutes['date'] ?? '')) ?> · Sent <?= esc(fmtDateTime($pendingMinutes['minutes_sent_at'] ?? '')) ?></div>
              <div style="margin-top:12px;padding:12px;border:1px solid #e5e7eb;border-radius:6px;white-space:pre-wrap;background:#f9fafb"><?= esc($pendingMinutes['minutes'] ?? '') ?></div>
              <form method="post" action="index.php?page=session-control" style="display:grid;gap:10px;margin-top:12px">
                <input type="hidden" name="action" value="update_session_minutes_workflow" />
                <input type="hidden" name="session_id" value="<?= esc($pendingMinutes['id']) ?>" />
                <input type="hidden" name="operation" value="approve_and_sign" />
                <label style="display:flex;gap:8px;align-items:flex-start"><input type="checkbox" name="confirm_minutes_signature" value="1" required /><span>I approve these minutes and confirm this action as my electronic signature. They will be sent to active Council members.</span></label>
                <button class="btn btn-primary btn-xs" type="submit">Approve &amp; Sign, Then Send to Council</button>
              </form>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>
  </div>
</div>

