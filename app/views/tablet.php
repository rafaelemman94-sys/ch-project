<div id="pg-tablet" class="page active">
  <div style="padding:18px">
    <h2>Tablet Session — Current Agendas</h2>
    <?php if (!$currentSession && count($currentSessionAgendas) === 0): ?>
      <div style="padding:12px;background:#fff4e6;border-radius:8px;color:#92400e">No current session or published council agendas for today.</div>
    <?php else: ?>
      <?php if ($currentSession): ?>
        <div style="margin-top:12px">Session: <strong><?= esc($currentSession['title']) ?></strong> — <?= fmtDate($currentSession['date']) ?> <?= esc($currentSession['start']) ?> - <?= esc($currentSession['end']) ?></div>
      <?php elseif (count($currentSessionAgendas) > 0): ?>
        <div style="margin-top:12px">Published council agendas are listed below. Each item includes its scheduled session date and time.</div>
      <?php endif; ?>
      <div style="margin-top:18px;display:grid;gap:12px">
        <?php if (count($currentSessionAgendas) === 0): ?>
          <div style="padding:12px;background:#f3f4f6;border-radius:8px">No approved agendas for this session.</div>
        <?php else: ?>
          <?php foreach ($currentSessionAgendas as $agenda): ?>
            <?php $readingStage = $agenda['reading_stage'] ?? ($agenda['approval_status'] ?? ''); ?>
            <?php $previewUrl = !empty($agenda['file_path']) ? 'download.php?file=' . rawurlencode($agenda['file_path']) . '&inline=1' : ''; ?>
            <div data-reading-stage="<?= esc($readingStage) ?>" style="padding:12px;border-radius:8px;border:1px solid #e5e7eb;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,260px);gap:12px;align-items:start;overflow:hidden">
              <div style="min-width:0">
                <div style="font-weight:600"><?= esc($agenda['title']) ?></div>
                <div style="font-size:13px;color:#6b7280"><?= esc($agenda['file_name']) ?></div>
                <?php if (!empty($agenda['session_date'])): ?>
                  <div style="margin-top:8px;color:#374151">
                    Scheduled: <?php if (!empty($agenda['session_title'])): ?><strong><?= esc($agenda['session_title']) ?></strong> — <?php endif; ?><?= fmtDate($agenda['session_date']) ?> <?= esc($agenda['session_start']) ?> - <?= esc($agenda['session_end']) ?>
                  </div>
                <?php endif; ?>
                <div style="margin-top:8px">Reading Stage: <strong class="reading-stage-value"><?= esc(readingStageLabel($readingStage)) ?></strong></div>
                <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap">
                  <a href="download.php?file=<?= esc($agenda['file_path']) ?>" class="btn btn-ghost" target="_blank">⬇️ Download</a>
                  <?php if (!empty($agenda['file_path']) && supportsInlinePreview($agenda['file_type'] ?? '')): ?>
                    <button class="btn btn-secondary" type="button" onclick="openFileViewer('<?= esc($previewUrl) ?>', '<?= esc($agenda['title']) ?>', '<?= esc($agenda['file_type']) ?>')">👁️ View File</button>
                  <?php endif; ?>
                </div>
              </div>
              <div style="min-width:0">
                <label style="font-weight:600">Reading Stage</label>
                <select disabled style="width:100%;margin-top:6px">
                  <option value="first" <?= str_contains(strtolower((string)$readingStage), 'first') ? 'selected' : '' ?>>First Reading</option>
                  <option value="second" <?= str_contains(strtolower((string)$readingStage), 'second') ? 'selected' : '' ?>>Second Reading</option>
                </select>
                <div style="margin-top:6px;font-size:12px;color:#6b7280">Admin controls this reading stage.</div>
                <div style="margin-top:8px">
                  <textarea id="comment-<?= esc($agenda['id']) ?>" placeholder="Add a comment for this reading…" style="box-sizing:border-box;width:100%;min-width:0;min-height:80px;resize:vertical"></textarea>
                </div>
                <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap">
                  <button class="btn btn-primary" onclick="submitReadingComment('<?= esc($agenda['id']) ?>')">💬 Submit Comment</button>
                  <button class="btn btn-secondary" onclick="loadComments('<?= esc($agenda['id']) ?>')">📄 View Comments</button>
                </div>
                <div id="comments-<?= esc($agenda['id']) ?>" style="margin-top:8px"></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="overlay" id="file-viewer-overlay" onclick="closeFileViewer()"></div>
<div class="slide-panel" id="file-viewer-panel" style="max-width:1100px;width:min(95vw,1100px)">
  <div class="panel-head">
    <div>
      <div class="panel-title" id="file-viewer-title">File Viewer</div>
      <div class="panel-subtitle">Preview the uploaded agenda file</div>
    </div>
    <button class="panel-close" type="button" onclick="closeFileViewer()">✕</button>
  </div>
  <div class="panel-body" style="padding:0 18px 18px 18px">
    <div id="file-viewer-frame" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;background:#fff;min-height:70vh"></div>
  </div>
  <div class="panel-foot">
    <a id="file-viewer-open-new" class="btn btn-secondary" href="#" target="_blank">Open in New Tab</a>
    <button class="btn btn-primary" type="button" onclick="closeFileViewer()">Close Viewer</button>
  </div>
</div>

<script>
function submitReadingComment(agendaId) {
  const agendaCard = getAgendaCard(agendaId);
  const reading = agendaCard ? (agendaCard.getAttribute('data-reading-stage') || 'unspecified') : 'unspecified';
  const text = document.getElementById('comment-' + agendaId).value.trim();
  if (!text) { alert('Please enter a comment.'); return; }

  const payload = new FormData();
  payload.append('action','save_annotation');
  payload.append('resource_type','agenda_reading');
  payload.append('resource_id', agendaId + '::' + reading);
  payload.append('type','comment');
  payload.append('data', text);

  fetch('index.php?page=tablet', { method: 'POST', body: payload })
    .then(r => r.json())
    .then(j => {
      if (j.success) {
        document.getElementById('comment-' + agendaId).value = '';
        loadComments(agendaId);
      } else {
        alert('Unable to save comment');
      }
    }).catch(e => alert('Network error'));
}

function onReadingChange(agendaId) {
  void agendaId;
}

function loadComments(agendaId) {
  const agendaCard = getAgendaCard(agendaId);
  const reading = agendaCard ? (agendaCard.getAttribute('data-reading-stage') || 'unspecified') : 'unspecified';
  const resourceId = agendaId + '::' + reading;
  const payload = new FormData();
  payload.append('action','get_annotations');
  payload.append('resource_type','agenda_reading');
  payload.append('resource_id', resourceId);

  fetch('index.php?page=tablet', { method: 'POST', body: payload })
    .then(r => r.json())
    .then(j => {
      let out = '';
      if (j.success && Array.isArray(j.annotations) && j.annotations.length > 0) {
        out += '<div style="padding:8px;border-radius:6px;background:#fff">';
        j.annotations.forEach(a => {
          out += '<div style="padding:6px;border-bottom:1px solid #f3f4f6"><strong>' + (a.name || 'Member') + '</strong><div style="font-size:13px;color:#374151">' + a.data + '</div></div>';
        });
        out += '</div>';
      } else {
        out = '<div style="color:#6b7280">No comments yet.</div>';
      }
      document.getElementById('comments-' + agendaId).innerHTML = out;
    }).catch(e => {
      document.getElementById('comments-' + agendaId).innerHTML = '<div style="color:#ef4444">Unable to load comments</div>';
    });
}

function getAgendaCard(agendaId) {
  return Array.from(document.querySelectorAll('[data-reading-stage]')).find(el => el.querySelector('#comment-' + agendaId)) || null;
}

function openFileViewer(fileUrl, title, fileType) {
  const overlay = document.getElementById('file-viewer-overlay');
  const panel = document.getElementById('file-viewer-panel');
  const frame = document.getElementById('file-viewer-frame');
  const heading = document.getElementById('file-viewer-title');
  const openNew = document.getElementById('file-viewer-open-new');
  if (!overlay || !panel || !frame || !heading || !openNew) return;

  heading.textContent = title || 'File Viewer';
  openNew.href = fileUrl;

  if ((fileType || '').startsWith('image/')) {
    frame.innerHTML = '<img src="' + fileUrl + '" alt="Agenda preview" style="display:block;width:100%;max-height:78vh;object-fit:contain;background:#fff" />';
  } else {
    frame.innerHTML = '<iframe src="' + fileUrl + '" title="Agenda preview" style="width:100%;height:78vh;border:0;background:#fff"></iframe>';
  }

  overlay.classList.add('open');
  panel.classList.add('open');
}

function closeFileViewer() {
  const overlay = document.getElementById('file-viewer-overlay');
  const panel = document.getElementById('file-viewer-panel');
  const frame = document.getElementById('file-viewer-frame');
  if (overlay) overlay.classList.remove('open');
  if (panel) panel.classList.remove('open');
  if (frame) frame.innerHTML = '';
}

function renderAgendaPrintPreview() {
  const preview = document.getElementById('agenda-print-preview');
  const rows = Array.from(document.querySelectorAll('#pg-tablet > div > div > div div[style*="border:1px solid"]'));
  if (!preview || rows.length === 0) {
    preview.innerHTML = '<div style="padding:12px;border-radius:8px;background:#f3f4f6;color:#374151">No agendas available for preview.</div>';
    return;
  }

  const agendas = rows.map(row => {
    const title = row.querySelector('div strong')?.textContent || 'Untitled';
    const fileName = row.querySelector('div[style*="font-size:13px"]')?.textContent || '';
    const detected = row.querySelector('.reading-stage-value')?.textContent || '';
    const downloadLink = row.querySelector('a[href*="download.php"]');
    const previewFrame = row.querySelector('iframe[title="Agenda preview"]');
    const previewImage = row.querySelector('img[alt="Agenda preview"]');
    const fileUrl = downloadLink ? downloadLink.href : '#';
    const previewUrl = previewFrame ? previewFrame.getAttribute('src') : (previewImage ? previewImage.getAttribute('src') : fileUrl);
    const previewType = previewImage ? 'image' : 'document';
    return { title, fileName, detected, fileUrl, previewUrl, previewType };
  });

  const firstReading = agendas.filter(a => /first/i.test(a.detected));
  const secondReading = agendas.filter(a => /second/i.test(a.detected));

  let html = '<div style="padding:14px;border-radius:10px;border:1px solid #e5e7eb;background:#ffffff">';
  html += '<h3 style="margin-top:0">Session Agenda Print Preview</h3>';
  html += '<div style="margin-bottom:12px;color:#4b5563">This preview shows the actual uploaded files that will be printed, grouped by the reading stage selected by admin.</div>';

  if (firstReading.length > 0) {
    html += '<div style="margin-bottom:16px"><h4>First Reading</h4>';
    firstReading.forEach(a => {
      html += '<div style="padding:10px;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:12px">';
      html += '<strong>' + a.title + '</strong><div style="font-size:13px;color:#6b7280">' + a.fileName + '</div>';
      if (a.previewType === 'image') {
        html += '<img src="' + a.previewUrl + '" alt="Agenda preview" style="display:block;width:100%;max-height:620px;object-fit:contain;margin-top:10px;background:#fff" />';
      } else {
        html += '<iframe src="' + a.previewUrl + '" title="Agenda preview" style="width:100%;height:780px;border:1px solid #e5e7eb;margin-top:10px;background:#fff"></iframe>';
      }
      html += '</div>';
    });
    html += '</div>';
  }

  if (secondReading.length > 0) {
    html += '<div style="margin-bottom:16px"><h4>Second Reading</h4>';
    secondReading.forEach(a => {
      html += '<div style="padding:10px;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:12px">';
      html += '<strong>' + a.title + '</strong><div style="font-size:13px;color:#6b7280">' + a.fileName + '</div>';
      if (a.previewType === 'image') {
        html += '<img src="' + a.previewUrl + '" alt="Agenda preview" style="display:block;width:100%;max-height:620px;object-fit:contain;margin-top:10px;background:#fff" />';
      } else {
        html += '<iframe src="' + a.previewUrl + '" title="Agenda preview" style="width:100%;height:780px;border:1px solid #e5e7eb;margin-top:10px;background:#fff"></iframe>';
      }
      html += '</div>';
    });
    html += '</div>';
  }

  const unknown = agendas.filter(a => !/first/i.test(a.detected) && !/second/i.test(a.detected));
  if (unknown.length > 0) {
    html += '<div style="margin-bottom:16px"><h4>Unspecified Reading</h4>';
    unknown.forEach(a => {
      html += '<div style="padding:10px;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:12px">';
      html += '<strong>' + a.title + '</strong><div style="font-size:13px;color:#6b7280">' + a.fileName + '</div>';
      if (a.previewType === 'image') {
        html += '<img src="' + a.previewUrl + '" alt="Agenda preview" style="display:block;width:100%;max-height:620px;object-fit:contain;margin-top:10px;background:#fff" />';
      } else {
        html += '<iframe src="' + a.previewUrl + '" title="Agenda preview" style="width:100%;height:780px;border:1px solid #e5e7eb;margin-top:10px;background:#fff"></iframe>';
      }
      html += '</div>';
    });
    html += '</div>';
  }

  html += '</div>';
  preview.innerHTML = html;
}

function printAgendaBatch() {
  const preview = document.getElementById('agenda-print-preview');
  renderAgendaPrintPreview();
  const sessionId = '<?= esc($currentSession['id'] ?? '') ?>';
  const agendaIds = Array.from(document.querySelectorAll('#pg-tablet a[href*="download.php"]')).map(link => {
    const row = link.closest('div[style*="border:1px solid"]');
    return row?.querySelector('button[onclick*="submitReadingComment"]')?.getAttribute('onclick')?.match(/'(.+)'/)?.[1] || null;
  }).filter(Boolean);

  if (!sessionId || agendaIds.length === 0) {
    alert('No session or agenda items were found for printing.');
    return;
  }

  const printWin = window.open('', '_blank');
  if (!printWin) {
    alert('Unable to open print window.');
    return;
  }

  printWin.document.write('<html><head><title>Session Agenda Print</title><style>body{font-family:Arial,sans-serif;padding:20px;color:#111}h1,h2,h3{margin:0 0 8px}section{margin-bottom:24px}article{margin-bottom:12px;padding:12px;border:1px solid #ddd;border-radius:8px}a{color:#2563eb;text-decoration:none}</style></head><body>');
  printWin.document.write('<h1>Session Agenda</h1>');
  printWin.document.write('<p>Session: <?= esc($currentSession['title'] ?? 'Unknown') ?>, <?= esc(fmtDate($currentSession['date'] ?? '')) ?> <?= esc($currentSession['start'] ?? '') ?> - <?= esc($currentSession['end'] ?? '') ?></p>');
  printWin.document.write(preview.innerHTML);
  printWin.document.write('<script>window.onload=function(){window.print();window.onafterprint=function(){window.close();}};<\/script>');
  printWin.document.write('</body></html>');
  printWin.document.close();

  const payload = new FormData();
  payload.append('action', 'council_printed_agenda');
  payload.append('session_id', sessionId);
  agendaIds.forEach(id => payload.append('agenda_ids[]', id));

  fetch('index.php?page=tablet', { method: 'POST', body: payload })
    .then(r => r.json())
    .then(j => {
      if (!j.success) {
        console.warn('Print notification failed', j.error || 'Unknown error');
      }
    }).catch(e => console.warn('Print notification error', e));
}

function toggleAgendaSummary() {
  const summary = document.getElementById('agenda-summary');
  if (!summary) {
    return;
  }
  if (summary.style.display === 'none' || summary.style.display === '') {
    summary.style.display = 'block';
    renderAgendaSummary();
  } else {
    summary.style.display = 'none';
  }
}

function renderAgendaSummary() {
  const summary = document.getElementById('agenda-summary');
  const agendaCards = Array.from(document.querySelectorAll('#pg-tablet > div > div > div div[style*="border:1px solid"]'));
  if (!summary || agendaCards.length === 0) {
    summary.innerHTML = '<div style="padding:12px;border-radius:8px;background:#f3f4f6;color:#374151">No agendas sent to you yet.</div>';
    return;
  }

  let html = '<div style="padding:14px;border-radius:10px;border:1px solid #e5e7eb;background:#ffffff">';
  html += '<h3 style="margin-top:0">Agendas Sent to Council</h3>';
  html += '<div style="margin-bottom:12px;color:#4b5563">This list shows the approved agendas sent to your tablet for the current session.</div>';

  agendaCards.forEach(card => {
    const title = card.querySelector('div strong')?.textContent || 'Untitled';
    const fileName = card.querySelector('div[style*="font-size:13px"]')?.textContent || '';
    const detected = card.querySelector('.reading-stage-value')?.textContent || 'Unspecified';
    const downloadLink = card.querySelector('a[href*="download.php"]');
    const fileUrl = downloadLink ? downloadLink.href : '#';

    html += '<div style="padding:12px;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:10px">';
    html += '<div style="font-weight:700">' + title + '</div>';
    html += '<div style="font-size:13px;color:#6b7280">' + fileName + '</div>';
    html += '<div style="margin-top:6px;color:#374151">' + detected + '</div>';
    html += '<div style="margin-top:8px;"><a href="' + fileUrl + '" class="btn btn-ghost" target="_blank">⬇️ Download</a></div>';
    html += '</div>';
  });

  html += '</div>';
  summary.innerHTML = html;
}
</script>
