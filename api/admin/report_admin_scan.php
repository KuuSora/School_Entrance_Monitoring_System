<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';

$uid = '';
$MASTER_UID = '97:2A:59:06';
$MASTER_UID_NORM = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $MASTER_UID));
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
$name = '';

if ($uid_norm === $MASTER_UID_NORM) {
    $name = 'Master Card';
} else {
    $stmt = $mysqli->prepare("SELECT uid, name FROM admins WHERE REPLACE(UPPER(uid), ':', '') = ? LIMIT 1");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Query preparation failed']);
        exit;
    }
    $stmt->bind_param('s', $uid_norm);
    $stmt->execute();
    $res = $stmt->get_result();
    $admin = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$admin) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Not authorized']);
        exit;
    }

    $name = $admin['name'];
}

$signal_file = __DIR__ . '/../state/admin_scan_signal.json';
$data = [
    'uid' => $uid,
    'name' => $name,
    'created_at' => date('Y-m-d H:i:s'),
    'ts' => time()
];
@file_put_contents($signal_file, json_encode($data));

echo json_encode(['ok' => true, 'name' => $name]);
