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
// Fix: status values are Full Scholar / Half Scholar / Not Scholar — count non-"Not Scholar"
$activeScholar   = dbQueryOne('SELECT COUNT(*) AS n FROM scholarships WHERE status != "Not Scholar"')['n'] ?? 0;
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
<div class="alert alert-success d-flex align-items-center gap-2" data-auto-dismiss>
  <i class="bi bi-check-circle-fill"></i> <?= h($s) ?>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid">
  <?php if (isOfficer()): ?>
  <a href="/members.php" class="stat-card stat-card-link" style="text-decoration:none">
    <div class="stat-icon"><i class="bi bi-people"></i></div>
    <div><div class="stat-value"><?= $totalMembers ?></div><div class="stat-label">Band Members</div></div>
  </a>
  <a href="/music-sheets.php" class="stat-card stat-card-link" style="text-decoration:none">
    <div class="stat-icon"><i class="bi bi-music-note-beamed"></i></div>
    <div><div class="stat-value"><?= $totalSheets ?></div><div class="stat-label">Music Sheets</div></div>
  </a>
  <a href="/scholarships.php" class="stat-card stat-card-link" style="text-decoration:none">
    <div class="stat-icon gold"><i class="bi bi-award"></i></div>
    <div><div class="stat-value"><?= $activeScholar ?></div><div class="stat-label">Active Scholars</div></div>
  </a>
  <a href="/events.php" class="stat-card stat-card-link" style="text-decoration:none">
    <div class="stat-icon"><i class="bi bi-calendar3"></i></div>
    <div><div class="stat-value"><?= $upcomingEvents ?></div><div class="stat-label">Upcoming Events</div></div>
  </a>
  <?php else: ?>
  <a href="/attendance.php" class="stat-card stat-card-link" style="text-decoration:none">
    <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
    <div><div class="stat-value"><?= $myStats['presents'] ?? 0 ?></div><div class="stat-label">Times Present</div></div>
  </a>
  <a href="/attendance.php" class="stat-card stat-card-link" style="text-decoration:none">
    <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
    <div><div class="stat-value"><?= $myStats['absents'] ?? 0 ?></div><div class="stat-label">Absences</div></div>
  </a>
  <a href="/attendance.php" class="stat-card stat-card-link" style="text-decoration:none">
    <div class="stat-icon gold"><i class="bi bi-exclamation-triangle"></i></div>
    <div>
      <div class="stat-value <?= penaltyColor((float)($myStats['total_points'] ?? 0)) ?>"><?= $myStats['total_points'] ?? 0 ?></div>
      <div class="stat-label">Penalty Points</div>
    </div>
  </a>
  <?php if (isset($myScholarship)): ?>
  <a href="/scholarships.php" class="stat-card stat-card-link" style="text-decoration:none">
    <div class="stat-icon"><i class="bi bi-award"></i></div>
    <div>
      <div class="stat-value" style="font-size:1.1rem"><?= ucfirst($myScholarship['status'] ?? '—') ?></div>
      <div class="stat-label">Scholarship Status</div>
    </div>
  </a>
  <?php endif; ?>
  <?php endif; ?>
</div>

<div class="row g-3">

  <!-- Announcements -->
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="card-title"><i class="bi bi-megaphone me-2"></i>Latest Announcements</span>
        <a href="/announcements.php" class="btn btn-sm btn-outline">View All</a>
      </div>
      <div class="card-body p-0">
        <?php if (!$announcements): ?>
        <div class="empty-state"><div class="empty-icon"><i class="bi bi-inbox"></i></div><p>No announcements yet.</p></div>
        <?php else: foreach ($announcements as $ann): ?>
        <a href="/announcements.php" class="text-decoration-none d-block px-3 py-3 border-bottom dashboard-row-link">
          <div class="d-flex align-items-center gap-2 mb-1">
            <?php if ($ann['pinned']): ?><span class="badge text-bg-warning" style="font-size:.65rem">PINNED</span><?php endif; ?>
            <span class="text-muted" style="font-size:.75rem"><?= formatDate($ann['created_at']) ?></span>
          </div>
          <div class="fw-bold" style="color:var(--navy)"><?= h($ann['title']) ?></div>
          <div class="text-muted small mt-1"><?= h(mb_substr($ann['body'], 0, 100)) ?>…</div>
        </a>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>

  <!-- Upcoming Events -->
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="card-title"><i class="bi bi-calendar3 me-2"></i>Upcoming Events</span>
        <a href="/events.php" class="btn btn-sm btn-outline">View Calendar</a>
      </div>
      <div class="card-body p-0">
        <?php if (!$nextEvents): ?>
        <div class="empty-state"><div class="empty-icon"><i class="bi bi-calendar-x"></i></div><p>No upcoming events.</p></div>
        <?php else: foreach ($nextEvents as $ev): ?>
        <a href="/events.php" class="text-decoration-none d-block px-3 py-3 border-bottom dashboard-row-link d-flex gap-3 align-items-start">
          <div class="text-center flex-shrink-0" style="min-width:44px;background:var(--navy);color:#fff;border-radius:8px;padding:6px 4px">
            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;opacity:.7"><?= (new DateTime($ev['event_date']))->format('M') ?></div>
            <div style="font-size:1.2rem;font-weight:800;line-height:1"><?= (new DateTime($ev['event_date']))->format('d') ?></div>
          </div>
          <div>
            <div class="fw-bold" style="color:var(--navy)"><?= h($ev['title']) ?></div>
            <div class="text-muted small"><?= h($ev['location'] ?? '') ?><?= $ev['event_time'] ? ' · ' . date('h:i A', strtotime($ev['event_time'])) : '' ?></div>
            <span class="badge badge-member mt-1"><?= h(ucfirst($ev['type'])) ?></span>
          </div>
        </a>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>

</div>

<?php layout_foot(); ?>
