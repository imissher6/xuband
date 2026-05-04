<?php
require_once __DIR__ . '/../includes/auth.php';
startSession();
if (isLoggedIn()) {
    header('Location: /dashboard.php');
} else {
    header('Location: /login.php');
}
exit;
