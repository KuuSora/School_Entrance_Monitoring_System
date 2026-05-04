<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$uid = isset($_GET['uid']) ? trim($_GET['uid']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;
if ($limit <= 0 || $limit > 500) {
    $limit = 200;
}

if ($uid === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing uid']);
    exit;
}

$stmt = $mysqli->prepare(
        "SELECT s.id, s.uid, s.direction, s.created_at, p.name, p.role,
                s.admin_uid,
                COALESCE(a.name, '') AS admin_name,
                CASE WHEN ss.cur_id IS NULL THEN 0 ELSE 1 END AS suspicious
         FROM scans s
         LEFT JOIN admins a ON s.admin_uid = a.uid
         LEFT JOIN (
             SELECT uid, name, 'student' AS role FROM students
             UNION ALL
             SELECT uid, name, 'faculty' AS role FROM faculty
             UNION ALL
             SELECT uid, name, 'staff' AS role FROM staff
             UNION ALL
             SELECT uid, name, 'visitor' AS role FROM visitors
         ) p ON s.uid = p.uid
         LEFT JOIN (
             SELECT s2.id AS cur_id
             FROM scans s1
             JOIN scans s2 ON s2.uid = s1.uid AND s2.id = (
                 SELECT MIN(x.id) FROM scans x WHERE x.uid = s1.uid AND x.id > s1.id
             )
             WHERE s1.direction = s2.direction
         ) ss ON ss.cur_id = s.id
         WHERE s.uid = ?
         ORDER BY s.id DESC
         LIMIT ?"
);
$stmt->bind_param('si', $uid, $limit);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}
$stmt->close();

echo json_encode(['ok' => true, 'data' => $rows]);
