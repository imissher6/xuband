<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isOfficer()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title  = trim($_POST['title'] ?? '');
        $type   = $_POST['type'] ?? 'rehearsal';
        $date   = $_POST['event_date'] ?? '';
        $time   = $_POST['event_time'] ?: null;
        $loc    = trim($_POST['location'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        if (!$title || !$date) { flash('error', 'Title and date are required.'); redirect('/events.php'); }
        dbInsert('INSERT INTO events (title,type,event_date,event_time,location,description,created_by) VALUES (?,?,?,?,?,?,?)',
            [$title,$type,$date,$time,$loc,$desc,$user['id']]);
        flash('success', "Event \"$title\" created.");
        redirect('/events.php');
    }

    if ($action === 'update') {
        $id    = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $type  = $_POST['type'] ?? 'rehearsal';
        $date  = $_POST['event_date'] ?? '';
        $time  = $_POST['event_time'] ?: null;
        $loc   = trim($_POST['location'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        dbExecute('UPDATE events SET title=?,type=?,event_date=?,event_time=?,location=?,description=? WHERE id=?',
            [$title,$type,$date,$time,$loc,$desc,$id]);
        flash('success', 'Event updated.');
        redirect('/events.php');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        dbExecute('DELETE FROM events WHERE id = ?', [$id]);
        flash('success', 'Event deleted.');
        redirect('/events.php');
    }
}

// Calendar month
$month = (int)($_GET['month'] ?? date('n'));
$year  = (int)($_GET['year']  ?? date('Y'));
if ($month < 1) { $month = 12; $year--; }
if ($month > 12){ $month = 1;  $year++; }

$firstDay   = mktime(0,0,0,$month,1,$year);
$daysInMonth= (int)date('t', $firstDay);
$startDow   = (int)date('w', $firstDay); // 0=Sun

$monthEvents = dbQuery('SELECT * FROM events WHERE MONTH(event_date)=? AND YEAR(event_date)=? ORDER BY event_date,event_time',
    [$month, $year]);

// Map events to day
$eventsByDay = [];
foreach ($monthEvents as $ev) {
    $d = (int)date('j', strtotime($ev['event_date']));
    $eventsByDay[$d][] = $ev;
}

$allEvents = dbQuery('SELECT e.*, u.name AS organizer FROM events e JOIN users u ON u.id = e.created_by ORDER BY e.event_date DESC');

$monthName = date('F Y', $firstDay);
$prevMonth = $month - 1; $prevYear = $year;
$nextMonth = $month + 1; $nextYear = $year;
if ($prevMonth < 1)  { $prevMonth = 12; $prevYear--; }
if ($nextMonth > 12) { $nextMonth = 1;  $nextYear++; }

layout_head('Events & Calendar', 'events');
?>

<?php if ($e = getFlash('error')): ?><div class="alert alert-error" data-auto-dismiss>⚠️ <?= h($e) ?></div><?php endif; ?>
<?php if ($s = getFlash('success')): ?><div class="alert alert-success" data-auto-dismiss>✅ <?= h($s) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:20px;align-items:start">

<!-- Calendar -->
<div class="card">
  <div class="card-header">
    <div class="flex items-center gap-2">
      <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-outline btn-sm">‹</a>
      <span class="card-title"><?= $monthName ?></span>
      <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-outline btn-sm">›</a>
    </div>
    <?php if (isOfficer()): ?>
    <button class="btn btn-primary btn-sm" data-modal="modalEvent" onclick="resetEventForm()">+ Add Event</button>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <div class="calendar-grid">
      <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
        <div class="cal-day-header"><?= $d ?></div>
      <?php endforeach; ?>

      <?php
      // Empty cells before first day
      for ($i = 0; $i < $startDow; $i++) echo '<div class="cal-day other-month"></div>';
      for ($d = 1; $d <= $daysInMonth; $d++):
        $isToday = ($d == date('j') && $month == date('n') && $year == date('Y'));
      ?>
      <div class="cal-day <?= $isToday ? 'today' : '' ?>">
        <div class="cal-day-num"><?= $d ?></div>
        <?php foreach ($eventsByDay[$d] ?? [] as $ev): ?>
        <span class="cal-event-dot <?= h($ev['type']) ?>" title="<?= h($ev['title']) ?>"><?= h($ev['title']) ?></span>
        <?php endforeach; ?>
      </div>
      <?php endfor; ?>
    </div>

    <div class="mt-3 flex gap-3" style="font-size:.75rem;flex-wrap:wrap">
      <span style="display:flex;align-items:center;gap:4px"><span style="width:10px;height:10px;background:var(--navy);border-radius:2px;display:inline-block"></span> Rehearsal</span>
      <span style="display:flex;align-items:center;gap:4px"><span style="width:10px;height:10px;background:#7c3aed;border-radius:2px;display:inline-block"></span> Performance</span>
      <span style="display:flex;align-items:center;gap:4px"><span style="width:10px;height:10px;background:var(--green);border-radius:2px;display:inline-block"></span> Meeting</span>
      <span style="display:flex;align-items:center;gap:4px"><span style="width:10px;height:10px;background:var(--gold);border-radius:2px;display:inline-block"></span> Other</span>
    </div>
  </div>
</div>

<!-- Event List -->
<div class="card">
  <div class="card-header"><span class="card-title">📋 All Events</span></div>
  <div style="max-height:520px;overflow-y:auto">
    <?php if (!$allEvents): ?>
    <div class="empty-state"><div class="empty-icon">📅</div><p>No events yet.</p></div>
    <?php else: foreach ($allEvents as $ev):
      $isPast = strtotime($ev['event_date']) < strtotime(date('Y-m-d'));
    ?>
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);<?= $isPast ? 'opacity:.6' : '' ?>">
      <div class="flex items-center gap-2 mb-1" style="justify-content:space-between">
        <div class="flex items-center gap-2">
          <span class="badge badge-member"><?= h(ucfirst($ev['type'])) ?></span>
          <?php if ($isPast): ?><span class="text-xs text-muted">Past</span><?php endif; ?>
        </div>
        <?php if (isOfficer()): ?>
        <div class="flex gap-1">
          <button class="btn btn-xs btn-outline" data-modal="modalEvent" onclick="fillEvent(<?= htmlspecialchars(json_encode($ev), ENT_QUOTES) ?>)">Edit</button>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $ev['id'] ?>">
            <button class="btn btn-xs btn-danger" data-confirm="Delete this event?">Del</button>
          </form>
        </div>
        <?php endif; ?>
      </div>
      <div style="font-weight:700;color:var(--navy)"><?= h($ev['title']) ?></div>
      <div class="text-sm text-muted">
        <?= formatDate($ev['event_date']) ?>
        <?= $ev['event_time'] ? ' · ' . date('h:i A', strtotime($ev['event_time'])) : '' ?>
        <?= $ev['location'] ? ' · ' . h($ev['location']) : '' ?>
      </div>
      <?php if ($ev['description']): ?><div class="text-sm mt-1"><?= h(mb_substr($ev['description'],0,80)) ?></div><?php endif; ?>
    </div>
    <?php endforeach; endif; ?>
  </div>
</div>

</div>

<?php if (isOfficer()): ?>
<!-- Add/Edit Event Modal -->
<div class="modal-overlay" id="modalEvent">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="eventModalTitle">Add Event</span>
      <button class="modal-close" data-modal-close>✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" id="ev_action" value="create">
      <input type="hidden" name="id" id="ev_id" value="">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Title *</label><input id="ev_title" name="title" class="form-control" required></div>
        <div class="form-row form-row-2">
          <div class="form-group"><label class="form-label">Type</label>
            <select id="ev_type" name="type" class="form-control">
              <option value="rehearsal">Rehearsal</option>
              <option value="performance">Performance</option>
              <option value="meeting">Meeting</option>
              <option value="competition">Competition</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Date *</label><input id="ev_date" name="event_date" type="date" class="form-control" required></div>
        </div>
        <div class="form-row form-row-2">
          <div class="form-group"><label class="form-label">Time</label><input id="ev_time" name="event_time" type="time" class="form-control"></div>
          <div class="form-group"><label class="form-label">Location</label><input id="ev_loc" name="location" class="form-control"></div>
        </div>
        <div class="form-group"><label class="form-label">Description</label><textarea id="ev_desc" name="description" class="form-control"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Save Event</button>
      </div>
    </form>
  </div>
</div>

<script>
function resetEventForm() {
  document.getElementById('ev_action').value = 'create';
  document.getElementById('eventModalTitle').textContent = 'Add Event';
  ['id','title','date','time','loc','desc'].forEach(k => {
    const el = document.getElementById('ev_' + k);
    if (el) el.value = '';
  });
  document.getElementById('ev_type').value = 'rehearsal';
}
function fillEvent(e) {
  document.getElementById('ev_action').value = 'update';
  document.getElementById('eventModalTitle').textContent = 'Edit Event';
  document.getElementById('ev_id').value    = e.id;
  document.getElementById('ev_title').value = e.title;
  document.getElementById('ev_type').value  = e.type;
  document.getElementById('ev_date').value  = e.event_date;
  document.getElementById('ev_time').value  = e.event_time || '';
  document.getElementById('ev_loc').value   = e.location || '';
  document.getElementById('ev_desc').value  = e.description || '';
}
</script>
<?php endif; ?>

<?php layout_foot(); ?>
