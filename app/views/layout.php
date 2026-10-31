<?php
/**
 * @var string $page
 * @var array $data
 * @var array $users
 * @var array $sessions
 * @var array $agendas
 * @var string|null $search
 * @var string|null $openPanel
 * @var array|null $editItem
 * @var string|null $confirm
 * @var array|null $confirmItem
 * @var array $flashes
 * @var array $sessionOptions
 */

$pages = [
    'dashboard' => ['title' => 'Dashboard', 'search' => false, 'action' => null, 'actionLabel' => null],
    'users' => ['title' => 'Manage Users', 'search' => true, 'action' => 'Create User', 'actionLabel' => 'Create User', 'panel' => 'user'],
    'sessions' => ['title' => 'Session Scheduling', 'search' => true, 'action' => 'Schedule Session', 'actionLabel' => 'Schedule Session', 'panel' => 'session'],
    'agendas' => ['title' => 'Agenda Upload', 'search' => false, 'action' => 'Upload Agenda', 'actionLabel' => 'Upload Agenda', 'panel' => 'agenda'],
    'create-document' => ['title' => 'Create New Document', 'search' => false, 'action' => null, 'actionLabel' => null],
    'tablet' => ['title' => 'Tablet Session', 'search' => false, 'action' => null, 'actionLabel' => null],
    'proceeding-officer' => ['title' => 'Agenda Approval', 'search' => true, 'action' => null, 'actionLabel' => null],
    'ctrfb' => ['title' => 'Agenda Review', 'search' => true, 'action' => null, 'actionLabel' => null],
    'ctfrb-report' => ['title' => 'CTFRB Report', 'search' => false, 'action' => null, 'actionLabel' => null],
    'legislative-admin' => ['title' => 'Legislative Records', 'search' => true, 'action' => null, 'actionLabel' => null, 'panel' => null],
    'legislative-user' => ['title' => 'Legislative', 'search' => true, 'action' => 'Submit Proposal', 'actionLabel' => 'New Proposal', 'panel' => 'legislative'],
    'legislative-bills' => ['title' => 'Resolutions / Ordinances', 'search' => true, 'action' => 'Upload Resolution / Ordinance', 'actionLabel' => 'Upload Resolution / Ordinance', 'panel' => 'legislative'],
    'legislative-audit-trail' => ['title' => 'Legislative Audit Trail', 'search' => false, 'action' => null, 'actionLabel' => null],
    'lce' => ['title' => 'LCE Legislative Review', 'search' => false, 'action' => null, 'actionLabel' => null],
    'notifications' => ['title' => 'Notifications', 'search' => false, 'action' => null, 'actionLabel' => null],
  'ordinances' => ['title' => 'Approved Bills', 'search' => true, 'action' => null, 'actionLabel' => null],
];

$pageConfig = $pages[$page] ?? $pages['dashboard'];
if ($page === 'legislative-bills' && ($_GET['bill_status'] ?? '') === 'pending') {
  $pageConfig['title'] = 'Pending Legislative Matters';
}
if ($page === 'legislative-bills' && ($currentUser['role'] ?? '') === 'council members') {
  $pageConfig['action'] = null;
  $pageConfig['actionLabel'] = null;
  $pageConfig['panel'] = null;
}

function esc($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function fmtDate($value): string
{
    if (!$value) {
        return '—';
    }
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return esc($value);
    }
    return date('M j, Y', $timestamp);
}

function fmtDateTime($value): string
{
    if (!$value) {
        return '—';
    }
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return esc($value);
    }
    return date('M j, Y · g:i A', $timestamp);
}

function isTextEditableMime($mimeType): bool
{
    $mimeType = strtolower((string)$mimeType);
    return str_starts_with($mimeType, 'text/')
        || in_array($mimeType, [
            'application/json',
            'application/xml',
            'application/javascript',
            'application/x-javascript',
            'application/xhtml+xml',
            'application/x-php',
            'application/php',
            'application/msword',
            'application/vnd.ms-word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ], true);
}

function extractDocumentTextPreview(string $path, string $mimeType): string
{
    if (!is_file($path) || !is_readable($path)) {
        return '';
    }

    $mimeType = strtolower($mimeType);
    if (str_starts_with($mimeType, 'text/') || in_array($mimeType, ['application/json', 'application/xml', 'application/javascript', 'application/x-javascript', 'application/xhtml+xml', 'application/x-php', 'application/php'], true)) {
        $content = @file_get_contents($path);
        return $content === false ? '' : $content;
    }

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($extension === 'docx') {
      if (!class_exists('ZipArchive')) {
        return '';
      }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
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

function fmtSize($bytes): string
{
    if (!$bytes) {
        return '0 B';
    }
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = min(floor(log(max($bytes, 1), 1024)), count($units) - 1);
    return round($bytes / (1024 ** $i), 1) . ' ' . $units[$i];
}

function badgeClass($value): string
{
    $map = [
        'active' => 'b-active',
        'inactive' => 'b-inactive',
        'suspended' => 'b-suspended',
        'scheduled' => 'b-ongoing',
        'ongoing' => 'b-ongoing',
        'completed' => 'b-completed',
        'cancelled' => 'b-cancelled',
        'admin' => 'b-admin',
        'user' => 'b-user',
        'council members' => 'b-user',
        'presiding officer' => 'b-admin',
        'ctrfb' => 'b-approved',
        'draft' => 'b-pending',
        'review' => 'b-ongoing',
        'published' => 'b-published',
        'approved' => 'b-approved',
        'rejected' => 'b-inactive',
    ];
    return $map[$value] ?? 'b-active';
}

function fileIcon($type): string
{
    if (!$type) {
        return '📄';
    }
    if (str_contains($type, 'pdf')) {
        return '📕';
    }
    if (str_contains($type, 'word') || str_contains($type, 'document')) {
        return '📘';
    }
    if (str_contains($type, 'presentation') || str_contains($type, 'powerpoint')) {
        return '📙';
    }
    if (str_contains($type, 'sheet') || str_contains($type, 'excel')) {
        return '📗';
    }
    if (str_contains($type, 'image')) {
        return '🖼️';
    }
    return '📄';
}

function fileIconBg($type): string
{
    if (!$type) {
        return 'rgba(99,102,241,.15)';
    }
    if (str_contains($type, 'pdf')) {
        return 'rgba(239,68,68,.15)';
    }
    if (str_contains($type, 'word') || str_contains($type, 'document')) {
        return 'rgba(59,130,246,.15)';
    }
    if (str_contains($type, 'presentation') || str_contains($type, 'powerpoint')) {
        return 'rgba(245,158,11,.15)';
    }
    if (str_contains($type, 'sheet') || str_contains($type, 'excel')) {
        return 'rgba(16,185,129,.15)';
    }
    if (str_contains($type, 'image')) {
        return 'rgba(236,72,153,.15)';
    }
    return 'rgba(99,102,241,.15)';
}

  function readingStageLabel($value): string
  {
    return match ($value) {
      'first', 'first_reading' => 'First Reading',
      'second', 'second_reading' => 'Second Reading',
      'third', 'third_reading' => 'Third Reading',
      default => 'Not set',
    };
  }

  function supportsInlinePreview(?string $type): bool
  {
    if (!$type) {
      return false;
    }

    return str_starts_with($type, 'image/') || str_starts_with($type, 'text/') || str_contains($type, 'pdf');
  }

function avatarColor($name): string
{
    $colors = ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#3b82f6', '#ef4444', '#06b6d4'];
    return $colors[ord($name[0] ?? 'A') % count($colors)];
}

$filteredUsers = $users;
$filteredSessions = $sessions;
$activeUsers = count(array_filter($data['users'], fn ($user) => $user['status'] === 'active'));
$upcomingSessions = count(array_filter($data['sessions'], fn ($session) => $session['date'] >= date('Y-m-d') && $session['status'] === 'scheduled'));
$activeRate = $data['users'] ? round($activeUsers / count($data['users']) * 100) : 0;

$dashboardUsers = array_reverse(array_slice(array_values($data['users']), -5));
$dashboardSessions = $data['sessions'];
usort($dashboardSessions, fn ($a, $b) => strcmp($a['date'] ?? '', $b['date'] ?? ''));
$dashboardSessions = array_filter($dashboardSessions, fn ($session) => ($session['date'] ?? '') >= date('Y-m-d'));
$dashboardSessions = array_slice($dashboardSessions, 0, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Admin Portal</title>
<link rel="stylesheet" href="public/assets/style.css?v=<?= (int)@filemtime(dirname(__DIR__, 2) . '/public/assets/style.css') ?>" />
  <link rel="manifest" href="public/manifest.json" />
  <meta name="theme-color" content="#6366f1" />
  <link rel="apple-touch-icon" href="public/assets/icon-192.png" />
</head>
<body data-page="<?= esc($page) ?>">
<div id="app">
  <div id="sidebar">
    <div class="sb-head">
      <div class="sb-logo">⚡</div>
      <span class="sb-name">Admin Portal</span>
      <button class="sb-toggle" id="sidebarToggle">‹</button>
    </div>
    <nav class="sb-nav">
      <div class="sb-section">Main</div>
      <a class="nav-item<?= $page === 'dashboard' ? ' active' : '' ?>" href="?page=dashboard">
        <span class="nav-icon">📊</span>
        <span class="nav-label">Dashboard</span>
        <span class="nav-tooltip">Dashboard</span>
      </a>
      <?php if (in_array(($currentUser['role'] ?? ''), ['admin'])): ?>
        <a class="nav-item<?= $page === 'analytics' ? ' active' : '' ?>" href="?page=analytics">
          <span class="nav-icon">📈</span>
          <span class="nav-label">Analytics</span>
          <span class="nav-tooltip">Analytics & Reports</span>
        </a>
        <a class="nav-item<?= $page === 'legislative-admin' ? ' active' : '' ?>" href="?page=legislative-admin">
          <span class="nav-icon">🧾</span>
          <span class="nav-label">Legislative Records</span>
          <span class="nav-tooltip">Create and manage legislative drafts</span>
        </a>
        <a class="nav-item<?= $page === 'legislative-bills' && ($_GET['bill_status'] ?? '') !== 'pending' ? ' active' : '' ?>" href="?page=legislative-bills">
          <span class="nav-icon">🧾</span>
          <span class="nav-label">Resolutions / Ordinances</span>
          <?php $agendaSourceIds = array_flip(array_filter(array_column($data['agendas'] ?? [], 'source_legislative_id'))); ?>
          <?php $pendingBills = count(array_filter($data['legislatives'] ?? [], function (array $item) use ($agendaSourceIds): bool { $type = strtolower((string)($item['type'] ?? '')); return (str_contains($type, 'bill') || str_contains($type, 'resolution') || str_contains($type, 'ordinance')) && in_array($item['status'] ?? '', ['submitted_to_admin', 'approved'], true) && (($item['status'] ?? '') !== 'approved' || !isset($agendaSourceIds[$item['id'] ?? ''])); })); ?>
          <span class="nav-tooltip">Review legislative submissions</span>
        </a>
        <a class="nav-item<?= $page === 'legislative-bills' && ($_GET['bill_status'] ?? '') === 'pending' ? ' active' : '' ?>" href="?page=legislative-bills&bill_status=pending">
          <span class="nav-icon">⏳</span>
          <span class="nav-label">Pending Legislation</span>
          <?php if ($pendingBills > 0): ?><span class="nav-badge" style="background:#f59e0b"><?= $pendingBills ?></span><?php endif; ?>
          <span class="nav-tooltip">Review, approve, and schedule pending legislation</span>
        </a>
        <a class="nav-item<?= $page === 'legislative-audit-trail' ? ' active' : '' ?>" href="?page=legislative-audit-trail">
          <span class="nav-icon">📜</span>
          <span class="nav-label">Legislative Audit Trail</span>
          <span class="nav-tooltip">Review users, actions, versions, signatures, and timestamps</span>
        </a>
        <a class="nav-item<?= $page === 'users' ? ' active' : '' ?>" href="?page=users">
          <span class="nav-icon">👥</span>
          <span class="nav-label">Manage Users</span>
          <span class="nav-badge"><?= count($data['users']) ?></span>
          <span class="nav-tooltip">Manage Users</span>
        </a>
        <a class="nav-item<?= $page === 'session-control' ? ' active' : '' ?>" href="?page=session-control">
          <span class="nav-icon">🎛️</span>
          <span class="nav-label">Session Control</span>
          <span class="nav-tooltip">Session Control Panel</span>
        </a>
        <div class="sb-section">Events</div>
        <a class="nav-item<?= $page === 'sessions' ? ' active' : '' ?>" href="?page=sessions">
          <span class="nav-icon">📅</span>
          <span class="nav-label">Session Scheduling</span>
          <span class="nav-badge"><?= count($data['sessions']) ?></span>
          <span class="nav-tooltip">Sessions</span>
        </a>
        <a class="nav-item<?= $page === 'minutes-history' ? ' active' : '' ?>" href="?page=minutes-history">
          <span class="nav-icon">📝</span>
          <span class="nav-label">Minutes History</span>
          <span class="nav-tooltip">View sent session minutes</span>
        </a>
        <a class="nav-item<?= $page === 'agendas' ? ' active' : '' ?>" href="?page=agendas">
          <span class="nav-icon">📎</span>
          <span class="nav-label">Agenda Upload</span>
          <span class="nav-badge"><?= count($data['agendas']) ?></span>
          <span class="nav-tooltip">Agendas</span>
        </a>
        <a class="nav-item<?= $page === 'tablet' ? ' active' : '' ?>" href="?page=tablet">
          <span class="nav-icon">📱</span>
          <span class="nav-label">Tablet Session</span>
          <span class="nav-tooltip">Tablet Session</span>
        </a>
        <button class="nav-item" type="button" onclick="openTranscriptionPanel()">
          <span class="nav-icon">🎙️</span>
          <span class="nav-label">Voice to Text</span>
          <span class="nav-tooltip">Open Voice to Text</span>
        </button>
      <?php endif; ?>
      <?php if (($currentUser['role'] ?? '') === 'presiding officer'): ?>
        <div class="sb-section">Reviews</div>
        <a class="nav-item<?= $page === 'session-control' ? ' active' : '' ?>" href="?page=session-control">
          <span class="nav-icon">📝</span>
          <span class="nav-label">Minutes Approval</span>
          <span class="nav-tooltip">Review and sign session minutes</span>
        </a>
        <a class="nav-item<?= $page === 'proceeding-officer' ? ' active' : '' ?>" href="?page=proceeding-officer">
          <span class="nav-icon">✍️</span>
          <span class="nav-label">Agenda Approval</span>
          <?php $pendingCount = count(array_filter($data['agendas'] ?? [], fn($a) => ($a['sent_to_officer_id'] ?? '') === ($currentUser['id'] ?? '') && ($a['approval_status'] ?? '') === 'sent_to_officer')); ?>
          <?php if ($pendingCount > 0): ?>
            <span class="nav-badge" style="background:#ef4444"><?= $pendingCount ?></span>
          <?php endif; ?>
          <span class="nav-tooltip">Agenda Approval</span>
        </a>
        <a class="nav-item<?= $page === 'legislative-bills' ? ' active' : '' ?>" href="?page=legislative-bills&bill_status=all">
          <span class="nav-icon">🧾</span>
          <span class="nav-label">Resolutions / Ordinances</span>
          <span class="nav-tooltip">View and edit legislative matters</span>
        </a>
      <?php elseif (($currentUser['role'] ?? '') === 'ctrfb'): ?>
        <div class="sb-section">Reviews</div>
        <a class="nav-item<?= $page === 'ctrfb' ? ' active' : '' ?>" href="?page=ctrfb">
          <span class="nav-icon">✍️</span>
          <span class="nav-label">Agenda Review</span>
          <?php $pendingCount = count(array_filter($data['agendas'] ?? [], fn($a) => ($a['sent_to_officer_id'] ?? '') === ($currentUser['id'] ?? '') && ($a['approval_status'] ?? '') === 'sent_to_officer')); ?>
          <?php if ($pendingCount > 0): ?>
            <span class="nav-badge" style="background:#ef4444"><?= $pendingCount ?></span>
          <?php endif; ?>
          <span class="nav-tooltip">Agenda Review</span>
        </a>
        <a class="nav-item<?= $page === 'ctfrb-report' ? ' active' : '' ?>" href="?page=ctfrb-report">
          <span class="nav-icon">📊</span>
          <span class="nav-label">CTFRB Report</span>
          <span class="nav-tooltip">Manage CTFRB applications</span>
        </a>
      <?php elseif (in_array(($currentUser['role'] ?? ''), ['lce', 'mayor'], true)): ?>
        <div class="sb-section">LCE Review</div>
        <a class="nav-item<?= $page === 'lce' ? ' active' : '' ?>" href="?page=lce">
          <span class="nav-icon">🏛️</span>
          <span class="nav-label">LCE Legislative Review</span>
          <span class="nav-tooltip">Approve, veto, and download local legislative files</span>
        </a>
      <?php endif; ?>
      <?php if (!in_array(($currentUser['role'] ?? ''), ['admin', 'presiding officer', 'ctrfb', 'lce', 'mayor'])): ?>
        <a class="nav-item<?= $page === 'legislative-user' && !filter_var($_GET['history'] ?? false, FILTER_VALIDATE_BOOLEAN) ? ' active' : '' ?>" href="?page=legislative-user&create=1">
          <span class="nav-icon">🧾</span>
          <span class="nav-label">Legislative</span>
          <span class="nav-tooltip">Create bills, resolutions, and ordinances</span>
        </a>
        <a class="nav-item<?= $page === 'legislative-user' && filter_var($_GET['history'] ?? false, FILTER_VALIDATE_BOOLEAN) ? ' active' : '' ?>" href="?page=legislative-user&history=1">
          <span class="nav-icon">🕘</span>
          <span class="nav-label">My History</span>
          <span class="nav-tooltip">View and manage your legislative records</span>
        </a>
        <a class="nav-item<?= $page === 'legislative-bills' ? ' active' : '' ?>" href="?page=legislative-bills">
          <span class="nav-icon">🧾</span>
          <span class="nav-label">Resolutions / Ordinances</span>
          <span class="nav-tooltip">Create and view legislative matters</span>
        </a>
        <a class="nav-item<?= $page === 'legislative-audit-trail' ? ' active' : '' ?>" href="?page=legislative-audit-trail">
          <span class="nav-icon">📜</span>
          <span class="nav-label">Legislative Audit Trail</span>
          <span class="nav-tooltip">Review users, actions, versions, signatures, and timestamps</span>
        </a>
        <a class="nav-item<?= $page === 'ordinances' ? ' active' : '' ?>" href="?page=ordinances">
          <span class="nav-icon">📋</span>
          <span class="nav-label">Approved Bills</span>
          <span class="nav-tooltip">Approved Bills</span>
        </a>
        <?php if (str_contains(strtolower($currentUser['role'] ?? ''), 'council')): ?>
          <a class="nav-item<?= $page === 'minutes-history' ? ' active' : '' ?>" href="?page=minutes-history">
            <span class="nav-icon">📝</span>
            <span class="nav-label">Minutes History</span>
            <span class="nav-tooltip">View session minutes sent to you</span>
          </a>
          <a class="nav-item<?= $page === 'session-control' ? ' active' : '' ?>" href="?page=session-control">
            <span class="nav-icon">🎛️</span>
            <span class="nav-label">Session Control</span>
            <span class="nav-tooltip">Request attendance and join live session</span>
          </a>
          <a class="nav-item<?= $page === 'tablet' ? ' active' : '' ?>" href="?page=tablet">
            <span class="nav-icon">📱</span>
            <span class="nav-label">Tablet Session</span>
            <span class="nav-tooltip">Tablet Session</span>
          </a>
        <?php endif; ?>
      <?php endif; ?>
    </nav>
    <div class="sb-foot">
      <?php $__displayName = $currentUser['name'] ?? 'Guest'; $__displayRole = $currentUser['role'] ?? 'visitor'; ?>
      <div class="admin-card">
        <div class="avatar" style="background:linear-gradient(135deg,#f093fb,#f5576c)"><?= esc(mb_strtoupper(mb_substr($__displayName, 0, 1))) ?></div>
        <div class="admin-text">
          <div class="admin-name-txt"><?= esc($__displayName) ?></div>
          <div class="admin-role-txt"><?= esc(ucfirst($__displayRole)) ?></div>
        </div>
      </div>
    </div>
  </div>
  <button id="mobileNavBackdrop" class="mobile-nav-backdrop" type="button" aria-label="Close navigation"></button>
  <div id="main">
    <div class="topbar">
      <div class="topbar-left">
        <button class="btn btn-ghost mobile-nav-toggle" id="mobileSidebarToggle" type="button" aria-label="Open navigation" aria-controls="sidebar" aria-expanded="false">☰</button>
        <span class="page-title" id="pt"><?= esc($pageConfig['title']) ?></span>
        <span class="breadcrumb"><?= ucfirst($pageConfig['title']) ?></span>
      </div>
      <div class="topbar-right">
        <?php if ($pageConfig['search']): ?>
          <form class="search-box" method="get" action="index.php" style="display:flex" >
            <input type="hidden" name="page" value="<?= esc($page) ?>" />
            <span style="opacity:.4;font-size:13px">🔍</span>
            <input type="text" name="search" placeholder="Search…" value="<?= esc($search) ?>" />
          </form>
        <?php endif; ?>
        <button class="btn btn-ghost" type="button" id="themeToggle" aria-label="Toggle theme">☀️</button>
        <?php if ($pageConfig['action']): ?>
          <a class="btn btn-primary" href="?page=<?= esc($page) ?>&open=<?= esc($pageConfig['panel']) ?>">
            <span>＋</span><span><?= esc($pageConfig['actionLabel']) ?></span>
          </a>
        <?php endif; ?>
        <?php if (in_array($page, ['legislative-admin', 'legislative-user'], true)): ?>
          <a class="btn btn-secondary" href="?page=legislative-bills&open=<?= esc($pageConfig['panel']) ?>">
            <span>🧾</span><span>Resolutions / Ordinances</span>
          </a>
        <?php endif; ?>
        <?php if (!empty($currentUser)): ?>
          <div class="user-info" style="display:flex;align-items:center;gap:12px;margin-right:12px">
            <div class="user-name" style="font-weight:600"><?= esc($currentUser['name'] ?? 'User') ?></div>
            <div class="user-role" style="opacity:.6;font-size:13px"><?= esc(ucfirst($currentUser['role'] ?? 'user')) ?></div>
          </div>
          <?php if (!empty($notifications)): ?>
            <?php if (count($notifications) > 0): ?>
              <?php $unreadCount = count(array_filter($notifications, fn($n) => empty($n['read']))); ?>
              <div class="notification-wrapper">
                <button type="button" class="btn btn-secondary notification-toggle" id="notificationToggle" aria-label="Open notifications">
                  🔔
                  <?php if ($unreadCount > 0): ?>
                    <span class="notification-count"><?= $unreadCount ?></span>
                  <?php endif; ?>
                </button>
                <div class="notification-dropdown" id="notificationDropdown">
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px;border-bottom:1px solid #f3f4f6">
                      <div style="font-weight:600">Notifications</div>
                    </div>
                    <?php foreach (array_slice($notifications, 0, 8) as $note): ?>
                    <a class="notification-item<?= str_starts_with((string)($note['message'] ?? ''), 'Integrity alert:') ? ' integrity-notification' : '' ?>" href="<?= esc($note['url'] ?? 'index.php?page=dashboard') ?>">
                      <div class="notification-message"><?= esc($note['message']) ?></div>
                      <div class="notification-time"><?= esc(fmtDate($note['created_at'])) ?></div>
                    </a>
                  <?php endforeach; ?>
                  <a class="notification-item notification-view-all" href="index.php?page=notifications">View all notifications</a>
                </div>
              </div>
            <?php endif; ?>
          <?php endif; ?>
          <form method="post" action="index.php" style="margin:0">
            <input type="hidden" name="action" value="logout" />
            <button type="submit" class="btn btn-secondary">Logout</button>
          </form>
      <?php endif; ?>
      </div>
    </div>
    <div id="content">
      <?php if ($currentUser): ?><div id="legislative-integrity-monitor" data-endpoint="index.php" hidden></div><?php endif; ?>
      <?php if (!empty($flashes)): ?>
        <div class="toast-wrap" style="position:relative;gap:8px;margin-bottom:16px;display:flex;flex-direction:column;">
          <?php foreach ($flashes as $flash): ?>
            <div class="toast toast-<?= $flash['type'] === 'success' ? 'ok' : ($flash['type'] === 'error' ? 'err' : 'info') ?> show">
              <span><?= $flash['type'] === 'success' ? '✓' : ($flash['type'] === 'error' ? '✕' : 'ℹ') ?></span>
              <span><?= esc($flash['message']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php $viewPage = $page === 'legislative-audit-trail' ? 'legislative-ledger' : $page; include __DIR__ . '/' . $viewPage . '.php'; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/panels.php'; ?>
<div id="pageLoader" class="page-loader" role="status" aria-live="polite" aria-label="Loading">
  <div class="loader-ring"></div>
  <div class="loader-text">Loading</div>
</div>
<?php if (!empty($shouldDownloadSigningKey)): ?>
  <iframe src="index.php?download_signing_key=1" title="Signing key download" hidden></iframe>
<?php endif; ?>
<script src="public/assets/app.js"></script>
</body>
</html>
