<?php
function h(mixed $val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

function flash(string $key, string $msg): void {
    $_SESSION['flash'][$key] = $msg;
}

function getFlash(string $key): ?string {
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function formatDate(string $date, string $format = 'M d, Y'): string {
    if (!$date) return '—';
    return (new DateTime($date))->format($format);
}

function formatDateTime(string $dt): string {
    if (!$dt) return '—';
    return (new DateTime($dt))->format('M d, Y h:i A');
}

function statusBadge(string $status): string {
    $map = [
        'active'     => 'badge-active',
        'inactive'   => 'badge-inactive',
        'probation'  => 'badge-probation',
        'terminated' => 'badge-terminated',
        'present'    => 'badge-present',
        'absent'     => 'badge-absent',
        'late'       => 'badge-late',
        'excused'    => 'badge-excused',
    ];
    $cls = $map[$status] ?? 'badge-default';
    return '<span class="badge ' . $cls . '">' . h(ucfirst($status)) . '</span>';
}

function roleBadge(string $role): string {
    $map = [
        'moderator' => 'badge-moderator',
        'officer'   => 'badge-officer',
        'member'    => 'badge-member',
    ];
    $cls = $map[$role] ?? 'badge-default';
    return '<span class="badge ' . $cls . '">' . h(ucfirst($role)) . '</span>';
}

function penaltyColor(float $points): string {
    if ($points === 0.0) return 'text-green';
    if ($points <= 3.0) return 'text-yellow';
    return 'text-red';
}

function computePenalty(string $status): float {
    return match($status) {
        'absent'  => PENALTY_ABSENT,
        'late'    => PENALTY_LATE,
        default   => 0.0,
    };
}

function recomputePenaltySummary(int $userId): void {
    $total = dbQueryOne(
        'SELECT COALESCE(SUM(penalty_points), 0) AS total FROM attendance WHERE user_id = ?',
        [$userId]
    )['total'] ?? 0;

    dbExecute(
        'INSERT INTO penalty_summary (user_id, total_points, last_computed)
         VALUES (?, ?, NOW())
         ON DUPLICATE KEY UPDATE total_points = VALUES(total_points), last_computed = NOW()',
        [$userId, $total]
    );
}

function uploadFile(array $file, string $subdir = ''): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload error code: ' . $file['error']];
    }
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['ok' => false, 'error' => 'File too large (max 20MB).'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
        return ['ok' => false, 'error' => 'File type not allowed.'];
    }
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, ALLOWED_TYPES, true)) {
        return ['ok' => false, 'error' => 'MIME type not allowed.'];
    }

    $dir = UPLOAD_DIR . ($subdir ? rtrim($subdir, '/') . '/' : '');
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = uniqid('', true) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
    $dest = $dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'error' => 'Failed to save file.'];
    }

    return [
        'ok'        => true,
        'path'      => 'uploads/' . ($subdir ? $subdir . '/' : '') . $filename,
        'filename'  => $file['name'],
        'size'      => $file['size'],
        'mime'      => $mime,
    ];
}

function jsonResponse(mixed $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function isAjax(): bool {
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        || ($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json';
}
