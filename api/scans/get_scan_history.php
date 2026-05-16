<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';

$admin_uid_filter = isset($_GET['admin_uid']) ? trim($_GET['admin_uid']) : null;
$mode = isset($_GET['mode']) ? strtolower(trim($_GET['mode'])) : 'day';
if ($mode !== 'hour') {
    $mode = 'day';
}

$whereParts = [];
if ($admin_uid_filter !== null && $admin_uid_filter !== '') {
    $whereParts[] = "s.admin_uid = '" . $mysqli->real_escape_string($admin_uid_filter) . "'";
}
$where_clause = '';
if (count($whereParts) > 0) {
    $where_clause = 'WHERE ' . implode(' AND ', $whereParts);
}

$roleUnionSql = "(SELECT uid, 'student' AS role FROM students
    UNION ALL SELECT uid, 'faculty' AS role FROM faculty
    UNION ALL SELECT uid, 'staff' AS role FROM staff
    UNION ALL SELECT uid, 'visitor' AS role FROM visitors)";

$bucketExpr = $mode === 'hour'
    ? "DATE_FORMAT(s.created_at, '%Y-%m-%d %H:00')"
    : "DATE(s.created_at)";

$history = [];
$historyRes = $mysqli->query(
    "SELECT $bucketExpr AS day,
            SUM(s.direction = 'IN') AS total_in,
            SUM(s.direction = 'OUT') AS total_out,
            SUM(s.direction = 'IN' AND p.role = 'student') AS student_in,
            SUM(s.direction = 'OUT' AND p.role = 'student') AS student_out,
            SUM(s.direction = 'IN' AND p.role = 'faculty') AS faculty_in,
            SUM(s.direction = 'OUT' AND p.role = 'faculty') AS faculty_out,
            SUM(s.direction = 'IN' AND p.role = 'staff') AS staff_in,
            SUM(s.direction = 'OUT' AND p.role = 'staff') AS staff_out,
            SUM(s.direction = 'IN' AND p.role = 'visitor') AS visitor_in,
            SUM(s.direction = 'OUT' AND p.role = 'visitor') AS visitor_out,
            SUM(s.direction = 'IN' AND p.role IS NULL) AS unknown_in,
            SUM(s.direction = 'OUT' AND p.role IS NULL) AS unknown_out
     FROM scans s
     LEFT JOIN $roleUnionSql p ON s.uid = p.uid
     $where_clause
     GROUP BY day
     ORDER BY day ASC"
);
if ($historyRes) {
    while ($row = $historyRes->fetch_assoc()) {
        $history[] = [
            'day' => $row['day'],
            'total_in' => (int)$row['total_in'],
            'total_out' => (int)$row['total_out'],
            'student_in' => (int)$row['student_in'],
            'student_out' => (int)$row['student_out'],
            'faculty_in' => (int)$row['faculty_in'],
            'faculty_out' => (int)$row['faculty_out'],
            'staff_in' => (int)$row['staff_in'],
            'staff_out' => (int)$row['staff_out'],
            'visitor_in' => (int)$row['visitor_in'],
            'visitor_out' => (int)$row['visitor_out'],
            'unknown_in' => (int)$row['unknown_in'],
            'unknown_out' => (int)$row['unknown_out']
        ];
    }
    $historyRes->free();
}

$roleTotals = [];
$roleRes = $mysqli->query(
    "SELECT COALESCE(p.role, 'unknown') AS role, COUNT(*) AS c
     FROM scans s
     LEFT JOIN $roleUnionSql p ON s.uid = p.uid
     $where_clause
     GROUP BY role
     ORDER BY c DESC"
);
if ($roleRes) {
    while ($row = $roleRes->fetch_assoc()) {
        $roleTotals[$row['role']] = (int)$row['c'];
    }
    $roleRes->free();
}

$directionTotals = ['in' => 0, 'out' => 0];
$directionRes = $mysqli->query(
    "SELECT SUM(direction = 'IN') AS in_total, SUM(direction = 'OUT') AS out_total
     FROM scans s
     $where_clause"
);
if ($directionRes) {
    $directionRow = $directionRes->fetch_assoc();
    $directionRes->free();
    if ($directionRow) {
        $directionTotals['in'] = (int)$directionRow['in_total'];
        $directionTotals['out'] = (int)$directionRow['out_total'];
    }
}

echo json_encode([
    'ok' => true,
    'history' => $history,
    'role_totals' => $roleTotals,
    'direction_totals' => $directionTotals
]);
