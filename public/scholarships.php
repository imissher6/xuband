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

    if ($action === 'create_school_year') {
        $label = trim($_POST['label'] ?? '');
        if (!$label) { flash('error', 'School year label required.'); redirect('/scholarships.php'); }
        $exists = dbQueryOne('SELECT id FROM school_years WHERE label = ?', [$label]);
        if ($exists) { flash('error', 'School year already exists.'); redirect('/scholarships.php'); }
        $sy_id = dbInsert('INSERT INTO school_years (label, created_by) VALUES (?,?)', [$label, $user['id']]);
        foreach (['1st Semester','2nd Semester','Summer'] as $term) {
            dbInsert('INSERT INTO scholarship_terms (school_year_id, term) VALUES (?,?)', [$sy_id, $term]);
        }
        flash('success', "School year $label created with 3 terms.");
        redirect('/scholarships.php');
    }

    if ($action === 'delete_school_year') {
        $id = (int)($_POST['id'] ?? 0);
        dbExecute('DELETE FROM school_years WHERE id = ?', [$id]);
        flash('success', 'School year deleted.');
        redirect('/scholarships.php');
    }

    if ($action === 'update_status') {
        $scholarship_id = (int)($_POST['scholarship_id'] ?? 0);
        $term_id        = (int)($_POST['term_id'] ?? 0);
        $uid            = (int)($_POST['user_id'] ?? 0);
        $status         = $_POST['status'] ?? 'Not Scholar';
        $allowed = ['Full Scholar','Half Scholar','Not Scholar'];
        if (!in_array($status, $allowed)) { flash('error', 'Invalid status.'); redirect('/scholarships.php'); }

        if ($scholarship_id) {
            dbExecute('UPDATE scholarships SET status=?,updated_by=?,updated_at=NOW() WHERE id=?',
                [$status, $user['id'], $scholarship_id]);
        } else {
            dbExecute('INSERT INTO scholarships (term_id,user_id,status,updated_by) VALUES (?,?,?,?)
                       ON DUPLICATE KEY UPDATE status=VALUES(status),updated_by=VALUES(updated_by),updated_at=NOW()',
                [$term_id, $uid, $status, $user['id']]);
        }
        flash('success', 'Scholarship status updated.');
        redirect('/scholarships.php');
    }
}

$schoolYears = dbQuery('SELECT sy.*, u.name AS creator FROM school_years sy JOIN users u ON u.id=sy.created_by ORDER BY sy.label DESC');

$yearsData = [];
foreach ($schoolYears as $sy) {
    $terms = dbQuery('SELECT * FROM scholarship_terms WHERE school_year_id=? ORDER BY FIELD(term,"1st Semester","2nd Semester","Summer")', [$sy['id']]);
    $termsData = [];
    foreach ($terms as $t) {
        if (isOfficer()) {
            $records = dbQuery(
                'SELECT u.id AS user_id, u.name, u.instrument, u.year_level,
                    COALESCE(s.id,0) AS scholarship_id,
                    COALESCE(s.status,"Not Scholar") AS status,
                    ub.name AS updated_by_name, s.updated_at
                 FROM users u
                 LEFT JOIN scholarships s ON s.user_id=u.id AND s.term_id=?
                 LEFT JOIN users ub ON ub.id=s.updated_by
                 WHERE u.role="member" AND u.status="active"
                 ORDER BY u.name',
                [$t['id']]
            );
        } else {
            $records = dbQuery(
                'SELECT u.id AS user_id, u.name,
                    COALESCE(s.id,0) AS scholarship_id,
                    COALESCE(s.status,"Not Scholar") AS status
                 FROM users u
                 LEFT JOIN scholarships s ON s.user_id=u.id AND s.term_id=?
                 WHERE u.id=?',
                [$t['id'], $user['id']]
            );
        }
        $termsData[] = ['term' => $t, 'records' => $records];
    }
    $yearsData[] = ['sy' => $sy, 'terms' => $termsData];
}

layout_head('Scholarships', 'scholarships');
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

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
  <h2 class="mb-0" style="color:var(--navy)"><i class="bi bi-award me-2"></i>Scholarship Records</h2>
  <?php if (isOfficer()): ?>
  <button class="btn btn-primary btn-sm" onclick="openModal('modalSY')">
    <i class="bi bi-plus-lg me-1"></i> Create School Year
  </button>
  <?php else: ?>
  <div class="alert alert-info mb-0 py-2 px-3 small d-flex align-items-center gap-2">
    <i class="bi bi-info-circle-fill"></i> Showing your scholarship status only.
  </div>
  <?php endif; ?>
</div>

<?php if (!$yearsData): ?>
<div class="card">
  <div class="empty-state p-5">
    <div class="empty-icon"><i class="bi bi-award"></i></div>
    <p><?= isOfficer() ? 'No school years yet. Create one above.' : 'No scholarship records yet.' ?></p>
  </div>
</div>
<?php endif; ?>

<!-- Bootstrap Accordion -->
<div class="accordion" id="scholarshipAccordion">
<?php foreach ($yearsData as $yi => $yd): $sy = $yd['sy']; $syId = 'sy-' . $sy['id']; ?>

<div class="accordion-item mb-3 border rounded shadow-sm">
  <h2 class="accordion-header">
    <button class="accordion-button collapsed fw-bold" type="button"
            data-bs-toggle="collapse" data-bs-target="#collapse-<?= $syId ?>"
            aria-expanded="false" aria-controls="collapse-<?= $syId ?>">
      <i class="bi bi-calendar-range me-2"></i>
      <?= h($sy['label']) ?>
      <span class="ms-2 text-muted fw-normal small">3 terms</span>
    </button>
  </h2>
  <div id="collapse-<?= $syId ?>" class="accordion-collapse collapse">
    <div class="accordion-body p-0">

      <?php if (isOfficer()): ?>
      <div class="px-3 py-2 border-bottom d-flex justify-content-end">
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="delete_school_year">
          <input type="hidden" name="id" value="<?= $sy['id'] ?>">
          <button class="btn btn-xs btn-danger" data-confirm="Delete school year <?= h($sy['label']) ?>?">
            <i class="bi bi-trash me-1"></i>Delete Year
          </button>
        </form>
      </div>
      <?php endif; ?>

      <!-- Nested accordion for terms -->
      <div class="accordion accordion-flush" id="termAccordion-<?= $sy['id'] ?>">
        <?php foreach ($yd['terms'] as $tdi => $td): $t = $td['term']; $records = $td['records']; $termId = 'term-' . $t['id']; ?>
        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed py-2 fw-semibold" type="button"
                    data-bs-toggle="collapse" data-bs-target="#collapse-<?= $termId ?>"
                    aria-expanded="false" aria-controls="collapse-<?= $termId ?>"
                    style="background:var(--bg);color:var(--navy);font-size:.9rem">
              <i class="bi bi-calendar-check me-2"></i>
              <?= h($t['term']) ?>
              <span class="ms-2 text-muted fw-normal small">(<?= count($records) ?> member<?= count($records)!=1?'s':'' ?>)</span>
            </button>
          </h2>
          <div id="collapse-<?= $termId ?>" class="accordion-collapse collapse">
            <div class="accordion-body p-0">
              <?php if (!$records): ?>
              <div class="empty-state p-3"><p>No members found.</p></div>
              <?php else: ?>
              <div class="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>Member</th>
                      <?php if (isOfficer()): ?><th>Instrument</th><th>Year</th><?php endif; ?>
                      <th>Scholarship Status</th>
                      <?php if (isOfficer()): ?><th>Updated By</th><th>Action</th><?php endif; ?>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($records as $r): ?>
                    <tr>
                      <td><strong><?= h($r['name']) ?></strong></td>
                      <?php if (isOfficer()): ?>
                      <td><?= h($r['instrument']??'—') ?></td>
                      <td class="small text-muted"><?= h($r['year_level']??'—') ?></td>
                      <?php endif; ?>
                      <td><?= scholarshipBadge($r['status']) ?></td>
                      <?php if (isOfficer()): ?>
                      <td class="small text-muted"><?= h($r['updated_by_name']??'—') ?></td>
                      <td>
                        <form method="POST" class="d-flex gap-2 align-items-center">
                          <input type="hidden" name="action" value="update_status">
                          <input type="hidden" name="scholarship_id" value="<?= $r['scholarship_id'] ?>">
                          <input type="hidden" name="term_id" value="<?= $t['id'] ?>">
                          <input type="hidden" name="user_id" value="<?= $r['user_id'] ?>">
                          <select name="status" class="form-control" style="width:140px">
                            <option value="Full Scholar"  <?= $r['status']==='Full Scholar' ?'selected':'' ?>>Full Scholar</option>
                            <option value="Half Scholar"  <?= $r['status']==='Half Scholar' ?'selected':'' ?>>Half Scholar</option>
                            <option value="Not Scholar"   <?= $r['status']==='Not Scholar'  ?'selected':'' ?>>Not Scholar</option>
                          </select>
                          <button type="submit" class="btn btn-xs btn-primary">
                            <i class="bi bi-floppy"></i> Save
                          </button>
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
          </div>
        </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>
</div>

<?php endforeach; ?>
</div>

<?php if (isOfficer()): ?>
<div class="modal-overlay" id="modalSY">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Create School Year</span>
      <button class="modal-close" onclick="closeModal(this)"><i class="bi bi-x-lg"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create_school_year">
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">School Year Label *</label>
          <input name="label" class="form-control" placeholder="e.g. 2024-2025" required>
          <div class="form-text text-muted">Auto-creates 1st Semester, 2nd Semester, and Summer terms.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal(this)">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-plus-lg me-1"></i>Create
        </button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php layout_foot(); ?>
