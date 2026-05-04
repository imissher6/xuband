<?php
// layout.php — shared header/sidebar/footer
// Call layout_head($title, $pageKey) before output
// Call layout_foot() after output

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
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎵</text></svg>">
</head>
<body>
<div class="app-wrapper">
  <!-- Sidebar -->
  <nav class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="logo-title">🎵 XUBand</div>
      <div class="logo-sub">Digital Filing System</div>
    </div>
    <div class="sidebar-nav">
      <div class="nav-section-label">Main</div>
      <a href="/dashboard.php" class="nav-link<?= $isActive('dashboard') ?>">
        <span class="nav-icon">🏠</span> Dashboard
      </a>
      <a href="/announcements.php" class="nav-link<?= $isActive('announcements') ?>">
        <span class="nav-icon">📢</span> Announcements
      </a>
      <a href="/events.php" class="nav-link<?= $isActive('events') ?>">
        <span class="nav-icon">📅</span> Events & Calendar
      </a>

      <div class="nav-section-label">Modules</div>
      <a href="/music-sheets.php" class="nav-link<?= $isActive('music-sheets') ?>">
        <span class="nav-icon">🎼</span> Music Sheets
      </a>
      <a href="/attendance.php" class="nav-link<?= $isActive('attendance') ?>">
        <span class="nav-icon">✅</span> Attendance
      </a>
      <a href="/scholarships.php" class="nav-link<?= $isActive('scholarships') ?>">
        <span class="nav-icon">🎓</span> Scholarships
      </a>

      <?php if ($role === 'moderator' || $role === 'officer'): ?>
      <div class="nav-section-label">Management</div>
      <a href="/members.php" class="nav-link<?= $isActive('members') ?>">
        <span class="nav-icon">👥</span> Members
      </a>
      <?php endif; ?>

      <div class="nav-section-label">Account</div>
      <a href="/profile.php" class="nav-link<?= $isActive('profile') ?>">
        <span class="nav-icon">👤</span> My Profile
      </a>
      <a href="/logout.php" class="nav-link" data-confirm="Log out?">
        <span class="nav-icon">🚪</span> Logout
      </a>
    </div>
    <div class="sidebar-footer">
      <?= h($user['name']) ?><br>
      <span style="color:rgba(255,255,255,.3)"><?= h(ucfirst($role)) ?></span>
    </div>
  </nav>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- Main -->
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <button class="hamburger" id="hamburger">☰</button>
        <span class="page-title"><?= h($title) ?></span>
      </div>
      <div class="topbar-right">
        <div class="user-chip">
          <div class="user-avatar"><?= h($initials) ?></div>
          <div>
            <div style="font-weight:600;color:var(--text)"><?= h($user['name']) ?></div>
            <div><?= h(ucfirst($role)) ?></div>
          </div>
        </div>
      </div>
    </div>
    <div class="page-content">
<?php
}

function layout_foot(): void {
?>
    </div><!-- /page-content -->
  </div><!-- /main-content -->
</div><!-- /app-wrapper -->
<script src="/assets/js/main.js"></script>
</body>
</html>
<?php
}
?>
