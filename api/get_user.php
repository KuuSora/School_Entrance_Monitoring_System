<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$uid = isset($_GET['uid']) ? trim($_GET['uid']) : '';
if ($uid === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing uid']);
    exit;
}

$stmt = $mysqli->prepare(
    "SELECT uid, name, student_id, course, school_year, section,
         NULL AS faculty_id, NULL AS staff_id, NULL AS department,
         NULL AS purpose, NULL AS valid_until,
         phone, notes, 'student' AS role
     FROM students WHERE uid = ?
     UNION ALL
     SELECT uid, name, NULL, NULL, NULL, NULL,
         faculty_id, NULL, department,
         NULL, NULL,
         phone, notes, 'faculty' AS role
     FROM faculty WHERE uid = ?
     UNION ALL
     SELECT uid, name, NULL, NULL, NULL, NULL,
         NULL, staff_id, department,
         NULL, NULL,
         phone, notes, 'staff' AS role
     FROM staff WHERE uid = ?
     UNION ALL
     SELECT uid, name, NULL, NULL, NULL, NULL,
         NULL, NULL, NULL,
         purpose, valid_until,
         phone, notes, 'visitor' AS role
     FROM visitors WHERE uid = ?
     LIMIT 1"
);
$stmt->bind_param('ssss', $uid, $uid, $uid, $uid);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['ok' => false, 'error' => 'User not found']);
    exit;
}

echo json_encode(['ok' => true, 'data' => $user]);
