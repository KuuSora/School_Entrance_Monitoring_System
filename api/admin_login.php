<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

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

if ($uid_norm === $MASTER_UID_NORM) {
    session_start();
    $_SESSION['admin_uid'] = $uid;
    $_SESSION['admin_name'] = 'Master Card';
    // clear any auto-login block
    $block_file = __DIR__ . '/auto_login_block.json';
    if (file_exists($block_file)) @unlink($block_file);

    echo json_encode(['ok' => true, 'name' => 'Master Card']);
    exit;
}

$stmt = $mysqli->prepare("SELECT uid, name FROM admins WHERE REPLACE(UPPER(uid), ':', '') = ? LIMIT 1");
$stmt->bind_param('s', $uid_norm);
$stmt->execute();
$res = $stmt->get_result();
$admin = $res->fetch_assoc();
$stmt->close();

if (!$admin) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Not authorized']);
    exit;
}

session_start();
$_SESSION['admin_uid'] = $admin['uid'];
$_SESSION['admin_name'] = $admin['name'];

// clear any auto-login block
$block_file = __DIR__ . '/auto_login_block.json';
if (file_exists($block_file)) @unlink($block_file);

echo json_encode(['ok' => true, 'name' => $admin['name']]);
