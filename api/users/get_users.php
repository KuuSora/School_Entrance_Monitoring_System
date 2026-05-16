<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';

$admin_uid_filter = isset($_GET['admin_uid']) ? trim($_GET['admin_uid']) : null;
if ($admin_uid_filter === '') {
    $admin_uid_filter = null;
}

$baseSql = "SELECT uid, name, student_id, NULL AS faculty_id, NULL AS staff_id,
         course, school_year, section, NULL AS department,
        NULL AS email, NULL AS phone, NULL AS purpose, NULL AS valid_until, 'student' AS role
     FROM students
     UNION ALL
     SELECT uid, name, NULL, faculty_id, NULL,
         NULL, NULL, NULL, department,
        NULL AS email, NULL AS phone, NULL, NULL, 'faculty' AS role
     FROM faculty
     UNION ALL
     SELECT uid, name, NULL, NULL, staff_id,
         NULL, NULL, NULL, department,
        NULL AS email, NULL AS phone, NULL, NULL, 'staff' AS role
     FROM staff
     UNION ALL
     SELECT uid, name, NULL, NULL, NULL,
         NULL, NULL, NULL, NULL,
        NULL AS email, NULL AS phone, purpose, valid_until, 'visitor' AS role
     FROM visitors";

if ($admin_uid_filter) {
    $admin_uid_safe = $mysqli->real_escape_string($admin_uid_filter);
    $sql = "SELECT p.*, s.admin_uid, COALESCE(a.name, '') AS admin_name
            FROM (" . $baseSql . ") p
            JOIN (SELECT DISTINCT uid, admin_uid FROM scans WHERE admin_uid = '" . $admin_uid_safe . "') s
              ON s.uid = p.uid
            LEFT JOIN admins a ON s.admin_uid = a.uid
            ORDER BY p.name ASC";
} else {
    $sql = "SELECT p.*, NULL AS admin_uid, NULL AS admin_name
            FROM (" . $baseSql . ") p
            ORDER BY p.name ASC";
}

$result = $mysqli->query($sql);

$rows = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();
}

echo json_encode(['ok' => true, 'data' => $rows]);
