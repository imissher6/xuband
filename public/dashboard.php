<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
$user = currentUser();

// Stats
$totalMembers    = dbQueryOne('SELECT COUNT(*) AS n FROM users WHERE role = "member"')['n'] ?? 0;
$totalOfficers   = dbQueryOne('SELECT COUNT(*) AS n FROM users WHERE role IN ("officer","moderator")')['n'] ?? 0;
$totalSheets     = dbQueryOne('SELECT COUNT(*) AS n FROM music_sheets')['n'] ?? 0;
$upcomingEvents  = dbQueryOne('SELECT COUNT(*) AS n FROM events WHERE event_date >= CURDATE()')['n'] ?? 0;
$activeScholar   = dbQueryOne('SELECT COUNT(*) AS n FROM scholarships WHERE status = "active"')['n'] ?? 0;
$announcements   = dbQuery('SELECT a.*, u.name AS author FROM announcements a JOIN users u ON u.id = a.created_by ORDER BY a.pinned DESC, a.created_at DESC LIMIT 5');
$nextEvents      = dbQuery('SELECT e.*, u.name AS organizer FROM events e JOIN users u ON u.id = e.created_by WHERE e.event_date >= CURDATE() ORDER BY e.event_date ASC LIMIT 5');

// My attendance (for members)
$myStats = null;
if ($user['role'] === 'member') {
    $myStats = dbQueryOne('SELECT ps.total_points,
        (SELECT COUNT(*) FROM attendance WHERE user_id = ? AND status = "present") AS presents,
        (SELECT COUNT(*) FROM attendance WHERE user_id = ? AND status = "absent") AS absents
        FROM penalty_summary ps WHERE ps.user_id = ?',
        [$user['id'], $user['id'], $user['id']]);
    $myScholarship = dbQueryOne('SELECT * FROM scholarships WHERE user_id = ? ORDER BY id DESC LIMIT 1', [$user['id']]);
}

layout_head('Dashboard', 'dashboard');
?>

<?php if ($s = getFlash('success')): ?>
<div class="alert alert-success" data-auto-dismiss>✅ <?= h($s) ?></div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid">
  <?php if (isOfficer()): ?>
  <div class="stat-card">
    <div class="stat-icon">👥</div>
    <div><div class="stat-value"><?= $totalMembers ?></div><div class="stat-label">Band Members</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🎼</div>
    <div><div class="stat-value"><?= $totalSheets ?></div><div class="stat-label">Music Sheets</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon gold">🎓</div>
    <div><div class="stat-value"><?= $activeScholar ?></div><div class="stat-label">Active Scholars</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">📅</div>
    <div><div class="stat-value"><?= $upcomingEvents ?></div><div class="stat-label">Upcoming Events</div></div>
  </div>
  <?php else: ?>
  <div class="stat-card">
    <div class="stat-icon">✅</div>
    <div><div class="stat-value"><?= $myStats['presents'] ?? 0 ?></div><div class="stat-label">Times Present</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">❌</div>
    <div><div class="stat-value"><?= $myStats['absents'] ?? 0 ?></div><div class="stat-label">Absences</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon gold">⚠️</div>
    <div>
      <div class="stat-value <?= penaltyColor((float)($myStats['total_points'] ?? 0)) ?>"><?= $myStats['total_points'] ?? 0 ?></div>
      <div class="stat-label">Penalty Points</div>
    </div>
  </div>
  <?php if (isset($myScholarship)): ?>
  <div class="stat-card">
    <div class="stat-icon">🎓</div>
    <div>
      <div class="stat-value" style="font-size:1.1rem"><?= ucfirst($myScholarship['status'] ?? '—') ?></div>
      <div class="stat-label">Scholarship Status</div>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;flex-wrap:wrap">

  <!-- Announcements -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">📢 Latest Announcements</span>
      <a href="/announcements.php" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="card-body" style="padding:0">
      <?php if (!$announcements): ?>
      <div class="empty-state"><div class="empty-icon">📭</div><p>No announcements yet.</p></div>
      <?php else: foreach ($announcements as $ann): ?>
      <div style="padding:14px 18px;border-bottom:1px solid var(--border)">
        <div class="flex items-center gap-2 mb-1">
          <?php if ($ann['pinned']): ?><span style="font-size:.7rem;font-weight:700;color:var(--gold)">📌 PINNED</span><?php endif; ?>
          <span style="font-size:.75rem;color:var(--text-muted)"><?= formatDate($ann['created_at']) ?></span>
        </div>
        <div style="font-weight:700;color:var(--navy)"><?= h($ann['title']) ?></div>
        <div class="text-sm text-muted mt-1"><?= h(mb_substr($ann['body'], 0, 100)) ?>...</div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- Upcoming Events -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">📅 Upcoming Events</span>
      <a href="/events.php" class="btn btn-sm btn-outline">View Calendar</a>
    </div>
    <div class="card-body" style="padding:0">
      <?php if (!$nextEvents): ?>
      <div class="empty-state"><div class="empty-icon">📭</div><p>No upcoming events.</p></div>
      <?php else: foreach ($nextEvents as $ev): ?>
      <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;gap:14px;align-items:flex-start">
        <div style="text-align:center;min-width:44px;background:var(--navy);color:var(--white);border-radius:8px;padding:6px 4px">
          <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;opacity:.7"><?= (new DateTime($ev['event_date']))->format('M') ?></div>
          <div style="font-size:1.2rem;font-weight:800;line-height:1"><?= (new DateTime($ev['event_date']))->format('d') ?></div>
        </div>
        <div>
          <div style="font-weight:700;color:var(--navy)"><?= h($ev['title']) ?></div>
          <div class="text-sm text-muted"><?= h($ev['location'] ?? '') ?> <?= $ev['event_time'] ? '· ' . date('h:i A', strtotime($ev['event_time'])) : '' ?></div>
          <span class="badge badge-member mt-1"><?= h(ucfirst($ev['type'])) ?></span>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

</div>

<?php layout_foot(); ?>
