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
  $recipientIds = $agenda['sent_to_officer_id'] ?? '';
  if (empty($recipientIds)) return false;
  
  // Try to decode as JSON array
  $decoded = json_decode($recipientIds, true);
  if (is_array($decoded)) {
    return in_array($currentUserId, $decoded, true);
  }
  // Fallback for single ID (backward compatibility)
  return $recipientIds === $currentUserId;
}

$userAgendas = ($currentUser['role'] ?? '') === 'presiding officer'
  ? array_filter($agendas ?? [], fn($a) => isAgendaRecipient($a, $currentUser['id'] ?? ''))
  : [];
?>

<div id="pg-ctrfb" class="page active">
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:24px">
    <div class="stat-card">
      <div class="stat-val"><?= count(array_filter($userAgendas ?? [], fn($a) => ($a['approval_status'] ?? '') === 'sent_to_officer')) ?></div>
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
            <td colspan="5" style="text-align:center;padding:32px;color:#9ca3af">Agenda review and signing are handled by the Presiding Officer.</td>
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
                    if ($statusLabel === 'sent_to_officer') echo '⏳ Pending Review';
                    elseif ($statusLabel === 'approved') echo '✅ Approved';
                    elseif ($statusLabel === 'rejected') echo '❌ Rejected';
                    else echo ucfirst($statusLabel);
                  ?>
                </span>
              </td>
              <td>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                  <?php if (!empty($agenda['file_path'])): ?>
                    <a href="download.php?file=<?= esc($agenda['file_path']) ?>" class="btn btn-ghost btn-xs">⬇️ Download</a>
                  <?php endif; ?>
                  
                  <?php if (($currentUser['role'] ?? '') === 'presiding officer' && ($agenda['approval_status'] ?? '') === 'sent_to_officer'): ?>
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

<?php if (($currentUser['role'] ?? '') === 'presiding officer'): ?>
<!-- Approval Panel -->
<div class="overlay<?= $openPanel === 'approval' ? ' open' : '' ?>" onclick="closePanel()"></div>
<div class="slide-panel<?= $openPanel === 'approval' ? ' open' : '' ?>" id="pnl-approval">
  <div class="panel-head">
    <div>
      <div class="panel-title">Review & Approve Agenda</div>
      <div class="panel-subtitle">Add your digital signature and comments</div>
    </div>
    <button class="panel-close" type="button" onclick="closePanel()">✕</button>
  </div>
  
  <form class="panel-body" method="post" action="index.php?page=ctrfb" id="approval-form" enctype="multipart/form-data" autocomplete="off">
    <input type="hidden" name="action" value="approve_agenda" />
    <input type="hidden" name="agenda_id" value="" id="approval-agenda-id" />
    <input type="hidden" name="signature" value="" id="approval-signature" />

    <div class="fg" id="approval-file-preview" style="display:none">
      <label style="font-weight:500">Review File</label>
      <div id="approval-file-name" style="font-size:12px;color:#6b7280;margin:6px 0"></div>
      <div id="approval-file-frame" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;background:#fff;min-height:120px"></div>
      <a id="approval-file-download" class="btn btn-ghost btn-xs" href="#" target="_blank" style="margin-top:8px">⬇️ Open / Download File</a>
    </div>

    <div class="fg">
      <label style="font-weight:500">Digital Signature</label>
      <div id="sig-draw-mode" style="border:2px solid #e5e7eb;border-radius:8px;background:#f9fafb;padding:12px;margin-top:8px">
        <canvas id="sig-canvas" style="border:1px solid #d1d5db;width:100%;height:250px;cursor:crosshair;background:white;display:block;border-radius:4px"></canvas>
        <div style="display:flex;gap:8px;margin-top:8px;justify-content:flex-end;align-items:center">
          <span id="signature-confirm-status" style="font-size:11px;color:#6b7280;flex:1;text-align:left">Please confirm your signature before approving.</span>
          <button type="button" class="btn btn-primary btn-xs" onclick="confirmSignature()">Confirm Signature</button>
          <button type="button" class="btn btn-secondary btn-xs" onclick="clearSignature()">Clear</button>
        </div>
        <div style="font-size:11px;color:#6b7280;margin-top:8px">Click and draw your signature above</div>
      </div>
    </div>

    <div class="fg">
      <label>Or Upload Digital Signature</label>
      <input type="file" name="signature_upload" id="signature-upload" accept="image/png,image/jpeg,image/webp" />
      <div style="font-size:11px;color:#6b7280;margin-top:6px">Use a PNG, JPG, or WEBP image up to 2 MB. The system checks that the uploaded agenda changed and that this signature is confirmed.</div>
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
<?php endif; ?>

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

      <div id="view-signature-section" style="display:none">
        <label style="font-weight:500;font-size:12px;display:block;margin-bottom:6px">Digital Signature:</label>
        <img id="view-signature-img" style="border:1px solid #e5e7eb;border-radius:4px;max-width:100%;height:auto;max-height:300px" />
      </div>

    </div>
  </div>
</div>

<script>
let sigCanvas, sigCtx;
let isDrawing = false;
let signatureConfirmed = false;

function initSignature() {
  sigCanvas = document.getElementById('sig-canvas');
  if (!sigCanvas) return;
  
  sigCtx = sigCanvas.getContext('2d');
  
  // Set canvas resolution
  const rect = sigCanvas.getBoundingClientRect();
  sigCanvas.width = rect.width;
  sigCanvas.height = rect.height;
  
  // Set drawing style
  sigCtx.lineWidth = 2;
  sigCtx.lineCap = 'round';
  sigCtx.lineJoin = 'round';
  sigCtx.strokeStyle = '#1f2937';
  
  // Mouse events
  sigCanvas.addEventListener('mousedown', startDrawing);
  sigCanvas.addEventListener('mousemove', draw);
  sigCanvas.addEventListener('mouseup', stopDrawing);
  sigCanvas.addEventListener('mouseout', stopDrawing);
  
  // Touch events
  sigCanvas.addEventListener('touchstart', startDrawing);
  sigCanvas.addEventListener('touchmove', draw);
  sigCanvas.addEventListener('touchend', stopDrawing);
}

function startDrawing(e) {
  if (!sigCanvas) return;
  if (signatureConfirmed) {
    signatureConfirmed = false;
    document.getElementById('approval-signature').value = '';
    updateSignatureStatus('Please confirm your signature before approving.', '#6b7280');
  }
  isDrawing = true;
  const rect = sigCanvas.getBoundingClientRect();
  const x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
  const y = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top;
  sigCtx.beginPath();
  sigCtx.moveTo(x, y);
}

function draw(e) {
  if (!isDrawing || !sigCanvas) return;
  e.preventDefault();
  const rect = sigCanvas.getBoundingClientRect();
  const x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
  const y = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top;
  sigCtx.lineTo(x, y);
  sigCtx.stroke();
}

function stopDrawing() {
  isDrawing = false;
  if (sigCtx) sigCtx.closePath();
}

function updateSignatureStatus(message, color) {
  const statusEl = document.getElementById('signature-confirm-status');
  if (statusEl) {
    statusEl.textContent = message;
    statusEl.style.color = color;
  }
}

function clearSignature() {
  if (!sigCanvas) return;
  sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
  document.getElementById('approval-signature').value = '';
  signatureConfirmed = false;
  updateSignatureStatus('Please confirm your signature before approving.', '#6b7280');
}

function confirmSignature() {
  if (!sigCanvas) return;
  const drawnSignature = sigCanvas.toDataURL();
  const blankCanvas = document.createElement('canvas');
  blankCanvas.width = sigCanvas.width;
  blankCanvas.height = sigCanvas.height;
  const blankCtx = blankCanvas.getContext('2d');
  blankCtx.fillStyle = 'white';
  blankCtx.fillRect(0, 0, blankCanvas.width, blankCanvas.height);

  if (drawnSignature === blankCanvas.toDataURL()) {
    alert('Please draw your signature before confirming');
    return;
  }

  document.getElementById('approval-signature').value = drawnSignature;
  signatureConfirmed = true;
  updateSignatureStatus('Signature confirmed ✓', '#16a34a');
}

function switchSignatureMode(mode) {
  const drawMode = document.getElementById('sig-draw-mode');
  if (drawMode) {
    drawMode.style.display = 'block';
  }
}

function openApprovalPanel(agendaId) {
  const agenda = <?= json_encode(array_values($officerAgendas ?? [])) ?>.find(a => a.id === agendaId);
  if (!agenda) return;

  document.getElementById('approval-agenda-id').value = agendaId;
  document.getElementById('approval-comments').value = '';
  renderApprovalFile(agenda);
  clearSignature();
  switchSignatureMode('draw');
  document.getElementById('pnl-approval').previousElementSibling.classList.add('open');
  document.getElementById('pnl-approval').classList.add('open');
  setTimeout(initSignature, 100);
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
  
  if (agenda.approved_at) {
    document.getElementById('officer-details-section').style.display = 'block';
    document.getElementById('view-approved-date').textContent = fmtDate(agenda.approved_at);
    
    if (agenda.officer_comments) {
      document.getElementById('view-comments-section').style.display = 'block';
      document.getElementById('view-comments').textContent = esc(agenda.officer_comments);
    }

    const approvalSignature = agenda.ctrfb_signature || agenda.proceeding_signature || agenda.officer_signature;
    if (approvalSignature) {
      document.getElementById('view-signature-section').style.display = 'block';
      document.getElementById('view-signature-img').src = approvalSignature;
    }
    
  } else {
    document.getElementById('officer-details-section').style.display = 'none';
  }
  
  document.getElementById('pnl-view-details').previousElementSibling.classList.add('open');
  document.getElementById('pnl-view-details').classList.add('open');
}

function closePanel() {
  document.getElementById('pnl-approval').previousElementSibling.classList.remove('open');
  document.getElementById('pnl-view-details').previousElementSibling.classList.remove('open');
  document.getElementById('pnl-approval').classList.remove('open');
  document.getElementById('pnl-view-details').classList.remove('open');
}

function setApprovalStatus(status) {
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
