<?php
/**
 * XUBand Login Debug Tool
 * Visit /debug-login.php to diagnose login issues
 * DELETE this file after debugging!
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$hash = '$2y$10$bgJSY64smLKwTYVtv0SXhuXDjij9BzVnpm45cPQqrcOg8OWjIrIia';
$testPass = 'password';
$hashWorks = password_verify($testPass, $hash);

// Try DB connection
$dbOk = false;
$dbError = '';
$users = [];
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $dbOk = true;
    $users = $pdo->query('SELECT id, email, role, LEFT(password_hash,10) AS hash_prefix, status FROM users LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $dbError = $e->getMessage();
}
?><!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>XUBand Debug</title>
<style>body{font-family:monospace;max-width:900px;margin:30px auto;padding:20px;background:#f5f5f5}
.ok{color:green;font-weight:bold}.fail{color:red;font-weight:bold}
table{width:100%;border-collapse:collapse;background:#fff;margin-top:10px}
th,td{padding:8px 12px;border:1px solid #ddd;text-align:left}
th{background:#283971;color:#fff}
h2{color:#283971}.box{background:#fff;padding:16px;border-radius:8px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,.1)}
</style></head>
<body>
<h1>🔍 XUBand Login Debugger</h1>
<p style="color:red"><strong>⚠️ Delete this file after debugging!</strong></p>

<div class="box">
  <h2>1. PHP bcrypt Hash Test</h2>
  <p>Testing: <code>password_verify('password', '<?= $hash ?>')</code></p>
  <p>Result: <?= $hashWorks ? '<span class="ok">✅ PASS — hash works correctly</span>' : '<span class="fail">❌ FAIL — hash broken on this PHP version</span>' ?></p>
  <p>PHP version: <strong><?= PHP_VERSION ?></strong></p>
</div>

<div class="box">
  <h2>2. Database Connection</h2>
  <p>Host: <code><?= DB_HOST ?>:<?= DB_PORT ?></code> | DB: <code><?= DB_NAME ?></code> | User: <code><?= DB_USER ?></code></p>
  <?php if ($dbOk): ?>
  <p class="ok">✅ Connected successfully</p>
  <h3>Users in DB:</h3>
  <table><tr><th>ID</th><th>Email</th><th>Role</th><th>Hash Prefix</th><th>Status</th></tr>
  <?php foreach ($users as $u): ?>
  <tr><td><?= $u['id'] ?></td><td><?= htmlspecialchars($u['email']) ?></td><td><?= $u['role'] ?></td>
      <td><code><?= htmlspecialchars($u['hash_prefix']) ?>…</code></td><td><?= $u['status'] ?></td></tr>
  <?php endforeach; ?>
  </table>
  <?php if (!$users): ?><p style="color:orange">⚠️ No users found. Run setup.php first!</p><?php endif; ?>
  <?php else: ?>
  <p class="fail">❌ Connection failed: <?= htmlspecialchars($dbError) ?></p>
  <?php endif; ?>
</div>

<div class="box">
  <h2>3. Login Simulation</h2>
  <?php if ($dbOk && $users):
    $row = $pdo->prepare('SELECT password_hash FROM users WHERE email = ?');
    $row->execute(['moderator@xuband.edu.ph']);
    $u = $row->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        $stored = $u['password_hash'];
        $converted = str_replace('$2b$', '$2y$', $stored);
        $result = password_verify('password', $converted);
        echo "<p>Stored hash prefix: <code>" . htmlspecialchars(substr($stored,0,7)) . "…</code></p>";
        echo "<p>After conversion: <code>" . htmlspecialchars(substr($converted,0,7)) . "…</code></p>";
        echo "<p>password_verify('password', hash): " . ($result ? '<span class="ok">✅ PASS — login will work</span>' : '<span class="fail">❌ FAIL — run setup.php to re-seed users</span>') . "</p>";
    } else {
        echo "<p class='fail'>❌ moderator@xuband.edu.ph not found in DB. Run setup.php!</p>";
    }
  endif; ?>
</div>

<div class="box">
  <h2>Quick Links</h2>
  <a href="/setup.php?token=xuband_setup_2024">→ Run Setup</a> &nbsp;|&nbsp;
  <a href="/login.php">→ Login Page</a>
</div>
</body></html>
