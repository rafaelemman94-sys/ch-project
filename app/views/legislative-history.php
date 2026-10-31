<?php
$historyVersions = $legislativeVersions ?? [];
$historyEvents = $legislativeAudit ?? [];
$historyEscape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$auditViewGroups = [];
$auditEventsForDisplay = [];
foreach ($historyEvents as $event) {
    if (in_array($event['event_type'] ?? '', ['viewed', 'viewed_in_records'], true)) {
        $groupKey = implode('|', [
            (string)($event['event_type'] ?? ''),
            (string)($event['user_id'] ?? ''),
            (string)($event['user_role'] ?? ''),
        ]);
        if (!isset($auditViewGroups[$groupKey])) {
            $auditViewGroups[$groupKey] = ['event' => $event, 'events' => []];
        }
        $auditViewGroups[$groupKey]['events'][] = $event;
        continue;
    }
    $auditEventsForDisplay[] = $event;
}
?>
<div class="legislative-history-panel">
  <section class="legislative-history-group">
    <h4>Versions</h4>
    <div class="legislative-history-list">
      <?php if (!$historyVersions): ?><p class="field-note">No saved versions.</p><?php endif; ?>
      <?php foreach ($historyVersions as $version): ?>
        <article class="legislative-history-item">
          <div class="legislative-history-heading">
            <strong>v<?= (int)$version['version_number'] ?> · <?= $historyEscape($version['status']) ?></strong>
            <span><?= $historyEscape($version['created_by_name'] ?? 'System') ?> · <?= $historyEscape(fmtDateTime($version['created_at'])) ?></span>
          </div>
          <code class="legislative-history-hash">SHA-256: <?= $historyEscape($version['document_hash']) ?></code>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="legislative-history-group">
    <h4>Audit Events</h4>
    <?php if (!$auditEventsForDisplay && !$auditViewGroups): ?>
      <p class="field-note">No audit events recorded.</p>
    <?php else: ?>
      <div class="legislative-history-list">
        <?php foreach ($auditEventsForDisplay as $event): ?>
          <?php
            $detailsText = (string)($event['details'] ?? '');
            $decodedDetails = $detailsText !== '' ? json_decode($detailsText, true) : null;
            $details = is_array($decodedDetails) ? $decodedDetails : [];
            $eventType = (string)($event['event_type'] ?? 'event');
          ?>
          <article class="legislative-history-item<?= $eventType === 'file_integrity_tampered' ? ' integrity-alert' : '' ?>">
            <div class="legislative-history-heading">
              <strong><?= $historyEscape(ucfirst(str_replace('_', ' ', $eventType))) ?></strong>
              <span><?= $historyEscape($event['user_name'] ?? 'System') ?><?= !empty($event['user_role']) ? ' · ' . $historyEscape($event['user_role']) : '' ?> · <?= $historyEscape(fmtDateTime($event['created_at'] ?? '')) ?></span>
            </div>
            <?php if ($details): ?>
              <dl class="legislative-audit-details">
                <?php foreach ($details as $key => $value): ?>
                  <div>
                    <dt><?= $historyEscape(ucwords(str_replace('_', ' ', (string)$key))) ?></dt>
                    <dd><?= is_scalar($value) ? $historyEscape((string)$value) : $historyEscape(json_encode($value, JSON_UNESCAPED_UNICODE)) ?></dd>
                  </div>
                <?php endforeach; ?>
              </dl>
            <?php elseif ($detailsText !== ''): ?>
              <p class="field-note"><?= $historyEscape($detailsText) ?></p>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
      <?php foreach ($auditViewGroups as $group): ?>
        <?php $viewEvent = $group['event']; $viewCount = count($group['events']); ?>
        <details class="audit-view-group">
          <summary><?= $historyEscape($viewEvent['user_name'] ?? 'System') ?> opened this record <?= $viewCount ?> time<?= $viewCount === 1 ? '' : 's' ?> · <?= $historyEscape($viewEvent['user_role'] ?? '') ?></summary>
          <ul>
            <?php foreach ($group['events'] as $viewEventItem): ?>
              <li><?= $historyEscape(fmtDateTime($viewEventItem['created_at'] ?? '')) ?></li>
            <?php endforeach; ?>
          </ul>
        </details>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>
</div>