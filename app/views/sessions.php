<div id="pg-sessions" class="page active">
  <?php if (!empty($attendanceRequests)): ?>
  <div class="card" style="margin-bottom:12px" id="attendance-requests-card">
    <div class="card-head"><span class="card-title">Attendance Requests <span style="color:#4a5568;font-weight:400">(<?= count($attendanceRequests) ?>)</span></span></div>
    <div style="display:grid;gap:8px;padding:14px">
      <?php foreach ($attendanceRequests as $request): ?>
        <div class="attendance-request-row" data-request-id="<?= esc($request['id']) ?>" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:11px 12px;border:1px solid #e5e7eb;border-radius:8px">
          <div>
            <strong><?= esc($request['name'] ?? 'Council member') ?></strong>
            <div style="font-size:12px;color:#6b7280"><?= esc($request['session_title'] ?? 'Session') ?> · <?= esc($request['date'] ?? '') ?> <?= esc($request['start'] ?? '') ?></div>
          </div>
          <div style="display:flex;gap:7px">
            <button class="btn btn-primary btn-xs attendance-decision" type="button" data-status="approved">Yes</button>
            <button class="btn btn-danger btn-xs attendance-decision" type="button" data-status="rejected">No</button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
  <div style="display:flex;justify-content:flex-end;margin-bottom:12px;gap:8px">
    <?php if (!empty($sendMinutesSessions)): ?><button class="btn btn-primary btn-xs" type="button" onclick="openSendMinutesModal('', '')">📝 Send for Approval</button><?php endif; ?>
    <button class="btn btn-secondary btn-xs" type="button" onclick="openDeliveryHistoryModal()">🕘 History</button>
  </div>
  <div class="card">
    <div class="card-head"><span class="card-title">Session Schedule <span id="s-cnt" style="color:#4a5568;font-weight:400">(<?= count($sessions) ?>)</span></span></div>
    <table>
      <thead><tr><th>#</th><th>Title</th><th>Date</th><th>Time</th><th>Location</th><th>Cap.</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if (count($sessions) === 0): ?>
          <tr><td colspan="8"></td></tr>
        <?php else: ?>
          <?php $i = 1; foreach ($sessions as $session): ?>
            <tr>
              <td style="color:#3d4363"><?= $i++ ?></td>
              <td><div style="font-weight:600;font-size:13px"><?= esc($session['title']) ?></div><?= $session['desc'] ? '<div style="font-size:10.5px;color:#4a5568;margin-top:1px">' . esc(mb_substr($session['desc'], 0, 60)) . (mb_strlen($session['desc']) > 60 ? '…' : '') . '</div>' : '' ?></td>
              <td style="font-size:12.5px;font-weight:600;color:#c4c9f0"><?= esc($session['date']) ?></td>
              <td style="color:#6b7280;font-size:12px"><?= esc(date('g:i A', strtotime((string)$session['start']))) ?>–<?= esc(date('g:i A', strtotime((string)$session['end']))) ?></td>
              <td style="color:#9ca3af;font-size:12px"><?= esc($session['location'] ?: '—') ?></td>
              <td style="text-align:center;color:#6b7280"><?= $session['capacity'] !== 0 ? esc($session['capacity']) : '∞' ?></td>
              <td><span class="badge <?= badgeClass($session['status']) ?>"><?= esc($session['status']) ?></span></td>
              <td><div class="row-actions"><a class="btn btn-ghost btn-xs" href="?page=sessions&open=session&type=session&edit=<?= esc($session['id']) ?>">✏️ Edit</a><?php if (isset($sendMinutesSessionIds[(string)$session['id']])): ?><button class="btn btn-secondary btn-xs" type="button" onclick="openSendMinutesModal('<?= esc($session['id']) ?>', '<?= esc($session['title']) ?>')">📝 Send for Approval</button><?php endif; ?><a class="btn btn-danger btn-xs" href="?page=sessions&confirm=delete&type=session&id=<?= esc($session['id']) ?>">🗑️</a></div></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
    <?php if (count($sessions) === 0): ?>
      <div class="empty-state"><div class="empty-icon">📅</div><div class="empty-txt">No sessions scheduled. Click <strong>+ Schedule Session</strong> to add one.</div></div>
    <?php endif; ?>
  </div>
</div>

<div class="overlay" id="send-minutes-overlay" onclick="closeSendMinutesModal()"></div>
<div class="slide-panel" id="send-minutes-panel" style="max-width:780px;width:min(95vw,780px)">
  <div class="panel-head">
    <div>
      <div class="panel-title">Send Minutes for Approval</div>
      <div class="panel-subtitle" id="send-minutes-subtitle"></div>
    </div>
    <button class="panel-close" type="button" onclick="closeSendMinutesModal()">✕</button>
  </div>
  <form class="panel-body" method="post" action="index.php?page=sessions" id="send-minutes-form">
    <input type="hidden" name="action" value="send_session_minutes" />
    <div class="fg">
      <label for="send-minutes-session-id">Session *</label>
      <select name="session_id" id="send-minutes-session-id" required onchange="updateSendMinutesSubtitle()">
        <?php foreach ($sendMinutesSessions as $session): ?>
          <option value="<?= esc($session['id']) ?>"><?= esc($session['title']) ?> · <?= esc($session['date']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="fg">
      <label style="font-weight:600;margin-bottom:12px;display:block">Presiding Officer *</label>
      <div style="display:grid;gap:8px">
        <?php $minuteRecipients = array_filter($users ?? [], fn($u) => ($u['role'] ?? '') === 'presiding officer' && ($u['status'] ?? 'active') === 'active'); foreach ($minuteRecipients as $recipient): ?>
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:8px;border-radius:6px;border:1px solid #e5e7eb">
            <input type="checkbox" name="recipient_ids[]" value="<?= esc($recipient['id']) ?>" />
            <span style="flex:1"><?= esc($recipient['name']) ?></span>
            <span style="font-size:11px;color:#9ca3af"><?= esc($recipient['role']) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="fg">
      <label>Delivery Template (Optional)</label>
      <select name="template_id">
        <option value="">No template</option>
        <?php foreach (($data['delivery_templates'] ?? []) as $template): ?>
          <option value="<?= esc($template['id']) ?>"><?= esc($template['name'] ?: $template['file_name']) ?> (<?= esc($template['template_type'] ?: 'general') ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="fg">
      <label>Optional Note</label>
      <textarea name="notes" placeholder="Add extra context for the minute delivery…" style="min-height:95px"></textarea>
    </div>
  </form>
  <div class="panel-foot">
    <button class="btn btn-secondary" type="button" onclick="closeSendMinutesModal()">Cancel</button>
    <button class="btn btn-primary" type="submit" form="send-minutes-form" onclick="return validateMinutesRecipients()">📨 Send to Presiding Officer</button>
  </div>
</div>

<div class="overlay" id="delivery-history-overlay" onclick="closeDeliveryHistoryModal()"></div>
<div class="slide-panel" id="delivery-history-panel" style="max-width:980px;width:min(95vw,980px)">
  <div class="panel-head">
    <div>
      <div class="panel-title">Delivery History</div>
      <div class="panel-subtitle">Printable log of agenda and session minute deliveries</div>
    </div>
    <button class="panel-close" type="button" onclick="closeDeliveryHistoryModal()">✕</button>
  </div>
  <div class="panel-body" style="display:grid;gap:12px">
    <form method="post" action="index.php?page=sessions" enctype="multipart/form-data" style="display:grid;gap:8px;padding:12px;border:1px solid #e5e7eb;border-radius:8px;background:#f9fafb">
      <input type="hidden" name="action" value="upload_delivery_template" />
      <div style="font-weight:700;color:#111827">Upload History Template</div>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:8px">
        <input type="text" name="template_name" placeholder="Template name" />
        <input type="text" name="template_type" placeholder="Template type" value="session" />
      </div>
      <input type="file" name="template_file" accept=".doc,.docx,.pdf,.txt,.rtf" />
      <button class="btn btn-secondary btn-xs" type="submit">⬆️ Upload Template</button>
    </form>

    <?php $historyEntries = array_values(array_filter($data['delivery_history'] ?? [], fn($entry) => in_array($entry['item_type'] ?? '', ['agenda', 'session_minutes'], true))); ?>
    <?php if (count($historyEntries) === 0): ?>
      <div style="padding:16px;border:1px dashed #d1d5db;border-radius:8px;color:#6b7280">No delivery history yet.</div>
    <?php else: ?>
      <?php $historyGroups = []; foreach ($historyEntries as $entry) { $templateKey = !empty($entry['template_name']) ? $entry['template_name'] : (!empty($entry['template_type']) ? $entry['template_type'] : 'general'); $templateLabel = !empty($entry['template_name']) ? $entry['template_name'] : (!empty($entry['template_type']) ? ucfirst($entry['template_type']) : 'General'); if (!isset($historyGroups[$templateKey])) { $historyGroups[$templateKey] = ['label' => $templateLabel, 'entries' => []]; } $historyGroups[$templateKey]['entries'][] = $entry; } foreach ($historyGroups as $group): ?>
        <div style="border:1px solid #e5e7eb;border-radius:10px;padding:12px;background:#fff">
          <div style="font-weight:700;color:#111827;margin-bottom:8px">Template: <?= esc($group['label']) ?></div>
          <div style="display:grid;gap:8px">
            <?php foreach ($group['entries'] as $entry): ?>
              <?php $recipientIds = json_decode($entry['recipient_ids'] ?? '[]', true); if (!is_array($recipientIds)) { $recipientIds = []; } $recipientNames = json_decode($entry['recipient_names'] ?? '[]', true); if (!is_array($recipientNames)) { $recipientNames = []; } $recipientDisplay = count($recipientNames) > 0 ? $recipientNames : array_map(fn($id) => 'ID ' . $id, $recipientIds); ?>
              <div style="border:1px solid #e5e7eb;border-radius:8px;padding:10px;background:#f9fafb">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
                  <strong><?= esc(($entry['item_type'] ?? 'agenda') === 'agenda' ? 'Agenda' : 'Session Minutes') ?></strong>
                  <span class="badge b-published"><?= esc(str_replace('_', ' ', $entry['action'] ?? 'sent')) ?></span>
                </div>
                <div style="font-size:13px;color:#374151;display:grid;gap:4px">
                  <div><strong>Title:</strong> <?= esc($entry['item_title'] ?? 'Untitled') ?></div>
                  <div><strong>Recipients:</strong> <?= esc(implode(', ', $recipientDisplay)) ?></div>
                  <div><strong>Sent by:</strong> <?= esc($entry['sent_by_name'] ?? 'Admin') ?></div>
                  <div><strong>Date:</strong> <?= esc($entry['created_at'] ?? '—') ?></div>
                  <?php if (!empty($entry['notes'])): ?><div><strong>Note:</strong> <?= esc($entry['notes']) ?></div><?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <div class="panel-foot">
    <button class="btn btn-secondary" type="button" onclick="window.print()">🖨️ Print History</button>
    <button class="btn btn-primary" type="button" onclick="closeDeliveryHistoryModal()">Close</button>
  </div>
</div>

