<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';

function columnOrNull(mysqli $mysqli, string $table, string $column, string $alias): string {
    $tbl = $mysqli->real_escape_string($table);
    $col = $mysqli->real_escape_string($column);
    $res = $mysqli->query("SHOW COLUMNS FROM `{$tbl}` LIKE '{$col}'");
    if (!$res) {
        return "NULL AS `{$alias}`";
    }
    $has = $res->num_rows > 0;
    $res->free();
    return $has ? "`{$column}` AS `{$alias}`" : "NULL AS `{$alias}`";
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$roleFilter = isset($_GET['role']) ? trim($_GET['role']) : '';
$adminUidFilter = isset($_GET['admin_uid']) ? trim($_GET['admin_uid']) : null;
if ($adminUidFilter === '') {
    $adminUidFilter = null;
}

if ($query === '') {
    echo json_encode(['ok' => true, 'data' => []]);
    exit;
}

$searchSafe = $mysqli->real_escape_string($query);
$roleSafe = $roleFilter !== '' ? $mysqli->real_escape_string($roleFilter) : '';

$roleCondition = '';
if (in_array($roleFilter, ['student', 'faculty', 'staff', 'visitor', 'admin'], true)) {
    $roleCondition = "AND p.role = '{$roleSafe}'";
}

$adminJoin = '';
$adminWhere = '';
if ($adminUidFilter !== null) {
    $adminUidSafe = $mysqli->real_escape_string($adminUidFilter);
    $adminJoin = "JOIN scans s ON s.uid = p.uid";
    $adminWhere = "AND s.admin_uid = '{$adminUidSafe}'";
}

$studentEmail = columnOrNull($mysqli, 'students', 'email', 'email');
$facultyEmail = columnOrNull($mysqli, 'faculty', 'email', 'email');
$staffEmail = columnOrNull($mysqli, 'staff', 'email', 'email');
$visitorEmail = columnOrNull($mysqli, 'visitors', 'email', 'email');

$studentPhone = columnOrNull($mysqli, 'students', 'phone', 'phone');
$facultyPhone = columnOrNull($mysqli, 'faculty', 'phone', 'phone');
$staffPhone = columnOrNull($mysqli, 'staff', 'phone', 'phone');
$visitorPhone = columnOrNull($mysqli, 'visitors', 'phone', 'phone');

$studentPhoto = columnOrNull($mysqli, 'students', 'photo', 'photo');
$facultyPhoto = columnOrNull($mysqli, 'faculty', 'photo', 'photo');
$staffPhoto = columnOrNull($mysqli, 'staff', 'photo', 'photo');
$visitorPhoto = columnOrNull($mysqli, 'visitors', 'photo', 'photo');

$unionSql = "(
    SELECT uid, name, student_id AS identifier, course, school_year, section, NULL AS department, {$studentEmail}, {$studentPhone}, NULL AS purpose, NULL AS valid_until, 'student' AS role, {$studentPhoto}
    FROM students
    UNION ALL
    SELECT uid, name, faculty_id AS identifier, NULL AS course, NULL AS school_year, NULL AS section, department, {$facultyEmail}, {$facultyPhone}, NULL AS purpose, NULL AS valid_until, 'faculty' AS role, {$facultyPhoto}
    FROM faculty
    UNION ALL
    SELECT uid, name, staff_id AS identifier, NULL AS course, NULL AS school_year, NULL AS section, department, {$staffEmail}, {$staffPhone}, NULL AS purpose, NULL AS valid_until, 'staff' AS role, {$staffPhoto}
    FROM staff
    UNION ALL
    SELECT uid, name, purpose AS identifier, NULL AS course, NULL AS school_year, NULL AS section, NULL AS department, {$visitorEmail}, {$visitorPhone}, purpose, valid_until, 'visitor' AS role, {$visitorPhoto}
    FROM visitors
) p";

$sql = "SELECT p.*
    FROM {$unionSql}
    {$adminJoin}
    WHERE (p.name LIKE '%{$searchSafe}%' OR p.uid LIKE '%{$searchSafe}%' OR p.identifier LIKE '%{$searchSafe}%')
    {$roleCondition}
    {$adminWhere}
    ORDER BY p.name ASC
    LIMIT 50";

try {
    $result = $mysqli->query($sql);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Search query failed']);
    exit;
}

$rows = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $mysqli->error]);
    exit;
}

echo json_encode(['ok' => true, 'data' => $rows]);