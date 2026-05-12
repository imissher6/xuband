<?php
/**
 * XUBand Database Setup Script
 * Run this ONCE to initialize the database: /setup.php?token=xuband
 * DELETE this file after running in production!
 */

require_once __DIR__ . '/../includes/config.php';

$token      = $_GET['token'] ?? '';
$validToken = 'xuband';

if ($token !== $validToken) {
    http_response_code(403);
    die('<h1>Forbidden</h1><p>Add ?token=xuband to the URL to run setup.</p>');
}

$host = (DB_HOST === 'localhost') ? '127.0.0.1' : DB_HOST;
$dsn  = 'mysql:host=' . $host . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    die('<h1>DB Connection Failed</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>');
}

// Use multi_query approach via PDO — execute entire schema at once
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');

$sql = file_get_contents(__DIR__ . '/../sql/schema.sql');

// Strip comments and split properly
$lines     = explode("\n", $sql);
$cleaned   = [];
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '--')) continue;
    $cleaned[] = $line;
}
$sql = implode("\n", $cleaned);

// Split on semicolons that are at end of a statement
$statements = preg_split('/;\s*\n/m', $sql);

$log    = [];
$errors = [];

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;
    try {
        $pdo->exec($stmt);
        $log[] = "<span style='color:green'>OK:</span> " . htmlspecialchars(mb_substr($stmt, 0, 80)) . '…';
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate entry') ||
            str_contains($e->getMessage(), 'already exists')) {
            $log[] = "<span style='color:orange'>Skip:</span> " . htmlspecialchars(mb_substr($stmt, 0, 80));
        } else {
            $errors[] = $e->getMessage();
            $log[] = "<span style='color:red'>Error:</span> " . htmlspecialchars($e->getMessage());
        }
    }
}

$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

// ── Column migrations (MySQL-safe: no IF NOT EXISTS on ALTER TABLE) ──────────
$migrations = [
    'avatar_path'        => "ALTER TABLE users ADD COLUMN avatar_path VARCHAR(255) DEFAULT NULL",
    'scholarship_status' => "ALTER TABLE users ADD COLUMN scholarship_status ENUM('Not Scholar','Half Scholar','Full Scholar') NOT NULL DEFAULT 'Not Scholar'",
];
foreach ($migrations as $col => $alterSql) {
    $exists = $pdo->query(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = " . $pdo->quote($col)
    )->fetchColumn();
    if ($exists) {
        $log[] = "<span style='color:orange'>Skip:</span> Column `users.{$col}` already exists";
    } else {
        try {
            $pdo->exec($alterSql);
            $log[] = "<span style='color:green'>OK:</span> Added column `users.{$col}`";
        } catch (PDOException $e) {
            $errors[] = $e->getMessage();
            $log[] = "<span style='color:red'>Error:</span> " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>XUBand Setup</title>
<style>
body{font-family:monospace;max-width:900px;margin:40px auto;padding:20px;background:#f5f5f5}
pre{background:#1a1a1a;color:#eee;padding:20px;border-radius:8px;overflow-x:auto;font-size:13px;line-height:1.6}
h1{color:#283971}
.success{background:#dcfce7;border:1px solid #86efac;padding:12px 16px;border-radius:6px;margin:16px 0}
.error{background:#fee2e2;border:1px solid #fca5a5;padding:12px 16px;border-radius:6px;margin:16px 0}
</style></head>
<body>
<h1>XUBand Database Setup</h1>
<?php if (empty($errors)): ?>
<div class="success">Setup completed successfully! Database is ready.</div>
<?php else: ?>
<div class="error">Setup completed with <?= count($errors) ?> error(s). Check log below.</div>
<?php endif; ?>

<pre><?= implode("\n", $log) ?></pre>

<?php if (empty($errors)): ?>
<div class="success">
  <strong>Default Accounts:</strong><br>
  &bull; Moderator: moderator@xuband.edu.ph<br>
  &bull; Officer: gabutin@xuband.edu.ph<br>
  &bull; All passwords: <code>password</code><br><br>
  <strong>DELETE setup.php after use!</strong><br>
  <a href="/login.php">→ Go to Login</a>
</div>
<?php endif; ?>
</body>
</html>
