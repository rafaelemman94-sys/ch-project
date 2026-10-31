<?php
/**
 * @var bool $needsSetup
 * @var array $flashes
 */
if (!function_exists('esc')) {
  function esc($value): string
  {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  }
}

// Put your logo image in public/assets and update this path if needed.
$logoWebPaths = [
  'public/assets/logo-1-transparent.png',
  'public/assets/logo-2-transparent.png',
];

$logoPaths = [];
foreach ($logoWebPaths as $path) {
  $fsPath = dirname(__DIR__, 2) . '/' . ltrim($path, '/');
  if (is_file($fsPath)) {
    $logoPaths[] = $path;
  }
}

$hasLogoImages = !empty($logoPaths);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>E-Legis Paperless Legislative Workflow and Document Management</title>
<link rel="stylesheet" href="public/assets/style.css?v=<?= (int)@filemtime(dirname(__DIR__, 2) . '/public/assets/style.css') ?>" />
<script src="public/assets/app.js"></script>
</head>
<body class="login-page">
  <div class="login-backdrop" aria-hidden="true">
    <img src="public/assets/login-background.jpg.png" alt="" />
  </div>
  <div class="login-shell">
    <div class="login-panel">
      <div class="login-brand">
        <div class="login-logo-row">
          <?php if ($hasLogoImages): ?>
            <?php foreach ($logoPaths as $index => $logoPath): ?>
            <div class="login-logo">
              <img src="<?= esc($logoPath) ?>" alt="E-Legis logo <?= $index + 1 ?>" class="login-logo-image" />
            </div>
            <?php endforeach; ?>
          <?php else: ?>
          <div class="login-logo">
            <span class="login-logo-mark" aria-hidden="true">🏛</span>
          </div>
          <?php endif; ?>
        </div>
        <h1><?= $needsSetup ? 'Create Administrator Account' : 'E-Legis ' ?></h1>
        <p><?= $needsSetup ? 'You must create the first administrator account before signing in.' : 'Paperless Legislative Workflow and Document Management' ?></p>
      </div>

      <?php if (!empty($flashes)): ?>
        <div class="toast-wrap login-toast-wrap" style="margin-bottom:16px;display:flex;flex-direction:column;gap:8px;">
          <?php foreach ($flashes as $flash): ?>
            <div class="toast toast-<?= $flash['type'] === 'success' ? 'ok' : ($flash['type'] === 'error' ? 'err' : 'info') ?> show">
              <span><?= esc($flash['message']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($needsSetup): ?>
        <form class="login-form" method="post" action="index.php">
          <input type="hidden" name="action" value="create_first_admin" />
          <label for="name">Name</label>
          <input id="name" type="text" name="name" placeholder="Administrator name" autocomplete="name" required />
          <label for="email">Email</label>
          <input id="email" type="email" name="email" placeholder="admin@example.com" autocomplete="email" required autofocus />
          <label for="password">Password</label>
          <div class="password-field">
            <input id="password" type="password" name="password" placeholder="Create a secure password" autocomplete="new-password" required />
            <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false">
              <span class="password-toggle-icon password-toggle-show" aria-hidden="true">👁</span>
              <span class="password-toggle-icon password-toggle-hide" aria-hidden="true">🙈</span>
            </button>
          </div>
          <button type="submit" class="btn btn-primary">Create Admin Account</button>
        </form>
      <?php else: ?>
        <form class="login-form" method="post" action="index.php">
          <input type="hidden" name="action" value="login" />
          <label for="email">Email</label>
          <input id="email" type="email" name="email" placeholder="admin@example.com" autocomplete="email" required autofocus />
          <label for="password">Password</label>
          <div class="password-field">
            <input id="password" type="password" name="password" placeholder="Password" autocomplete="current-password" required />
            <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false">
              <span class="password-toggle-icon password-toggle-show" aria-hidden="true">👁</span>
              <span class="password-toggle-icon password-toggle-hide" aria-hidden="true">🙈</span>
            </button>
          </div>
          <label for="mode">Login As</label>
          <select id="mode" name="mode">
            <option value="admin">Admin</option>
            <option value="presiding officer">Presiding Officer</option>
            <option value="ctrfb">CTRFB</option>
            <option value="council members">Council Members</option>
            <option value="lce">Local Chief Executive / Mayor</option>
            <option value="user">User</option>
          </select>
          <div class="field-note">Select your role to sign in.</div>
          <button type="submit" class="btn btn-primary">Sign In</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <div id="pageLoader" class="page-loader" role="status" aria-live="polite" aria-label="Loading">
    <div class="loader-ring"></div>
    <div class="loader-text">Loading</div>
  </div>
</body>
</html>
