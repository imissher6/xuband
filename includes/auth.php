<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function login(string $email, string $password): bool {
    $user = dbQueryOne('SELECT * FROM users WHERE email = ? AND status = "active"', [trim($email)]);
    if (!$user) return false;
    // Normalize $2b$ → $2y$ for PHP compatibility (bcryptjs generates $2b$)
    $hash = str_replace('$2b
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_email']= $user['email'];
    session_regenerate_id(true);
    return true;
}

function logout(): void {
    startSession();
    $_SESSION = [];
    session_destroy();
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'],
        'role'  => $_SESSION['user_role'],
        'email' => $_SESSION['user_email'],
    ];
}

function requireLogin(): void {
    startSession();
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireRole(array $roles): void {
    requireLogin();
    $user = currentUser();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        include __DIR__ . '/../public/403.php';
        exit;
    }
}

function isModerator(): bool {
    return (currentUser()['role'] ?? '') === 'moderator';
}

function isOfficer(): bool {
    $role = currentUser()['role'] ?? '';
    return $role === 'officer' || $role === 'moderator';
}

function isMember(): bool {
    return isLoggedIn();
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}
, '$2y
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_email']= $user['email'];
    session_regenerate_id(true);
    return true;
}

function logout(): void {
    startSession();
    $_SESSION = [];
    session_destroy();
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'],
        'role'  => $_SESSION['user_role'],
        'email' => $_SESSION['user_email'],
    ];
}

function requireLogin(): void {
    startSession();
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireRole(array $roles): void {
    requireLogin();
    $user = currentUser();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        include __DIR__ . '/../public/403.php';
        exit;
    }
}

function isModerator(): bool {
    return (currentUser()['role'] ?? '') === 'moderator';
}

function isOfficer(): bool {
    $role = currentUser()['role'] ?? '';
    return $role === 'officer' || $role === 'moderator';
}

function isMember(): bool {
    return isLoggedIn();
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}
, $user['password_hash']);
    if (!password_verify($password, $hash)) return false;
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_email']= $user['email'];
    session_regenerate_id(true);
    return true;
}

function logout(): void {
    startSession();
    $_SESSION = [];
    session_destroy();
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'],
        'role'  => $_SESSION['user_role'],
        'email' => $_SESSION['user_email'],
    ];
}

function requireLogin(): void {
    startSession();
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireRole(array $roles): void {
    requireLogin();
    $user = currentUser();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        include __DIR__ . '/../public/403.php';
        exit;
    }
}

function isModerator(): bool {
    return (currentUser()['role'] ?? '') === 'moderator';
}

function isOfficer(): bool {
    $role = currentUser()['role'] ?? '';
    return $role === 'officer' || $role === 'moderator';
}

function isMember(): bool {
    return isLoggedIn();
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}
