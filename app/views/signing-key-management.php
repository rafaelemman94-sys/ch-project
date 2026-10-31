<?php
$signingKeys = $signingKeys ?? [];
$activeSigningPurposes = [];
foreach ($signingKeys as $signingKey) {
    if (!empty($signingKey['is_active'])) {
    $activeSigningPurposes[] = $signingKey['key_purpose'] ?? 'legislative';
    }
}
?>
<section class="page-section">
  <header class="section-heading">
    <div>
      <h2>Signing Key Management</h2>
      <p>Manage separate PKI keys for legislative documents and agenda approvals.</p>
    </div>
  </header>

  <section class="card">
    <div class="card-head"><div class="card-title">Generate or Rotate Signing Key</div></div>
    <div class="card-body">
      <form method="post" action="index.php?page=signing-key-management" class="form-grid signing-key-generator" id="signing-key-form">
        <input type="hidden" name="action" id="signing-key-action" value="generate_signing_key" />
        <div class="fg">
          <label for="signing-key-purpose">Signing key</label>
          <select id="signing-key-purpose" name="key_purpose" required>
            <option value="legislative">Legislative Signing Key</option>
            <option value="agenda">Agenda Signing Key</option>
          </select>
        </div>
        <div class="fg">
          <label for="signing-key-passphrase">Private key passphrase</label>
          <div class="password-field">
            <input id="signing-key-passphrase" type="password" name="key_passphrase" autocomplete="new-password" minlength="12" required />
            <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false" title="Show password">
                <span class="password-toggle-icon password-toggle-show" aria-hidden="true">👁</span>
                <span class="password-toggle-icon password-toggle-hide" aria-hidden="true">🙈</span>
            </button>
          </div>
        </div>
        <div class="fg">
          <label for="signing-key-passphrase-confirm">Confirm passphrase</label>
          <div class="password-field">
            <input id="signing-key-passphrase-confirm" type="password" name="key_passphrase_confirm" autocomplete="new-password" minlength="12" required />
            <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false" title="Show password">
                <span class="password-toggle-icon password-toggle-show" aria-hidden="true">👁</span>
                <span class="password-toggle-icon password-toggle-hide" aria-hidden="true">🙈</span>
            </button>
          </div>
        </div>
        <div class="form-actions">
          <button class="btn btn-primary" id="signing-key-submit" type="submit">Generate Key</button>
        </div>
      </form>
    </div>
  </section>

  <section class="card" style="margin-top:18px;">
    <div class="card-head"><div class="card-title">Public Key History</div></div>
    <div class="card-body signing-key-list">
      <?php if (!$signingKeys): ?>
        <div class="empty-state">No signing keys have been generated.</div>
      <?php endif; ?>
      <?php foreach ($signingKeys as $signingKey): ?>
        <article class="signing-key-card">
          <div class="signing-key-card-header">
            <strong class="signing-key-card-title"><?= esc(ucfirst((string)($signingKey['key_purpose'] ?? 'legislative'))) ?> · <?= esc($signingKey['key_name']) ?></strong>
            <span class="badge <?= !empty($signingKey['is_active']) ? 'badge-success' : 'badge-info' ?>"><?= !empty($signingKey['is_active']) ? 'Active' : 'Retired' ?></span>
          </div>
          <div class="field-note signing-key-meta">RSA-SHA256 · Created by <?= esc($signingKey['created_by_name'] ?? 'Administrator') ?> · <?= esc(fmtDateTime($signingKey['created_at'])) ?></div>
          <?php if (!empty($signingKey['retired_at'])): ?><div class="field-note">Retired <?= esc(fmtDateTime($signingKey['retired_at'])) ?></div><?php endif; ?>
          <code class="signing-key-fingerprint">SHA-256 fingerprint: <?= esc($signingKey['fingerprint']) ?></code>
          <div class="signing-key-actions">
          <form method="post" action="index.php?page=signing-key-management" class="signing-key-export-form">
            <input type="hidden" name="action" value="export_signing_key" />
            <input type="hidden" name="key_id" value="<?= esc($signingKey['id']) ?>" />
            <label>Key passphrase
              <div class="password-field">
                <input type="password" name="key_passphrase" autocomplete="current-password" required />
                <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false" title="Show password">
                  <span class="password-toggle-icon password-toggle-show" aria-hidden="true">👁</span>
                  <span class="password-toggle-icon password-toggle-hide" aria-hidden="true">🙈</span>
                </button>
              </div>
            </label>
            <button class="btn btn-secondary btn-xs" type="submit">Export Protected TXT</button>
          </form>
          <form method="post" action="index.php?page=signing-key-management" class="signing-key-delete-form" onsubmit="return confirm('Delete this key permanently? Existing signatures remain verifiable, but new signing will require another key.')">
            <input type="hidden" name="action" value="delete_signing_key" />
            <input type="hidden" name="key_id" value="<?= esc($signingKey['id']) ?>" />
            <button class="btn btn-danger btn-xs" type="submit">Delete Key</button>
          </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</section>
<script>
const activeSigningPurposes = <?= json_encode(array_values(array_unique($activeSigningPurposes))) ?>;
const signingPurposeSelect = document.getElementById('signing-key-purpose');
const signingActionInput = document.getElementById('signing-key-action');
const signingSubmitButton = document.getElementById('signing-key-submit');

function updateSigningKeyAction() {
  const hasActiveKey = activeSigningPurposes.includes(signingPurposeSelect.value);
  signingActionInput.value = hasActiveKey ? 'rotate_signing_key' : 'generate_signing_key';
  signingSubmitButton.textContent = hasActiveKey ? 'Rotate Key' : 'Generate Key';
}

signingPurposeSelect.addEventListener('change', updateSigningKeyAction);
updateSigningKeyAction();
</script>