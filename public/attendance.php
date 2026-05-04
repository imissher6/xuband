<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isOfficer()) { http_response_code(403); exit; }
    $action = $_POST['action'] ?? '';

    if ($action === 'create_event') {
        $title = trim($_POST['title'] ?? '');
        $date  = $_POST['event_date'] ?? '';
        $type  = $_POST['type'] ?? 'rehearsal';
        $loc   = trim($_POST['location'] ?? '');
        if (!$title || !$date) { flash('error', 'Title and date required.'); redirect('/attendance.php'); }
        $event_id = dbInsert('INSERT INTO events (title,type,event_date,location,created_by) VALUES (?,?,?,?,?)',
            [$title, $type, $date, $loc, $user['id']]);
        flash('success', 'Attendance event created. Now mark attendance below.');
        redirect('/attendance.php?event_id=' . $event_id);
    }

    if ($action === 'bulk_save') {
        $event_id = (int)($_POST['event_id'] ?? 0);
        $statuses = $_POST['status'] ?? [];
        foreach ($statuses as $uid => $status) {
            $uid     = (int)$uid;
            $penalty = computePenalty($status);
            dbExecute('INSERT INTO attendance (user_id,event_id,status,penalty_points,recorded_by)
                       VALUES (?,?,?,?,?)
                       ON DUPLICATE KEY UPDATE status=VALUES(status), penalty_points=VALUES(penalty_points), recorded_by=VALUES(recorded_by)',
                [$uid, $event_id, $status, $penalty, $user['id']]);
            recomputePenaltySummary($uid);
        }
        flash('success', 'Attendance saved. Penalties updated.');
        redirect('/attendance.php?event_id=' . $event_id);
    }
}

$selectedEvent = (int)($_GET['event_id'] ?? 0);
$events = dbQuery('SELECT * FROM events ORDER BY event_date DESC');
$members   = [];
$eventData = null;

if ($selectedEvent) {
    $eventData = dbQueryOne('SELECT * FROM events WHERE id = ?', [$selectedEvent]);
    if ($eventData) {
        if (isOfficer()) {
            $members = dbQuery(
                'SELECT u.id, u.name, u.instrument, u.year_level,
                    COALESCE(a.status,"absent") AS att_status,
                    COALESCE(a.penalty_points,0) AS att_penalty
                 FROM users u
                 LEFT JOIN attendance a ON a.user_id=u.id AND a.event_id=?
                 WHERE u.role="member" AND u.status="active"
                 ORDER BY u.name',
                [$selectedEvent]
            );
        } else {
            $members = dbQuery(
                'SELECT u.id, u.name,
                    COALESCE(a.status,"—") AS att_status,
                    COALESCE(a.penalty_points,0) AS att_penalty
                 FROM users u
                 LEFT JOIN attendance a ON a.user_id=u.id AND a.event_id=?
                 WHERE u.id=?',
                [$selectedEvent, $user['id']]
            );
        }
    }
}

$penaltySummary = [];
if (isOfficer()) {
    $penaltySummary = dbQuery(
        'SELECT u.id, u.name, u.instrument, u.year_level,
            COALESCE(ps.total_points,0) AS total_points,
            (SELECT COUNT(*) FROM attendance WHERE user_id=u.id AND status="present") AS presents,
            (SELECT COUNT(*) FROM attendance WHERE user_id=u.id AND status="absent")  AS absents,
            (SELECT COUNT(*) FROM attendance WHERE user_id=u.id AND status="late")    AS lates
         FROM users u
         LEFT JOIN penalty_summary ps ON ps.user_id=u.id
         WHERE u.role="member" AND u.status="active"
         ORDER BY ps.total_points DESC, u.name'
    );
}

$myPenalty = null;
if ($user['role'] === 'member') {
    $myPenalty = dbQueryOne(
        'SELECT COALESCE(ps.total_points,0) AS total_points,
            (SELECT COUNT(*) FROM attendance WHERE user_id=? AND status="present") AS presents,
            (SELECT COUNT(*) FROM attendance WHERE user_id=? AND status="absent")  AS absents,
            (SELECT COUNT(*) FROM attendance WHERE user_id=? AND status="late")    AS lates
         FROM users u LEFT JOIN penalty_summary ps ON ps.user_id=u.id WHERE u.id=?',
        [$user['id'],$user['id'],$user['id'],$user['id']]
    );
    $myHistory = dbQuery(
        'SELECT a.*, e.title AS event_title, e.event_date FROM attendance a
         JOIN events e ON e.id=a.event_id WHERE a.user_id=? ORDER BY e.event_date DESC',
        [$user['id']]
    );
}

layout_head('Attendance', 'attendance');
?>

<?php if ($e = getFlash('error')): ?>
<div class="alert alert-danger d-flex align-items-center gap-2" data-auto-dismiss>
  <i class="bi bi-exclamation-triangle-fill"></i> <?= h($e) ?>
</div>
<?php endif; ?>
<?php if ($s = getFlash('success')): ?>
<div class="alert alert-success d-flex align-items-center gap-2" data-auto-dismiss>
  <i class="bi bi-check-circle-fill"></i> <?= h($s) ?>
</div>
<?php endif; ?>

<?php if ($user['role'] === 'member'): ?>
<!-- ── MEMBER VIEW ─────────────────────────── -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
  <div class="stat-card">
    <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
    <div><div class="stat-value text-green"><?= $myPenalty['presents']??0 ?></div><div class="stat-label">Present</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
    <div><div class="stat-value text-red"><?= $myPenalty['absents']??0 ?></div><div class="stat-label">Absent</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
    <div><div class="stat-value text-yellow"><?= $myPenalty['lates']??0 ?></div><div class="stat-label">Late</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon gold"><i class="bi bi-exclamation-triangle"></i></div>
    <div>
      <div class="stat-value <?= penaltyColor((float)($myPenalty['total_points']??0)) ?>"><?= $myPenalty['total_points']??0 ?></div>
      <div class="stat-label">Total Penalty</div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title"><i class="bi bi-list-check me-2"></i>My Attendance History</span>
  </div>
  <?php if (empty($myHistory)): ?>
  <div class="empty-state"><div class="empty-icon"><i class="bi bi-clipboard-x"></i></div><p>No attendance records yet.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Event</th><th>Date</th><th>Status</th><th>Penalty Points</th></tr></thead>
      <tbody>
        <?php foreach ($myHistory as $r): ?>
        <tr>
          <td><?= h($r['event_title']) ?></td>
          <td class="small"><?= formatDate($r['event_date']) ?></td>
          <td><?= statusBadge($r['status']) ?></td>
          <td class="<?= penaltyColor((float)$r['penalty_points']) ?> fw-bold"><?= $r['penalty_points'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php else: ?>
<!-- ── OFFICER/MODERATOR VIEW ─────────────────────────── -->
<div class="row g-3 mb-3">

  <!-- Create Attendance Record -->
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header">
        <span class="card-title"><i class="bi bi-plus-circle me-2"></i>Create Attendance Record</span>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="create_event">
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Event Title *</label>
            <input name="title" class="form-control" placeholder="e.g. Rehearsal May 4" required>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Date *</label>
              <input name="event_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Type</label>
              <select name="type" class="form-control">
                <option value="rehearsal">Rehearsal</option>
                <option value="performance">Performance</option>
                <option value="meeting">Meeting</option>
                <option value="competition">Competition</option>
                <option value="other">Other</option>
              </select>
            </div>
          </div>
          <div class="mt-3">
            <label class="form-label">Location</label>
            <input name="location" class="form-control" placeholder="e.g. XU Band Hall">
          </div>
          <div class="alert alert-warning d-flex align-items-center gap-2 mt-3 mb-0 py-2 small">
            <i class="bi bi-info-circle-fill"></i>
            Penalty: Present = 0 &nbsp;&middot;&nbsp; Late = 75 pts &nbsp;&middot;&nbsp; Absent = 150 pts
          </div>
        </div>
        <div class="p-3 pt-0">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Create &amp; Mark Attendance
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Select existing event -->
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header">
        <span class="card-title"><i class="bi bi-list-ul me-2"></i>Select Event to Edit</span>
      </div>
      <div class="card-body">
        <form method="GET">
          <label class="form-label">Event</label>
          <select name="event_id" class="form-control" onchange="this.form.submit()">
            <option value="">— Select event —</option>
            <?php foreach ($events as $ev): ?>
            <option value="<?= $ev['id'] ?>" <?= $selectedEvent===$ev['id']?'selected':'' ?>>
              <?= h($ev['title']) ?> (<?= formatDate($ev['event_date']) ?>)
            </option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
    </div>
  </div>

</div>

<!-- Mark Attendance -->
<?php if ($selectedEvent && $eventData): ?>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span class="card-title">
      <i class="bi bi-check2-square me-2"></i>Mark Attendance — <?= h($eventData['title']) ?>
    </span>
    <span class="text-muted small">
      <?= formatDate($eventData['event_date']) ?>
      <?= $eventData['location'] ? ' · '.h($eventData['location']) : '' ?>
    </span>
  </div>
  <form method="POST">
    <input type="hidden" name="action" value="bulk_save">
    <input type="hidden" name="event_id" value="<?= $selectedEvent ?>">
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Member</th><th>Instrument</th><th>Year</th><th>Status</th><th>Penalty Preview</th></tr>
        </thead>
        <tbody>
          <?php foreach ($members as $m): ?>
          <tr>
            <td><strong><?= h($m['name']) ?></strong></td>
            <td><?= h($m['instrument']??'—') ?></td>
            <td class="small text-muted"><?= h($m['year_level']??'—') ?></td>
            <td>
              <select name="status[<?= $m['id'] ?>]" class="form-control" style="width:120px"
                id="sel_<?= $m['id'] ?>" onchange="updatePts(<?= $m['id'] ?>)">
                <?php foreach (['present','late','absent'] as $st): ?>
                <option value="<?= $st ?>" <?= ($m['att_status']===$st)?'selected':'' ?>><?= ucfirst($st) ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td>
              <span id="pts_<?= $m['id'] ?>" class="fw-bold <?= penaltyColor((float)$m['att_penalty']) ?>">
                <?= $m['att_penalty'] ?>
              </span> pts
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$members): ?>
          <tr><td colspan="5"><div class="empty-state p-3"><p>No active members.</p></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($members): ?>
    <div class="p-3">
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-floppy me-1"></i>Save Attendance &amp; Compute Penalties
      </button>
    </div>
    <?php endif; ?>
  </form>
</div>
<?php endif; ?>

<!-- Penalty Summary Table -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="card-title">
      <i class="bi bi-exclamation-triangle me-2"></i>Penalty Summary — All Members
    </span>
    <span class="text-muted small">Present = 0 pts &middot; Late = 75 pts &middot; Absent = 150 pts</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Member</th><th>Instrument</th><th>Present</th><th>Late</th><th>Absent</th><th>Total Penalty</th></tr>
      </thead>
      <tbody>
        <?php foreach ($penaltySummary as $p): ?>
        <tr>
          <td>
            <strong><?= h($p['name']) ?></strong>
            <?php if($p['year_level']):?><br><span class="text-muted" style="font-size:.75rem"><?= h($p['year_level']) ?></span><?php endif; ?>
          </td>
          <td><?= h($p['instrument']??'—') ?></td>
          <td class="text-green fw-bold"><?= $p['presents'] ?></td>
          <td class="text-yellow fw-bold"><?= $p['lates'] ?></td>
          <td class="text-red fw-bold"><?= $p['absents'] ?></td>
          <td>
            <span class="fw-bold <?= penaltyColor((float)$p['total_points']) ?>" style="font-size:1.05rem">
              <?= $p['total_points'] ?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$penaltySummary): ?>
        <tr><td colspan="6"><div class="empty-state p-3"><p>No data yet.</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

<script>
const penMap = {present:0, late:75, absent:150};
function updatePts(uid) {
  const sel = document.getElementById('sel_' + uid);
  const el  = document.getElementById('pts_' + uid);
  if (!sel || !el) return;
  const pts = penMap[sel.value] ?? 0;
  el.textContent = pts;
  el.className = 'fw-bold ' + (pts === 0 ? 'text-green' : pts === 75 ? 'text-yellow' : 'text-red');
}
</script>

<?php layout_foot(); ?>
