<?php
// XUBand Configuration
define('APP_NAME', 'XUBand Filing System');
define('APP_VERSION', '1.0.0');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');

// Database config from environment variables (Railway injects these)
define('DB_HOST', getenv('MYSQLHOST')     ?: getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('MYSQLPORT')     ?: getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'xuband');
define('DB_USER', getenv('MYSQLUSER')     ?: getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '');

// Session
define('SESSION_NAME', 'xuband_session');
define('SESSION_LIFETIME', 3600 * 8); // 8 hours

// Uploads
define('UPLOAD_DIR', __DIR__ . '/../public/uploads/');
define('UPLOAD_MAX_SIZE', 20 * 1024 * 1024); // 20MB
define('ALLOWED_TYPES', ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'audio/mpeg', 'audio/mp3']);
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'mp3']);

// Penalty points
define('PENALTY_ABSENT',  3.0);
define('PENALTY_LATE',    1.0);
define('PENALTY_EXCUSED', 0.0);
define('PENALTY_PRESENT', 0.0);
