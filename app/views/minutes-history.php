<?php
$isAdmin = ($currentUser['role'] ?? '') === 'admin';
$isCouncilMember = ($currentUser['role'] ?? '') === 'council members';
$minutesSessionsById = [];
foreach ($sessions ?? [] as $session) {
  $minutesSessionsById[(string)($session['id'] ?? '')] = $session;
}
$minutesHistory = array_values(array_filter($data['delivery_history'] ?? [], static function (array $entry) use ($currentUser, $isAdmin): bool {
    if (($entry['item_type'] ?? '') !== 'session_minutes') {
        return false;
    }
    if ($isAdmin) {
        return true;
    }
    $recipientIds = json_decode($entry['recipient_ids'] ?? '[]', true);
    return is_array($recipientIds) && in_array((string)($currentUser['id'] ?? ''), array_map('strval', $recipientIds), true);
}));
?>
<div class="page-section minutes-history-page">
  <div class="section-heading">
    <h2>Session Minutes History</h2>
    <p><?= $isAdmin ? 'Sent session minutes and their delivery details.' : 'Session minutes sent to your account.' ?></p>
  </div>
  <section class="card minutes-history-card">
    <div class="card-head">
      <span class="card-title">Sent Minutes</span>
      <span class="badge b-approved"><?= count($minutesHistory) ?> record<?= count($minutesHistory) === 1 ? '' : 's' ?></span>
    </div>
    <div class="card-body" style="display:grid;gap:10px">
      <?php if (empty($minutesHistory)): ?>
        <div class="empty-state">No session minutes have been sent<?= $isAdmin ? ' yet' : ' to you yet' ?>.</div>
      <?php else: ?>
        <?php foreach ($minutesHistory as $entry): ?>
          <?php
            $recipientNames = json_decode($entry['recipient_names'] ?? '[]', true);
            if (!is_array($recipientNames)) {
                $recipientNames = [];
            }
            $sessionId = (string)($entry['session_id'] ?? $entry['item_id'] ?? '');
            $session = $minutesSessionsById[$sessionId] ?? null;
            $sessionMinutes = trim((string)($session['minutes'] ?? ''));
          ?>
          <article class="minutes-history-entry" style="border:1px solid #e5e7eb;border-radius:8px;padding:12px;background:#fff">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap">
              <div>
                <strong><?= esc($entry['item_title'] ?? 'Untitled session') ?></strong>
                <div class="field-note" style="margin-top:4px">Sent <?= esc(fmtDateTime($entry['created_at'] ?? '')) ?> by <?= esc($entry['sent_by_name'] ?? 'Admin') ?></div>
              </div>
              <span class="badge b-published"><?= esc(str_replace('_', ' ', $entry['action'] ?? 'sent')) ?></span>
            </div>
            <?php if ($isAdmin): ?>
              <div class="field-note" style="margin-top:8px"><strong>Recipients:</strong> <?= esc(implode(', ', $recipientNames) ?: 'Not recorded') ?></div>
            <?php else: ?>
              <div class="field-note" style="margin-top:8px"><strong>Recipients:</strong> You</div>
            <?php endif; ?>
            <?php if (!empty($entry['template_name'])): ?><div class="field-note" style="margin-top:4px"><strong>Template:</strong> <?= esc($entry['template_name']) ?></div><?php endif; ?>
            <?php if (!empty($entry['notes'])): ?><div class="field-note" style="margin-top:4px"><strong>Note:</strong> <?= esc($entry['notes']) ?></div><?php endif; ?>
            <?php if ($isCouncilMember): ?>
              <?php if ($sessionMinutes !== ''): ?>
                <div class="minutes-print-content" style="margin-top:12px;padding-top:12px;border-top:1px solid #e5e7eb;white-space:pre-wrap"><?= esc($sessionMinutes) ?></div>
                <button class="btn btn-secondary btn-xs minutes-history-print" type="button" onclick="printSentMinutes(this)">🖨️ Print Minutes</button>
              <?php else: ?>
                <div class="field-note" style="margin-top:8px">Minutes text is not available to print for this session.</div>
              <?php endif; ?>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>
</div>