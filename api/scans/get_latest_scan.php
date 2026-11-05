<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';
require_once __DIR__ . '/../system/auth.php';

require_admin_session();

$result = $mysqli->query(
    "SELECT s.id, s.uid, s.direction, s.created_at,
            COALESCE(p.name, 'New User') AS name
     FROM scans s
     LEFT JOIN (
         SELECT uid, name FROM students
         UNION ALL SELECT uid, name FROM faculty
         UNION ALL SELECT uid, name FROM staff
         UNION ALL SELECT uid, name FROM visitors
         UNION ALL SELECT uid, name FROM admins
     ) p ON REPLACE(UPPER(s.uid), ':', '') = REPLACE(UPPER(p.uid), ':', '')
     ORDER BY s.id DESC
     LIMIT 1"
);

if (!$result) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Unable to load latest scan']);
    exit;
}

$scan = $result->fetch_assoc();
$result->free();
echo json_encode(['ok' => true, 'data' => $scan ?: null]);
