<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
startSession();
requireLogin();
if (!isOfficer()) { http_response_code(403); exit; }

$sheet_id = (int)($_GET['sheet_id'] ?? 0);
$rows = dbQuery('SELECT user_id FROM music_assignments WHERE sheet_id = ?', [$sheet_id]);
$ids = array_column($rows, 'user_id');
header('Content-Type: application/json');
echo json_encode(array_map('intval', $ids));
