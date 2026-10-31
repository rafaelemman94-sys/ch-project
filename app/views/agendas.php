<?php
$agendaBillsById = [];
if (($currentUser['role'] ?? '') === 'admin') {
  foreach (($data['legislatives'] ?? []) as $bill) {
    $agendaBillsById[(string)($bill['id'] ?? '')] = $bill;
  }
}
?>
<div id="pg-agendas" class="page active">
  <div style="display:flex;justify-content:flex-end;margin-bottom:12px">
    <button class="btn btn-secondary btn-xs" type="button" onclick="openDeliveryHistoryModal()">🕘 History</button>
  </div>
  <div id="ag-grid" class="agenda-grid">
    <?php if (count($agendas) === 0): ?>
      <div></div>
    <?php else: ?>
      <?php foreach ($agendas as $agenda): ?>
        <?php $session = null; foreach ($data['sessions'] as $item) { if ($item['id'] === $agenda['session_id']) { $session = $item; break; } } ?>
        <?php $linkedBill = !empty($agenda['source_legislative_id']) ? ($agendaBillsById[(string)$agenda['source_legislative_id']] ?? null) : null; ?>
        <?php $readingStage = $agenda['reading_stage'] ?? ($agenda['approval_status'] ?? ''); ?>
        <?php $previewUrl = !empty($agenda['file_path']) ? 'download.php?file=' . rawurlencode($agenda['file_path']) . '&inline=1' : ''; ?>
        <div class="ag-item">
          <div class="ag-icon" style="background:<?= fileIconBg($agenda['file_type']) ?>"><?= fileIcon($agenda['file_type']) ?></div>
          <div class="ag-body">
            <div class="ag-title"><?= esc($agenda['title']) ?></div>
            <div class="ag-meta">
              <?= esc($agenda['file_name']) ?> · <?= fmtSize($agenda['file_size']) ?><br>
              Uploaded <?= fmtDate($agenda['uploaded_at']) ?><?= $session ? ' · <span style="color:#a5b4fc">📅 ' . esc($session['title']) . '</span>' : '' ?>
              <br><span class="badge b-published" style="margin-top:4px">🗂️ Reading: <?= esc(readingStageLabel($readingStage)) ?></span>
              <?php if (!empty($agenda['approval_status']) && $agenda['approval_status'] !== 'pending'): ?>
                <br><span class="badge <?= badgeClass($agenda['approval_status']) ?>" style="margin-top:4px">
                  <?php
                    $status = $agenda['approval_status'] ?? '';
                    if ($status === 'sent_to_officer') echo '⏳ Pending Officer Review';
                    elseif ($status === 'approved') echo '✅ Approved';
                    elseif ($status === 'rejected') echo '❌ Rejected';
                    else echo ucfirst($status);
                  ?>
                </span>
              <?php endif; ?>
            </div>
            <?php if ($linkedBill): ?>
              <details class="ag-linked-bill">
                <summary>
                  <span class="ag-linked-bill-label">Linked bill</span>
                  <strong><?= esc($linkedBill['control_number'] ?? 'Bill') ?> · <?= esc($linkedBill['title'] ?? 'Untitled bill') ?></strong>
                  <span class="ag-linked-bill-status"><?= esc(ucfirst(str_replace('_', ' ', (string)($linkedBill['status'] ?? 'draft')))) ?></span>
                </summary>
                <div class="ag-linked-bill-details">
                  <span><?= esc($linkedBill['type'] ?? 'Bill') ?><?php if (!empty($linkedBill['createdByName'])): ?> · Submitted by <?= esc($linkedBill['createdByName']) ?><?php endif; ?></span>
                  <?php if (!empty($linkedBill['desc'])): ?><p><?= nl2br(esc($linkedBill['desc'])) ?></p><?php endif; ?>
                  <div class="ag-linked-bill-content legislative-clean-content"><?= \App\Services\LegislativeContentFormatter::toSafeHtml((string)($linkedBill['content'] ?? 'No bill text is available.')) ?></div>
                  <a class="link" href="index.php?page=legislative-admin&amp;edit=<?= rawurlencode((string)$linkedBill['id']) ?>&amp;type=legislative" target="_blank" rel="noopener">Open full bill record</a>
                </div>
              </details>
            <?php endif; ?>
            <div class="ag-actions">
              <div style="display:grid;gap:6px">
                <?php if (!empty($agenda['file_path'])): ?>
                  <a href="download.php?file=<?= esc($agenda['file_path']) ?>" class="btn btn-ghost btn-xs" target="_blank">⬇️ Download</a>
                <?php else: ?>
                  <span style="font-size:11px;color:#4a5568">Preview only</span>
                <?php endif; ?>
                <?php if (!empty($agenda['file_path']) && supportsInlinePreview($agenda['file_type'] ?? '')): ?>
                  <button class="btn btn-secondary btn-xs" type="button" onclick="openFileViewer('<?= esc($previewUrl) ?>', '<?= esc($agenda['title']) ?>', '<?= esc($agenda['file_type']) ?>')">👁️ View File</button>
                <?php endif; ?>
              </div>
              <div style="display:grid;gap:6px">
                <label style="font-size:12px;font-weight:600;color:#374151;margin:0">Set Reading Stage</label>
                <select onchange="setAgendaReadingStage('<?= esc($agenda['id']) ?>', this.value)">
                  <option value="">Not set</option>
                  <option value="first_reading" <?= $readingStage === 'first_reading' ? 'selected' : '' ?>>First Reading</option>
                  <option value="second_reading" <?= $readingStage === 'second_reading' ? 'selected' : '' ?>>Second Reading</option>
                </select>
              </div>
              
              <?php 
              $approvalStage = $agenda['approval_stage'] ?? 'ctrfb';
              $approvalStatus = $agenda['approval_status'] ?? 'pending';
              $hasSchedule = !empty($agenda['session_id']);
              $reviewerApproved = $approvalStatus === 'approved' || $approvalStage === 'approved_for_council';
              $sentToCouncil = !empty($agenda['sent_to_council_at']) || $approvalStatus === 'published_to_council' || $approvalStage === 'council_notified';
              $canSendToCouncil = $reviewerApproved && !$sentToCouncil && $hasSchedule;
              if ($sentToCouncil): ?>
                <button class="btn btn-secondary btn-xs" disabled>📬 Sent to Council</button>
              <?php elseif ($canSendToCouncil): ?>
                <form method="post" onsubmit="return confirm('Send this agenda to all Council Members?')">
                  <input type="hidden" name="action" value="send_to_council"/>
                  <input type="hidden" name="agenda_id" value="<?= esc($agenda['id']) ?>"/>
                  <select name="template_id" style="display:block;width:100%;margin-bottom:6px;font-size:11px;padding:4px 6px;border:1px solid #d1d5db;border-radius:4px">
                    <option value="">No template</option>
                    <?php foreach (($data['delivery_templates'] ?? []) as $template): ?>
                      <option value="<?= esc($template['id']) ?>"><?= esc($template['name'] ?: $template['file_name']) ?> (<?= esc($template['template_type'] ?: 'general') ?>)</option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-success btn-xs" type="submit">📢 Send to Council Members</button>
                </form>
              <?php elseif ($approvalStage === 'awaiting_proceeding' && !$hasSchedule): ?>
                <button class="btn btn-warning btn-xs" type="button" disabled title="Set a session before sending for signature">Set Schedule Before Sending</button>
              <?php elseif ($approvalStage === 'awaiting_proceeding' && empty($agenda['sent_to_proceeding_id'])): ?>
                <button class="btn btn-warning btn-xs" onclick="openSendToProceedingModal('<?= esc($agenda['id']) ?>', '<?= esc($agenda['title']) ?>')">👤 Send to Presiding Officer</button>
              <?php elseif ($approvalStage === 'awaiting_proceeding'): ?>
                <button class="btn btn-secondary btn-xs" type="button" disabled>Awaiting Presiding Officer signature</button>
              <?php elseif (($approvalStage === 'ctrfb' || empty($approvalStage)) && !$hasSchedule): ?>
                <button class="btn btn-info btn-xs" type="button" disabled title="Set a session before sending for signature">Set Schedule Before Sending</button>
              <?php elseif ($approvalStage === 'ctrfb' || empty($approvalStage)): ?>
                <button class="btn btn-info btn-xs" onclick="openSendToProceedingModal('<?= esc($agenda['id']) ?>', '<?= esc($agenda['title']) ?>')">👤 Send to Presiding Officer</button>
              <?php else: ?>
                <button class="btn btn-secondary btn-xs" onclick="showApprovalStatus('<?= esc($agenda['id']) ?>')" title="View approval details">📋 Status</button>
              <?php endif; ?>
              <button class="btn btn-secondary btn-xs" onclick="openPublishModal('<?= esc($agenda['id']) ?>', '<?= esc($agenda['title']) ?>', '<?= esc($agenda['session_id']) ?>')">🗓️ <?= !empty($agenda['session_id']) ? 'Change Schedule' : 'Set Schedule' ?></button>
              <a class="btn btn-danger btn-xs" href="?page=agendas&confirm=delete&type=agenda&id=<?= esc($agenda['id']) ?>">🗑️</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <?php if (count($agendas) === 0): ?>
    <div id="ag-empty" class="empty-state" style="display:block"><div class="empty-icon">📎</div><div class="empty-txt">No agendas uploaded yet. Click <strong>+ Upload Agenda</strong> to add one.</div></div>
  <?php endif; ?>
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

<!-- Send to Presiding Officer Modal -->
<div class="overlay" id="send-proceeding-overlay" onclick="closeProceedingModal()"></div>
<div class="slide-panel" id="send-proceeding-panel">
  <div class="panel-head">
    <div>
      <div class="panel-title">Send to Presiding Officer</div>
      <div class="panel-subtitle" id="send-proceeding-subtitle"></div>
    </div>
    <button class="panel-close" type="button" onclick="closeProceedingModal()">✕</button>
  </div>
  
  <form class="panel-body" method="post" action="index.php?page=agendas" id="send-to-proceeding-form">
    <input type="hidden" name="action" value="send_to_proceeding_officer" />
    <input type="hidden" name="agenda_id" value="" id="send-proceeding-agenda-id" />

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
      <label style="font-weight:600;margin-bottom:12px;display:block">Select Presiding Officer(s) *</label>
      <div style="display:grid;gap:6px">
        <?php 
        $presidingOfficersOnly = array_filter($users ?? [], fn($u) => ($u['role'] ?? '') === 'presiding officer' && ($u['status'] ?? 'active') === 'active');
        if (count($presidingOfficersOnly) > 0): 
          foreach ($presidingOfficersOnly as $officer):
        ?>
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:8px;border-radius:4px;border:1px solid #e5e7eb;transition:all .2s">
            <input type="checkbox" name="officer_ids[]" value="<?= esc($officer['id']) ?>" style="cursor:pointer" />
            <span style="flex:1"><?= esc($officer['name']) ?></span>
          </label>
        <?php endforeach; else: ?>
          <div style="padding:16px;background:#fef2f2;border-radius:6px;color:#991b1b;font-size:13px">
            ⚠️ No Presiding Officers available. Please create users with this role first.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </form>

  <div class="panel-foot">
    <button class="btn btn-secondary" type="button" onclick="closeProceedingModal()">Cancel</button>
    <button class="btn btn-primary" type="submit" form="send-to-proceeding-form" onclick="return validateProceedingRecipients()">👤 Send to Presiding Officer</button>
  </div>
</div>

<!-- Schedule Modal -->
<div class="overlay" id="publish-overlay" onclick="closePublishModal()"></div>
<div class="slide-panel" id="publish-panel">
  <div class="panel-head">
    <div>
      <div class="panel-title">Set Agenda Schedule</div>
      <div class="panel-subtitle" id="publish-subtitle"></div>
    </div>
    <button class="panel-close" type="button" onclick="closePublishModal()">✕</button>
  </div>
  
  <form class="panel-body" method="post" action="index.php?page=agendas" id="publish-form">
    <input type="hidden" name="action" value="publish_agenda" />
    <input type="hidden" name="publish" value="1" />
    <input type="hidden" name="id" value="" id="publish-agenda-id" />
    <input type="hidden" name="session_id" value="" id="publish-session-id" />

    <div class="fg">
      <label style="font-weight:600;margin-bottom:12px;display:block">Select Schedule/Session *</label>
      <div style="font-size:12px;color:#6b7280;margin-bottom:12px">
        ⓘ Admin sets the session schedule. The Presiding Officer signs the agenda.
      </div>
      <div style="display:grid;gap:6px">
        <?php 
        $sessions = $data['sessions'] ?? [];
        if (count($sessions) > 0): 
          foreach ($sessions as $sess):
        ?>
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px;border-radius:4px;border:2px solid #e5e7eb;transition:all .2s;background:#f9fafb">
            <input type="radio" name="session_select" value="<?= esc($sess['id']) ?>" style="cursor:pointer" onchange="document.getElementById('publish-session-id').value = this.value" />
            <div style="flex:1">
              <div style="font-weight:500;color:#111827"><?= esc($sess['title']) ?></div>
              <div style="font-size:11px;color:#6b7280">📅 <?= fmtDate($sess['date']) ?> · ⏰ <?= esc($sess['start']) ?> - <?= esc($sess['end']) ?></div>
            </div>
          </label>
        <?php endforeach; else: ?>
          <div style="padding:16px;background:#fef3c7;border-radius:6px;color:#92400e;font-size:13px;border-left:4px solid #f59e0b">
            ⚠️ No schedules/sessions available. Please create a session first.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </form>

  <div class="panel-foot">
    <button class="btn btn-secondary" type="button" onclick="closePublishModal()">Cancel</button>
    <button class="btn btn-primary" type="submit" form="publish-form" onclick="return validatePublish()">💾 Save Schedule</button>
  </div>
</div>

<div class="overlay" id="delivery-history-overlay" onclick="closeDeliveryHistoryModal()"></div>
<div class="slide-panel" id="delivery-history-panel" style="max-width:980px;width:min(95vw,980px)">
  <div class="panel-head">
    <div>
      <div class="panel-title">Delivery History</div>
      <div class="panel-subtitle">Printable log of agenda and minutes delivery events</div>
    </div>
    <button class="panel-close" type="button" onclick="closeDeliveryHistoryModal()">✕</button>
  </div>
  <div class="panel-body" style="display:grid;gap:12px">
    <form method="post" action="index.php?page=agendas" enctype="multipart/form-data" style="display:grid;gap:8px;padding:12px;border:1px solid #e5e7eb;border-radius:8px;background:#f9fafb">
      <input type="hidden" name="action" value="upload_delivery_template" />
      <div style="font-weight:700;color:#111827">Upload History Template</div>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:8px">
        <input type="text" name="template_name" placeholder="Template name" />
        <input type="text" name="template_type" placeholder="Template type" value="agenda" />
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

