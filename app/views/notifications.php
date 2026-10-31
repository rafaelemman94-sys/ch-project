<?php
$notifications = $notifications ?? [];
?>
<section class="page-section">
  <header class="section-heading">
    <div>
      <h2>Notifications</h2>
      <p><?= count($notifications) ?> notification<?= count($notifications) === 1 ? '' : 's' ?></p>
    </div>
  </header>

  <?php if (!$notifications): ?>
    <div class="empty-state">You have no notifications yet.</div>
  <?php else: ?>
    <div class="notification-page-list">
      <?php foreach ($notifications as $note): ?>
        <a class="notification-page-entry<?= str_starts_with((string)($note['message'] ?? ''), 'Integrity alert:') ? ' integrity-notification' : '' ?>" href="<?= esc($note['url'] ?? 'index.php?page=dashboard') ?>">
          <span class="notification-message"><?= esc($note['message'] ?? '') ?></span>
          <time class="notification-time" datetime="<?= esc($note['created_at'] ?? '') ?>"><?= esc(fmtDate($note['created_at'] ?? '')) ?></time>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>