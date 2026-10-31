<div id="pg-analytics" class="page active">
  <div style="padding: 20px;">
    <h1 style="margin-bottom: 30px; color: #1f2937; font-size: 28px;">Analytics & Reports</h1>

    <?php
      $agendaSourceIds = array_flip(array_filter(array_column($data['agendas'] ?? [], 'source_legislative_id')));
      $pendingBills = array_values(array_filter($data['legislatives'] ?? [], function (array $item) use ($agendaSourceIds): bool {
        $type = strtolower((string)($item['type'] ?? ''));
        $isBillOrResolution = str_contains($type, 'bill') || str_contains($type, 'resolution');
        $isSubmittedOrApproved = in_array($item['status'] ?? '', ['submitted_to_admin', 'approved'], true);
        return $isBillOrResolution
          && $isSubmittedOrApproved
          && (($item['status'] ?? '') !== 'approved' || !isset($agendaSourceIds[$item['id'] ?? '']));
      }));
    ?>
    <div class="card" style="margin-bottom:20px;border:2px solid #f59e0b;">
      <div class="card-head" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <span class="card-title">🧾 Pending Bills / Resolutions for Review <span class="badge" style="background:#fef3c7;color:#92400e;margin-left:6px;"><?= count($pendingBills) ?></span></span>
        <a class="btn btn-secondary btn-xs" href="?page=legislative-bills&bill_status=pending">Open Bills Queue</a>
      </div>
      <div class="card-body" style="padding:0;">
        <?php if (empty($pendingBills)): ?>
          <div class="empty-state" style="padding:24px 20px;text-align:center;">No submitted bills or resolutions require review.</div>
        <?php else: ?>
          <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;min-width:680px;">
              <thead>
                <tr style="border-bottom:2px solid #e5e7eb;">
                  <th style="text-align:left;padding:12px;">Bill</th>
                  <th style="text-align:left;padding:12px;">Submitted By</th>
                  <th style="text-align:left;padding:12px;">Status</th>
                  <th style="text-align:right;padding:12px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendingBills as $bill): ?>
                  <tr style="border-bottom:1px solid #f3f4f6;">
                    <td style="padding:12px;"><strong><?= esc($bill['title'] ?? 'Untitled bill') ?></strong><div style="font-size:12px;color:#6b7280;"><?= esc($bill['type'] ?? 'Bill') ?></div></td>
                    <td style="padding:12px;color:#4b5563;"><?= esc($bill['createdByName'] ?? 'Unknown') ?></td>
                    <td style="padding:12px;"><span class="badge <?= badgeClass($bill['status'] ?? 'pending') ?>"><?= esc(($bill['status'] ?? '') === 'approved' ? 'Approved - Waiting for Agenda' : ucfirst(str_replace('_', ' ', $bill['status'] ?? 'pending'))) ?></span></td>
                    <td style="padding:12px;text-align:right;white-space:nowrap;">
                      <a class="btn btn-secondary btn-xs" href="?page=legislative-admin&edit=<?= esc($bill['id']) ?>&type=legislative">Review</a>
                      <?php if (($bill['status'] ?? '') !== 'approved'): ?>
                        <form method="post" action="index.php?page=analytics" enctype="multipart/form-data" style="display:inline-flex;gap:5px;margin-left:6px;align-items:center;flex-wrap:wrap;" onsubmit="return confirm('Set this bill or resolution on the selected session agenda for Presiding Officer signature?')">
                          <input type="hidden" name="action" value="approve_legislative" />
                          <input type="hidden" name="legislative_id" value="<?= esc($bill['id']) ?>" />
                          <select name="session_id" required style="max-width:145px;font-size:11px;padding:4px;">
                            <option value="">Select session</option>
                            <?php foreach (($data['sessions'] ?? []) as $session): ?>
                              <option value="<?= esc($session['id']) ?>"><?= esc($session['title']) ?> · <?= esc($session['date']) ?></option>
                            <?php endforeach; ?>
                          </select>
                          <input type="file" name="agenda_file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp,.txt" style="max-width:170px;font-size:11px;" />
                          <button class="btn btn-success btn-xs" type="submit">Set Agenda for Officer Signature</button>
                        </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="dash-grid" style="grid-template-columns: 1fr 1fr; gap: 20px;">
      <!-- Agenda Approvals Card -->
      <div class="card">
        <div class="card-head">
          <span class="card-title">📎 Agenda Approvals by Month</span>
        </div>
        <div class="card-body" style="padding: 20px;">
          <?php if (empty($analytics['monthlyApprovals'])): ?>
            <div class="empty-state" style="padding: 40px 20px; text-align: center;">
              <div class="empty-txt">No agenda data available</div>
            </div>
          <?php else: ?>
            <table style="width: 100%; border-collapse: collapse;">
              <thead>
                <tr style="border-bottom: 2px solid #e5e7eb;">
                  <th style="text-align: left; padding: 12px; font-weight: 600; color: #6b7280;">Month</th>
                  <th style="text-align: center; padding: 12px; font-weight: 600; color: #6b7280;">Total</th>
                  <th style="text-align: center; padding: 12px; font-weight: 600; color: #6b7280;">Approved</th>
                  <th style="text-align: right; padding: 12px; font-weight: 600; color: #6b7280;">%</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($analytics['monthlyApprovals'] as $item): ?>
                  <?php 
                    $approved = (int)($item['approved'] ?? 0);
                    $total = (int)($item['total'] ?? 1);
                    $percentage = $total > 0 ? round(($approved / $total) * 100) : 0;
                    $monthStr = date('M Y', strtotime($item['month'] . '-01'));
                  ?>
                  <tr style="border-bottom: 1px solid #f3f4f6; hover:background-color: #f9fafb;">
                    <td style="padding: 12px; color: #1f2937;"><?= esc($monthStr) ?></td>
                    <td style="text-align: center; padding: 12px; color: #6b7280;"><?= $total ?></td>
                    <td style="text-align: center; padding: 12px;">
                      <span style="background: #dbeafe; color: #0c4a6e; padding: 4px 8px; border-radius: 4px; font-weight: 600;"><?= $approved ?></span>
                    </td>
                    <td style="text-align: right; padding: 12px;">
                      <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                        <div style="width: 80px; height: 6px; background: #e5e7eb; border-radius: 3px; overflow: hidden;">
                          <div style="width: <?= $percentage ?>%; height: 100%; background: linear-gradient(90deg, #3b82f6, #06b6d4); border-radius: 3px;"></div>
                        </div>
                        <span style="color: #3b82f6; font-weight: 600; min-width: 35px; text-align: right;"><?= $percentage ?>%</span>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

      <!-- Session Attendance Card -->
      <div class="card">
        <div class="card-head">
          <span class="card-title">📅 Session Attendance by Month</span>
        </div>
        <div class="card-body" style="padding: 20px;">
          <?php if (empty($analytics['monthlySessions'])): ?>
            <div class="empty-state" style="padding: 40px 20px; text-align: center;">
              <div class="empty-txt">No session data available</div>
            </div>
          <?php else: ?>
            <table style="width: 100%; border-collapse: collapse;">
              <thead>
                <tr style="border-bottom: 2px solid #e5e7eb;">
                  <th style="text-align: left; padding: 12px; font-weight: 600; color: #6b7280;">Month</th>
                  <th style="text-align: center; padding: 12px; font-weight: 600; color: #6b7280;">Sessions</th>
                  <th style="text-align: center; padding: 12px; font-weight: 600; color: #6b7280;">Present</th>
                  <th style="text-align: right; padding: 12px; font-weight: 600; color: #6b7280;">%</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($analytics['monthlySessions'] as $item): ?>
                  <?php 
                    $present = (int)($item['total_present'] ?? 0);
                    $capacity = (int)($item['total_capacity'] ?? 1);
                    $percentage = $capacity > 0 ? round(($present / $capacity) * 100) : 0;
                    $monthStr = date('M Y', strtotime($item['month'] . '-01'));
                    $sessions = (int)($item['total_sessions'] ?? 0);
                  ?>
                  <tr style="border-bottom: 1px solid #f3f4f6;">
                    <td style="padding: 12px; color: #1f2937;"><?= esc($monthStr) ?></td>
                    <td style="text-align: center; padding: 12px; color: #6b7280;"><?= $sessions ?></td>
                    <td style="text-align: center; padding: 12px;">
                      <span style="background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 4px; font-weight: 600;"><?= $present ?></span>
                    </td>
                    <td style="text-align: right; padding: 12px;">
                      <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                        <div style="width: 80px; height: 6px; background: #e5e7eb; border-radius: 3px; overflow: hidden;">
                          <div style="width: <?= min($percentage, 100) ?>%; height: 100%; background: linear-gradient(90deg, #10b981, #14b8a6); border-radius: 3px;"></div>
                        </div>
                        <span style="color: #10b981; font-weight: 600; min-width: 35px; text-align: right;"><?= $percentage ?>%</span>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Summary Stats -->
    <div class="dash-grid" style="grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 20px;">
      <?php 
        $totalApprovals = 0;
        $totalAgendas = 0;
        foreach ($analytics['monthlyApprovals'] as $item) {
          $totalApprovals += (int)($item['approved'] ?? 0);
          $totalAgendas += (int)($item['total'] ?? 0);
        }
        $overallApprovalRate = $totalAgendas > 0 ? round(($totalApprovals / $totalAgendas) * 100) : 0;

        $totalPresent = 0;
        $totalCapacity = 0;
        foreach ($analytics['monthlySessions'] as $item) {
          $totalPresent += (int)($item['total_present'] ?? 0);
          $totalCapacity += (int)($item['total_capacity'] ?? 0);
        }
        $overallAttendance = $totalCapacity > 0 ? round(($totalPresent / $totalCapacity) * 100) : 0;
      ?>
      <div class="stat-card sc-blue">
        <div class="stat-icon">✅</div>
        <div class="stat-val"><?= $overallApprovalRate ?>%</div>
        <div class="stat-lbl">Overall Approval Rate</div>
        <div class="stat-sub"><?= $totalApprovals ?> of <?= $totalAgendas ?> approved</div>
      </div>
      <div class="stat-card sc-green">
        <div class="stat-icon">👥</div>
        <div class="stat-val"><?= $overallAttendance ?>%</div>
        <div class="stat-lbl">Overall Attendance</div>
        <div class="stat-sub"><?= $totalPresent ?> present out of <?= $totalCapacity ?></div>
      </div>
      <div class="stat-card sc-amber">
        <div class="stat-icon">📊</div>
        <div class="stat-val"><?= count($analytics['monthlySessions']) ?></div>
        <div class="stat-lbl">Months with Data</div>
        <div class="stat-sub">Last 12 months</div>
      </div>
    </div>
  </div>
</div>
