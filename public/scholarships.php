<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isOfficer()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'upsert') {
        $uid         = (int)($_POST['user_id'] ?? 0);
        $semester    = trim($_POST['semester'] ?? '');
        $acad_year   = trim($_POST['academic_year'] ?? '');
        $gpa         = $_POST['gpa'] !== '' ? (float)$_POST['gpa'] : null;
        $band_score  = $_POST['band_participation_score'] !== '' ? (int)$_POST['band_participation_score'] : null;
        $status      = $_POST['status'] ?? 'inactive';
        $allowance   = $_POST['monthly_allowance'] !== '' ? (float)$_POST['monthly_allowance'] : null;
        $notes       = trim($_POST['notes'] ?? '');
        $existing_id = (int)($_POST['scholarship_id'] ?? 0);

        if ($existing_id) {
            dbExecute('UPDATE scholarships SET semester=?,academic_year=?,gpa=?,band_participation_score=?,status=?,monthly_allowance=?,notes=?,updated_by=?,updated_at=NOW() WHERE id=?',
                [$semester,$acad_year,$gpa,$band_score,$status,$allowance,$notes,$user['id'],$existing_id]);
        } else {
            dbInsert('INSERT INTO scholarships (user_id,semester,academic_year,gpa,band_participation_score,status,monthly_allowance,notes,updated_by) VALUES (?,?,?,?,?,?,?,?,?)',
                [$uid,$semester,$acad_year,$gpa,$band_score,$status,$allowance,$notes,$user['id']]);
        }
        flash('success', 'Scholarship record saved.');
        redirect('/scholarships.php');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        dbExecute('DELETE FROM scholarships WHERE id = ?', [$id]);
        flash('success', 'Record deleted.');
        redirect('/scholarships.php');
    }
}

// View: moderator/officer sees all; member sees own
if (isOfficer()) {
    $records = dbQuery('SELECT s.*, u.name AS member_name, u.instrument, u.year_level,
        ub.name AS updated_by_name
        FROM scholarships s
        JOIN users u ON u.id = s.user_id
        LEFT JOIN users ub ON ub.id = s.updated_by
        ORDER BY s.updated_at DESC');
    $members = dbQuery('SELECT id, name FROM users WHERE role = "member" AND status = "active" ORDER BY name');
} else {
    $records = dbQuery('SELECT s.*, u.name AS member_name, u.instrument, u.year_level,
        ub.name AS updated_by_name
        FROM scholarships s
        JOIN users u ON u.id = s.user_id
        LEFT JOIN users ub ON ub.id = s.updated_by
        WHERE s.user_id = ?
        ORDER BY s.updated_at DESC', [$user['id']]);
    $members = [];
}

layout_head('Scholarships', 'scholarships');
?>

<?php if ($e = getFlash('error')): ?><div class="alert alert-error" data-auto-dismiss>⚠️ <?= h($e) ?></div><?php endif; ?>
<?php if ($s = getFlash('success')): ?><div class="alert alert-success" data-auto-dismiss>✅ <?= h($s) ?></div><?php endif; ?>

<?php if (!isOfficer()): ?>
<div class="alert alert-info">
  ℹ️ Showing your scholarship records. Contact an officer to update your status.
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <span class="card-title">🎓 Scholarship Monitoring</span>
    <?php if (isOfficer()): ?>
    <button class="btn btn-primary btn-sm" data-modal="modalScholarship" onclick="resetScholarshipForm()">+ Add Record</button>
    <?php endif; ?>
  </div>

  <?php if (!$records): ?>
  <div class="empty-state"><div class="empty-icon">🎓</div><p>No scholarship records.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Member</th><th>Semester</th><th>Year</th>
          <th>GPA</th><th>Band Score</th><th>Status</th>
          <th>Allowance</th><th>Updated By</th><th>Date</th>
          <?php if (isOfficer()): ?><th>Actions</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($records as $r): ?>
        <tr>
          <td><strong><?= h($r['member_name']) ?></strong>
            <?php if ($r['instrument']): ?><br><span class="text-xs text-muted"><?= h($r['instrument']) ?></span><?php endif; ?></td>
          <td><?= h($r['semester']) ?></td>
          <td><?= h($r['academic_year']) ?></td>
          <td><?= $r['gpa'] !== null ? number_format($r['gpa'],2) : '—' ?></td>
          <td><?= $r['band_participation_score'] !== null ? $r['band_participation_score'].'/100' : '—' ?></td>
          <td><?= statusBadge($r['status']) ?></td>
          <td><?= $r['monthly_allowance'] ? '₱'.number_format($r['monthly_allowance'],2) : '—' ?></td>
          <td class="text-sm text-muted"><?= h($r['updated_by_name'] ?? '—') ?></td>
          <td class="text-sm text-muted"><?= formatDate($r['updated_at']) ?></td>
          <?php if (isOfficer()): ?>
          <td>
            <button class="btn btn-xs btn-outline" data-modal="modalScholarship"
              onclick="fillScholarship(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)">Edit</button>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button class="btn btn-xs btn-danger" data-confirm="Delete this record?">Del</button>
            </form>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php if (isOfficer()): ?>
<!-- Add/Edit Scholarship Modal -->
<div class="modal-overlay" id="modalScholarship">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="scholarshipModalTitle">Add Scholarship Record</span>
      <button class="modal-close" data-modal-close>✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="upsert">
      <input type="hidden" name="scholarship_id" id="sch_id" value="">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Member *</label>
          <select id="sch_user_id" name="user_id" class="form-control" required>
            <option value="">Select member…</option>
            <?php foreach ($members as $m): ?>
            <option value="<?= $m['id'] ?>"><?= h($m['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row form-row-2">
          <div class="form-group"><label class="form-label">Semester *</label>
            <select id="sch_semester" name="semester" class="form-control">
              <option>1st Semester</option><option>2nd Semester</option><option>Summer</option>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Academic Year *</label>
            <input id="sch_academic_year" name="academic_year" class="form-control" placeholder="e.g. 2024-2025" required>
          </div>
        </div>
        <div class="form-row form-row-2">
          <div class="form-group"><label class="form-label">GPA</label>
            <input id="sch_gpa" name="gpa" type="number" step=".01" min="1" max="5" class="form-control" placeholder="1.00 – 5.00">
          </div>
          <div class="form-group"><label class="form-label">Band Participation Score (0-100)</label>
            <input id="sch_band" name="band_participation_score" type="number" min="0" max="100" class="form-control">
          </div>
        </div>
        <div class="form-row form-row-2">
          <div class="form-group"><label class="form-label">Status</label>
            <select id="sch_status" name="status" class="form-control">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="probation">Probation</option>
              <option value="terminated">Terminated</option>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Monthly Allowance (₱)</label>
            <input id="sch_allowance" name="monthly_allowance" type="number" step=".01" min="0" class="form-control">
          </div>
        </div>
        <div class="form-group"><label class="form-label">Notes</label>
          <textarea id="sch_notes" name="notes" class="form-control"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Save Record</button>
      </div>
    </form>
  </div>
</div>

<script>
function resetScholarshipForm() {
  document.getElementById('sch_id').value = '';
  document.getElementById('scholarshipModalTitle').textContent = 'Add Scholarship Record';
  ['user_id','semester','academic_year','gpa','band','status','allowance','notes'].forEach(k => {
    const el = document.getElementById('sch_' + k);
    if (el) el.value = '';
  });
  const sel = document.getElementById('sch_status');
  if (sel) sel.value = 'active';
}
function fillScholarship(r) {
  document.getElementById('scholarshipModalTitle').textContent = 'Edit Scholarship Record';
  document.getElementById('sch_id').value            = r.id;
  document.getElementById('sch_user_id').value       = r.user_id;
  document.getElementById('sch_semester').value      = r.semester;
  document.getElementById('sch_academic_year').value = r.academic_year;
  document.getElementById('sch_gpa').value           = r.gpa || '';
  document.getElementById('sch_band').value          = r.band_participation_score || '';
  document.getElementById('sch_status').value        = r.status;
  document.getElementById('sch_allowance').value     = r.monthly_allowance || '';
  document.getElementById('sch_notes').value         = r.notes || '';
}
</script>
<?php endif; ?>

<?php layout_foot(); ?>
