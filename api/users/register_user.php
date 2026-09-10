<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';

function tableHasColumn(mysqli $mysqli, string $table, string $column): bool {
    $tbl = $mysqli->real_escape_string($table);
    $col = $mysqli->real_escape_string($column);
    $res = $mysqli->query("SHOW COLUMNS FROM `{$tbl}` LIKE '{$col}'");
    if (!$res) return false;
    $has = $res->num_rows > 0;
    $res->free();
    return $has;
}

function insertOrUpdateRole(mysqli $mysqli, string $table, array $baseColumns, array $baseValues, array $updatableColumns): bool {
    $columns = $baseColumns;
    $values = $baseValues;
    $updateParts = [];

    foreach ($updatableColumns as $column) {
        $updateParts[] = "`{$column}` = VALUES(`{$column}`)";
    }

    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $columnList = '`' . implode('`, `', $columns) . '`';
    $sql = "INSERT INTO `{$table}` ({$columnList}) VALUES ({$placeholders}) ON DUPLICATE KEY UPDATE " . implode(', ', $updateParts);

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $types = '';
    foreach ($columns as $column) {
        $types .= 's';
    }

    $bindValues = [];
    foreach ($columns as $column) {
        $bindValues[] = $baseValues[$column] ?? null;
    }

    $bindArgs = [$types];
    foreach ($bindValues as $index => $value) {
        $bindArgs[$index + 1] = &$bindValues[$index];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindArgs);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$uid = isset($_POST['uid']) ? trim($_POST['uid']) : '';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$studentId = isset($_POST['student_id']) ? trim($_POST['student_id']) : '';
$course = isset($_POST['course']) ? trim($_POST['course']) : '';
$schoolYear = isset($_POST['school_year']) ? trim($_POST['school_year']) : '';
$section = isset($_POST['section']) ? trim($_POST['section']) : '';
$facultyId = isset($_POST['faculty_id']) ? trim($_POST['faculty_id']) : '';
$staffId = isset($_POST['staff_id']) ? trim($_POST['staff_id']) : '';
$department = isset($_POST['department']) ? trim($_POST['department']) : '';
$purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : '';
$validUntil = isset($_POST['valid_until']) ? trim($_POST['valid_until']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
$role = isset($_POST['role']) ? trim($_POST['role']) : 'student';
$validUntil = $validUntil === '' ? null : $validUntil;

$photoFileName = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($ext, $allowed, true)) {
        $safeExt = in_array($ext, ['jpeg', 'jpg'], true) ? 'jpg' : $ext;
        $photoFileName = preg_replace('/[^A-Za-z0-9_\-]/', '', $uid) . '_' . time() . '.' . $safeExt;
        $destination = $uploadDir . $photoFileName;
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
            $photoFileName = null;
        }
    }
}

if ($uid === '' || $name === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing required fields (uid, name)']);
    exit;
}

if (!preg_match('/^[0-9A-Fa-f:]+$/', $uid)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid uid format']);
    exit;
}

// Upsert registration data by role.
$role = strtolower($role);
$ok = false;

if ($role === 'student') {
    if ($studentId === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing student_id for student']);
        exit;
    }
    $hasEmail = tableHasColumn($mysqli, 'students', 'email');
    $hasPhone = tableHasColumn($mysqli, 'students', 'phone');
    $hasNotes = tableHasColumn($mysqli, 'students', 'notes');
    $hasPhoto = tableHasColumn($mysqli, 'students', 'photo');
    $baseColumns = ['uid', 'name', 'student_id', 'course', 'school_year', 'section'];
    $baseValues = [
        'uid' => $uid,
        'name' => $name,
        'student_id' => $studentId,
        'course' => $course,
        'school_year' => $schoolYear,
        'section' => $section,
        'email' => $email,
        'phone' => $phone,
        'notes' => $notes,
        'photo' => $photoFileName,
    ];
    if ($hasEmail) $baseColumns[] = 'email';
    if ($hasPhone) $baseColumns[] = 'phone';
    if ($hasNotes) $baseColumns[] = 'notes';
    if ($hasPhoto) $baseColumns[] = 'photo';
    $updatableColumns = array_values(array_intersect(['name', 'student_id', 'course', 'school_year', 'section'], $baseColumns));
    if ($hasEmail) $updatableColumns[] = 'email';
    if ($hasPhone) $updatableColumns[] = 'phone';
    if ($hasNotes) $updatableColumns[] = 'notes';
    if ($hasPhoto) $updatableColumns[] = 'photo';
    $ok = insertOrUpdateRole($mysqli, 'students', $baseColumns, $baseValues, $updatableColumns);
} else if ($role === 'faculty') {
    if ($facultyId === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing faculty_id for faculty']);
        exit;
    }
    $hasEmail = tableHasColumn($mysqli, 'faculty', 'email');
    $hasPhone = tableHasColumn($mysqli, 'faculty', 'phone');
    $hasNotes = tableHasColumn($mysqli, 'faculty', 'notes');
    $hasPhoto = tableHasColumn($mysqli, 'faculty', 'photo');
    $baseColumns = ['uid', 'name', 'faculty_id', 'department'];
    $baseValues = [
        'uid' => $uid,
        'name' => $name,
        'faculty_id' => $facultyId,
        'department' => $department,
        'email' => $email,
        'phone' => $phone,
        'notes' => $notes,
        'photo' => $photoFileName,
    ];
    if ($hasEmail) $baseColumns[] = 'email';
    if ($hasPhone) $baseColumns[] = 'phone';
    if ($hasNotes) $baseColumns[] = 'notes';
    if ($hasPhoto) $baseColumns[] = 'photo';
    $updatableColumns = array_values(array_intersect(['name', 'faculty_id', 'department'], $baseColumns));
    if ($hasEmail) $updatableColumns[] = 'email';
    if ($hasPhone) $updatableColumns[] = 'phone';
    if ($hasNotes) $updatableColumns[] = 'notes';
    if ($hasPhoto) $updatableColumns[] = 'photo';
    $ok = insertOrUpdateRole($mysqli, 'faculty', $baseColumns, $baseValues, $updatableColumns);
} else if ($role === 'staff') {
    if ($staffId === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing staff_id for staff']);
        exit;
    }
    $hasEmail = tableHasColumn($mysqli, 'staff', 'email');
    $hasPhone = tableHasColumn($mysqli, 'staff', 'phone');
    $hasNotes = tableHasColumn($mysqli, 'staff', 'notes');
    $hasPhoto = tableHasColumn($mysqli, 'staff', 'photo');
    $baseColumns = ['uid', 'name', 'staff_id', 'department'];
    $baseValues = [
        'uid' => $uid,
        'name' => $name,
        'staff_id' => $staffId,
        'department' => $department,
        'email' => $email,
        'phone' => $phone,
        'notes' => $notes,
        'photo' => $photoFileName,
    ];
    if ($hasEmail) $baseColumns[] = 'email';
    if ($hasPhone) $baseColumns[] = 'phone';
    if ($hasNotes) $baseColumns[] = 'notes';
    if ($hasPhoto) $baseColumns[] = 'photo';
    $updatableColumns = array_values(array_intersect(['name', 'staff_id', 'department'], $baseColumns));
    if ($hasEmail) $updatableColumns[] = 'email';
    if ($hasPhone) $updatableColumns[] = 'phone';
    if ($hasNotes) $updatableColumns[] = 'notes';
    if ($hasPhoto) $updatableColumns[] = 'photo';
    $ok = insertOrUpdateRole($mysqli, 'staff', $baseColumns, $baseValues, $updatableColumns);
} else if ($role === 'visitor') {
    $hasEmail = tableHasColumn($mysqli, 'visitors', 'email');
    $hasPhone = tableHasColumn($mysqli, 'visitors', 'phone');
    $hasNotes = tableHasColumn($mysqli, 'visitors', 'notes');
    $hasPhoto = tableHasColumn($mysqli, 'visitors', 'photo');
    $baseColumns = ['uid', 'name', 'purpose', 'valid_until'];
    $baseValues = [
        'uid' => $uid,
        'name' => $name,
        'purpose' => $purpose,
        'valid_until' => $validUntil,
        'email' => $email,
        'phone' => $phone,
        'notes' => $notes,
        'photo' => $photoFileName,
    ];
    if ($hasEmail) $baseColumns[] = 'email';
    if ($hasPhone) $baseColumns[] = 'phone';
    if ($hasNotes) $baseColumns[] = 'notes';
    if ($hasPhoto) $baseColumns[] = 'photo';
    $updatableColumns = array_values(array_intersect(['name', 'purpose', 'valid_until'], $baseColumns));
    if ($hasEmail) $updatableColumns[] = 'email';
    if ($hasPhone) $updatableColumns[] = 'phone';
    if ($hasNotes) $updatableColumns[] = 'notes';
    if ($hasPhoto) $updatableColumns[] = 'photo';
    $ok = insertOrUpdateRole($mysqli, 'visitors', $baseColumns, $baseValues, $updatableColumns);
} else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid role']);
    exit;
}

echo json_encode(['ok' => $ok]);
