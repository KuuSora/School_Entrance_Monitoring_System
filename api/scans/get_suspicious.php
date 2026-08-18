<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;
if ($limit <= 0 || $limit > 1000) $limit = 200;

$admin_uid_filter = isset($_GET['admin_uid']) ? trim($_GET['admin_uid']) : null;
if ($admin_uid_filter === '') {
    $admin_uid_filter = null;
}

// Find consecutive scans for the same UID where direction didn't change (IN/IN or OUT/OUT)
$whereParts = ["s1.direction = s2.direction"];
if ($admin_uid_filter) {
    $admin_uid_safe = $mysqli->real_escape_string($admin_uid_filter);
    $whereParts[] = "s2.admin_uid = '" . $admin_uid_safe . "'";
}
$whereClause = implode(' AND ', $whereParts);

$sql = "SELECT s2.id, s2.uid, s2.direction, s2.created_at, s1.id AS prev_id, s1.created_at AS prev_created_at, s2.admin_uid, COALESCE(a.name, '') AS admin_name,
               COALESCE(p.name, 'New User') AS name, COALESCE(p.role, 'unknown') AS role
        FROM scans s1
        JOIN scans s2 ON s2.uid = s1.uid AND s2.id = (
            SELECT MIN(x.id) FROM scans x WHERE x.uid = s1.uid AND x.id > s1.id
        )
        LEFT JOIN admins a ON s2.admin_uid = a.uid
        LEFT JOIN (
            SELECT uid, name, 'student' AS role FROM students
            UNION ALL SELECT uid, name, 'faculty' AS role FROM faculty
            UNION ALL SELECT uid, name, 'staff' AS role FROM staff
            UNION ALL SELECT uid, name, 'visitor' AS role FROM visitors
            UNION ALL SELECT uid, name, 'admin' AS role FROM admins
        ) p ON s2.uid = p.uid
        WHERE " . $whereClause . "
        ORDER BY s2.id DESC
        LIMIT " . (int)$limit;

$res = $mysqli->query($sql);
$rows = [];
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    $res->free();
}

echo json_encode(['ok' => true, 'data' => $rows]);
