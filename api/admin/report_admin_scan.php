<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../system/db.php';

function normalize_uid_value(string $uid): string {
    return strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $uid));
}

// Debug info (non-blocking)
$stateFile = __DIR__ . '/../state/device_heartbeats.json';
$state = [];
if (is_file($stateFile)) { $d = json_decode((string)@file_get_contents($stateFile), true); if (is_array($d)) $state = $d; }
$mainLastSeen = (int)($state['main_system']['last_seen'] ?? 0);
$mainOnline = $mainLastSeen > 0 && (time() - $mainLastSeen) <= 90;

$uid = '';
$admin_uid = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = isset($_POST['uid']) ? trim($_POST['uid']) : '';
    $admin_uid = isset($_POST['admin_uid']) ? trim($_POST['admin_uid']) : '';
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $uid = isset($_GET['uid']) ? trim($_GET['uid']) : '';
    $admin_uid = isset($_GET['admin_uid']) ? trim($_GET['admin_uid']) : '';
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

// When admin_uid is provided, the scanned uid is a regular card being edited
// by an already-logged-in admin. Look up the admin by admin_uid instead.
$lookupUid = ($admin_uid !== '') ? $admin_uid : $uid;

if ($admin_uid !== '' && !preg_match('/^[0-9A-Fa-f:]+$/', $admin_uid)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid admin_uid format']);
    exit;
}

$lookup_norm = normalize_uid_value($lookupUid);
$name = '';

$stmt = $mysqli->prepare("SELECT uid, name FROM admins WHERE REPLACE(UPPER(uid), ':', '') = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Query preparation failed']);
    exit;
}
$stmt->bind_param('s', $lookup_norm);
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

$signal_file = __DIR__ . '/../state/admin_scan_signal.json';
$signal_data = [
    'uid' => $uid,
    'name' => $name,
    'created_at' => date('Y-m-d H:i:s'),
    'ts' => time()
];

if ($admin_uid !== '') {
    $signal_data['admin_uid'] = $admin_uid;
    $signal_data['edit_mode'] = true;
}

@file_put_contents($signal_file, json_encode($signal_data));

echo json_encode(['ok' => true, 'name' => $name, 'edit_mode' => $admin_uid !== '', 'debug' => ['main_system_online' => $mainOnline, 'last_seen' => $mainLastSeen > 0 ? date('c', $mainLastSeen) : null]]);
