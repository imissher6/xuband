<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name    = trim($_POST['name'] ?? '');
        $instr   = trim($_POST['instrument'] ?? '');
        $yr      = ($user['role'] !== 'moderator') ? trim($_POST['year_level'] ?? '') : null;
        $contact = trim($_POST['contact_number'] ?? '');
        $notes   = trim($_POST['profile_notes'] ?? '');
        if (!$name) { flash('error', 'Name is required.'); redirect('/profile.php'); }

        // Handle avatar upload
        $avatarPath = null;
        if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (!in_array($ext, $allowed)) {
                flash('error', 'Avatar must be an image (jpg, png, gif, webp).');
                redirect('/profile.php');
            }
            if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
                flash('error', 'Avatar must be under 2MB.');
                redirect('/profile.php');
            }
            $avatarDir = __DIR__ . '/uploads/avatars/';
            if (!is_dir($avatarDir)) mkdir($avatarDir, 0755, true);
            $filename   = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
            $destPath   = $avatarDir . $filename;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destPath)) {
                $avatarPath = '/uploads/avatars/' . $filename;
            }
        }

        if ($avatarPath) {
            if ($user['role'] !== 'moderator') {
                dbExecute('UPDATE users SET name=?,instrument=?,year_level=?,contact_number=?,profile_notes=?,avatar_path=? WHERE id=?',
                    [$name,$instr,$yr,$contact,$notes,$avatarPath,$user['id']]);
            } else {
                dbExecute('UPDATE users SET name=?,instrument=?,contact_number=?,profile_notes=?,avatar_path=? WHERE id=?',
                    [$name,$instr,$contact,$notes,$avatarPath,$user['id']]);
            }
        } else {
            if ($user['role'] !== 'moderator') {
                dbExecute('UPDATE users SET name=?,instrument=?,year_level=?,contact_number=?,profile_notes=? WHERE id=?',
                    [$name,$instr,$yr,$contact,$notes,$user['id']]);
            } else {
                dbExecute('UPDATE users SET name=?,instrument=?,contact_number=?,profile_notes=? WHERE id=?',
                    [$name,$instr,$contact,$notes,$user['id']]);
            }
        }
        $_SESSION['user_name'] = $name;
        flash('success', 'Profile updated.');
        redirect('/profile.php');
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $dbUser  = dbQueryOne('SELECT password_hash FROM users WHERE id = ?', [$user['id']]);
        if (!password_verify($current, $dbUser['password_hash'])) {
            flash('error', 'Current password is incorrect.');
        } elseif (strlen($new) < 6) {
            flash('error', 'New password must be at least 6 characters.');
        } elseif ($new !== $confirm) {
            flash('error', 'Passwords do not match.');
        } else {
            dbExecute('UPDATE users SET password_hash=? WHERE id=?', [password_hash($new, PASSWORD_BCRYPT), $user['id']]);
            flash('success', 'Password changed.');
        }
        redirect('/profile.php');
    }
}

$profile       = dbQueryOne('SELECT * FROM users WHERE id = ?', [$user['id']]);
$myScholarship = dbQueryOne(
    'SELECT s.*, st.term, sy.label AS year_label
     FROM scholarships s
     JOIN scholarship_terms st ON st.id = s.term_id
     JOIN school_years sy ON sy.id = st.school_year_id
     WHERE s.user_id = ? ORDER BY s.id DESC LIMIT 1',
    [$user['id']]
);
$myAttendance  = dbQuery('SELECT a.*, e.title AS event_title, e.event_date, e.type FROM attendance a JOIN events e ON e.id = a.event_id WHERE a.user_id = ? ORDER BY e.event_date DESC LIMIT 10', [$user['id']]);
$myPenalty     = dbQueryOne('SELECT * FROM penalty_summary WHERE user_id = ?', [$user['id']]);

// Officers and members both see attendance/penalty stats
$showStats = in_array($user['role'], ['member', 'officer']);

layout_head('My Profile', 'profile');
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

<!-- Left: Profile Info + Password -->
<div class="col-lg-4">
  <div class="card mb-3">
    <div class="card-header">
      <span class="card-title"><i class="bi bi-person me-2"></i>Profile Information</span>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="update_profile">
      <div class="card-body">
        <!-- Avatar -->
        <div class="text-center mb-3">
          <?php if (!empty($profile['avatar_path']) && file_exists(__DIR__ . $profile['avatar_path'])): ?>
          <img src="<?= h($profile['avatar_path']) ?>" alt="Avatar"
               id="avatarPreview"
               style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid var(--xu-navy);margin-bottom:.5rem">
          <?php else: ?>
          <div class="user-avatar mx-auto mb-2" id="avatarInitials"
               style="width:72px;height:72px;font-size:1.6rem">
            <?= strtoupper(substr($profile['name'],0,1)) ?>
          </div>
          <img id="avatarPreview" src="" alt="Avatar"
               style="display:none;width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid var(--xu-navy);margin-bottom:.5rem">
          <?php endif; ?>
          <?= roleBadge($profile['role']) ?>
          <div class="mt-2">
            <label class="btn btn-sm btn-outline-secondary" for="avatarInput">
              <i class="bi bi-camera me-1"></i>Change Photo
            </label>
            <input id="avatarInput" name="avatar" type="file" accept="image/*" style="display:none"
              onchange="previewAvatar(this)">
            <div class="text-muted" style="font-size:.72rem;margin-top:4px">JPG, PNG, WebP &middot; max 2MB</div>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Full Name *</label>
          <input name="name" class="form-control" value="<?= h($profile['name']) ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input class="form-control" value="<?= h($profile['email']) ?>" disabled>
        </div>
        <div class="row g-3">
          <div class="col-6">
            <label class="form-label">Instrument</label>
            <input name="instrument" class="form-control" value="<?= h($profile['instrument'] ?? '') ?>">
          </div>
          <?php if ($user['role'] !== 'moderator'): ?>
          <div class="col-6">
            <label class="form-label">Year Level</label>
            <input name="year_level" class="form-control" value="<?= h($profile['year_level'] ?? '') ?>">
          </div>
          <?php endif; ?>
        </div>
        <div class="mt-3">
          <label class="form-label">Contact Number</label>
          <input name="contact_number" class="form-control" value="<?= h($profile['contact_number'] ?? '') ?>">
        </div>
        <div class="mt-3">
          <label class="form-label">Notes</label>
          <textarea name="profile_notes" class="form-control"><?= h($profile['profile_notes'] ?? '') ?></textarea>
        </div>
        <div class="text-muted small mt-2">
          Student ID: <?= h($profile['student_id'] ?: '—') ?> &middot; Joined <?= formatDate($profile['created_at']) ?>
        </div>
      </div>
      <div class="card-footer">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-floppy me-1"></i>Save Changes
        </button>
      </div>
    </form>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title"><i class="bi bi-key me-2"></i>Change Password</span>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="change_password">
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label">Current Password</label>
          <input name="current_password" type="password" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">New Password</label>
          <input name="new_password" type="password" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Confirm Password</label>
          <input name="confirm_password" type="password" class="form-control" required>
        </div>
      </div>
      <div class="card-footer">
        <button type="submit" class="btn btn-gold">
          <i class="bi bi-shield-lock me-1"></i>Change Password
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Right: Stats + Attendance (member & officer) -->
<div class="col-lg-8">
  <?php if ($showStats): ?>

  <?php if ($myScholarship && $user['role'] === 'member'): ?>
  <div class="card mb-3">
    <div class="card-header">
      <span class="card-title"><i class="bi bi-award me-2"></i>My Scholarship</span>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-6">
          <div class="text-muted small">Status</div>
          <?= scholarshipBadge($myScholarship['status']) ?>
        </div>
        <div class="col-6">
          <div class="text-muted small">Semester</div>
          <div class="fw-bold"><?= h($myScholarship['term'] ?? '—') ?></div>
        </div>
        <div class="col-6">
          <div class="text-muted small">School Year</div>
          <div class="fw-bold"><?= h($myScholarship['year_label'] ?? '—') ?></div>
        </div>
        <div class="col-6">
          <div class="text-muted small">Last Updated</div>
          <div class="fw-bold"><?= $myScholarship['updated_at'] ? formatDate($myScholarship['updated_at']) : '—' ?></div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Penalty Summary -->
  <div class="card mb-3">
    <div class="card-header">
      <span class="card-title"><i class="bi bi-clipboard-check me-2"></i>My Attendance &amp; Penalties</span>
    </div>
    <div class="card-body">
      <div class="row g-3 mb-3 text-center">
        <div class="col-4">
          <div class="p-2 rounded" style="background:var(--bg)">
            <div class="fw-bold" style="font-size:1.5rem;color:var(--green)">
              <?= dbQueryOne('SELECT COUNT(*) AS n FROM attendance WHERE user_id=? AND status="present"',[$user['id']])['n']??0 ?>
            </div>
            <div class="text-muted small">Present</div>
          </div>
        </div>
        <div class="col-4">
          <div class="p-2 rounded" style="background:var(--bg)">
            <div class="fw-bold" style="font-size:1.5rem;color:var(--red)">
              <?= dbQueryOne('SELECT COUNT(*) AS n FROM attendance WHERE user_id=? AND status="absent"',[$user['id']])['n']??0 ?>
            </div>
            <div class="text-muted small">Absent</div>
          </div>
        </div>
        <div class="col-4">
          <div class="p-2 rounded" style="background:var(--bg)">
            <div class="fw-bold <?= penaltyColor((float)($myPenalty['total_points']??0)) ?>" style="font-size:1.5rem">
              <?= $myPenalty['total_points'] ?? 0 ?>
            </div>
            <div class="text-muted small">Penalty Pts</div>
          </div>
        </div>
      </div>

      <h6 class="mb-2">Recent Attendance</h6>
      <?php if (!$myAttendance): ?>
      <p class="text-muted small">No records yet.</p>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Event</th><th>Date</th><th>Status</th><th>Penalty</th></tr>
          </thead>
          <tbody>
            <?php foreach ($myAttendance as $a): ?>
            <tr>
              <td><?= h($a['event_title']) ?></td>
              <td class="small"><?= formatDate($a['event_date']) ?></td>
              <td><?= statusBadge($a['att_status'] ?? $a['status']) ?></td>
              <td class="<?= penaltyColor((float)$a['penalty_points']) ?> fw-bold"><?= $a['penalty_points'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <?php else: ?>
  <!-- Moderator — no attendance stats, just a welcome card -->
  <div class="card">
    <div class="card-body text-center py-5">
      <div class="empty-icon mb-3"><i class="bi bi-shield-check fs-1" style="color:var(--xu-navy)"></i></div>
      <h5 style="color:var(--xu-navy)">Moderator Account</h5>
      <p class="text-muted">Moderators manage the system and do not participate in attendance or scholarship tracking.</p>
    </div>
  </div>
  <?php endif; ?>
</div>

</div>

<script>
function previewAvatar(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      var preview = document.getElementById('avatarPreview');
      var initials = document.getElementById('avatarInitials');
      if (preview) {
        preview.src = e.target.result;
        preview.style.display = '';
      }
      if (initials) initials.style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>

<?php layout_foot(); ?>
