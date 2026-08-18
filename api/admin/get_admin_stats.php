<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';

$admin_uid_filter = isset($_GET['admin_uid']) ? trim($_GET['admin_uid']) : null;
if ($admin_uid_filter === '') {
    $admin_uid_filter = null;
}

$sql = "SELECT a.uid AS admin_uid, a.name AS admin_name,
               COUNT(s.id) AS total_scans,
               SUM(s.direction = 'IN') AS in_count,
               SUM(s.direction = 'OUT') AS out_count,
               COUNT(DISTINCT s.uid) AS unique_users,
               SUM(CASE WHEN ss.cur_id IS NOT NULL THEN 1 ELSE 0 END) AS suspicious_count
        FROM admins a
        LEFT JOIN scans s ON s.admin_uid = a.uid
        LEFT JOIN (
            SELECT s2.id AS cur_id
            FROM scans s1
            JOIN scans s2 ON s2.uid = s1.uid AND s2.id = (
                SELECT MIN(x.id) FROM scans x WHERE x.uid = s1.uid AND x.id > s1.id
            )
            WHERE s1.direction = s2.direction
        ) ss ON ss.cur_id = s.id";

if ($admin_uid_filter !== null) {
    $sql .= " WHERE a.uid = '" . $mysqli->real_escape_string($admin_uid_filter) . "'";
}

$sql .= " GROUP BY a.uid, a.name
          ORDER BY a.name ASC";

$result = $mysqli->query($sql);
$rows = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();
}

echo json_encode(['ok' => true, 'data' => $rows]);