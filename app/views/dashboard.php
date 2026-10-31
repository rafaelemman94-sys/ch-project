<div id="pg-dashboard" class="page active">
  <div class="stats-row">
    <div class="stat-card sc-purple"><div class="stat-icon">👥</div><div class="stat-val"><?= count($data['users']) ?></div><div class="stat-lbl">Total Users</div><div class="stat-sub"><?= $activeUsers ?> active</div></div>
    <div class="stat-card sc-green"><div class="stat-icon">📅</div><div class="stat-val"><?= count($data['sessions']) ?></div><div class="stat-lbl">Total Sessions</div><div class="stat-sub"><?= $upcomingSessions ?> upcoming</div></div>
    <div class="stat-card sc-amber"><div class="stat-icon">📎</div><div class="stat-val"><?= count($data['agendas']) ?></div><div class="stat-lbl">Agendas</div></div>
    <div class="stat-card sc-blue"><div class="stat-icon">📈</div><div class="stat-val"><?= $activeRate ?>%</div><div class="stat-lbl">Active Rate</div><div class="prog-wrap"><div class="prog-bar" id="d-bar" style="width:<?= $activeRate ?>%"></div></div></div>
  </div>
  <div class="dash-grid">
    <?php if (str_contains(strtolower($currentUser['role'] ?? ''), 'council')): ?>
      <div class="card">
        <div class="card-head"><span class="card-title">Council Agendas</span></div>
        <div class="card-body" style="padding:18px;line-height:1.5;color:#cbd5e1">
          Access agendas sent to your council tablet, review them, and print the current session package.
        </div>
        <div style="padding:0 18px 18px">
          <a class="btn btn-primary" href="?page=tablet">👁️ View & Print Agendas</a>
        </div>
      </div>
    <?php endif; ?>
    <div class="card">
      <div class="card-head"><span class="card-title">Recent Users</span><a class="btn btn-ghost btn-sm" href="?page=users">View All →</a></div>
      <table><thead><tr><th>Name</th><th>Role</th><th>Status</th></tr></thead><tbody>
        <?php if (count($dashboardUsers) === 0): ?>
          <tr><td colspan="3"><div class="empty-state" style="padding:20px"><div class="empty-txt">No users yet</div></div></td></tr>
        <?php else: ?>
          <?php foreach ($dashboardUsers as $user): ?>
            <tr><td><strong><?= esc($user['name']) ?></strong></td><td><span class="badge <?= badgeClass($user['role']) ?>"><?= esc($user['role']) ?></span></td><td><span class="badge <?= badgeClass($user['status']) ?>"><?= esc($user['status']) ?></span></td></tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody></table>
    </div>
    <div class="card">
      <div class="card-head"><span class="card-title">AI Draft Helper</span><button class="btn btn-ghost btn-sm" type="button" onclick="openAIDraftPanel()">Open Draft Assistant</button></div>
      <div class="card-body" style="padding:18px;line-height:1.5;color:#cbd5e1">
        Generate a draft resolution or ordinance suggestion from a quick prompt, then copy it into the legislative proposal form.
      </div>
    </div>
    <div class="card">
      <div class="card-head"><span class="card-title">Upcoming Sessions</span><a class="btn btn-ghost btn-sm" href="?page=sessions">View All →</a></div>
      <table><thead><tr><th>Title</th><th>Date</th><th>Status</th></tr></thead><tbody>
        <?php if (count($dashboardSessions) === 0): ?>
          <tr><td colspan="3"><div class="empty-state" style="padding:20px"><div class="empty-txt">No upcoming sessions</div></div></td></tr>
        <?php else: ?>
          <?php foreach ($dashboardSessions as $session): ?>
            <tr><td><strong><?= esc($session['title']) ?></strong></td><td style="color:#9ca3af;font-size:11px"><?= esc($session['date']) ?></td><td><span class="badge <?= badgeClass($session['status']) ?>"><?= esc($session['status']) ?></span></td></tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody></table>
    </div>
  </div>
</div>
