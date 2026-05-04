<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
startSession();

if (isLoggedIn()) { redirect('/dashboard.php'); }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } elseif (login($email, $password)) {
        redirect('/dashboard.php');
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login — XUBand Digital Filing System</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-box">
    <div class="login-logo">
      <div class="logo-mark"><i class="bi bi-music-note-beamed"></i></div>
      <h1>XUBand Filing System</h1>
      <p>Xavier University Band — Digital Platform</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2" data-auto-dismiss>
      <i class="bi bi-exclamation-triangle-fill"></i> <?= h($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" autocomplete="on">
      <div class="mb-3">
        <label class="form-label" for="email">Email Address</label>
        <input id="email" name="email" type="email" class="form-control"
               value="<?= h($_POST['email'] ?? '') ?>"
               placeholder="you@xuband.edu.ph" required autofocus autocomplete="email">
      </div>
      <div class="mb-3">
        <label class="form-label" for="password">Password</label>
        <input id="password" name="password" type="password" class="form-control"
               placeholder="••••••••" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-primary w-100 py-2">
        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
      </button>
    </form>

    <div class="mt-4 p-3 rounded" style="background:var(--bg);font-size:.78rem;color:var(--text-muted)">
      <strong style="color:var(--navy)">Demo Accounts</strong><br><br>
      <strong>Moderator:</strong> moderator@xuband.edu.ph<br>
      <strong>Officer:</strong> gabutin@xuband.edu.ph<br>
      <strong>Member:</strong> macalaguing@xuband.edu.ph<br>
      <br><em>All passwords: <code>password</code></em>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/main.js"></script>
</body>
</html>
