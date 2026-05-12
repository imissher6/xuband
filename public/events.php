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

    if ($action === 'bulk_delete') {
        $ids = array_map('intval', $_POST['ids'] ?? []);
        if ($ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            dbExecute("DELETE FROM events WHERE id IN ($placeholders)", $ids);
            flash('success', count($ids) . ' event(s) deleted.');
        }
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

layout_head('Events Calendar', 'events');
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

<div class="row g-3 align-items-start">

<!-- Calendar -->
<div class="col-lg-8">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-2">
        <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-chevron-left"></i>
        </a>
        <span class="card-title"><?= $monthName ?></span>
        <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-chevron-right"></i>
        </a>
      </div>
      <?php if (isOfficer()): ?>
      <button class="btn btn-primary btn-sm" onclick="openModal('xumodalEvent'); resetEventForm()">
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
          <span class="cal-event-dot <?= h($ev['type']) ?>"
            onclick="viewEvent(<?= htmlspecialchars(json_encode($ev), ENT_QUOTES) ?>)"
            title="<?= h($ev['title']) ?>"
            role="button" tabindex="0"
            style="cursor:pointer"><?= h($ev['title']) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endfor; ?>
      </div>
      <div class="d-flex gap-3 mt-3 flex-wrap" style="font-size:.75rem">
        <span><span class="d-inline-block me-1" style="width:10px;height:10px;background:var(--xu-navy);border-radius:2px"></span>Rehearsal</span>
        <span><span class="d-inline-block me-1" style="width:10px;height:10px;background:#7c3aed;border-radius:2px"></span>Performance</span>
        <span><span class="d-inline-block me-1" style="width:10px;height:10px;background:#16a34a;border-radius:2px"></span>Meeting</span>
        <span><span class="d-inline-block me-1" style="width:10px;height:10px;background:#dc3545;border-radius:2px"></span>Competition</span>
        <span><span class="d-inline-block me-1" style="width:10px;height:10px;background:var(--xu-gold);border-radius:2px"></span>Other</span>
      </div>
    </div>
  </div>
</div>

<!-- Event List -->
<div class="col-lg-4">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span class="card-title"><i class="bi bi-list-ul me-2"></i>All Events</span>
      <?php if (isOfficer() && $allEvents): ?>
      <div class="d-flex gap-1 flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="evSelectAllBtn" onclick="evToggleAll()">
          <i class="bi bi-check-all"></i>
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger d-none" id="evBulkBtn"
          onclick="evBulkDelete()">
          <i class="bi bi-trash me-1"></i><span id="evBulkCount">0</span>
        </button>
      </div>
      <?php endif; ?>
    </div>
    <form method="POST" id="bulkForm-events">
      <input type="hidden" name="action" value="bulk_delete">
      <div style="max-height:520px;overflow-y:auto">
        <?php if (!$allEvents): ?>
        <div class="empty-state"><div class="empty-icon"><i class="bi bi-calendar-x"></i></div><p>No events yet.</p></div>
        <?php else: foreach ($allEvents as $ev):
          $isPast = strtotime($ev['event_date']) < strtotime(date('Y-m-d'));
          $evJson = htmlspecialchars(json_encode($ev), ENT_QUOTES);
        ?>
        <div class="px-3 py-2 border-bottom <?= $isPast ? 'opacity-50' : '' ?>"
             style="cursor:pointer"
             onclick="viewEvent(<?= $evJson ?>)"
             role="button" tabindex="0">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-1"
               onclick="event.stopPropagation()">
            <div class="d-flex align-items-center gap-2">
              <?php if (isOfficer()): ?>
              <input type="checkbox" name="ids[]" value="<?= $ev['id'] ?>"
                class="form-check-input ev-bulk-cb" onchange="evUpdateCount()"
                onclick="event.stopPropagation()">
              <?php endif; ?>
              <span class="badge bg-secondary"><?= h(ucfirst($ev['type'])) ?></span>
            </div>
            <?php if (isOfficer()): ?>
            <div class="d-flex gap-1" onclick="event.stopPropagation()">
              <button type="button" class="btn btn-xs btn-outline-secondary"
                onclick="openModal('xumodalEvent'); fillEvent(<?= $evJson ?>)">
                <i class="bi bi-pencil"></i>
              </button>
              <button type="button" class="btn btn-xs btn-outline-danger"
                onclick="if(confirm('Delete this event?')){document.getElementById('evDelId').value='<?= $ev['id'] ?>';document.getElementById('evDelForm').submit();}">
                <i class="bi bi-trash"></i>
              </button>
            </div>
            <?php endif; ?>
          </div>
          <div class="fw-bold small" style="color:var(--xu-navy)"><?= h($ev['title']) ?></div>
          <div class="text-muted" style="font-size:.75rem">
            <?= formatDate($ev['event_date']) ?>
            <?= $ev['event_time'] ? ' · '.date('h:i A', strtotime($ev['event_time'])) : '' ?>
            <?= $ev['location'] ? ' · '.h($ev['location']) : '' ?>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </form>
  </div>
</div>

</div>

<!-- Event Detail View Modal (all users) -->
<div class="xu-modal-overlay" id="xumodalEventView">
  <div class="xu-modal" style="max-width:560px">
    <div class="xu-modal-header">
      <span class="xu-modal-title" id="evViewTitle">Event Details</span>
      <button class="xu-modal-close" onclick="closeModal(this)"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="xu-modal-body">
      <div class="mb-3">
        <span id="evViewTypeBadge" class="badge bg-secondary me-2"></span>
      </div>
      <div class="row g-3">
        <div class="col-6">
          <div class="text-muted small">Date</div>
          <div class="fw-bold" id="evViewDate"></div>
        </div>
        <div class="col-6">
          <div class="text-muted small">Time</div>
          <div class="fw-bold" id="evViewTime"></div>
        </div>
        <div class="col-6">
          <div class="text-muted small">Location</div>
          <div class="fw-bold" id="evViewLocation"></div>
        </div>
        <div class="col-6">
          <div class="text-muted small">Organizer</div>
          <div class="fw-bold" id="evViewOrganizer"></div>
        </div>
      </div>
      <div id="evViewDescWrap" class="mt-3" style="display:none">
        <div class="text-muted small mb-1">Description</div>
        <p id="evViewDesc" style="white-space:pre-line;line-height:1.7;color:var(--text)"></p>
      </div>
    </div>
    <div class="xu-modal-footer">
      <?php if (isOfficer()): ?>
      <button type="button" class="btn btn-outline-secondary btn-sm" id="evViewEditBtn"
        onclick="openModal('xumodalEvent'); fillEvent(window._viewingEvent)">
        <i class="bi bi-pencil me-1"></i>Edit
      </button>
      <?php endif; ?>
      <button type="button" class="btn btn-outline-secondary" onclick="closeModal(this)">Close</button>
    </div>
  </div>
</div>

<?php if (isOfficer()): ?>
<!-- Single delete form -->
<form method="POST" id="evDelForm" style="display:none">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id" id="evDelId" value="">
</form>

<!-- Add/Edit Modal -->
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
            <select id="ev_type" name="type" class="form-select">
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
          <textarea id="ev_desc" name="description" class="form-control" rows="3"></textarea>
        </div>
      </div>
      <div class="xu-modal-footer">
        <button type="button" class="btn btn-outline-secondary" onclick="closeModal(this)">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Event</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
window._viewingEvent = null;
function viewEvent(e) {
  window._viewingEvent = e;
  document.getElementById('evViewTitle').textContent   = e.title;
  document.getElementById('evViewTypeBadge').textContent = (e.type||'').charAt(0).toUpperCase()+(e.type||'').slice(1);
  document.getElementById('evViewDate').textContent     = e.event_date || '—';
  document.getElementById('evViewTime').textContent     = e.event_time ? formatTime12(e.event_time) : '—';
  document.getElementById('evViewLocation').textContent = e.location || '—';
  document.getElementById('evViewOrganizer').textContent = e.organizer || '—';
  var descWrap = document.getElementById('evViewDescWrap');
  var descEl   = document.getElementById('evViewDesc');
  if (e.description) { descEl.textContent = e.description; descWrap.style.display = ''; }
  else { descWrap.style.display = 'none'; }
  openModal('xumodalEventView');
}
function formatTime12(t) {
  if (!t) return '—';
  var parts = t.split(':');
  var h = parseInt(parts[0]), m = parts[1];
  var ampm = h >= 12 ? 'PM' : 'AM';
  h = h % 12 || 12;
  return h + ':' + m + ' ' + ampm;
}
function resetEventForm() {
  document.getElementById('ev_action').value = 'create';
  document.getElementById('eventModalTitle').textContent = 'Add Event';
  ['id','title','date','time','loc','desc'].forEach(k => { const el = document.getElementById('ev_'+k); if(el) el.value=''; });
  document.getElementById('ev_type').value = 'rehearsal';
}
function fillEvent(e) {
  document.getElementById('ev_action').value = 'update';
  document.getElementById('eventModalTitle').textContent = 'Edit Event';
  document.getElementById('ev_id').value    = e.id;
  document.getElementById('ev_title').value = e.title;
  document.getElementById('ev_type').value  = e.type;
  document.getElementById('ev_date').value  = e.event_date;
  document.getElementById('ev_time').value  = e.event_time  || '';
  document.getElementById('ev_loc').value   = e.location    || '';
  document.getElementById('ev_desc').value  = e.description || '';
  // Close view modal if open, open edit modal
  document.querySelectorAll('.xu-modal-overlay.open').forEach(m => m.classList.remove('open'));
  openModal('xumodalEvent');
}
function evUpdateCount() {
  const n = document.querySelectorAll('.ev-bulk-cb:checked').length;
  document.getElementById('evBulkCount').textContent = n;
  document.getElementById('evBulkBtn').classList.toggle('d-none', n === 0);
}
function evToggleAll() {
  const cbs = document.querySelectorAll('.ev-bulk-cb');
  const allChecked = [...cbs].every(c => c.checked);
  cbs.forEach(c => c.checked = !allChecked);
  evUpdateCount();
}
function evBulkDelete() {
  const n = document.querySelectorAll('.ev-bulk-cb:checked').length;
  if (!n) return;
  if (!confirm('Delete ' + n + ' selected event(s)?')) return;
  document.getElementById('bulkForm-events').submit();
}
// Keyboard accessibility
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('[role=button]').forEach(function(el) {
    el.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); el.click(); }
    });
  });
});
</script>

<?php layout_foot(); ?>
