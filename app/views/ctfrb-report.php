<?php
$ctfrbReports = $ctfrbReports ?? [];
$reportEdit = (($editType ?? '') === 'ctfrb-report') ? ($editItem ?? null) : null;
$reportTypeCounts = ['Renewal' => 0, 'Change Unit' => 0, 'New' => 0, 'Transfer' => 0];
foreach ($ctfrbReports as $report) {
  $type = $report['application_type'] ?? '';
  if ($type === 'Renewal/Transfer') {
    $reportTypeCounts['Transfer']++;
  } elseif ($type === 'Renewal/Change Unit') {
    $reportTypeCounts['Change Unit']++;
  } elseif (isset($reportTypeCounts[$type])) {
    $reportTypeCounts[$type]++;
  }
}
?>
<div id="pg-ctfrb-report" class="page active">
  <div class="card">
    <div class="card-head" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
      <div>
        <div class="card-title">CTFRB Report</div>
        <div style="font-size:12px;color:#6b7280;margin-top:4px">City Tricycle Franchising and Regulatory Board application records</div>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <form method="post" action="index.php?page=ctfrb-report" style="margin:0">
          <input type="hidden" name="action" value="export_ctfrb_report" />
          <button class="btn btn-secondary btn-xs" type="submit">⬇️ Export CSV</button>
        </form>
        <button class="btn btn-primary btn-xs" type="button" onclick="openCtfrbReportPanel()">＋ Add Application</button>
      </div>
    </div>
    <div class="ctfrb-summary-grid">
      <div class="ctfrb-summary"><strong><?= count($ctfrbReports) ?></strong><span>Total Applications</span></div>
      <?php foreach ($reportTypeCounts as $label => $count): ?>
        <div class="ctfrb-summary"><strong><?= $count ?></strong><span><?= esc($label) ?></span></div>
      <?php endforeach; ?>
    </div>
    <div style="overflow:auto">
      <table>
        <thead><tr><th>Applicant Name</th><th>Purok/Barangay Address</th><th>Franchise Number</th><th>Application Type</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if (count($ctfrbReports) === 0): ?>
            <tr><td colspan="5" style="text-align:center;padding:28px;color:#9ca3af">No CTFRB applications recorded yet.</td></tr>
          <?php else: ?>
            <?php foreach ($ctfrbReports as $report): ?>
              <tr>
                <td><strong><?= esc($report['applicant_name']) ?></strong></td>
                <td><?= esc($report['address']) ?></td>
                <td><?= esc($report['franchise_number']) ?></td>
                <td><span class="badge b-published"><?= esc($report['application_type']) ?></span></td>
                <td><a class="btn btn-ghost btn-xs" href="?page=ctfrb-report&open=ctfrb-report&type=ctfrb-report&edit=<?= esc($report['id']) ?>">✏️ Edit</a></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="overlay<?= $reportEdit ? ' open' : '' ?>" id="ctfrb-report-overlay" onclick="closeCtfrbReportPanel()"></div>
  <div class="slide-panel<?= $reportEdit ? ' open' : '' ?>" id="ctfrb-report-panel">
    <div class="panel-head">
      <div><div class="panel-title"><?= $reportEdit ? 'Edit CTFRB Application' : 'Add CTFRB Application' ?></div><div class="panel-subtitle">Enter the applicant and franchise details</div></div>
      <button class="panel-close" type="button" onclick="closeCtfrbReportPanel()">✕</button>
    </div>
    <form class="panel-body" method="post" action="index.php?page=ctfrb-report">
      <input type="hidden" name="action" value="save_ctfrb_report" />
      <input type="hidden" name="id" value="<?= esc($reportEdit['id'] ?? '') ?>" />
      <div class="fg"><label>Applicant Name *</label><input type="text" name="applicant_name" required value="<?= esc($reportEdit['applicant_name'] ?? '') ?>" /></div>
      <div class="fg"><label>Purok/Barangay Address *</label><input type="text" name="address" required value="<?= esc($reportEdit['address'] ?? '') ?>" /></div>
      <div class="fg"><label>Franchise Number *</label><input type="text" name="franchise_number" required value="<?= esc($reportEdit['franchise_number'] ?? '') ?>" /></div>
      <div class="fg"><label>Application Type *</label><select name="application_type" required>
        <option value="">Select application type</option>
        <?php foreach (['Renewal', 'Change Unit', 'New', 'Renewal/Transfer', 'Renewal/Change Unit'] as $type): ?>
          <option value="<?= esc($type) ?>" <?= ($reportEdit['application_type'] ?? '') === $type ? 'selected' : '' ?>><?= esc($type) ?></option>
        <?php endforeach; ?>
      </select></div>
      <div class="panel-foot" style="margin:0 -16px -16px"><button class="btn btn-secondary" type="button" onclick="closeCtfrbReportPanel()">Cancel</button><button class="btn btn-primary" type="submit">Save Application</button></div>
    </form>
  </div>
</div>

