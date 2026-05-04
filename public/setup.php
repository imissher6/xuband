<?php
/**
 * XUBand Database Setup Script
 * Run this ONCE to initialize the database: /setup.php
 * DELETE this file after running in production!
 */

require_once __DIR__ . '/../includes/config.php';

$token = $_GET['token'] ?? '';
$validToken = 'xuband_setup_2024'; // Change this before deploying

if ($token !== $validToken) {
    http_response_code(403);
    die('<h1>Forbidden</h1><p>Add ?token=xuband_setup_2024 to the URL to run setup.</p>');
}

$dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4';
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    die('<h1>DB Connection Failed</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>');
}

$sql = file_get_contents(__DIR__ . '/../sql/schema.sql');

// Split into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));
$log = [];
$errors = [];

foreach ($statements as $stmt) {
    if (empty($stmt) || str_starts_with($stmt, '--')) continue;
    try {
        $pdo->exec($stmt);
        $preview = mb_substr($stmt, 0, 60);
        $log[] = "<span style='color:green'>✅ OK:</span> " . htmlspecialchars($preview) . '…';
    } catch (PDOException $e) {
        // Ignore duplicate entry errors (seeding)
        if (str_contains($e->getMessage(), 'Duplicate entry')) {
            $log[] = "<span style='color:orange'>⏭ Skip (duplicate):</span> " . htmlspecialchars(mb_substr($stmt,0,60));
        } else {
            $errors[] = $e->getMessage();
            $log[] = "<span style='color:red'>❌ Error:</span> " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>XUBand Setup</title>
<style>body{font-family:monospace;max-width:800px;margin:40px auto;padding:20px;background:#f5f5f5}
pre{background:#1a1a1a;color:#eee;padding:20px;border-radius:8px;overflow-x:auto;font-size:13px;line-height:1.6}
h1{color:#283971}
.success{background:#dcfce7;border:1px solid #86efac;padding:12px 16px;border-radius:6px;margin:16px 0}
.error  {background:#fee2e2;border:1px solid #fca5a5;padding:12px 16px;border-radius:6px;margin:16px 0}
</style></head>
<body>
<h1>🎵 XUBand Database Setup</h1>
<?php if (empty($errors)): ?>
<div class="success">✅ Setup completed successfully! Database is ready.</div>
<?php else: ?>
<div class="error">⚠️ Setup completed with <?= count($errors) ?> error(s). Check log below.</div>
<?php endif; ?>

<pre><?= implode("\n", $log) ?></pre>

<?php if (empty($errors)): ?>
<div class="success">
  <strong>Default Accounts:</strong><br>
  • Moderator: moderator@xuband.edu.ph<br>
  • Officer: gabutin@xuband.edu.ph<br>
  • All passwords: <code>password</code><br><br>
  <strong>⚠️ DELETE this setup.php file after setup!</strong><br>
  <a href="/login.php">→ Go to Login</a>
</div>
<?php endif; ?>
</body>
</html>
