<?php
// TEMPORARY - delete after debugging
$host = getenv('MYSQLHOST') ?: 'NOT SET';
$port = getenv('MYSQLPORT') ?: 'NOT SET';
$db   = getenv('MYSQLDATABASE') ?: 'NOT SET';
$user = getenv('MYSQLUSER') ?: 'NOT SET';
$pass = getenv('MYSQLPASSWORD') ? '(set)' : 'NOT SET';

echo "MYSQLHOST: $host\n";
echo "MYSQLPORT: $port\n";
echo "MYSQLDATABASE: $db\n";
echo "MYSQLUSER: $user\n";
echo "MYSQLPASSWORD: $pass\n\n";

// Try connection
$h = $host === 'localhost' ? '127.0.0.1' : $host;
$dsn = "mysql:host=$h;port=$port;dbname=$db;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $user, getenv('MYSQLPASSWORD'), [PDO::ATTR_TIMEOUT => 5]);
    echo "CONNECTION OK\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
