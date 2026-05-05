<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$result = $mysqli->query(
    "SELECT uid, name, student_id, NULL AS faculty_id, NULL AS staff_id,
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
     FROM visitors
     ORDER BY name ASC"
);

$rows = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();
}

echo json_encode(['ok' => true, 'data' => $rows]);
