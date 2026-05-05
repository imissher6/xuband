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

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $type  = $_POST['type'] ?? 'rehearsal';
        $date  = $_POST['event_date'] ?? '';
        $time  = $_POST['event_time'] ?: null;
        $loc   = trim($_POST['location'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        if (!$title || !$date) { flash('error', 'Title and date are required.'); redirect('/events.php'); }
        dbInsert('INSERT INTO events (title,type,event_date,event_time,location,description,created_by) VALUES (?,?,?,?,?,?,?)',
            [$title,$type,$date,$time,$loc,$desc,$user['id']]);
        flash('success', "Event \"$title\" created.");
        redirect('/events.php');
    }

    if ($action === 'update') {
        $id   = (int)($_POST['id'] ?? 0);
        $title= trim($_POST['title'] ?? '');
        $type = $_POST['type'] ?? 'rehearsal';
        $date = $_POST['event_date'] ?? '';
        $time = $_POST['event_time'] ?: null;
        $loc  = trim($_POST['location'] ?? '');
        $desc = trim($_POST['description'] ?? '');
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

$month = (int)($_GET['month'] ?? date('n'));
$year  = (int)($_GET['year']  ?? date('Y'));
if ($month < 1) { $month = 12; $year--; }
if ($month > 12){ $month = 1;  $year++; }
$firstDay    = mktime(0,0,0,$month,1,$year);
$daysInMonth = (int)date('t', $firstDay);
$startDow    = (int)date('w', $firstDay);
$monthEvents = dbQuery('SELECT * FROM events WHERE MONTH(event_date)=? AND YEAR(event_date)=? ORDER BY event_date,event_time', [$month,$year]);
$eventsByDay = [];
foreach ($monthEvents as $ev) { $d = (int)date('j', strtotime($ev['event_date'])); $eventsByDay[$d][] = $ev; }
$allEvents   = dbQuery('SELECT e.*, u.name AS organizer FROM events e JOIN users u ON u.id = e.created_by ORDER BY e.event_date DESC');
$monthName   = date('F Y', $firstDay);
$prevMonth = $month-1; $prevYear = $year; if ($prevMonth<1) { $prevMonth=12; $prevYear--; }
$nextMonth = $month+1; $nextYear = $year; if ($nextMonth>12){ $nextMonth=1;  $nextYear++; }

layout_head('Events & Calendar', 'events');
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

<?php if (!isOfficer()): ?>
<div class="alert alert-info d-flex align-items-center gap-2">
  <i class="bi bi-info-circle-fill"></i> You can view events. Contact an officer to add or modify events.
</div>
<?php endif; ?>

<div class="row g-3 align-items-start">

<div class="col-lg-8">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-2">
        <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-outline btn-sm">
          <i class="bi bi-chevron-left"></i>
        </a>
        <span class="card-title"><?= $monthName ?></span>
        <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-outline btn-sm">
          <i class="bi bi-chevron-right"></i>
        </a>
      </div>
      <?php if (isOfficer()): ?>
      <button class="btn btn-primary btn-sm" onclick="openModal('xumodalEvent')" onclick="resetEventForm()">
        <i class="bi bi-plus-lg me-1"></i> Add Event
      </button>
      <?php endif; ?>
    </div>
    <div class="card-body">
      <div class="calendar-grid">
        <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
          <div class="cal-day-header"><?= $d ?></div>
        <?php endforeach; ?>
        <?php for ($i = 0; $i < $startDow; $i++) echo '<div class="cal-day other-month"></div>'; ?>
        <?php for ($d=1; $d<=$daysInMonth; $d++):
          $isToday = ($d==date('j') && $month==date('n') && $year==date('Y'));
        ?>
        <div class="cal-day <?= $isToday?'today':'' ?>">
          <div class="cal-day-num"><?= $d ?></div>
          <?php foreach ($eventsByDay[$d] ?? [] as $ev): ?>
          <span class="cal-event-dot <?= h($ev['type']) ?>" title="<?= h($ev['title']) ?>"><?= h($ev['title']) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endfor; ?>
      </div>
      <div class="d-flex gap-3 mt-3 flex-wrap" style="font-size:.75rem">
        <span><span class="d-inline-block me-1" style="width:10px;height:10px;background:var(--navy);border-radius:2px"></span>Rehearsal</span>
        <span><span class="d-inline-block me-1" style="width:10px;height:10px;background:#7c3aed;border-radius:2px"></span>Performance</span>
        <span><span class="d-inline-block me-1" style="width:10px;height:10px;background:var(--green);border-radius:2px"></span>Meeting</span>
        <span><span class="d-inline-block me-1" style="width:10px;height:10px;background:var(--gold);border-radius:2px"></span>Other</span>
      </div>
    </div>
  </div>
</div>

<div class="col-lg-4">
  <div class="card">
    <div class="card-header"><span class="card-title"><i class="bi bi-list-ul me-2"></i>All Events</span></div>
    <div style="max-height:520px;overflow-y:auto">
      <?php if (!$allEvents): ?>
      <div class="empty-state"><div class="empty-icon"><i class="bi bi-calendar-x"></i></div><p>No events yet.</p></div>
      <?php else: foreach ($allEvents as $ev):
        $isPast = strtotime($ev['event_date']) < strtotime(date('Y-m-d'));
      ?>
      <div class="px-3 py-2 border-bottom <?= $isPast ? 'opacity-50' : '' ?>">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-1">
          <span class="badge badge-member"><?= h(ucfirst($ev['type'])) ?></span>
          <?php if (isOfficer()): ?>
          <div class="d-flex gap-1">
            <button class="btn btn-xs btn-outline" onclick="openModal('xumodalEvent'); fillEvent(<?= htmlspecialchars(json_encode($ev), ENT_QUOTES) ?>)">
              <i class="bi bi-pencil"></i>
            </button>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $ev['id'] ?>">
              <button class="btn btn-xs btn-danger" data-confirm="Delete this event?">
                <i class="bi bi-trash"></i>
              </button>
            </form>
          </div>
          <?php endif; ?>
        </div>
        <div class="fw-bold small" style="color:var(--navy)"><?= h($ev['title']) ?></div>
        <div class="text-muted" style="font-size:.75rem">
          <?= formatDate($ev['event_date']) ?>
          <?= $ev['event_time'] ? ' · '.date('h:i A', strtotime($ev['event_time'])) : '' ?>
          <?= $ev['location'] ? ' · '.h($ev['location']) : '' ?>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

</div>

<?php if (isOfficer()): ?>
<div class="xu-modal-overlay" id="xumodalEvent">
  <div class="xu-modal">
    <div class="xu-modal-header">
      <span class="xu-modal-title" id="eventModalTitle">Add Event</span>
      <button class="xu-modal-close" onclick="closeModal(this)"><i class="bi bi-x-lg"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" id="ev_action" value="create">
      <input type="hidden" name="id"     id="ev_id"     value="">
      <div class="xu-modal-body">
        <div class="mb-3">
          <label class="form-label">Title *</label>
          <input id="ev_title" name="title" class="form-control" required>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Type</label>
            <select id="ev_type" name="type" class="form-control">
              <option value="rehearsal">Rehearsal</option>
              <option value="performance">Performance</option>
              <option value="meeting">Meeting</option>
              <option value="competition">Competition</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Date *</label>
            <input id="ev_date" name="event_date" type="date" class="form-control" required>
          </div>
        </div>
        <div class="row g-3 mt-0">
          <div class="col-md-6">
            <label class="form-label">Time</label>
            <input id="ev_time" name="event_time" type="time" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Location</label>
            <input id="ev_loc" name="location" class="form-control">
          </div>
        </div>
        <div class="mt-3">
          <label class="form-label">Description</label>
          <textarea id="ev_desc" name="description" class="form-control"></textarea>
        </div>
      </div>
      <div class="xu-modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal(this)">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Event</button>
      </div>
    </form>
  </div>
</div>
<script>
function resetEventForm(){
  document.getElementById('ev_action').value='create';
  document.getElementById('eventModalTitle').textContent='Add Event';
  ['id','title','date','time','loc','desc'].forEach(k=>{const el=document.getElementById('ev_'+k);if(el)el.value='';});
  document.getElementById('ev_type').value='rehearsal';
}
function fillEvent(e){
  document.getElementById('ev_action').value='update';
  document.getElementById('eventModalTitle').textContent='Edit Event';
  document.getElementById('ev_id').value=e.id;
  document.getElementById('ev_title').value=e.title;
  document.getElementById('ev_type').value=e.type;
  document.getElementById('ev_date').value=e.event_date;
  document.getElementById('ev_time').value=e.event_time||'';
  document.getElementById('ev_loc').value=e.location||'';
  document.getElementById('ev_desc').value=e.description||'';
}
</script>
<?php endif; ?>

<?php layout_foot(); ?>
