<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
$user = currentUser();

// Moderators have their own Members Management page
// This page is for Band Members & Officers to see the member list (no moderators shown)
$members = dbQuery(
    'SELECT u.id, u.name, u.role, u.instrument, u.year_level, u.student_id,
            u.status, u.avatar_path, COALESCE(ps.total_points,0) AS penalty_points
     FROM users u
     LEFT JOIN penalty_summary ps ON ps.user_id = u.id
     WHERE u.role != "moderator" AND u.status = "active"
     ORDER BY u.role ASC, u.name ASC'
);

layout_head('Members', 'band-members');
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span class="card-title"><i class="bi bi-people me-2"></i>Band Members</span>
    <div class="input-group" style="max-width:260px">
      <span class="input-group-text"><i class="bi bi-search"></i></span>
      <input id="memberSearch" type="search" class="form-control" placeholder="Search…">
    </div>
  </div>
  <div class="card-body p-3">
    <?php if (!$members): ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="bi bi-people"></i></div>
      <p>No members found.</p>
    </div>
    <?php else: ?>
    <div class="row g-3" id="memberGrid">
      <?php foreach ($members as $m):
        $initials = strtoupper(substr($m['name'], 0, 1));
        $isMe = $m['id'] === $user['id'];
      ?>
      <div class="col-sm-6 col-md-4 col-lg-3 member-item">
        <div class="card h-100 text-center p-3" style="border:1.5px solid #e3e6ea;border-radius:.6rem">
          <?php if (!empty($m['avatar_path']) && file_exists(__DIR__ . '/' . $m['avatar_path'])): ?>
          <img src="<?= h($m['avatar_path']) ?>" alt="<?= h($m['name']) ?>"
               style="width:64px;height:64px;border-radius:50%;object-fit:cover;margin:0 auto .75rem">
          <?php else: ?>
          <div class="user-avatar mx-auto mb-3" style="width:64px;height:64px;font-size:1.4rem">
            <?= h($initials) ?>
          </div>
          <?php endif; ?>
          <div class="fw-bold" style="color:var(--xu-navy)"><?= h($m['name']) ?><?= $isMe ? ' <span class="badge text-bg-info" style="font-size:.65rem">You</span>' : '' ?></div>
          <div class="mb-2"><?= roleBadge($m['role']) ?></div>
          <?php if ($m['instrument']): ?>
          <div class="text-muted small"><i class="bi bi-music-note me-1"></i><?= h($m['instrument']) ?></div>
          <?php endif; ?>
          <?php if ($m['year_level']): ?>
          <div class="text-muted small"><i class="bi bi-mortarboard me-1"></i><?= h($m['year_level']) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const inp = document.getElementById('memberSearch');
  if (!inp) return;
  inp.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.member-item').forEach(function(item) {
      item.style.display = item.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
});
</script>

<?php layout_foot(); ?>
