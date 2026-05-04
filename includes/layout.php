<?php
// layout.php — shared header/sidebar/footer (Bootstrap 5 + Bootstrap Icons)

function layout_head(string $title = 'XUBand', string $pageKey = ''): void {
    $user = currentUser();
    $initials = strtoupper(implode('', array_map(fn($w) => $w[0], explode(' ', $user['name']))));
    $initials = substr($initials, 0, 2);
    $role = $user['role'];
    $currentPage = basename($_SERVER['PHP_SELF'], '.php');

    $isActive = fn(string $key) => ($key === $currentPage || $key === $pageKey) ? ' active' : '';
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
    <div class="brand-name"><i class="bi bi-music-note-beamed me-2"></i>XUBand</div>
    <div class="brand-sub">Digital Filing System</div>
  </div>
  <div class="sidebar-nav">
    <div class="sidebar-section">Main</div>
    <a href="/dashboard.php" class="sidebar-link<?= $isActive('dashboard') ?>">
      <i class="bi bi-house"></i> Dashboard
    </a>
    <a href="/announcements.php" class="sidebar-link<?= $isActive('announcements') ?>">
      <i class="bi bi-megaphone"></i> Announcements
    </a>
    <a href="/events.php" class="sidebar-link<?= $isActive('events') ?>">
      <i class="bi bi-calendar3"></i> Events &amp; Calendar
    </a>

    <div class="sidebar-section">Modules</div>
    <a href="/music-sheets.php" class="sidebar-link<?= $isActive('music-sheets') ?>">
      <i class="bi bi-music-note-beamed"></i> Music Sheets
    </a>
    <a href="/attendance.php" class="sidebar-link<?= $isActive('attendance') ?>">
      <i class="bi bi-check2-square"></i> Attendance
    </a>
    <a href="/scholarships.php" class="sidebar-link<?= $isActive('scholarships') ?>">
      <i class="bi bi-award"></i> Scholarships
    </a>

    <?php if ($role === 'moderator' || $role === 'officer'): ?>
    <div class="sidebar-section">Management</div>
    <a href="/members.php" class="sidebar-link<?= $isActive('members') ?>">
      <i class="bi bi-people"></i> Members
    </a>
    <?php endif; ?>

    <div class="sidebar-section">Account</div>
    <a href="/profile.php" class="sidebar-link<?= $isActive('profile') ?>">
      <i class="bi bi-person"></i> My Profile
    </a>
    <a href="/logout.php" class="sidebar-link" data-confirm="Log out?">
      <i class="bi bi-box-arrow-right"></i> Logout
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
      <button class="btn btn-sm btn-outline-secondary d-lg-none border-0" id="sidebarToggler">
        <i class="bi bi-list fs-5"></i>
      </button>
      <span class="topbar-title"><?= h($title) ?></span>
    </div>
    <div class="d-flex align-items-center gap-2">
      <div class="user-avatar"><?= h($initials) ?></div>
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
<script src="/assets/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/main.js"></script>
</body>
</html>
<?php
}
?>
