<?php
/**
 * @var string $page
 * @var array $currentUser
 * @var array $legislatives
 * @var array|null $editItem
 * @var string|null $search
 * @var array $flashes
 */
if (!function_exists('esc')) {
  function esc($value): string
  {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  }
}

$councilRecipients = array_values(array_filter($data['users'] ?? [], fn ($user) => ($user['role'] ?? '') === 'council members' && ($user['status'] ?? 'active') === 'active' && ($user['id'] ?? '') !== ($currentUser['id'] ?? '')));
$isCouncilView = ($currentUser['role'] ?? '') === 'council members';
$myHistory = $myLegislatives ?? array_values(array_filter($legislatives, fn (array $item): bool => ($item['createdById'] ?? '') === ($currentUser['id'] ?? '')));
$historyOnly = filter_var($_GET['history'] ?? false, FILTER_VALIDATE_BOOLEAN);
$createOnly = filter_var($_GET['create'] ?? false, FILTER_VALIDATE_BOOLEAN);
?>
<div class="page-section<?= $isCouncilView ? ' council-create-page' : '' ?>">
  <div class="section-heading">
    <h2><?= $historyOnly ? 'My Legislative History' : ($isCouncilView || $createOnly ? 'Create Resolution / Ordinance' : 'Legislative Proposals') ?></h2>
    <?php if ($historyOnly): ?><a class="btn btn-ghost" href="?page=legislative-user&create=1">← Back to Legislative</a><?php endif; ?>
    <?php if (!$isCouncilView && !$historyOnly && !$createOnly): ?><p>Submit or review legislative proposals from the user side. Administrators can manage all records. All users can view approved proposals and edit legislative items created by others.</p><?php endif; ?>
    <?php if (!$isCouncilView && !$historyOnly && !$createOnly): ?>
    <div class="section-actions" style="margin-top:12px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:flex-start;">
      <form method="get" action="index.php" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <input type="hidden" name="page" value="legislative-user" />
        <input type="text" name="hash_search" placeholder="Search control number or SHA-256" value="<?= esc($hashSearch ?? '') ?>" style="max-width:320px;min-width:220px;" />
        <button class="btn btn-secondary" type="submit">🔍 Search hash</button>
        <?php if (($hashSearch ?? '') !== ''): ?>
          <a class="btn btn-ghost" href="?page=legislative-user">Clear</a>
        <?php endif; ?>
      </form>
    </div>
    <div class="section-actions" style="margin-top:14px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:flex-start;">
      <button class="btn btn-primary" type="button" onclick="openAIDraftPanel()">✨ AI Draft Assistant</button>
    </div>
    <div class="filter-tabs" style="margin-top:18px;display:flex;flex-wrap:wrap;gap:10px;">
      <?php foreach (['all' => 'All', 'approved' => 'Approved', 'mine' => 'My Proposals'] as $value => $label): ?>
        <a class="btn btn-ghost<?= ($statusFilter ?? 'all') === $value ? ' btn-active' : '' ?>" href="?page=legislative-user&status=<?= esc($value) ?>">
          <?= esc($label) ?>
          <?php if ($value === 'approved'): ?> (<?= esc($statusCounts['approved'] ?? 0) ?>)<?php endif; ?>
          <?php if ($value === 'mine'): ?> (<?= esc($statusCounts['mine'] ?? 0) ?>)<?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
    <?php if (in_array(($currentUser['role'] ?? ''), ['admin'])): ?>
      <div class="section-actions" style="margin-top:12px;">
        <a class="btn btn-secondary" href="?page=legislative-admin">Manage Records</a>
      </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>

  <?php if ($historyOnly): ?>
  <section class="my-legislative-history" id="my-history" aria-labelledby="my-history-title">
    <div class="history-section-heading">
      <div>
        <h3 id="my-history-title">My legislative history</h3>
        <p>Review, edit, or remove proposals you created.</p>
      </div>
      <span class="history-count"><?= count($myHistory) ?> record<?= count($myHistory) === 1 ? '' : 's' ?></span>
    </div>
    <?php if (empty($myHistory)): ?>
      <div class="history-empty">You have not created any legislative records yet.</div>
    <?php else: ?>
      <div class="my-history-list">
        <?php foreach ($myHistory as $historyItem): ?>
          <article class="my-history-item">
            <div class="my-history-copy">
              <strong><?= esc($historyItem['title'] ?? 'Untitled record') ?></strong>
              <span><?= esc($historyItem['type'] ?? 'Legislative record') ?> · <?= esc(fmtDateTime($historyItem['updatedAt'] ?? $historyItem['createdAt'] ?? '')) ?></span>
            </div>
            <span class="badge <?= badgeClass($historyItem['status'] ?? 'draft') ?>"><?= esc(ucfirst(str_replace('_', ' ', $historyItem['status'] ?? 'draft'))) ?></span>
            <div class="my-history-actions">
              <a class="link" href="?page=legislative-user&edit=<?= esc($historyItem['id'] ?? '') ?>&type=legislative">Edit</a>
              <form method="post" action="index.php?page=legislative-user" style="display:inline">
                <input type="hidden" name="action" value="delete_item" />
                <input type="hidden" name="item_type" value="legislative" />
                <input type="hidden" name="item_id" value="<?= esc($historyItem['id'] ?? '') ?>" />
                <button class="link link-danger" type="submit" onclick="return confirm('Delete this legislative record permanently?')">Delete</button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if (!$historyOnly): ?>
  <div class="legislative-grid">
    <?php if (!$isCouncilView && !$createOnly): ?>
    <div class="legislative-list">
      <div class="list-header">
        <span>Title</span>
        <span>Type</span>
        <span>Status</span>
        <span>Owner</span>
        <span>Created</span>
        <span></span>
      </div>
      <?php if (empty($legislatives)): ?>
        <div class="empty-state">No proposals found yet. Use the form to submit one.</div>
      <?php else: ?>
        <?php foreach ($legislatives as $item): ?>
          <div class="list-row">
            <div>
              <?= esc($item['title']) ?>
              <?php if (!empty($item['lastModifiedByName']) && ($item['lastModifiedByName'] !== ($item['createdByName'] ?? ''))): ?>
                <div class="info-note">AI Alert: This document was altered by <?= esc($item['lastModifiedByName']) ?>.</div>
              <?php endif; ?>
            </div>
            <div><?= esc($item['type']) ?></div>
            <div>
              <span class="badge <?= badgeClass($item['status']) ?>"><?= esc(ucfirst(str_replace('_', ' ', $item['status']))) ?></span>
              <?php if (($item['createdById'] ?? '') === ($currentUser['id'] ?? '') && in_array(($item['status'] ?? ''), ['submitted_to_admin', 'agenda_created'], true)): ?>
                <div class="field-note" style="margin-top:5px;color:#2563eb">
                  <?= ($item['status'] ?? '') === 'agenda_created' ? 'Added to an agenda' : 'Waiting for admin agenda preparation' ?>
                </div>
              <?php endif; ?>
            </div>
            <div><?= esc($item['createdByName'] ?? 'Unknown') ?></div>
            <div class="created-cell">
              <?= esc(fmtDateTime($item['createdAt'] ?? $item['updatedAt'] ?? '')) ?>
            </div>
            <div>
              <?php if (($item['createdById'] ?? '') === ($currentUser['id'] ?? '') || ($currentUser['role'] ?? '') === 'admin'): ?>
                <a class="link" href="?page=legislative-user&edit=<?= esc($item['id']) ?>&type=legislative">Edit</a>
                <?php if (($currentUser['role'] ?? '') === 'council members' && ($item['createdById'] ?? '') === ($currentUser['id'] ?? '')): ?>
                  <?php if (in_array(($item['status'] ?? ''), ['submitted_to_admin', 'agenda_created'], true)): ?>
                    <span class="link" style="color:#64748b">✓ Submitted to Admin</span>
                  <?php else: ?>
                    <form method="post" action="index.php?page=legislative-user" style="display:inline">
                      <input type="hidden" name="action" value="submit_legislative_to_admin" />
                      <input type="hidden" name="legislative_id" value="<?= esc($item['id']) ?>" />
                      <button class="link" type="submit" style="border:0;background:none;padding:0;cursor:pointer;color:#2563eb" onclick="return confirm('Submit this document to the admin for agenda preparation?')">📨 Submit to Admin</button>
                    </form>
                  <?php endif; ?>
                  <a class="link" href="#" onclick="openLegislativeSendPanel('<?= esc($item['id']) ?>', '<?= esc($item['title']) ?>');return false;">📤 Send to Council</a>
                <?php endif; ?>
              <?php endif; ?>
              <span class="link link-success" style="color:#059669;font-weight:600">✓ Approved</span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="legislative-form-card">
      <h3><?= $editItem ? 'Edit Legislative Matter' : 'Create Resolution / Ordinance' ?></h3>
      <div class="field-note" style="margin-bottom:10px;">Timestamp: <?= esc(fmtDateTime($editItem['createdAt'] ?? $editItem['updatedAt'] ?? date(DATE_ATOM))) ?></div>
      <?php if (!$isCouncilView && $editItem && !empty($editItem['createdByName']) && ($editItem['createdById'] ?? '') !== ($currentUser['id'] ?? '')): ?>
        <div class="info-note">AI Alert: this legislative item was originally created by <?= esc($editItem['createdByName']) ?> and is being edited by <?= esc($currentUser['name'] ?? 'you') ?>.</div>
      <?php endif; ?>
      <?php if ($editItem): ?>
        <div class="info-note">
          Control number <?= esc($editItem['control_number'] ?? '') ?> · <?= esc($editItem['workflow_status'] ?? 'Received') ?> · Assigned to <?= esc($editItem['assigned_role'] ?? 'Secretariat') ?>
          <br>Origin: <?= esc($editItem['origin'] ?? 'Not recorded') ?> · Office: <?= esc($editItem['originating_office'] ?? 'Not recorded') ?>
          <?php if (!empty($editItem['signed_hash'])): ?><br>Signed SHA-256: <?= esc($editItem['signed_hash']) ?><?php endif; ?>
        </div>
        <?php foreach (($legislativeVersions ?? []) as $version): ?>
          <div class="field-note">v<?= (int)$version['version_number'] ?> · <?= esc($version['status']) ?> · SHA-256 <?= esc($version['document_hash']) ?> · <?= esc($version['created_by_name'] ?? 'System') ?> · <?= esc(fmtDateTime($version['created_at'])) ?></div>
        <?php endforeach; ?>
        <?php foreach (($legislativeAudit ?? []) as $event): ?>
          <div class="field-note"><?= esc($event['event_type']) ?> · <?= esc($event['user_name'] ?? 'System') ?> · <?= esc(fmtDateTime($event['created_at'])) ?></div>
        <?php endforeach; ?>
      <?php endif; ?>
      <?php if (empty($editItem['locked_at'])): ?>
      <form method="post" action="index.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_legislative" />
        <input type="hidden" name="id" value="<?= esc($editItem['id'] ?? '') ?>" />
        <label>Title</label>
        <input type="text" name="title" value="<?= esc($editItem['title'] ?? '') ?>" placeholder="Leave blank to detect from the document" />
        <label>Author</label>
        <input type="text" name="author_name" value="<?= esc($editItem['author_name'] ?? '') ?>" placeholder="Optional author" />
        <label>Sponsor</label>
        <input type="text" name="sponsor_name" value="<?= esc($editItem['sponsor_name'] ?? '') ?>" placeholder="Optional sponsor" />
        <label>Type</label>
        <select name="type" data-legislative-type>
          <option value="">Auto detect from title and document</option>
          <option value="Bill"<?= ($editItem['type'] ?? '') === 'Bill' ? ' selected' : '' ?>>Bill</option>
          <option value="Resolution"<?= ($editItem['type'] ?? '') === 'Resolution' ? ' selected' : '' ?>>Resolution</option>
          <option value="Ordinance"<?= ($editItem['type'] ?? '') === 'Ordinance' ? ' selected' : '' ?>>Ordinance</option>
        </select>
        <label>Status</label>
        <select name="status">
          <option value="draft"<?= isset($editItem['status']) && $editItem['status'] === 'draft' ? ' selected' : '' ?>>Draft</option>
          <option value="review"<?= isset($editItem['status']) && $editItem['status'] === 'review' ? ' selected' : '' ?>>Review</option>
          <option value="approved"<?= isset($editItem['status']) && $editItem['status'] === 'approved' ? ' selected' : '' ?>>Approved</option>
        </select>
        <?php if (!$editItem): ?>
          <label>Origin</label>
          <input type="text" name="origin" placeholder="Submitting office or originating body" />
          <label>Originating Office</label>
          <input type="text" name="originating_office" value="Secretariat" required />
        <?php endif; ?>
        <label>Document Content</label>
        <div class="legislative-editor-toolbar">
          <button type="button" onclick="formatLegislative('bold')"><strong>B</strong></button>
          <button type="button" onclick="formatLegislative('italic')"><em>I</em></button>
          <button type="button" onclick="formatLegislative('underline')"><u>U</u></button>
          <button type="button" onclick="formatLegislative('insertUnorderedList')">•</button>
          <button type="button" onclick="formatLegislative('insertOrderedList')">1.</button>
        </div>
        <?php $editorContent = \App\Services\LegislativeContentFormatter::toSafeHtml((string)($editItem['content'] ?? '')); ?>
        <div id="legislative-editor" class="legislative-editor legislative-clean-content" contenteditable="true"><?= $editorContent ?></div>
        <textarea id="legislative-content" name="content" hidden><?= esc($editItem['content'] ?? '') ?></textarea>
        <label>Optional Attachment</label>
        <input id="legislative-attachment-input" type="file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.rtf,.jpg,.jpeg,.png" />
        <div id="legislative-attachment-hash-preview" class="field-note" role="status" aria-live="polite"></div>
        <button id="import-legislative-text" class="btn btn-ghost" type="button" onclick="importLegislativeText(this)">Import selected file into editor</button>
        <div id="legislative-import-status" class="field-note" role="status" aria-live="polite"></div>
        <?php if (!empty($editItem['file_name'])): ?>
          <div class="field-note">Current attachment: <a class="link" href="download.php?file=<?= esc($editItem['file_path'] ?? '') ?>&inline=1" target="_blank" rel="noopener">View in system</a></div>
          <div class="field-note">SHA-256: <code><?= esc($editItem['fileRevisionHash'] ?? 'Not recorded') ?></code></div>
        <?php else: ?>
          <div class="field-note">Optional attachment for this proposal.</div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary"><?= $editItem ? 'Update Legislative Matter' : 'Submit Resolution / Ordinance' ?></button>
      </form>
      <?php else: ?>
        <div class="info-note">This signed version is locked. Its previous versions and audit events remain available in Digital Records.</div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<div class="overlay" id="leg-send-overlay" onclick="closeLegislativeSendPanel()"></div>
<div class="slide-panel" id="leg-send-panel">
  <div class="panel-head">
    <div><div class="panel-title">Send Legislative Draft</div><div class="panel-subtitle" id="leg-send-subtitle"></div></div>
    <button class="panel-close" type="button" onclick="closeLegislativeSendPanel()">✕</button>
  </div>
  <form class="panel-body" method="post" action="index.php?page=legislative-user" id="leg-send-form">
    <input type="hidden" name="action" value="send_legislative_to_recipients" />
    <input type="hidden" name="legislative_id" value="" id="leg-send-legislative-id" />
    <div class="fg">
      <label>Choose Council Members</label>
      <div style="display:grid;gap:8px;max-height:280px;overflow:auto;padding:12px;border:1px solid #e5e7eb;border-radius:10px;background:#fff">
        <?php if (empty($councilRecipients)): ?>
          <div class="empty-state">No active council members available for sending.</div>
        <?php else: ?>
          <?php foreach ($councilRecipients as $recipient): ?>
            <label style="display:flex;align-items:center;gap:10px">
              <input type="checkbox" name="recipient_ids[]" value="<?= esc($recipient['id']) ?>" />
              <span><?= esc($recipient['name'] ?? 'Council Member') ?> <span style="opacity:.65">(<?= esc($recipient['email'] ?? 'no email') ?>)</span></span>
            </label>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </form>
  <div class="panel-foot">
    <button class="btn btn-secondary" type="button" onclick="closeLegislativeSendPanel()">Cancel</button>
    <button class="btn btn-primary" type="submit" form="leg-send-form" onclick="return validateLegislativeRecipients()">📨 Send to Selected Council Members</button>
  </div>
</div>

<script>
document.getElementById('legislative-attachment-input')?.addEventListener('change', async (event) => {
  const output = document.getElementById('legislative-attachment-hash-preview');
  const file = event.currentTarget.files?.[0];
  if (!output) return;
  if (!file) {
    output.textContent = '';
    return;
  }
  if (!globalThis.crypto?.subtle) {
    output.textContent = 'SHA-256 preview is unavailable in this browser.';
    return;
  }
  output.textContent = 'Calculating SHA-256...';
  const digest = await crypto.subtle.digest('SHA-256', await file.arrayBuffer());
  const hash = Array.from(new Uint8Array(digest), (byte) => byte.toString(16).padStart(2, '0')).join('');
  output.textContent = `Selected file SHA-256: ${hash}`;
});

function openLegislativeSendPanel(legislativeId, legislativeTitle) {
  var idField = document.getElementById('leg-send-legislative-id');
  var subtitle = document.getElementById('leg-send-subtitle');
  if (idField) idField.value = legislativeId;
  if (subtitle) subtitle.textContent = 'Send "' + legislativeTitle + '" to selected council members';
  document.querySelectorAll('#leg-send-form input[type="checkbox"]').forEach(function (cb) { cb.checked = false; });
  document.getElementById('leg-send-overlay').classList.add('open');
  document.getElementById('leg-send-panel').classList.add('open');
}

function closeLegislativeSendPanel() {
  document.getElementById('leg-send-overlay').classList.remove('open');
  document.getElementById('leg-send-panel').classList.remove('open');
}

function validateLegislativeRecipients() {
  var checked = document.querySelectorAll('#leg-send-form input[type="checkbox"]:checked');
  if (!checked.length) {
    alert('Choose at least one council member.');
    return false;
  }
  return confirm('Send this legislative draft to the selected council member(s)?');
}

function syncLegislativeContent() {
  var editor = document.getElementById('legislative-editor');
  var content = document.getElementById('legislative-content');
  if (editor && content) content.value = editor.innerHTML.trim();
}

function formatLegislative(command) {
  document.getElementById('legislative-editor')?.focus();
  document.execCommand(command, false, null);
  syncLegislativeContent();
}

function downloadLegislativeContent(button) {
  var uploadedFile = button?.dataset.uploadedFile || '';
  if (uploadedFile) {
    window.location.href = 'download.php?file=' + encodeURIComponent(uploadedFile);
    return;
  }
  var fileInput = document.querySelector('input[name="file"]');
  if (fileInput?.files?.length) {
    syncLegislativeContent();
    var form = button.closest('form');
    var exportField = document.createElement('input');
    exportField.type = 'hidden';
    exportField.name = 'export_after_save';
    exportField.value = '1';
    form.appendChild(exportField);
    form.submit();
    return;
  }
  var title = document.querySelector('input[name="title"]')?.value.trim() || 'legislative-bill';
  syncLegislativeContent();
  var content = document.getElementById('legislative-content');
  var payload = content ? content.value.trim() : '';
  if (payload === '') {
    alert('Enter document content before downloading.');
    return;
  }
  var params = new URLSearchParams({
    docx: '1',
    legislative_id: document.querySelector('input[name="id"]')?.value || '',
    title: title,
    type: document.querySelector('select[name="type"]')?.value || '',
    status: document.querySelector('select[name="status"]')?.value || '',
    content: payload
  });
  window.location.href = 'download.php?' + params.toString();
}

function importLegislativeText(button) {
  var fileInput = document.querySelector('input[name="file"]');
  if (!fileInput?.files?.length) {
    alert('Choose a file first.');
    return;
  }
  var status = document.getElementById('legislative-import-status');
  var originalLabel = button.textContent;
  var formData = new FormData();
  formData.append('action', 'import_legislative_text');
  formData.append('file', fileInput.files[0]);
  button.disabled = true;
  button.textContent = 'Importing...';
  if (status) status.textContent = 'Reading the selected file...';
  fetch('index.php', { method: 'POST', body: formData, credentials: 'same-origin' })
    .then(function (response) { return response.json(); })
    .then(function (result) {
      if (!result.success) throw new Error(result.error || 'Unable to import the file.');
      var editor = document.getElementById('legislative-editor');
      if (editor) editor.innerHTML = result.content;
      syncLegislativeContent();
      detectLegislativeType();
      if (status) status.textContent = 'Text imported into the editor. Review it before saving.';
    })
    .catch(function (error) {
      if (status) status.textContent = error.message;
      else alert(error.message);
    })
    .finally(function () {
      button.disabled = false;
      button.textContent = originalLabel;
    });
}

document.querySelector('input[name="file"]')?.addEventListener('change', function () {
  var importButton = document.getElementById('import-legislative-text');
  if (this.files?.length && importButton) importLegislativeText(importButton);
});

function detectLegislativeType() {
  var typeField = document.querySelector('[data-legislative-type]');
  if (!typeField || typeField.dataset.manual === 'true') return;
  var source = [
    document.querySelector('input[name="title"]')?.value || '',
    document.getElementById('legislative-content')?.value || ''
  ].join(' ').toLowerCase();
  if (/\b(resolution|resolved|resolves)\b/.test(source)) typeField.value = 'Resolution';
  else if (/\b(bill|act|enact|enacted)\b/.test(source)) typeField.value = 'Bill';
}

document.querySelector('[data-legislative-type]')?.addEventListener('change', function () {
  this.dataset.manual = 'true';
});
['input[name="title"]', '#legislative-editor'].forEach(function (selector) {
  document.querySelector(selector)?.addEventListener('input', detectLegislativeType);
});
document.querySelector('form[action="index.php?page=legislative-user"]')?.addEventListener('submit', syncLegislativeContent);
</script>
