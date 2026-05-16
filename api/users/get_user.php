<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';

function tableHasColumn(mysqli $mysqli, string $table, string $column): bool {
    $tbl = $mysqli->real_escape_string($table);
    $col = $mysqli->real_escape_string($column);
    $res = $mysqli->query("SHOW COLUMNS FROM `{$tbl}` LIKE '{$col}'");
    if (!$res) {
        return false;
    }
    $has = $res->num_rows > 0;
    $res->free();
    return $has;
}

function columnOrNull(mysqli $mysqli, string $table, string $column, string $alias): string {
    if (tableHasColumn($mysqli, $table, $column)) {
        return "`{$column}` AS `{$alias}`";
    }
    return "NULL AS `{$alias}`";
}

$uid = isset($_GET['uid']) ? trim($_GET['uid']) : '';
if ($uid === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing uid']);
    exit;
}

$studentEmail = columnOrNull($mysqli, 'students', 'email', 'email');
$studentPhone = columnOrNull($mysqli, 'students', 'phone', 'phone');
$studentNotes = columnOrNull($mysqli, 'students', 'notes', 'notes');
$facultyEmail = columnOrNull($mysqli, 'faculty', 'email', 'email');
$facultyPhone = columnOrNull($mysqli, 'faculty', 'phone', 'phone');
$facultyNotes = columnOrNull($mysqli, 'faculty', 'notes', 'notes');
$staffEmail = columnOrNull($mysqli, 'staff', 'email', 'email');
$staffPhone = columnOrNull($mysqli, 'staff', 'phone', 'phone');
$staffNotes = columnOrNull($mysqli, 'staff', 'notes', 'notes');
$visitorEmail = columnOrNull($mysqli, 'visitors', 'email', 'email');
$visitorPhone = columnOrNull($mysqli, 'visitors', 'phone', 'phone');
$visitorNotes = columnOrNull($mysqli, 'visitors', 'notes', 'notes');

$sql = "SELECT uid, name, student_id, course, school_year, section,
         NULL AS faculty_id, NULL AS staff_id, NULL AS department,
         NULL AS purpose, NULL AS valid_until,
         {$studentEmail}, {$studentPhone}, {$studentNotes}, 'student' AS role
     FROM students WHERE uid = ?
     UNION ALL
     SELECT uid, name, NULL, NULL, NULL, NULL,
         faculty_id, NULL, department,
         NULL, NULL,
         {$facultyEmail}, {$facultyPhone}, {$facultyNotes}, 'faculty' AS role
     FROM faculty WHERE uid = ?
     UNION ALL
     SELECT uid, name, NULL, NULL, NULL, NULL,
         NULL, staff_id, department,
         NULL, NULL,
         {$staffEmail}, {$staffPhone}, {$staffNotes}, 'staff' AS role
     FROM staff WHERE uid = ?
     UNION ALL
     SELECT uid, name, NULL, NULL, NULL, NULL,
         NULL, NULL, NULL,
         purpose, valid_until,
         {$visitorEmail}, {$visitorPhone}, {$visitorNotes}, 'visitor' AS role
     FROM visitors WHERE uid = ?
     LIMIT 1";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Query preparation failed']);
    exit;
}
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
