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
        'active'    => 'bg-success-subtle text-success',
        'inactive'  => 'bg-secondary-subtle text-secondary',
        'present'   => 'bg-success-subtle text-success',
        'absent'    => 'bg-danger-subtle text-danger',
        'late'      => 'bg-warning-subtle text-warning',
        'excused'   => 'bg-info-subtle text-info',
    ];
    $cls = $map[$status] ?? 'bg-secondary-subtle text-secondary';
    return '<span class="badge rounded-pill ' . $cls . '">' . h(ucfirst($status)) . '</span>';
}

function roleBadge(string $role): string {
    $map = [
        'moderator' => 'bg-primary text-white',
        'officer'   => 'text-bg-warning',
        'member'    => 'bg-secondary-subtle text-secondary',
    ];
    $cls = $map[$role] ?? 'bg-secondary-subtle text-secondary';
    return '<span class="badge ' . $cls . '">' . h(ucfirst($role)) . '</span>';
}

function scholarshipBadge(string $status): string {
    $map = [
        'Full Scholar' => 'bg-success-subtle text-success',
        'Half Scholar' => 'bg-warning-subtle text-warning',
        'Not Scholar'  => 'bg-secondary-subtle text-secondary',
    ];
    $cls = $map[$status] ?? 'bg-secondary-subtle text-secondary';
    return '<span class="badge rounded-pill ' . $cls . '">' . h($status) . '</span>';
}

function penaltyClass(int $points): string {
    if ($points === 0)   return 'text-success';
    if ($points <= 75)   return 'text-warning';
    return 'text-danger';
}

function penaltyColor(float $points): string {
    return penaltyClass((int)$points);
}

function computePenalty(string $status): int {
    return match($status) {
        'absent' => PENALTY_ABSENT,
        'late'   => PENALTY_LATE,
        default  => 0,
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
        [$userId, (int)$total]
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
        'ok'       => true,
        'path'     => 'uploads/' . ($subdir ? $subdir . '/' : '') . $filename,
        'filename' => $file['name'],
        'size'     => $file['size'],
        'mime'     => $mime,
    ];
}

function jsonResponse(mixed $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
