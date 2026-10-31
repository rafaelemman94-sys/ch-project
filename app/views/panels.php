<div class="overlay<?= $openPanel || ($confirm === 'delete' && $confirmItem) ? ' open' : '' ?>" onclick="closePanel()"></div>

<div class="slide-panel<?= $openPanel === 'user' ? ' open' : '' ?>" id="pnl-user">
  <div class="panel-head">
    <div><div class="panel-title"><?= $editItem && $openPanel === 'user' ? 'Edit User' : 'Create User' ?></div><div class="panel-subtitle">Fill in the user details below</div></div>
    <button class="panel-close" type="button" onclick="closePanel()">✕</button>
  </div>
  <form class="panel-body" method="post" action="index.php?page=users" autocomplete="off">
    <input type="hidden" name="action" value="save_user" />
    <input type="hidden" name="id" value="<?= esc($editItem['id'] ?? '') ?>" />
    <div class="fg-row">
      <div class="fg"><label>Full Name *</label><input type="text" name="name" value="<?= esc($editItem['name'] ?? '') ?>" placeholder="Jane Smith" required /></div>
      <div class="fg"><label>Email Address *</label><input type="email" name="email" value="<?= esc($editItem['email'] ?? '') ?>" placeholder="jane@example.com" required /></div>
    </div>
    <div class="fg-row">
      <div class="fg"><label>Password <span id="u-pw-note" style="font-weight:400;text-transform:none;font-size:10px;color:#4a5568"><?= $editItem ? '(leave blank to keep)' : '' ?></span></label><div class="password-field"><input type="password" name="password" placeholder="Min. 6 characters, 1 special character" autocomplete="new-password" <?= $editItem ? '' : 'required' ?> /><button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false" title="Show password">
          <span class="password-toggle-icon password-toggle-show" aria-hidden="true">👁</span>
          <span class="password-toggle-icon password-toggle-hide" aria-hidden="true">🙈</span>
        </button></div></div>
      <div class="fg"><label>Role</label><select name="role">
          <option value="council members" <?= isset($editItem['role']) && $editItem['role'] === 'council members' ? 'selected' : '' ?>>Council Members</option>
          <option value="presiding officer" <?= isset($editItem['role']) && in_array($editItem['role'], ['presiding officer', 'proceeding officer'], true) ? 'selected' : '' ?>>Presiding Officer</option>
          <option value="ctrfb" <?= isset($editItem['role']) && $editItem['role'] === 'ctrfb' ? 'selected' : '' ?>>CTFRB</option>
          <option value="lce" <?= isset($editItem['role']) && in_array($editItem['role'], ['lce', 'mayor'], true) ? 'selected' : '' ?>>Local Chief Executive / Mayor</option>
          <option value="admin" <?= isset($editItem['role']) && $editItem['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select></div>
    </div>
    <div class="fg"><label>Account Status</label><select name="status"><option value="active" <?= !isset($editItem['status']) || $editItem['status'] === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= isset($editItem['status']) && $editItem['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option><option value="suspended" <?= isset($editItem['status']) && $editItem['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option></select></div>
    <div class="fg"><label>Department / Notes</label><textarea name="notes" placeholder="Optional department or notes…"><?= esc($editItem['notes'] ?? '') ?></textarea></div>
  </form>
  <div class="panel-foot"><button class="btn btn-secondary" type="button" onclick="closePanel()">Cancel</button><button class="btn btn-primary" onclick="document.querySelector('#pnl-user form').submit()">💾 <span id="u-save-lbl"><?= $editItem ? 'Update User' : 'Create User' ?></span></button></div>
</div>

<div class="slide-panel<?= $openPanel === 'session' ? ' open' : '' ?>" id="pnl-sess">
  <div class="panel-head">
    <div><div class="panel-title"><?= $editItem && $openPanel === 'session' ? 'Edit Session' : 'Schedule Session' ?></div><div class="panel-subtitle">Configure session details and timing</div></div>
    <button class="panel-close" type="button" onclick="closePanel()">✕</button>
  </div>
  <form class="panel-body" method="post" action="index.php?page=sessions">
    <input type="hidden" name="action" value="save_session" />
    <input type="hidden" name="id" value="<?= esc($editItem['id'] ?? '') ?>" />
    <div class="fg"><label>Session Title *</label><input type="text" name="title" value="<?= esc($editItem['title'] ?? '') ?>" placeholder="e.g. Q3 Planning Workshop" required /></div>
    <div class="fg"><label>Description</label><textarea name="description" placeholder="Brief overview of the session…"><?= esc($editItem['desc'] ?? '') ?></textarea></div>
    <div class="fg-row"><div class="fg"><label>Date *</label><input type="date" name="date" value="<?= esc($editItem['date'] ?? '') ?>" required /></div><div class="fg"><label>Location / Room</label><input type="text" name="location" value="<?= esc($editItem['location'] ?? '') ?>" placeholder="Conference Room A" /></div></div>
    <div class="fg-row"><div class="fg"><label>Start Time *</label><input type="text" name="start" value="<?= !empty($editItem['start']) ? esc(date('g:i A', strtotime((string)$editItem['start']))) : '' ?>" placeholder="e.g. 9:30 AM" inputmode="text" autocomplete="off" required /></div><div class="fg"><label>End Time *</label><input type="text" name="end" value="<?= !empty($editItem['end']) ? esc(date('g:i A', strtotime((string)$editItem['end']))) : '' ?>" placeholder="e.g. 11:00 AM" inputmode="text" autocomplete="off" required /></div></div>
    <div class="fg-row"><div class="fg"><label>Capacity</label><input type="number" name="capacity" min="0" value="<?= esc($editItem['capacity'] ?? '0') ?>" placeholder="0 = unlimited" /></div><div class="fg"><label>Status</label><select name="status"><option value="scheduled" <?= !isset($editItem['status']) || $editItem['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option><option value="ongoing" <?= isset($editItem['status']) && $editItem['status'] === 'ongoing' ? 'selected' : '' ?>>Ongoing</option><option value="completed" <?= isset($editItem['status']) && $editItem['status'] === 'completed' ? 'selected' : '' ?>>Completed</option><option value="cancelled" <?= isset($editItem['status']) && $editItem['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option></select></div></div>
    <div class="fg"><label>Admin Minutes</label><textarea name="minutes" placeholder="Type meeting minutes or action notes here…"><?= esc($editItem['minutes'] ?? '') ?></textarea></div>
  </form>
  <div class="panel-foot"><button class="btn btn-secondary" type="button" onclick="closePanel()">Cancel</button><button class="btn btn-primary" onclick="document.querySelector('#pnl-sess form').submit()">📅 <span id="s-save-lbl"><?= $editItem ? 'Update Session' : 'Schedule Session' ?></span></button></div>
</div>

<div class="slide-panel<?= $openPanel === 'agenda' ? ' open' : '' ?>" id="pnl-ag">
  <div class="panel-head">
    <div><div class="panel-title">Upload Agenda</div><div class="panel-subtitle">Attach a document or file to this agenda</div></div>
    <button class="panel-close" type="button" onclick="closePanel()">✕</button>
  </div>
  <form class="panel-body" method="post" action="index.php?page=agendas" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save_agenda" />
    <div class="fg"><label>Agenda Title *</label><input type="text" name="title" value="<?= esc($editItem['title'] ?? '') ?>" placeholder="e.g. Q3 Meeting Agenda" required /></div>
    <div class="fg"><label>Link to Session (optional)</label><select name="session_id"><option value="">— None —</option><?php foreach ($sessionOptions as $sessionOption): ?><option value="<?= esc($sessionOption['id']) ?>" <?= isset($editItem['session_id']) && $editItem['session_id'] === $sessionOption['id'] ? 'selected' : '' ?>><?= esc($sessionOption['title']) ?> (<?= esc($sessionOption['date']) ?>)</option><?php endforeach; ?></select></div>
    <div class="fg"><label>File *</label><div class="drop-zone" id="dz" ondragover="dzOver(event)" ondragleave="dzLeave()" ondrop="dzDrop(event)">
        <input type="file" id="ag-file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.jpg,.jpeg,.png" onchange="fileChosen(this.files[0])" required />
        <div class="dz-icon">📂</div>
        <div class="dz-text">Click to browse or drag & drop</div>
        <div class="dz-hint">PDF, Word, PowerPoint, Excel, images • Max 10MB</div>
      </div>
      <div style="margin-top:10px"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="notify_council" value="1" /> <span>Notify Council Members after upload (auto-approve)</span></label></div>
      <div id="fp" class="file-preview" style="display:none">
        <span class="fp-icon" id="fp-ico">📄</span>
        <div class="fp-info"><div class="fp-name" id="fp-name"></div><div class="fp-size" id="fp-sz"></div></div>
        <button class="fp-rm" type="button" onclick="clearFile()">✕</button>
      </div>
    </div>
  </form>
  <div class="panel-foot"><button class="btn btn-secondary" type="button" onclick="closePanel()">Cancel</button><button class="btn btn-primary" onclick="document.querySelector('#pnl-ag form').submit()">⬆️ Upload Agenda</button></div>
</div>

<div class="slide-panel" id="pnl-ai">
  <div class="panel-head">
    <div><div class="panel-title">AI Draft Assistant</div><div class="panel-subtitle">Generate a draft resolution or ordinance in seconds</div></div>
    <button class="panel-close" type="button" onclick="closeAIPanel()">✕</button>
  </div>
  <div class="panel-body">
    <div class="fg"><label>Draft request</label><textarea id="ai-prompt" placeholder="Describe what you want to create, e.g. support youth employment, update park safety rules, fund rehabilitation projects" style="min-height:100px"></textarea></div>
    <div class="fg-row" style="gap:10px;align-items:flex-end">
      <div class="fg" style="flex:1"><label>Type</label><select id="ai-type"><option value="">Auto detect</option><option value="resolution">Resolution</option><option value="ordinance">Ordinance</option></select></div>
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <button class="btn btn-ghost" type="button" onclick="generateAIDraft()">Generate</button>
        <button class="btn btn-primary" type="button" onclick="applyAIDraftToForm()">Use in Form</button>
      </div>
    </div>
    <div class="ai-output" id="ai-output" style="margin-top:18px;padding:16px;border:1px solid rgba(255,255,255,.08);border-radius:12px;background:rgba(255,255,255,.03);min-height:180px;color:#cbd5e1">
      <div class="empty-state" style="padding:18px"><div class="empty-txt">Enter a prompt and click Generate to see draft suggestions.</div></div>
    </div>
  </div>
  <div class="panel-foot"><button class="btn btn-secondary" type="button" onclick="closeAIPanel()">Close</button></div>
</div>

<div class="overlay" id="transcription-overlay" onclick="closeTranscriptionPanel()"></div>
<div class="slide-panel" id="pnl-transcription">
  <div class="panel-head">
    <div><div class="panel-title">Voice to Text</div><div class="panel-subtitle">Speak into your microphone and convert your words into editable text</div></div>
    <button class="panel-close" type="button" onclick="closeTranscriptionPanel()">✕</button>
  </div>
  <form class="panel-body" id="transcription-form">
    <div class="fg">
      <label>Microphone</label>
      <div class="drop-zone" id="transcription-mic-status">
        <div class="dz-icon">🎙️</div>
        <div class="dz-text" id="transcription-mic-label">Ready to listen</div>
        <div class="dz-hint">One recording captures your voice and live transcript together.</div>
      </div>
    </div>
    <div class="fg">
      <label>Transcript</label>
      <textarea id="transcription-output" placeholder="Your spoken words will appear here…" style="min-height:230px"></textarea>
    </div>
    <div id="transcription-status" style="font-size:12px;color:#6b7280;min-height:18px" aria-live="polite"></div>
    <audio id="transcription-audio" controls style="display:none;width:100%;margin-top:8px"></audio>
    <div id="transcription-save-links" style="display:none;font-size:12px;gap:10px;flex-wrap:wrap"></div>
  </form>
  <div class="panel-foot">
    <button class="btn btn-secondary" type="button" onclick="closeTranscriptionPanel()">Close</button>
    <button class="btn btn-ghost" type="button" id="transcription-copy" onclick="copyTranscript()" disabled>📋 Copy Text</button>
    <button class="btn btn-primary" type="button" id="transcription-start">🎙️ Start Listening</button>
  </div>
</div>

<div id="confirm-box" class="<?= $confirm === 'delete' && $confirmItem ? 'open' : '' ?>">  <div class="confirm-card">
    <div class="confirm-title">Confirm Delete</div>
    <div class="confirm-msg">Are you sure you want to delete "<?= esc($confirmItem['name'] ?? $confirmItem['title'] ?? '') ?>"? This cannot be undone.</div>
    <form class="confirm-btns" method="post" action="index.php?page=<?= esc($page) ?>">
      <input type="hidden" name="action" value="delete_item" />
      <input type="hidden" name="item_type" value="<?= esc($editType ?? '') ?>" />
      <input type="hidden" name="item_id" value="<?= esc($confirmItem['id'] ?? '') ?>" />
      <button class="btn btn-ghost" type="button" onclick="closePanel()">Cancel</button>
      <button class="btn btn-danger" type="submit">Delete</button>
    </form>
  </div>
</div>
