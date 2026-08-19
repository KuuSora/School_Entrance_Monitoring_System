<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';

$uid = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = isset($_POST['uid']) ? trim($_POST['uid']) : '';
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $uid = isset($_GET['uid']) ? trim($_GET['uid']) : '';
}

if ($uid === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing uid']);
    exit;
}

if (!preg_match('/^[0-9A-Fa-f:]+$/', $uid)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid uid format']);
    exit;
}

$uid_norm = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $uid));

$stmt_admin = $mysqli->prepare("SELECT uid FROM admins WHERE REPLACE(UPPER(uid), ':', '') = ? LIMIT 1");
if (!$stmt_admin) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Query preparation failed']);
    exit;
}
$stmt_admin->bind_param('s', $uid_norm);
$stmt_admin->execute();
$res_admin = $stmt_admin->get_result();
$admin = $res_admin ? $res_admin->fetch_assoc() : null;
$stmt_admin->close();

if ($admin) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Admin card']);
    exit;
}

$stmt = $mysqli->prepare(
    "SELECT uid FROM students WHERE REPLACE(UPPER(uid), ':', '') = ?
     UNION ALL
     SELECT uid FROM faculty WHERE REPLACE(UPPER(uid), ':', '') = ?
     UNION ALL
     SELECT uid FROM staff WHERE REPLACE(UPPER(uid), ':', '') = ?
     UNION ALL
     SELECT uid FROM visitors WHERE REPLACE(UPPER(uid), ':', '') = ?
     LIMIT 1"
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Query preparation failed']);
    exit;
}
$stmt->bind_param('ssss', $uid_norm, $uid_norm, $uid_norm, $uid_norm);
$stmt->execute();
$res = $stmt->get_result();
$found = $res ? $res->fetch_assoc() : null;
$stmt->close();

if ($found) {
    echo json_encode(['ok' => false, 'error' => 'Already registered']);
    exit;
}

$signal_file = __DIR__ . '/register_scan_signal.json';
$data = [
    'uid' => $uid,
    'created_at' => date('Y-m-d H:i:s'),
    'ts' => time()
];
@file_put_contents($signal_file, json_encode($data));

echo json_encode(['ok' => true]);
