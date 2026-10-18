<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';
require_once __DIR__ . '/../system/auth.php';
require_admin_session();

$result = $mysqli->query("SELECT id, type, title, message, is_read, created_at
    FROM dashboard_notifications ORDER BY is_read ASC, created_at DESC LIMIT 50");
$rows = [];
if ($result) {
    while ($row = $result->fetch_assoc()) $rows[] = $row;
}
echo json_encode(['ok' => true, 'data' => $rows]);
