<div id="pg-users" class="page active">
  <div class="card">
    <div class="card-head"><span class="card-title">All Users <span id="u-cnt" style="color:#4a5568;font-weight:400">(<?= count($users) ?>)</span></span></div>
    <table>
      <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if (count($users) === 0): ?>
          <tr><td colspan="7"></td></tr>
        <?php else: ?>
          <?php $i = 1; foreach ($users as $user): ?>
            <tr>
              <td style="color:#3d4363"><?= $i++ ?></td>
              <td><div style="display:flex;align-items:center;gap:9px"><div style="width:30px;height:30px;border-radius:50%;background:<?= avatarColor($user['name']) ?>;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0"><?= esc(strtoupper($user['name'][0] ?? 'A')) ?></div><div><div style="font-weight:600;font-size:13px"><?= esc($user['name']) ?><?php if (($currentUser['id'] ?? '') === ($user['id'] ?? '')): ?> <span class="badge" style="margin-left:6px;background:#e0e7ff;color:#3730a3">You</span><?php endif; ?></div><?= $user['notes'] ? '<div style="font-size:10.5px;color:#4a5568">' . esc(mb_substr($user['notes'], 0, 40)) . '</div>' : '' ?></div></div></td>
              <td style="color:#9ca3af;font-size:12px"><?= esc($user['email']) ?></td>
              <td><span class="badge <?= badgeClass($user['role']) ?>"><?= esc($user['role']) ?></span></td>
              <td><span class="badge <?= badgeClass($user['status']) ?>"><?= esc($user['status']) ?></span></td>
              <td style="color:#4a5568;font-size:11px"><?= fmtDate($user['created_at']) ?></td>
              <td><div class="row-actions"><a class="btn btn-ghost btn-xs" href="?page=users&open=user&type=user&edit=<?= esc($user['id']) ?>">✏️ Edit</a><?php if (($currentUser['id'] ?? '') !== ($user['id'] ?? '')): ?><a class="btn btn-danger btn-xs" href="?page=users&confirm=delete&type=user&id=<?= esc($user['id']) ?>">🗑️</a><?php endif; ?></div></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
    <?php if (count($users) === 0): ?>
      <div class="empty-state"><div class="empty-icon">👥</div><div class="empty-txt">No users yet. Click <strong>+ Create User</strong> to add one.</div></div>
    <?php endif; ?>
  </div>
</div>
