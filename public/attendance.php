<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
$user = currentUser();

// Handle bulk attendance save (officers/moderator)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isOfficer()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'bulk_save') {
        $event_id = (int)($_POST['event_id'] ?? 0);
        $statuses = $_POST['status'] ?? []; // array: [user_id => status]

        foreach ($statuses as $uid => $status) {
            $uid = (int)$uid;
            $penalty = computePenalty($status);
            dbExecute('INSERT INTO attendance (user_id,event_id,status,penalty_points,recorded_by)
                       VALUES (?,?,?,?,?)
                       ON DUPLICATE KEY UPDATE status=VALUES(status), penalty_points=VALUES(penalty_points), recorded_by=VALUES(recorded_by)',
                [$uid,$event_id,$status,$penalty,$user['id']]);
            recomputePenaltySummary($uid);
        }
        flash('success', 'Attendance saved and penalties computed.');
        redirect('/attendance.php?event_id=' . $event_id);
    }
}

$selectedEvent = (int)($_GET['event_id'] ?? 0);
$events = dbQuery('SELECT * FROM events ORDER BY event_date DESC');

// Members with their attendance for selected event
$members   = [];
$eventData = null;

if ($selectedEvent) {
    $eventData = dbQueryOne('SELECT * FROM events WHERE id = ?', [$selectedEvent]);
    if (isOfficer()) {
        $members = dbQuery('SELECT u.id, u.name, u.instrument, u.year_level,
            COALESCE(a.status, "absent") AS att_status,
            COALESCE(a.penalty_points, 0) AS att_penalty,
            a.remarks, a.id AS att_id
            FROM users u
            LEFT JOIN attendance a ON a.user_id = u.id AND a.event_id = ?
            WHERE u.role = "member" AND u.status = "active"
            ORDER BY u.name', [$selectedEvent]);
    } else {
        // Members see only their own attendance
        $members = dbQuery('SELECT u.id, u.name, u.instrument, u.year_level,
            COALESCE(a.status, "—") AS att_status,
            COALESCE(a.penalty_points, 0) AS att_penalty,
            a.remarks
            FROM users u
            LEFT JOIN attendance a ON a.user_id = u.id AND a.event_id = ?
            WHERE u.id = ?', [$selectedEvent, $user['id']]);
    }
}

// Penalty summary for all members (officers)
$penaltySummary = [];
if (isOfficer()) {
    $rows = dbQuery('SELECT u.id, u.name, u.instrument, u.year_level,
        COALESCE(ps.total_points, 0) AS total_points,
        (SELECT COUNT(*) FROM attendance WHERE user_id = u.id AND status = "present") AS presents,
        (SELECT COUNT(*) FROM attendance WHERE user_id = u.id AND status = "absent") AS absents,
        (SELECT COUNT(*) FROM attendance WHERE user_id = u.id AND status = "late") AS lates,
        (SELECT COUNT(*) FROM attendance WHERE user_id = u.id AND status = "excused") AS excused
        FROM users u
        LEFT JOIN penalty_summary ps ON ps.user_id = u.id
        WHERE u.role = "member" AND u.status = "active"
        ORDER BY ps.total_points DESC');
    $penaltySummary = $rows;
}

// Member's own penalty summary
$myPenalty = null;
if ($user['role'] === 'member') {
    $myPenalty = dbQueryOne('SELECT COALESCE(ps.total_points,0) AS total_points,
        (SELECT COUNT(*) FROM attendance WHERE user_id = ? AND status = "present") AS presents,
        (SELECT COUNT(*) FROM attendance WHERE user_id = ? AND status = "absent") AS absents,
        (SELECT COUNT(*) FROM attendance WHERE user_id = ? AND status = "late") AS lates
        FROM users u LEFT JOIN penalty_summary ps ON ps.user_id = u.id WHERE u.id = ?',
        [$user['id'],$user['id'],$user['id'],$user['id']]);
}

layout_head('Attendance', 'attendance');
?>

<?php if ($e = getFlash('error')): ?><div class="alert alert-error" data-auto-dismiss>⚠️ <?= h($e) ?></div><?php endif; ?>
<?php if ($s = getFlash('success')): ?><div class="alert alert-success" data-auto-dismiss>✅ <?= h($s) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;align-items:start" data-tabs>

<!-- Event Selector + Attendance -->
<div class="card" style="grid-column:1/-1">
  <div class="card-header">
    <span class="card-title">✅ Attendance Record</span>
    <?php if (isOfficer()): ?>
    <span class="text-sm text-muted">Select an event to mark attendance</span>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <form method="GET" class="flex gap-3 items-center mb-4" style="flex-wrap:wrap">
      <select name="event_id" class="form-control" style="width:auto;flex:1;max-width:400px" onchange="this.form.submit()">
        <option value="">— Select Event —</option>
        <?php foreach ($events as $ev): ?>
        <option value="<?= $ev['id'] ?>" <?= $selectedEvent===$ev['id']?'selected':'' ?>>
          <?= h($ev['title']) ?> (<?= formatDate($ev['event_date']) ?>)
        </option>
        <?php endforeach; ?>
      </select>
    </form>

    <?php if (!$selectedEvent): ?>
    <div class="empty-state"><div class="empty-icon">✅</div><p>Select an event above to view or record attendance.</p></div>
    <?php elseif (!$eventData): ?>
    <div class="alert alert-warning">Event not found.</div>
    <?php else: ?>

    <div style="background:var(--bg);border-radius:8px;padding:12px 16px;margin-bottom:16px;display:flex;gap:24px;flex-wrap:wrap">
      <div><span class="text-muted text-sm">Event</span><div style="font-weight:700"><?= h($eventData['title']) ?></div></div>
      <div><span class="text-muted text-sm">Date</span><div style="font-weight:700"><?= formatDate($eventData['event_date']) ?></div></div>
      <div><span class="text-muted text-sm">Type</span><div><?= h(ucfirst($eventData['type'])) ?></div></div>
      <?php if ($eventData['location']): ?><div><span class="text-muted text-sm">Location</span><div><?= h($eventData['location']) ?></div></div><?php endif; ?>
    </div>

    <?php if (isOfficer()): ?>
    <form method="POST">
      <input type="hidden" name="action" value="bulk_save">
      <input type="hidden" name="event_id" value="<?= $selectedEvent ?>">
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Member</th><th>Instrument</th><th>Status</th><th>Penalty Pts</th></tr>
          </thead>
          <tbody>
            <?php foreach ($members as $m): ?>
            <tr>
              <td><strong><?= h($m['name']) ?></strong><?php if($m['year_level']):?><br><span class="text-xs text-muted"><?=h($m['year_level'])?></span><?php endif;?></td>
              <td><?= h($m['instrument'] ?: '—') ?></td>
              <td>
                <select name="status[<?= $m['id'] ?>]" class="form-control" style="width:130px" onchange="updatePts(this, <?= $m['id'] ?>)">
                  <?php foreach (['present','absent','late','excused'] as $s): ?>
                  <option value="<?= $s ?>" <?= $m['att_status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                  <?php endforeach; ?>
                </select>
                <input type="hidden" name="status[<?= $m['id'] ?>]" id="hidden_status_<?= $m['id'] ?>" value="<?= h($m['att_status']) ?>">
              </td>
              <td><span id="pts_<?= $m['id'] ?>" class="text-bold <?= penaltyColor((float)$m['att_penalty']) ?>"><?= $m['att_penalty'] ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="mt-3">
        <button type="submit" class="btn btn-primary">💾 Save Attendance & Compute Penalties</button>
      </div>
    </form>
    <?php else: ?>
    <!-- Member view: read only -->
    <div class="table-wrap">
      <table>
        <thead><tr><th>Status</th><th>Penalty Points</th></tr></thead>
        <tbody>
          <?php foreach ($members as $m): ?>
          <tr>
            <td><?= ($m['att_status'] !== '—' && $m['att_status'] !== 'absent') ? statusBadge($m['att_status']) : ($m['att_status'] === 'absent' ? statusBadge('absent') : '<span class="text-muted">Not recorded</span>') ?></td>
            <td class="<?= penaltyColor((float)$m['att_penalty']) ?> text-bold"><?= $m['att_penalty'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Penalty Summary -->
<div class="card" style="grid-column:1/-1">
  <div class="card-header">
    <span class="card-title">⚠️ Penalty Summary</span>
    <div class="text-xs text-muted">Absent: +3pts · Late: +1pt · Excused/Present: 0pts</div>
  </div>
  <?php if (isOfficer()): ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Member</th><th>Instrument</th><th>Present</th><th>Absent</th><th>Late</th><th>Excused</th><th>Total Points</th></tr>
      </thead>
      <tbody>
        <?php foreach ($penaltySummary as $p): ?>
        <tr>
          <td><strong><?= h($p['name']) ?></strong><?php if($p['year_level']):?><br><span class="text-xs text-muted"><?=h($p['year_level'])?></span><?php endif;?></td>
          <td><?= h($p['instrument'] ?: '—') ?></td>
          <td class="text-green text-bold"><?= $p['presents'] ?></td>
          <td class="text-red text-bold"><?= $p['absents'] ?></td>
          <td class="text-yellow text-bold"><?= $p['lates'] ?></td>
          <td class="text-muted"><?= $p['excused'] ?></td>
          <td><span class="text-bold <?= penaltyColor((float)$p['total_points']) ?>" style="font-size:1.05rem"><?= $p['total_points'] ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$penaltySummary): ?>
        <tr><td colspan="7"><div class="empty-state" style="padding:24px"><p>No attendance data yet.</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="card-body">
    <?php if ($myPenalty): ?>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px">
      <div class="stat-card"><div class="stat-icon">✅</div><div><div class="stat-value text-green"><?= $myPenalty['presents'] ?></div><div class="stat-label">Present</div></div></div>
      <div class="stat-card"><div class="stat-icon">❌</div><div><div class="stat-value text-red"><?= $myPenalty['absents'] ?></div><div class="stat-label">Absent</div></div></div>
      <div class="stat-card"><div class="stat-icon">⏰</div><div><div class="stat-value text-yellow"><?= $myPenalty['lates'] ?></div><div class="stat-label">Late</div></div></div>
      <div class="stat-card"><div class="stat-icon gold">⚠️</div><div><div class="stat-value <?= penaltyColor((float)$myPenalty['total_points']) ?>"><?= $myPenalty['total_points'] ?></div><div class="stat-label">Penalty Pts</div></div></div>
    </div>
    <?php else: ?>
    <div class="empty-state"><p>No attendance records yet.</p></div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

</div>

<script>
const penaltyMap = { present: 0, absent: 3, late: 1, excused: 0 };
function updatePts(sel, uid) {
  const pts = penaltyMap[sel.value] ?? 0;
  const el = document.getElementById('pts_' + uid);
  if (el) el.textContent = pts;
  const hidden = document.getElementById('hidden_status_' + uid);
  if (hidden) hidden.value = sel.value;
}
// sync select name to hidden
document.querySelectorAll('select[name^="status_"]').forEach(sel => {
  sel.addEventListener('change', () => {
    const uid = sel.name.replace('status_', '');
    const hidden = document.getElementById('hidden_status_' + uid);
    if (hidden) hidden.value = sel.value;
  });
});
</script>

<?php layout_foot(); ?>
