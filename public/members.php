<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole(['moderator','officer']);
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? 'password';
        $role     = $_POST['role'] ?? 'member';
        $instr    = trim($_POST['instrument'] ?? '');
        $yr       = trim($_POST['year_level'] ?? '');
        $sid      = trim($_POST['student_id'] ?? '');
        $contact  = trim($_POST['contact_number'] ?? '');
        if ($role === 'moderator' && $user['role'] !== 'moderator') $role = 'member';

        if (!$name || !$email) { flash('error', 'Name and email are required.'); redirect('/members.php'); }
        $exists = dbQueryOne('SELECT id FROM users WHERE email = ?', [$email]);
        if ($exists) { flash('error', 'Email already in use.'); redirect('/members.php'); }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        dbInsert('INSERT INTO users (name,email,password_hash,role,instrument,year_level,student_id,contact_number) VALUES (?,?,?,?,?,?,?,?)',
            [$name,$email,$hash,$role,$instr,$yr,$sid,$contact]);
        flash('success', "Member $name added.");
        redirect('/members.php');
    }

    if ($action === 'update') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email= trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'member';
        $instr= trim($_POST['instrument'] ?? '');
        $yr   = trim($_POST['year_level'] ?? '');
        $sid  = trim($_POST['student_id'] ?? '');
        $contact = trim($_POST['contact_number'] ?? '');
        $status  = $_POST['status'] ?? 'active';
        if ($role === 'moderator' && $user['role'] !== 'moderator') $role = 'member';

        dbExecute('UPDATE users SET name=?,email=?,role=?,instrument=?,year_level=?,student_id=?,contact_number=?,status=? WHERE id=?',
            [$name,$email,$role,$instr,$yr,$sid,$contact,$status,$id]);
        flash('success', 'Member updated.');
        redirect('/members.php');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === $user['id']) { flash('error', 'Cannot delete yourself.'); redirect('/members.php'); }
        dbExecute('DELETE FROM users WHERE id = ?', [$id]);
        flash('success', 'Member removed.');
        redirect('/members.php');
    }

    if ($action === 'reset_password') {
        $id = (int)($_POST['id'] ?? 0);
        $pw = $_POST['new_password'] ?? 'password';
        $hash = password_hash($pw, PASSWORD_BCRYPT);
        dbExecute('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $id]);
        flash('success', 'Password reset.');
        redirect('/members.php');
    }
}

$members = dbQuery('SELECT u.*, COALESCE(ps.total_points,0) AS penalty_points
    FROM users u
    LEFT JOIN penalty_summary ps ON ps.user_id = u.id
    ORDER BY u.role ASC, u.name ASC');

layout_head('Members', 'members');
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

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="card-title"><i class="bi bi-people me-2"></i>Band Members</span>
    <button class="btn btn-primary btn-sm" data-modal="modalAdd">
      <i class="bi bi-person-plus me-1"></i> Add Member
    </button>
  </div>
  <div class="card-body py-3 px-3">
    <div class="input-group" style="max-width:320px">
      <span class="input-group-text"><i class="bi bi-search"></i></span>
      <input id="tableSearch" type="search" class="form-control" placeholder="Search members…">
    </div>
  </div>
  <div class="table-wrap" data-searchable>
    <table>
      <thead>
        <tr>
          <th>Name</th><th>Email</th><th>Role</th><th>Instrument</th>
          <th>Year</th><th>Student ID</th><th>Status</th><th>Penalty Pts</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($members as $m): ?>
        <tr>
          <td><strong><?= h($m['name']) ?></strong></td>
          <td class="small text-muted"><?= h($m['email']) ?></td>
          <td><?= roleBadge($m['role']) ?></td>
          <td><?= h($m['instrument'] ?: '—') ?></td>
          <td><?= h($m['year_level'] ?: '—') ?></td>
          <td class="small"><?= h($m['student_id'] ?: '—') ?></td>
          <td><?= statusBadge($m['status']) ?></td>
          <td class="<?= penaltyColor((float)$m['penalty_points']) ?> fw-bold"><?= $m['penalty_points'] ?></td>
          <td>
            <button class="btn btn-xs btn-outline" data-modal="modalEdit"
              onclick="fillEdit(<?= htmlspecialchars(json_encode($m), ENT_QUOTES) ?>)">
              <i class="bi bi-pencil"></i> Edit
            </button>
            <?php if ($m['id'] !== $user['id']): ?>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $m['id'] ?>">
              <button type="submit" class="btn btn-xs btn-danger" data-confirm="Delete <?= h($m['name']) ?>?">
                <i class="bi bi-trash"></i>
              </button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$members): ?>
        <tr><td colspan="9">
          <div class="empty-state">
            <div class="empty-icon"><i class="bi bi-people"></i></div>
            <p>No members yet.</p>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="modalAdd">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title"><i class="bi bi-person-plus me-2"></i>Add Member</span>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Full Name *</label>
            <input name="name" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email *</label>
            <input name="email" type="email" class="form-control" required>
          </div>
        </div>
        <div class="row g-3 mt-0">
          <div class="col-md-6">
            <label class="form-label">Password</label>
            <input name="password" class="form-control" placeholder="Default: password">
          </div>
          <div class="col-md-6">
            <label class="form-label">Role</label>
            <select name="role" class="form-control">
              <option value="member">Member</option>
              <option value="officer">Officer</option>
              <?php if ($user['role'] === 'moderator'): ?><option value="moderator">Moderator</option><?php endif; ?>
            </select>
          </div>
        </div>
        <div class="row g-3 mt-0">
          <div class="col-md-6">
            <label class="form-label">Instrument</label>
            <input name="instrument" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Year Level</label>
            <input name="year_level" class="form-control" placeholder="e.g. 2nd Year">
          </div>
        </div>
        <div class="row g-3 mt-0">
          <div class="col-md-6">
            <label class="form-label">Student ID</label>
            <input name="student_id" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Contact</label>
            <input name="contact_number" class="form-control">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-person-plus me-1"></i>Add Member
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="modalEdit">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Member</span>
      <button class="modal-close" data-modal-close><i class="bi bi-x-lg"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="edit_id">
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Full Name *</label>
            <input id="edit_name" name="name" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email *</label>
            <input id="edit_email" name="email" type="email" class="form-control" required>
          </div>
        </div>
        <div class="row g-3 mt-0">
          <div class="col-md-6">
            <label class="form-label">Role</label>
            <select id="edit_role" name="role" class="form-control">
              <option value="member">Member</option>
              <option value="officer">Officer</option>
              <?php if ($user['role'] === 'moderator'): ?><option value="moderator">Moderator</option><?php endif; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Status</label>
            <select id="edit_status" name="status" class="form-control">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
        <div class="row g-3 mt-0">
          <div class="col-md-6">
            <label class="form-label">Instrument</label>
            <input id="edit_instrument" name="instrument" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Year Level</label>
            <input id="edit_year_level" name="year_level" class="form-control">
          </div>
        </div>
        <div class="row g-3 mt-0">
          <div class="col-md-6">
            <label class="form-label">Student ID</label>
            <input id="edit_student_id" name="student_id" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Contact</label>
            <input id="edit_contact_number" name="contact_number" class="form-control">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-floppy me-1"></i>Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function fillEdit(m) {
  ['id','name','email','role','status','instrument','year_level','student_id','contact_number'].forEach(k => {
    const el = document.getElementById('edit_' + k);
    if (el) el.value = m[k] || '';
  });
}
</script>

<?php layout_foot(); ?>
