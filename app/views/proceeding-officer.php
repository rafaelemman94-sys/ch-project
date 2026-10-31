<?php
/**
 * @var array $agendas
 * @var array $currentUser
 * @var array $data
 * @var string|null $openPanel
 * @var array|null $editItem
 */

// Helper function to check if current user is a recipient
function isAgendaRecipient($agenda, $currentUserId) {
  // Check for CTRFB review stage (original flow)
  $recipientIds = $agenda['sent_to_officer_id'] ?? '';
  if (!empty($recipientIds)) {
    $decoded = json_decode($recipientIds, true);
    if (is_array($decoded)) {
      if (in_array($currentUserId, $decoded, true)) return true;
    } elseif ($recipientIds === $currentUserId) {
      return true;
    }
  }
  
  // Check for Presiding Officer review stage (new multi-stage flow)
  $proceedingIds = $agenda['sent_to_proceeding_id'] ?? '';
  if (!empty($proceedingIds)) {
    $decoded = json_decode($proceedingIds, true);
    if (is_array($decoded)) {
      if (in_array($currentUserId, $decoded, true)) return true;
    } elseif ($proceedingIds === $currentUserId) {
      return true;
    }
  }
  
  return false;
}

$userAgendas = array_filter($agendas ?? [], fn($a) => isAgendaRecipient($a, $currentUser['id'] ?? ''));
?>

<div id="pg-officer" class="page active">
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:24px">
    <div class="stat-card">
      <div class="stat-val"><?= count(array_filter($userAgendas ?? [], fn($a) => in_array($a['approval_status'] ?? '', ['sent_to_officer', 'awaiting_proceeding_review']))) ?></div>
      <div class="stat-lbl">📋 Pending Review</div>
    </div>
    <div class="stat-card">
      <div class="stat-val"><?= count(array_filter($userAgendas ?? [], fn($a) => ($a['approval_status'] ?? '') === 'approved')) ?></div>
      <div class="stat-lbl">✅ Approved</div>
    </div>
    <div class="stat-card">
      <div class="stat-val"><?= count(array_filter($userAgendas ?? [], fn($a) => ($a['approval_status'] ?? '') === 'rejected')) ?></div>
      <div class="stat-lbl">❌ Rejected</div>
    </div>
  </div>

  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Agenda Title</th>
          <th>File</th>
          <th>Sent Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $officerAgendas = array_values(array_reverse($userAgendas));
        
        if (count($officerAgendas) === 0):
        ?>
          <tr>
            <td colspan="5" style="text-align:center;padding:32px;color:#9ca3af">No agendas sent for approval yet</td>
          </tr>
        <?php else: ?>
          <?php foreach ($officerAgendas as $agenda): ?>
            <tr>
              <td><strong><?= esc($agenda['title'] ?? '') ?></strong></td>
              <td style="font-size:12px;color:#6b7280">
                <?= esc($agenda['file_name'] ?? '') ?><br>
                <span style="color:#9ca3af"><?= fmtSize($agenda['file_size'] ?? 0) ?></span>
              </td>
              <td style="font-size:12px"><?= fmtDate($agenda['sent_to_officer_date'] ?? '') ?></td>
              <td>
                <span class="badge <?= badgeClass($agenda['approval_status'] ?? 'pending') ?>">
                  <?php
                    $statusLabel = $agenda['approval_status'] ?? 'pending';
                    if ($statusLabel === 'sent_to_officer' || $statusLabel === 'awaiting_proceeding_review') echo '⏳ Pending Review';
                    elseif ($statusLabel === 'approved') echo '✅ Approved';
                    elseif ($statusLabel === 'rejected') echo '❌ Rejected';
                    else echo ucfirst(str_replace('_', ' ', $statusLabel));
                  ?>
                </span>
              </td>
              <td>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                  <?php if (!empty($agenda['file_path'])): ?>
                    <a href="download.php?file=<?= esc($agenda['file_path']) ?>" class="btn btn-ghost btn-xs">⬇️ Download</a>
                  <?php endif; ?>
                  <?php if (!empty($agenda['pki_signatures'][0])): ?>
                    <a href="index.php?verify_agenda=<?= rawurlencode((string)$agenda['id']) ?>" class="btn btn-ghost btn-xs" target="_blank" rel="noopener">Verify PKI signature · v<?= (int)$agenda['pki_signatures'][0]['version_number'] ?></a>
                  <?php endif; ?>
                  
                  <?php if (in_array(($agenda['approval_status'] ?? ''), ['sent_to_officer', 'awaiting_proceeding_review'], true)): ?>
                    <button class="btn btn-primary btn-xs" onclick="openApprovalPanel('<?= esc($agenda['id']) ?>')">✍️ Review & Approve</button>
                  <?php else: ?>
                    <button class="btn btn-secondary btn-xs" onclick="openViewPanel('<?= esc($agenda['id']) ?>')">👁️ View Details</button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Approval Panel -->
<div class="overlay<?= $openPanel === 'approval' ? ' open' : '' ?>" onclick="closePanel()"></div>
<div class="slide-panel<?= $openPanel === 'approval' ? ' open' : '' ?>" id="pnl-approval">
  <div class="panel-head">
    <div>
      <div class="panel-title">Review & Approve Agenda</div>
      <div class="panel-subtitle">Digitally sign the agenda and add comments</div>
    </div>
    <button class="panel-close" type="button" onclick="closePanel()">✕</button>
  </div>
  
  <form class="panel-body" method="post" action="index.php?page=proceeding-officer" id="approval-form" enctype="multipart/form-data" autocomplete="off">
    <input type="hidden" name="action" value="approve_agenda" />
    <input type="hidden" name="agenda_id" value="" id="approval-agenda-id" />

    <div class="fg" id="approval-file-preview" style="display:none">
      <label style="font-weight:500">Review File</label>
      <div id="approval-file-name" style="font-size:12px;color:#6b7280;margin:6px 0"></div>
      <div id="approval-file-frame" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;background:#fff;min-height:120px"></div>
      <a id="approval-file-download" class="btn btn-ghost btn-xs" href="#" target="_blank" style="margin-top:8px">⬇️ Open / Download File</a>
    </div>

    <div class="fg">
      <label for="agenda-pki-key-file">Signing key TXT file</label>
      <input id="agenda-pki-key-file" type="file" name="signing_key_file" accept=".txt,text/plain" required />
      <div class="field-note">Upload the TXT file downloaded when you first signed in. Keep it private; anyone with this file can sign as you.</div>
    </div>

    <div class="fg">
      <label>Comments (Optional)</label>
      <textarea name="comments" id="approval-comments" placeholder="Add any comments or conditions for approval…" style="min-height:120px"></textarea>
    </div>

    <div class="fg" style="display:flex;gap:12px">
      <button type="button" class="btn btn-danger" onclick="rejectWithReason()" style="flex:1">❌ Reject</button>
      <button type="submit" class="btn btn-primary" style="flex:1" onclick="return setApprovalStatus('approved')">✅ Approve & Sign</button>
    </div>
  </form>
</div>

<!-- View Details Panel -->
<div class="overlay<?= $openPanel === 'view-details' ? ' open' : '' ?>" onclick="closePanel()"></div>
<div class="slide-panel<?= $openPanel === 'view-details' ? ' open' : '' ?>" id="pnl-view-details">
  <div class="panel-head">
    <div>
      <div class="panel-title">Agenda Details</div>
      <div class="panel-subtitle" id="view-agenda-title"></div>
    </div>
    <button class="panel-close" type="button" onclick="closePanel()">✕</button>
  </div>
  
  <div class="panel-body">
    <div class="fg">
      <label>File Name</label>
      <div style="font-size:13px;color:#4b5563;padding:8px;background:#f3f4f6;border-radius:4px" id="view-file-name"></div>
    </div>
    
    <div class="fg">
      <label>File Size</label>
      <div style="font-size:13px;color:#4b5563;padding:8px;background:#f3f4f6;border-radius:4px" id="view-file-size"></div>
    </div>

    <div class="fg">
      <label>Sent Date</label>
      <div style="font-size:13px;color:#4b5563;padding:8px;background:#f3f4f6;border-radius:4px" id="view-sent-date"></div>
    </div>

    <div class="fg">
      <label>Current Status</label>
      <div id="view-status" style="margin-top:8px"></div>
    </div>

    <div class="fg">
      <label>Uploaded Agenda File</label>
      <div id="view-file-frame" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;background:#fff;min-height:80px"></div>
      <a id="view-file-download" class="btn btn-ghost btn-xs" href="#" target="_blank" style="margin-top:8px">⬇️ Open / Download File</a>
    </div>

    <div class="fg" id="officer-details-section" style="display:none">
      <label style="font-weight:600;margin-bottom:12px;display:block">Officer Approval Details</label>
      
      <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:6px;padding:12px;margin-bottom:12px">
        <div style="font-size:12px;color:#15803d;margin-bottom:8px"><strong>✅ Approved on:</strong> <span id="view-approved-date"></span></div>
      </div>

      <div id="view-comments-section" style="display:none;margin-bottom:12px">
        <label style="font-weight:500;font-size:12px;display:block;margin-bottom:6px">Officer Comments:</label>
        <div style="background:#f3f4f6;padding:8px;border-radius:4px;font-size:12px;color:#4b5563" id="view-comments"></div>
      </div>

      <div class="agenda-signature-certificate" id="view-signature-section" style="display:none">
        <div class="agenda-signature-visual">
          <label>Printed signature representation</label>
          <img id="view-officer-signature-img" alt="Presiding Officer signature representation" />
          <div id="view-signature-meta"></div>
        </div>
        <div class="agenda-signature-verification" id="view-signature-verification" style="display:none">
          <a id="view-agenda-verify-link" href="#" target="_blank" rel="noopener">Verify agenda signature</a>
        </div>
        <p class="agenda-signature-disclaimer">The signature image is a visual representation. Authenticity is proven by the PKI digital signature and SHA-256 hash.</p>
      </div>

    </div>
  </div>
</div>

<script>
function openApprovalPanel(agendaId) {
  const agenda = <?= json_encode(array_values($officerAgendas ?? [])) ?>.find(a => a.id === agendaId);
  if (!agenda) return;

  document.getElementById('approval-form').reset();
  document.getElementById('approval-agenda-id').value = agendaId;
  document.getElementById('approval-comments').value = '';
  renderApprovalFile(agenda);
  document.querySelector('.overlay').classList.add('open');
  document.getElementById('pnl-approval').classList.add('open');
}

function renderApprovalFile(agenda) {
  const preview = document.getElementById('approval-file-preview');
  const frame = document.getElementById('approval-file-frame');
  const name = document.getElementById('approval-file-name');
  const download = document.getElementById('approval-file-download');
  if (!preview || !frame || !name || !download) return;

  const fileUrl = 'download.php?file=' + encodeURIComponent(agenda.file_path || '') + '&inline=1';
  name.textContent = agenda.file_name || 'Agenda file';
  download.href = fileUrl;
  if ((agenda.file_type || '').startsWith('image/')) {
    frame.innerHTML = '<img src="' + fileUrl + '" alt="Agenda file preview" style="display:block;width:100%;max-height:420px;object-fit:contain" />';
  } else if ((agenda.file_type || '').includes('pdf') || (agenda.file_type || '').startsWith('text/')) {
    frame.innerHTML = '<iframe src="' + fileUrl + '" title="Agenda file preview" style="width:100%;height:420px;border:0"></iframe>';
  } else {
    frame.innerHTML = '<div style="padding:18px;color:#6b7280">Preview is unavailable for this file type. Use Open / Download File to review it.</div>';
  }
  preview.style.display = 'block';
}

function openViewPanel(agendaId) {
  const agenda = <?= json_encode(array_values($officerAgendas ?? [])) ?>.find(a => a.id === agendaId);
  if (!agenda) return;
  
  document.getElementById('view-agenda-title').textContent = esc(agenda.title);
  document.getElementById('view-file-name').textContent = esc(agenda.file_name);
  document.getElementById('view-file-size').textContent = fmtSize(agenda.file_size);
  document.getElementById('view-sent-date').textContent = fmtDate(agenda.sent_to_officer_date);

  const fileFrame = document.getElementById('view-file-frame');
  const fileDownload = document.getElementById('view-file-download');
  fileFrame.innerHTML = '';
  fileDownload.style.display = 'inline-flex';
  if (agenda.file_path) {
    const fileUrl = 'download.php?file=' + encodeURIComponent(agenda.file_path) + '&inline=1';
    fileDownload.href = fileUrl;
    const fileType = String(agenda.file_type || '').toLowerCase();
    if (fileType.includes('pdf')) {
      fileFrame.innerHTML = '<iframe src="' + fileUrl + '" title="Uploaded agenda file" style="width:100%;height:420px;border:0"></iframe>';
    } else if (fileType.startsWith('image/')) {
      fileFrame.innerHTML = '<img src="' + fileUrl + '" alt="Uploaded agenda file" style="display:block;max-width:100%;max-height:420px;margin:auto">';
    } else {
      fileFrame.textContent = 'Preview is not available for this file type. Use Open / Download File.';
    }
  } else {
    fileDownload.style.display = 'none';
    fileFrame.textContent = 'No uploaded file available.';
  }
  
  const statusBadge = document.createElement('span');
  statusBadge.className = 'badge b-' + (agenda.approval_status === 'sent_to_officer' ? 'pending' : agenda.approval_status);
  statusBadge.textContent = agenda.approval_status === 'sent_to_officer' ? 'Pending Review' : (agenda.approval_status === 'approved' ? 'Approved' : 'Rejected');
  document.getElementById('view-status').innerHTML = '';
  document.getElementById('view-status').appendChild(statusBadge);
  
  const approvalDate = agenda.proceeding_approved_at || agenda.ctrfb_approved_at || agenda.approved_at;
  if (approvalDate) {
    document.getElementById('officer-details-section').style.display = 'block';
    document.getElementById('view-approved-date').textContent = fmtDate(approvalDate);
    
    if (agenda.officer_comments) {
      document.getElementById('view-comments-section').style.display = 'block';
      document.getElementById('view-comments').textContent = esc(agenda.officer_comments);
    } else {
      document.getElementById('view-comments-section').style.display = 'none';
    }

    const approvalSignature = agenda.proceeding_signature || agenda.ctrfb_signature || agenda.officer_signature;
    const signatureSection = document.getElementById('view-signature-section');
    const verificationSection = document.getElementById('view-signature-verification');
    const signedVersion = Array.isArray(agenda.pki_signatures) ? agenda.pki_signatures[0] : null;
    if (approvalSignature) {
      signatureSection.style.display = 'grid';
      document.getElementById('view-officer-signature-img').src = approvalSignature;
      document.getElementById('view-signature-meta').textContent = signedVersion
        ? signedVersion.signer_name + ' · ' + signedVersion.signer_role + ' · ' + fmtDate(signedVersion.signed_at)
        : '';
    } else {
      signatureSection.style.display = 'none';
    }
    if (approvalSignature && signedVersion) {
      const verificationUrl = 'index.php?verify_agenda=' + encodeURIComponent(agenda.id);
      document.getElementById('view-agenda-verify-link').href = verificationUrl;
      document.getElementById('view-agenda-verify-link').textContent = 'Verify agenda signature';
      verificationSection.style.display = 'grid';
    } else {
      verificationSection.style.display = 'none';
    }
    
  } else {
    document.getElementById('officer-details-section').style.display = 'none';
    document.getElementById('view-signature-section').style.display = 'none';
  }
  
  document.querySelector('.overlay').classList.add('open');
  document.getElementById('pnl-view-details').classList.add('open');
}

function closePanel() {
  document.querySelector('.overlay').classList.remove('open');
  document.getElementById('pnl-approval').classList.remove('open');
  document.getElementById('pnl-view-details').classList.remove('open');
}

function setApprovalStatus(status) {
  const signingKeyFile = document.getElementById('agenda-pki-key-file');
  if (!signingKeyFile || !signingKeyFile.files.length) {
    alert('Upload your Presiding Officer signing key TXT file before approving.');
    return false;
  }
  return true;
}

function rejectWithReason() {
  const reason = prompt('Enter reason for rejection:');
  if (reason === null) return;
  
  const form = document.getElementById('approval-form');
  const input = document.createElement('input');
  input.type = 'hidden';
  input.name = 'status';
  input.value = 'rejected';
  form.appendChild(input);
  
  const reasonInput = document.createElement('input');
  reasonInput.type = 'hidden';
  reasonInput.name = 'rejection_reason';
  reasonInput.value = reason;
  form.appendChild(reasonInput);
  
  form.submit();
}

function esc(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

function fmtSize(bytes) {
  if (!bytes) return '0 B';
  const units = ['B', 'KB', 'MB', 'GB'];
  const i = Math.min(Math.floor(Math.log(Math.max(bytes, 1), 1024)), units.length - 1);
  return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + units[i];
}

function fmtDate(dateStr) {
  if (!dateStr) return '—';
  const normalized = String(dateStr).trim();
  const date = /^\d{4}-\d{2}-\d{2}$/.test(normalized)
    ? new Date(normalized + 'T00:00:00+08:00')
    : new Date(normalized.includes(' ') && !/[zZ]|[+-]\d{2}:?\d{2}$/.test(normalized) ? normalized.replace(' ', 'T') + '+08:00' : normalized);
  return date.toLocaleDateString('en-US', { timeZone: 'Asia/Manila', year: 'numeric', month: 'short', day: 'numeric' });
}
</script>
