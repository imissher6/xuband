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
        <?php else: foreach ($announcements as $ann):
          $annJson = htmlspecialchars(json_encode($ann), ENT_QUOTES);
        ?>
        <div class="px-3 py-3 border-bottom dashboard-row-link"
             style="cursor:pointer"
             onclick="dashViewAnn(<?= $annJson ?>)"
             role="button" tabindex="0">
          <div class="d-flex align-items-center gap-2 mb-1">
            <?php if ($ann['pinned']): ?><span class="badge text-bg-warning" style="font-size:.65rem">PINNED</span><?php endif; ?>
            <span class="text-muted" style="font-size:.75rem"><?= formatDate($ann['created_at']) ?></span>
          </div>
          <div class="fw-bold" style="color:var(--xu-navy)"><?= h($ann['title']) ?></div>
          <div class="text-muted small mt-1"><?= h(mb_substr($ann['body'], 0, 100)) ?>…</div>
        </div>
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
        <?php else: foreach ($nextEvents as $ev):
          $evJson = htmlspecialchars(json_encode($ev), ENT_QUOTES);
        ?>
        <div class="px-3 py-3 border-bottom dashboard-row-link d-flex gap-3 align-items-start"
             style="cursor:pointer"
             onclick="dashViewEvent(<?= $evJson ?>)"
             role="button" tabindex="0">
          <div class="text-center flex-shrink-0"
               style="min-width:44px;background:var(--xu-navy);color:#fff;border-radius:8px;padding:6px 4px">
            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;opacity:.7"><?= (new DateTime($ev['event_date']))->format('M') ?></div>
            <div style="font-size:1.2rem;font-weight:800;line-height:1"><?= (new DateTime($ev['event_date']))->format('d') ?></div>
          </div>
          <div>
            <div class="fw-bold" style="color:var(--xu-navy)"><?= h($ev['title']) ?></div>
            <div class="text-muted small"><?= h($ev['location'] ?? '') ?><?= $ev['event_time'] ? ' · ' . date('h:i A', strtotime($ev['event_time'])) : '' ?></div>
            <span class="badge badge-member mt-1"><?= h(ucfirst($ev['type'])) ?></span>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>

</div>

<!-- Dashboard: Announcement View Modal -->
<div class="xu-modal-overlay" id="xumodalDashAnn">
  <div class="xu-modal" style="max-width:640px">
    <div class="xu-modal-header">
      <span class="xu-modal-title" id="dashAnnTitle">Announcement</span>
      <button class="xu-modal-close" onclick="closeModal(this)"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="xu-modal-body" style="max-height:65vh;overflow-y:auto">
      <div id="dashAnnPinBadge" class="mb-2" style="display:none">
        <span class="badge text-bg-warning"><i class="bi bi-pin-angle-fill me-1"></i>PINNED</span>
      </div>
      <p id="dashAnnBody" style="white-space:pre-line;line-height:1.8;color:var(--xu-text);font-size:.93rem"></p>
      <hr>
      <div class="text-muted d-flex align-items-center gap-2 flex-wrap" style="font-size:.8rem">
        <span><i class="bi bi-person me-1"></i><span id="dashAnnAuthor"></span></span>
        <span>&middot;</span>
        <span><i class="bi bi-clock me-1"></i><span id="dashAnnDate"></span></span>
      </div>
    </div>
    <div class="xu-modal-footer">
      <a href="/announcements.php" class="btn btn-outline-secondary btn-sm me-auto">
        <i class="bi bi-arrow-right me-1"></i>View All Announcements
      </a>
      <button type="button" class="btn btn-outline" onclick="closeModal(this)">Close</button>
    </div>
  </div>
</div>

<!-- Dashboard: Event View Modal -->
<div class="xu-modal-overlay" id="xumodalDashEvent">
  <div class="xu-modal" style="max-width:520px">
    <div class="xu-modal-header">
      <span class="xu-modal-title" id="dashEvTitle">Event Details</span>
      <button class="xu-modal-close" onclick="closeModal(this)"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="xu-modal-body">
      <div class="mb-3"><span id="dashEvTypeBadge" class="badge bg-secondary"></span></div>
      <div class="row g-3">
        <div class="col-6">
          <div class="text-muted small">Date</div>
          <div class="fw-bold" id="dashEvDate"></div>
        </div>
        <div class="col-6">
          <div class="text-muted small">Time</div>
          <div class="fw-bold" id="dashEvTime"></div>
        </div>
        <div class="col-6">
          <div class="text-muted small">Location</div>
          <div class="fw-bold" id="dashEvLocation"></div>
        </div>
        <div class="col-6">
          <div class="text-muted small">Organizer</div>
          <div class="fw-bold" id="dashEvOrganizer"></div>
        </div>
      </div>
      <div id="dashEvDescWrap" class="mt-3" style="display:none">
        <div class="text-muted small mb-1">Description</div>
        <p id="dashEvDesc" style="white-space:pre-line;line-height:1.7"></p>
      </div>
    </div>
    <div class="xu-modal-footer">
      <a href="/events.php" class="btn btn-outline-secondary btn-sm me-auto">
        <i class="bi bi-calendar3 me-1"></i>View Calendar
      </a>
      <button type="button" class="btn btn-outline" onclick="closeModal(this)">Close</button>
    </div>
  </div>
</div>

<script>
function dashViewAnn(a) {
  document.getElementById('dashAnnTitle').textContent  = a.title;
  document.getElementById('dashAnnBody').textContent   = a.body;
  document.getElementById('dashAnnAuthor').textContent = a.author || '';
  document.getElementById('dashAnnDate').textContent   = a.created_at || '';
  document.getElementById('dashAnnPinBadge').style.display = a.pinned == 1 ? '' : 'none';
  openModal('xumodalDashAnn');
}
function dashViewEvent(e) {
  document.getElementById('dashEvTitle').textContent    = e.title;
  document.getElementById('dashEvTypeBadge').textContent = (e.type||'').charAt(0).toUpperCase() + (e.type||'').slice(1);
  document.getElementById('dashEvDate').textContent     = e.event_date || '—';
  document.getElementById('dashEvTime').textContent     = e.event_time ? dashFmtTime(e.event_time) : '—';
  document.getElementById('dashEvLocation').textContent = e.location || '—';
  document.getElementById('dashEvOrganizer').textContent = e.organizer || '—';
  var dw = document.getElementById('dashEvDescWrap');
  var dd = document.getElementById('dashEvDesc');
  if (e.description) { dd.textContent = e.description; dw.style.display = ''; }
  else { dw.style.display = 'none'; }
  openModal('xumodalDashEvent');
}
function dashFmtTime(t) {
  if (!t) return '—';
  var p = t.split(':'), h = parseInt(p[0]), m = p[1];
  var ampm = h >= 12 ? 'PM' : 'AM';
  h = h % 12 || 12;
  return h + ':' + m + ' ' + ampm;
}
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('[role=button]').forEach(function(el) {
    el.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); el.click(); }
    });
  });
});
</script>

<?php layout_foot(); ?>
