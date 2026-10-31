<?php
/**
 * @var string $page
 * @var array $currentUser
 * @var array $legislatives
 * @var string|null $search
 * @var array $data
 */
if (!function_exists('esc')) {
  function esc($value): string
  {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  }
}

if (!function_exists('badgeClass')) {
  function badgeClass($value): string
  {
    $classes = [
      'draft' => 'b-pending',
      'review' => 'b-ongoing',
      'approved' => 'b-approved',
      'published' => 'b-published',
      'active' => 'b-active',
      'inactive' => 'b-inactive',
    ];
    return $classes[$value] ?? 'b-pending';
  }
}

if (!function_exists('fmtDate')) {
  function fmtDate($iso): string
  {
    return (new DateTime($iso))->format('M d, Y');
  }
}
?>

<div class="page-section">
  <div class="section-heading">
    <h2>📋 Approved Bills & Resolutions</h2>
    <p>View all approved bills, resolutions, and legislative documents.</p>
    <div class="filter-tabs" style="margin-top:18px;display:flex;flex-wrap:wrap;gap:10px;">
      <?php foreach (['all' => 'All', 'approved' => 'Active', 'review' => 'In Review'] as $value => $label): ?>
        <a class="btn btn-ghost<?= ($statusFilter ?? 'all') === $value ? ' btn-active' : '' ?>" href="?page=ordinances&status=<?= esc($value) ?>">
          <?= esc($label) ?>
          <?php if ($value === 'approved'): ?> (<?= esc($statusCounts['approved'] ?? 0) ?>)<?php endif; ?>
          <?php if ($value === 'review'): ?> (<?= esc($statusCounts['review'] ?? 0) ?>)<?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="legislative-grid">
    <div class="legislative-list">
      <div class="list-header">
        <span>Title</span>
        <span>Type</span>
        <span>Status</span>
        <span>Approved</span>
        <span></span>
      </div>
      <?php if (empty($legislatives)): ?>
        <div class="empty-state">
          <div style="font-size:48px;margin-bottom:12px">📭</div>
          <div>No approved bills or resolutions yet.</div>
        </div>
      <?php else: ?>
        <?php foreach ($legislatives as $item): ?>
          <div class="list-row">
            <div>
              <div style="font-weight:500"><?= esc($item['title']) ?></div>
              <div style="font-size:12px;color:#6b7280;margin-top:4px"><?= esc(substr($item['desc'] ?? '', 0, 100)) ?><?= strlen($item['desc'] ?? '') > 100 ? '…' : '' ?></div>
            </div>
            <div><?= esc($item['type']) ?></div>
            <div><span class="badge <?= badgeClass($item['status']) ?>"><?= esc(ucfirst($item['status'])) ?></span></div>
            <div style="font-size:12px;color:#6b7280"><?= fmtDate($item['published_to_public_at'] ?? $item['createdAt']) ?></div>
            <div>
              <a class="link" href="?page=ordinances&view=<?= esc($item['id']) ?>" onclick="openOrdinanceDetail(event, '<?= esc($item['id']) ?>')">View Details</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Ordinance Detail Modal -->
<div class="overlay" id="ordinance-overlay" onclick="closeOrdinanceDetail()"></div>
<div class="slide-panel" id="ordinance-panel">
  <div class="panel-head">
    <div>
      <div class="panel-title" id="ordinance-title"></div>
      <div class="panel-subtitle" id="ordinance-subtitle"></div>
    </div>
    <button class="panel-close" type="button" onclick="closeOrdinanceDetail()">✕</button>
  </div>
  
  <div class="panel-body" id="ordinance-content" style="overflow-y:auto;max-height:60vh;padding:24px">
    <!-- Content loaded dynamically -->
  </div>

  <div class="panel-foot">
    <button class="btn btn-secondary" type="button" onclick="closeOrdinanceDetail()">Close</button>
  </div>
</div>

<script>
const ordinancesData = <?= json_encode($legislatives ?? []) ?>;

function openOrdinanceDetail(e, ordinanceId) {
  e.preventDefault();
  const ordinance = ordinancesData.find(o => o.id === ordinanceId);
  if (!ordinance) return;
  
  document.getElementById('ordinance-title').textContent = ordinance.title;
  document.getElementById('ordinance-subtitle').textContent = ordinance.type + ' • Published ' + formatDate(ordinance.published_to_public_at || ordinance.createdAt);
  
  let content = '<div style="display:grid;gap:16px">';
  
  if (ordinance.ref) {
    content += '<div><strong>Reference:</strong> ' + escapeHtml(ordinance.ref) + '</div>';
  }
  
  if (ordinance.desc) {
    content += '<div><strong>Description:</strong><div style="margin-top:8px;color:#374151">' + escapeHtml(ordinance.desc) + '</div></div>';
  }
  
  if (ordinance.content) {
    content += '<div><strong>Full Text:</strong><div style="margin-top:8px;background:#f9fafb;padding:16px;border-radius:6px;border-left:4px solid #3b82f6;font-family:monospace;font-size:13px;white-space:pre-wrap;color:#1f2937">' + escapeHtml(ordinance.content) + '</div></div>';
  }
  
  content += '<div style="border-top:1px solid #e5e7eb;padding-top:16px;margin-top:16px;font-size:12px;color:#6b7280">';
  content += '<div><strong>Status:</strong> ' + ordinance.status.charAt(0).toUpperCase() + ordinance.status.slice(1) + '</div>';
  content += '<div><strong>Created By:</strong> ' + escapeHtml(ordinance.createdByName || 'System') + '</div>';
  content += '<div><strong>Created:</strong> ' + formatDate(ordinance.createdAt) + '</div>';
  if (ordinance.lastModifiedByName) {
    content += '<div><strong>Last Modified:</strong> ' + formatDate(ordinance.lastModifiedAt) + ' by ' + escapeHtml(ordinance.lastModifiedByName) + '</div>';
  }
  content += '</div>';
  
  content += '</div>';
  
  document.getElementById('ordinance-content').innerHTML = content;
  document.getElementById('ordinance-overlay').classList.add('open');
  document.getElementById('ordinance-panel').classList.add('open');
}

function closeOrdinanceDetail() {
  document.getElementById('ordinance-overlay').classList.remove('open');
  document.getElementById('ordinance-panel').classList.remove('open');
}

function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return (text || '').replace(/[&<>"']/g, m => map[m]);
}

function formatDate(iso) {
  try {
    const normalized = String(iso).trim();
    const d = /^\d{4}-\d{2}-\d{2}$/.test(normalized)
      ? new Date(normalized + 'T00:00:00+08:00')
      : new Date(normalized.includes(' ') && !/[zZ]|[+-]\d{2}:?\d{2}$/.test(normalized) ? normalized.replace(' ', 'T') + '+08:00' : normalized);
    return d.toLocaleDateString('en-US', { timeZone: 'Asia/Manila', year: 'numeric', month: 'short', day: 'numeric' });
  } catch(e) {
    return iso;
  }
}
</script>
