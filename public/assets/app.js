const APP_TIME_ZONE = 'Asia/Manila';

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text == null ? '' : String(text);
  return div.innerHTML;
}

function parseAppDate(value) {
  if (!value) return null;
  const normalized = String(value).trim();
  if (/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
    return new Date(normalized + 'T00:00:00+08:00');
  }
  if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/.test(normalized)) {
    return new Date(normalized.replace(' ', 'T') + '+08:00');
  }
  return new Date(normalized);
}

function formatAppDate(value) {
  const date = parseAppDate(value);
  return date && !Number.isNaN(date.getTime())
    ? date.toLocaleDateString('en-US', { timeZone: APP_TIME_ZONE })
    : '';
}

document.addEventListener('DOMContentLoaded', function () {
  const sidebar = document.getElementById('sidebar');
  const toggle = document.getElementById('sidebarToggle');
  const mobileToggle = document.getElementById('mobileSidebarToggle');
  const mobileBackdrop = document.getElementById('mobileNavBackdrop');
  const themeToggle = document.getElementById('themeToggle');
  const themeKey = 'adminPortalTheme';

  if (toggle) {
    toggle.addEventListener('click', function () {
      sidebar.classList.toggle('mini');
    });
  }

  const closeMobileNavigation = function () {
    if (!sidebar) return;
    sidebar.classList.remove('mobile-open');
    document.body.classList.remove('mobile-nav-open');
    if (mobileToggle) mobileToggle.setAttribute('aria-expanded', 'false');
  };

  if (sidebar && mobileToggle) {
    mobileToggle.addEventListener('click', function () {
      const isOpen = sidebar.classList.toggle('mobile-open');
      document.body.classList.toggle('mobile-nav-open', isOpen);
      mobileToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    mobileBackdrop?.addEventListener('click', closeMobileNavigation);
    sidebar.querySelectorAll('.sb-nav a').forEach(function (link) {
      link.addEventListener('click', closeMobileNavigation);
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closeMobileNavigation();
    });
    window.matchMedia('(min-width: 769px)').addEventListener('change', function (event) {
      if (event.matches) closeMobileNavigation();
    });
  }

  const applyTheme = (theme) => {
    const isLight = theme === 'light';
    // set both classes explicitly so CSS can target dark-mode or light-mode
    document.body.classList.toggle('light-theme', isLight);
    document.body.classList.toggle('dark-theme', !isLight);
    if (themeToggle) {
      themeToggle.textContent = isLight ? '🌙' : '☀️';
      themeToggle.title = isLight ? 'Switch to dark theme' : 'Switch to light theme';
    }
  };

  const savedTheme = localStorage.getItem(themeKey) || 'dark';
  applyTheme(savedTheme);

  if (themeToggle) {
    themeToggle.addEventListener('click', function () {
      const nextTheme = document.body.classList.contains('light-theme') ? 'dark' : 'light';
      localStorage.setItem(themeKey, nextTheme);
      applyTheme(nextTheme);
    });
  }

  const integrityMonitor = document.getElementById('legislative-integrity-monitor');
  if (integrityMonitor) {
    let integrityScanInFlight = false;
    const scanLegislativeIntegrity = async function () {
      if (integrityScanInFlight || document.visibilityState !== 'visible') return;
      integrityScanInFlight = true;
      try {
        const response = await fetch(integrityMonitor.dataset.endpoint || 'index.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ action: 'scan_legislative_integrity' }),
        });
        if (!response.ok) return;
        const result = await response.json();
        (result.alerts || []).forEach(({ control_number: controlNumber, title }) => {
          const billLabel = controlNumber || title || 'A bill';
          showToast('error', `Integrity alert: ${billLabel} was changed in storage. The owner was notified.`);
        });
      } catch (error) {
        console.warn('Legislative integrity scan failed', error);
      } finally {
        integrityScanInFlight = false;
      }
    };

    window.setTimeout(scanLegislativeIntegrity, 3000);
    window.setInterval(scanLegislativeIntegrity, 60000);
    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState === 'visible') scanLegislativeIntegrity();
    });
  }

  const notificationToggle = document.getElementById('notificationToggle');
  const notificationDropdown = document.getElementById('notificationDropdown');
  if (notificationToggle && notificationDropdown) {
    notificationToggle.addEventListener('click', function (event) {
      event.stopPropagation();
      notificationDropdown.classList.toggle('open');
    });
    notificationDropdown.addEventListener('click', function (event) {
      event.stopPropagation();
    });
    document.addEventListener('click', function () {
      notificationDropdown.classList.remove('open');
    });
  }

  document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
      const field = button.parentElement
        ? button.parentElement.querySelector('input[type="password"], input[type="text"]')
        : null;
      if (!field) return;
      const isHidden = field.type === 'password';
      field.type = isHidden ? 'text' : 'password';
      button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
      button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
      button.setAttribute('title', isHidden ? 'Hide password' : 'Show password');
    });
  });
});

function openSendMinutesModal(sessionId, sessionTitle) {
  const id = document.getElementById('send-minutes-session-id');
  if (id && sessionId) id.value = sessionId;
  updateSendMinutesSubtitle(sessionTitle);
  document.querySelectorAll('#send-minutes-form input[type="checkbox"]').forEach((checkbox) => { checkbox.checked = false; });
  document.getElementById('send-minutes-overlay')?.classList.add('open');
  document.getElementById('send-minutes-panel')?.classList.add('open');
}

function updateSendMinutesSubtitle(sessionTitle) {
  const id = document.getElementById('send-minutes-session-id');
  const subtitle = document.getElementById('send-minutes-subtitle');
  const title = sessionTitle || id?.selectedOptions[0]?.textContent || '';
  if (subtitle) subtitle.textContent = title ? 'Send session minutes for "' + title + '"' : 'Select a session to send minutes';
}

function closeSendMinutesModal() {
  document.getElementById('send-minutes-overlay')?.classList.remove('open');
  document.getElementById('send-minutes-panel')?.classList.remove('open');
}

function openDeliveryHistoryModal() {
  document.getElementById('delivery-history-overlay')?.classList.add('open');
  document.getElementById('delivery-history-panel')?.classList.add('open');
}

function closeDeliveryHistoryModal() {
  document.getElementById('delivery-history-overlay')?.classList.remove('open');
  document.getElementById('delivery-history-panel')?.classList.remove('open');
}

function printSentMinutes(button) {
  const entry = button.closest('.minutes-history-entry');
  if (!entry) return;
  document.body.classList.add('print-single-minute');
  entry.classList.add('print-target');
  window.addEventListener('afterprint', function restoreMinutesHistory() {
    document.body.classList.remove('print-single-minute');
    entry.classList.remove('print-target');
  }, { once: true });
  window.print();
}

function validateMinutesRecipients() {
  if (!document.querySelectorAll('#send-minutes-form input[type="checkbox"]:checked').length) { alert('Please select at least one recipient'); return false; }
  return true;
}

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.attendance-decision').forEach((button) => button.addEventListener('click', function () {
    const row = this.closest('.attendance-request-row');
    if (!row) return;
    const buttons = row.querySelectorAll('button');
    buttons.forEach((item) => { item.disabled = true; });
    fetch('index.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action: 'decide_attendance', request_id: row.dataset.requestId, status: this.dataset.status }), credentials: 'same-origin' })
      .then((response) => response.json()).then((result) => {
        if (!result.success) throw new Error(result.error || 'Unable to decide attendance.');
        row.remove();
        const card = document.getElementById('attendance-requests-card');
        if (card && !card.querySelector('.attendance-request-row')) card.remove();
      }).catch((error) => { buttons.forEach((item) => { item.disabled = false; }); alert(error.message); });
  }));
});

function openSendToOfficerModal(agendaId, agendaTitle) {
  document.getElementById('send-agenda-id').value = agendaId;
  document.getElementById('send-officer-subtitle').textContent = 'Send "' + escapeHtml(agendaTitle) + '" for review';
  document.querySelectorAll('#send-to-officer-form input[type="checkbox"]').forEach((checkbox) => { checkbox.checked = false; });
  document.getElementById('send-officer-overlay')?.classList.add('open');
  document.getElementById('send-officer-panel')?.classList.add('open');
}

function closeOfficerModal() { document.getElementById('send-officer-overlay')?.classList.remove('open'); document.getElementById('send-officer-panel')?.classList.remove('open'); }
function openSendToProceedingModal(agendaId, agendaTitle) {
  document.getElementById('send-proceeding-agenda-id').value = agendaId;
  document.getElementById('send-proceeding-subtitle').textContent = 'Send "' + escapeHtml(agendaTitle) + '" to Proceeding Officer for final review';
  document.querySelectorAll('#send-to-proceeding-form input[type="checkbox"]').forEach((checkbox) => { checkbox.checked = false; });
  document.getElementById('send-proceeding-overlay')?.classList.add('open');
  document.getElementById('send-proceeding-panel')?.classList.add('open');
}
function closeProceedingModal() { document.getElementById('send-proceeding-overlay')?.classList.remove('open'); document.getElementById('send-proceeding-panel')?.classList.remove('open'); }
function openPublishModal(agendaId, agendaTitle, currentSessionId) {
  document.getElementById('publish-agenda-id').value = agendaId;
  document.getElementById('publish-subtitle').textContent = 'Assign a schedule to "' + escapeHtml(agendaTitle) + '"';
  document.querySelectorAll('#publish-form input[type="radio"]').forEach((radio) => { radio.checked = Boolean(currentSessionId && radio.value === currentSessionId); if (radio.checked) document.getElementById('publish-session-id').value = radio.value; });
  document.getElementById('publish-overlay')?.classList.add('open'); document.getElementById('publish-panel')?.classList.add('open');
}
function closePublishModal() { document.getElementById('publish-overlay')?.classList.remove('open'); document.getElementById('publish-panel')?.classList.remove('open'); }
function validatePublish() { if (!document.getElementById('publish-session-id')?.value) { alert('Please select a schedule first'); return false; } return true; }
function validateProceedingRecipients() { if (!document.querySelectorAll('#send-to-proceeding-form input[type="checkbox"]:checked').length) { alert('Please select at least one Proceeding Officer'); return false; } return true; }
function validateRecipients() { if (!document.querySelectorAll('#send-to-officer-form input[type="checkbox"]:checked').length) { alert('Please select at least one recipient'); return false; } return true; }
function showApprovalStatus() { alert('This agenda has been sent for officer review. You will be notified once the officer completes their review.'); }
function setAgendaReadingStage(agendaId, reading) { const payload = new FormData(); payload.append('action', 'set_reading_stage'); payload.append('agenda_id', agendaId); payload.append('reading', reading); fetch('index.php?page=agendas', { method: 'POST', body: payload }).then((response) => response.json()).then((result) => { if (result.success) window.location.reload(); else alert('Unable to update reading stage: ' + (result.error || 'Unknown error')); }).catch(() => alert('Network error')); }
function openFileViewer(fileUrl, title, fileType) { const overlay = document.getElementById('file-viewer-overlay'); const panel = document.getElementById('file-viewer-panel'); const frame = document.getElementById('file-viewer-frame'); const heading = document.getElementById('file-viewer-title'); const openNew = document.getElementById('file-viewer-open-new'); if (!overlay || !panel || !frame || !heading || !openNew) return; heading.textContent = title || 'File Viewer'; openNew.href = fileUrl; frame.innerHTML = (fileType || '').startsWith('image/') ? '<img src="' + fileUrl + '" alt="Agenda preview" class="file-viewer-image" />' : '<iframe src="' + fileUrl + '" title="Agenda preview" class="file-viewer-frame"></iframe>'; overlay.classList.add('open'); panel.classList.add('open'); }
function closeFileViewer() { document.getElementById('file-viewer-overlay')?.classList.remove('open'); document.getElementById('file-viewer-panel')?.classList.remove('open'); const frame = document.getElementById('file-viewer-frame'); if (frame) frame.innerHTML = ''; }

function updateDocumentPerson(type) {
  const select = document.getElementById(type + '-id');
  if (!select) return;
  const option = select.options[select.selectedIndex];
  const name = option && option.dataset.name ? option.dataset.name : '';
  const nameField = document.getElementById(type + '-name');
  const pill = document.getElementById(type + '-pill');
  if (nameField) nameField.value = name;
  if (pill) { pill.hidden = !name; if (name) pill.querySelector('span').textContent = name; }
}

function addDocumentPerson(type) {
  const name = window.prompt(type === 'author' ? 'Enter the new author name:' : 'Enter the new sponsor name:');
  if (!name || !name.trim()) return;
  const idField = document.getElementById(type + '-id');
  const nameField = document.getElementById(type + '-name');
  const pill = document.getElementById(type + '-pill');
  if (idField) idField.value = '';
  if (nameField) nameField.value = name.trim();
  if (pill) { pill.hidden = false; pill.querySelector('span').textContent = name.trim(); }
}

function formatDocument(command, value) {
  document.getElementById('document-content')?.focus();
  document.execCommand(command, false, value || null);
}

function openCtfrbReportPanel() {
  document.getElementById('ctfrb-report-overlay')?.classList.add('open');
  document.getElementById('ctfrb-report-panel')?.classList.add('open');
}

function closeCtfrbReportPanel() {
  const url = new URL(window.location.href);
  ['open', 'type', 'edit'].forEach((key) => url.searchParams.delete(key));
  window.location.href = url.toString();
}

function openLegislativeSendPanel(legislativeId, legislativeTitle) {
  const idField = document.getElementById('leg-send-legislative-id');
  const subtitle = document.getElementById('leg-send-subtitle');
  if (idField) idField.value = legislativeId;
  if (subtitle) subtitle.textContent = 'Send "' + legislativeTitle + '" to selected council members';
  document.querySelectorAll('#leg-send-form input[type="checkbox"]').forEach((checkbox) => { checkbox.checked = false; });
  document.getElementById('leg-send-overlay')?.classList.add('open');
  document.getElementById('leg-send-panel')?.classList.add('open');
}

function closeLegislativeSendPanel() {
  document.getElementById('leg-send-overlay')?.classList.remove('open');
  document.getElementById('leg-send-panel')?.classList.remove('open');
}

function validateLegislativeRecipients() {
  const checked = document.querySelectorAll('#leg-send-form input[type="checkbox"]:checked');
  if (!checked.length) { alert('Choose at least one council member.'); return false; }
  return confirm('Send this legislative draft to the selected council member(s)?');
}

function previewLegislativeContent() {
  const preview = document.getElementById('legislative-preview');
  const content = document.getElementById('legislative-content');
  const wrapper = document.getElementById('legislative-preview-wrapper');
  if (!preview || !content || !wrapper) return;
  preview.textContent = content.value.trim() || 'Upload a template or type the bill content first.';
  wrapper.style.display = 'block';
}

function downloadLegislativeContent(button) {
  const uploadedFile = button?.dataset.uploadedFile || '';
  if (uploadedFile) {
    window.location.href = 'download.php?file=' + encodeURIComponent(uploadedFile);
    return;
  }
  const fileInput = document.querySelector('input[name="file"]');
  if (fileInput?.files?.length && button) {
    syncLegislativeContent();
    const form = button.closest('form');
    const exportField = document.createElement('input');
    exportField.type = 'hidden'; exportField.name = 'export_after_save'; exportField.value = '1';
    form?.appendChild(exportField); form?.submit();
    return;
  }
  const title = document.querySelector('input[name="title"]')?.value.trim() || 'legislative-bill';
  const content = document.getElementById('legislative-content');
  const payload = content ? content.value.trim() : '';
  if (!payload) { alert('Upload a template or enter bill content before downloading.'); return; }
  window.location.href = 'download.php?docx=1&title=' + encodeURIComponent(title) + '&content=' + encodeURIComponent(payload);
}

function syncLegislativeContent() {
  const editor = document.getElementById('legislative-editor');
  const content = document.getElementById('legislative-content');
  if (editor && content) content.value = editor.innerHTML.trim();
}

function formatLegislative(command) {
  document.getElementById('legislative-editor')?.focus();
  document.execCommand(command, false, null);
  syncLegislativeContent();
}

function importLegislativeText(button) {
  const fileInput = document.querySelector('input[name="file"]');
  if (!fileInput?.files?.length) { alert('Choose a bill file first.'); return; }
  syncLegislativeContent();
  const field = document.createElement('input');
  field.type = 'hidden'; field.name = 'import_after_save'; field.value = '1';
  button.closest('form')?.appendChild(field); button.closest('form')?.submit();
}

function detectLegislativeType() {
  const typeField = document.querySelector('[data-legislative-type]');
  if (!typeField || typeField.dataset.manual === 'true') return;
  const source = [document.querySelector('input[name="title"]')?.value || '', document.getElementById('legislative-content')?.value || ''].join(' ').toLowerCase();
  if (/\b(resolution|resolved|resolves)\b/.test(source)) typeField.value = 'Resolution';
  else if (/\b(bill|act|enact|enacted)\b/.test(source)) typeField.value = 'Bill';
}

document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('author-id')?.addEventListener('change', () => updateDocumentPerson('author'));
  document.getElementById('sponsor-id')?.addEventListener('change', () => updateDocumentPerson('sponsor'));
  document.querySelector('[data-legislative-type]')?.addEventListener('change', function () { this.dataset.manual = 'true'; });
  ['input[name="title"]', '#legislative-editor'].forEach((selector) => document.querySelector(selector)?.addEventListener('input', detectLegislativeType));
  document.querySelector('form[action="index.php?page=legislative-bills"]')?.addEventListener('submit', syncLegislativeContent);
});

// Auto polling for analytics on legislative-admin page
// Polling manager for analytics
let _analyticsPollId = null;
let _analyticsPollInterval = 30000;
function startAnalyticsPolling(intervalMs) {
  stopAnalyticsPolling();
  _analyticsPollInterval = intervalMs || _analyticsPollInterval;
  // initial run
  try { refreshAnalytics(); } catch (e) { console.warn('Polling initial refresh failed', e); }
  _analyticsPollId = setInterval(() => {
    try { refreshAnalytics(); } catch (e) { console.warn('Polling error', e); }
  }, _analyticsPollInterval);
}
function stopAnalyticsPolling() {
  if (_analyticsPollId) {
    clearInterval(_analyticsPollId);
    _analyticsPollId = null;
  }
}
// Initialize polling controls when page loads
document.addEventListener('DOMContentLoaded', function () {
  try {
    if (typeof currentPage !== 'undefined' && currentPage === 'legislative-admin') {
      const chk = document.getElementById('analytics-autorefresh');
      const input = document.getElementById('analytics-interval');
      const apply = document.getElementById('analytics-apply');
      const enabled = localStorage.getItem('analytics_poll_enabled');
      const iv = localStorage.getItem('analytics_poll_interval');
      if (input && iv) input.value = iv;
      if (chk) chk.checked = enabled !== '0';
      apply?.addEventListener('click', function () {
        const enabledNow = chk && chk.checked;
        const secs = Math.max(5, parseInt(input?.value || '30', 10));
        localStorage.setItem('analytics_poll_enabled', enabledNow ? '1' : '0');
        localStorage.setItem('analytics_poll_interval', String(secs));
        if (enabledNow) startAnalyticsPolling(secs * 1000);
        else stopAnalyticsPolling();
        showToast('success', 'Analytics polling ' + (enabledNow ? 'enabled' : 'disabled'));
      });
      // start according to saved settings
      const startEnabled = (enabled !== '0');
      const startSecs = Math.max(5, parseInt(iv || '30', 10));
      if (startEnabled) startAnalyticsPolling(startSecs * 1000);
    }
  } catch (e) {
    console.warn('Polling init error', e);
  }
});

function closePanel() {
  const url = new URL(window.location.href);
  url.searchParams.delete('open');
  url.searchParams.delete('edit');
  url.searchParams.delete('confirm');
  url.searchParams.delete('type');
  url.searchParams.delete('id');
  window.location.replace(url.toString());
}

function openAIDraftPanel() {
  const overlay = document.querySelector('.overlay');
  const panel = document.getElementById('pnl-ai');
  if (!panel || !overlay) return;
  document.getElementById('ai-prompt').value = '';
  document.getElementById('ai-type').value = '';
  document.getElementById('ai-output').innerHTML = '<div class="empty-state" style="padding:18px"><div class="empty-txt">Enter a prompt and click Generate to see draft suggestions.</div></div>';
  overlay.classList.add('open');
  panel.classList.add('open');
}

function closeAIPanel() {
  const overlay = document.querySelector('.overlay');
  const panel = document.getElementById('pnl-ai');
  if (!panel || !overlay) return;
  panel.classList.remove('open');
  overlay.classList.remove('open');
}

function detectLegislativeType(prompt) {
  const text = (prompt || '').toLowerCase();
  if (!text) return '';
  const ordinanceKeywords = ['ordinance', 'law', 'regulation', 'code', 'zoning', 'penalty', 'permit', 'license', 'tax', 'fee', 'public safety', 'parking'];
  const resolutionKeywords = ['resolution', 'support', 'recognize', 'commend', 'declare', 'appoint', 'authorize', 'endorse', 'honor', 'celebrate', 'policy'];
  const ordScore = ordinanceKeywords.reduce((sum, keyword) => sum + (text.includes(keyword) ? 1 : 0), 0);
  const resScore = resolutionKeywords.reduce((sum, keyword) => sum + (text.includes(keyword) ? 1 : 0), 0);
  if (ordScore === 0 && resScore === 0) return '';
  return ordScore >= resScore ? 'ordinance' : 'resolution';
}

function generateAIDraft() {
  const prompt = document.getElementById('ai-prompt').value.trim();
  if (!prompt) {
    alert('Please enter a request for the draft.');
    return;
  }
  const typeInput = document.getElementById('ai-type').value;
  const resolvedType = typeInput || detectLegislativeType(prompt) || 'resolution';
  const title = prompt.length < 20 ? `${resolvedType.charAt(0).toUpperCase() + resolvedType.slice(1)} on ${prompt}` : `${resolvedType.charAt(0).toUpperCase() + resolvedType.slice(1)} for ${prompt.replace(/\.$/, '')}`;
  const desc = `This ${resolvedType} addresses ${prompt.toLowerCase()}. It aims to guide local government action and next steps.`;
  const content = `WHEREAS, ${prompt.charAt(0).toUpperCase() + prompt.slice(1)}.${prompt.endsWith('.') ? '' : '.'}\n\nNOW, THEREFORE, BE IT ${resolvedType === 'ordinance' ? 'ORDAINED' : 'RESOLVED'}, by the council that:\n1. The council hereby adopts this ${resolvedType} to address ${prompt.toLowerCase()}.\n2. The appropriate municipal officers are directed to take the necessary steps to implement this ${resolvedType}.\n3. This ${resolvedType} shall take effect immediately upon adoption.`;
  const output = document.getElementById('ai-output');
  output.innerHTML = `
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px">
      <div style="flex:1;min-width:180px"><strong>Detected type</strong><div style="margin-top:6px">${resolvedType}</div></div>
      <div style="flex:1;min-width:180px"><strong>Suggested title</strong><div style="margin-top:6px">${title}</div></div>
    </div>
    <div style="margin-bottom:14px"><strong>Description</strong><div style="margin-top:6px;white-space:pre-wrap">${desc}</div></div>
    <div><strong>Draft text</strong><div style="margin-top:6px;white-space:pre-wrap;line-height:1.6">${content}</div></div>
  `;
  document.getElementById('ai-type').value = resolvedType;
  output.dataset.draftTitle = title;
  output.dataset.draftDesc = desc;
  output.dataset.draftContent = content;
}

function applyAIDraftToForm() {
  const output = document.getElementById('ai-output');
  if (!output || !output.dataset.draftTitle) {
    alert('Generate a draft first.');
    return;
  }
  const title = output.dataset.draftTitle;
  const desc = output.dataset.draftDesc;
  const content = output.dataset.draftContent;
  const type = document.getElementById('ai-type').value || 'resolution';
  const titleInput = document.querySelector('form[action="index.php?page=legislative-user"] input[name="title"]');
  const typeInput = document.querySelector('form[action="index.php?page=legislative-user"] input[name="type"]');
  const descInput = document.querySelector('form[action="index.php?page=legislative-user"] textarea[name="desc"]');
  const contentInput = document.querySelector('form[action="index.php?page=legislative-user"] textarea[name="content"]');
  if (titleInput) titleInput.value = title;
  if (typeInput) typeInput.value = type;
  if (descInput) descInput.value = desc;
  if (contentInput) contentInput.value = content;
  closeAIPanel();
  alert('Draft copied into the legislative form.');
}

function fileChosen(file) {
  if (!file) {
    return;
  }
  const preview = document.getElementById('fp');
  const icon = document.getElementById('fp-ico');
  const name = document.getElementById('fp-name');
  const size = document.getElementById('fp-sz');
  icon.textContent = fileIcon(file.type);
  name.textContent = file.name;
  size.textContent = fmtSize(file.size);
  preview.style.display = 'flex';
}

function clearFile() {
  const input = document.getElementById('ag-file');
  if (input) {
    input.value = '';
  }
  const preview = document.getElementById('fp');
  preview.style.display = 'none';
}

function openTranscriptionPanel() {
  const overlay = document.getElementById('transcription-overlay');
  const panel = document.getElementById('pnl-transcription');
  if (!overlay || !panel) return;
  overlay.classList.add('open');
  panel.classList.add('open');
}

function closeTranscriptionPanel() {
  const overlay = document.getElementById('transcription-overlay');
  const panel = document.getElementById('pnl-transcription');
  if (overlay) overlay.classList.remove('open');
  if (panel) panel.classList.remove('open');
}

function copyTranscript() {
  const output = document.getElementById('transcription-output');
  if (!output || !output.value) return;
  navigator.clipboard.writeText(output.value).then(() => showToast('success', 'Transcript copied to clipboard.'));
}

document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('transcription-form');
  const output = document.getElementById('transcription-output');
  const status = document.getElementById('transcription-status');
  const start = document.getElementById('transcription-start');
  const label = document.getElementById('transcription-mic-label');
  const copy = document.getElementById('transcription-copy');
  const audioPlayer = document.getElementById('transcription-audio');
  const saveLinks = document.getElementById('transcription-save-links');
  if (!form || !start) return;

  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!navigator.mediaDevices || !window.MediaRecorder) {
    start.disabled = true;
    label.textContent = 'Audio recording is not supported in this browser.';
    status.textContent = 'Warning: use the latest Chrome or Edge for microphone recording.';
    status.style.color = '#ef4444';
    return;
  }

  const recognition = SpeechRecognition ? new SpeechRecognition() : null;
  if (recognition) {
    recognition.continuous = true;
    recognition.interimResults = true;
    recognition.lang = 'en-US';
  }
  let listening = false;
  let finalText = '';
  let mediaRecorder = null;
  let audioChunks = [];
  let audioStream = null;

  async function saveRecording(blob) {
    const data = new FormData();
    data.append('action', 'save_recording');
    data.append('transcript', output.value.trim());
    data.append('audio', blob, 'recording.' + (blob.type.includes('mp4') ? 'm4a' : 'webm'));
    status.textContent = 'Saving audio and transcript…';
    status.style.color = '#2563eb';
    try {
      const response = await fetch('index.php', { method: 'POST', body: data, credentials: 'same-origin' });
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.error || 'Unable to save the recording.');
      audioPlayer.src = result.audio_url;
      audioPlayer.style.display = 'block';
      saveLinks.innerHTML = '<a href="' + result.audio_download_url + '">Download audio</a><a href="' + result.transcript_download_url + '">Download transcript</a>';
      saveLinks.style.display = 'flex';
      status.textContent = 'Audio and transcript saved.';
      status.style.color = '#10b981';
    } catch (error) {
      status.textContent = 'Warning: ' + error.message;
      status.style.color = '#ef4444';
    }
  }

  start.addEventListener('click', async function () {
    if (listening) {
      if (recognition) recognition.stop();
      if (mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop();
      return;
    }
    try {
      audioStream = await navigator.mediaDevices.getUserMedia({ audio: true });
      const mimeType = MediaRecorder.isTypeSupported('audio/webm;codecs=opus') ? 'audio/webm;codecs=opus' : '';
      mediaRecorder = new MediaRecorder(audioStream, mimeType ? { mimeType } : undefined);
      audioChunks = [];
      mediaRecorder.ondataavailable = event => { if (event.data.size) audioChunks.push(event.data); };
      mediaRecorder.onstop = function () {
        listening = false;
        start.textContent = '🎙️ Start Recording';
        label.textContent = 'Ready to listen';
        audioStream.getTracks().forEach(track => track.stop());
        saveRecording(new Blob(audioChunks, { type: mediaRecorder.mimeType || 'audio/webm' }));
      };
      finalText = output.value.trim();
      status.textContent = SpeechRecognition ? 'Recording and transcribing… speak into your microphone.' : 'Recording audio…';
      status.style.color = '#2563eb';
      mediaRecorder.start();
      if (recognition) recognition.start();
      else {
        listening = true;
        start.textContent = '⏹ Stop Recording';
        label.textContent = 'Recording now…';
      }
    } catch (error) {
      status.textContent = error.name === 'NotAllowedError' ? 'Warning: allow microphone access in your browser.' : 'Warning: microphone unavailable.';
      status.style.color = '#ef4444';
    }
  });

  if (recognition) recognition.onstart = function () {
    listening = true;
    start.textContent = '⏹ Stop Recording';
    label.textContent = 'Recording and transcribing now…';
  };
  if (recognition) recognition.onresult = function (event) {
    let interimText = '';
    for (let index = event.resultIndex; index < event.results.length; index++) {
      const text = event.results[index][0].transcript;
      if (event.results[index].isFinal) finalText += (finalText ? ' ' : '') + text.trim();
      else interimText += text;
    }
    output.value = (finalText + (interimText ? (finalText ? ' ' : '') + interimText : '')).trim();
    copy.disabled = !output.value;
  };
  if (recognition) recognition.onend = function () {
    listening = false;
    start.textContent = '🎙️ Start Recording';
    label.textContent = 'Ready to listen';
    if (!mediaRecorder || mediaRecorder.state === 'inactive') {
      status.textContent = output.value ? 'Transcription complete.' : 'Ready to listen.';
      status.style.color = output.value ? '#10b981' : '#6b7280';
    }
  };
  if (recognition) recognition.onerror = function (event) {
    listening = false;
    start.textContent = '🎙️ Start Recording';
    label.textContent = 'Microphone unavailable';
    status.textContent = event.error === 'not-allowed' ? 'Warning: allow microphone access in your browser. Audio may still be saved.' : `Warning: ${event.error}.`;
    status.style.color = '#ef4444';
  };
});

function dzOver(event) {
  event.preventDefault();
  document.getElementById('dz').classList.add('dragover');
}

function dzLeave() {
  document.getElementById('dz').classList.remove('dragover');
}

function dzDrop(event) {
  event.preventDefault();
  document.getElementById('dz').classList.remove('dragover');
  const file = event.dataTransfer.files[0];
  if (!file) {
    return;
  }
  const input = document.getElementById('ag-file');
  const dataTransfer = new DataTransfer();
  dataTransfer.items.add(file);
  input.files = dataTransfer.files;
  fileChosen(file);
}

function fileIcon(type) {
  if (!type) {
    return '📄';
  }
  if (type.includes('pdf')) {
    return '📕';
  }
  if (type.includes('word') || type.includes('document')) {
    return '📘';
  }
  if (type.includes('presentation') || type.includes('powerpoint')) {
    return '📙';
  }
  if (type.includes('sheet') || type.includes('excel')) {
    return '📗';
  }
  if (type.includes('image')) {
    return '🖼️';
  }
  return '📄';
}

function fmtSize(bytes) {
  if (!bytes) {
    return '0 B';
  }
  const units = ['B', 'KB', 'MB', 'GB'];
  const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
  return (bytes / Math.pow(1024, index)).toFixed(1) + ' ' + units[index];
}

// PWA install prompt handling
let deferredPrompt = null;
window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt = e;
  console.log('PWA install prompt captured');
});

function promptInstall() {
  if (!deferredPrompt) {
    alert('Install prompt not available');
    return;
  }
  deferredPrompt.prompt();
  deferredPrompt.userChoice.then((choice) => {
    console.log('User choice', choice);
    deferredPrompt = null;
  });
}

// Fetch server data for offline use and store in localStorage
function syncOfflineData() {
  const fd = new FormData();
  fd.append('action','export_data');
  fetch('index.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(j => {
      if (j.success && j.data) {
        localStorage.setItem('kaya_offline_data', JSON.stringify(j.data));
        console.log('Offline data synced');
      } else {
        console.warn('Unable to sync offline data');
      }
    }).catch(e => console.warn('Sync failed', e));
}

// Simple toast for client-side feedback
function showToast(type, message, timeout = 3500) {
  let wrap = document.querySelector('.toast-wrap');
  if (!wrap) {
    wrap = document.createElement('div');
    wrap.className = 'toast-wrap';
    wrap.style.position = 'fixed';
    wrap.style.right = '12px';
    wrap.style.top = '12px';
    wrap.style.display = 'flex';
    wrap.style.flexDirection = 'column';
    wrap.style.gap = '8px';
    wrap.style.zIndex = '9999';
    document.body.appendChild(wrap);
  }
  const el = document.createElement('div');
  el.className = 'toast';
  el.style.padding = '10px 12px';
  el.style.borderRadius = '8px';
  el.style.color = '#fff';
  el.style.boxShadow = '0 6px 18px rgba(0,0,0,0.08)';
  if (type === 'success') el.style.background = '#10b981'; else if (type === 'error') el.style.background = '#ef4444'; else el.style.background = '#111827';
  el.textContent = message;
  wrap.appendChild(el);
  setTimeout(() => { try { el.remove(); } catch (e) {} }, timeout);
}

// Refresh analytics via AJAX and add a notification
function refreshAnalytics() {
  const fd = new FormData();
  fd.append('action', 'poll_analytics');
  fetch('index.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(j => {
      if (!j.success) {
        alert('Unable to refresh analytics: ' + (j.error || 'Unknown'));
        return;
      }
      if (j.analytics) {
        const a = j.analytics;
        const totalEl = document.getElementById('analytics-total');
        const appEl = document.getElementById('analytics-approved');
        const pctEl = document.getElementById('analytics-percent');
        if (totalEl) totalEl.textContent = a.total ?? '0';
        if (appEl) appEl.textContent = a.approved ?? '0';
        if (pctEl) pctEl.textContent = (a.percent_approved ?? '0') + '%';
      }
      if (j.attendance) {
        const container = document.getElementById('attendance-stats');
        if (container) {
          container.innerHTML = '';
          j.attendance.forEach(s => {
            const div = document.createElement('div');
            div.style.display = 'flex'; div.style.justifyContent = 'space-between'; div.style.padding = '6px 0'; div.style.borderBottom = '1px dashed #f3f4f6';
            div.innerHTML = '<div>' + (s.title || s.date) + ' - ' + (s.date || '') + '</div><div>' + (s.present || 0) + ' / ' + (s.council_total || 0) + ' (' + (s.percent_attendance || 0) + '%)</div>';
            container.appendChild(div);
          });
        }
      }

      // update notifications UI if provided
      if (Array.isArray(j.notifications)) {
        const dropdown = document.getElementById('notificationDropdown');
        const toggle = document.getElementById('notificationToggle');
        if (dropdown && toggle) {
          // rebuild small list (limit 6)
          dropdown.innerHTML = '';
          const limit = Math.min(j.notifications.length, 6);
          for (let i = 0; i < limit; i++) {
            const n = j.notifications[i];
            const a = document.createElement('a');
            a.className = 'notification-item';
            a.href = n.url || 'index.php?page=dashboard';
            a.innerHTML = '<div class="notification-message">' + (n.message || '') + '</div><div class="notification-time">' + formatAppDate(n.created_at) + '</div>';
            dropdown.appendChild(a);
          }
          const viewAll = document.createElement('a');
          viewAll.className = 'notification-item notification-view-all';
          viewAll.href = 'index.php?page=tablet';
          viewAll.textContent = 'View all notifications';
          dropdown.appendChild(viewAll);

          // update unread count badge
          const unread = j.unread || 0;
          let badge = toggle.querySelector('.notification-count');
          if (badge) {
            if (unread > 0) badge.textContent = unread; else badge.remove();
          } else if (unread > 0) {
            const span = document.createElement('span');
            span.className = 'notification-count';
            span.textContent = unread;
            toggle.appendChild(span);
          }
        }
      }
    }).catch(e => {
      console.warn('Refresh analytics failed', e);
      alert('Unable to refresh analytics (network error)');
    });
}

// debounce wrapper for refresh to avoid rapid duplicate calls
refreshAnalytics = (function () {
  let timer = null;
  let inFlight = false;
  const delay = 1500;
  return function () {
    if (inFlight) return; // prevent concurrent
    clearTimeout(timer);
    timer = setTimeout(() => {
      inFlight = true;
      const original = (function inner() { return Promise.resolve(); })();
      // run actual logic defined above by name _refreshAnalyticsImpl
      _refreshAnalyticsImpl().finally(() => { inFlight = false; });
    }, delay);
  };
}());

// create a private implementation name used by the debounce wrapper
function _refreshAnalyticsImpl() {
  const fd = new FormData();
  fd.append('action', 'poll_analytics');
  return fetch('index.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(j => {
      if (!j.success) {
        alert('Unable to refresh analytics: ' + (j.error || 'Unknown'));
        return;
      }
      if (j.analytics) {
        const a = j.analytics;
        const totalEl = document.getElementById('analytics-total');
        const appEl = document.getElementById('analytics-approved');
        const pctEl = document.getElementById('analytics-percent');
        if (totalEl) totalEl.textContent = a.total ?? '0';
        if (appEl) appEl.textContent = a.approved ?? '0';
        if (pctEl) pctEl.textContent = (a.percent_approved ?? '0') + '%';
      }
      if (j.attendance) {
        const container = document.getElementById('attendance-stats');
        if (container) {
          container.innerHTML = '';
          j.attendance.forEach(s => {
            const div = document.createElement('div');
            div.style.display = 'flex'; div.style.justifyContent = 'space-between'; div.style.padding = '6px 0'; div.style.borderBottom = '1px dashed #f3f4f6';
            div.innerHTML = '<div>' + (s.title || s.date) + ' - ' + (s.date || '') + '</div><div>' + (s.present || 0) + ' / ' + (s.council_total || 0) + ' (' + (s.percent_attendance || 0) + '%)</div>';
            container.appendChild(div);
          });
        }
      }

      // update notifications UI if provided (replace instead of append to avoid duplicates)
      if (Array.isArray(j.notifications)) {
        const dropdown = document.getElementById('notificationDropdown');
        const toggle = document.getElementById('notificationToggle');
        if (dropdown && toggle) {
          dropdown.innerHTML = '';
          const header = document.createElement('div');
          header.style.display = 'flex'; header.style.justifyContent = 'space-between'; header.style.alignItems = 'center'; header.style.padding = '8px'; header.style.borderBottom = '1px solid #f3f4f6';
          header.innerHTML = '<div style="font-weight:600">Notifications</div>';
          const markBtn = document.createElement('button');
          markBtn.className = 'btn btn-ghost';
          markBtn.id = 'markAllNotifications';
          markBtn.textContent = 'Mark all read';
          header.appendChild(markBtn);
          dropdown.appendChild(header);

          const limit = Math.min(j.notifications.length, 6);
          for (let i = 0; i < limit; i++) {
            const n = j.notifications[i];
            const a = document.createElement('a');
            a.className = 'notification-item';
            a.href = n.url || 'index.php?page=dashboard';
            a.innerHTML = '<div class="notification-message">' + (n.message || '') + '</div><div class="notification-time">' + formatAppDate(n.created_at) + '</div>';
            dropdown.appendChild(a);
          }
          const viewAll = document.createElement('a');
          viewAll.className = 'notification-item notification-view-all';
          viewAll.href = 'index.php?page=tablet';
          viewAll.textContent = 'View all notifications';
          dropdown.appendChild(viewAll);

          const unread = j.unread || 0;
          let badge = toggle.querySelector('.notification-count');
          if (badge) {
            if (unread > 0) badge.textContent = unread; else badge.remove();
          } else if (unread > 0) {
            const span = document.createElement('span');
            span.className = 'notification-count';
            span.textContent = unread;
            toggle.appendChild(span);
          }
        }
      }
    }).catch(e => {
      console.warn('Refresh analytics failed', e);
    });
}

// Mark all notifications read
function markAllNotificationsRead() {
  const fd = new FormData();
  fd.append('action', 'mark_notifications_read');
  fetch('index.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(j => {
      console.log('mark_notifications_read response', j);
      if (!j.success) {
        alert('Unable to mark notifications read: ' + (j.error || 'Unknown'));
        return;
      }
      const dropdown = document.getElementById('notificationDropdown');
      const toggle = document.getElementById('notificationToggle');
      if (dropdown) {
        dropdown.innerHTML = '<div style="padding:12px;color:#6b7280">All notifications marked read.</div><a class="notification-item notification-view-all" href="index.php?page=tablet">View all notifications</a>';
      }
      if (toggle) {
        const badge = toggle.querySelector('.notification-count');
        if (badge) badge.remove();
      }
      showToast('success', 'All notifications marked read');
    }).catch(e => alert('Network error'));
}

// hook up mark all button (delegate for dynamic content)
// REMOVED - functionality no longer needed

// Remove notification when clicked and navigate to URL
function removeAndNavigateNotification(event, message) {
  const url = event.currentTarget.getAttribute('href');
  
  const fd = new FormData();
  fd.append('action', 'remove_notification');
  fd.append('message', message);
  
  fetch('index.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(j => {
      console.log('remove_notification response', j);
      if (j.success) {
        // Update notification count if available
        const toggle = document.getElementById('notificationToggle');
        if (toggle) {
          const badge = toggle.querySelector('.notification-count');
          if (badge) {
            const count = parseInt(badge.textContent) - 1;
            if (count > 0) {
              badge.textContent = count;
            } else {
              badge.remove();
            }
          }
        }
        // Update dropdown
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown && j.notifications) {
          let html = '<div style="display:flex;justify-content:space-between;align-items:center;padding:8px;border-bottom:1px solid #f3f4f6"><div style="font-weight:600">Notifications</div></div>';
          if (j.notifications.length === 0) {
            html += '<div style="padding:12px;color:#6b7280">No notifications</div>';
          } else {
            j.notifications.forEach(n => {
              html += '<a class="notification-item" href="' + (n.url || 'index.php?page=dashboard') + '" onclick="removeAndNavigateNotification(event, \'' + n.message.replace(/'/g, "\\'") + '\')">' +
                      '<div class="notification-message">' + n.message + '</div>' +
                      '<div class="notification-time">' + n.created_at + '</div>' +
                      '</a>';
            });
          }
          html += '<a class="notification-item notification-view-all" href="index.php?page=tablet">View all notifications</a>';
          dropdown.innerHTML = html;
        }
      }
      // Navigate to the URL
      window.location.href = url;
    })
    .catch(e => {
      console.error('Error removing notification:', e);
      // Still navigate even if removal fails
      window.location.href = url;
    });
}

// Session Control: agenda voting, attendance polling, and session minutes status.
document.addEventListener('DOMContentLoaded', function () {
  const pageLoader = document.getElementById('pageLoader');
  window.currentPage = document.body?.dataset.page || '';
  window.showPageLoader = function () { if (pageLoader) pageLoader.classList.add('show'); };
  document.querySelectorAll('form').forEach((form) => { if (form.id !== 'transcription-form') form.addEventListener('submit', window.showPageLoader); });
  document.querySelectorAll('.ledger-record').forEach((link) => link.addEventListener('click', window.showPageLoader));
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('/public/service-worker.js').catch(function (error) { console.warn('ServiceWorker registration failed:', error); });
    });
  }

  const emailField = document.getElementById('email');
  const modeField = document.getElementById('mode');
  let roleLookupTimer;
  const updateLoginRole = () => {
    if (!emailField || !modeField || !emailField.value.trim() || !emailField.checkValidity()) return;
    fetch('index.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action: 'lookup_login_role', email: emailField.value.trim() }) })
      .then((response) => response.json()).then(({ role }) => {
        const loginRole = role === 'mayor' ? 'lce' : role;
        if (loginRole && Array.from(modeField.options).some((option) => option.value === loginRole)) modeField.value = loginRole;
      }).catch(() => {});
  };
  emailField?.addEventListener('blur', updateLoginRole);
  emailField?.addEventListener('input', () => { clearTimeout(roleLookupTimer); roleLookupTimer = setTimeout(updateLoginRole, 350); });

  const sessionPage = document.getElementById('pg-session-control');
  if (!sessionPage) return;
  const currentSessionId = sessionPage.dataset.sessionId || '';
  let selectedAgendaId = '';
  const el = (id) => document.getElementById(id);

  function renderTally(tally) {
    const target = el('tallyDisplay');
    if (!target) return;
    target.textContent = `FAVOR: ${tally.FAVOR || 0}, AGAINST: ${tally.AGAINST || 0}, ABSTAIN: ${tally.ABSTAIN || 0} — TOTAL: ${tally.total || 0}`;
  }

  function fetchTally() {
    if (!selectedAgendaId) return;
    fetch('index.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action: 'poll_votes', agenda_id: selectedAgendaId }), credentials: 'same-origin' })
      .then((response) => response.json()).then((result) => { if (result.success) renderTally(result.tally); }).catch(() => {});
  }

  function fetchPresence() {
    if (!currentSessionId) return;
    fetch('index.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action: 'poll_presence', session_id: currentSessionId }), credentials: 'same-origin' })
      .then((response) => response.json()).then((result) => {
        if (!result.success) return;
        const list = result.presence || [];
        const count = el('attendanceCount');
        if (count) count.textContent = list.length;
        const request = result.request;
        const checkIn = el('checkInBtn');
        const requestStatus = el('attendanceRequestStatus');
        if (checkIn && requestStatus && request) {
          if (request.status === 'pending') { checkIn.disabled = true; checkIn.textContent = 'Request Pending'; requestStatus.textContent = 'Waiting for admin confirmation.'; }
          if (request.status === 'approved') { checkIn.disabled = true; checkIn.textContent = '✓ Attendance Approved'; requestStatus.textContent = 'You are marked present for this session.'; }
          if (request.status === 'rejected') { checkIn.disabled = false; checkIn.textContent = '✓ Request Attendance Again'; requestStatus.textContent = 'Your previous attendance request was declined.'; }
        }
        const presence = el('presenceList');
        if (presence) presence.innerHTML = list.map((person) => `<div class="presence-entry">${person.name || person.user_id} <span class="presence-entry-time">${person.present_at}</span></div>`).join('') || '<div>No one checked in yet.</div>';
      }).catch(() => {});
  }

  document.querySelectorAll('.select-agenda').forEach((button) => button.addEventListener('click', function () {
    selectedAgendaId = this.dataset.id || '';
    const title = el('currentAgendaTitle');
    const controls = el('votingControls');
    if (title) title.textContent = this.textContent.trim();
    if (controls) controls.style.display = 'block';
    fetchTally();
  }));
  document.querySelectorAll('.voteBtn').forEach((button) => button.addEventListener('click', function () {
    if (!selectedAgendaId) { alert('Select an agenda first'); return; }
    fetch('index.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action: 'cast_vote', agenda_id: selectedAgendaId, session_id: currentSessionId, vote: this.dataset.vote || '' }), credentials: 'same-origin' })
      .then((response) => response.json()).then((result) => { if (result.success) renderTally(result.tally); else alert(result.error || 'Vote failed'); }).catch(() => {});
  }));
  const checkIn = el('checkInBtn');
  if (checkIn) checkIn.addEventListener('click', function () {
    if (!currentSessionId) { alert('No active session'); return; }
    fetch('index.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action: 'set_presence', session_id: currentSessionId, device_id: navigator.userAgent.slice(0, 60) }), credentials: 'same-origin' })
      .then((response) => response.json()).then((result) => { if (result.success) fetchPresence(); else alert(result.error || 'Attendance request failed'); }).catch(() => {});
  });
  setInterval(() => { fetchTally(); fetchPresence(); }, 3000);
  fetchPresence();
});
