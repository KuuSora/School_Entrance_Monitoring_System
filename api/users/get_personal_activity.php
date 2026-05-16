<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';

$uid = isset($_GET['uid']) ? trim($_GET['uid']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;
if ($limit <= 0 || $limit > 500) {
    $limit = 200;
}

$admin_uid_filter = isset($_GET['admin_uid']) ? trim($_GET['admin_uid']) : null;
if ($admin_uid_filter === '') {
    $admin_uid_filter = null;
}

if ($uid === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing uid']);
    exit;
}

$sql = "SELECT s.id, s.uid, s.direction, s.created_at, s.admin_uid,
               COALESCE(p.name, 'New User') AS name, p.role,
               COALESCE(a.name, '') AS admin_name,
               CASE WHEN ss.cur_id IS NULL THEN 0 ELSE 1 END AS suspicious
        FROM scans s
        LEFT JOIN (
            SELECT uid, name, 'student' AS role FROM students
            UNION ALL
            SELECT uid, name, 'faculty' AS role FROM faculty
            UNION ALL
            SELECT uid, name, 'staff' AS role FROM staff
            UNION ALL
            SELECT uid, name, 'visitor' AS role FROM visitors
            UNION ALL
            SELECT uid, name, 'admin' AS role FROM admins
        ) p ON s.uid = p.uid
        LEFT JOIN admins a ON s.admin_uid = a.uid
        LEFT JOIN (
            SELECT s2.id AS cur_id
            FROM scans s1
            JOIN scans s2 ON s2.uid = s1.uid AND s2.id = (
                SELECT MIN(x.id) FROM scans x WHERE x.uid = s1.uid AND x.id > s1.id
            )
            WHERE s1.direction = s2.direction
        ) ss ON ss.cur_id = s.id
        WHERE s.uid = ?";

$params = [$uid];
$types = 's';

if ($admin_uid_filter !== null) {
    $sql .= ' AND s.admin_uid = ?';
    $params[] = $admin_uid_filter;
    $types .= 's';
}

$sql .= ' ORDER BY s.id DESC LIMIT ?';
$params[] = $limit;
$types .= 'i';

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed to prepare statement']);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}

$stmt->close();

echo json_encode(['ok' => true, 'data' => $rows]);