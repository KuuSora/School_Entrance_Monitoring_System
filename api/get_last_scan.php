<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$MASTER_UID = '97:2A:59:06';
$MASTER_UID_NORM = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $MASTER_UID));

$result = $mysqli->query(
    "SELECT s.id, s.uid, s.direction, s.created_at,
            COALESCE(a.name, '') AS admin_name,
            CASE WHEN a.uid IS NULL THEN 0 ELSE 1 END AS is_admin
     FROM scans s
     LEFT JOIN admins a ON REPLACE(UPPER(a.uid), ':', '') = REPLACE(UPPER(s.uid), ':', '')
     ORDER BY s.id DESC
     LIMIT 1"
);

if (!$result) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Query failed']);
    exit;
}

$row = $result->fetch_assoc();
$result->free();

if (!$row) {
    echo json_encode(['ok' => true, 'data' => null]);
    exit;
}

$uid_norm = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $row['uid']));
if ($uid_norm === $MASTER_UID_NORM) {
    $row['is_admin'] = 1;
    $row['admin_name'] = $row['admin_name'] !== '' ? $row['admin_name'] : 'Master Card';
}

echo json_encode(['ok' => true, 'data' => $row]);
