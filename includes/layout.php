<?php
// layout.php — shared header/sidebar/footer (Bootstrap 5 + Bootstrap Icons)

function layout_head(string $title = 'XUBand', string $pageKey = ''): void {
    $user = currentUser();
    $initials = strtoupper(implode('', array_map(fn($w) => $w[0], explode(' ', $user['name']))));
    $initials = substr($initials, 0, 2);
    $role = $user['role'];
    $currentPage = basename($_SERVER['PHP_SELF'], '.php');

    $isActive = fn(string $key) => ($key === $currentPage || $key === $pageKey) ? ' active' : '';

    // Avatar
    $profile = \dbQueryOne('SELECT avatar_path FROM users WHERE id = ?', [$user['id']]);
    $avatarPath = $profile['avatar_path'] ?? '';
    $hasAvatar = !empty($avatarPath) && file_exists(__DIR__ . '/../public' . $avatarPath);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($title) ?> — XUBand</title>
  <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="/assets/css/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<!-- Sidebar -->
<nav id="sidebar">
  <div class="sidebar-brand">
    <img src="/assets/img/xuband-logo.png" alt="XUBand Logo" class="sidebar-logo">
    <div class="sidebar-brand-text">
      <div class="brand-name">Xavier University Band</div>
      <div class="brand-sub">XU Band</div>
    </div>
  </div>
  <div class="sidebar-nav">
    <div class="sidebar-section">Main</div>
    <a href="/dashboard.php" class="sidebar-link<?= $isActive('dashboard') ?>">
      <i class="bi bi-house"></i><span class="sidebar-link-text"> Dashboard</span>
    </a>
    <a href="/announcements.php" class="sidebar-link<?= $isActive('announcements') ?>">
      <i class="bi bi-megaphone"></i><span class="sidebar-link-text"> Announcements</span>
    </a>
    <a href="/events.php" class="sidebar-link<?= $isActive('events') ?>">
      <i class="bi bi-calendar3"></i><span class="sidebar-link-text"> Events Calendar</span>
    </a>

    <div class="sidebar-section">Modules</div>
    <a href="/music-sheets.php" class="sidebar-link<?= $isActive('music-sheets') ?>">
      <i class="bi bi-music-note-beamed"></i><span class="sidebar-link-text"> Music Sheets</span>
    </a>
    <a href="/attendance.php" class="sidebar-link<?= $isActive('attendance') ?>">
      <i class="bi bi-check2-square"></i><span class="sidebar-link-text"> Attendance</span>
    </a>
    <a href="/scholarships.php" class="sidebar-link<?= $isActive('scholarships') ?>">
      <i class="bi bi-award"></i><span class="sidebar-link-text"> Scholarships</span>
    </a>

    <?php if ($role === 'moderator'): ?>
    <div class="sidebar-section">Management</div>
    <a href="/members.php" class="sidebar-link<?= $isActive('members') ?>">
      <i class="bi bi-people"></i><span class="sidebar-link-text"> Members</span>
    </a>
    <?php endif; ?>

    <div class="sidebar-section">Account</div>
    <a href="/profile.php" class="sidebar-link<?= $isActive('profile') ?>">
      <i class="bi bi-person"></i><span class="sidebar-link-text"> My Profile</span>
    </a>
    <?php if (in_array($role, ['member', 'officer'])): ?>
    <a href="/band-members.php" class="sidebar-link<?= $isActive('band-members') ?>">
      <i class="bi bi-people"></i><span class="sidebar-link-text"> Members</span>
    </a>
    <?php endif; ?>
    <a href="#" class="sidebar-link sidebar-link-logout" onclick="openModal('xumodalLogout'); return false;">
      <i class="bi bi-box-arrow-right"></i><span class="sidebar-link-text"> Logout</span>
    </a>
  </div>
  <div class="sidebar-footer">
    <?= h($user['name']) ?><br>
    <?= h(ucfirst($role)) ?>
  </div>
</nav>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- Main wrapper -->
<div id="main-wrapper">
  <div id="topbar">
    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-sm btn-outline-secondary border-0" id="sidebarToggler">
        <i class="bi bi-list fs-5"></i>
      </button>
      <span class="topbar-title"><?= h($title) ?></span>
    </div>
    <div class="d-flex align-items-center gap-2">
      <?php if ($hasAvatar): ?>
      <img src="<?= h($avatarPath) ?>" alt="Avatar"
           style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0">
      <?php else: ?>
      <div class="user-avatar"><?= h($initials) ?></div>
      <?php endif; ?>
      <div class="d-none d-sm-block">
        <div class="fw-semibold" style="font-size:.82rem;color:#344054"><?= h($user['name']) ?></div>
        <div class="text-muted" style="font-size:.74rem"><?= h(ucfirst($role)) ?></div>
      </div>
    </div>
  </div>
  <div class="page-body">
<?php
}

function layout_foot(): void {
?>
  </div><!-- /page-body -->
</div><!-- /main-wrapper -->

<!-- Logout Confirmation Modal -->
<div class="xu-modal-overlay" id="xumodalLogout">
  <div class="xu-modal" style="max-width:400px">
    <div class="xu-modal-header">
      <span class="xu-modal-title"><i class="bi bi-box-arrow-right me-2"></i>Log Out</span>
      <button class="xu-modal-close" onclick="closeModal(this)"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="xu-modal-body">
      <p class="mb-0" style="color:var(--text-muted)">Are you sure you want to log out?</p>
    </div>
    <div class="xu-modal-footer">
      <button type="button" class="btn btn-outline" onclick="closeModal(this)">Cancel</button>
      <a href="/logout.php" class="btn btn-danger">
        <i class="bi bi-box-arrow-right me-1"></i>Log Out
      </a>
    </div>
  </div>
</div>

<script src="/assets/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/main.js"></script>
<script>
// Modal functions — defined globally, available to all inline onclick handlers
function openModal(id) {
  var el = document.getElementById(id);
  if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(btn) {
  var overlay = btn.closest('.xu-modal-overlay');
  if (overlay) { overlay.classList.remove('open'); document.body.style.overflow = ''; }
}
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('xu-modal-overlay')) {
    e.target.classList.remove('open'); document.body.style.overflow = '';
  }
});

// ── Sidebar toggle (mobile slide-in + desktop collapse rail) ──
(function() {
  var sidebar   = document.getElementById('sidebar');
  var backdrop  = document.getElementById('sidebarBackdrop');
  var toggler   = document.getElementById('sidebarToggler');
  var isDesktop = function() { return window.innerWidth >= 992; };

  // Restore desktop collapsed state from localStorage
  if (isDesktop() && localStorage.getItem('sidebarCollapsed') === '1') {
    document.body.classList.add('sidebar-collapsed');
  }

  if (toggler) {
    toggler.addEventListener('click', function() {
      if (isDesktop()) {
        // Desktop: toggle icon-only rail
        var collapsed = document.body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
      } else {
        // Mobile: slide-in overlay
        var open = sidebar.classList.toggle('show');
        if (backdrop) backdrop.classList.toggle('show', open);
        document.body.style.overflow = open ? 'hidden' : '';
      }
    });
  }

  if (backdrop) {
    backdrop.addEventListener('click', function() {
      sidebar.classList.remove('show');
      backdrop.classList.remove('show');
      document.body.style.overflow = '';
    });
  }

  // On resize: if going desktop, close mobile overlay; restore saved state
  window.addEventListener('resize', function() {
    if (isDesktop()) {
      sidebar.classList.remove('show');
      if (backdrop) backdrop.classList.remove('show');
      document.body.style.overflow = '';
      if (localStorage.getItem('sidebarCollapsed') === '1') {
        document.body.classList.add('sidebar-collapsed');
      } else {
        document.body.classList.remove('sidebar-collapsed');
      }
    } else {
      document.body.classList.remove('sidebar-collapsed');
    }
  });
})();
</script>
</body>
</html>
<?php
}
?>
