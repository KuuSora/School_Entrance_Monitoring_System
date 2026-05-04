<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

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
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
$role = isset($_POST['role']) ? trim($_POST['role']) : 'student';
$validUntil = $validUntil === '' ? null : $validUntil;

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
    $stmt = $mysqli->prepare(
        'INSERT INTO students (uid, name, student_id, course, school_year, section, phone, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           name = VALUES(name),
           student_id = VALUES(student_id),
           course = VALUES(course),
           school_year = VALUES(school_year),
           section = VALUES(section),
           phone = VALUES(phone),
           notes = VALUES(notes)'
    );
    $stmt->bind_param('ssssssss', $uid, $name, $studentId, $course, $schoolYear, $section, $phone, $notes);
    $ok = $stmt->execute();
    $stmt->close();
} else if ($role === 'faculty') {
    if ($facultyId === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing faculty_id for faculty']);
        exit;
    }
    $stmt = $mysqli->prepare(
        'INSERT INTO faculty (uid, name, faculty_id, department, phone, notes)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           name = VALUES(name),
           faculty_id = VALUES(faculty_id),
           department = VALUES(department),
           phone = VALUES(phone),
           notes = VALUES(notes)'
    );
    $stmt->bind_param('ssssss', $uid, $name, $facultyId, $department, $phone, $notes);
    $ok = $stmt->execute();
    $stmt->close();
} else if ($role === 'staff') {
    if ($staffId === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing staff_id for staff']);
        exit;
    }
    $stmt = $mysqli->prepare(
        'INSERT INTO staff (uid, name, staff_id, department, phone, notes)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           name = VALUES(name),
           staff_id = VALUES(staff_id),
           department = VALUES(department),
           phone = VALUES(phone),
           notes = VALUES(notes)'
    );
    $stmt->bind_param('ssssss', $uid, $name, $staffId, $department, $phone, $notes);
    $ok = $stmt->execute();
    $stmt->close();
} else if ($role === 'visitor') {
    $stmt = $mysqli->prepare(
        'INSERT INTO visitors (uid, name, purpose, valid_until, phone, notes)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           name = VALUES(name),
           purpose = VALUES(purpose),
           valid_until = VALUES(valid_until),
           phone = VALUES(phone),
           notes = VALUES(notes)'
    );
    $stmt->bind_param('ssssss', $uid, $name, $purpose, $validUntil, $phone, $notes);
    $ok = $stmt->execute();
    $stmt->close();
} else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid role']);
    exit;
}

echo json_encode(['ok' => $ok]);
