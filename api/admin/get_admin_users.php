<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';

$admin_uid_filter = isset($_GET['admin_uid']) ? trim($_GET['admin_uid']) : null;
if ($admin_uid_filter === '') {
    $admin_uid_filter = null;
}

$peopleSql = "SELECT uid, name, student_id, NULL AS faculty_id, NULL AS staff_id,
         course, school_year, section, NULL AS department,
         email, phone, NULL AS purpose, NULL AS valid_until, 'student' AS role
     FROM students
     UNION ALL
     SELECT uid, name, NULL, faculty_id, NULL,
         NULL, NULL, NULL, department,
         email, phone, NULL, NULL, 'faculty' AS role
     FROM faculty
     UNION ALL
     SELECT uid, name, NULL, NULL, staff_id,
         NULL, NULL, NULL, department,
         email, phone, NULL, NULL, 'staff' AS role
     FROM staff
     UNION ALL
     SELECT uid, name, NULL, NULL, NULL,
         NULL, NULL, NULL, NULL,
         email, phone, purpose, valid_until, 'visitor' AS role
     FROM visitors";

if ($admin_uid_filter) {
    $admin_uid_safe = $mysqli->real_escape_string($admin_uid_filter);
    $sql = "SELECT
                p.uid,
                MIN(p.name) AS name,
                MIN(p.student_id) AS student_id,
                MIN(p.faculty_id) AS faculty_id,
                MIN(p.staff_id) AS staff_id,
                MIN(p.course) AS course,
                MIN(p.school_year) AS school_year,
                MIN(p.section) AS section,
                MIN(p.department) AS department,
                MIN(p.email) AS email,
                MIN(p.phone) AS phone,
                MIN(p.purpose) AS purpose,
                MIN(p.valid_until) AS valid_until,
                MIN(p.role) AS role,
                s.admin_uid,
                COALESCE(a.name, '') AS admin_name,
                COUNT(*) AS scan_count
            FROM (" . $peopleSql . ") p
            JOIN scans s ON s.uid = p.uid
            LEFT JOIN admins a ON s.admin_uid = a.uid
            WHERE s.admin_uid = '" . $admin_uid_safe . "'
            GROUP BY p.uid, s.admin_uid, a.name
            ORDER BY name ASC";
} else {
    $sql = "SELECT
                p.uid,
                MIN(p.name) AS name,
                MIN(p.student_id) AS student_id,
                MIN(p.faculty_id) AS faculty_id,
                MIN(p.staff_id) AS staff_id,
                MIN(p.course) AS course,
                MIN(p.school_year) AS school_year,
                MIN(p.section) AS section,
                MIN(p.department) AS department,
                MIN(p.email) AS email,
                MIN(p.phone) AS phone,
                MIN(p.purpose) AS purpose,
                MIN(p.valid_until) AS valid_until,
                MIN(p.role) AS role,
                s.admin_uid,
                COALESCE(a.name, '') AS admin_name,
                COUNT(*) AS scan_count
            FROM (" . $peopleSql . ") p
            JOIN scans s ON s.uid = p.uid
            LEFT JOIN admins a ON s.admin_uid = a.uid
            WHERE s.admin_uid IS NOT NULL AND s.admin_uid <> ''
            GROUP BY p.uid, s.admin_uid, a.name
            ORDER BY name ASC";
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
